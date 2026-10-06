<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportsController;
use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * CSV, PDF and print: the same filters and the same ActivityLog::filter()
 * query as the screen, admin only, and every export logged.
 */
class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    private const CSV_HEADER = [
        'Date', 'Time', 'Activity type', 'Activity group', 'Done by', 'Done by role', 'Equipment',
        'Affected person', 'Quantity', 'Status from', 'Status to', 'Details', 'Loan ID',
    ];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-07 14:00:00'));
        $this->admin = User::factory()->create(['user_type' => 'Admin', 'name' => 'Quincy Jane Oliver']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function entry(string $moment, string $type = 'login', array $attrs = []): ActivityLog
    {
        return ActivityLog::record($type, $attrs + ['actor' => null, 'occurred_at' => Carbon::parse($moment)]);
    }

    /** Insert many rows at once; the append-only guard blocks changes, not inserts. */
    private function bulk(int $count, string $moment = '2026-10-06 08:00:00'): void
    {
        foreach (array_chunk(range(1, $count), 500) as $chunk) {
            ActivityLog::insert(array_map(fn ($i) => [
                'occurred_at' => $moment, 'type' => 'login', 'source' => 'live',
                'details' => 'Signed in #'.$i, 'created_at' => $moment, 'updated_at' => $moment,
            ], $chunk));
        }
    }

    /** The CSV's body as rows, after checking and stripping the BOM. */
    private function csv(array $query = []): array
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.csv', $query))->assertOk();
        $body = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'The CSV has no UTF-8 byte-order mark');
        $lines = preg_split("/\r\n/", rtrim(substr($body, 3), "\r\n"));

        return array_map('str_getcsv', $lines);
    }

    /** The Details column of each data row. */
    private function details(array $query = []): array
    {
        return array_column(array_slice($this->csv($query), 1), 11);
    }

    /* ------------------------------------------------------------------
     | Access
     ------------------------------------------------------------------ */

    public function test_the_three_exports_are_admin_only(): void
    {
        $student = User::factory()->create(['user_type' => 'Student']);

        foreach (['admin.reports.csv', 'admin.reports.pdf', 'admin.reports.print'] as $name) {
            $this->actingAs($student)->get(route($name))->assertForbidden();
        }

        auth()->logout();
        foreach (['admin.reports.csv', 'admin.reports.pdf', 'admin.reports.print'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }

        $this->assertSame(0, ActivityLog::where('type', 'report_exported')->count());
    }

    /* ------------------------------------------------------------------
     | CSV
     ------------------------------------------------------------------ */

    public function test_the_csv_has_a_bom_the_header_row_and_a_dated_filename(): void
    {
        $mia = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos-Ñuñez']);
        $this->entry('2026-10-06 09:05:00', 'loan_checked_in', [
            'actor' => $this->admin, 'subject' => $mia, 'equipment_name' => 'Projector (Epson)', 'quantity' => 2,
            'status_from' => 'Borrowed', 'status_to' => 'Returned', 'borrow_transaction_id' => null,
            'details' => 'Checked in 2 × Projector (Epson) → shelf.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.csv', ['from' => '2026-10-01', 'to' => '2026-10-31']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('cict-activity-report_2026-10-01_to_2026-10-31.csv', $response->headers->get('Content-Disposition'));

        $rows = $this->csv(['from' => '2026-10-01', 'to' => '2026-10-31']);
        $this->assertSame(self::CSV_HEADER, $rows[0]);
        $this->assertSame([
            '2026-10-06', '09:05', 'Checked in', 'Loans', 'Quincy Jane Oliver', 'Admin', 'Projector (Epson)',
            'Mia Santos-Ñuñez', '2', 'Borrowed', 'Returned', 'Checked in 2 × Projector (Epson) → shelf.', '',
        ], $rows[1]);
    }

    public function test_the_csv_contains_every_matching_row_not_one_page(): void
    {
        $this->bulk(ReportsController::PER_PAGE * 2 + 20);

        $this->assertCount(120, $this->details());
    }

    public function test_the_csv_honours_each_filter_like_the_screen(): void
    {
        $mia = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);
        $projector = Equipment::create(['equipment_name' => 'Projector (Epson)', 'quantity' => 5, 'available_quantity' => 5, 'status' => 'Available']);

        $this->entry('2026-09-30 23:59:59', 'login', ['details' => 'before range']);
        $this->entry('2026-10-01 00:00:00', 'loan_created', ['details' => 'lent', 'equipment' => $projector, 'subject' => $mia, 'status_to' => 'Borrowed']);
        $this->entry('2026-10-02 10:00:00', 'request_declined', ['details' => 'declined: none left', 'actor' => $this->admin, 'status_to' => 'Declined']);
        $this->entry('2026-10-03 23:30:00', 'login', ['details' => 'signed in', 'actor' => $mia]);
        $this->entry('2026-10-04 00:00:00', 'login', ['details' => 'after range']);

        $range = ['from' => '2026-10-01', 'to' => '2026-10-03'];

        $this->assertSame(['signed in', 'declined: none left', 'lent'], $this->details($range));
        $this->assertSame(['lent'], $this->details($range + ['type' => 'Loans']));
        $this->assertSame(['declined: none left'], $this->details($range + ['type' => 'request_declined']));
        $this->assertSame(['lent'], $this->details($range + ['equipment_id' => $projector->id]));
        $this->assertSame(['signed in', 'lent'], $this->details($range + ['user_id' => $mia->id]));   // actor, subject
        $this->assertSame(['Declined'], array_column(array_slice($this->csv($range + ['status' => 'Declined']), 1), 10));
        $this->assertSame(['declined: none left'], $this->details($range + ['q' => 'none left']));

        // With no range: the screen's default, the last 30 days.
        $this->assertContains('after range', $this->details());
    }

    public function test_cells_that_would_run_as_formulas_are_neutralised(): void
    {
        $this->entry('2026-10-06 09:00:00', 'user_updated', [
            'actor_name' => '@SUM(A1:A9)',
            'subject_name' => '+1 555 0100',
            'equipment_name' => '-2+3',
            'details' => '=HYPERLINK("http://example.test","click")',
            'quantity' => 3,
        ]);
        $this->entry('2026-10-06 10:00:00', 'login', ['details' => "\tTabbed", 'subject_name' => 'Mia Santos']);

        $rows = array_slice($this->csv(), 1);
        [$tabbed, $evil] = $rows;   // newest first

        $this->assertSame("'@SUM(A1:A9)", $evil[4]);
        $this->assertSame("'-2+3", $evil[6]);
        $this->assertSame("'+1 555 0100", $evil[7]);
        $this->assertSame('3', $evil[8]);                                   // numbers untouched
        $this->assertSame("'=HYPERLINK(\"http://example.test\",\"click\")", $evil[11]);
        $this->assertSame("'\tTabbed", $tabbed[11]);
        $this->assertSame('Mia Santos', $tabbed[7]);                        // ordinary text untouched
    }

    /* ------------------------------------------------------------------
     | PDF
     ------------------------------------------------------------------ */

    public function test_the_pdf_is_an_a4_pdf_named_by_its_range(): void
    {
        $this->entry('2026-10-06 09:00:00', 'loan_created', ['details' => 'Lent 1 × Projector (Epson) to Mia Santos-Ñuñez.']);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.pdf', ['from' => '2026-10-01', 'to' => '2026-10-07']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('cict-activity-report_2026-10-01_to_2026-10-07.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_and_print_refuse_more_than_the_row_cap_and_point_to_csv(): void
    {
        $this->bulk(ReportsController::MAX_PAGED_ROWS + 1);

        foreach (['admin.reports.pdf', 'admin.reports.print'] as $name) {
            $this->actingAs($this->admin)->get(route($name, ['type' => 'login']))
                ->assertRedirect(route('admin.reports', ['type' => 'login']))
                ->assertSessionHas('error', fn ($message) => str_contains($message, '2,001') && str_contains($message, 'Export CSV'));
        }

        // Refused exports are not logged; the toolbar says why before anyone clicks.
        $this->assertSame(0, ActivityLog::where('type', 'report_exported')->count());
        $html = $this->actingAs($this->admin)->get(route('admin.reports'))->getContent();
        $this->assertMatchesRegularExpression('/<span aria-disabled="true" data-export="pdf"/', $html);
        $this->assertStringContainsString('PDF and Print stop at 2,000 rows', $html);
    }

    public function test_the_pdf_lays_rows_out_in_100_row_tables_and_print_in_one(): void
    {
        $this->bulk(150);
        $activities = ActivityLog::query()->newestFirst()->get();
        $meta = ['title' => 'T', 'range' => 'R', 'filters' => 'None', 'generated_at' => 'now', 'generated_by' => 'me', 'total' => 150];

        // dompdf's cost for one table grows faster than its rows, so the PDF
        // splits; each table repeats the header. The browser needs no split.
        $pdfHtml = view('admin.reports.pdf', ['activities' => $activities, 'meta' => $meta, 'logoSrc' => 'logo.png'])->render();
        $printHtml = view('admin.reports.print', ['activities' => $activities, 'meta' => $meta, 'logoSrc' => 'logo.png', 'backUrl' => '/'])->render();

        $this->assertSame(ReportsController::PDF_TABLE_ROWS, 100);
        $this->assertSame(2, substr_count($pdfHtml, '<table class="report-table">'));
        $this->assertSame(1, substr_count($printHtml, '<table class="report-table">'));
        $this->assertSame(150, substr_count($pdfHtml, '<td class="num">'));   // one per row, every row kept
        $this->assertSame(75, substr_count($pdfHtml, 'class="alt"'));         // zebra by class, across the split
        $this->assertStringNotContainsString(':nth-child', $pdfHtml);
    }

    /* ------------------------------------------------------------------
     | Print
     ------------------------------------------------------------------ */

    public function test_the_print_view_shows_the_title_range_filters_and_rows_without_the_app_chrome(): void
    {
        $mia = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);
        $projector = Equipment::create(['equipment_name' => 'Projector (Epson)', 'quantity' => 5, 'available_quantity' => 5, 'status' => 'Available']);
        $this->entry('2026-10-06 09:00:00', 'loan_created', [
            'actor' => $this->admin, 'subject' => $mia, 'equipment' => $projector, 'details' => 'Lent 1 × Projector (Epson) to Mia Santos.',
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.reports.print', ['from' => '2026-10-01', 'to' => '2026-10-07', 'type' => 'Loans',
                'equipment_id' => $projector->id, 'user_id' => $mia->id]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('CICT Equipment Borrower System', $html);
        $this->assertStringContainsString('Activity and Transactions Report', $html);
        $this->assertStringContainsString('Oct 1, 2026 – Oct 7, 2026', $html);
        $this->assertStringContainsString('Activity: Loans; Equipment: Projector (Epson); User: Mia Santos', $html);
        $this->assertStringContainsString('Oct 7, 2026, 2:00 PM by Quincy Jane Oliver (Admin)', $html);
        $this->assertStringContainsString('Lent 1 × Projector (Epson) to Mia Santos.', $html);
        $this->assertStringContainsString(asset('images/logo.png'), $html);

        // Print rules and the automatic dialog.
        $this->assertStringContainsString('size: A4 landscape', $html);
        $this->assertStringContainsString('display: table-header-group', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertStringContainsString('window.print()', $html);

        // No sidebar, no app navigation.
        $this->assertStringNotContainsString('sidebar', $html);
        $this->assertStringNotContainsString('Borrow Transactions', $html);
    }

    public function test_with_no_filters_the_header_says_none(): void
    {
        $this->entry('2026-10-06 09:00:00');

        $this->actingAs($this->admin)->get(route('admin.reports.print'))->assertOk()
            ->assertSee('Sep 8, 2026 – Oct 7, 2026')
            ->assertSeeInOrder(['Filters', 'None']);
    }

    /* ------------------------------------------------------------------
     | Logging and the toolbar
     ------------------------------------------------------------------ */

    public function test_each_export_writes_one_report_exported_entry(): void
    {
        foreach (range(1, 3) as $i) {
            $this->entry('2026-10-06 09:0'.$i.':00', 'login');
        }
        $query = ['type' => 'login'];

        $this->csv($query);
        $csv = ActivityLog::where('type', 'report_exported')->sole();
        $this->assertSame('Exported CSV (3 rows)', $csv->details);
        $this->assertSame('csv', $csv->meta['format']);
        $this->assertSame('login', $csv->meta['filters']['type']);
        $this->assertSame(['2026-09-08', '2026-10-07'], [$csv->meta['filters']['from'], $csv->meta['filters']['to']]);
        $this->assertSame($this->admin->id, $csv->actor_id);

        $this->actingAs($this->admin)->get(route('admin.reports.pdf', $query))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.reports.print', $query))->assertOk();

        $this->assertSame(
            [['csv', 'Exported CSV (3 rows)'], ['pdf', 'Exported PDF (3 rows)'], ['print', 'Exported print view (3 rows)']],
            ActivityLog::where('type', 'report_exported')->orderBy('id')->get()
                ->map(fn ($log) => [$log->meta['format'], $log->details])->all()
        );
    }

    public function test_the_toolbar_links_carry_the_screens_query_string(): void
    {
        $this->entry('2026-10-06 09:00:00', 'loan_created');
        $query = ['from' => '2026-10-01', 'to' => '2026-10-07', 'type' => 'Loans', 'page' => 1];

        $html = $this->actingAs($this->admin)->get(route('admin.reports', $query))->assertOk()->getContent();

        foreach (['csv' => 'Export CSV', 'pdf' => 'Export PDF', 'print' => 'Print'] as $format => $label) {
            $url = route('admin.reports.'.$format, ['from' => '2026-10-01', 'to' => '2026-10-07', 'type' => 'Loans']);
            $this->assertStringContainsString('href="'.e($url).'" data-export="'.$format.'"', $html);
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringNotContainsString('export.csv?from=2026-10-01&amp;to=2026-10-07&amp;type=Loans&amp;page', $html);
    }
}
