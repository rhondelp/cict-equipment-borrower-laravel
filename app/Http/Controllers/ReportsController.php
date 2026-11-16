<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReportsController extends Controller
{
    public const PER_PAGE = 50;

    public const DEFAULT_DAYS = 30;

    public const MAX_PAGED_ROWS = 2000;

    public const TITLE = 'Activity and Transactions Report';

    public const PDF_TABLE_ROWS = 100;

    private const PDF_LOGO_PX = 192;

    public const REPORTS = [
        'borrowers' => ['tab' => 'Borrower Report', 'title' => 'Borrower Report', 'dated' => true, 'noun' => 'transaction', 'file' => 'borrower-report'],
        'inventory' => ['tab' => 'Inventory Report', 'title' => 'Inventory Report', 'dated' => false, 'noun' => 'item', 'file' => 'inventory-report'],
        'not-returned' => ['tab' => 'Not Yet Returned', 'title' => 'List of Borrowers Not Yet Returned', 'dated' => false, 'noun' => 'borrow', 'file' => 'not-yet-returned'],
        'most-borrowed' => ['tab' => 'Most Borrowed', 'title' => 'Most Borrowed Equipment', 'dated' => true, 'noun' => 'item', 'file' => 'most-borrowed'],
        'defective' => ['tab' => 'Defective Items', 'title' => 'List of Defective Items', 'dated' => false, 'optional_range' => true, 'noun' => 'defect', 'file' => 'defective-items'],
    ];

    public const COLUMNS = [
        'borrowers' => [
            ['label' => 'Date & Time Borrowed', 'width' => 18],
            ['label' => 'Name of Borrower', 'width' => 22],
            ['label' => 'Item Borrowed', 'width' => 28],
            ['label' => 'Returned Date & Time', 'width' => 18],
            ['label' => 'Status', 'width' => 14],
        ],
        'inventory' => [
            ['label' => 'Quantity', 'width' => 10, 'num' => true],
            ['label' => 'Unit', 'width' => 10],
            ['label' => 'Item Description', 'width' => 58],
            ['label' => 'Status', 'width' => 22],
        ],
        'not-returned' => [
            ['label' => 'Name of Borrower', 'width' => 22],
            ['label' => 'Course & Year', 'width' => 15],
            ['label' => 'Item Borrowed', 'width' => 25],
            ['label' => 'Date Borrowed', 'width' => 14],
            ['label' => 'Due Date & Time', 'width' => 24],
        ],
        'most-borrowed' => [
            ['label' => 'Rank', 'width' => 8, 'num' => true],
            ['label' => 'Item Description', 'width' => 52],
            ['label' => 'Times Borrowed', 'width' => 20, 'num' => true],
            ['label' => 'Total Quantity', 'width' => 20, 'num' => true],
        ],
        'defective' => [
            ['label' => 'Date & Time Reported', 'width' => 18],
            ['label' => 'Quantity', 'width' => 9, 'num' => true],
            ['label' => 'Unit', 'width' => 8],
            ['label' => 'Item Description', 'width' => 30],
            ['label' => 'Item Defect', 'width' => 35],
        ],
    ];

    public const TOP_LIMITS = [10, 25, 50];

    public const INVENTORY_STATUSES = ['serviceable' => 'Serviceable', 'non-serviceable' => 'Non-serviceable'];

    public function index(Request $request)
    {
        if (($report = $this->requestedReport($request)) !== 'activity') {
            return $this->officialIndex($request, $report);
        }

        $resolved = $this->resolveFilters($request);
        $filters = $resolved['filters'];

        $query = $this->reportQuery($filters);

        $counts = (clone $query)->selectRaw('type, COUNT(*) as aggregate')->groupBy('type')
            ->pluck('aggregate', 'type')->map(fn ($n) => (int) $n);
        $total = $counts->sum();

        $activities = $query->newestFirst()
            ->paginate(self::PER_PAGE, ['*'], 'page', null, $total)
            ->withQueryString();

        return response()->view('admin.reports', [
            'report' => 'activity',
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

    public function exportCsv(Request $request)
    {
        if (($report = $this->requestedReport($request)) !== 'activity') {
            return $this->officialCsv($request, $report);
        }

        $filters = $this->resolveFilters($request)['filters'];
        $query = $this->reportQuery($filters)->newestFirst();

        return response()->streamDownload(function () use ($query, $filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, [
                'Date', 'Time', 'Activity type', 'Activity group', 'Done by', 'Done by role', 'Equipment',
                'Affected person', 'Quantity', 'Status from', 'Status to', 'Details', 'Borrow ID',
            ], eol: "\r\n");

            $rows = 0;
            foreach ($query->cursor() as $activity) {
                fputcsv($out, array_map([self::class, 'csvCell'], [
                    $activity->occurred_at->format('Y-m-d'),
                    $activity->occurred_at->format('H:i'),
                    $activity->typeLabel(),
                    $activity->groupLabel(),
                    $activity->actorLabel(),
                    $activity->actor_role,
                    $activity->equipment_name,
                    $activity->subject_name,
                    $activity->quantity,
                    $activity->status_from,
                    $activity->status_to,
                    $activity->details,
                    $activity->borrow_transaction_id,
                ]), eol: "\r\n");
                $rows++;
            }

            fclose($out);

            $this->recordExport('CSV', $rows, $filters);
        }, $this->filename($filters, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function exportPdf(Request $request)
    {
        if (($official = $this->requestedReport($request)) !== 'activity') {
            return $this->officialPdf($request, $official);
        }

        $report = $this->pagedReport($request);
        if (! is_array($report)) {
            return $report;
        }

        $pdf = $this->renderPdf('admin.reports.pdf', $report, self::TITLE);

        $this->recordExport('PDF', $report['activities']->count(), $report['filters']);

        return $pdf->download($this->filename($report['filters'], 'pdf'));
    }

    private function renderPdf(string $view, array $data, string $footerTitle)
    {
        if ((int) ini_get('max_execution_time') !== 0 && (int) ini_get('max_execution_time') < 120) {
            set_time_limit(120);
        }
        $memory = ini_get('memory_limit');
        if ($memory !== '-1' && $this->bytes($memory) < $this->bytes('1024M')) {
            ini_set('memory_limit', '1024M');
        }

        $pdf = Pdf::setOption([
            'enable_remote' => false,
            'enable_php' => false,
            'enable_javascript' => false,
            'chroot' => public_path('images'),
            'allowed_protocols' => ['data://' => ['rules' => []], 'file://' => ['rules' => []]],
            'default_font' => 'DejaVu Sans',
            'enable_font_subsetting' => true,
        ])->loadView($view, $data + [
            'logoSrc' => $this->pdfLogo(),
        ])->setPaper('a4', 'landscape');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 120, $canvas->get_height() - 28,
            'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.28, 0.33, 0.41]);
        $canvas->page_text(34, $canvas->get_height() - 28,
            'CICT Equipment Borrower System · '.$footerTitle, $font, 8, [0.28, 0.33, 0.41]);

        return $pdf;
    }

    public function print(Request $request)
    {
        if (($official = $this->requestedReport($request)) !== 'activity') {
            return $this->officialPrint($request, $official);
        }

        $report = $this->pagedReport($request);
        if (! is_array($report)) {
            return $report;
        }

        $this->recordExport('print view', $report['activities']->count(), $report['filters']);

        return response()->view('admin.reports.print', $report + [
            'logoSrc' => asset('images/logo.png'),
            'backUrl' => route('admin.reports', $request->except('page')),
        ])->header('Cache-Control', 'no-store, private');
    }

    private function pagedReport(Request $request): array|\Illuminate\Http\RedirectResponse
    {
        $resolved = $this->resolveFilters($request);
        $filters = $resolved['filters'];
        $query = $this->reportQuery($filters);

        $total = (clone $query)->count();
        if ($total > self::MAX_PAGED_ROWS) {
            return redirect()->route('admin.reports', $request->except('page'))->with('error',
                number_format($total).' activities match these filters — too many to lay out on paper (the limit is '
                .number_format(self::MAX_PAGED_ROWS).'). Narrow the dates or filters, or use Export CSV, which has no limit.');
        }

        $user = $request->user();

        return [
            'activities' => $query->newestFirst()->get(),
            'filters' => $filters,
            'meta' => [
                'title' => self::TITLE,
                'range' => $this->rangeWords($filters),
                'filters' => $this->filterWords($filters),
                'generated_at' => now()->format('M j, Y, g:i A'),
                'generated_by' => $user->name.' ('.$user->user_type.')',
                'total' => $total,
            ],
        ];
    }

    private function pdfLogo(): string
    {
        $path = public_path('images/logo.png');

        if (! function_exists('imagecreatefrompng') || ! is_file($path)) {
            return $path;
        }

        $source = @imagecreatefrompng($path);
        if (! $source) {
            return $path;
        }

        $width = self::PDF_LOGO_PX;
        $height = (int) round(imagesy($source) * $width / imagesx($source));
        $small = imagecreatetruecolor($width, $height);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagecopyresampled($small, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        ob_start();
        imagepng($small, null, 9);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($small);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtoupper(substr(trim($value), -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }

    private function reportQuery(array $filters): Builder
    {
        return ActivityLog::filter($filters);
    }

    private function recordExport(string $format, int $rows, array $filters, ?string $report = null): void
    {
        ActivityLog::record('report_exported', [
            'quantity' => $rows,
            'details' => 'Exported '.($report ? self::REPORTS[$report]['title'].' as ' : '').$format
                .' ('.number_format($rows).' '.str('row')->plural($rows).')',
            'meta' => array_filter([
                'report' => $report,
                'format' => strtolower($format) === 'print view' ? 'print' : strtolower($format),
                'filters' => $filters,
            ], fn ($value) => $value !== null),
        ]);
    }

    private function filename(array $filters, string $extension): string
    {
        return 'cict-activity-report_'.$filters['from'].'_to_'.$filters['to'].'.'.$extension;
    }

    public static function csvCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function rangeWords(array $filters): string
    {
        return Carbon::parse($filters['from'])->format('M j, Y').' – '.Carbon::parse($filters['to'])->format('M j, Y');
    }

    private function filterWords(array $filters): string
    {
        $words = collect($this->appliedFilters($filters))->except('Dates')
            ->map(fn ($value, $name) => $name.': '.$value)
            ->implode('; ');

        return $words !== '' ? $words : 'None';
    }

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
                'label' => 'Borrows recorded', 'value' => number_format($lent + $issued), 'unit' => '',
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

    private function appliedFilters(array $filters): array
    {
        $applied = [
            'Dates' => Carbon::parse($filters['from'])->format('M j, Y').' – '.Carbon::parse($filters['to'])->format('M j, Y'),
        ];

        if (isset($filters['type'])) {
            $applied['Activity'] = isset(ActivityLog::TYPES[$filters['type']])
                ? ActivityLog::TYPES[$filters['type']]['label']
                : ActivityLog::groupName($filters['type']);
        }
        if (isset($filters['equipment_id'])) {
            $applied['Equipment'] = Equipment::find($filters['equipment_id'])?->equipment_name ?? 'Item #'.$filters['equipment_id'];
        }
        if (isset($filters['user_id'])) {
            $applied['User'] = User::find($filters['user_id'])?->name ?? 'Account #'.$filters['user_id'];
        }
        if (isset($filters['status'])) {
            $applied['Status'] = $filters['status'];
        }
        if (isset($filters['q'])) {
            $applied['Search'] = '“'.$filters['q'].'”';
        }

        return $applied;
    }

    private function requestedReport(Request $request): string
    {
        $report = $request->query('report');

        return is_string($report) && isset(self::REPORTS[$report]) ? $report : 'activity';
    }

    private function officialIndex(Request $request, string $report)
    {
        $data = $this->officialReport($request, $report);

        $rows = collect($data['rows']);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rowsPage = (new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values(), $rows->count(), self::PER_PAGE, $page, ['path' => $request->url()]
        ))->withQueryString();

        return response()->view('admin.reports', $data + [
            'rowsPage' => $rowsPage,
            'categoryOptions' => $report === 'inventory' ? Equipment::categoriesInUse() : collect(),
        ])->header('Cache-Control', 'no-store, private');
    }

    private function officialCsv(Request $request, string $report)
    {
        $data = $this->officialReport($request, $report);

        return response()->streamDownload(function () use ($data, $report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, array_column($data['columns'], 'label'), eol: "\r\n");

            foreach ($data['rows'] as $row) {
                fputcsv($out, array_map(fn ($cell) => self::csvCell(self::cellText($cell)), $row), eol: "\r\n");
            }

            fclose($out);

            $this->recordExport('CSV', count($data['rows']), $data['filters'], $report);
        }, $this->officialFilename($report, $data['filters'], 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function officialPdf(Request $request, string $report)
    {
        $data = $this->officialPaged($request, $report);
        if (! is_array($data)) {
            return $data;
        }

        $pdf = $this->renderPdf('admin.reports.pdf', $data + ['official' => true], $data['meta']['title']);

        $this->recordExport('PDF', $data['meta']['total'], $data['filters'], $report);

        return $pdf->download($this->officialFilename($report, $data['filters'], 'pdf'));
    }

    private function officialPrint(Request $request, string $report)
    {
        $data = $this->officialPaged($request, $report);
        if (! is_array($data)) {
            return $data;
        }

        $this->recordExport('print view', $data['meta']['total'], $data['filters'], $report);

        return response()->view('admin.reports.print', $data + [
            'official' => true,
            'logoSrc' => asset('images/logo.png'),
            'backUrl' => route('admin.reports', $request->except('page')),
        ])->header('Cache-Control', 'no-store, private');
    }

    private function officialPaged(Request $request, string $report): array|\Illuminate\Http\RedirectResponse
    {
        $data = $this->officialReport($request, $report);
        $total = $data['meta']['total'];

        if ($total > self::MAX_PAGED_ROWS) {
            return redirect()->route('admin.reports', $request->except('page'))->with('error',
                number_format($total).' '.str($data['meta']['noun'])->plural($total).' match these filters — too many to lay out on paper (the limit is '
                .number_format(self::MAX_PAGED_ROWS).'). Narrow the dates or filters, or use Export CSV, which has no limit.');
        }

        return $data;
    }

    private function officialReport(Request $request, string $report): array
    {
        $resolved = $this->resolveOfficial($request, $report);
        $filters = $resolved['filters'];
        $definition = self::REPORTS[$report];

        $rows = match ($report) {
            'borrowers' => $this->borrowerRows($filters),
            'inventory' => $this->inventoryRows($filters),
            'not-returned' => $this->notReturnedRows(),
            'most-borrowed' => $this->mostBorrowedRows($filters),
            'defective' => $this->defectiveRows($filters),
        };

        $user = $request->user();
        $ranged = isset($filters['from']);

        return [
            'report' => $report,
            'definition' => $definition,
            'columns' => self::COLUMNS[$report],
            'rows' => $rows,
            'filters' => $filters,
            'resolved' => $resolved,
            'emptyMessage' => $this->officialEmptyMessage($report, $filters),
            'meta' => [
                'title' => $definition['title'],
                'range_label' => $ranged ? 'Date range' : 'As of',
                'range' => $ranged ? $this->rangeWords($filters) : now()->format('M j, Y, g:i A'),
                'filters' => $this->officialFilterWords($report, $filters),
                'generated_at' => now()->format('M j, Y, g:i A'),
                'generated_by' => $user->name.' ('.$user->user_type.')',
                'prepared_by' => $user->name,
                'noted_by' => [
                    'name' => trim((string) config('office.noted_by.name', '')),
                    'position' => trim((string) config('office.noted_by.position', '')),
                ],
                'total' => count($rows),
                'noun' => $definition['noun'],
            ],
        ];
    }

    public function resolveOfficial(Request $request, string $report): array
    {
        $filters = [];
        $errors = [];
        $defaultRange = false;
        $definition = self::REPORTS[$report];
        $optional = ! empty($definition['optional_range']);

        if ($definition['dated'] || $optional) {
            $dates = $this->resolveFilters($request);
            $errors = array_intersect_key($dates['errors'], array_flip(['from', 'to', 'range']));
            $defaultRange = $dates['defaultRange'];

            if ($definition['dated'] || ! $defaultRange) {
                $filters['from'] = $dates['filters']['from'];
                $filters['to'] = $dates['filters']['to'];
            }
            if ($optional && isset($errors['range'])) {
                $errors['range'] = 'The start date is after the end date, so the report lists every open '
                    .$definition['noun'].' instead.';
            }
        }

        $query = fn (string $key) => is_string($value = $request->query($key)) ? trim($value) : '';

        if ($report === 'inventory') {
            $category = $query('category');
            if ($category !== '' && mb_strlen($category) <= 60) {
                $filters['category'] = $category;
            } elseif ($category !== '') {
                $errors['category'] = 'That category is longer than any on file.';
            }

            $status = $query('status');
            if (isset(self::INVENTORY_STATUSES[$status])) {
                $filters['status'] = $status;
            } elseif ($status !== '') {
                $errors['status'] = 'Choose Serviceable or Non-serviceable.';
            }
        }

        if ($report === 'most-borrowed') {
            $limit = $query('limit');
            $filters['limit'] = self::TOP_LIMITS[0];
            if (in_array($limit, array_map('strval', self::TOP_LIMITS), true)) {
                $filters['limit'] = (int) $limit;
            } elseif ($limit !== '') {
                $errors['limit'] = 'Show the top 10, 25 or 50.';
            }
        }

        return ['filters' => $filters, 'errors' => $errors, 'defaultRange' => $defaultRange];
    }

    private function borrowerRows(array $filters): array
    {
        return BorrowTransaction::query()
            ->whereNull('voided_at')
            ->whereBetween('borrow_date', $this->dayBounds($filters))
            ->with(['user:id,name', 'equipment:id,equipment_name,unit', 'returnLog'])
            ->orderBy('borrow_date')
            ->orderBy('id')
            ->get()
            ->map(fn (BorrowTransaction $loan) => [
                $this->borrowedAtWords($loan),
                $loan->user?->name ?? '—',
                $this->itemWords($loan),
                $loan->isIssued() ? '—' : $this->returnedAtWords($loan),
                match (true) {
                    $loan->isIssued() => 'Issued',
                    $loan->isReturned() => 'Returned',
                    default => 'Not yet returned',
                },
            ])
            ->all();
    }

    private function inventoryRows(array $filters): array
    {
        $query = Equipment::lendable()
            ->withExists(['returnLogs as has_open_defects' => fn ($logs) => $logs->needsFollowUp()])
            ->orderBy('equipment_name')
            ->orderBy('id');

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        match ($filters['status'] ?? null) {
            'serviceable' => $query->serviceable(),
            'non-serviceable' => $query->nonServiceable(),
            default => null,
        };

        return $query->get()
            ->map(fn (Equipment $item) => [
                (int) $item->quantity,
                $item->unit,
                $item->equipment_name.($item->description ? ' — '.$item->description : ''),
                self::INVENTORY_STATUSES[$item->isServiceable() ? 'serviceable' : 'non-serviceable'],
            ])
            ->all();
    }

    private function notReturnedRows(): array
    {
        return BorrowTransaction::out()
            ->with(['user:id,name,course,year_level', 'equipment:id,equipment_name,unit'])
            ->orderBy('return_date')
            ->orderBy('id')
            ->get()
            ->map(fn (BorrowTransaction $loan) => [
                $loan->user?->name ?? '—',
                $loan->user?->courseAndYear() ?? '—',
                $this->itemWords($loan),
                $loan->borrow_date?->format('M j, Y') ?? '—',
                $loan->isOverdue()
                    ? ['text' => $this->dueWords($loan), 'mark' => 'Overdue']
                    : $this->dueWords($loan),
            ])
            ->all();
    }

    private function mostBorrowedRows(array $filters): array
    {
        $top = BorrowTransaction::query()
            ->join('equipment', 'equipment.id', '=', 'borrow_transactions.equipment_id')
            ->whereNull('borrow_transactions.voided_at')
            ->whereBetween('borrow_transactions.borrow_date', $this->dayBounds($filters))
            ->groupBy('borrow_transactions.equipment_id', 'equipment.equipment_name', 'equipment.unit', 'equipment.retired_at')
            ->selectRaw('equipment.equipment_name as name, equipment.unit as unit, equipment.retired_at as retired_at,'
                .' COUNT(*) as times, SUM(borrow_transactions.quantity) as units')
            ->orderByDesc('times')
            ->orderByDesc('units')
            ->orderBy('equipment.equipment_name')
            ->limit($filters['limit'])
            ->toBase()
            ->get();

        $rank = 0;
        $previous = null;

        return $top->values()->map(function ($row, int $index) use (&$rank, &$previous) {
            if ((int) $row->times !== $previous) {
                $rank = $index + 1;
                $previous = (int) $row->times;
            }

            return [
                $rank,
                $row->name.($row->retired_at ? ' (retired)' : ''),
                (int) $row->times,
                (int) $row->units.' '.$row->unit,
            ];
        })->all();
    }

    private function defectiveRows(array $filters): array
    {
        $query = ReturnLog::defects()->reorder()->orderByDesc('created_at')->orderByDesc('id');

        if (isset($filters['from'])) {
            $query->whereBetween('created_at', $this->dayBounds($filters));
        }

        return $query->get()
            ->map(fn (ReturnLog $log) => [
                $log->created_at
                    ? ['text' => $log->created_at->format('M j, Y g:i A'), 'csv' => $log->created_at->format('Y-m-d H:i')]
                    : '—',
                (int) ($log->borrowTransaction?->quantity ?? 0),
                $log->borrowTransaction?->equipment?->unit ?? Equipment::DEFAULT_UNIT,
                $log->borrowTransaction?->equipment?->equipment_name ?? '—',
                filled($log->remarks) ? trim($log->remarks) : $log->condition,
            ])
            ->all();
    }

    private function dayBounds(array $filters): array
    {
        return [Carbon::parse($filters['from'])->startOfDay(), Carbon::parse($filters['to'])->endOfDay()];
    }

    private function itemWords(BorrowTransaction $loan): string
    {
        return ($loan->equipment?->equipment_name ?? 'Removed item').', '.$loan->quantity.' '
            .($loan->equipment?->unit ?? Equipment::DEFAULT_UNIT);
    }

    private function borrowedAtWords(BorrowTransaction $loan): string
    {
        return $this->momentWords($loan->borrow_date, $loan->timed ? $loan->borrow_date : $loan->created_at);
    }

    private function returnedAtWords(BorrowTransaction $loan): string
    {
        return $this->momentWords($loan->returnLog?->return_date, $loan->returnLog?->created_at);
    }

    private function momentWords(?CarbonInterface $day, ?CarbonInterface $time): string
    {
        if ($day === null) {
            return '—';
        }

        return $time !== null && $time->isSameDay($day)
            ? $day->format('M j, Y').', '.$time->format('g:i A')
            : $day->format('M j, Y');
    }

    private function dueWords(BorrowTransaction $loan): string
    {
        if ($loan->return_date === null) {
            return 'No due date';
        }

        return $loan->timed
            ? $loan->return_date->format('M j, Y, g:i A')
            : $loan->return_date->format('M j, Y').', end of day';
    }

    public static function cellText(mixed $cell): mixed
    {
        if (is_array($cell)) {
            return $cell['csv'] ?? $cell['text'].(! empty($cell['mark']) ? ' ('.$cell['mark'].')' : '');
        }

        return $cell;
    }

    private function officialFilterWords(string $report, array $filters): string
    {
        $words = array_filter([
            isset($filters['category']) ? 'Category: '.$filters['category'] : null,
            isset($filters['status']) ? 'Status: '.self::INVENTORY_STATUSES[$filters['status']] : null,
            isset($filters['limit']) ? 'Top '.$filters['limit'] : null,
        ]);

        return $words !== [] ? implode('; ', $words) : 'None';
    }

    private function officialEmptyMessage(string $report, array $filters): string
    {
        return match ($report) {
            'borrowers' => 'Nothing was borrowed or issued between '.$this->rangeWords($filters).'.',
            'inventory' => isset($filters['category']) || isset($filters['status'])
                ? 'No equipment matches these filters.'
                : 'There is no equipment in use.',
            'not-returned' => 'Every borrowed item has been returned. Nothing is out right now.',
            'most-borrowed' => 'Nothing was borrowed between '.$this->rangeWords($filters).'.',
            'defective' => isset($filters['from'])
                ? 'No open defects were reported between '.$this->rangeWords($filters).'.'
                : 'No defective items. Every damaged or lost return has a resolution recorded.',
        };
    }

    private function officialFilename(string $report, array $filters, string $extension): string
    {
        $span = isset($filters['from']) ? $filters['from'].'_to_'.$filters['to'] : today()->toDateString();

        return 'cict-'.self::REPORTS[$report]['file'].'_'.$span.'.'.$extension;
    }
}
