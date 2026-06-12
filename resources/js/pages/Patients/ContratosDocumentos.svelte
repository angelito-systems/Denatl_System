<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { FileText, Trash2, Download, Plus, MessageCircle, FileUp, Loader2, PenTool } from 'lucide-svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import PdfViewerModal from '@/components/PdfViewerModal.svelte';
    import SignaturePadModal from '@/components/SignaturePadModal.svelte';
    import SendWhatsappButton from '@/components/SendWhatsappButton.svelte';
    import { toast } from 'svelte-sonner';

    let { patient } = $props();

    // Estado del Modal de WhatsApp
    let isWhatsAppModalOpen = $state(false);
    let selectedMediaForWa = $state<any>(null);
    let waPhone = $state(patient.phone || '');
    let isSendingWa = $state(false);

    // Formulario para Subir un Documento Externo
    const uploadForm = useForm({
        name: '',
        file: null as File | null
    });

    // Formulario para Generar Contrato Interno
    let isGenerarModalOpen = $state(false);
    const generarForm = useForm({
        plantilla: ''
    });

    // Estado del modal de Firma
    let isSignatureModalOpen = $state(false);
    let documentToSign = $state<any>(null);

    // PDF Viewer State
    let isPdfViewerOpen = $state(false);
    let pdfViewerUrl = $state('');
    let pdfViewerTitle = $state('');

    function handleFileChange(e: Event) {
        const target = e.target as HTMLInputElement;
        if (target.files && target.files.length > 0) {
            uploadForm.file = target.files[0];
            if (!uploadForm.name) {
                uploadForm.name = target.files[0].name.split('.')[0];
            }
        }
    }

    function uploadDocument(e: Event) {
        e.preventDefault();
        uploadForm.post(`/pacientes/${patient.id}/documents/upload`, {
            preserveScroll: true,
            onSuccess: () => {
                uploadForm.reset();
                uploadForm.file = null;
                const fileInput = document.getElementById('file-upload') as HTMLInputElement;
                if (fileInput) fileInput.value = '';
                toast.success('Documento subido correctamente');
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

    function generarContrato(e: Event) {
        e.preventDefault();
        if (!generarForm.plantilla) {
            toast.error('Selecciona una plantilla');
            return;
        }
        
        generarForm.post(`/pacientes/${patient.id}/documents/store`, {
            preserveScroll: true,
            onSuccess: () => {
                isGenerarModalOpen = false;
                toast.success('Borrador generado. Ahora puede visualizarlo o firmarlo.');
                generarForm.reset();
            }
        });
    }

    function deleteDocument(docId: number) {
        if (confirm('¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.')) {
            router.delete(`/documents/${docId}`, {
                preserveScroll: true,
                onSuccess: () => toast.success('Documento eliminado correctamente')
            });
        }
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
                toast.success('Documento firmado y guardado correctamente', { id: 'sign-toast' });
                isSignatureModalOpen = false;
                documentToSign = null;
            },
            onError: () => {
                toast.error('Ocurrió un error al firmar el documento', { id: 'sign-toast' });
            }
        });
    }

    function formatPlantilla(type: string) {
        const labels: Record<string, string> = {
            'ortodoncia': 'Contrato de Ortodoncia',
            'implantes': 'Contrato de Implantes',
            'consentimiento': 'Consentimiento Informado',
            'externo': 'Documento Subido'
        };
        return labels[type] || 'Desconocido';
    }
</script>

<div class="space-y-6">
    <Card>
        <CardHeader class="flex flex-row items-center justify-between border-b pb-4">
            <div>
                <CardTitle class="text-xl">Contratos y Consentimientos</CardTitle>
                <CardDescription>Genera y administra los documentos de este paciente.</CardDescription>
            </div>
            <div class="flex items-center gap-3">
                <Button onclick={() => isGenerarModalOpen = true}>
                    <Plus class="w-4 h-4 mr-2" />
                    Nuevo Documento
                </Button>
                <Dialog bind:open={isGenerarModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Nuevo Contrato/Consentimiento</DialogTitle>
                            <DialogDescription>
                                Selecciona la plantilla que deseas generar para este paciente.
                            </DialogDescription>
                        </DialogHeader>
                        <form onsubmit={generarContrato} class="space-y-6 pt-4">
                            <div class="space-y-2">
                                <Label>Seleccionar Plantilla:</Label>
                                <Select bind:value={generarForm.plantilla} type="single">
                                    <SelectTrigger>
                                        {generarForm.plantilla ? formatPlantilla(generarForm.plantilla) : 'Elige un documento...'}
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ortodoncia">Contrato de Ortodoncia</SelectItem>
                                        <SelectItem value="implantes">Contrato de Implantes</SelectItem>
                                        <SelectItem value="consentimiento">Consentimiento Informado</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" class="w-full" disabled={generarForm.processing}>
                                {#if generarForm.processing}
                                    <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Generando...
                                {:else}
                                    <FileText class="w-4 h-4 mr-2" /> Generar Contrato
                                {/if}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            {#if patient.documents && patient.documents.length > 0}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-muted-foreground uppercase bg-slate-50 border-b">
                            <tr>
                                <th class="px-6 py-4 font-medium">Fecha Creación</th>
                                <th class="px-6 py-4 font-medium">Plantilla</th>
                                <th class="px-6 py-4 font-medium">Estado</th>
                                <th class="px-6 py-4 font-medium text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            {#each patient.documents as doc}
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-slate-900 flex items-center gap-2">
                                        <FileText class="w-4 h-4 text-slate-400" />
                                        {doc.name}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">
                                        {formatPlantilla(doc.type)}
                                    </td>
                                    <td class="px-4 py-3">
                                        {#if doc.status === 'Firmado'}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                Firmado
                                            </span>
                                        {:else if doc.status === 'Borrador'}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                                Borrador
                                            </span>
                                        {:else}
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                Subido
                                            </span>
                                        {/if}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {new Date(doc.created_at).toLocaleDateString()}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            {#if doc.status === 'Borrador'}
                                                <Button variant="ghost" size="icon" class="text-emerald-600 hover:bg-emerald-50" onclick={() => openSignModal(doc)} title="Firmar Documento">
                                                    <PenTool class="w-4 h-4" />
                                                </Button>
                                            {/if}
                                            {#if doc.status !== 'Borrador' && doc.media && doc.media.length > 0}
                                                <SendWhatsappButton 
                                                    phone={patient.phone} 
                                                    media_id={doc.media[0].id} 
                                                    caption={`Hola, adjunto el documento: ${doc.name}`}
                                                    variant="ghost"
                                                    buttonText=""
                                                    class="h-9 w-9 p-0 text-green-600 hover:bg-green-50 hover:text-green-700"
                                                >
                                                    <MessageCircle class="w-4 h-4" />
                                                </SendWhatsappButton>
                                            {/if}
                                            <Button variant="ghost" size="icon" onclick={() => viewPdf(doc)} title="Imprimir / Ver PDF">
                                                <FileText class="w-4 h-4 text-blue-600" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-600 hover:bg-red-50" onclick={() => deleteDocument(doc.id)} title="Eliminar">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {:else}
                <div class="text-center py-16 text-muted-foreground">
                    <FileText class="w-12 h-12 mx-auto mb-4 opacity-20" />
                    <p class="text-lg font-medium text-slate-900">No hay contratos</p>
                    <p>Aún no se ha generado ningún contrato o documento para este paciente.</p>
                </div>
            {/if}
        </CardContent>
    </Card>

    <Card>
        <CardHeader>
            <CardTitle class="text-lg">Subir Archivo Externo</CardTitle>
            <CardDescription>Sube un contrato ya firmado físicamente, imagen del DNI u otro documento.</CardDescription>
        </CardHeader>
        <CardContent>
            <form onsubmit={uploadDocument} class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="space-y-2 flex-1">
                    <Label for="file-upload">Archivo PDF o Imagen</Label>
                    <Input id="file-upload" type="file" accept=".pdf,.jpg,.jpeg,.png" onchange={handleFileChange} required />
                </div>
                
                <div class="space-y-2 flex-1">
                    <Label for="doc-name">Nombre del Documento</Label>
                    <Input id="doc-name" type="text" bind:value={uploadForm.name} placeholder="Ej: Contrato Escaneado" />
                </div>

                <Button type="submit" disabled={!uploadForm.file || uploadForm.processing}>
                    <FileUp class="w-4 h-4 mr-2" />
                    Subir
                </Button>
            </form>
        </CardContent>
    </Card>
</div>

<!-- Modal Visor de PDF Reutilizable -->
<PdfViewerModal 
    bind:isOpen={isPdfViewerOpen} 
    url={pdfViewerUrl} 
    title={pdfViewerTitle} 
/>

<!-- Modal para Firmar Documento -->
<SignaturePadModal 
    bind:isOpen={isSignatureModalOpen} 
    documentName={documentToSign?.name || 'Documento'}
    onSign={handleSign}
/>
