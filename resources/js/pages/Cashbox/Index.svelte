<script module lang="ts">
    import AppLayout from '@/layouts/AppLayout.svelte';
    export const layout = AppLayout;
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
    import { useForm, router } from '@inertiajs/svelte';
    import { Wallet, ArrowUpRight, ArrowDownRight, Archive, CheckCircle2, AlertCircle } from 'lucide-svelte';

    let { currentCashbox, transactions = [], totals = { income: 0, expense: 0 }, recentCashboxes = [] } = $props();

    let isOpeningModal = $state(false);
    let isClosingModal = $state(false);
    let isTransactionModal = $state(false);

    const openForm = useForm({
        opening_amount: '0.00',
        notes: ''
    });

    const closeForm = useForm({});

    const transactionForm = useForm({
        type: 'income',
        amount: '',
        payment_method: 'Efectivo',
        description: ''
    });

    function handleOpen() {
        openForm.post('/cashbox/open', {
            onSuccess: () => { isOpeningModal = false; openForm.reset(); }
        });
    }

    function handleClose() {
        closeForm.post(`/cashbox/${currentCashbox.id}/close`, {
            onSuccess: () => { isClosingModal = false; }
        });
    }

    function handleTransaction() {
        transactionForm.post(`/cashbox/${currentCashbox.id}/transactions`, {
            onSuccess: () => { isTransactionModal = false; transactionForm.reset(); }
        });
    }

    let expectedAmount = $derived(
        currentCashbox ? (Number(currentCashbox.opening_amount) + Number(totals.income) - Number(totals.expense)).toFixed(2) : '0.00'
    );
</script>

<AppHead title="Gestión de Caja" />

<div class="p-8 max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Módulo de Caja</h1>
            <p class="text-slate-500 mt-1">Apertura, cierre y movimientos financieros diarios.</p>
        </div>
        <div>
            {#if !currentCashbox}
                <Button onclick={() => isOpeningModal = true} class="bg-blue-600 hover:bg-blue-700">
                    <Wallet class="w-4 h-4 mr-2" />
                    Abrir Caja
                </Button>
            {:else}
                <div class="flex gap-2">
                    <Button variant="outline" onclick={() => isTransactionModal = true}>
                        Añadir Movimiento
                    </Button>
                    <Button variant="destructive" onclick={() => isClosingModal = true}>
                        <Archive class="w-4 h-4 mr-2" />
                        Cerrar Caja
                    </Button>
                </div>
            {/if}
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card class={!currentCashbox ? 'opacity-50' : ''}>
            <CardContent class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Monto Inicial</p>
                        <p class="text-2xl font-bold text-slate-900">S/ {currentCashbox ? currentCashbox.opening_amount : '0.00'}</p>
                    </div>
                    <div class="p-3 bg-slate-100 rounded-full text-slate-600">
                        <Wallet class="w-5 h-5" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class={!currentCashbox ? 'opacity-50' : ''}>
            <CardContent class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Ingresos Totales</p>
                        <p class="text-2xl font-bold text-green-600">S/ {totals.income.toFixed(2)}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full text-green-600">
                        <ArrowUpRight class="w-5 h-5" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class={!currentCashbox ? 'opacity-50' : ''}>
            <CardContent class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Egresos Totales</p>
                        <p class="text-2xl font-bold text-red-600">S/ {totals.expense.toFixed(2)}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full text-red-600">
                        <ArrowDownRight class="w-5 h-5" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class={!currentCashbox ? 'border-dashed' : 'border-blue-200 shadow-blue-100 shadow-md'}>
            <CardContent class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Saldo Esperado</p>
                        <p class="text-2xl font-bold text-blue-700">S/ {expectedAmount}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                        <CheckCircle2 class="w-5 h-5" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Transactions Table -->
    {#if currentCashbox}
        <Card>
            <CardHeader>
                <CardTitle>Movimientos de Caja (Turno Actual)</CardTitle>
                <CardDescription>Aperturada en: {new Date(currentCashbox.opened_at).toLocaleString()}</CardDescription>
            </CardHeader>
            <CardContent>
                {#if transactions.length === 0}
                    <div class="text-center p-8 text-slate-500">
                        <AlertCircle class="w-12 h-12 mx-auto mb-4 opacity-20" />
                        <p>No hay movimientos registrados en este turno.</p>
                    </div>
                {:else}
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha/Hora</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Método de Pago</TableHead>
                                <TableHead>Usuario</TableHead>
                                <TableHead class="text-right">Monto</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {#each transactions as trx}
                                <TableRow>
                                    <TableCell>{new Date(trx.created_at).toLocaleTimeString()}</TableCell>
                                    <TableCell>
                                        {#if trx.type === 'income'}
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Ingreso</span>
                                        {:else}
                                            <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-medium">Egreso</span>
                                        {/if}
                                    </TableCell>
                                    <TableCell class="font-medium">{trx.description}</TableCell>
                                    <TableCell>{trx.payment_method || '-'}</TableCell>
                                    <TableCell>{trx.user?.first_name} {trx.user?.last_name}</TableCell>
                                    <TableCell class="text-right font-bold {trx.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                                        {trx.type === 'income' ? '+' : '-'} S/ {trx.amount}
                                    </TableCell>
                                </TableRow>
                            {/each}
                        </TableBody>
                    </Table>
                {/if}
            </CardContent>
        </Card>
    {:else}
        <div class="text-center p-12 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
            <Wallet class="w-16 h-16 text-slate-300 mx-auto mb-4" />
            <h2 class="text-xl font-bold text-slate-700">La caja está cerrada</h2>
            <p class="text-slate-500 mt-2 max-w-md mx-auto">Para poder registrar ingresos y egresos de pacientes, es necesario aperturar la caja del día con un monto inicial base.</p>
            <Button onclick={() => isOpeningModal = true} class="mt-6">Aperturar Caja Ahora</Button>
        </div>
    {/if}

    <!-- Recent Cashboxes -->
    {#if recentCashboxes.length > 0}
        <Card>
            <CardHeader>
                <CardTitle>Historial de Cierres Recientes</CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Apertura</TableHead>
                            <TableHead>Cierre</TableHead>
                            <TableHead>Abierto Por</TableHead>
                            <TableHead>Cerrado Por</TableHead>
                            <TableHead class="text-right">Monto Declarado</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {#each recentCashboxes as c}
                            <TableRow>
                                <TableCell>{new Date(c.opened_at).toLocaleString()}</TableCell>
                                <TableCell>{new Date(c.closed_at).toLocaleString()}</TableCell>
                                <TableCell>{c.opened_by?.first_name}</TableCell>
                                <TableCell>{c.closed_by?.first_name}</TableCell>
                                <TableCell class="text-right font-bold text-blue-600">S/ {c.closing_amount}</TableCell>
                            </TableRow>
                        {/each}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    {/if}
</div>

<!-- Open Modal -->
<Dialog bind:open={isOpeningModal}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Aperturar Caja</DialogTitle>
            <DialogDescription>Ingrese el monto inicial con el que comienza el día operativo.</DialogDescription>
        </DialogHeader>
        <form onsubmit={handleOpen} class="space-y-4">
            <div>
                <Label>Monto Inicial (S/)</Label>
                <Input type="number" step="0.01" bind:value={openForm.opening_amount} required />
            </div>
            <div>
                <Label>Notas (Opcional)</Label>
                <Input bind:value={openForm.notes} placeholder="Billetes o monedas específicas, observaciones..." />
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" type="button" onclick={() => isOpeningModal = false}>Cancelar</Button>
                <Button type="submit" disabled={openForm.processing}>Aperturar Caja</Button>
            </div>
        </form>
    </DialogContent>
</Dialog>

<!-- Close Modal -->
<Dialog bind:open={isClosingModal}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Cerrar Caja Actual</DialogTitle>
            <DialogDescription>Esta acción cerrará el turno. Todo nuevo ingreso requerirá aperturar una caja nueva.</DialogDescription>
        </DialogHeader>
        <form onsubmit={handleClose} class="space-y-4">
            <div class="bg-blue-50 p-4 rounded-xl text-center">
                <p class="text-sm text-blue-600 font-medium mb-1">Monto Físico Esperado en Caja</p>
                <p class="text-4xl font-bold text-blue-900">S/ {expectedAmount}</p>
            </div>
            <p class="text-sm text-slate-500 text-center">Al cerrar la caja declaras que tienes esta cantidad física/virtual tras contabilizar los ingresos del día.</p>
            <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" type="button" onclick={() => isClosingModal = false}>Cancelar</Button>
                <Button variant="destructive" type="submit" disabled={closeForm.processing}>Confirmar y Cerrar Caja</Button>
            </div>
        </form>
    </DialogContent>
</Dialog>

<!-- Transaction Modal -->
<Dialog bind:open={isTransactionModal}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Añadir Movimiento Manual</DialogTitle>
        </DialogHeader>
        <form onsubmit={handleTransaction} class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <Label>Tipo de Movimiento</Label>
                    <select bind:value={transactionForm.type} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="income">Ingreso Manual</option>
                        <option value="expense">Egreso (Gasto)</option>
                    </select>
                </div>
                <div>
                    <Label>Método</Label>
                    <select bind:value={transactionForm.payment_method} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Yape/Plin">Yape / Plin</option>
                    </select>
                </div>
            </div>
            <div>
                <Label>Monto (S/)</Label>
                <Input type="number" step="0.01" bind:value={transactionForm.amount} required />
            </div>
            <div>
                <Label>Descripción o Motivo</Label>
                <Input bind:value={transactionForm.description} required placeholder="Pago de luz, Compra de insumos, etc..." />
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" type="button" onclick={() => isTransactionModal = false}>Cancelar</Button>
                <Button type="submit" disabled={transactionForm.processing}>Guardar Movimiento</Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
