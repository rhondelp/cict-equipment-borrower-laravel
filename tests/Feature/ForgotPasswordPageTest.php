<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The forgot-password screen.
 *
 * The flow used to stop at the click — press Send, watch nothing happen, press
 * again — so most of what is pinned here is the confirmation state and the
 * things that make it true: the expiry and the resend cooldown both read from
 * the configured broker rather than written into the copy, and a deactivated
 * account refused outright instead of being sent a link that resets a password
 * it still cannot sign in with.
 */
class ForgotPasswordPageTest extends TestCase
{
    use RefreshDatabase;

    private function borrower(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'Student',
            'email' => 'maria@student.nmsc.edu.ph',
        ], $overrides));
    }

    private function html(): string
    {
        return $this->get('/forgot-password')->assertOk()->getContent();
    }

    /* ---------------------------------------------------------------------
     | The form
     --------------------------------------------------------------------- */

    public function test_the_page_has_the_request_form_on_it(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('action="'.route('password.email').'"', $html);
        $this->assertStringContainsString('name="_token"', $html, 'The form is not CSRF protected');
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('id="forgot-submit"', $html);
    }

    /** Stated before sending, not discovered when the link is already dead. */
    public function test_the_expiry_is_stated_before_the_link_is_sent(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('The link expires', $html);
        $this->assertStringContainsString('works once', $html);
        // Read from config, not written into the template.
        $this->assertStringContainsString('1 hour', $html, 'The copy is not quoting the configured 60-minute expiry');
        $this->assertStringNotContainsString('30 minutes', $html, 'The reference mock\'s 30 minutes was hardcoded');
    }

    /** Change the broker config and the copy follows it. */
    public function test_the_expiry_copy_tracks_the_broker_config(): void
    {
        config(['auth.passwords.users.expire' => 25]);

        $this->assertStringContainsString('The link expires 25 minutes after it is sent', $this->html());
    }

    public function test_the_page_carries_the_office_hours_and_address(): void
    {
        $html = $this->html();

        $this->assertStringContainsString(config('office.hours'), $html);
        $this->assertStringContainsString(config('office.email'), $html);
        $this->assertStringContainsString('mailto:'.config('office.email'), $html);
    }

    /** The emergency the left panel is actually for. */
    public function test_the_left_panel_says_returns_do_not_need_a_sign_in(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('Locked out with equipment still due?', $html);
        $this->assertStringContainsString('return items at the counter without signing in', $html);
    }

    /** The failure a reset form cannot solve. */
    public function test_there_is_a_way_out_for_a_forgotten_address(): void
    {
        $this->assertStringContainsString(
            'Forgot which address you registered with?',
            $this->html(),
            'Someone who cannot remember their address has nowhere to go'
        );
    }

    /** Warned, not blocked: the admin form can create an account on any address. */
    public function test_an_off_domain_address_is_warned_about_but_still_accepted(): void
    {
        $this->borrower(['email' => 'legacy@example.com']);
        Notification::fake();

        $html = $this->from('/forgot-password')
            ->followingRedirects()
            ->post('/forgot-password', ['email' => 'legacy@example.com'])
            ->getContent();

        // It went through — the confirmation state, not a rejection.
        $this->assertStringContainsString('data-reset-sent', $html);
        Notification::assertSentTo(User::where('email', 'legacy@example.com')->first(), ResetPassword::class);
    }

    public function test_the_off_domain_warning_renders_for_a_rejected_address(): void
    {
        $html = $this->from('/forgot-password')
            ->followingRedirects()
            ->post('/forgot-password', ['email' => 'someone@gmail.com'])
            ->getContent();

        $this->assertStringContainsString('That is not a school address', $html);
    }

    /* ---------------------------------------------------------------------
     | The confirmation state
     --------------------------------------------------------------------- */

    public function test_a_sent_link_lands_on_a_confirmation_naming_the_address(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $html = $this->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Check your inbox', $html);
        $this->assertStringContainsString($user->email, $html);
        $this->assertStringContainsString('It works once', $html);
        $this->assertStringContainsString('Not seeing it?', $html);
        // The form is gone, so there is nothing left to press repeatedly.
        $this->assertStringNotContainsString('id="forgot-form"', $html);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** The clock time comes from the token row, not from a number in the copy. */
    public function test_the_confirmation_shows_the_real_expiry_time(): void
    {
        $user = $this->borrower();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-21 14:00:00', config('app.timezone')));

        $html = $this->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->getContent();

        // 60 minutes after 2:00 PM, per config/auth.php.
        $this->assertStringContainsString('3:00 PM', $html);

        Carbon::setTestNow();
    }

    /** Both real causes, and the second one is the loop-breaker. */
    public function test_the_not_seeing_it_block_covers_spam_and_deactivated_accounts(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $html = $this->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->getContent();

        $this->assertStringContainsString('spam or junk folder', $html);
        $this->assertStringContainsString('deactivated cannot be reset', $html);
    }

    public function test_the_confirmation_offers_a_resend_and_a_way_back_to_the_form(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $html = $this->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->getContent();

        $this->assertStringContainsString('id="resend-button"', $html);
        $this->assertStringContainsString('name="email" value="'.$user->email.'"', $html, 'Resend does not carry the address');
        $this->assertStringContainsString('Use a different address', $html);
        $this->assertStringContainsString(route('password.request', ['new' => 1]), $html);
    }

    /**
     * The cooldown is the broker's throttle, not a number chosen to look
     * reassuring. A 45-second button in front of a 60-second throttle would
     * hand people a rejection, which is the failure this screen exists to fix.
     */
    public function test_the_resend_cooldown_starts_at_the_brokers_throttle(): void
    {
        $user = $this->borrower();
        Notification::fake();
        // Frozen, or a slow run ticks a second between the send and the render
        // and the assertion is really testing how busy the machine is.
        Carbon::setTestNow(Carbon::parse('2026-09-21 14:00:00', config('app.timezone')));

        $html = $this->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->getContent();

        $throttle = config('auth.passwords.users.throttle');

        $this->assertStringContainsString('data-cooldown="'.$throttle.'"', $html);
        $this->assertStringContainsString('Resend in '.$throttle.'s', $html);
        // Anchored to the button, because a bare assertion on "disabled" would
        // also be satisfied by the Tailwind `disabled:` variants in its class
        // list. Blade's @disabled emits the bare attribute.
        $this->assertMatchesRegularExpression(
            '/data-cooldown="'.$throttle.'"\s+disabled/',
            $html,
            'The resend button is not disabled during the cooldown'
        );

        Carbon::setTestNow();
    }

    /** And it counts down with real elapsed time rather than resetting. */
    public function test_the_cooldown_reflects_time_already_elapsed(): void
    {
        $user = $this->borrower();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-21 14:00:00', config('app.timezone')));

        $this->post('/forgot-password', ['email' => $user->email]);

        // Come back 40 seconds later; 20 of the 60 are left.
        Carbon::setTestNow(Carbon::parse('2026-09-21 14:00:40', config('app.timezone')));

        $this->assertStringContainsString('data-cooldown="20"', $this->html());

        Carbon::setTestNow();
    }

    /** The confirmation survives a refresh — people reload while waiting. */
    public function test_the_confirmation_survives_a_reload(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->get('/forgot-password')->assertOk()->assertSee('Check your inbox');
        $this->get('/forgot-password')->assertOk()->assertSee('Check your inbox');
    }

    /** "Use a different address" puts the form back. */
    public function test_use_a_different_address_returns_the_form(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->get('/forgot-password?new=1')
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertDontSee('Check your inbox');

        // And it stays gone.
        $this->get('/forgot-password')->assertOk()->assertDontSee('Check your inbox');
    }

    /** A used link deletes its token, so the confirmation stops describing it. */
    public function test_the_confirmation_clears_once_the_token_is_gone(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $this->post('/forgot-password', ['email' => $user->email]);
        $this->get('/forgot-password')->assertSee('Check your inbox');

        // What Password::reset does on success.
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $this->get('/forgot-password')->assertOk()->assertDontSee('Check your inbox');
    }

    /** An expired token says so rather than counting down to a dead link. */
    public function test_an_expired_link_is_reported_as_expired(): void
    {
        $user = $this->borrower();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-21 14:00:00', config('app.timezone')));

        $this->post('/forgot-password', ['email' => $user->email]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 16:00:00', config('app.timezone')));

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('has expired', false);

        Carbon::setTestNow();
    }

    /* ---------------------------------------------------------------------
     | Resending
     --------------------------------------------------------------------- */

    /** A resend inside the window is refused, and keeps the confirmation. */
    public function test_a_throttled_resend_stays_on_the_confirmation(): void
    {
        $user = $this->borrower();
        Notification::fake();

        $this->post('/forgot-password', ['email' => $user->email]);

        $html = $this->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->getContent();

        $this->assertStringContainsString('data-reset-sent', $html, 'A throttled resend dumped the user back to the form');
        $this->assertStringContainsString('data-reset-error', $html, 'Nothing explained why the resend did nothing');

        // Exactly one email, which is the point of the cooldown.
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    /** Past the throttle, a resend really does send again. */
    public function test_a_resend_after_the_cooldown_sends_a_second_email(): void
    {
        $user = $this->borrower();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-21 14:00:00', config('app.timezone')));

        $this->post('/forgot-password', ['email' => $user->email]);

        Carbon::setTestNow(Carbon::parse('2026-09-21 14:02:00', config('app.timezone')));
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentToTimes($user, ResetPassword::class, 2);

        Carbon::setTestNow();
    }

    /* ---------------------------------------------------------------------
     | Deactivated accounts
     --------------------------------------------------------------------- */

    /**
     * The loop this screen exists to break. A deactivated account could be
     * reset, and then still could not sign in, because AuthenticateUser::login
     * refuses it separately — so the reset looked like it worked and people did
     * it again. Refused at the form now, with the instruction that helps.
     */
    public function test_a_deactivated_account_is_refused_with_the_reason(): void
    {
        $user = $this->borrower(['deactivated_at' => now()]);
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('deactivated', session('errors')->first('email'));
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_the_deactivated_refusal_renders_on_the_form(): void
    {
        $user = $this->borrower(['deactivated_at' => now()]);
        Notification::fake();

        $html = $this->from('/forgot-password')
            ->followingRedirects()
            ->post('/forgot-password', ['email' => $user->email])
            ->getContent();

        $this->assertStringContainsString('data-reset-error', $html);
        $this->assertStringContainsString('id="forgot-form"', $html, 'The form is gone, so there is nowhere to correct the address');
    }

    /* ---------------------------------------------------------------------
     | Nothing else moved
     --------------------------------------------------------------------- */

    /** The broker config itself is untouched by this redesign. */
    public function test_the_broker_config_is_unchanged(): void
    {
        $this->assertSame(60, config('auth.passwords.users.expire'));
        $this->assertSame(60, config('auth.passwords.users.throttle'));
        $this->assertSame('password_reset_tokens', config('auth.passwords.users.table'));
    }

    /** An unknown address still reports what it always reported. */
    public function test_an_unknown_address_still_reports_the_brokers_own_message(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'nobody@student.nmsc.edu.ph'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    /** The reset link still works end to end. */
    public function test_the_link_still_resets_a_password(): void
    {
        $user = $this->borrower();
        $token = null;

        Notification::fake();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertRedirect(route('login'));

        $this->post('/login', ['email' => $user->email, 'password' => 'newpass123'])
            ->assertRedirect(route('borrower.dashboard'));

        $this->assertAuthenticated();
    }
}
