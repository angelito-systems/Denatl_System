<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Facturación', href: '#facturacion' },
            { title: 'Registro de Pagos', href: '#pagos' },
        ],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import { Plus, Search, FileText, Download, MessageCircle, Loader2, Eye } from 'lucide-svelte';
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
    import PdfViewerModal from '@/components/PdfViewerModal.svelte';

    let { payments, patients, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;

    // PDF Viewer State
    let isPdfViewerOpen = $state(false);
    let pdfViewerUrl = $state('');
    let pdfViewerTitle = $state('');

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

    // SUNAT Modal State
    let isSunatModalOpen = $state(false);
    let selectedPaymentForSunat = $state<any>(null);
    const sunatForm = useForm({
        billing_document: '',
        billing_name: ''
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
                toast.error('No se encontraron impresoras instaladas.');
                return;
            }
            // If the user has a selected printer in configs, we should ideally use that.
            // But we don't have access to global configs here. Let's try to get it from QZ Tray list,
            // or we'll just prompt/use default. Assuming the default is what they want, or we fetch config.
            // For now, we'll just use the first printer, or the user can select it later.
            // Wait, actually, let's just ask the backend for the configured printer or pass it via props.
            // Since we don't have it as prop right now, we will use printers[0]
            const printer = printers[0]; // TODO: use configured printer
            
            const pdfUrl = `${window.location.origin}/pagos/${payment.id}/pdf`;
            
            toast.info('Enviando ticket a la impresora...');
            await QZTrayService.imprimirPdf(printer, pdfUrl);
            toast.success('Ticket enviado a la impresora exitosamente.');
        } catch (e) {
            console.error(e);
            toast.error('Error al imprimir el ticket. Asegúrese de que QZ Tray esté corriendo.');
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

    function openSunatModal(payment: any) {
        selectedPaymentForSunat = payment;
        sunatForm.billing_document = payment.billing_document || (payment.patient?.dni || '');
        sunatForm.billing_name = payment.billing_name || (payment.patient ? `${payment.patient.first_name} ${payment.patient.last_name}` : '');
        isSunatModalOpen = true;
    }

    function emitirSunat(e: Event) {
        e.preventDefault();
        toast.loading('Generando comprobante electrónico y enviando a SUNAT...', { id: 'sunat-toast' });
        sunatForm.post(`/payments/${selectedPaymentForSunat.id}/sunat/emitir`, {
            preserveScroll: true,
            onSuccess: () => {
                isSunatModalOpen = false;
                toast.success('Comprobante emitido correctamente a SUNAT.', { id: 'sunat-toast' });
            },
            onError: (errors) => {
                toast.error('Error al emitir a SUNAT. Revisa los datos o la configuración.', { id: 'sunat-toast' });
            }
        });
    }

    function submitPayment(e: Event) {
        e.preventDefault();
        paymentForm.post('/payments', {
            preserveScroll: true,
            onSuccess: (pageObj) => {
                isPaymentModalOpen = false;
                paymentForm.reset();
                
                const flash = pageObj.props.flash as any;
                if (flash?.auto_print) {
                    const paymentId = flash?.new_payment_id;
                    const paymentObj = payments.data.find((p: any) => p.id === paymentId) || {id: paymentId};
                    
                    // Show PDF Modal
                    pdfViewerUrl = `/pagos/${paymentId}/pdf`;
                    pdfViewerTitle = `Comprobante ${paymentId}`;
                    isPdfViewerOpen = true;

                    // Automatically trigger print
                    printTicket(paymentObj);
                }
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
                            <TableCell>
                                <div>{payment.receipt_type}</div>
                                {#if payment.sunat_status}
                                    <div class="text-[10px] mt-1 font-semibold {payment.sunat_status === 'Aceptado' ? 'text-green-600' : 'text-red-600'}" title={payment.sunat_message}>
                                        {payment.sunat_status}
                                        {#if payment.sunat_serie} ({payment.sunat_serie}-{payment.sunat_correlativo}) {/if}
                                    </div>
                                {/if}
                            </TableCell>
                            <TableCell>
                                <span class="px-2 py-1 text-xs rounded-full {payment.status === 'Pagado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                    {payment.status}
                                </span>
                            </TableCell>
                            <TableCell class="text-right font-bold">S/ {Number(payment.amount).toFixed(2)}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {#if ['Boleta', 'Factura'].includes(payment.receipt_type)}
                                        {#if payment.sunat_status === 'Aceptado'}
                                            <a href={`/payments/${payment.id}/sunat/download/xml`} target="_blank" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50" title="Descargar XML">
                                                <FileText class="h-4 w-4" />
                                            </a>
                                            <a href={`/payments/${payment.id}/sunat/download/cdr`} target="_blank" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50" title="Descargar CDR">
                                                <Download class="h-4 w-4" />
                                            </a>
                                        {:else}
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50" onclick={() => openSunatModal(payment)} title="Emitir a SUNAT">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                            </Button>
                                        {/if}
                                    {/if}
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50" onclick={() => { pdfViewerUrl = `/pagos/${payment.id}/pdf`; pdfViewerTitle = `Comprobante ${payment.id}`; isPdfViewerOpen = true; }} title="Ver Comprobante">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50" onclick={() => openWhatsAppModal(payment)} title="Enviar por WhatsApp">
                                        <MessageCircle class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-slate-700 hover:bg-slate-100" onclick={() => printTicket(payment)} title="Imprimir Ticket QZ Tray">
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

<!-- Modal Emitir a SUNAT -->
<Dialog bind:open={isSunatModalOpen}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Emitir Comprobante a SUNAT</DialogTitle>
            <DialogDescription>
                Verifica los datos del cliente antes de emitir la {selectedPaymentForSunat?.receipt_type} por S/ {Number(selectedPaymentForSunat?.amount).toFixed(2)}.
            </DialogDescription>
        </DialogHeader>
        <form onsubmit={emitirSunat} class="space-y-4 pt-4">
            <div class="space-y-2">
                <Label>{selectedPaymentForSunat?.receipt_type === 'Factura' ? 'RUC del Cliente (Requerido para Factura)' : 'DNI/Documento del Cliente'}</Label>
                <Input type="text" bind:value={sunatForm.billing_document} placeholder={selectedPaymentForSunat?.receipt_type === 'Factura' ? 'Ej: 20000000000' : 'Ej: 70000000'} required={selectedPaymentForSunat?.receipt_type === 'Factura'} />
                {#if sunatForm.errors.billing_document}<p class="text-xs text-red-500">{sunatForm.errors.billing_document}</p>{/if}
            </div>
            <div class="space-y-2">
                <Label>Razón Social / Nombre</Label>
                <Input type="text" bind:value={sunatForm.billing_name} placeholder="Nombre completo o Razón Social" required={selectedPaymentForSunat?.receipt_type === 'Factura'} />
                {#if sunatForm.errors.billing_name}<p class="text-xs text-red-500">{sunatForm.errors.billing_name}</p>{/if}
            </div>
            
            <div class="pt-4 flex justify-end gap-2">
                <Button variant="outline" type="button" onclick={() => isSunatModalOpen = false}>Cancelar</Button>
                <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white" disabled={sunatForm.processing}>
                    {#if sunatForm.processing}
                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Procesando...
                    {:else}
                        Emitir Comprobante
                    {/if}
                </Button>
            </div>
        </form>
    </DialogContent>
</Dialog>

<!-- Modal Visor de PDF Reutilizable -->
<PdfViewerModal 
    bind:isOpen={isPdfViewerOpen} 
    url={pdfViewerUrl} 
    title={pdfViewerTitle} 
/>
