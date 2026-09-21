<?php

namespace Tests\Feature;

use App\Models\ItemRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `Admin` is not an assignable role on either registration route, but the
     * two routes refuse it for different reasons now.
     *
     * Public sign-up does not read a role from the request at all: the role is
     * derived from the school domain of the submitted address, so a posted
     * `user_type` is not validated, not trusted and not written. That is a
     * stronger property than rejecting the field — there is no field.
     */
    public function test_public_registration_ignores_any_role_sent_by_the_client(): void
    {
        $this->post('/register', [
            'user_type' => 'Admin',
            'name'      => 'Evil User',
            'email'     => 'evil.user@student.nmsc.edu.ph',
            'password'  => 'secret123',
            'agree'     => '1',
        ]);

        // Written as the address says, not as the payload asked.
        $this->assertDatabaseHas('users', [
            'email'     => 'evil.user@student.nmsc.edu.ph',
            'user_type' => 'Student',
        ]);
        $this->assertDatabaseMissing('users', ['user_type' => 'Admin']);
    }

    /** The same holds for the staff domain: Instructor, never Admin. */
    public function test_a_posted_role_cannot_override_the_domain_on_the_staff_domain_either(): void
    {
        $this->post('/register', [
            'user_type' => 'Admin',
            'name'      => 'Evil Staff',
            'email'     => 'evil.staff@nmsc.edu.ph',
            'password'  => 'secret123',
            'agree'     => '1',
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'evil.staff@nmsc.edu.ph',
            'user_type' => 'Instructor',
        ]);
        $this->assertDatabaseMissing('users', ['user_type' => 'Admin']);
    }

    /** An address outside the two school domains creates nothing at all. */
    public function test_public_registration_refuses_an_outside_address(): void
    {
        $response = $this->post('/register', [
            'name'     => 'Evil User',
            'email'    => 'evil@example.com',
            'password' => 'secret123',
            'agree'    => '1',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'evil@example.com']);
    }

    /**
     * The domain is matched whole, not as a suffix. `endsWith('nmsc.edu.ph')`
     * would hand an Instructor account to anyone who can register
     * `not-nmsc.edu.ph`, and a lookalike registrable domain is the cheapest
     * way there is to buy a role.
     */
    public function test_a_lookalike_domain_does_not_pass_as_the_school(): void
    {
        foreach (['evil@not-nmsc.edu.ph', 'evil@nmsc.edu.ph.attacker.com', 'evil@fake-student.nmsc.edu.ph.co'] as $email) {
            $this->post('/register', [
                'name'     => 'Evil User',
                'email'    => $email,
                'password' => 'secret123',
                'agree'    => '1',
            ])->assertSessionHasErrors('email');

            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    /** Public signup serves both borrower roles, one domain each. */
    public function test_public_registration_derives_both_borrower_roles_from_the_domain(): void
    {
        $cases = [
            'student.one@student.nmsc.edu.ph' => 'Student',
            'instructor.one@nmsc.edu.ph'      => 'Instructor',
        ];

        foreach ($cases as $email => $role) {
            $this->post('/register', [
                'name'     => "Public $role",
                'email'    => $email,
                'password' => 'secret123',
                'agree'    => '1',
            ]);

            $this->assertDatabaseHas('users', [
                'email'     => $email,
                'user_type' => $role,
            ]);
        }
    }

    /**
     * POST /admin/users keeps AuthenticateUser::register, which still takes an
     * explicit user_type capped at the two borrower roles — an admin choosing a
     * borrower's role is a decision they are entitled to make, and they are
     * behind `userType:Admin` to make it. Admin accounts are made directly in
     * the database (seed or tinker) by design — see AGENT_CONTEXT.md. This pins
     * that decision so the cap is not widened by accident.
     */
    public function test_admin_users_form_cannot_create_admin_accounts(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'user_type' => 'Admin',
            'name'      => 'New Admin',
            'email'     => 'new-admin@example.com',
            'password'  => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors('user_type');
        $this->assertDatabaseMissing('users', ['email' => 'new-admin@example.com']);
    }

    /** The same form does create the two roles it is meant to. */
    public function test_admin_users_form_creates_borrower_accounts(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        $this->actingAs($admin)->post('/admin/users', [
            'user_type' => 'Instructor',
            'name'      => 'New Instructor',
            'email'     => 'new-instructor@example.com',
            'password'  => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'new-instructor@example.com',
            'user_type' => 'Instructor',
        ]);
    }

    public function test_borrower_cannot_update_another_users_request(): void
    {
        $owner   = User::factory()->create(['user_type' => 'Student']);
        $attacker = User::factory()->create(['user_type' => 'Student']);
        $equipment = \App\Models\Equipment::create(['equipment_name' => 'Test Laptop', 'quantity' => 10, 'available_quantity' => 10, 'status' => 'Available']);

        $request = ItemRequest::create([
            'user_id'        => $owner->id,
            'equipment_id'   => $equipment->id,
            'quantity'       => 1,
            'status'         => 'Pending',
            'requested_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($attacker)->put('/borrower/request', [
            'id'       => $request->id,
            'quantity' => 99,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('item_requests', [
            'id'       => $request->id,
            'quantity' => 1,
        ]);
    }

    public function test_borrower_can_update_own_request(): void
    {
        $owner = User::factory()->create(['user_type' => 'Student']);
        $equipment = \App\Models\Equipment::create(['equipment_name' => 'Test Laptop', 'quantity' => 10, 'available_quantity' => 10, 'status' => 'Available']);

        $request = ItemRequest::create([
            'user_id'        => $owner->id,
            'equipment_id'   => $equipment->id,
            'quantity'       => 1,
            'status'         => 'Pending',
            'requested_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($owner)->put('/borrower/request', [
            'id'       => $request->id,
            'quantity' => 3,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('item_requests', [
            'id'       => $request->id,
            'quantity' => 3,
        ]);
    }

    public function test_borrower_cannot_delete_another_users_request(): void
    {
        $owner    = User::factory()->create(['user_type' => 'Student']);
        $attacker = User::factory()->create(['user_type' => 'Student']);
        $equipment = \App\Models\Equipment::create(['equipment_name' => 'Test Laptop', 'quantity' => 10, 'available_quantity' => 10, 'status' => 'Available']);

        $request = ItemRequest::create([
            'user_id'        => $owner->id,
            'equipment_id'   => $equipment->id,
            'quantity'       => 1,
            'status'         => 'Pending',
            'requested_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($attacker)->delete("/borrower/request/{$request->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('item_requests', ['id' => $request->id]);
    }

    public function test_return_alerts_endpoint_requires_authentication(): void
    {
        $this->get('/send-return-alerts')->assertNotFound();
        $this->get('/admin/send-return-alerts')->assertRedirect('/login');
    }

    public function test_test_mail_and_dead_navbar_routes_are_gone(): void
    {
        $this->get('/test-mail')->assertNotFound();
        $this->get('/components/admin/navbar')->assertNotFound();
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_login_validation_returns_field_errors_not_generic_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email'    => 'not-an-email',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertStringNotContainsString(
            'Something went wrong',
            session('errors')->all()[0] ?? ''
        );
    }

    public function test_public_portal_pages_render_with_shared_stylesheet(): void
    {
        // Landing page
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('CICT Equipment Borrower System')
            ->assertSee('auth.css');

        // Login page. It was rebuilt on the app's Tailwind bundle and no longer
        // pulls auth.css — the other three public pages still do, so the
        // stylesheet stays shared and this asserts the form instead.
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('Sign in')
            ->assertSee('Students and instructors both sign in here.')
            ->assertSee(route('login.store'));

        // Register page. Rebuilt on the app's Tailwind bundle alongside the
        // login page, so it no longer pulls auth.css either — the remaining
        // three public pages still do, which is what keeps the file shared.
        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('Request an account')
            ->assertSee(route('register.store'));
    }

    public function test_shared_auth_stylesheet_is_publicly_servable(): void
    {
        // The shared stylesheet must resolve to a real file under public/
        // so the browser can actually load it (asset() points to public/).
        $publicPath = public_path('resources/css/auth.css');
        $this->assertFileExists($publicPath);

        // asset() must resolve to a URL rooted at the stylesheet location
        $assetUrl = asset('resources/css/auth.css');
        $this->assertStringContainsString('/resources/css/auth.css', $assetUrl);
    }
}

