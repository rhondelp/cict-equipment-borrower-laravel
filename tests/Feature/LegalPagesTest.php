<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two legal documents.
 *
 * The substance of these pages was already right; what was wrong was that they
 * were typeset as UI rather than as documents — 13px sans in a floating card,
 * headings barely larger than body copy, no way to navigate. So most of what is
 * pinned here is typographic, plus the one structural guarantee that makes the
 * contents rail trustworthy: it is generated from the same headings the article
 * renders, so the two cannot drift apart.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function documents(): array
    {
        return [
            'terms' => ['/terms', 'Terms of service'],
            'privacy' => ['/privacy', 'Privacy policy'],
        ];
    }

    private function html(string $uri): string
    {
        return $this->get($uri)->assertOk()->getContent();
    }

    /* ---------------------------------------------------------------------
     | Typography
     --------------------------------------------------------------------- */

    /** Body copy is 17px serif at a 66-character measure. It is read, not scanned. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_body_copy_is_serif_at_reading_size(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('font-serif text-[17px]', $html, "$uri body copy is not 17px serif");
        $this->assertStringContainsString('max-w-[66ch]', $html, "$uri has no reading measure");
        // And the face is actually loaded, rather than named and never fetched.
        $this->assertStringContainsString('Source+Serif+4', $html, "$uri names a serif it never loads");
    }

    /** The reading face is not dragged onto every other page in the app. */
    public function test_the_serif_is_loaded_only_on_the_legal_pages(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/'] as $uri) {
            $this->assertStringNotContainsString(
                'Source+Serif+4',
                $this->html($uri),
                "$uri is loading the legal-document serif it never uses"
            );
        }
    }

    /** Real hierarchy: a 34px title over 20px headings over 17px body. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_headings_outrank_body_copy(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('sm:text-[34px]', $html, "$uri has no page title size");
        $this->assertStringContainsString('text-[20px] font-semibold', $html, "$uri section headings are not 20px");
    }

    /** It is a document, not a card sitting on a page. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_the_floating_card_is_gone(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringNotContainsString('shadow-flat', $html, "$uri still floats the prose in a card");
        $this->assertStringNotContainsString('rounded-xl border border-neutral-200 shadow', $html);
    }

    /* ---------------------------------------------------------------------
     | The contents rail
     --------------------------------------------------------------------- */

    /**
     * The guarantee that makes the rail worth having: every section is linked,
     * and every link lands on a section. Both lists come from one array in the
     * page's own file, so this fails only if the layout stops deriving one from
     * the other.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_every_section_is_in_the_contents_and_vice_versa(string $uri): void
    {
        $html = $this->html($uri);

        preg_match_all('/<section id="([^"]+)"/', $html, $sections);
        preg_match_all('/href="#([^"]+)"/', $html, $links);

        $ids = $sections[1];
        $targets = array_values(array_diff(array_unique($links[1]), ['legal-content']));

        $this->assertNotEmpty($ids, "$uri renders no sections");
        $this->assertEqualsCanonicalizing($ids, $targets, "$uri contents rail does not match its sections");
    }

    /** Anchors that land under a sticky header are anchors that land wrong. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_sections_carry_scroll_margin(string $uri): void
    {
        preg_match_all('/<section id="[^"]+" class="([^"]*)"/', $this->html($uri), $matches);

        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $classes) {
            $this->assertStringContainsString('scroll-mt-', $classes, "$uri has a section with no scroll margin");
        }
    }

    /** The rail exists on both a desktop column and a phone disclosure. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_the_contents_are_reachable_at_both_widths(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('aria-label="On this page"', $html, "$uri has no contents rail");
        $this->assertStringContainsString('sticky top-[84px]', $html, "$uri rail does not stick");
        $this->assertStringContainsString('<details', $html, "$uri drops the contents entirely on a phone");
    }

    /* ---------------------------------------------------------------------
     | The short version
     --------------------------------------------------------------------- */

    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_each_document_opens_with_three_summary_points(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('The short version', $html);

        // Three, and exactly three — the box stops working as a summary the
        // moment it becomes a second copy of the document.
        preg_match('/The short version.*?<\/ul>/s', $html, $box);
        $this->assertSame(3, substr_count($box[0], '<li'), "$uri summary is not three points");
    }

    /** The three things that cost a borrower something if they do not know them. */
    public function test_the_terms_summary_covers_what_actually_affects_people(): void
    {
        $this->get('/terms')
            ->assertSee('A request is not a reservation')
            ->assertSee('The default loan is seven days')
            ->assertSee('Overdue items pause your ability to borrow more');
    }

    public function test_the_privacy_summary_covers_what_actually_affects_people(): void
    {
        $this->get('/privacy')
            ->assertSee('The system stores your name, school email, contact number and role')
            ->assertSee('Administrators can see all records; other borrowers cannot see yours.')
            ->assertSee('No analytics, no advertising trackers');
    }

    /**
     * The summary must not promise something the document does not say. This
     * caught a real one: the design reference's bullet ended "— nothing else",
     * which is false here, because sessions are stored in the database with an
     * IP address and a user agent.
     */
    public function test_the_privacy_summary_does_not_overclaim(): void
    {
        $this->get('/privacy')->assertDontSee('nothing else');
    }

    /* ---------------------------------------------------------------------
     | The two fallback clauses
     --------------------------------------------------------------------- */

    /**
     * Both were the last sentence of a paragraph, which is where a reader stops
     * looking — and both are the sentence that tells someone what to actually
     * do when the normal path is closed.
     */
    public function test_the_offline_fallback_is_an_aside_not_a_trailing_clause(): void
    {
        $html = $this->html('/terms');

        $this->assertStringContainsString('data-legal-aside', $html, 'The terms have no pulled-out aside');
        $this->assertMatchesRegularExpression(
            '/data-legal-aside[^>]*>\s*Where the system is unavailable, borrowing falls back to the paper process/',
            $html,
            'The paper-process fallback is not in the aside'
        );
    }

    public function test_the_deletion_fallback_is_an_aside_not_a_trailing_clause(): void
    {
        $html = $this->html('/privacy');

        $this->assertStringContainsString('data-legal-aside', $html, 'The privacy policy has no pulled-out aside');
        $this->assertMatchesRegularExpression(
            '/data-legal-aside[^>]*>\s*Borrowing records tied to equipment that is still out cannot be removed/',
            $html,
            'The deletion limit is not in the aside'
        );
    }

    /** One each — the treatment stops meaning anything if everything gets it. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_each_document_has_exactly_one_aside(string $uri): void
    {
        $this->assertSame(1, substr_count($this->html($uri), 'data-legal-aside'), "$uri has more than one aside");
    }

    /* ---------------------------------------------------------------------
     | One layout, two documents
     --------------------------------------------------------------------- */

    /** Both documents keep their own URL: they are linked to from consent boxes. */
    public function test_both_documents_keep_their_own_url(): void
    {
        $this->get('/terms')->assertOk()->assertSee('Terms of service');
        $this->get('/privacy')->assertOk()->assertSee('Privacy policy');
    }

    /** The tab switch is two real links, not a control that only exists in JS. */
    #[\PHPUnit\Framework\Attributes\DataProvider('documents')]
    public function test_the_tab_switch_links_to_both_documents(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('href="'.route('legal.terms').'"', $html);
        $this->assertStringContainsString('href="'.route('legal.privacy').'"', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'), "$uri does not mark exactly one active tab");
    }

    /** And the current document is the one marked active. */
    public function test_the_active_tab_is_the_document_being_read(): void
    {
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('legal.terms'), '/').'"\s+aria-current="page"/',
            $this->html('/terms')
        );
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('legal.privacy'), '/').'"\s+aria-current="page"/',
            $this->html('/privacy')
        );
    }

    /* ---------------------------------------------------------------------
     | The writing is unchanged
     --------------------------------------------------------------------- */

    /** This was a typographic pass. The clauses have to survive it intact. */
    public function test_the_terms_keep_their_substance(): void
    {
        $this->get('/terms')
            ->assertSee('A request reserves nothing until an administrator approves it. Stock is deducted at approval, not at request.')
            ->assertSee('Items are due on the return date shown on your transaction. The default loan period is seven days.')
            ->assertSee('Return the item to a custodian, who records its condition. A transaction is not closed until that return is logged.')
            ->assertSee('Report damage when you return the item rather than leaving it to be found.')
            ->assertSee('The department may decline further requests from an account with items still overdue')
            ->assertSee('Who may use this system')
            ->assertSee('Damage and loss')
            ->assertSee('Suspension of borrowing');
    }

    public function test_the_privacy_policy_keeps_its_substance(): void
    {
        $this->get('/privacy')
            ->assertSee('Equipment requests you submit, including quantity, purpose and remarks.')
            ->assertSee('Return logs recording the condition of an item when it came back, and which staff member received it.')
            ->assertSee('The system does not use analytics or advertising trackers, and does not share records with anyone outside the college.')
            ->assertSee('Department administrators can see all records')
            ->assertSee('Borrowing records are kept for the academic year in which they were created')
            ->assertSee('The system sets one session cookie')
            ->assertSee('Who can see your records')
            ->assertSee('How long records are kept');
    }

    /**
     * Added during this pass, because it was true and undisclosed:
     * SESSION_DRIVER is `database` and the sessions table carries `ip_address`
     * and `user_agent`, while the "what we store" list claimed to be complete.
     */
    public function test_the_privacy_policy_discloses_the_session_record(): void
    {
        // Asserted against what ships, not against config('session.driver'):
        // phpunit.xml pins the driver to `array` for the suite, so the runtime
        // value here says nothing about how the deployed app stores sessions.
        $this->assertStringContainsString(
            'SESSION_DRIVER=database',
            file_get_contents(base_path('.env.example')),
            'Sessions are no longer stored server-side by default — recheck this disclosure'
        );

        // And the columns that make the disclosure true in the first place.
        $migration = file_get_contents(database_path('migrations/0001_01_01_000000_create_users_table.php'));
        $this->assertStringContainsString("\$table->string('ip_address'", $migration);
        $this->assertStringContainsString("\$table->text('user_agent')", $migration);

        $this->get('/privacy')->assertSee('Sign-in sessions, which record the IP address and browser your account signed in from');
    }
}
