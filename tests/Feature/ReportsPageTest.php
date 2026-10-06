<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Reports screen: the activity log behind admin-only access, narrowed by
 * query-string filters that all go through ActivityLog::filter().
 */
class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

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
        // $attrs first: `+` keeps the left side's keys, so an actor or
        // occurred_at passed in wins over the defaults.
        return ActivityLog::record($type, $attrs + ['actor' => null, 'occurred_at' => Carbon::parse($moment)]);
    }

    private function report(array $query = [])
    {
        return $this->actingAs($this->admin)->get(route('admin.reports', $query));
    }

    /** Ids on the page, in the order shown. */
    private function shown(array $query = []): array
    {
        return $this->report($query)->assertOk()->viewData('activities')->getCollection()->pluck('id')->all();
    }

    /* ------------------------------------------------------------------
     | Access
     ------------------------------------------------------------------ */

    public function test_an_admin_opens_it_from_the_sidebar_and_it_is_never_cached(): void
    {
        $this->entry('2026-10-07 09:00');

        $response = $this->report()->assertOk()->assertSee('Reports');
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('admin.reports'), '#').'"\s+aria-current="page"#', $html);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_a_borrower_gets_403_and_a_guest_is_sent_to_sign_in(): void
    {
        $student = User::factory()->create(['user_type' => 'Student']);

        $this->actingAs($student)->get('/admin/reports')->assertForbidden();
        auth()->logout();
        $this->get('/admin/reports')->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------
     | Dates
     ------------------------------------------------------------------ */

    public function test_the_default_range_is_the_last_30_days_including_today(): void
    {
        $tooOld = $this->entry('2026-09-07 23:59:59');
        $firstDay = $this->entry('2026-09-08 00:00:00');
        $today = $this->entry('2026-10-07 13:59:00');

        $response = $this->report()->assertOk();

        $this->assertSame(['2026-09-08', '2026-10-07'], [
            $response->viewData('resolved')['filters']['from'],
            $response->viewData('resolved')['filters']['to'],
        ]);
        $this->assertTrue($response->viewData('resolved')['defaultRange']);
        $this->assertSame([$today->id, $firstDay->id], $this->shown());
        $this->assertNotContains($tooOld->id, $this->shown());
    }

    public function test_the_date_range_includes_the_whole_end_day(): void
    {
        $before = $this->entry('2026-09-30 23:59:59');
        $start = $this->entry('2026-10-01 00:00:00');
        $endLate = $this->entry('2026-10-03 23:30:00');
        $after = $this->entry('2026-10-04 00:00:00');

        $this->assertSame([$endLate->id, $start->id], $this->shown(['from' => '2026-10-01', 'to' => '2026-10-03']));
        $this->assertNotContains($before->id, $this->shown(['from' => '2026-10-01', 'to' => '2026-10-03']));
        $this->assertNotContains($after->id, $this->shown(['from' => '2026-10-01', 'to' => '2026-10-03']));
    }

    public function test_an_inverted_or_unreadable_range_shows_an_inline_error_and_falls_back(): void
    {
        $recent = $this->entry('2026-10-06 10:00');

        $response = $this->report(['from' => '2026-10-05', 'to' => '2026-10-01'])->assertOk();
        $response->assertSee('The start date is after the end date', false);
        $response->assertSee('role="alert"', false);
        $this->assertSame('2026-09-08', $response->viewData('resolved')['filters']['from']);
        $this->assertSame([$recent->id], $response->viewData('activities')->getCollection()->pluck('id')->all());

        $this->report(['from' => 'yesterday-ish', 'to' => '2026-13-45'])->assertOk()
            ->assertSee('Enter the start date as a date.');
        $this->report(['type' => 'loan_teleported'])->assertOk()
            ->assertSee('That activity type is not one the log records.');
    }

    /* ------------------------------------------------------------------
     | The other filters
     ------------------------------------------------------------------ */

    public function test_the_type_filter_takes_one_type_or_a_whole_group(): void
    {
        $created = $this->entry('2026-10-05 09:00', 'loan_created');
        $checkedIn = $this->entry('2026-10-05 10:00', 'loan_checked_in');
        $approved = $this->entry('2026-10-05 11:00', 'request_approved');
        $login = $this->entry('2026-10-05 12:00', 'login');

        $this->assertSame([$checkedIn->id], $this->shown(['type' => 'loan_checked_in']));
        $this->assertSame([$checkedIn->id, $created->id], $this->shown(['type' => 'Loans']));
        $this->assertSame([$approved->id], $this->shown(['type' => 'Requests']));
        $this->assertSame([$login->id], $this->shown(['type' => 'Account & system']));
    }

    public function test_equipment_person_status_and_search_each_narrow_the_report(): void
    {
        $mia = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);
        $projector = Equipment::create(['equipment_name' => 'Projector (Epson)', 'quantity' => 5, 'available_quantity' => 5, 'status' => 'Available']);
        $clicker = Equipment::create(['equipment_name' => 'Clicker', 'quantity' => 5, 'available_quantity' => 5, 'status' => 'Available',
            'retired_at' => now()]);

        $lent = $this->entry('2026-10-05 09:00', 'loan_created', [
            'actor' => $this->admin, 'subject' => $mia, 'equipment' => $projector, 'status_to' => 'Borrowed',
            'details' => 'Lent 1 × Projector (Epson) to Mia Santos.',
        ]);
        $requested = $this->entry('2026-10-05 10:00', 'request_submitted', [
            'actor' => $mia, 'subject' => $mia, 'equipment' => $clicker, 'status_to' => 'Pending',
            'details' => 'Requested 1 × Clicker.',
        ]);
        $retired = $this->entry('2026-10-05 11:00', 'equipment_retired', [
            'actor' => $this->admin, 'equipment' => $clicker, 'status_to' => 'Retired', 'details' => 'Retired Clicker: cracked casing.',
        ]);

        // Retired equipment is still offered, so its history can be reported.
        $this->assertTrue($this->report()->viewData('equipmentOptions')->contains('id', $clicker->id));

        $this->assertSame([$lent->id], $this->shown(['equipment_id' => $projector->id]));
        $this->assertSame([$retired->id, $requested->id], $this->shown(['equipment_id' => $clicker->id]));

        // Mia acted on her request and was the subject of the loan.
        $this->assertSame([$requested->id, $lent->id], $this->shown(['user_id' => $mia->id]));
        // The admin only ever acted.
        $this->assertSame([$retired->id, $lent->id], $this->shown(['user_id' => $this->admin->id]));

        $this->assertSame([$requested->id], $this->shown(['status' => 'Pending']));
        $this->assertEqualsCanonicalizing(['Borrowed', 'Pending', 'Retired'], $this->report()->viewData('statusOptions')->all());

        $this->assertSame([$retired->id], $this->shown(['q' => 'cracked casing']));       // details
        $this->assertSame([$requested->id, $lent->id], $this->shown(['q' => 'mia']));     // names
        $this->assertSame([$requested->id], $this->shown(['q' => 'Clicker', 'type' => 'Requests']));  // combined
    }

    /* ------------------------------------------------------------------
     | Results
     ------------------------------------------------------------------ */

    public function test_results_paginate_50_at_a_time_and_keep_the_query_string(): void
    {
        foreach (range(1, 55) as $i) {
            $this->entry('2026-10-06 08:00:00', 'login', ['details' => 'Signed in.']);
        }
        $this->entry('2026-10-06 09:00:00', 'logout');

        $first = $this->report(['type' => 'login', 'q' => 'Signed'])->assertOk();
        $this->assertCount(50, $first->viewData('activities')->getCollection());
        $first->assertSeeInOrder(['Showing', '1–50', 'of', '55', 'activities'], false);

        $next = $first->viewData('activities')->nextPageUrl();
        $this->assertStringContainsString('type=login', $next);
        $this->assertStringContainsString('q=Signed', $next);
        $this->assertStringContainsString('page=2', $next);
        $first->assertSee(e($next), false);

        $this->assertCount(5, $this->actingAs($this->admin)->get($next)->assertOk()->viewData('activities')->getCollection());
    }

    public function test_the_summary_strip_comes_from_one_grouped_count_of_the_same_filtered_query(): void
    {
        foreach (['loan_created', 'loan_created', 'loan_issued', 'loan_checked_in', 'loan_checked_in', 'loan_checked_in',
            'request_approved', 'request_declined', 'login'] as $i => $type) {
            $this->entry('2026-10-06 08:0'.$i.':00', $type);
        }
        $this->entry('2026-08-01 08:00', 'loan_created');   // outside the default range

        DB::enableQueryLog();
        $response = $this->report()->assertOk();
        $counts = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn ($sql) => str_contains($sql, 'activity_logs') && stripos($sql, 'count(') !== false);

        $this->assertCount(1, $counts, 'Expected one COUNT over activity_logs, got: '.$counts->implode(' | '));
        $this->assertStringContainsStringIgnoringCase('group by', $counts->first());

        $this->assertSame(
            [['Activities', '9'], ['Loans recorded', '3'], ['Returns', '3'], ['Requests decided', '2']],
            collect($response->viewData('summary'))->map(fn ($s) => [$s['label'], $s['value']])->all()
        );
        $this->assertSame('2 lent · 1 issued for good', $response->viewData('summary')[1]['sub']);
        $this->assertSame('1 approved · 1 declined', $response->viewData('summary')[3]['sub']);

        // Filtered, the strip follows the filter.
        $this->assertSame('3', $this->report(['type' => 'loan_checked_in'])->viewData('summary')[2]['value']);
        $this->assertSame('0', $this->report(['type' => 'loan_checked_in'])->viewData('summary')[1]['value']);
    }

    public function test_the_table_has_the_columns_a_sticky_header_and_readable_cells(): void
    {
        $mia = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);
        $this->entry('2026-10-07 14:35:00', 'loan_checked_in', [
            'actor' => $this->admin, 'subject' => $mia, 'equipment_name' => 'Projector (Epson)', 'quantity' => 2,
            'status_from' => 'Borrowed', 'status_to' => 'Returned', 'details' => 'Checked in 2 × Projector (Epson) in Good condition.',
            'occurred_at' => Carbon::parse('2026-10-06 14:35:00'),
        ]);
        $this->entry('2026-10-06 08:00:00', 'loan_marked_overdue', ['status_from' => 'Borrowed', 'status_to' => 'Overdue']);

        $html = $this->report()->assertOk()->getContent();

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('overflow-auto', $html);
        preg_match_all('/<th scope="col"[^>]*>\s*(.*?)\s*<\/th>/s', $html, $headers);
        $this->assertSame(['Date and time', 'Activity', 'Done by', 'Equipment', 'Affected person', 'Qty', 'Status', 'Details'], $headers[1]);
        $this->assertMatchesRegularExpression('/<th scope="col"\s+class="sticky top-0/', $html);
        $this->assertMatchesRegularExpression('/<table class="[^"]*text-base/', $html);

        // Date over time, stacked to keep the column narrow.
        $this->assertMatchesRegularExpression('/>Oct 6, 2026<\/span>\s*<span[^>]*>2:35 PM</', $html);
        $this->assertStringContainsString('Checked in', $html);
        $this->assertMatchesRegularExpression('/bg-primary-100[^"]*"[^>]*>\s*Loans\s*</', $html);   // group badge
        $this->assertStringContainsString('Quincy Jane Oliver', $html);
        $this->assertStringContainsString('System', $html);                                        // the sweep
        $this->assertMatchesRegularExpression('/Borrowed <span aria-label="to">→<\/span><\/span>\s*<span class="block font-semibold whitespace-nowrap">Returned/', $html);
    }

    /* ------------------------------------------------------------------
     | Empty states
     ------------------------------------------------------------------ */

    public function test_nothing_recorded_yet_has_its_own_empty_state(): void
    {
        $response = $this->report()->assertOk();

        $response->assertSee('No activity recorded yet.');
        $response->assertSee('Activity appears here as soon as equipment is lent, returned or changed.');
        $response->assertDontSee('<table', false);
    }

    public function test_filters_that_match_nothing_echo_the_filters_and_offer_to_clear_them(): void
    {
        $this->entry('2026-10-06 10:00', 'login', ['details' => 'Signed in.']);

        $response = $this->report(['q' => 'zzz', 'type' => 'Returns'])->assertOk();

        $response->assertSee('No activity matches these filters.');
        $response->assertSee('Search:');
        $response->assertSee('“zzz”', false);
        $response->assertSee('All returns');
        $response->assertSee('Sep 8, 2026 – Oct 7, 2026');
        $response->assertSee('href="'.route('admin.reports').'"', false);
        $response->assertSee('Clear filters');
        $response->assertDontSee('<table', false);
        $response->assertDontSee('No activity recorded yet.');
    }
}
