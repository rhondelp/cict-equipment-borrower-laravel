<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The account-request page.
 *
 * Two decisions here are load-bearing and cheap to undo: the role is derived
 * from the school domain server-side rather than chosen on the form, and the
 * password requirements are stated before submit rather than reported back
 * after a rejection. The rest pins the page against sliding back to a generic
 * sign-up — a user-type select, a confirm-password box, a submit that goes
 * through without consent.
 */
class RegisterPageTest extends TestCase
{
    use RefreshDatabase;

    private function html(): string
    {
        return $this->get('/register')->assertOk()->getContent();
    }

    /** A complete, valid payload. Individual tests override one key. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Angeles Bautista',
            'email' => 'maria.bautista@student.nmsc.edu.ph',
            'contact_number' => '09171234567',
            'password' => 'lab2026pass',
            'agree' => '1',
        ], $overrides);
    }

    /* ---------------------------------------------------------------------
     | The form itself
     --------------------------------------------------------------------- */

    public function test_the_page_has_a_registration_form_on_it(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('action="'.route('register.store').'"', $html, 'No form posting to register.store');
        $this->assertStringContainsString('name="_token"', $html, 'The form is not CSRF protected');
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="contact_number"', $html);
        $this->assertStringContainsString('name="agree"', $html);
    }

    /** Role is a fact read off the address, so there is no control that sets one. */
    public function test_the_page_offers_no_way_to_pick_a_role(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('name="user_type"', $html, 'The user type field is back');
        $this->assertStringNotContainsString('<select', $html, 'A select is back on the sign-up form');
        $this->assertStringNotContainsString('>Instructor</option>', $html);
        $this->assertStringNotContainsString('>Student</option>', $html);
    }

    /** Confirm-password is gone; the reveal control replaces it. */
    public function test_confirm_password_is_gone_and_the_reveal_is_a_word(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('name="password_confirmation"', $html, 'Confirm password is back');
        $this->assertStringContainsString('id="password-toggle"', $html);
        $this->assertStringContainsString('>Show</button>', $html, 'The reveal control is not labelled');
        // The shared `.eye-btn` handler in the layout swaps a glyph; this page
        // does not use it, matching the sign-in page.
        $this->assertStringNotContainsString('class="eye-btn"', $html);
    }

    /** The three rules are on the page before anyone submits anything. */
    public function test_the_password_requirements_are_stated_before_submit(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('At least 8 characters', $html);
        $this->assertStringContainsString('Contains letters', $html);
        $this->assertStringContainsString('Contains numbers', $html);
        // And the live indicator they tick off against.
        $this->assertStringContainsString('data-strength-bar', $html, 'No password strength indicator');
    }

    /** The placeholder is a school address, not `name@company.com`. */
    public function test_the_email_placeholder_is_a_school_address(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('name@company.com', $html, 'The generic placeholder is back');
        $this->assertStringContainsString('name@'.User::STUDENT_DOMAIN, $html);
        // And the domain rule is explained before the field is rejected.
        $this->assertStringContainsString(User::STAFF_DOMAIN, $html);
    }

    /** What happens after the form, on the page where it is being filled in. */
    public function test_the_approval_process_is_explained_before_submitting(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('Fill this in', $html);
        $this->assertStringContainsString('Office verifies you', $html);
        $this->assertStringContainsString('Start requesting', $html);
    }

    /** One consent, carrying the commitment and both legal links. */
    public function test_there_is_exactly_one_consent_checkbox(): void
    {
        $html = $this->html();

        $this->assertSame(
            1,
            substr_count($html, 'type="checkbox"'),
            'The sign-up form has more than one checkbox on it'
        );
        $this->assertStringContainsString('return borrowed equipment on time', $html);
        $this->assertStringContainsString(route('legal.terms'), $html);
        $this->assertStringContainsString(route('legal.privacy'), $html);
    }

    /** The contact field says what the number is for, next to the field. */
    public function test_the_contact_number_says_why_it_is_collected(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('id="contact-why"', $html);
        $this->assertStringContainsString('equipment office', $html);
        // It is nullable in the schema, and the label says so.
        $this->assertStringContainsString('optional', $html);
    }

    /* ---------------------------------------------------------------------
     | Server-side role derivation
     --------------------------------------------------------------------- */

    public function test_a_student_address_creates_a_student(): void
    {
        $this->post('/register', $this->payload(['email' => 'juan@student.nmsc.edu.ph']))
            ->assertRedirect(route('register'));

        $this->assertDatabaseHas('users', [
            'email' => 'juan@student.nmsc.edu.ph',
            'user_type' => 'Student',
        ]);
    }

    public function test_an_instructor_address_creates_an_instructor(): void
    {
        $this->post('/register', $this->payload(['email' => 'rmers@nmsc.edu.ph']))
            ->assertRedirect(route('register'));

        $this->assertDatabaseHas('users', [
            'email' => 'rmers@nmsc.edu.ph',
            'user_type' => 'Instructor',
        ]);
    }

    /** Case and stray whitespace do not change which domain an address is on. */
    public function test_the_domain_is_read_case_insensitively(): void
    {
        $this->post('/register', $this->payload(['email' => '  Juan@STUDENT.NMSC.edu.PH  ']));

        $this->assertDatabaseHas('users', [
            'email' => 'juan@student.nmsc.edu.ph',
            'user_type' => 'Student',
        ]);
    }

    public function test_a_personal_address_is_refused_with_a_message_naming_both_domains(): void
    {
        $this->from('/register')
            ->post('/register', $this->payload(['email' => 'maria@gmail.com']))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertStringContainsString(User::STUDENT_DOMAIN, session('errors')->first('email'));
        $this->assertStringContainsString(User::STAFF_DOMAIN, session('errors')->first('email'));
    }

    /** The role helper itself, at the boundaries the controller relies on. */
    public function test_role_derivation_matches_the_whole_domain(): void
    {
        $this->assertSame('Student', User::roleForEmail('a@student.nmsc.edu.ph'));
        $this->assertSame('Instructor', User::roleForEmail('a@nmsc.edu.ph'));

        foreach ([
            'a@not-nmsc.edu.ph',
            'a@nmsc.edu.ph.evil.com',
            'a@notstudent.nmsc.edu.ph',
            'a@sub.student.nmsc.edu.ph',
            'a@example.com',
            'not-an-address',
            '@nmsc.edu.ph',
            'a@b@nmsc.edu.ph',
            null,
            '',
        ] as $address) {
            $this->assertNull(User::roleForEmail($address), "[$address] was accepted as a school address");
        }
    }

    /* ---------------------------------------------------------------------
     | The rest of the validation
     --------------------------------------------------------------------- */

    public function test_a_short_password_is_refused(): void
    {
        $this->post('/register', $this->payload(['password' => 'ab12']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_password_without_numbers_is_refused(): void
    {
        $this->post('/register', $this->payload(['password' => 'onlylettershere']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_password_without_letters_is_refused(): void
    {
        $this->post('/register', $this->payload(['password' => '1234567890']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * The consent is enforced where it counts. Unticking a box in devtools is a
     * two-second job, so the checkbox is a validated field, not a UI gate.
     */
    public function test_registration_without_consent_is_refused(): void
    {
        $payload = $this->payload();
        unset($payload['agree']);

        $this->post('/register', $payload)->assertSessionHasErrors('agree');
        $this->assertDatabaseCount('users', 0);

        $this->post('/register', $this->payload(['agree' => '0']))->assertSessionHasErrors('agree');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_a_name_is_required(): void
    {
        $this->post('/register', $this->payload(['name' => '']))->assertSessionHasErrors('name');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_an_address_already_registered_is_refused(): void
    {
        User::factory()->create(['email' => 'taken@student.nmsc.edu.ph', 'user_type' => 'Student']);

        $this->post('/register', $this->payload(['email' => 'taken@student.nmsc.edu.ph']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    /** Contact number stays optional, as the column and the label both say. */
    public function test_the_contact_number_can_be_left_blank(): void
    {
        $payload = $this->payload();
        unset($payload['contact_number']);

        $this->post('/register', $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'maria.bautista@student.nmsc.edu.ph',
            'contact_number' => null,
        ]);
    }

    public function test_the_password_is_stored_hashed(): void
    {
        $this->post('/register', $this->payload());

        $user = User::where('email', 'maria.bautista@student.nmsc.edu.ph')->firstOrFail();

        $this->assertNotSame('lab2026pass', $user->password);
        $this->assertTrue(Hash::check('lab2026pass', $user->password));
    }

    /* ---------------------------------------------------------------------
     | The success state
     --------------------------------------------------------------------- */

    public function test_a_successful_registration_lands_on_a_success_state(): void
    {
        $this->followingRedirects()
            ->post('/register', $this->payload())
            ->assertOk()
            ->assertSee('Account created')
            ->assertSee('maria.bautista@student.nmsc.edu.ph')
            ->assertSee('Registered as')
            ->assertSee('Student')
            ->assertSee('What happens next')
            ->assertSee('Back to sign in');
    }

    /** The success state is a page, not a modal thrown over the form. */
    public function test_the_success_state_replaces_the_form_rather_than_covering_it(): void
    {
        $html = $this->followingRedirects()->post('/register', $this->payload())->getContent();

        $this->assertStringContainsString('data-register-success', $html);
        $this->assertStringNotContainsString('id="register-form"', $html, 'The form is still on the success page');
        $this->assertStringContainsString(route('login'), $html, 'No way back to sign in');
    }

    /**
     * It claims no approval window, because the application defines none: a new
     * account is active on creation and nothing queues it for review. Inventing
     * "within 24 hours" here would be a promise no code keeps.
     */
    public function test_the_success_state_does_not_invent_an_approval_timeframe(): void
    {
        $html = $this->followingRedirects()->post('/register', $this->payload())->getContent();

        foreach (['within 24 hours', 'within one working day', '1-2 business days', 'within 48 hours'] as $promise) {
            $this->assertStringNotContainsString($promise, $html, "The success state promises: $promise");
        }

        // What it says instead is what actually happens.
        $this->assertStringContainsString('sign in straight away', $html);
    }

    /** Landing on /register directly shows the form, never a stale success. */
    public function test_the_success_state_does_not_survive_a_reload(): void
    {
        $this->followingRedirects()->post('/register', $this->payload())->assertSee('Account created');

        $this->get('/register')
            ->assertOk()
            ->assertSee('Request an account')
            ->assertDontSee('Account created');
    }

    /* ---------------------------------------------------------------------
     | Errors come back to the form
     --------------------------------------------------------------------- */

    public function test_a_rejected_submit_returns_the_form_with_the_values_kept(): void
    {
        // from() so the validation redirect goes back to the form rather than
        // to the referer-less default of '/'.
        $html = $this->from('/register')->followingRedirects()
            ->post('/register', $this->payload(['email' => 'maria@gmail.com']))
            ->getContent();

        $this->assertStringContainsString('id="register-form"', $html);
        $this->assertStringContainsString('value="Maria Angeles Bautista"', $html, 'The name was not kept');
        $this->assertStringContainsString('value="maria@gmail.com"', $html, 'The address was not kept');
        // The password is never echoed back into the markup.
        $this->assertStringNotContainsString('lab2026pass', $html);
    }

    /** A new account can actually sign in, which is what the success state says. */
    public function test_a_registered_borrower_can_sign_in(): void
    {
        $this->post('/register', $this->payload());

        $this->post('/login', [
            'email' => 'maria.bautista@student.nmsc.edu.ph',
            'password' => 'lab2026pass',
        ])->assertRedirect(route('borrower.dashboard'));

        $this->assertAuthenticated();
    }
}
