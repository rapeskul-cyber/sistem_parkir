<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulasiRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_super_admin_can_access_simulasi_status(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@parkir.test',
            'role' => 'admin',
        ]);

        $superAdmin = User::factory()->create([
            'email' => 'superadmin@parkir.test',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/simulasi/status')
            ->assertOk();

        $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/simulasi/status')
            ->assertOk();
    }
}
