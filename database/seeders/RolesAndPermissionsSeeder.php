<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'ver_dashboard', 'ver_pacientes', 'crear_pacientes',
            'editar_pacientes', 'eliminar_pacientes',
            'ver_citas', 'crear_citas', 'editar_citas', 'cancelar_citas',
            'ver_facturacion', 'crear_pagos', 'ver_reportes',
            'ver_staff', 'crear_staff', 'editar_staff',
            'ver_configuracion', 'editar_configuracion',
            'ver_contratos', 'crear_contratos',
            'ver_mensajes_crm', 'enviar_mensajes',
            'imprimir_tickets',
            'patient-images.view', 'patient-images.create', 'patient-images.edit',
            'patient-images.delete', 'patient-images.download',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador']);
        $roleAdmin->givePermissionTo(Permission::all());

        $roleDentista = Role::firstOrCreate(['name' => 'Dentista']);
        $roleDentista->syncPermissions([
            'ver_dashboard', 'ver_pacientes', 'crear_pacientes', 'editar_pacientes',
            'ver_citas', 'crear_citas', 'editar_citas',
            'ver_facturacion', 'crear_pagos',
            'ver_contratos', 'crear_contratos',
            'patient-images.view', 'patient-images.create', 'patient-images.edit',
            'patient-images.delete', 'patient-images.download',
        ]);

        $roleRecep = Role::firstOrCreate(['name' => 'Recepcionista']);
        $roleRecep->syncPermissions([
            'ver_dashboard', 'ver_pacientes', 'crear_pacientes', 'editar_pacientes',
            'ver_citas', 'crear_citas', 'editar_citas', 'cancelar_citas',
            'ver_mensajes_crm', 'enviar_mensajes',
            'imprimir_tickets',
        ]);

        $roleAsistente = Role::firstOrCreate(['name' => 'Asistente']);
        $roleAsistente->syncPermissions([
            'ver_dashboard', 'ver_pacientes', 'ver_citas',
        ]);

        // Create a default admin user
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Sistema',
                'email' => 'admin@dentalsystem.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('Administrador');
    }
}
