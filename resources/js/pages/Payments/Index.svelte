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
    import { Toast } from '@/lib/utils/toast';
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
    import SendWhatsappButton from '@/components/SendWhatsappButton.svelte';

    let { payments, patients, filters } = $props();

    let search = $state(filters?.search || '');
    let searchTimeout: ReturnType<typeof setTimeout>;

    // PDF Viewer State
    let isPdfViewerOpen = $state(false);
    let pdfViewerUrl = $state('');
    let pdfViewerTitle = $state('');

    // Payment Form Modal State
    let isPaymentModalOpen = $state(false);
    let sunatActive = $derived(page.props.sunatConfig?.active ?? false);

    const paymentForm = useForm({
        patient_id: '',
        treatment_contract_id: '',
        amount: '',
        payment_method: 'Efectivo',
        receipt_type: (page.props.sunatConfig?.active ?? false) ? 'Boleta' : 'Ticket',
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

    let patientSearchQuery = $state('');
    let showPatientDropdown = $state(false);
    let filteredPatients = $derived(
        patients.filter(p => {
            if (!patientSearchQuery) return true;
            const q = patientSearchQuery.toLowerCase();
            return (p.first_name?.toLowerCase().includes(q) || 
                    p.last_name?.toLowerCase().includes(q) || 
                    p.dni?.includes(q) ||
                    p.phone?.includes(q));
        })
    );

    let selectedPatientDetails = $derived(
        patients.find(pt => pt.id.toString() === paymentForm.patient_id) || null
    );

    $effect(() => {
        if (paymentForm.patient_id) {
            const p = selectedPatientDetails;
            if (p && !showPatientDropdown) {
                patientSearchQuery = `${p.first_name} ${p.last_name} (${p.dni})`;
            }
        } else if (!showPatientDropdown) {
            patientSearchQuery = '';
            paymentForm.treatment_contract_id = '';
        }
    });

    function onContractChange(contractId: string) {
        if (!contractId || contractId === 'none') {
            paymentForm.treatment_contract_id = '';
            return;
        }
        paymentForm.treatment_contract_id = contractId;
        const contract = selectedPatientDetails?.treatment_contracts?.find((c: any) => c.id.toString() === contractId);
        if (contract) {
            const suggestedAmount = Math.min(Number(contract.installment_amount || contract.balance_due), Number(contract.balance_due));
            paymentForm.amount = suggestedAmount > 0 ? suggestedAmount.toString() : contract.balance_due;
        }
    }

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

    async function fetchPdfBase64(paymentId: number): Promise<string> {
        const res = await fetch(`/pagos/${paymentId}/pdf-base64`);
        if (!res.ok) throw new Error('Error al obtener el PDF');
        const data = await res.json();
        return data.base64 as string;
    }

    async function printAndShow(paymentId: number) {
        // 1. Fetch PDF as base64 from server (no HTTPS issues for QZ Tray)
        let base64: string;
        try {
            base64 = await fetchPdfBase64(paymentId);
        } catch (e) {
            Toast.error('Error', 'Error al generar el PDF del comprobante.');
            return;
        }

        // 2. Show in PDF viewer using a blob URL (works in browser)
        const binaryStr = atob(base64);
        const bytes = new Uint8Array(binaryStr.length);
        for (let i = 0; i < binaryStr.length; i++) bytes[i] = binaryStr.charCodeAt(i);
        const blob = new Blob([bytes], { type: 'application/pdf' });
        pdfViewerUrl = URL.createObjectURL(blob);
        pdfViewerTitle = `Comprobante ${paymentId}`;
        isPdfViewerOpen = true;

        // 3. Auto-print via QZ Tray using base64 (bypasses SSL cert problem)
        try {
            const printers = await QZTrayService.getPrinters();
            if (printers.length === 0) {
                Toast.info('Atención', 'PDF abierto. No se encontraron impresoras para imprimir.');
                return;
            }
            const printer = printers[0];
            Toast.info('Información', 'Enviando ticket a la impresora...');
            await QZTrayService.imprimirPdfBase64(printer, base64);
            Toast.success('Éxito', 'Ticket enviado a la impresora exitosamente.');
        } catch (e) {
            console.error(e);
            Toast.info('Atención', 'PDF mostrado. QZ Tray no pudo imprimir automáticamente.');
        }
    }

    async function printTicket(payment: any) {
        await printAndShow(payment.id);
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
                Toast.success('Éxito', 'Comprobante emitido correctamente a SUNAT.', { id: 'sunat-toast' });
            },
            onError: (errors) => {
                Toast.error('Error', 'Error al emitir a SUNAT. Revisa los datos o la configuración.', { id: 'sunat-toast' });
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
                if (flash?.new_payment_id) {
                    printAndShow(flash.new_payment_id);
                }
            }
        });
    }

    function sendWhatsApp() {
        if (!waPhone) {
            Toast.error('Error', 'Debes ingresar un número de teléfono');
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
                Toast.success('Éxito', 'Ticket enviado por WhatsApp');
            },
            onError: () => {
                isSendingWa = false;
                Toast.error('Error', 'Error al enviar el ticket por WhatsApp');
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
                                        {:else if sunatActive}
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50" onclick={() => openSunatModal(payment)} title="Emitir a SUNAT">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                            </Button>
                                        {/if}
                                    {/if}
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50" onclick={() => { pdfViewerUrl = `/pagos/${payment.id}/pdf`; pdfViewerTitle = `Comprobante ${payment.id}`; isPdfViewerOpen = true; }} title="Ver Comprobante">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                    <SendWhatsappButton 
                                        phone={payment.patient?.phone} 
                                        payment_id={payment.id} 
                                        type="payment"
                                        plantilla="pago_confirmado"
                                        buttonText=""
                                        variant="ghost"
                                        class="h-8 w-8 px-0 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center"
                                    />
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
            <div class="grid grid-cols-1 gap-5">
                <div class="space-y-2 relative">
                    <Label>Paciente</Label>
                    <div class="relative">
                        <Input 
                            type="text" 
                            placeholder="Buscar por nombre, DNI o número..." 
                            bind:value={patientSearchQuery}
                            class="h-11 rounded-xl pr-8"
                            onfocus={() => showPatientDropdown = true}
                            onblur={() => setTimeout(() => showPatientDropdown = false, 200)}
                        />
                        {#if paymentForm.patient_id}
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick={() => { paymentForm.patient_id = ''; patientSearchQuery = ''; }}>
                                ×
                            </button>
                        {/if}
                    </div>
                    {#if showPatientDropdown}
                        <div class="absolute z-50 w-full mt-1 max-h-60 overflow-auto rounded-xl border bg-popover text-popover-foreground shadow-md outline-none">
                            {#if filteredPatients.length === 0}
                                <div class="p-3 text-sm text-gray-500 text-center">No se encontraron pacientes.</div>
                            {:else}
                                {#each filteredPatients as pt}
                                    <button 
                                        type="button" 
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-accent hover:text-accent-foreground flex flex-col"
                                        onclick={() => { 
                                            paymentForm.patient_id = pt.id.toString(); 
                                            patientSearchQuery = `${pt.first_name} ${pt.last_name} (${pt.dni})`;
                                            showPatientDropdown = false;
                                        }}
                                    >
                                        <span class="font-medium">{pt.first_name} {pt.last_name}</span>
                                        <span class="text-xs text-muted-foreground">DNI: {pt.dni} {pt.phone ? `• Tel: ${pt.phone}` : ''}</span>
                                    </button>
                                {/each}
                            {/if}
                        </div>
                    {/if}
                    {#if paymentForm.errors.patient_id}<p class="text-xs text-red-500">{paymentForm.errors.patient_id}</p>{/if}
                </div>

                {#if selectedPatientDetails?.treatment_contracts && selectedPatientDetails.treatment_contracts.length > 0}
                    <div class="space-y-2 bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <Label class="text-blue-800">Contratos Activos del Paciente</Label>
                        <Select 
                            type="single" 
                            bind:value={paymentForm.treatment_contract_id}
                            onValueChange={onContractChange}
                        >
                            <SelectTrigger class="h-11 rounded-xl bg-white">
                                {#if paymentForm.treatment_contract_id && paymentForm.treatment_contract_id !== 'none'}
                                    {@const c = selectedPatientDetails.treatment_contracts.find((ct: any) => ct.id.toString() === paymentForm.treatment_contract_id.toString())}
                                    {c ? `${c.treatment_name} (Deuda: S/ ${c.balance_due})` : 'Seleccionar contrato...'}
                                {:else}
                                    Pago Libre (Sin Contrato)
                                {/if}
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none" class="rounded-lg">Pago Libre (Sin Contrato)</SelectItem>
                                {#each selectedPatientDetails.treatment_contracts as contract}
                                    <SelectItem value={contract.id.toString()} class="rounded-lg">
                                        {contract.treatment_name} (Deuda: S/ {contract.balance_due})
                                    </SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-blue-600/80">Selecciona si el pago abonará a un tratamiento financiado.</p>
                    </div>
                {/if}
                
                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <Label>Monto (S/)</Label>
                        <Input type="number" step="0.01" min="0" bind:value={paymentForm.amount} placeholder="0.00" class="h-11 rounded-xl" />
                        {#if paymentForm.errors.amount}<p class="text-xs text-red-500">{paymentForm.errors.amount}</p>{/if}
                    </div>
                    <div class="space-y-2">
                        <Label>Método de Pago</Label>
                        <Select bind:value={paymentForm.payment_method} type="single">
                            <SelectTrigger class="h-11 rounded-xl">{paymentForm.payment_method}</SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="Efectivo" class="rounded-lg">Efectivo</SelectItem>
                                <SelectItem value="Tarjeta" class="rounded-lg">Tarjeta</SelectItem>
                                <SelectItem value="Transferencia" class="rounded-lg">Transferencia</SelectItem>
                                <SelectItem value="Yape/Plin" class="rounded-lg">Yape/Plin</SelectItem>
                            </SelectContent>
                        </Select>
                        {#if paymentForm.errors.payment_method}<p class="text-xs text-red-500">{paymentForm.errors.payment_method}</p>{/if}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <Label>Tipo Comprobante</Label>
                        <Select bind:value={paymentForm.receipt_type} type="single">
                            <SelectTrigger class="h-11 rounded-xl">{paymentForm.receipt_type}</SelectTrigger>
                            <SelectContent class="rounded-xl">
                                {#if sunatActive}
                                    <SelectItem value="Boleta" class="rounded-lg">Boleta</SelectItem>
                                    <SelectItem value="Factura" class="rounded-lg">Factura</SelectItem>
                                {/if}
                                <SelectItem value="Ticket" class="rounded-lg">Ticket (Interno)</SelectItem>
                            </SelectContent>
                        </Select>
                        {#if paymentForm.errors.receipt_type}<p class="text-xs text-red-500">{paymentForm.errors.receipt_type}</p>{/if}
                    </div>
                    <div class="space-y-2">
                        <Label>Estado</Label>
                        <Select bind:value={paymentForm.status} type="single">
                            <SelectTrigger class="h-11 rounded-xl">{paymentForm.status}</SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="Pagado" class="rounded-lg">Pagado</SelectItem>
                                <SelectItem value="Pendiente" class="rounded-lg">Pendiente</SelectItem>
                            </SelectContent>
                        </Select>
                        {#if paymentForm.errors.status}<p class="text-xs text-red-500">{paymentForm.errors.status}</p>{/if}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Notas (Opcional)</Label>
                    <Input type="text" bind:value={paymentForm.notes} placeholder="Concepto del pago..." class="h-11 rounded-xl" />
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
