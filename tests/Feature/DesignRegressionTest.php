<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks in the redesign pass. These assert markup contracts that are easy to
 * undo by accident — a purged stylesheet rule, a dropped skip link, a page that
 * drifts back onto the old scale — and that nothing else in the suite covers.
 */
class DesignRegressionTest extends TestCase
{
    use RefreshDatabase;

    /** Every page that hangs off the admin sidebar. */
    public static function adminPages(): array
    {
        return [
            'dashboard'     => ['/admin/dashboard'],
            'equipment'     => ['/admin/equipment'],
            'users'         => ['/admin/users'],
            'transaction'   => ['/admin/transaction'],
            'request'       => ['/admin/request'],
            'logs'          => ['/admin/logs'],
            'notifications' => ['/admin/notifications'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminPages')]
    public function test_admin_pages_keep_their_accessibility_chrome(string $uri): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        $html = $this->actingAs($admin)->get($uri)->assertOk()->getContent();

        // The sidebar is six links deep; without a skip link every page starts
        // with six tab stops before the content.
        $this->assertStringContainsString('Skip to content', $html, "$uri has no skip link");
        $this->assertStringContainsString('id="main-content"', $html, "$uri has no skip-link target");

        // 100vh shifts as the mobile URL bar collapses; dvh tracks it.
        $this->assertStringContainsString('min-h-[100dvh]', $html, "$uri is not on dvh");
        $this->assertStringNotContainsString('min-h-screen', $html, "$uri regressed to 100vh");
    }

    public function test_layout_carries_search_and_link_preview_metadata(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('og:title', $html);
        $this->assertStringContainsString('og:image', $html);
    }

    public function test_public_pages_have_a_styled_cta_and_no_dead_links(): void
    {
        foreach (['/', '/login', '/register', '/forgot-password'] as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();

            // .btn-primary went undefined for a long time, leaving every public
            // call to action rendering as bare text. The landing page follows
            // design-reference/Landing.dc.html instead — a white button on the
            // navy hero — so it is checked for a styled sign-in link.
            if ($uri === '/') {
                $this->assertMatchesRegularExpression(
                    '/<a href="'.preg_quote(route('login'), '/').'"\s+class="[^"]*bg-white[^"]*"/',
                    $html,
                    '/ lost its styled sign-in button'
                );
            } else {
                $this->assertStringContainsString('btn-primary', $html, "$uri lost its CTA class");
            }

            // Asserted as links rather than as the `lp-legal` class: the login
            // page was rebuilt on the app's Tailwind system and no longer
            // carries auth.css class names, but it still has to carry the two
            // links, which is what this was ever really checking.
            $this->assertStringContainsString(route('legal.privacy'), $html, "$uri has no privacy link");
            $this->assertStringContainsString(route('legal.terms'), $html, "$uri has no terms link");

            $this->assertStringNotContainsString('href="#"', $html, "$uri has a dead link");
        }
    }

    public function test_legal_pages_render(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy policy');
        $this->get('/terms')->assertOk()->assertSee('Terms of service');
    }

    public function test_missing_page_gets_the_branded_404(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to home');
    }

    public function test_empty_admin_tables_render_the_shared_empty_state(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        // Nothing seeded, so each of these lands on its empty state.
        foreach (['/admin/equipment', '/admin/request', '/admin/logs'] as $uri) {
            $html = $this->actingAs($admin)->get($uri)->assertOk()->getContent();

            // The markers of x-ui.empty-state: framed icon tile + the unified
            // text-lg / text-base pairing. Three pages used to sit on an older,
            // smaller scale with a bare glyph.
            $this->assertStringContainsString('rounded-2xl', $html, "$uri is not using the shared empty state");
            $this->assertStringContainsString('text-lg font-semibold', $html, "$uri empty state is off-scale");
        }
    }

    /**
     * The DataTables theme block used to live here as plain CSS so Tailwind
     * would not purge it. DataTables itself is gone — with jQuery, which was
     * loaded on every page for it alone — so what has to be pinned now is the
     * absence, plus the one rule the replacement list filter depends on.
     */
    public function test_the_datatables_stack_stays_out_of_the_build(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;

        $this->assertNotNull($cssFile, 'No compiled CSS in the Vite manifest');

        $css = file_get_contents(public_path('build/'.$cssFile));

        $this->assertStringNotContainsString('.dataTables_wrapper', $css, 'DataTables styling is back in the build');

        // resources/js/ui.js filters by setting row.hidden, and a filtered row
        // usually carries a display utility that beats preflight's [hidden]
        // rule. This override has to survive, and has to stay outside @layer.
        $this->assertStringContainsString('[hidden]', $css, 'The hidden-row override was purged from the build');
    }

    /** The borrower dashboard gets the same chrome as the admin pages. */
    public function test_borrower_dashboard_keeps_its_accessibility_chrome(): void
    {
        $borrower = User::factory()->create(['user_type' => 'Student']);

        $html = $this->actingAs($borrower)->get('/borrower/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Skip to content', $html);
        $this->assertStringContainsString('id="main-content"', $html);
        $this->assertStringContainsString('min-h-[100dvh]', $html);
        $this->assertStringNotContainsString('min-h-screen', $html);
    }
}
