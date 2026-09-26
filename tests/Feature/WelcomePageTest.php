<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing page, built to design-reference/Landing.dc.html.
 *
 * The mockup supplies layout and type; the numbers and claims come from the
 * application. What is pinned here is exactly that split: every figure is
 * read (shelf, counts, loan period, hours), every claim the mockup got wrong
 * stays corrected, and the six sections the reference defines are the ones
 * present.
 */
class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function html(string $uri = '/'): string
    {
        return $this->get($uri)->assertOk()->getContent();
    }

    /** The status pill as a reader sees it: tags stripped, whitespace collapsed. */
    private function pill(string $html): string
    {
        preg_match('/<span[^>]*data-open-status="[^"]*"[^>]*>(.*?)<\/span>\s*<\/span>/s', $html, $m);

        return trim(preg_replace('/\s+/', ' ', strip_tags($m[1] ?? '')));
    }

    private function item(string $name, int $quantity, int $available, array $extra = []): Equipment
    {
        return Equipment::create(array_merge([
            'equipment_name' => $name, 'description' => 'd',
            'quantity' => $quantity, 'available_quantity' => $available, 'status' => 'Available',
        ], $extra));
    }

    /* ---------------------------------------------------------------------
     | Structure
     --------------------------------------------------------------------- */

    public function test_the_sections_are_the_ones_the_reference_defines(): void
    {
        $html = $this->html();

        foreach (['data-welcome-shelf', 'data-reminder-card', 'data-welcome-facts', 'id="how"', 'id="rules"', 'id="visit"', '<footer'] as $marker) {
            $this->assertStringContainsString($marker, $html, "Missing: $marker");
        }

        // The nav's anchors point at sections that exist.
        foreach (['#how', '#rules', '#visit'] as $anchor) {
            $this->assertStringContainsString('href="'.$anchor.'"', $html);
        }

        $this->assertStringContainsString('grid-hairlines', $html, 'The navy grounds lost their grid texture');
        $this->assertStringNotContainsString('auth.css', $html);
    }

    public function test_there_is_one_h1_and_it_is_the_headline(): void
    {
        $html = $this->html();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*The equipment room, without the paper logbook\.\s*<\/h1>/', $html);
    }

    public function test_the_layout_reflows_rather_than_fixing_widths(): void
    {
        $html = $this->html();

        // Hero, facts, steps, rules and visit all reflow on auto-fit grids.
        $this->assertGreaterThanOrEqual(5, substr_count($html, 'grid-cols-[repeat(auto-fit,minmax(min(100%,'));
        // Display type scales down instead of overflowing a phone.
        $this->assertStringContainsString('text-[clamp(2.5rem,1.2rem+4.4vw,4rem)]', $html);
    }

    public function test_figures_and_step_labels_use_plex_mono(): void
    {
        $this->item('Projector (Epson)', 4, 2);
        $html = $this->html();

        $this->assertStringContainsString('family=IBM+Plex+Mono', $html);
        $this->assertMatchesRegularExpression('/font-mono[^"]*"[^>]*>01</', $html);
        $this->assertMatchesRegularExpression('/font-mono[^"]*">\s*2 of 4\s*</', $html);
    }

    /* ---------------------------------------------------------------------
     | Links and auth
     --------------------------------------------------------------------- */

    public function test_the_actions_go_to_the_existing_routes(): void
    {
        $html = $this->html();

        $this->assertGreaterThanOrEqual(3, substr_count($html, 'href="'.route('login').'"'), 'Nav, hero and visit panel should all sign in');
        $this->assertSame(2, substr_count($html, 'href="'.route('register').'"'));
        $this->assertStringContainsString('href="'.route('legal.terms').'"', $html);
        $this->assertStringContainsString('href="'.route('legal.privacy').'"', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_a_signed_in_borrower_is_sent_to_their_dashboard(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student', 'name' => 'Mia Santos']);

        $html = $this->actingAs($borrower)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('borrower.dashboard').'"', $html);
        $this->assertStringContainsString('Go to your dashboard', $html);
        $this->assertStringContainsString('Signed in as Mia Santos', $html);
        $this->assertStringNotContainsString('href="'.route('register').'"', $html);
    }

    public function test_a_signed_in_admin_is_sent_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        $this->assertStringContainsString(
            'href="'.route('admin.dashboard').'"',
            $this->actingAs($admin)->get('/')->getContent()
        );
    }

    public function test_the_named_welcome_route_serves_the_same_page(): void
    {
        $this->assertStringContainsString('data-landing', $this->html(route('auth.welcome')));
    }

    /* ---------------------------------------------------------------------
     | The shelf card — real data, classified like the inventory page
     --------------------------------------------------------------------- */

    public function test_shelf_rows_come_from_the_equipment_table(): void
    {
        $this->item('Projector (Epson)', 10, 7);

        $html = $this->html();

        $this->assertStringContainsString('Projector (Epson)', $html);
        $this->assertMatchesRegularExpression('/>\s*7 of 10\s*</', $html);
        $this->assertStringContainsString('style="width: 70%"', $html);
        $this->assertStringContainsString('aria-label="Partly out — 7 of 10 on the shelf"', $html);
    }

    /** Colour follows Equipment::availabilityState(): full, partly out, ≤30%, none. */
    public function test_each_state_gets_its_reference_colour(): void
    {
        $this->item('Full', 10, 10);
        $this->item('Partly', 10, 6);
        $this->item('Low', 10, 3);
        $this->item('Empty', 3, 0);

        $html = $this->html();

        $this->assertStringContainsString('data-state="all-in"', $html);
        $this->assertStringContainsString('bg-[oklch(0.6_0.14_158)]', $html, 'full is not green');
        $this->assertStringContainsString('data-state="partial"', $html);
        $this->assertStringContainsString('bg-[oklch(0.55_0.17_258)]', $html, 'partly out is not blue');
        $this->assertStringContainsString('data-state="low"', $html);
        $this->assertStringContainsString('bg-[oklch(0.72_0.15_70)]', $html, 'low is not amber');
        $this->assertStringContainsString('data-state="out"', $html);
        $this->assertStringContainsString('bg-[oklch(0.6_0.18_25)]', $html, 'none left is not red');
        $this->assertStringContainsString('none left', $html);
    }

    public function test_at_most_five_rows_and_low_or_out_come_first(): void
    {
        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo'] as $name) {
            $this->item($name, 10, 10);           // fully stocked
        }
        $this->item('Zulu (low)', 10, 2);         // running low
        $this->item('Yankee (out)', 4, 0);        // none left
        $this->item('Retired thing', 50, 50, ['retired_at' => now(), 'status' => 'Unavailable']);

        $html = $this->html();

        $this->assertSame(5, substr_count($html, 'data-shelf-item'));
        $this->assertStringNotContainsString('Retired thing', $html);

        $out = strpos($html, 'Yankee (out)');
        $low = strpos($html, 'Zulu (low)');
        $full = strpos($html, 'Alpha');
        $this->assertNotFalse($out);
        $this->assertNotFalse($low);
        $this->assertLessThan($low, $out, 'Out should lead low');
        $this->assertLessThan($full, $low, 'Low should lead fully stocked');
        // Two problem rows plus three of the five full ones.
        $this->assertStringNotContainsString('Echo', $html);
    }

    public function test_unit_and_item_type_counts_are_computed_not_typed(): void
    {
        $this->item('Projector', 7, 7);
        $this->item('HDMI cable', 5, 1);
        $this->item('Old camera', 9, 9, ['retired_at' => now(), 'status' => 'Unavailable']);

        $html = $this->html();

        // Card summary and facts band both read 12 / 2 — retired stock excluded.
        $this->assertMatchesRegularExpression('/>12<\/span> units\s+across <span class="font-mono">2<\/span> item types/', $html);
        $this->assertMatchesRegularExpression('/>12<\/dd>/', $html);
        $this->assertMatchesRegularExpression('/>2<\/dd>/', $html);
    }

    public function test_an_empty_inventory_says_so_instead_of_inventing_rows(): void
    {
        $html = $this->html();

        $this->assertSame(0, substr_count($html, 'data-shelf-item'));
        $this->assertStringContainsString('Nothing has been added to the inventory yet.', $html);
    }

    /* ---------------------------------------------------------------------
     | Hours and loan period — one config value each
     --------------------------------------------------------------------- */

    public function test_the_status_pill_says_open_today_on_a_working_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00', 'Asia/Manila')); // a Monday

        $html = $this->html();

        $this->assertStringContainsString('data-open-status="open"', $html);
        $this->assertSame('Equipment room open today · 8:00 AM – 5:00 PM', $this->pill($html));
    }

    public function test_the_status_pill_says_closed_today_at_the_weekend(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-27 10:00', 'Asia/Manila')); // a Sunday

        $html = $this->html();

        $this->assertStringContainsString('data-open-status="closed"', $html);
        $this->assertSame('Equipment room closed today · Mon–Fri, 8:00 AM – 5:00 PM', $this->pill($html));
    }

    public function test_the_status_pill_says_closed_after_closing_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 17:30', 'Asia/Manila')); // Monday evening

        $this->assertStringContainsString('data-open-status="closed"', $this->html());
    }

    public function test_one_hours_config_drives_the_pill_the_facts_and_the_visit_panel(): void
    {
        config(['office.hours' => ['days' => [1, 2, 3, 4, 5, 6], 'open' => '07:30', 'close' => '16:00']]);
        Carbon::setTestNow(Carbon::parse('2026-10-03 09:00', 'Asia/Manila')); // a Saturday

        $html = $this->html();

        $this->assertSame('Equipment room open today · 7:30 AM – 4:00 PM', $this->pill($html));
        $this->assertMatchesRegularExpression('/>Mon–Sat<\/dd>/', $html);
        $this->assertStringContainsString('Monday to Saturday, 7:30 AM – 4:00 PM', $html);
        $this->assertStringContainsString('by 4:00 PM.', $html, 'The reminder card kept a hard-coded closing time');
    }

    public function test_the_loan_period_is_read_from_config(): void
    {
        config(['office.loan_days' => 10]);

        $html = $this->html();

        $this->assertMatchesRegularExpression('/>10 days<\/dd>/', $html);
        $this->assertStringContainsString('Loans run ten days by default', $html);
    }

    /* ---------------------------------------------------------------------
     | Claims the mockup got wrong stay corrected
     --------------------------------------------------------------------- */

    /** There is no account approval step, and nothing defines a turnaround. */
    public function test_no_account_approval_timeframe_is_promised(): void
    {
        $html = $this->html();

        foreach (['within one working day', 'within a working day', 'approves new accounts', 'Accounts approved', 'usually the same working day'] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "The landing page claims: $claim");
        }
    }

    /** Reminders go out on the due date (sendReturnAlertNotification), not the day before. */
    public function test_the_reminder_timing_matches_the_scheduled_job(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('Email reminder on the day it is due', $html);
        $this->assertStringNotContainsString('the day before it is due', $html);
    }

    /** A request carries item, quantity and remarks — no dates — and decisions are not emailed. */
    public function test_the_steps_describe_the_request_form_that_exists(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('the dates you need it', $html);
        $this->assertStringNotContainsString('hear back by email', $html);
        $this->assertStringContainsString('the decision shows on your dashboard', $html);
    }

    public function test_the_contact_falls_back_to_the_counter_when_no_address_is_set(): void
    {
        config(['office.email' => '']);

        $html = $this->html();

        $this->assertStringContainsString('Ask at the equipment room', $html);
        $this->assertStringNotContainsString('mailto:', $html);
    }

    public function test_keyboard_users_get_a_skip_link_and_focus_rings(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('href="#landing-main"', $html);
        $this->assertStringContainsString('id="landing-main"', $html);
        $this->assertStringContainsString('focus-visible:ring-2', $html);
    }
}
