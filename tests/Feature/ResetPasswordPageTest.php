<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * The page the emailed reset link opens.
 *
 * Pins: the email is the link's and cannot be edited, there is one password
 * field held to min 8 / letters / numbers / not the email name, the countdown
 * is read from the broker's token row and config, a dead token gets an expired
 * state rather than a field error, and a successful reset signs nobody in but
 * does sign every other session out.
 */
class ResetPasswordPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function borrower(): User
    {
        return User::factory()->create([
            'user_type' => 'Student',
            'name' => 'Maria Santos',
            'email' => 'maria.santos@nmsc.edu.ph',
            'password' => 'oldpass123',
        ]);
    }

    /** A real token, written by the broker itself. */
    private function tokenFor(User $user): string
    {
        return Password::broker()->createToken($user);
    }

    private function page(User $user, string $token)
    {
        return $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]));
    }

    private function submit(User $user, string $token, string $password)
    {
        return $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => $password,
            ]);
    }

    /* ---------------------------------------------------------------------
     | A valid link
     --------------------------------------------------------------------- */

    public function test_a_valid_link_shows_the_form_in_the_auth_frame(): void
    {
        $user = $this->borrower();
        $html = $this->page($user, $this->tokenFor($user))->assertOk()->getContent();

        $this->assertStringContainsString('Choose a new password', $html);
        $this->assertStringContainsString('What happens when you save', $html);
        $this->assertStringContainsString('Your old password stops working', $html);
        $this->assertStringContainsString('Other sessions are signed out', $html);
        $this->assertStringContainsString('Your loans and requests are untouched', $html);
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)]', $html);
        $this->assertStringContainsString('text-[24px] font-semibold', $html);
        $this->assertStringContainsString('Save new password', $html);

        // The old page's furniture is gone.
        $this->assertStringNotContainsString('auth.css', $html);
        $this->assertStringNotContainsString('Account Recovery', $html);
        $this->assertStringNotContainsString('lp-', $html);
    }

    public function test_the_email_is_a_read_only_chip_carried_in_a_hidden_input(): void
    {
        $user = $this->borrower();
        $token = $this->tokenFor($user);
        $html = $this->page($user, $token)->assertOk()->getContent();

        $this->assertStringContainsString('Resetting the password for', $html);
        $this->assertStringContainsString('>MS</span>', $html);
        $this->assertStringContainsString('<input type="hidden" name="email" value="maria.santos@nmsc.edu.ph">', $html);
        $this->assertStringContainsString('<input type="hidden" name="token" value="'.$token.'">', $html);
        $this->assertDoesNotMatchRegularExpression('/<input type="email"/', $html);
    }

    public function test_there_is_one_password_field_and_no_confirmation(): void
    {
        $user = $this->borrower();
        $html = $this->page($user, $this->tokenFor($user))->getContent();

        $this->assertSame(1, substr_count($html, 'name="password"'));
        $this->assertStringNotContainsString('password_confirmation', $html);
        $this->assertStringContainsString('id="password-toggle"', $html);
        $this->assertMatchesRegularExpression('/id="password-toggle"[^>]*>Show<\/button>/', $html);
    }

    public function test_the_three_requirements_and_strength_bar_are_on_the_page(): void
    {
        $user = $this->borrower();
        $html = $this->page($user, $this->tokenFor($user))->getContent();

        $this->assertStringContainsString('At least 8 characters', $html);
        $this->assertStringContainsString('Letters and numbers', $html);
        $this->assertStringContainsString('Not the same as your email name', $html);
        $this->assertSame(3, substr_count($html, 'data-strength-segment></span>'));
        // The client checks the same email name the server does.
        $this->assertStringContainsString('const emailName = "maria.santos"', $html);
    }

    /** Counted from the token row plus config, never a literal 30. */
    public function test_the_countdown_is_read_from_the_token_and_config(): void
    {
        config(['auth.passwords.users.expire' => 45]);
        Carbon::setTestNow('2026-09-28 10:00:00');

        $user = $this->borrower();
        $token = $this->tokenFor($user);

        Carbon::setTestNow('2026-09-28 10:05:30');
        $html = $this->page($user, $token)->getContent();

        $this->assertStringContainsString('data-seconds-left="2370"', $html);
        $this->assertStringContainsString('>39:30</span>', $html);
        $this->assertStringContainsString('font-mono', $html);
    }

    /* ---------------------------------------------------------------------
     | Expired or invalid
     --------------------------------------------------------------------- */

    public function test_an_expired_token_shows_the_expired_state(): void
    {
        Carbon::setTestNow('2026-09-28 10:00:00');
        $user = $this->borrower();
        $token = $this->tokenFor($user);

        Carbon::setTestNow('2026-09-28 11:00:01');
        $html = $this->page($user, $token)->assertOk()->getContent();

        $this->assertStringContainsString('This link has expired', $html);
        $this->assertStringContainsString('Reset links work once and last 60 minutes. Your password has not been changed.', $html);
        $this->assertStringContainsString('href="'.route('password.request').'"', $html);
        $this->assertStringContainsString('Send a new link', $html);
        $this->assertStringNotContainsString('id="reset-form"', $html);
    }

    public function test_a_wrong_or_used_token_shows_the_expired_state(): void
    {
        $user = $this->borrower();
        $this->tokenFor($user);

        $html = $this->page($user, 'not-the-token')->getContent();
        $this->assertStringContainsString('This link has expired', $html);

        // No email in the link at all.
        $this->get(route('password.reset', ['token' => 'x']))->assertSee('This link has expired');
    }

    public function test_the_broker_refusing_the_token_on_submit_lands_on_the_expired_state(): void
    {
        $user = $this->borrower();
        $token = $this->tokenFor($user);
        DB::table('password_reset_tokens')->delete();

        $this->submit($user, $token, 'newpass123')
            ->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertSessionHas('reset_link_expired')
            ->assertSessionDoesntHaveErrors();

        $this->assertTrue(Hash::check('oldpass123', $user->fresh()->password));
        $this->followingRedirects()->submit($user, $token, 'newpass123')->assertSee('This link has expired');
    }

    /* ---------------------------------------------------------------------
     | Validation — the same rules the page ticks off
     --------------------------------------------------------------------- */

    public function test_the_password_rules_are_enforced_on_the_server(): void
    {
        $user = $this->borrower();
        $token = $this->tokenFor($user);

        foreach (['short1a', 'onlyletters', '1234567890', 'Maria.Santos99', 'xxMARIA.SANTOSxx1'] as $bad) {
            $this->submit($user, $token, $bad)->assertSessionHasErrors('password');
        }

        $this->assertTrue(Hash::check('oldpass123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_a_validation_error_is_shown_inline_under_the_field(): void
    {
        $user = $this->borrower();
        $token = $this->tokenFor($user);

        $this->followingRedirects()
            ->submit($user, $token, 'onlyletters')
            ->assertSee('Choose a new password')
            ->assertSee('data-password-error', false)
            ->assertSee('at least one number');
    }

    /* ---------------------------------------------------------------------
     | Success
     --------------------------------------------------------------------- */

    public function test_a_successful_reset_redirects_to_sign_in_without_signing_in(): void
    {
        $user = $this->borrower();

        $this->submit($user, $this->tokenFor($user), 'newpass123')
            ->assertRedirect(route('login'))
            ->assertSessionHas('password_reset', true);

        $this->assertGuest();
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_the_sign_in_page_shows_the_success_panel(): void
    {
        config(['office.email' => 'office@nmsc.edu.ph']);
        $user = $this->borrower();

        $this->followingRedirects()
            ->submit($user, $this->tokenFor($user), 'newpass123')
            ->assertSee('Password updated')
            ->assertSee('Sign in with your new password. Anywhere else you were signed in has been logged out.')
            ->assertSee("Didn't make this change? Tell the equipment office", false)
            ->assertSee('mailto:office@nmsc.edu.ph', false)
            ->assertSee('value="maria.santos@nmsc.edu.ph"', false);

        // And not on an ordinary visit.
        $this->get(route('login'))->assertDontSee('data-password-reset-done', false);
    }

    public function test_a_successful_reset_signs_other_sessions_out(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->borrower();
        $other = User::factory()->create(['user_type' => 'Student', 'email' => 'other@nmsc.edu.ph']);
        $oldRemember = $user->remember_token;

        foreach ([[$user->id, 'lab-pc'], [$user->id, 'phone'], [$other->id, 'someone-else']] as [$id, $sid]) {
            DB::table('sessions')->insert([
                'id' => $sid, 'user_id' => $id, 'ip_address' => '127.0.0.1',
                'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->submit($user, $this->tokenFor($user), 'newpass123')->assertRedirect(route('login'));

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'someone-else']);
        $this->assertNotSame($oldRemember, $user->fresh()->remember_token);
    }

    public function test_the_new_password_signs_in_and_the_old_one_does_not(): void
    {
        $user = $this->borrower();
        $this->submit($user, $this->tokenFor($user), 'newpass123');

        $this->post('/login', ['email' => $user->email, 'password' => 'oldpass123']);
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'newpass123'])
            ->assertRedirect(route('borrower.dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
