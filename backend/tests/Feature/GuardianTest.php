<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuardianTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();

        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $adminRole = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id, ['school_id' => $this->school->id]);

        $this->secretary = User::factory()->create(['school_id' => $this->school->id]);
        $secretaryRole = Role::where('name', RoleEnum::SECRETARY->value)->first();
        $this->secretary->roles()->attach($secretaryRole->id, ['school_id' => $this->school->id]);
    }

    public function test_can_list_guardians(): void
    {
        Guardian::factory()->count(3)->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->secretary);

        $response = $this->getJson('/api/v1/guardians');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_guardian(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/v1/guardians', [
            'full_name' => 'João Silva',
            'relationship' => 'pai',
            'phone' => '923000000',
            'email' => 'joao@example.com',
            'bi_number' => '000000000LA000',
            'occupation' => 'Engenheiro',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'João Silva');

        $this->assertDatabaseHas('guardians', [
            'full_name' => 'João Silva',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_can_update_guardian(): void
    {
        $guardian = Guardian::factory()->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->secretary);

        $response = $this->putJson("/api/v1/guardians/{$guardian->id}", [
            'full_name' => 'Nome Actualizado',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.full_name', 'Nome Actualizado');

        $this->assertDatabaseHas('guardians', [
            'id' => $guardian->id,
            'full_name' => 'Nome Actualizado',
        ]);
    }

    public function test_can_delete_guardian(): void
    {
        $guardian = Guardian::factory()->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->secretary);

        $response = $this->deleteJson("/api/v1/guardians/{$guardian->id}");

        $response->assertOk();

        $this->assertSoftDeleted('guardians', ['id' => $guardian->id]);
    }
}
