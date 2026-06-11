<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Gestión de Staff', href: '/staff' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Checkbox } from '@/components/ui/checkbox';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow
    } from '@/components/ui/table';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogHeader,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { router, useForm } from '@inertiajs/svelte';
    import { index } from '@/routes/staff';
    import { Search, Plus, UserCog, Trash2, Edit } from 'lucide-svelte';

    let { users, roles, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;
    let isUserModalOpen = $state(false);

    const userForm = useForm({
        id: null as number | null,
        first_name: '',
        last_name: '',
        email: '',
        username: '',
        password: '',
        role: 'Administrador',
        is_active: true
    });

    function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(index(), { search }, { preserveState: true, replace: true });
        }, 300);
    }

    function openNewUserModal() {
        userForm.reset();
        userForm.id = null;
        userForm.is_active = true;
        userForm.role = 'Administrador';
        isUserModalOpen = true;
    }

    function saveUser(e: Event) {
        e.preventDefault();
        // Simulación: aquí llamaríamos a la ruta POST / PUT real del staff
        console.log('Guardando usuario:', $userForm);
        isUserModalOpen = false;
        userForm.reset();
    }
</script>

<AppHead title="Gestión de Staff" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Gestión de Staff</h1>
            <p class="text-muted-foreground mt-1">Administra los usuarios y roles de la clínica.</p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openNewUserModal}>
            <Plus class="mr-2 h-4 w-4" />
            Nuevo usuario
        </Button>
    </div>

    <div class="flex items-center gap-4 bg-card p-4 rounded-lg shadow-sm border">
        <div class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input 
                type="text" 
                placeholder="Buscar por nombre o correo..." 
                class="pl-9"
                bind:value={search}
                oninput={handleSearch}
            />
        </div>
    </div>

    <div class="bg-card rounded-lg shadow-sm border overflow-hidden">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Rol Principal</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead class="text-right">Acciones</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {#if users.data.length === 0}
                    <TableRow>
                        <TableCell colspan="5" class="text-center h-24 text-muted-foreground">
                            No se encontraron usuarios.
                        </TableCell>
                    </TableRow>
                {:else}
                    {#each users.data as user}
                        <TableRow class="hover:bg-muted/50 cursor-pointer">
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold">
                                        {user.first_name.charAt(0)}{user.last_name.charAt(0)}
                                    </div>
                                    {user.first_name} {user.last_name}
                                </div>
                            </TableCell>
                            <TableCell>{user.email}</TableCell>
                            <TableCell>
                                {#if user.roles && user.roles.length > 0}
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-md border border-purple-200">
                                        {user.roles[0].name}
                                    </span>
                                {:else}
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-md border border-blue-200">
                                        Dentista
                                    </span>
                                {/if}
                            </TableCell>
                            <TableCell>
                                <span class="text-green-600 bg-green-50 px-2 py-1 rounded-md border border-green-200 text-xs">Activo</span>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="outline" size="sm" class="h-8">
                                    <Edit class="h-3 w-3 mr-1" /> Editar
                                </Button>
                            </TableCell>
                        </TableRow>
                    {/each}
                {/if}
            </TableBody>
        </Table>
        <div class="p-2 border-t text-xs text-muted-foreground text-right px-4">
            Doble clic para editar
        </div>
    </div>
</div>

<Dialog bind:open={isUserModalOpen}>
    <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
                <UserCog class="w-5 h-5 text-blue-600" />
                Nuevo Usuario
            </DialogTitle>
        </DialogHeader>
        <form onsubmit={saveUser} class="space-y-4 py-4">
            <div class="space-y-2">
                <Label>Nombres *</Label>
                <Input bind:value={$userForm.first_name} placeholder="Ej: Juan" required />
            </div>
            <div class="space-y-2">
                <Label>Apellidos *</Label>
                <Input bind:value={$userForm.last_name} placeholder="Ej: Pérez" required />
            </div>
            <div class="space-y-2">
                <Label>Correo electrónico</Label>
                <Input type="email" bind:value={$userForm.email} placeholder="ejemplo@clinica.com" />
            </div>
            <div class="space-y-2">
                <Label>Nombre de usuario (Login) *</Label>
                <Input bind:value={$userForm.username} placeholder="Ej: jperez" required />
            </div>
            <div class="space-y-2">
                <Label>Contraseña *</Label>
                <Input type="password" bind:value={$userForm.password} required />
            </div>
            <div class="space-y-2">
                <Label>Rol del usuario *</Label>
                <select class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" bind:value={$userForm.role}>
                    <option>Administrador</option>
                    <option>Dentista</option>
                    <option>Recepcionista</option>
                </select>
            </div>
            <div class="flex items-center space-x-2 pt-2">
                <Checkbox id="is-active" bind:checked={$userForm.is_active} />
                <Label for="is-active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Usuario activo (puede iniciar sesión)
                </Label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                <Button variant="outline" type="button" onclick={() => isUserModalOpen = false}>
                    ✕ Cancelar
                </Button>
                <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={$userForm.processing}>
                    <UserCog class="w-4 h-4 mr-2" />
                    Guardar usuario
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
