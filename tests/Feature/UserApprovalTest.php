<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUser(string $role, array $attrs = []): User
    {
        $user = User::create(array_merge([
            'name' => 'User '.$role,
            'email' => $role.'@test',
            'password' => 'password',
            'is_approved' => true,
        ], $attrs));
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_approve_pending_user(): void
    {
        $admin = $this->makeUser('admin');
        $pending = $this->makeUser('karyawan', [
            'email' => 'pending@test',
            'nik' => 'NIK123',
            'is_approved' => false,
            'requested_role' => 'karyawan',
        ]);

        $this->actingAs($admin)
            ->post('/users/'.$pending->id.'/approve', ['role' => 'karyawan'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($pending->fresh()->is_approved);
        $this->assertTrue($pending->fresh()->hasRole('karyawan'));
    }

    public function test_karyawan_cannot_access_user_management(): void
    {
        $karyawan = $this->makeUser('karyawan');

        $this->actingAs($karyawan)
            ->get('/users')
            ->assertForbidden();
    }
}
