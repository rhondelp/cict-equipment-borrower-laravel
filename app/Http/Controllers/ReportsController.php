<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The Reports screen: the activity log, filtered.
 *
 * Every figure and row on the page comes from one query built by
 * ActivityLog::filter(), from filters read out of the query string by
 * resolveFilters(). The CSV, PDF and print outputs go through the same two
 * calls (reportQuery()), so the screen and the three exports cannot disagree
 * about what "this report" contains.
 *
 * Nothing is cached: the page reads the log on every request, so it shows an
 * action the moment it is recorded.
 */
class ReportsController extends Controller
{
    public const PER_PAGE = 50;

    /** The default range, ending today: today and the 29 days before it. */
    public const DEFAULT_DAYS = 30;

    /**
     * The most rows a PDF or printout will lay out. Beyond this dompdf takes
     * too long and the paper stops being something anyone reads; the CSV has
     * no limit because it streams.
     */
    public const MAX_PAGED_ROWS = 2000;

    public const TITLE = 'Activity and Transactions Report';

    /**
     * Rows per table in the PDF. dompdf's cost for one table grows faster
     * than its rows; consecutive tables of this size keep a 2,000-row report
     * near 30 seconds and 550 MB instead of 96 seconds and 3 GB.
     */
    public const PDF_TABLE_ROWS = 100;

    /** Pixel width the logo is scaled to for the PDF (shown at 48pt). */
    private const PDF_LOGO_PX = 192;

    public function index(Request $request)
    {
        $resolved = $this->resolveFilters($request);
        $filters = $resolved['filters'];

        $query = $this->reportQuery($filters);

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
     * Every matching row as CSV, streamed: rows are read with a cursor and
     * written as they come, so a year of activity never sits in memory.
     *
     * UTF-8 with a byte-order mark, which is what makes Excel read "Ñ" and "→"
     * correctly. A cell that starts with = + - @ (or a tab or carriage
     * return) is prefixed with an apostrophe, so a name or a note can never
     * run as a formula when the file is opened.
     */
    public function exportCsv(Request $request)
    {
        $filters = $this->resolveFilters($request)['filters'];
        $query = $this->reportQuery($filters)->newestFirst();

        return response()->streamDownload(function () use ($query, $filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, [
                'Date', 'Time', 'Activity type', 'Activity group', 'Done by', 'Done by role', 'Equipment',
                'Affected person', 'Quantity', 'Status from', 'Status to', 'Details', 'Loan ID',
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

            // Logged once the rows are written, so the count is the file's
            // and the export's own entry is never one of its rows.
            $this->recordExport('CSV', $rows, $filters);
        }, $this->filename($filters, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * The report as an A4 landscape PDF, built by dompdf from local files only:
     * remote fetching and embedded PHP stay off, and file access is confined
     * to public/images, where the logo is read from disk.
     */
    public function exportPdf(Request $request)
    {
        $report = $this->pagedReport($request);
        if (! is_array($report)) {
            return $report;
        }

        // A full 2,000-row report needs about half a minute and half a
        // gigabyte; never lower a limit the server already allows.
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
            // Embed only the glyphs used. Off by default, which put the
            // whole of DejaVu Sans regular and bold (~900 KB) in every file.
            'enable_font_subsetting' => true,
        ])->loadView('admin.reports.pdf', $report + [
            'logoSrc' => $this->pdfLogo(),
        ])->setPaper('a4', 'landscape');

        // "Page X of Y" needs the page count, which exists only after layout,
        // so it is stamped onto every page once rendering is done.
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 120, $canvas->get_height() - 28,
            'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.28, 0.33, 0.41]);
        $canvas->page_text(34, $canvas->get_height() - 28,
            'CICT Equipment Borrower System · '.self::TITLE, $font, 8, [0.28, 0.33, 0.41]);

        $this->recordExport('PDF', $report['activities']->count(), $report['filters']);

        return $pdf->download($this->filename($report['filters'], 'pdf'));
    }

    /** The report as a printable page: no sidebar, the PDF's header, then the full table. */
    public function print(Request $request)
    {
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

    /**
     * Everything the PDF and the printout share, or a redirect back to the
     * screen when there is too much to lay out on paper.
     */
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

    /**
     * The logo for the PDF: read from disk (public/images/logo.png) and, when
     * GD is available, scaled down in memory to a data URI. The original is
     * 1840 px and 375 KB, which made even a one-page PDF 1.2 MB; at 48pt on
     * the page nobody can see the difference. Without GD the file path is
     * used as is.
     */
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

    /** "512M" → bytes, for comparing memory limits. */
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

    /** The one query every output reads: the screen, CSV, PDF and print. */
    private function reportQuery(array $filters): Builder
    {
        return ActivityLog::filter($filters);
    }

    private function recordExport(string $format, int $rows, array $filters): void
    {
        ActivityLog::record('report_exported', [
            'quantity' => $rows,
            'details' => 'Exported '.$format.' ('.number_format($rows).' '.str('row')->plural($rows).')',
            'meta' => ['format' => strtolower($format) === 'print view' ? 'print' : strtolower($format), 'filters' => $filters],
        ]);
    }

    /** cict-activity-report_2026-10-01_to_2026-10-31.csv */
    private function filename(array $filters, string $extension): string
    {
        return 'cict-activity-report_'.$filters['from'].'_to_'.$filters['to'].'.'.$extension;
    }

    /**
     * Spreadsheet formula injection: a cell starting with = + - @, a tab or
     * a carriage return is read as a formula by Excel and LibreOffice. The
     * apostrophe makes it text. Numbers are left alone.
     */
    public static function csvCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /** "Oct 1, 2026 – Oct 31, 2026" */
    private function rangeWords(array $filters): string
    {
        return Carbon::parse($filters['from'])->format('M j, Y').' – '.Carbon::parse($filters['to'])->format('M j, Y');
    }

    /** "Activity: Loans; Equipment: Projector; User: Ana Cruz", or "None". */
    private function filterWords(array $filters): string
    {
        $words = collect($this->appliedFilters($filters))->except('Dates')
            ->map(fn ($value, $name) => $name.': '.$value)
            ->implode('; ');

        return $words !== '' ? $words : 'None';
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
                : $filters['type'];
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
}
