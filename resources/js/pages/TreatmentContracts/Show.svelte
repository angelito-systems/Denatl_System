<script module lang="ts">
    import { index as patientsIndex } from '@/routes/patients';

    export const layout = {
        breadcrumbs: [
            { title: 'Pacientes', href: patientsIndex() },
            { title: 'Contrato de Tratamiento', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { useForm, router, Link } from '@inertiajs/svelte';
    import { ReceiptText, CheckCircle2, PenTool, FileText, MessageCircle, Trash2, ArrowLeft, UserRound, Wallet, TrendingUp, PiggyBank } from 'lucide-svelte';
    import SignaturePadModal from '@/components/SignaturePadModal.svelte';
    import SendWhatsappButton from '@/components/SendWhatsappButton.svelte';
    import PdfViewerModal from '@/components/PdfViewerModal.svelte';
    import { toast } from 'svelte-sonner';

    let { contract } = $props();

    // Derived states
    let patient = $derived(contract.patient);
    let totalPaid = $derived(contract.payments?.filter(p => p.status === 'Pagado').reduce((sum, p) => sum + Number(p.amount), 0) || 0);
    let percentage = $derived(Math.min(100, Math.round((totalPaid / contract.total_cost) * 100)));
    let balance = $derived(Math.max(0, contract.total_cost - totalPaid));

    let showNewPaymentModal = $state(false);

    // Signature State
    let isSignatureModalOpen = $state(false);
    let documentToSign = $state<any>(null);

    // PDF Viewer State
    let isPdfViewerOpen = $state(false);
    let pdfViewerUrl = $state('');
    let pdfViewerTitle = $state('');

    const paymentForm = useForm({
        patient_id: contract.patient_id,
        treatment_contract_id: contract.id,
        amount: '',
        payment_method: 'Transferencia',
        receipt_type: 'Boleta',
        status: 'Pagado',
        notes: '',
    });

    function openPaymentModal() {
        paymentForm.amount = balance.toString(); // Sugerir el saldo si es menor a cuota, sino la cuota
        if (contract.installment_amount && contract.installment_amount < balance) {
            paymentForm.amount = contract.installment_amount.toString();
        }
        showNewPaymentModal = true;
    }

    function savePayment(e: Event) {
        e.preventDefault();
        paymentForm.post('/payments', {
            preserveScroll: true,
            onSuccess: () => {
                showNewPaymentModal = false;
                paymentForm.reset('amount', 'notes');
                toast.success('Pago registrado');
            }
        });
    }

    function openSignModal(doc: any) {
        documentToSign = doc;
        isSignatureModalOpen = true;
    }

    function handleSign(signatureClient: string, signatureAdmin: string) {
        if (!documentToSign) return;

        toast.loading('Generando PDF firmado...', { id: 'sign-toast' });
        router.post(`/documents/${documentToSign.id}/sign`, {
            signature: signatureClient,
            admin_signature: signatureAdmin
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Contrato firmado correctamente', { id: 'sign-toast' });
                isSignatureModalOpen = false;
                documentToSign = null;
            },
            onError: () => {
                toast.error('Ocurrió un error al firmar el documento', { id: 'sign-toast' });
            }
        });
    }

    function viewPdf(doc: any) {
        if (doc.status === 'Borrador') {
            pdfViewerUrl = `/pacientes/${patient.id}/pdf/contrato?plantilla=${doc.type}`;
        } else if (doc.media && doc.media.length > 0) {
            pdfViewerUrl = doc.media[0].original_url;
        } else {
            toast.error('No se pudo encontrar el archivo del documento');
            return;
        }
        pdfViewerTitle = doc.name;
        isPdfViewerOpen = true;
    }

    function deleteContract() {
        if (confirm('¿Estás seguro de eliminar este contrato y su documento asociado? Esta acción no se puede deshacer.')) {
            router.delete(`/treatment_contracts/${contract.id}`, {
                onSuccess: () => {
                    toast.success('Contrato eliminado correctamente');
                    router.visit(`/patients/${patient.id}?tab=contratos`);
                }
            });
        }
    }
</script>

<AppHead title={`Contrato: ${contract.treatment_name}`} />

<div class="flex h-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col items-start justify-between gap-4 rounded-xl border bg-card p-6 text-card-foreground shadow-sm md:flex-row md:items-center">
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-700">
                {patient.first_name.charAt(0)}{patient.last_name.charAt(0)}
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{patient.first_name} {patient.last_name}</h1>
                <div class="mt-1 flex items-center gap-4 text-sm text-muted-foreground">
                    <span class="flex items-center gap-1">DNI: {patient.dni}</span>
                    <span class="flex items-center gap-1">Tel: {patient.phone}</span>
                </div>
            </div>
        </div>
        <div>
            <Button variant="outline" asChild>
                <Link href={`/patients/${patient.id}?tab=contratos`}>
                    <ArrowLeft class="w-4 h-4 mr-2" /> Volver al Perfil
                </Link>
            </Button>
        </div>
    </div>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
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
                </h1>
                <p class="text-slate-500 mt-1">Iniciado el {new Date(contract.start_date).toLocaleDateString()}</p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" class="text-red-500 hover:text-red-700 hover:bg-red-50" onclick={deleteContract}>
                    <Trash2 class="w-4 h-4 mr-2" /> Eliminar Contrato
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Costo Total Card -->
            <div class="relative overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-slate-50/50"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1">Costo Total</p>
                        <p class="text-3xl font-bold tracking-tight text-slate-900">
                            S/ {Number(contract.total_cost).toFixed(2)}
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                        <Wallet class="h-6 w-6" />
                    </div>
                </div>
            </div>

            <!-- Total Pagado Card -->
            <div class="relative overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all hover:shadow-md hover:border-green-100">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-green-50/50"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1">Total Pagado</p>
                        <p class="text-3xl font-bold tracking-tight text-green-600">
                            S/ {totalPaid.toFixed(2)}
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <TrendingUp class="h-6 w-6" />
                    </div>
                </div>
            </div>

            <!-- Saldo Pendiente Card -->
            <div class="relative overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all hover:shadow-md hover:border-red-100">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-red-50/50"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1">Saldo Pendiente</p>
                        <p class="text-3xl font-bold tracking-tight text-red-600">
                            S/ {balance.toFixed(2)}
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <PiggyBank class="h-6 w-6" />
                    </div>
                </div>
            </div>
        </div>

        <Card class="mb-8">
            <CardContent class="p-6">
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium">Progreso del Pago</span>
                    <span class="font-bold">{percentage}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: {percentage}%"></div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between border-b pb-4">
                <div>
                    <CardTitle class="text-lg">Documento y Pagos</CardTitle>
                    <CardDescription>Gestione las firmas y registre las cuotas del paciente.</CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    {#if contract.document}
                        {#if contract.document.status === 'Borrador'}
                            <Button variant="outline" class="text-emerald-600 hover:text-emerald-700" onclick={() => openSignModal(contract.document)}>
                                <PenTool class="mr-2 h-4 w-4" /> Firmar
                            </Button>
                        {:else}
                            <Button variant="outline" onclick={() => viewPdf(contract.document)}>
                                <FileText class="mr-2 h-4 w-4" /> Ver PDF
                            </Button>
                            {#if contract.document.media && contract.document.media.length > 0}
                                <SendWhatsappButton 
                                    phone={patient.phone} 
                                    media_id={contract.document.media[0].id} 
                                    caption={`Hola, adjunto el contrato de financiamiento para su tratamiento de ${contract.treatment_name}`}
                                    variant="outline"
                                    buttonText="WhatsApp"
                                    class="text-green-600 hover:bg-green-50 hover:text-green-700"
                                >
                                    <MessageCircle class="w-4 h-4 mr-2" /> WhatsApp
                                </SendWhatsappButton>
                            {/if}
                        {/if}
                    {/if}

                    {#if contract.status !== 'Finalizado'}
                        <Button onclick={openPaymentModal} class="bg-blue-600 hover:bg-blue-700">
                            <ReceiptText class="mr-2 h-4 w-4" /> Registrar Cuota
                        </Button>
                    {/if}
                </div>
            </CardHeader>
            <CardContent class="p-0">
                {#if !contract.payments || contract.payments.length === 0}
                    <div class="p-8 text-center text-muted-foreground">
                        <p>No hay pagos registrados aún para este contrato.</p>
                    </div>
                {:else}
                    <div class="divide-y">
                        {#each contract.payments as payment}
                            <div class="flex justify-between items-center p-4 hover:bg-slate-50">
                                <div>
                                    <p class="font-medium text-slate-900">Pago - {new Date(payment.created_at).toLocaleDateString()}</p>
                                    <p class="text-sm text-muted-foreground">{payment.payment_method} • {payment.receipt_type}</p>
                                    {#if payment.notes}
                                        <p class="text-xs text-slate-500 mt-1">{payment.notes}</p>
                                    {/if}
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-lg">S/ {Number(payment.amount).toFixed(2)}</p>
                                    {#if payment.sunat_status === 'Aceptado'}
                                        <span class="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded border border-green-200">Enviado SUNAT</span>
                                    {/if}
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </CardContent>
        </Card>
    </div>

<!-- Modals -->
<Dialog bind:open={showNewPaymentModal}>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Registrar Nueva Cuota / Pago</DialogTitle>
        </DialogHeader>
        <form onsubmit={savePayment} class="space-y-4 mt-4">
            <div class="grid gap-2">
                <Label for="amount">Monto (S/)</Label>
                <Input id="amount" type="number" step="0.01" bind:value={paymentForm.amount} required />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label>Método de Pago</Label>
                    <select bind:value={paymentForm.payment_method} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        <option value="Yape/Plin">Yape/Plin</option>
                    </select>
                </div>
                
                <div class="grid gap-2">
                    <Label>Comprobante</Label>
                    <select bind:value={paymentForm.receipt_type} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="Boleta">Boleta</option>
                        <option value="Factura">Factura</option>
                        <option value="Ticket">Ticket Interno</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="notes">Notas (Opcional)</Label>
                <Input id="notes" bind:value={paymentForm.notes} placeholder="Referencia o detalle del pago" />
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <Button variant="outline" type="button" onclick={() => showNewPaymentModal = false}>Cancelar</Button>
                <Button type="submit" disabled={paymentForm.processing}>Guardar Pago</Button>
            </div>
        </form>
    </DialogContent>
</Dialog>

<SignaturePadModal 
    bind:isOpen={isSignatureModalOpen}
    documentName={documentToSign?.name || ''}
    onSign={handleSign}
    requireAdminSignature={true}
/>

<PdfViewerModal
    bind:isOpen={isPdfViewerOpen}
    pdfUrl={pdfViewerUrl}
    title={pdfViewerTitle}
/>
