<script module lang="ts">
    import { index as patientsIndex } from '@/routes/patients';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pacientes',
                href: patientsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import { Plus, Search, UserRound, Trash2 } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from '@/components/ui/table';
    import PatientFormModal from '@/components/PatientFormModal.svelte';

    let { patients, filters } = $props();

    let searchQuery = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;
    let selectedPatientId = $state<number | null>(null);
    let selectedPatient = $derived(patients.data.find(p => p.id === selectedPatientId));

    let isModalOpen = $state(false);
    let modalPatient = $state(null);

    function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(
                patientsIndex(),
                { search: searchQuery },
                { preserveState: true, replace: true },
            );
        }, 300);
    }

    function handleRowClick(id: number) {
        selectedPatientId = id;
    }

    function handleRowDoubleClick(id: number) {
        goToPatient(id);
    }

    function goToPatient(id: number) {
        router.get(`/patients/${id}`);
    }

    function deletePatient() {
        if (
            selectedPatientId &&
            confirm('¿Estás seguro de eliminar este paciente?')
        ) {
            router.delete(`/patients/${selectedPatientId}`);
            selectedPatientId = null;
        }
    }

    function openCreateModal() {
        modalPatient = null;
        isModalOpen = true;
    }

    function openEditModal() {
        if (selectedPatient) {
            modalPatient = selectedPatient;
            isModalOpen = true;
        }
    }
</script>

<AppHead title="Directorio de Pacientes" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Directorio de Pacientes
            </h1>
            <p class="text-muted-foreground mt-1">
                Administra el registro general de tu clínica.
            </p>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700" onclick={openCreateModal}>
            <Plus class="mr-2 h-4 w-4" />
            Nuevo Paciente
        </Button>
    </div>

    <div
        class="flex flex-col sm:flex-row sm:items-center gap-4 bg-card p-4 rounded-lg shadow-sm border justify-between"
    >
        <div class="relative flex-1 max-w-md">
            <Search
                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"
            />
            <Input
                type="text"
                placeholder="Buscar por nombre, DNI o teléfono..."
                class="pl-9"
                bind:value={searchQuery}
                oninput={handleSearch}
            />
        </div>
        <div class="flex items-center gap-3">
            <Button
                variant="outline"
                disabled={!selectedPatientId}
                onclick={() =>
                    selectedPatientId && goToPatient(selectedPatientId)}
            >
                <UserRound class="mr-2 h-4 w-4" />
                Perfil 360
            </Button>

            <Button
                variant="outline"
                disabled={!selectedPatientId}
                onclick={openEditModal}
            >
                <i class="fa-light fa-pen mr-2"></i>
                Editar
            </Button>

            <Button
                variant="destructive"
                disabled={!selectedPatientId}
                onclick={deletePatient}
                class="bg-red-500 hover:bg-red-600"
            >
                <Trash2 class="mr-2 h-4 w-4" />
                Eliminar
            </Button>
        </div>
    </div>

    <div
        class="bg-card rounded-lg shadow-sm border overflow-hidden flex flex-col flex-1"
    >
        <div class="relative w-full overflow-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">#</TableHead>
                        <TableHead>Documento</TableHead>
                        <TableHead>Paciente</TableHead>
                        <TableHead>Teléfono</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead class="text-right">Acción</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {#if patients.data.length === 0}
                        <TableRow>
                            <TableCell
                                colspan="6"
                                class="text-center h-24 text-muted-foreground"
                            >
                                No se encontraron pacientes.
                            </TableCell>
                        </TableRow>
                    {:else}
                        {#each patients.data as patient, index}
                            <TableRow
                                class="cursor-pointer transition-colors hover:bg-muted/50 {selectedPatientId ===
                                patient.id
                                    ? 'bg-blue-50/50'
                                    : ''}"
                                onclick={() => handleRowClick(patient.id)}
                                ondblclick={() =>
                                    handleRowDoubleClick(patient.id)}
                            >
                                <TableCell
                                    class="font-medium text-muted-foreground"
                                >
                                    {index + 1}
                                </TableCell>
                                <TableCell>
                                    DNI: {patient.dni}
                                </TableCell>
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-8 w-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold"
                                        >
                                            {patient.first_name.charAt(
                                                0,
                                            )}{patient.last_name.charAt(0)}
                                        </div>
                                        {patient.first_name}
                                        {patient.last_name}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {patient.phone || '-'}
                                </TableCell>
                                <TableCell>
                                    {patient.email || '-'}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="dental-outline"
                                        size="sm"
                                        class="text-blue-600 px-3 py-2 flex items-center justify-center gap-2 font-medium cursor-pointer hover:bg-blue-50/50"
                                        onclick={(e) => {
                                            e.stopPropagation();
                                            goToPatient(patient.id);
                                        }}
                                    >
                                        <i class="fa-light fa-eye text-lg"></i>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        {/each}
                    {/if}
                </TableBody>
            </Table>
        </div>

        <div
            class="p-4 border-t flex flex-col sm:flex-row items-center justify-between text-sm text-muted-foreground gap-4"
        >
            <div>
                Total en directorio: {patients.total} paciente(s)
            </div>

            <div class="flex items-center gap-2">
                <span
                    class="text-xs tracking-wider opacity-70 flex items-center gap-1"
                >
                    <span
                        class="inline-block px-1 py-0.5 border rounded bg-muted"
                        >ⓘ</span
                    >
                    DOBLE CLIC EN UNA FILA PARA VER EL HISTORIAL
                </span>
            </div>

            <div class="flex gap-2">
                {#each patients.links as link}
                    <Button
                        variant={link.active ? 'default' : 'outline'}
                        size="sm"
                        disabled={!link.url}
                        onclick={() => link.url && router.get(link.url)}
                    >
                        {@html link.label}
                    </Button>
                {/each}
            </div>
        </div>
    </div>
</div>

<PatientFormModal bind:isOpen={isModalOpen} patient={modalPatient} />
