<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create(['name' => 'Escola Teste']);
        
        $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        $this->user->roles()->attach($role->id, ['school_id' => $this->school->id]);
    }

    public function test_can_list_rooms()
    {
        Room::factory()->count(3)->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v1/rooms');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_create_room()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/rooms', [
            'name' => 'Sala 1',
            'capacity' => 35,
            'building' => 'Bloco A',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('rooms', [
            'name' => 'Sala 1',
            'capacity' => 35,
            'school_id' => $this->school->id,
        ]);
    }

    public function test_cannot_delete_room_with_classes()
    {
        $room = Room::factory()->create(['school_id' => $this->school->id]);
        $class = SchoolClass::factory()->create([
            'room_id' => $room->id,
            'school_id' => $this->school->id,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/rooms/{$room->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_can_delete_room_without_classes()
    {
        $room = Room::factory()->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/rooms/{$room->id}");

        $response->assertOk();
        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }
}
