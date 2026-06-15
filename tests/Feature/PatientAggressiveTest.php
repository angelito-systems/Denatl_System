<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset permissions cache
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissions = ['ver_pacientes', 'crear_pacientes', 'editar_pacientes', 'eliminar_pacientes'];
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission]);
    }

    $this->adminRole = Role::firstOrCreate(['name' => 'Administrador']);
    $this->adminRole->givePermissionTo($permissions);

    $this->assistantRole = Role::firstOrCreate(['name' => 'Asistente']);
    $this->assistantRole->givePermissionTo(['ver_pacientes']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Administrador');

    $this->assistant = User::factory()->create();
    $this->assistant->assignRole('Asistente');

    $this->unauthorizedUser = User::factory()->create();
});

it('prevents unauthorized users from seeing patients list', function () {
    $this->actingAs($this->unauthorizedUser)
        ->get(route('patients.index'))
        ->assertForbidden();
});

it('prevents assistants from deleting patients', function () {
    $patient = Patient::factory()->create();

    $this->actingAs($this->assistant)
        ->delete(route('patients.destroy', $patient))
        ->assertForbidden();

    $this->assertDatabaseHas('patients', ['id' => $patient->id]);
});

it('allows admin to delete patients', function () {
    $patient = Patient::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('patients.destroy', $patient))
        ->assertRedirect();

    $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
});

dataset('extreme_patient_data', [
    'empty fields' => [[]],
    'sql injection' => [['first_name' => "Robert'); DROP TABLE patients;--"]],
    'xss payload' => [['first_name' => "<script>alert('xss')</script>"]],
    'emoji flood' => [['first_name' => str_repeat('🤪', 500)]],
    'giant string' => [['first_name' => str_repeat('A', 5000)]],
]);

it('handles aggressive extreme inputs gracefully when creating patients', function (array $payload) {
    // Fill the required missing fields with valid data if not testing them
    $data = array_merge([
        'dni' => fake()->unique()->numerify('########'),
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
    ], $payload);

    $response = $this->actingAs($this->admin)
        ->post(route('patients.store'), $data);

    // Should either redirect back with errors (validation failed) OR redirect back with success (data sanitized/truncated and saved)
    // We just want to ensure it doesn't throw a 500 error
    expect($response->status())->toBeIn([302]);
})->with('extreme_patient_data');
