<script lang="ts">
    import { Shield, Plus, Edit } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from '@/components/ui/table';
    import { Badge } from '@/components/ui/badge';
    import RoleFormModal from '@/components/RoleFormModal.svelte';

    let { roles, permissions } = $props();

    let isModalOpen = $state(false);
    let modalRole = $state(null);

    function openCreateModal() {
        modalRole = null;
        isModalOpen = true;
    }

    function openEditModal(role) {
        modalRole = role;
        isModalOpen = true;
    }
</script>

<AppHead title="Gestión de Roles y Permisos" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Roles y Permisos
            </h1>
            <p class="text-muted-foreground mt-1">
                Administra los accesos y niveles de seguridad del sistema.
            </p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openCreateModal}>
            <Plus class="mr-2 h-4 w-4" />
            Crear Rol
        </Button>
    </div>

    <div
        class="bg-card rounded-lg shadow-sm border overflow-hidden flex flex-col flex-1"
    >
        <div class="relative w-full overflow-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[200px]">Nombre del Rol</TableHead>
                        <TableHead>Permisos Asignados</TableHead>
                        <TableHead class="text-right w-[100px]">Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {#if roles.length === 0}
                        <TableRow>
                            <TableCell
                                colspan="3"
                                class="text-center h-24 text-muted-foreground"
                            >
                                No hay roles registrados.
                            </TableCell>
                        </TableRow>
                    {:else}
                        {#each roles as role}
                            <TableRow>
                                <TableCell class="font-medium align-top py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                            <Shield class="h-4 w-4" />
                                        </div>
                                        <span class="capitalize">{role.name}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        {#if role.permissions.length === 0}
                                            <span class="text-xs text-muted-foreground italic">Sin permisos asignados</span>
                                        {:else}
                                            {#each role.permissions as permission}
                                                <Badge variant="secondary" class="font-normal text-xs bg-blue-50 text-blue-700 hover:bg-blue-100">
                                                    {permission.name}
                                                </Badge>
                                            {/each}
                                        {/if}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right align-top py-4">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                        onclick={() => openEditModal(role)}
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <!-- No hay opción de eliminar según los requerimientos -->
                                </TableCell>
                            </TableRow>
                        {/each}
                    {/if}
                </TableBody>
            </Table>
        </div>
    </div>
</div>

<RoleFormModal bind:isOpen={isModalOpen} role={modalRole} {permissions} />
