<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        $roleAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrador']);
        $roleAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        $roleDentista = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Dentista']);
        $roleDentista->givePermissionTo([
            'ver_dashboard', 'ver_pacientes', 'crear_pacientes', 'editar_pacientes',
            'ver_citas', 'crear_citas', 'editar_citas',
            'ver_facturacion', 'crear_pagos',
            'ver_contratos', 'crear_contratos'
        ]);

        $roleRecep = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Recepcionista']);
        $roleRecep->givePermissionTo([
            'ver_dashboard', 'ver_pacientes', 'crear_pacientes', 'editar_pacientes',
            'ver_citas', 'crear_citas', 'editar_citas', 'cancelar_citas',
            'ver_facturacion', 'crear_pagos',
            'ver_mensajes_crm', 'enviar_mensajes',
            'imprimir_tickets'
        ]);

        $roleAsistente = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Asistente']);
        $roleAsistente->givePermissionTo([
            'ver_dashboard', 'ver_pacientes', 'ver_citas'
        ]);

        // Create a default admin user
        $admin = \App\Models\User::firstOrCreate(
            ['username' => 'admin'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Sistema',
                'email' => 'admin@dentalsystem.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('Administrador');
    }
}
