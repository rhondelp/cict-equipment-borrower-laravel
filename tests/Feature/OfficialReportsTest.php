<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportsController;
use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The five official reports on the Reports page ("Prompt 28"): the switcher,
 * each report's exact columns and rows, their filters, and the CSV / PDF /
 * print outputs with the signature block.
 */
class OfficialReportsTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-10-14 15:00:00';

    private const HEADINGS = [
        'borrowers' => ['Date & Time Borrowed', 'Name of Borrower', 'Item Borrowed', 'Returned Date & Time', 'Status'],
        'inventory' => ['Quantity', 'Unit', 'Item Description', 'Status'],
        'not-returned' => ['Name of Borrower', 'Course & Year', 'Item Borrowed', 'Date Borrowed', 'Due Date & Time'],
        'most-borrowed' => ['Rank', 'Item Description', 'Times Borrowed', 'Total Quantity'],
        'defective' => ['Quantity', 'Unit', 'Item Description', 'Item Defect'],
    ];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW));
        $this->admin = User::factory()->create(['user_type' => 'Admin', 'name' => 'Quincy Jane Oliver']);
        config(['office.noted_by' => ['name' => 'Dr. Ramon Cruz', 'position' => 'Dean, CICT']]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /* ------------------------------------------------------------------
     | Fixtures
     ------------------------------------------------------------------ */

    private function item(string $name, array $attrs = []): Equipment
    {
        return Equipment::create($attrs + [
            'equipment_name' => $name, 'quantity' => 5, 'available_quantity' => 5, 'status' => 'Available',
        ]);
    }

    private function student(string $name, array $attrs = []): User
    {
        return User::factory()->create($attrs + ['user_type' => 'Student', 'name' => $name]);
    }

    /** A loan recorded at $recordedAt (its created_at). */
    private function loan(User $user, Equipment $item, array $attrs, ?string $recordedAt = null): BorrowTransaction
    {
        Carbon::setTestNow(Carbon::parse($recordedAt ?? $attrs['borrow_date']));
        $loan = BorrowTransaction::create($attrs + [
            'user_id' => $user->id, 'equipment_id' => $item->id, 'quantity' => 1, 'purpose' => 'Lab', 'status' => 'Borrowed',
        ]);
        Carbon::setTestNow(Carbon::parse(self::NOW));

        return $loan;
    }

    private function returnLog(BorrowTransaction $loan, string $day, string $loggedAt, string $condition = 'Good', array $attrs = []): ReturnLog
    {
        Carbon::setTestNow(Carbon::parse($loggedAt));
        $log = ReturnLog::create($attrs + [
            'borrow_transaction_id' => $loan->id, 'return_date' => $day, 'condition' => $condition, 'user_id' => $this->admin->id,
        ]);
        Carbon::setTestNow(Carbon::parse(self::NOW));

        return $log;
    }

    /**
     * A small but complete month: a returned loan, an overdue one, a timed
     * one still out, an issue, a voided loan, and one from August.
     */
    private function month(): array
    {
        $mia = $this->student('Mia Santos', ['course' => 'BSIT', 'year_level' => '3rd Year']);
        $leo = $this->student('Leo Reyes');
        $projector = $this->item('Projector (Epson)', ['unit' => 'pc', 'category' => 'Projectors']);
        $cable = $this->item('HDMI cable', ['unit' => 'pc', 'category' => 'Cables']);
        $markers = $this->item('Whiteboard markers', ['unit' => 'box', 'loan_type' => Equipment::LOAN_NON_RETURNABLE]);

        $returned = $this->loan($mia, $projector, ['borrow_date' => '2026-10-05 00:00:00', 'return_date' => '2026-10-08 00:00:00', 'status' => 'Returned'], '2026-10-05 09:15:00');
        $this->returnLog($returned, '2026-10-07', '2026-10-07 16:10:00');

        $overdue = $this->loan($leo, $cable, ['borrow_date' => '2026-10-08 00:00:00', 'return_date' => '2026-10-10 00:00:00', 'quantity' => 2], '2026-10-08 10:00:00');
        $timed = $this->loan($mia, $projector, ['borrow_date' => '2026-10-14 14:00:00', 'return_date' => '2026-10-14 16:00:00', 'timed' => true]);
        $issued = $this->loan($leo, $markers, ['borrow_date' => '2026-10-09 00:00:00', 'return_date' => null, 'status' => 'Issued', 'quantity' => 3], '2026-10-09 11:30:00');
        $this->loan($mia, $cable, ['borrow_date' => '2026-10-06 00:00:00', 'return_date' => '2026-10-07 00:00:00', 'voided_at' => '2026-10-06 12:00:00', 'void_reason' => 'Entered twice'], '2026-10-06 08:00:00');
        $august = $this->loan($leo, $projector, ['borrow_date' => '2026-08-03 00:00:00', 'return_date' => '2026-08-05 00:00:00', 'status' => 'Returned'], '2026-08-03 13:00:00');
        $this->returnLog($august, '2026-08-05', '2026-08-05 09:00:00');

        return compact('mia', 'leo', 'projector', 'cable', 'markers', 'returned', 'overdue', 'timed', 'issued');
    }

    /** The CSV as rows (header first), after checking and stripping the BOM. */
    private function csv(string $report, array $query = []): array
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.csv', ['report' => $report] + $query))->assertOk();
        $body = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'The CSV has no UTF-8 byte-order mark');
        $lines = preg_split("/\r\n/", rtrim(substr($body, 3), "\r\n"));

        return array_map('str_getcsv', $lines);
    }

    private function screen(string $report, array $query = []): string
    {
        return $this->actingAs($this->admin)->get(route('admin.reports', ['report' => $report] + $query))
            ->assertOk()->getContent();
    }

    /* ------------------------------------------------------------------
     | The switcher
     ------------------------------------------------------------------ */

    public function test_the_switcher_lists_six_reports_in_order_and_marks_the_current_one(): void
    {
        $html = $this->screen('borrowers');

        preg_match_all('/data-report-tab="([a-z-]+)"/', $html, $tabs);
        $this->assertSame(['activity', 'borrowers', 'inventory', 'not-returned', 'most-borrowed', 'defective'], $tabs[1]);
        $this->assertMatchesRegularExpression('/data-report-tab="borrowers"\s+aria-current="page"/', $html);
        preg_match('/<nav aria-label="Report type".*?<\/nav>/s', $html, $nav);
        $this->assertSame(1, substr_count($nav[0], 'aria-current="page"'), 'More than one report is marked current');
        foreach (['Activity Log', 'Borrower Report', 'Inventory Report', 'Not Yet Returned', 'Most Borrowed', 'Defective Items'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('<nav aria-label="Report type" data-report-tabs>', $html);
        $this->assertStringContainsString('min-h-[48px]', $html, 'The tabs are not large targets');
    }

    public function test_the_activity_log_is_still_the_default_and_an_unknown_report_falls_back_to_it(): void
    {
        foreach ([[], ['report' => 'nonsense'], ['report' => ['x']]] as $query) {
            $html = $this->actingAs($this->admin)->get(route('admin.reports', $query))->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/data-report-tab="activity"\s+aria-current="page"/', $html);
            $this->assertStringContainsString('Everything recorded in the activity log, newest first.', $html);
        }
    }

    public function test_a_chosen_date_range_travels_with_the_switch(): void
    {
        $html = $this->screen('borrowers', ['from' => '2026-10-01', 'to' => '2026-10-14']);

        $this->assertStringContainsString(e(route('admin.reports', ['report' => 'most-borrowed', 'from' => '2026-10-01', 'to' => '2026-10-14'])), $html);
    }

    public function test_every_official_output_is_admin_only(): void
    {
        $student = $this->student('Mia Santos');

        foreach (['admin.reports', 'admin.reports.csv', 'admin.reports.pdf', 'admin.reports.print'] as $route) {
            $this->actingAs($student)->get(route($route, ['report' => 'inventory']))->assertForbidden();
        }
        auth()->logout();
        $this->get(route('admin.reports', ['report' => 'inventory']))->assertRedirect();
        $this->assertSame(0, ActivityLog::where('type', 'report_exported')->count());
    }

    /* ------------------------------------------------------------------
     | Columns
     ------------------------------------------------------------------ */

    public function test_each_report_has_exactly_its_columns_in_order_everywhere(): void
    {
        $month = $this->month();
        // Something for the defective list, so every report has a table.
        $this->returnLog($month['returned'], '2026-10-07', '2026-10-07 16:20:00', 'Damaged', ['remarks' => 'Cracked lens']);

        foreach (self::HEADINGS as $report => $headings) {
            $this->assertSame($headings, array_column(ReportsController::COLUMNS[$report], 'label'), $report);
            $this->assertSame($headings, $this->csv($report)[0], $report.' CSV header');

            foreach (['screen' => $this->screen($report), 'print' => $this->actingAs($this->admin)->get(route('admin.reports.print', ['report' => $report]))->getContent()] as $where => $html) {
                preg_match('/<thead>(.*?)<\/thead>/s', $html, $head);
                preg_match_all('/<th[^>]*>\s*(.*?)\s*<\/th>/s', $head[1] ?? '', $cells);
                $this->assertSame(array_map('e', $headings), $cells[1], $report.' '.$where.' headings');
            }
        }
    }

    /* ------------------------------------------------------------------
     | 1 · Borrower Report
     ------------------------------------------------------------------ */

    public function test_the_borrower_report_lists_loans_and_issues_with_their_return_and_status(): void
    {
        $this->month();

        $rows = array_slice($this->csv('borrowers'), 1);

        $this->assertSame([
            ['Oct 5, 2026, 9:15 AM', 'Mia Santos', 'Projector (Epson), 1 pc', 'Oct 7, 2026, 4:10 PM', 'Returned'],
            ['Oct 8, 2026, 10:00 AM', 'Leo Reyes', 'HDMI cable, 2 pc', '—', 'Not yet returned'],
            ['Oct 9, 2026, 11:30 AM', 'Leo Reyes', 'Whiteboard markers, 3 box', '—', 'Issued'],
            ['Oct 14, 2026, 2:00 PM', 'Mia Santos', 'Projector (Epson), 1 pc', '—', 'Not yet returned'],
        ], $rows, 'Voided loans and loans outside the last 30 days must not appear');
    }

    public function test_a_loan_entered_later_for_an_earlier_day_has_no_invented_time(): void
    {
        $mia = $this->student('Mia Santos');
        $this->loan($mia, $this->item('Tripod'), ['borrow_date' => '2026-10-02 00:00:00', 'return_date' => '2026-10-04 00:00:00'], '2026-10-03 08:00:00');

        $this->assertSame('Oct 2, 2026', $this->csv('borrowers')[1][0], 'A date-only loan printed as 12:00 AM or with another day\'s time');
    }

    public function test_the_borrower_report_follows_the_date_range(): void
    {
        $this->month();

        $rows = array_slice($this->csv('borrowers', ['from' => '2026-08-01', 'to' => '2026-08-31']), 1);
        $this->assertSame([['Aug 3, 2026, 1:00 PM', 'Leo Reyes', 'Projector (Epson), 1 pc', 'Aug 5, 2026, 9:00 AM', 'Returned']], $rows);

        // The end day is included whole.
        $this->assertCount(1, array_slice($this->csv('borrowers', ['from' => '2026-10-14', 'to' => '2026-10-14']), 1));

        $html = $this->screen('borrowers', ['from' => '2026-08-01', 'to' => '2026-08-31']);
        $this->assertStringContainsString('Aug 1, 2026 – Aug 31, 2026', $html);
    }

    /* ------------------------------------------------------------------
     | 2 · Inventory Report
     ------------------------------------------------------------------ */

    public function test_the_inventory_report_lists_items_in_use_with_serviceability(): void
    {
        $month = $this->month();
        $this->item('Old scanner', ['retired_at' => now()]);
        $this->returnLog($month['returned'], '2026-10-07', '2026-10-07 16:20:00', 'Damaged', ['remarks' => 'Cracked lens']);

        $rows = array_slice($this->csv('inventory'), 1);

        $this->assertSame([
            ['5', 'pc', 'HDMI cable', 'Serviceable'],
            ['5', 'pc', 'Projector (Epson)', 'Non-serviceable'],
            ['5', 'box', 'Whiteboard markers', 'Serviceable'],
        ], $rows, 'Retired equipment is listed, or serviceability is wrong');
    }

    public function test_the_inventory_report_filters_by_category_and_status(): void
    {
        $month = $this->month();
        $this->returnLog($month['returned'], '2026-10-07', '2026-10-07 16:20:00', 'Lost');

        $this->assertSame(['HDMI cable'], array_column(array_slice($this->csv('inventory', ['category' => 'Cables']), 1), 2));
        $this->assertSame(['Projector (Epson)'], array_column(array_slice($this->csv('inventory', ['status' => 'non-serviceable']), 1), 2));
        $this->assertSame(['HDMI cable', 'Whiteboard markers'], array_column(array_slice($this->csv('inventory', ['status' => 'serviceable']), 1), 2));

        // A bad status is dropped with an inline error, not a failed page.
        $html = $this->screen('inventory', ['status' => 'broken']);
        $this->assertStringContainsString('Choose Serviceable or Non-serviceable.', $html);
        $this->assertStringContainsString('<option value="Cables"', $html, 'The category filter has no options');
    }

    /* ------------------------------------------------------------------
     | 3 · Not Yet Returned
     ------------------------------------------------------------------ */

    public function test_not_yet_returned_lists_open_loans_soonest_due_first_with_an_overdue_marker_in_words(): void
    {
        $this->month();

        $rows = array_slice($this->csv('not-returned'), 1);
        $this->assertSame([
            ['Leo Reyes', '—', 'HDMI cable, 2 pc', 'Oct 8, 2026', 'Oct 10, 2026, end of day (Overdue)'],
            ['Mia Santos', 'BSIT · 3rd Year', 'Projector (Epson), 1 pc', 'Oct 14, 2026', 'Oct 14, 2026, 4:00 PM'],
        ], $rows, 'Returned, issued or voided loans leaked in, or the order is wrong');

        // On screen and on paper "Overdue" is a word, not just a colour.
        $this->assertSame(1, substr_count($this->screen('not-returned'), 'data-mark'));
        $print = $this->actingAs($this->admin)->get(route('admin.reports.print', ['report' => 'not-returned']))->getContent();
        $this->assertStringContainsString('<b class="mark">Overdue</b>', $print);
    }

    public function test_not_yet_returned_ignores_any_date_range(): void
    {
        $this->month();

        $this->assertCount(3, $this->csv('not-returned', ['from' => '2026-01-01', 'to' => '2026-01-31']));
        $this->assertStringNotContainsString('id="report-from"', $this->screen('not-returned'));
    }

    /* ------------------------------------------------------------------
     | 4 · Most Borrowed Equipment
     ------------------------------------------------------------------ */

    public function test_most_borrowed_ranks_items_by_times_borrowed_with_shared_ranks_for_ties(): void
    {
        $mia = $this->student('Mia Santos');
        [$a, $b, $c, $d] = [$this->item('Alpha'), $this->item('Bravo', ['unit' => 'set']), $this->item('Charlie'), $this->item('Delta')];
        $lend = fn (Equipment $item, int $times, int $qty = 1) => collect(range(1, $times))->each(
            fn () => $this->loan($mia, $item, ['borrow_date' => '2026-10-10 00:00:00', 'return_date' => '2026-10-11 00:00:00', 'quantity' => $qty])
        );
        $lend($c, 3);
        $lend($b, 2, 2);
        $lend($a, 2);
        $lend($d, 1);
        $this->loan($mia, $d, ['borrow_date' => '2026-10-10 00:00:00', 'return_date' => '2026-10-11 00:00:00', 'voided_at' => now()]);

        $this->assertSame([
            ['1', 'Charlie', '3', '3 pc'],
            ['2', 'Bravo', '2', '4 set'],
            ['2', 'Alpha', '2', '2 pc'],
            ['4', 'Delta', '1', '1 pc'],
        ], array_slice($this->csv('most-borrowed'), 1));

        // The date range applies.
        $this->assertCount(1, $this->csv('most-borrowed', ['from' => '2026-10-11', 'to' => '2026-10-14']));
    }

    public function test_most_borrowed_shows_the_top_10_by_default_and_25_or_50_on_request(): void
    {
        $mia = $this->student('Mia Santos');
        foreach (range(1, 30) as $i) {
            $this->loan($mia, $this->item(sprintf('Item %02d', $i)), ['borrow_date' => '2026-10-10 00:00:00', 'return_date' => '2026-10-11 00:00:00']);
        }

        $this->assertCount(10, array_slice($this->csv('most-borrowed'), 1));
        $this->assertCount(25, array_slice($this->csv('most-borrowed', ['limit' => '25']), 1));
        $this->assertCount(30, array_slice($this->csv('most-borrowed', ['limit' => '50']), 1));

        $html = $this->screen('most-borrowed', ['limit' => '7']);
        $this->assertStringContainsString('Show the top 10, 25 or 50.', $html);
        $this->assertSame(10, substr_count($html, 'data-report-row'));
        foreach (ReportsController::TOP_LIMITS as $limit) {
            $this->assertStringContainsString('<option value="'.$limit.'"', $html);
        }
    }

    /* ------------------------------------------------------------------
     | 5 · Defective Items
     ------------------------------------------------------------------ */

    public function test_defective_items_come_from_unresolved_incidents_with_remarks_or_the_condition(): void
    {
        $month = $this->month();
        $this->returnLog($month['returned'], '2026-10-07', '2026-10-07 16:20:00', 'Damaged', ['remarks' => 'Cracked lens']);
        $this->returnLog($month['overdue'], '2026-10-12', '2026-10-12 10:00:00', 'Lost');
        $this->returnLog($month['timed'], '2026-10-13', '2026-10-13 10:00:00', 'Damaged', [
            'remarks' => 'Bent pin', 'resolution' => 'Replaced', 'resolved_at' => now(), 'resolved_by' => $this->admin->id,
        ]);

        $this->assertSame([
            ['1', 'pc', 'Projector (Epson)', 'Cracked lens'],
            ['2', 'pc', 'HDMI cable', 'Lost'],
        ], array_slice($this->csv('defective'), 1), 'Resolved or clean returns are listed, or the defect text is wrong');
    }

    /* ------------------------------------------------------------------
     | Print, PDF and the signature block
     ------------------------------------------------------------------ */

    public function test_print_carries_the_title_range_generated_date_table_and_signatures_for_every_report(): void
    {
        $this->month();

        foreach (ReportsController::REPORTS as $report => $definition) {
            $html = $this->actingAs($this->admin)->get(route('admin.reports.print', ['report' => $report]))
                ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->getContent();

            $this->assertStringContainsString('<h1 class="report-title">'.e($definition['title']).'</h1>', $html, $report);
            $this->assertStringContainsString($definition['dated'] ? 'Sep 15, 2026 – Oct 14, 2026' : 'Oct 14, 2026, 3:00 PM', $html, $report);
            $this->assertStringContainsString('Generated</th>', $html);
            $this->assertStringContainsString('Oct 14, 2026, 3:00 PM by Quincy Jane Oliver (Admin)', $html);
            // After the table, or after the empty message when there are no rows.
            $this->assertMatchesRegularExpression('/(<table class="report-table">|<p class="report-empty">).*data-signatures/s', $html, $report.': the signatures are not after the table');

            $this->assertStringContainsString('Prepared By:', $html);
            $this->assertMatchesRegularExpression('/data-prepared-by>Quincy Jane Oliver/', $html);
            $this->assertStringContainsString('Noted By:', $html);
            $this->assertMatchesRegularExpression('/data-noted-by>Dr\. Ramon Cruz/', $html);
            $this->assertStringContainsString('Dean, CICT', $html);
            $this->assertStringNotContainsString('<aside', $html, 'The print view carries the app sidebar');
        }
    }

    public function test_the_signature_block_stays_together_and_an_empty_noted_by_leaves_the_line_blank(): void
    {
        config(['office.noted_by' => ['name' => '', 'position' => '']]);

        $html = $this->actingAs($this->admin)->get(route('admin.reports.print', ['report' => 'inventory']))->getContent();

        $this->assertMatchesRegularExpression('/data-noted-by>&nbsp;<\/div>/', $html, 'An empty Noted By printed something other than a blank line');
        $this->assertMatchesRegularExpression('/\.signatures \{[^}]*page-break-inside: avoid; break-inside: avoid;/', $html);
    }

    public function test_the_pdf_is_an_a4_pdf_with_the_signature_block_for_every_report(): void
    {
        $this->month();

        foreach (ReportsController::REPORTS as $report => $definition) {
            $response = $this->actingAs($this->admin)->get(route('admin.reports.pdf', ['report' => $report]));
            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $expected = $definition['dated'] ? 'cict-'.$definition['file'].'_2026-09-15_to_2026-10-14.pdf' : 'cict-'.$definition['file'].'_2026-10-14.pdf';
            $this->assertStringContainsString($expected, $response->headers->get('Content-Disposition'));
        }

        // The PDF is compressed; what dompdf lays out is the same view, rendered here.
        $html = view('admin.reports.pdf', [
            'official' => true, 'logoSrc' => 'logo.png', 'emptyMessage' => 'None.',
            'columns' => ReportsController::COLUMNS['defective'], 'rows' => [[1, 'pc', 'Projector', 'Cracked lens']],
            'meta' => [
                'title' => 'List of Defective Items', 'range_label' => 'As of', 'range' => 'Oct 14, 2026, 3:00 PM', 'filters' => 'None',
                'generated_at' => 'Oct 14, 2026, 3:00 PM', 'generated_by' => 'Quincy Jane Oliver (Admin)', 'total' => 1, 'noun' => 'defect',
                'prepared_by' => 'Quincy Jane Oliver', 'noted_by' => ['name' => 'Dr. Ramon Cruz', 'position' => 'Dean, CICT'],
            ],
        ])->render();
        $this->assertStringContainsString('Prepared By:', $html);
        $this->assertStringContainsString('Dr. Ramon Cruz', $html);
        $this->assertStringContainsString('<td>Cracked lens</td>', $html);
    }

    public function test_the_csv_neutralises_formula_cells(): void
    {
        $mallory = $this->student('=HYPERLINK("http://evil.example","click")');
        $this->loan($mallory, $this->item('@SUM(1)'), ['borrow_date' => '2026-10-10 00:00:00', 'return_date' => '2026-10-11 00:00:00']);

        $row = $this->csv('borrowers')[1];
        $this->assertSame("'=HYPERLINK(\"http://evil.example\",\"click\")", $row[1]);
        $this->assertSame("'@SUM(1), 1 pc", $row[2]);
        $this->assertSame("'".'=HYPERLINK("http://evil.example","click")', $this->csv('not-returned')[1][0]);
    }

    public function test_pdf_and_print_refuse_more_than_the_row_cap(): void
    {
        $stamp = now()->toDateTimeString();
        foreach (array_chunk(range(1, ReportsController::MAX_PAGED_ROWS + 1), 500) as $chunk) {
            Equipment::insert(array_map(fn ($i) => [
                'equipment_name' => 'Item '.$i, 'quantity' => 1, 'available_quantity' => 1, 'status' => 'Available',
                'created_at' => $stamp, 'updated_at' => $stamp,
            ], $chunk));
        }

        foreach (['admin.reports.pdf', 'admin.reports.print'] as $route) {
            $this->actingAs($this->admin)->get(route($route, ['report' => 'inventory']))
                ->assertRedirect(route('admin.reports', ['report' => 'inventory']))
                ->assertSessionHas('error', fn ($message) => str_contains($message, '2,001 items') && str_contains($message, 'Export CSV'));
        }

        $this->assertSame(0, ActivityLog::where('type', 'report_exported')->count());
        $this->assertStringContainsString('PDF and Print stop at 2,000 rows', $this->screen('inventory'));
        $this->assertCount(ReportsController::MAX_PAGED_ROWS + 2, $this->csv('inventory'), 'The CSV is capped too');
    }

    public function test_each_official_export_is_logged_with_its_report(): void
    {
        $this->month();

        $this->csv('borrowers');
        $this->actingAs($this->admin)->get(route('admin.reports.print', ['report' => 'defective']));
        $this->actingAs($this->admin)->get(route('admin.reports.pdf', ['report' => 'inventory']));

        $entries = ActivityLog::where('type', 'report_exported')->orderBy('id')->get();
        $this->assertSame(['borrowers', 'defective', 'inventory'], $entries->pluck('meta.report')->all());
        $this->assertSame(['csv', 'print', 'pdf'], $entries->pluck('meta.format')->all());
        $this->assertSame('Exported Borrower Report as CSV (4 rows)', $entries[0]->details);
    }

    public function test_the_export_links_carry_the_report_and_its_filters(): void
    {
        $this->month();

        $html = $this->screen('inventory', ['category' => 'Cables', 'status' => 'serviceable']);
        foreach (['csv', 'pdf', 'print'] as $format) {
            $this->assertStringContainsString(e(route('admin.reports.'.$format, ['report' => 'inventory', 'category' => 'Cables', 'status' => 'serviceable'])), $html);
        }
    }

    /* ------------------------------------------------------------------
     | Empty results
     ------------------------------------------------------------------ */

    public function test_each_report_has_its_own_empty_state_and_still_prints_signed(): void
    {
        $titles = [
            'borrowers' => 'Nothing borrowed in this date range',
            'inventory' => 'No equipment in use',
            'not-returned' => 'Everything has been returned',
            'most-borrowed' => 'Nothing borrowed in this date range',
            'defective' => 'No defective items',
        ];

        foreach ($titles as $report => $title) {
            $html = $this->screen($report);
            $this->assertStringContainsString('data-report-empty="'.$report.'"', $html);
            $this->assertStringContainsString($title, $html);
            $this->assertStringNotContainsString('data-official-table', $html);
            $this->assertStringContainsString('data-export="print"', $html, 'An empty official report cannot be printed and signed');

            $print = $this->actingAs($this->admin)->get(route('admin.reports.print', ['report' => $report]))->getContent();
            $this->assertStringContainsString('<p class="report-empty">', $print, $report);
            $this->assertStringContainsString('data-signatures', $print, $report);

            $this->assertCount(1, $this->csv($report), $report.': an empty CSV should be the header row alone');
        }
    }

    public function test_a_narrowed_inventory_with_no_match_offers_to_clear_the_filters(): void
    {
        $this->item('Tripod', ['category' => 'Stands']);

        $html = $this->screen('inventory', ['status' => 'non-serviceable']);
        $this->assertStringContainsString('No equipment matches these filters', $html);
        $this->assertStringContainsString('href="'.e(route('admin.reports', ['report' => 'inventory'])).'"', $html);
    }
}
