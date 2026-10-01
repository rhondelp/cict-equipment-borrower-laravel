<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Creating another admin (staff) account from the users screen.
 *
 * A new admin can make further admins, so the form holds it to more than a
 * borrower account: a school address, an 8-character letters-and-numbers
 * password, a reason recorded with the creator's name, and the creating
 * admin's own password.
 */
class StaffAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'user_type' => 'Admin',
            'name' => 'Quincy Jane Oliver',
            'email' => 'quincy@nmsc.edu.ph',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_type' => 'Admin',
            'name' => 'Ramon Cruz',
            'email' => 'ramon.cruz@nmsc.edu.ph',
            'password' => 'labstaff2026',
            'password_confirmation' => 'labstaff2026',
            'role_override_reason' => 'New lab custodian for the afternoon shift',
            // UserFactory's password.
            'current_password' => 'password',
        ], $overrides);
    }

    public function test_an_admin_can_create_another_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $staff = User::where('email', 'ramon.cruz@nmsc.edu.ph')->firstOrFail();
        $this->assertSame('Admin', $staff->user_type);
        $this->assertTrue(Hash::check('labstaff2026', $staff->password));
    }

    public function test_the_new_admin_is_recorded_with_who_made_them_and_why(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', $this->payload());

        $staff = User::where('email', 'ramon.cruz@nmsc.edu.ph')->firstOrFail();
        $this->assertSame($admin->id, $staff->role_overridden_by);
        $this->assertNotNull($staff->role_overridden_at);
        $this->assertStringContainsString('Set to Admin by Quincy Jane Oliver', $staff->roleOverrideLine());
        $this->assertStringContainsString('afternoon shift', $staff->roleOverrideLine());
    }

    public function test_the_new_admin_can_sign_in_and_reach_the_dashboard(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', $this->payload());
        auth()->logout();

        $this->post('/login', ['email' => 'ramon.cruz@nmsc.edu.ph', 'password' => 'labstaff2026'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_wrong_own_password_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/users', $this->payload(['current_password' => 'not-mine']))
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseMissing('users', ['email' => 'ramon.cruz@nmsc.edu.ph']);
    }

    public function test_a_reason_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/users', $this->payload(['role_override_reason' => '']))
            ->assertSessionHasErrors('role_override_reason');

        $this->assertDatabaseMissing('users', ['email' => 'ramon.cruz@nmsc.edu.ph']);
    }

    public function test_a_staff_account_must_be_on_the_school_domain(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/users', $this->payload(['email' => 'ramon@gmail.com']))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post('/admin/users', $this->payload(['email' => 'ramon@not-nmsc.edu.ph']))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, User::where('email', 'like', 'ramon@%')->count());
    }

    public function test_a_staff_password_needs_eight_characters_with_letters_and_numbers(): void
    {
        $admin = $this->admin();

        foreach (['abc123', 'onlyletters', '12345678'] as $weak) {
            $this->actingAs($admin)
                ->post('/admin/users', $this->payload(['password' => $weak, 'password_confirmation' => $weak]))
                ->assertSessionHasErrors('password');
        }

        $this->assertDatabaseMissing('users', ['email' => 'ramon.cruz@nmsc.edu.ph']);
    }

    public function test_borrower_accounts_keep_the_lighter_rules(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'user_type' => 'Student',
            'name' => 'Mia Santos',
            'email' => 'mia@example.com',
            'password' => 'abcd',
            'password_confirmation' => 'abcd',
        ])->assertSessionHasNoErrors();

        $mia = User::where('email', 'mia@example.com')->firstOrFail();
        $this->assertSame('Student', $mia->user_type);
        $this->assertNull($mia->role_overridden_at);
    }

    public function test_the_add_form_offers_admin_with_its_step_up_field(): void
    {
        $this->actingAs($this->admin())->get('/admin/users')
            ->assertOk()
            ->assertSee('<option value="Admin">Admin (staff)</option>', false)
            ->assertSee('name="current_password"', false)
            ->assertDontSee('<option value="Admin" hidden disabled>', false);
    }
}
