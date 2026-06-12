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
    import { Search, Plus, UserCog, Trash2, Edit, Loader2, CalendarClock } from 'lucide-svelte';
    import { toast } from 'svelte-sonner';
    import ScheduleModal from '@/components/ScheduleModal.svelte';

    let { users, roles, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;
    let isUserModalOpen = $state(false);
    let isDeleteModalOpen = $state(false);
    let isScheduleModalOpen = $state(false);
    let userToDelete = $state<number | null>(null);
    let selectedUserForSchedule = $state<any>(null);

    const userForm = useForm({
        id: null as number | null,
        first_name: '',
        last_name: '',
        email: '',
        username: '',
        password: '',
        role: 'Administrador',
        room: '',
        dni: '',
        cmp: '',
        is_active: true
    });

    let isSearchingDni = $state(false);

    async function searchDni() {
        if (!userForm.dni || userForm.dni.length !== 8) {
            toast.error('El DNI debe tener 8 dígitos');
            return;
        }

        isSearchingDni = true;
        try {
            const res = await fetch(`/api/reniec/${userForm.dni}`);
            const data = await res.json();
            
            if (data.success && data.data) {
                userForm.first_name = data.data.nombres;
                userForm.last_name = `${data.data.apellido_paterno} ${data.data.apellido_materno}`;
                toast.success('Datos obtenidos correctamente');
            } else {
                toast.error(data.message || 'No se encontraron resultados');
            }
        } catch (e) {
            toast.error('Error al conectar con el servicio RENIEC');
        } finally {
            isSearchingDni = false;
        }
    }

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
        userForm.room = '';
        userForm.dni = '';
        userForm.cmp = '';
        isUserModalOpen = true;
    }

    function saveUser(e: Event) {
        e.preventDefault();
        
        if (userForm.id) {
            userForm.put(`/staff/${userForm.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    isUserModalOpen = false;
                    toast.success('Usuario actualizado correctamente');
                }
            });
        } else {
            userForm.post('/staff', {
                preserveScroll: true,
                onSuccess: () => {
                    isUserModalOpen = false;
                    userForm.reset();
                    toast.success('Usuario creado correctamente');
                }
            });
        }
    }

    function editUser(user: any) {
        userForm.id = user.id;
        userForm.first_name = user.first_name;
        userForm.last_name = user.last_name;
        userForm.email = user.email;
        userForm.username = user.username;
        userForm.password = ''; // Don't show password, only edit if filled
        userForm.role = user.roles && user.roles.length > 0 ? user.roles[0].name : 'Dentista';
        userForm.room = user.room || '';
        userForm.dni = user.dni || '';
        userForm.cmp = user.cmp || '';
        userForm.is_active = user.is_active;
        isUserModalOpen = true;
    }

    function editSchedule(user: any) {
        selectedUserForSchedule = user;
        isScheduleModalOpen = true;
    }

    function deleteUser(id: number) {
        userToDelete = id;
        isDeleteModalOpen = true;
    }

    function confirmDelete() {
        if (userToDelete !== null) {
            router.delete(`/staff/${userToDelete}`, {
                preserveScroll: true,
                onSuccess: () => {
                    isDeleteModalOpen = false;
                    toast.success('Usuario eliminado');
                }
            });
        }
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
                        <TableRow class="hover:bg-muted/50 cursor-pointer" ondblclick={() => editUser(user)}>
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
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50" onclick={() => editSchedule(user)} title="Horario">
                                        <CalendarClock class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50" onclick={() => editUser(user)} title="Editar">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50" onclick={() => deleteUser(user.id)} title="Eliminar">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
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
    <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
                <UserCog class="w-5 h-5 text-blue-600" />
                {userForm.id ? 'Editar Usuario' : 'Nuevo Usuario'}
            </DialogTitle>
        </DialogHeader>
        <form onsubmit={saveUser} class="py-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2 col-span-2 md:col-span-1">
                    <Label>DNI</Label>
                    <div class="flex items-center gap-2">
                        <Input bind:value={userForm.dni} placeholder="Ej: 12345678" maxlength="8" />
                        <Button type="button" variant="secondary" onclick={searchDni} disabled={isSearchingDni}>
                            {#if isSearchingDni}
                                <Loader2 class="h-4 w-4 animate-spin" />
                            {:else}
                                <Search class="h-4 w-4" />
                            {/if}
                        </Button>
                    </div>
                    {#if userForm.errors.dni}<p class="text-xs text-red-500">{userForm.errors.dni}</p>{/if}
                </div>
                <div class="space-y-2 col-span-2 md:col-span-1">
                    <Label>Nombres *</Label>
                    <Input bind:value={userForm.first_name} placeholder="Ej: Juan" required />
                    {#if userForm.errors.first_name}<p class="text-xs text-red-500">{userForm.errors.first_name}</p>{/if}
                </div>
                <div class="space-y-2">
                    <Label>Apellidos *</Label>
                    <Input bind:value={userForm.last_name} placeholder="Ej: Pérez" required />
                    {#if userForm.errors.last_name}<p class="text-xs text-red-500">{userForm.errors.last_name}</p>{/if}
                </div>
                <div class="space-y-2">
                    <Label>Correo electrónico *</Label>
                    <Input type="email" bind:value={userForm.email} placeholder="ejemplo@clinica.com" required />
                    {#if userForm.errors.email}<p class="text-xs text-red-500">{userForm.errors.email}</p>{/if}
                </div>
                <div class="space-y-2">
                    <Label>Nombre de usuario (Login) *</Label>
                    <Input bind:value={userForm.username} placeholder="Ej: jperez" required />
                    {#if userForm.errors.username}<p class="text-xs text-red-500">{userForm.errors.username}</p>{/if}
                </div>
                <div class="space-y-2">
                    <Label>Contraseña *</Label>
                    <Input type="password" bind:value={userForm.password} required={!userForm.id} placeholder={userForm.id ? "Dejar en blanco para no cambiar" : ""} />
                    {#if userForm.errors.password}<p class="text-xs text-red-500">{userForm.errors.password}</p>{/if}
                </div>
                <div class="space-y-2">
                    <Label>Rol del usuario *</Label>
                    <select class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" bind:value={userForm.role}>
                        {#each roles as role}
                            <option value={role}>{role}</option>
                        {/each}
                    </select>
                    {#if userForm.errors.role}<p class="text-xs text-red-500">{userForm.errors.role}</p>{/if}
                </div>
                {#if userForm.role === 'Dentista'}
                    <div class="space-y-2">
                        <Label>Consultorio Asignado (Opcional)</Label>
                        <Input bind:value={userForm.room} placeholder="Ej: Consultorio 1" />
                    </div>
                    <div class="space-y-2">
                        <Label>Colegiatura (CMP)</Label>
                        <Input bind:value={userForm.cmp} placeholder="Ej: 12345" />
                    </div>
                {/if}
            </div>
            
            <div class="flex items-center space-x-2 pt-4 mt-2">
                <Checkbox id="is-active" bind:checked={userForm.is_active} />
                <Label for="is-active" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Usuario activo (puede iniciar sesión)
                </Label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                <Button variant="outline" type="button" onclick={() => isUserModalOpen = false}>
                    ✕ Cancelar
                </Button>
                <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={userForm.processing}>
                    {#if userForm.processing}
                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Guardando...
                    {:else}
                        <UserCog class="w-4 h-4 mr-2" /> {userForm.id ? 'Actualizar' : 'Guardar'} usuario
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>

<!-- Modal Confirmar Eliminación -->
<Dialog bind:open={isDeleteModalOpen}>
    <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
            <DialogTitle class="flex items-center gap-2 text-red-600">
                <Trash2 class="w-5 h-5" />
                Eliminar Usuario
            </DialogTitle>
            <DialogDescription class="pt-2">
                ¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer y el usuario perderá acceso al sistema inmediatamente.
            </DialogDescription>
        </DialogHeader>
        <div class="flex justify-end gap-3 pt-4 mt-2">
            <Button variant="outline" onclick={() => isDeleteModalOpen = false}>
                Cancelar
            </Button>
            <Button variant="destructive" class="bg-red-600 hover:bg-red-700 text-white" onclick={confirmDelete}>
                Sí, Eliminar
            </Button>
        </div>
    </DialogContent>
</Dialog>

<ScheduleModal bind:isOpen={isScheduleModalOpen} user={selectedUserForSchedule} />
