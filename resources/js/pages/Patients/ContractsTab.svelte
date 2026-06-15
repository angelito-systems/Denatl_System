<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { useForm, router, Link } from '@inertiajs/svelte';
    import { Plus, PiggyBank, ReceiptText, CheckCircle2, Search, ExternalLink } from 'lucide-svelte';
    import { Toast } from '@/lib/utils/toast';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';

    let { patient, treatments = [] } = $props();

    let showNewContractModal = $state(false);

    const contractForm = useForm({
        patient_id: patient.id,
        treatment_id: '',
        treatment_name: '',
        total_cost: '',
        down_payment: '0',
        installments: '1',
        start_date: new Date().toISOString().split('T')[0],
    });



    function saveContract(e: Event) {
        e.preventDefault();
        contractForm.post('/treatment_contracts', {
            onSuccess: () => {
                showNewContractModal = false;
                contractForm.reset('treatment_id', 'treatment_name', 'total_cost', 'down_payment', 'installments');
                Toast.success('Éxito', 'Contrato creado y documento borrador generado');
            }
        });
    }

    let treatmentSearchQuery = $state('');
    let showTreatmentDropdown = $state(false);

    let filteredTreatments = $derived(
        treatments.filter((t: any) => t.name.toLowerCase().includes(treatmentSearchQuery.toLowerCase()))
    );

    function selectTreatment(t: any) {
        contractForm.treatment_id = t.id;
        contractForm.treatment_name = t.name;
        contractForm.total_cost = t.base_price;
        treatmentSearchQuery = t.name;
        showTreatmentDropdown = false;
    }

</script>

<Card>
    <CardHeader class="flex flex-row items-center justify-between border-b pb-4">
        <div>
            <CardTitle class="text-xl">Contratos y Financiamientos</CardTitle>
            <CardDescription>Gestiona los planes de pago y cuotas del paciente.</CardDescription>
        </div>
        <div class="flex items-center gap-3">
            <Button onclick={() => showNewContractModal = true} class="bg-blue-600 hover:bg-blue-700">
                <Plus class="w-4 h-4 mr-2" /> Nuevo Contrato
            </Button>
        </div>
    </CardHeader>
    
    <CardContent class="p-6">
        {#if !patient.treatment_contracts || patient.treatment_contracts.length === 0}
            <div class="text-center py-16 text-muted-foreground">
                <PiggyBank class="w-12 h-12 mx-auto mb-4 opacity-20" />
                <p class="text-lg font-medium text-slate-900">No hay contratos</p>
                <p>No hay contratos de tratamiento registrados para este paciente.</p>
            </div>
        {:else}
            <div class="grid gap-6">
                {#each patient.treatment_contracts as contract}
                    {@const totalPaid = contract.payments?.filter(p => p.status === 'Pagado').reduce((sum, p) => sum + Number(p.amount), 0) || 0}
                    {@const percentage = Math.min(100, Math.round((totalPaid / contract.total_cost) * 100))}
                    {@const balance = Math.max(0, contract.total_cost - totalPaid)}
                    <Card class="border shadow-sm">
                        <CardHeader class="border-b bg-slate-50/50 pb-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base flex items-center gap-2">
                                        {contract.treatment_name}
                                        {#if contract.status === 'Finalizado'}
                                            <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full border border-green-200">
                                                <CheckCircle2 class="inline w-3 h-3 mr-1" /> Finalizado
                                            </span>
                                        {:else}
                                            <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full border border-blue-200">
                                                {contract.status}
                                            </span>
                                        {/if}
                                    </CardTitle>
                                    <p class="text-sm text-muted-foreground mt-1">Iniciado el {new Date(contract.start_date).toLocaleDateString()}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-muted-foreground">Costo Total</p>
                                    <p class="text-lg font-bold">S/ {Number(contract.total_cost).toFixed(2)}</p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="p-6">
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="font-medium text-green-600">S/ {totalPaid.toFixed(2)} Pagado ({percentage}%)</span>
                                    <span class="font-medium text-red-600">S/ {balance.toFixed(2)} Pendiente</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-green-500 h-2.5 rounded-full" style="width: {percentage}%"></div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-4">
                                <Button asChild variant="outline" size="sm">
                                    <Link href={`/treatment_contracts/${contract.id}`}>
                                        Ver Detalles
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                {/each}
            </div>
        {/if}
    </CardContent>
</Card>

<Dialog bind:open={showNewContractModal}>
    <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
            <DialogTitle>Nuevo Contrato de Tratamiento</DialogTitle>
        </DialogHeader>
        <form onsubmit={saveContract} class="space-y-4 py-4">
            <div class="space-y-2 relative">
                <Label>Tratamiento a Financiar</Label>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input 
                        type="text" 
                        placeholder="Buscar tratamiento..." 
                        class="pl-9"
                        bind:value={treatmentSearchQuery}
                        onfocus={() => showTreatmentDropdown = true}
                        onblur={() => setTimeout(() => showTreatmentDropdown = false, 200)}
                    />
                </div>
                {#if showTreatmentDropdown && filteredTreatments.length > 0}
                    <div class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-auto">
                        {#each filteredTreatments as t}
                            <button 
                                type="button"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-100 focus:bg-slate-100"
                                onclick={() => selectTreatment(t)}
                            >
                                {t.name} (S/ {t.base_price})
                            </button>
                        {/each}
                    </div>
                {/if}
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>Costo Total (S/)</Label>
                    <Input type="number" step="0.01" bind:value={contractForm.total_cost} required />
                </div>
                <div class="space-y-2">
                    <Label>Fecha Inicio</Label>
                    <Input type="date" bind:value={contractForm.start_date} required />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>Cuota Inicial (Opcional)</Label>
                    <Input type="number" step="0.01" bind:value={contractForm.down_payment} />
                </div>
                <div class="space-y-2">
                    <Label>N° Cuotas Programadas</Label>
                    <Input type="number" bind:value={contractForm.installments} />
                </div>
            </div>
            <div class="flex justify-end pt-4">
                <Button type="submit" disabled={contractForm.processing}>
                    Guardar Contrato
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
