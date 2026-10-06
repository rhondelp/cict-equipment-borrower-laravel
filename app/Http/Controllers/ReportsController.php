<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The Reports screen: the activity log, filtered.
 *
 * Every figure and row on the page comes from one query built by
 * ActivityLog::filter(), from filters read out of the query string. Exports
 * will read the same filters through resolveFilters(), so the screen, the CSV,
 * the PDF and the printout cannot disagree about what "this report" contains.
 *
 * Nothing is cached: the page reads the log on every request, so it shows an
 * action the moment it is recorded.
 */
class ReportsController extends Controller
{
    public const PER_PAGE = 50;

    /** The default range, ending today: today and the 29 days before it. */
    public const DEFAULT_DAYS = 30;

    public function index(Request $request)
    {
        $resolved = $this->resolveFilters($request);
        $filters = $resolved['filters'];

        $query = ActivityLog::filter($filters);

        // One grouped COUNT feeds the summary strip and the paginator's total,
        // so the figures above the table are the rows in it, counted once.
        $counts = (clone $query)->selectRaw('type, COUNT(*) as aggregate')->groupBy('type')
            ->pluck('aggregate', 'type')->map(fn ($n) => (int) $n);
        $total = $counts->sum();

        $activities = $query->newestFirst()
            ->paginate(self::PER_PAGE, ['*'], 'page', null, $total)
            ->withQueryString();

        return response()->view('admin.reports', [
            'activities' => $activities,
            'summary' => $this->summary($counts, $filters),
            'resolved' => $resolved,
            'applied' => $this->appliedFilters($filters),
            'anythingRecorded' => $total > 0 || ActivityLog::query()->exists(),
            'earliest' => ActivityLog::query()->min('occurred_at'),
            'equipmentOptions' => Equipment::orderBy('equipment_name')->get(['id', 'equipment_name', 'retired_at']),
            'userOptions' => User::orderBy('name')->get(['id', 'name', 'user_type', 'deactivated_at']),
            'statusOptions' => ActivityLog::query()->whereNotNull('status_to')->distinct()->orderBy('status_to')->pluck('status_to'),
            'typeGroups' => collect(ActivityLog::GROUPS)->mapWithKeys(fn ($group) => [
                $group => collect(ActivityLog::typesInGroup($group))
                    ->mapWithKeys(fn ($type) => [$type => ActivityLog::TYPES[$type]['label']]),
            ]),
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * The report's filters, read from the query string and checked.
     *
     * A bad value never fails the page. It is dropped, an error is returned
     * for the form to show beside its field, and the report runs without it —
     * for a bad date range, over the default range instead.
     *
     * @return array{filters: array, errors: array<string, string>, defaultRange: bool}
     */
    public function resolveFilters(Request $request): array
    {
        $input = collect($request->query())
            ->only(['from', 'to', 'type', 'equipment_id', 'user_id', 'status', 'q'])
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $validTypes = array_merge(array_keys(ActivityLog::TYPES), ActivityLog::GROUPS);

        $validator = Validator::make($input, [
            'from' => 'date_format:Y-m-d',
            'to' => 'date_format:Y-m-d',
            'type' => ['string', Rule::in($validTypes)],
            'equipment_id' => 'integer|min:1',
            'user_id' => 'integer|min:1',
            'status' => 'string|max:30',
            'q' => 'string|max:100',
        ], [
            'from.date_format' => 'Enter the start date as a date.',
            'to.date_format' => 'Enter the end date as a date.',
            'type.in' => 'That activity type is not one the log records.',
            'q.max' => 'Keep the search to 100 characters.',
        ]);

        $errors = collect($validator->errors()->messages())->map(fn ($messages) => $messages[0])->all();
        $clean = array_diff_key($input, $errors);

        if (isset($clean['from'], $clean['to']) && $clean['from'] > $clean['to']) {
            $errors['range'] = 'The start date is after the end date, so the report shows the last '
                .self::DEFAULT_DAYS.' days instead.';
            unset($clean['from'], $clean['to']);
        }

        $defaultRange = ! isset($clean['from']) && ! isset($clean['to']);
        $to = isset($clean['to']) ? Carbon::parse($clean['to']) : today();
        $from = isset($clean['from']) ? Carbon::parse($clean['from']) : $to->copy()->subDays(self::DEFAULT_DAYS - 1);

        return [
            'filters' => array_merge($clean, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
            'errors' => $errors,
            'defaultRange' => $defaultRange,
        ];
    }

    /** Four figures from the grouped counts; no further queries. */
    private function summary($counts, array $filters): array
    {
        $count = fn (string ...$types) => collect($types)->sum(fn ($type) => $counts->get($type, 0));
        $from = Carbon::parse($filters['from']);
        $to = Carbon::parse($filters['to']);
        $days = (int) $from->diffInDays($to) + 1;

        $lent = $count('loan_created');
        $issued = $count('loan_issued');
        $approved = $count('request_approved');
        $declined = $count('request_declined');

        return [
            [
                'label' => 'Activities', 'value' => number_format($counts->sum()), 'unit' => '',
                'sub' => $from->format($from->isSameYear($to) ? 'M j' : 'M j, Y').' – '.$to->format('M j, Y')
                    .' · '.$days.' '.str('day')->plural($days),
            ],
            [
                'label' => 'Loans recorded', 'value' => number_format($lent + $issued), 'unit' => '',
                'sub' => $issued > 0 ? $lent.' lent · '.$issued.' issued for good' : 'Lent from the counter or by approval',
            ],
            [
                'label' => 'Returns', 'value' => number_format($count('loan_checked_in')), 'unit' => '',
                'sub' => 'Checked back in',
            ],
            [
                'label' => 'Requests decided', 'value' => number_format($approved + $declined), 'unit' => '',
                'sub' => $approved.' approved · '.$declined.' declined',
            ],
        ];
    }

    /**
     * The filters in words, for the empty state and the line above the
     * table: what this report is a report of.
     *
     * @return array<string, string>
     */
    private function appliedFilters(array $filters): array
    {
        $applied = [
            'Dates' => Carbon::parse($filters['from'])->format('M j, Y').' – '.Carbon::parse($filters['to'])->format('M j, Y'),
        ];

        if (isset($filters['type'])) {
            $applied['Activity'] = isset(ActivityLog::TYPES[$filters['type']])
                ? ActivityLog::TYPES[$filters['type']]['label']
                : 'All '.strtolower($filters['type']);
        }
        if (isset($filters['equipment_id'])) {
            $applied['Equipment'] = Equipment::find($filters['equipment_id'])?->equipment_name ?? 'Item #'.$filters['equipment_id'];
        }
        if (isset($filters['user_id'])) {
            $applied['Person'] = User::find($filters['user_id'])?->name ?? 'Account #'.$filters['user_id'];
        }
        if (isset($filters['status'])) {
            $applied['Status'] = $filters['status'];
        }
        if (isset($filters['q'])) {
            $applied['Search'] = '“'.$filters['q'].'”';
        }

        return $applied;
    }
}
