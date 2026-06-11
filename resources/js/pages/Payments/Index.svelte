<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Facturación', href: '#facturacion' },
            { title: 'Registro de Pagos', href: '#pagos' },
        ],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Plus, Search, FileText, Download, MessageCircle, Loader2 } from 'lucide-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
    import { toast } from 'svelte-sonner';
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow
    } from '@/components/ui/table';
    import { router, useForm } from '@inertiajs/svelte';
    import { index } from '@/routes/payments';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { QZTrayService, type TicketData } from '@/lib/utils/qztray';

    let { payments, patients, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;

    // Payment Form Modal State
    let isPaymentModalOpen = $state(false);
    const paymentForm = useForm({
        patient_id: '',
        amount: '',
        payment_method: 'Efectivo',
        receipt_type: 'Boleta',
        status: 'Pagado',
        notes: ''
    });

    // WhatsApp Modal State
    let isWhatsAppModalOpen = $state(false);
    let selectedPaymentForWa = $state<any>(null);
    let waPhone = $state('');
    let isSendingWa = $state(false);

    function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(index(), { search }, { preserveState: true, replace: true });
        }, 300);
    }

    async function printTicket(payment: any) {
        try {
            const printers = await QZTrayService.getPrinters();
            if (printers.length === 0) {
                alert('No se encontraron impresoras instaladas.');
                return;
            }
            // Use the first printer for testing or prompt the user
            const printer = printers[0];
            
            const ticketData: TicketData = {
                clinicaNombre: 'Clínica Dental System',
                clinicaRuc: '20123456789',
                clinicaTel: '01-234-5678',
                fecha: new Date().toLocaleString(),
                pacienteNombre: payment.patient ? `${payment.patient.first_name} ${payment.patient.last_name}` : 'Desconocido',
                pacienteDni: payment.patient?.dni || '---',
                items: [
                    { nombre: 'Tratamiento Dental', precio: Number(payment.amount) }
                ],
                total: Number(payment.amount),
                metodoPago: payment.payment_method,
                mensajePie: 'Gracias por su preferencia!'
            };

            await QZTrayService.imprimirTicket(printer, ticketData);
            alert('Ticket enviado a la impresora exitosamente.');
        } catch (e) {
            console.error(e);
            alert('Error al imprimir el ticket. Asegúrese de que QZ Tray esté corriendo.');
        }
    }

    function openWhatsAppModal(payment: any) {
        selectedPaymentForWa = payment;
        if (payment.patient?.phone) {
            waPhone = payment.patient.phone;
            sendWhatsApp(); // Enviar directo
        } else {
            waPhone = '';
            isWhatsAppModalOpen = true;
        }
    }

    function submitPayment(e: Event) {
        e.preventDefault();
        paymentForm.post('/payments', {
            preserveScroll: true,
            onSuccess: () => {
                isPaymentModalOpen = false;
                paymentForm.reset();
            }
        });
    }

    function sendWhatsApp() {
        if (!waPhone) {
            toast.error('Debes ingresar un número de teléfono');
            return;
        }
        isSendingWa = true;
        router.post('/whatsapp/send-document', {
            phone: waPhone,
            payment_id: selectedPaymentForWa.id,
            caption: `Hola ${selectedPaymentForWa.patient?.first_name || 'Paciente'}, te enviamos tu comprobante de pago por S/ ${selectedPaymentForWa.amount}.`
        }, {
            preserveScroll: true,
            onSuccess: () => {
                isSendingWa = false;
                isWhatsAppModalOpen = false;
                toast.success('Ticket enviado por WhatsApp');
            },
            onError: () => {
                isSendingWa = false;
                toast.error('Error al enviar el ticket por WhatsApp');
            }
        });
    }
</script>

<AppHead title="Registro de Pagos" />

<div class="flex flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold tracking-tight">Registro de Pagos</h1>
        <div class="flex gap-2">
            <Button variant="outline" class="border-blue-200 text-blue-700 hover:bg-blue-50">
                <Download class="h-4 w-4 mr-2" />
                Exportar
            </Button>
            <Button class="bg-blue-600 hover:bg-blue-700" onclick={() => isPaymentModalOpen = true}>
                <Plus class="h-4 w-4 mr-2" />
                Registrar Pago
            </Button>
        </div>
    </div>

    <div class="flex items-center gap-4 bg-card p-4 rounded-lg shadow-sm border">
        <div class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input 
                type="text" 
                placeholder="Buscar por paciente o nota..." 
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
                    <TableHead>Fecha</TableHead>
                    <TableHead>Paciente</TableHead>
                    <TableHead>Método</TableHead>
                    <TableHead>Comprobante</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead class="text-right">Monto</TableHead>
                    <TableHead class="text-right">Acciones</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {#if payments.data.length === 0}
                    <TableRow>
                        <TableCell colspan="7" class="text-center h-24 text-muted-foreground">
                            No se encontraron pagos.
                        </TableCell>
                    </TableRow>
                {:else}
                    {#each payments.data as payment}
                        <TableRow>
                            <TableCell>{new Date(payment.created_at).toLocaleDateString()}</TableCell>
                            <TableCell class="font-medium">
                                {#if payment.patient}
                                    {payment.patient.first_name} {payment.patient.last_name}
                                {:else}
                                    Desconocido
                                {/if}
                            </TableCell>
                            <TableCell>{payment.payment_method}</TableCell>
                            <TableCell>{payment.receipt_type}</TableCell>
                            <TableCell>
                                <span class="px-2 py-1 text-xs rounded-full {payment.status === 'Pagado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                    {payment.status}
                                </span>
                            </TableCell>
                            <TableCell class="text-right font-bold">S/ {Number(payment.amount).toFixed(2)}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50" onclick={() => openWhatsAppModal(payment)} title="Enviar por WhatsApp">
                                        <MessageCircle class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500" onclick={() => printTicket(payment)} title="Imprimir Ticket QZ Tray">
                                        <FileText class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    {/each}
                {/if}
            </TableBody>
        </Table>
    </div>
</div>

<!-- Modal para enviar ticket por WhatsApp -->
<Dialog bind:open={isWhatsAppModalOpen}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Enviar Comprobante por WhatsApp</DialogTitle>
            <DialogDescription>
                Se generará y enviará el ticket de pago en PDF a <strong>{selectedPaymentForWa?.patient?.first_name || 'Paciente'}</strong>.
            </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 pt-4">
            <div class="space-y-2">
                <Label>Número de WhatsApp</Label>
                <Input type="text" bind:value={waPhone} placeholder="Ej: +51 987654321" />
                <p class="text-xs text-muted-foreground">Verifica que el número tenga el formato correcto con el código de país si es necesario.</p>
            </div>
            <Button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white" onclick={sendWhatsApp} disabled={isSendingWa}>
                {#if isSendingWa}
                    <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Enviando...
                {:else}
                    <MessageCircle class="w-4 h-4 mr-2" /> Enviar PDF
                {/if}
            </Button>
        </div>
    </DialogContent>
</Dialog>

<!-- Modal Registrar Pago -->
<Dialog bind:open={isPaymentModalOpen}>
    <DialogContent class="sm:max-w-lg">
        <DialogHeader>
            <DialogTitle>Registrar Nuevo Pago</DialogTitle>
            <DialogDescription>
                Ingresa los detalles del pago.
            </DialogDescription>
        </DialogHeader>
        <form onsubmit={submitPayment} class="space-y-4 pt-4">
            <div class="grid grid-cols-1 gap-4">
                <div class="space-y-2">
                    <Label>Paciente</Label>
                    <Select bind:value={paymentForm.patient_id} type="single">
                        <SelectTrigger>
                            {paymentForm.patient_id ? patients.find(p => p.id == paymentForm.patient_id)?.first_name + ' ' + patients.find(p => p.id == paymentForm.patient_id)?.last_name : 'Selecciona un paciente...'}
                        </SelectTrigger>
                        <SelectContent class="max-h-64">
                            {#each patients as pt}
                                <SelectItem value={pt.id.toString()}>{pt.first_name} {pt.last_name}</SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>
                    {#if paymentForm.errors.patient_id}<p class="text-xs text-red-500">{paymentForm.errors.patient_id}</p>{/if}
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Monto (S/)</Label>
                        <Input type="number" step="0.01" min="0" bind:value={paymentForm.amount} placeholder="0.00" />
                        {#if paymentForm.errors.amount}<p class="text-xs text-red-500">{paymentForm.errors.amount}</p>{/if}
                    </div>
                    <div class="space-y-2">
                        <Label>Método de Pago</Label>
                        <Select bind:value={paymentForm.payment_method} type="single">
                            <SelectTrigger>{paymentForm.payment_method}</SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Efectivo">Efectivo</SelectItem>
                                <SelectItem value="Tarjeta">Tarjeta</SelectItem>
                                <SelectItem value="Transferencia">Transferencia</SelectItem>
                                <SelectItem value="Yape/Plin">Yape/Plin</SelectItem>
                            </SelectContent>
                        </Select>
                        {#if paymentForm.errors.payment_method}<p class="text-xs text-red-500">{paymentForm.errors.payment_method}</p>{/if}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Tipo Comprobante</Label>
                        <Select bind:value={paymentForm.receipt_type} type="single">
                            <SelectTrigger>{paymentForm.receipt_type}</SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Boleta">Boleta</SelectItem>
                                <SelectItem value="Factura">Factura</SelectItem>
                                <SelectItem value="Ticket">Ticket (Interno)</SelectItem>
                            </SelectContent>
                        </Select>
                        {#if paymentForm.errors.receipt_type}<p class="text-xs text-red-500">{paymentForm.errors.receipt_type}</p>{/if}
                    </div>
                    <div class="space-y-2">
                        <Label>Estado</Label>
                        <Select bind:value={paymentForm.status} type="single">
                            <SelectTrigger>{paymentForm.status}</SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Pagado">Pagado</SelectItem>
                                <SelectItem value="Pendiente">Pendiente</SelectItem>
                            </SelectContent>
                        </Select>
                        {#if paymentForm.errors.status}<p class="text-xs text-red-500">{paymentForm.errors.status}</p>{/if}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Notas (Opcional)</Label>
                    <Input type="text" bind:value={paymentForm.notes} placeholder="Concepto del pago..." />
                    {#if paymentForm.errors.notes}<p class="text-xs text-red-500">{paymentForm.errors.notes}</p>{/if}
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-2">
                <Button variant="outline" type="button" onclick={() => isPaymentModalOpen = false}>Cancelar</Button>
                <Button type="submit" class="bg-blue-600 hover:bg-blue-700" disabled={paymentForm.processing}>
                    {#if paymentForm.processing}
                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Guardando...
                    {:else}
                        Guardar Pago
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
