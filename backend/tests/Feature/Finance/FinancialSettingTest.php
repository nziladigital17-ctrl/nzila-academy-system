<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\FinancialSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSettingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        
        $role = Role::where('name', RoleEnum::FINANCIAL->value)->first();
        $this->user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);
    }

    public function test_can_list_settings()
    {
        FinancialSetting::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'setting_key' => 'currency',
            'setting_value' => 'AOA',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/financial-settings?academic_year_id={$this->academicYear->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['setting_value' => 'AOA']);
    }

    public function test_can_upsert_settings()
    {
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'settings' => [
                ['setting_key' => 'late_fee_percentage', 'setting_value' => '5'],
                ['setting_key' => 'grace_period_days', 'setting_value' => '10'],
            ]
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/financial-settings', $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('financial_settings', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'setting_key' => 'late_fee_percentage',
            'setting_value' => '5',
        ]);

        // Upsert again with changed value
        $data['settings'][0]['setting_value'] = '10';
        $response = $this->actingAs($this->user)->postJson('/api/v1/financial-settings', $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('financial_settings', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'setting_key' => 'late_fee_percentage',
            'setting_value' => '10',
        ]);
        
        // Ensure no duplication
        $this->assertEquals(2, FinancialSetting::where('school_id', $this->school->id)->count());
    }
}



