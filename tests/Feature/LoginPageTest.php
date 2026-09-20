<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The sign-in page.
 *
 * Its whole job is to take a username and a password, so the first test here
 * is that the form is actually on it. The rest pin the decisions that are easy
 * to undo by accident: the reveal control being a word, "Forgot password?"
 * sitting beside the label rather than appearing after a failure, and account
 * creation staying secondary to signing in.
 */
class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    private function html(): string
    {
        return $this->get('/login')->assertOk()->getContent();
    }

    public function test_the_login_page_has_a_login_form_on_it(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('action="'.route('login.store').'"', $html, 'No form posting to login.store');
        $this->assertStringContainsString('name="_token"', $html, 'The form is not CSRF protected');
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="remember"', $html);
    }

    public function test_the_password_reveal_is_a_word_not_an_eye(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('id="password-toggle"', $html);
        $this->assertStringContainsString('>Show</button>', $html, 'The reveal control is not labelled');

        // `fa-eye` also appears in the layout's own script, which still serves
        // the other public pages — so this asserts on the rendered element, and
        // on the coupling that actually matters: the shared `.eye-btn` handler
        // swaps a glyph, and this page does not use it.
        $this->assertStringNotContainsString('<i class="fa-solid fa-eye"', $html, 'The eye icon is back');
        $this->assertStringNotContainsString('class="eye-btn"', $html, 'The page is using the shared glyph toggle');
    }

    public function test_forgot_password_sits_beside_the_password_label(): void
    {
        $html = $this->html();

        $this->assertStringContainsString(route('password.request'), $html, 'No forgot-password link');

        // Between the password label and the password input — i.e. in the label
        // row, not somewhere further down the page.
        $label = strpos($html, 'for="password"');
        $link = strpos($html, route('password.request'));
        $input = strpos($html, 'id="password"');

        $this->assertNotFalse($label);
        $this->assertGreaterThan($label, $link, 'The forgot-password link is above the password label');
        $this->assertLessThan($input, $link, 'The forgot-password link is not in the label row');
    }

    public function test_account_creation_is_demoted_below_sign_in(): void
    {
        $html = $this->html();

        $submit = strpos($html, 'id="login-submit"');
        $register = strpos($html, route('register'));

        $this->assertNotFalse($register, 'No route to account creation at all');
        $this->assertGreaterThan($submit, $register, 'Account creation is competing with sign-in');

        // And it says who approves one, rather than looking like a second
        // equally valid way in.
        $this->assertStringContainsString('the equipment office', $html);
        $this->assertStringContainsString('Request an account', $html);
    }

    public function test_the_brand_panel_carries_orientation_not_decoration(): void
    {
        Equipment::create([
            'equipment_name' => 'Projector (Epson)', 'description' => 'Portable projector',
            'quantity' => 4, 'available_quantity' => 4, 'status' => 'Available',
        ]);
        Equipment::create([
            'equipment_name' => 'HDMI Cable', 'description' => '3m cable',
            'quantity' => 20, 'available_quantity' => 20, 'status' => 'Available',
        ]);

        $html = $this->html();

        // Read from the shelf, not written into the template.
        $this->assertStringContainsString('24 units tracked across', $html);
        $this->assertStringContainsString('2 item types', $html);
        $this->assertStringContainsString('8:00 AM – 5:00 PM', $html);
    }

    /** Retired items are not lendable, so they are not part of the figure. */
    public function test_the_unit_count_ignores_retired_equipment(): void
    {
        Equipment::create([
            'equipment_name' => 'Projector (Epson)', 'description' => 'Portable projector',
            'quantity' => 4, 'available_quantity' => 4, 'status' => 'Available',
        ]);
        Equipment::create([
            'equipment_name' => 'Overhead Projector', 'description' => 'Superseded',
            'quantity' => 100, 'available_quantity' => 100, 'status' => 'Unavailable',
            'retired_at' => now(),
        ]);

        $this->assertStringContainsString('4 units tracked across 1 item type', $this->html());
    }

    public function test_the_page_drops_warning_style_copy(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsString('Authorized access', $html);
        // Same fact, phrased as the answer to the question people arrive with.
        $this->assertStringContainsString('Students and instructors both sign in here.', $html);
    }

    public function test_a_failed_sign_in_reports_above_the_form(): void
    {
        User::factory()->create(['email' => 'real@nmsc.edu.ph', 'user_type' => 'Student']);

        $this->from('/login')
            ->post('/login', ['email' => 'real@nmsc.edu.ph', 'password' => 'wrong-password'])
            ->assertRedirect('/login');

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('data-login-error', $html, 'No error region rendered');
        $this->assertStringContainsString('do not match our records', $html);

        // Above the form, not after it.
        $this->assertLessThan(
            strpos($html, 'id="login-form"'),
            strpos($html, 'data-login-error'),
            'The error is being reported below the form'
        );
    }

    /**
     * The checkbox was on the form before this redesign and did nothing:
     * Auth::attempt was called without the flag. A control that reports a
     * state the session does not have is the thing this pass exists to remove.
     */
    public function test_keep_me_signed_in_actually_keeps_you_signed_in(): void
    {
        User::factory()->create(['email' => 'borrower@nmsc.edu.ph', 'user_type' => 'Student']);

        $this->post('/login', [
            'email' => 'borrower@nmsc.edu.ph',
            'password' => 'password',
            'remember' => '1',
        ])->assertCookie(Auth::guard()->getRecallerName());

        Auth::logout();
        $this->flushSession();

        $this->post('/login', [
            'email' => 'borrower@nmsc.edu.ph',
            'password' => 'password',
        ])->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    /** Server-side rules are untouched; the client half only mirrors them. */
    public function test_server_side_validation_is_unchanged(): void
    {
        $this->from('/login')->post('/login', ['email' => 'not-an-email', 'password' => ''])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);

        // No minimum length on the password, matching AuthenticateUser::login:
        // a one-character password is rejected as a wrong credential (keyed
        // `email`), never as a malformed field.
        $this->from('/login')->post('/login', ['email' => 'someone@nmsc.edu.ph', 'password' => 'x']);

        $this->assertFalse(
            session('errors')->has('password'),
            'A one-character password was rejected as a malformed field, so a rule was added'
        );
    }
}
