<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_other_user_password(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Parkir',
            'email' => 'admin@parkir.test',
            'role' => 'admin',
        ]);

        $petugas = User::factory()->create([
            'name' => 'Petugas Baru',
            'email' => 'petugas@parkir.test',
            'password' => bcrypt('old-password'),
            'role' => 'petugas',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/petugas/{$petugas->id}/reset-password", [
                'password' => 'new-password-123',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $petugas->refresh();
        $this->assertTrue(Hash::check('new-password-123', $petugas->password));
    }
}
