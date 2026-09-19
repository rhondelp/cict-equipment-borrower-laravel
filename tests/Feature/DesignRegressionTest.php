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
            // call to action rendering as bare text.
            $this->assertStringContainsString('btn-primary', $html, "$uri lost its CTA class");
            $this->assertStringContainsString('lp-legal', $html, "$uri has no legal links");
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

    public function test_datatables_theme_survives_the_tailwind_build(): void
    {
        // DataTables writes these class names from JavaScript, so they appear in
        // no .blade.php file. Inside @layer components Tailwind tree-shook the
        // whole block away at build time; it has to stay as plain CSS.
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFile  = $manifest['resources/css/app.css']['file'] ?? null;

        $this->assertNotNull($cssFile, 'No compiled CSS in the Vite manifest');

        $css = file_get_contents(public_path('build/' . $cssFile));

        // Matched loosely: the minifier groups selectors, so assert on the
        // distinctive fragment rather than a whole selector list.
        foreach ([
            '.dataTables_wrapper .dataTables_filter',
            '.dataTables_wrapper .dataTables_length',
            '.dataTables_wrapper .dataTables_info',
            '.dataTables_wrapper .dataTables_empty',
            '.dataTables_wrapper .dataTables_paginate .paginate_button{',
        ] as $selector) {
            $this->assertStringContainsString(
                $selector,
                $css,
                "DataTables rule was purged from the build: $selector"
            );
        }
    }
}
