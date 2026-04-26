<?php

namespace Tests\Feature;

use App\Models\BusRoute;
use App\Models\BusVehicle;
use App\Models\Package;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PackagesAndBusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $eliteSchool;

    private School $proSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PackageSeeder::class);

        $this->eliteSchool = School::create([
            'name' => 'Elite High',
            'slug' => 'elite-high',
            'status' => 'active',
            'package_id' => Package::where('slug', Package::ELITE)->value('id'),
        ]);

        $this->proSchool = School::create([
            'name' => 'Pro High',
            'slug' => 'pro-high',
            'status' => 'active',
            'package_id' => Package::where('slug', Package::PRO)->value('id'),
        ]);
    }

    private function user(string $roleSlug, ?School $school = null): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'school_id' => $school?->id ?? $this->eliteSchool->id,
            'role_id' => $role->id,
            'first_name' => 'T',
            'last_name' => ucfirst(str_replace('_', '', $roleSlug)).rand(1000, 9999),
            'username' => $roleSlug.'-'.uniqid(),
            'password' => Hash::make('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function systemAdmin(): User
    {
        $role = Role::where('slug', Role::SYSTEM_ADMIN)->firstOrFail();

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'Sys',
            'last_name' => 'Admin'.rand(1000, 9999),
            'username' => 'sa-'.uniqid(),
            'password' => Hash::make('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ---------- Package catalog (System Admin only) ----------

    public function test_system_admin_can_view_packages_index(): void
    {
        $this->actingAs($this->systemAdmin())->get('/packages')->assertOk();
    }

    public function test_director_cannot_view_packages_index(): void
    {
        $this->actingAs($this->user(Role::DIRECTOR))->get('/packages')->assertForbidden();
    }

    public function test_school_admin_cannot_view_packages_index(): void
    {
        $this->actingAs($this->user(Role::SCHOOL_ADMIN))->get('/packages')->assertForbidden();
    }

    public function test_teacher_cannot_view_packages_index(): void
    {
        $this->actingAs($this->user(Role::TEACHER))->get('/packages')->assertForbidden();
    }

    public function test_system_admin_can_assign_package_to_school(): void
    {
        $admin = $this->systemAdmin();
        $basic = Package::where('slug', Package::BASIC)->firstOrFail();

        $this->actingAs($admin)
            ->post("/packages/schools/{$this->proSchool->id}", ['package_id' => $basic->id])
            ->assertRedirect('/packages');

        $this->assertSame($basic->id, $this->proSchool->fresh()->package_id);
    }

    public function test_director_cannot_assign_package(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $basic = Package::where('slug', Package::BASIC)->firstOrFail();

        $this->actingAs($director)
            ->post("/packages/schools/{$this->proSchool->id}", ['package_id' => $basic->id])
            ->assertForbidden();
    }

    // ---------- School subscription card ----------

    public function test_director_can_view_own_subscription(): void
    {
        $this->actingAs($this->user(Role::DIRECTOR))->get('/school/package')
            ->assertOk()
            ->assertSee('Elite');
    }

    public function test_school_admin_can_view_own_subscription(): void
    {
        $this->actingAs($this->user(Role::SCHOOL_ADMIN))->get('/school/package')->assertOk();
    }

    public function test_teacher_cannot_view_subscription(): void
    {
        $this->actingAs($this->user(Role::TEACHER))->get('/school/package')->assertForbidden();
    }

    public function test_student_cannot_view_subscription(): void
    {
        $this->actingAs($this->user(Role::STUDENT))->get('/school/package')->assertForbidden();
    }

    // ---------- Bus tracking — Elite gate ----------

    public function test_pro_school_director_cannot_access_bus_tracking(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->proSchool);
        $this->actingAs($director)->get('/bus')->assertForbidden();
    }

    public function test_pro_school_director_cannot_view_bus_map(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->proSchool);
        $this->actingAs($director)->get('/bus/map')->assertForbidden();
    }

    public function test_elite_school_director_can_access_bus_tracking(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $this->actingAs($director)->get('/bus')->assertOk();
    }

    public function test_elite_school_school_admin_can_access_bus_tracking(): void
    {
        $admin = $this->user(Role::SCHOOL_ADMIN, $this->eliteSchool);
        $this->actingAs($admin)->get('/bus')->assertOk();
    }

    public function test_elite_school_teacher_cannot_access_bus_tracking(): void
    {
        $teacher = $this->user(Role::TEACHER, $this->eliteSchool);
        $this->actingAs($teacher)->get('/bus')->assertForbidden();
    }

    public function test_elite_school_student_cannot_access_bus_tracking(): void
    {
        $student = $this->user(Role::STUDENT, $this->eliteSchool);
        $this->actingAs($student)->get('/bus')->assertForbidden();
    }

    // ---------- Bus CRUD lifecycle ----------

    public function test_director_can_create_bus_route(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);

        $this->actingAs($director)
            ->post('/bus/routes', [
                'name' => 'North Loop',
                'code' => 'N1',
                'description' => 'Suburb to school',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bus_routes', [
            'school_id' => $this->eliteSchool->id,
            'name' => 'North Loop',
        ]);
    }

    public function test_director_can_create_vehicle_assigned_to_route(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $route = BusRoute::create([
            'school_id' => $this->eliteSchool->id,
            'name' => 'East Loop',
            'status' => BusRoute::STATUS_ACTIVE,
        ]);

        $this->actingAs($director)
            ->post('/bus/vehicles', [
                'plate_number' => 'T-123-ABC',
                'label' => 'Bus 1',
                'capacity' => 40,
                'bus_route_id' => $route->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bus_vehicles', [
            'school_id' => $this->eliteSchool->id,
            'plate_number' => 'T-123-ABC',
            'bus_route_id' => $route->id,
        ]);
    }

    public function test_cannot_assign_vehicle_to_route_from_another_school(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);

        // Even though the proSchool is non-Elite, a hostile route_id from any other school
        // must be rejected for Elite school's CRUD to maintain isolation.
        $otherElite = School::create([
            'name' => 'Other Elite',
            'slug' => 'other-elite',
            'status' => 'active',
            'package_id' => Package::where('slug', Package::ELITE)->value('id'),
        ]);
        $foreignRoute = BusRoute::create([
            'school_id' => $otherElite->id,
            'name' => 'Foreign',
            'status' => BusRoute::STATUS_ACTIVE,
        ]);

        $this->actingAs($director)
            ->post('/bus/vehicles', [
                'plate_number' => 'T-999-XYZ',
                'bus_route_id' => $foreignRoute->id,
            ])
            ->assertSessionHasErrors('plate_number');

        $this->assertDatabaseMissing('bus_vehicles', ['plate_number' => 'T-999-XYZ']);
    }

    public function test_director_can_record_vehicle_position(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $vehicle = BusVehicle::create([
            'school_id' => $this->eliteSchool->id,
            'plate_number' => 'T-555-AAA',
            'capacity' => 30,
            'status' => BusVehicle::STATUS_ACTIVE,
        ]);

        $this->actingAs($director)
            ->post("/bus/vehicles/{$vehicle->id}/position", [
                'latitude' => -6.7924,
                'longitude' => 39.2083,
            ])
            ->assertRedirect();

        $vehicle->refresh();
        $this->assertSame(-6.7924, (float) $vehicle->last_latitude);
        $this->assertSame(39.2083, (float) $vehicle->last_longitude);
        $this->assertNotNull($vehicle->last_position_at);
    }

    public function test_invalid_coordinates_rejected(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $vehicle = BusVehicle::create([
            'school_id' => $this->eliteSchool->id,
            'plate_number' => 'T-666-BBB',
            'capacity' => 0,
            'status' => BusVehicle::STATUS_ACTIVE,
        ]);

        $this->actingAs($director)
            ->post("/bus/vehicles/{$vehicle->id}/position", [
                'latitude' => 200,
                'longitude' => 0,
            ])
            ->assertSessionHasErrors('latitude');
    }

    // ---------- Cross-school isolation on bus resources ----------

    public function test_director_cannot_edit_route_from_another_school(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $otherElite = School::create([
            'name' => 'Other Elite',
            'slug' => 'other-elite',
            'status' => 'active',
            'package_id' => Package::where('slug', Package::ELITE)->value('id'),
        ]);
        $foreignRoute = BusRoute::create([
            'school_id' => $otherElite->id,
            'name' => 'Foreign route',
            'status' => BusRoute::STATUS_ACTIVE,
        ]);

        $this->actingAs($director)
            ->post("/bus/routes/{$foreignRoute->id}/stops", ['name' => 'Should fail'])
            ->assertForbidden();

        $this->assertDatabaseMissing('bus_stops', ['name' => 'Should fail']);
    }

    public function test_director_cannot_record_position_for_other_school_vehicle(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $otherElite = School::create([
            'name' => 'Other Elite',
            'slug' => 'other-elite',
            'status' => 'active',
            'package_id' => Package::where('slug', Package::ELITE)->value('id'),
        ]);
        $foreignVehicle = BusVehicle::create([
            'school_id' => $otherElite->id,
            'plate_number' => 'X-1',
            'capacity' => 0,
            'status' => BusVehicle::STATUS_ACTIVE,
        ]);

        $this->actingAs($director)
            ->post("/bus/vehicles/{$foreignVehicle->id}/position", [
                'latitude' => 1, 'longitude' => 1,
            ])
            ->assertForbidden();
    }

    // ---------- Feature gate semantics ----------

    public function test_school_with_no_package_blocks_bus_tracking(): void
    {
        $orphan = School::create(['name' => 'Orphan', 'slug' => 'orphan', 'status' => 'active']);
        $director = $this->user(Role::DIRECTOR, $orphan);
        $this->actingAs($director)->get('/bus')->assertForbidden();
    }

    public function test_downgrading_school_revokes_bus_access(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $this->actingAs($director)->get('/bus')->assertOk();

        $this->eliteSchool->update([
            'package_id' => Package::where('slug', Package::PRO)->value('id'),
        ]);

        $this->actingAs($director->fresh())->get('/bus')->assertForbidden();
    }

    public function test_system_admin_bypass_bus_gate_via_school_id_param(): void
    {
        $admin = $this->systemAdmin();
        $this->actingAs($admin)->get("/bus?school_id={$this->eliteSchool->id}")->assertOk();
    }

    public function test_system_admin_can_inspect_non_elite_school_bus_admin(): void
    {
        // System Admin retains god-mode access for support / migration purposes,
        // even when the targeted school's subscription doesn't include bus tracking.
        $admin = $this->systemAdmin();
        $this->actingAs($admin)->get("/bus?school_id={$this->proSchool->id}")->assertOk();
    }

    // ---------- Regression: lat/lng = 0 coordinates and XSS-safe map rendering ----------

    public function test_stop_at_zero_coordinates_still_renders_position(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $route = BusRoute::create([
            'school_id' => $this->eliteSchool->id,
            'name' => 'Equator Loop',
            'status' => BusRoute::STATUS_ACTIVE,
        ]);
        $route->stops()->create([
            'name' => 'Equator Stop',
            'latitude' => 0,
            'longitude' => 0,
            'position' => 1,
        ]);

        $this->actingAs($director)->get('/bus')
            ->assertOk()
            ->assertSee('Equator Stop')
            ->assertSee('(0, 0)');
    }

    public function test_system_admin_forms_propagate_school_id(): void
    {
        // Regression: System Admin viewing /bus?school_id=X must see forms that submit
        // school_id back so storeRoute / storeVehicle don't 404 (they have no school of their own).
        $admin = $this->systemAdmin();
        $body = $this->actingAs($admin)
            ->get("/bus?school_id={$this->eliteSchool->id}")
            ->assertOk()
            ->getContent();

        $hidden = '<input type="hidden" name="school_id" value="'.$this->eliteSchool->id.'">';
        $this->assertSame(
            2,
            substr_count($body, $hidden),
            'Both the new-route and new-vehicle forms must carry the school_id.'
        );
        $this->assertStringContainsString(
            '/bus/map?school_id='.$this->eliteSchool->id,
            $body,
            'The Live map link must propagate school_id for System Admin.'
        );
    }

    public function test_system_admin_can_create_route_via_posted_school_id(): void
    {
        $admin = $this->systemAdmin();
        $this->actingAs($admin)
            ->post('/bus/routes', [
                'school_id' => $this->eliteSchool->id,
                'name' => 'Sysadmin-created',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bus_routes', [
            'school_id' => $this->eliteSchool->id,
            'name' => 'Sysadmin-created',
        ]);
    }

    public function test_school_user_does_not_emit_school_id_hidden_input(): void
    {
        // For non-admin users we don't render the hidden field — their school is
        // resolved from $actor->school regardless, and we don't want them to spoof.
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        $body = $this->actingAs($director)->get('/bus')->assertOk()->getContent();

        $this->assertStringNotContainsString('name="school_id"', $body);
    }

    public function test_map_does_not_emit_raw_script_from_vehicle_label(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->eliteSchool);
        BusVehicle::create([
            'school_id' => $this->eliteSchool->id,
            'plate_number' => 'XSS-TEST',
            'label' => '<script>alert(1)</script>',
            'capacity' => 0,
            'status' => BusVehicle::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($director)->get('/bus/map')->assertOk();
        $body = $response->getContent();

        // The label is embedded via @json into a JS literal, which escapes < and >
        // so the raw "<script>" tag must not appear inline. The escaped form is fine.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
    }
}
