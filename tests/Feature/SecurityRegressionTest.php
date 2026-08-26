<?php

namespace Tests\Feature;

use App\Models\ItemRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_cannot_create_admin_account(): void
    {
        $response = $this->post('/register', [
            'user_type' => 'Admin',
            'name'      => 'Evil User',
            'email'     => 'evil@example.com',
            'password'  => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertDatabaseHas('users', [
            'email'    => 'evil@example.com',
            'user_type' => 'Student',
        ]);

        $this->assertDatabaseMissing('users', [
            'email'    => 'evil@example.com',
            'user_type' => 'Admin',
        ]);
    }

    public function test_admin_can_still_create_admin_accounts(): void
    {
        $admin = User::factory()->create(['user_type' => 'Admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'user_type' => 'Admin',
            'name'      => 'New Admin',
            'email'     => 'new-admin@example.com',
            'password'  => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'new-admin@example.com',
            'user_type' => 'Admin',
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
}

