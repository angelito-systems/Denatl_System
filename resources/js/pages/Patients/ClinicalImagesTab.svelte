<script lang="ts">
    import { onMount } from 'svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Input } from '@/components/ui/input';
    import { Textarea } from '@/components/ui/textarea';
    import { UploadCloud, Image as ImageIcon, Trash2, Maximize2, GitCompare, Loader2, Download } from 'lucide-svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import ImageViewerModal from '@/components/images/ImageViewerModal.svelte';
    import ImageComparisonModal from '@/components/images/ImageComparisonModal.svelte';
    import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
    import { Toast } from '@/lib/utils/toast';
    import { Pencil } from 'lucide-svelte';

    let { patient } = $props();

    let images = $state([]);
    let categories = $state([]);
    let isLoading = $state(true);
    let selectedCategory = $state('all');

    // Modals state
    let isViewerOpen = $state(false);
    let viewerIndex = $state(0);
    
    let isComparisonOpen = $state(false);
    
    let editingImage = $state<any>(null);
    const editForm = useForm({
        title: '',
        description: '',
        category_id: '',
        taken_at: ''
    });

    // Upload state
    let isDragging = $state(false);
    let fileInput: HTMLInputElement;
    let selectedFiles = $state<File[]>([]);
    
    const form = useForm({
        category_id: '',
        title: '',
        description: '',
        taken_at: new Date().toISOString().split('T')[0],
        files: [] as File[]
    });

    onMount(async () => {
        await fetchImages();
    });

    async function fetchImages() {
        isLoading = true;
        try {
            const response = await fetch(`/pacientes/${patient.id}/images`);
            const data = await response.json();
            images = data.images;
            categories = data.categories;
        } catch (error) {
            console.error('Error fetching images:', error);
        } finally {
            isLoading = false;
        }
    }

    function handleDragEnter(e: DragEvent) {
        e.preventDefault();
        isDragging = true;
    }

    function handleDragLeave(e: DragEvent) {
        e.preventDefault();
        isDragging = false;
    }

    function handleDrop(e: DragEvent) {
        e.preventDefault();
        isDragging = false;
        if (e.dataTransfer?.files) {
            handleFiles(e.dataTransfer.files);
        }
    }

    function handleFileInput(e: Event) {
        const target = e.target as HTMLInputElement;
        if (target.files) {
            handleFiles(target.files);
        }
    }

    function handleFiles(files: FileList) {
        const newFiles = Array.from(files).filter(file => {
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'application/dicom'];
            return validTypes.includes(file.type) || file.name.endsWith('.dcm');
        });
        selectedFiles = [...selectedFiles, ...newFiles];
    }

    function removeFile(index: number) {
        selectedFiles = selectedFiles.filter((_, i) => i !== index);
    }

    function submitUpload(e: Event) {
        e.preventDefault();
        if (selectedFiles.length === 0) return;

        form.files = selectedFiles;
        
        form.post(`/pacientes/${patient.id}/images`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                selectedFiles = [];
                fetchImages();
            }
        });
    }

    function deleteImage(id: number) {
        Toast.confirm(
            '¿Estás seguro de eliminar esta imagen?',
            () => {
                router.delete(`/patient-images/${id}`, {
                    preserveScroll: true,
                    onSuccess: () => fetchImages()
                });
            },
            {
                title: 'Eliminar Imagen',
                type: 'destructive',
                confirmText: 'Eliminar'
            }
        );
    }

    function openViewer(index: number) {
        viewerIndex = index;
        isViewerOpen = true;
    }

    function openEditModal(img: any) {
        editingImage = img;
        editForm.title = img.title || '';
        editForm.description = img.description || '';
        editForm.category_id = img.category_id;
        editForm.taken_at = img.taken_at ? img.taken_at.split('T')[0] : '';
    }

    function submitEdit(e: Event) {
        e.preventDefault();
        editForm.put(`/patient-images/${editingImage.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                editingImage = null;
                fetchImages();
                Toast.success('Éxito', 'Imagen actualizada correctamente');
            }
        });
    }

    let filteredImages = $derived(
        selectedCategory === 'all' 
            ? images 
            : images.filter(img => img.category_id.toString() === selectedCategory)
    );

</script>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Columna Izquierda: Subida de Imágenes -->
    <div class="lg:col-span-1 space-y-6">
        <Card class="border-dashed border-2 shadow-sm bg-slate-50/50">
            <CardHeader class="pb-4">
                <CardTitle class="text-lg">Subir Imágenes</CardTitle>
                <CardDescription>Formatos: JPG, PNG, WEBP, PDF</CardDescription>
            </CardHeader>
            <CardContent>
                <form onsubmit={submitUpload} class="space-y-4">
                    <div class="space-y-2">
                        <Label>Categoría *</Label>
                        <select bind:value={form.category_id} required class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="">Seleccione...</option>
                            {#each categories as cat}
                                <option value={cat.id}>{cat.name}</option>
                            {/each}
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label>Título / Nombre (opcional)</Label>
                        <Input bind:value={form.title} placeholder="Si queda vacío, se usa el nombre del archivo" />
                    </div>

                    <div class="space-y-2">
                        <Label>Fecha de toma (opcional)</Label>
                        <Input type="date" bind:value={form.taken_at} />
                    </div>

                    <!-- svelte-ignore a11y_no_static_element_interactions -->
                    <div 
                        class="border-2 border-dashed rounded-xl p-6 text-center transition-colors {isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400 bg-white'}"
                        ondragenter={handleDragEnter}
                        ondragover={handleDragEnter}
                        ondragleave={handleDragLeave}
                        ondrop={handleDrop}
                    >
                        <input 
                            type="file" 
                            multiple 
                            accept="image/jpeg,image/png,image/webp,application/pdf,.dcm" 
                            class="hidden" 
                            bind:this={fileInput}
                            onchange={handleFileInput}
                        />
                        <UploadCloud class="w-10 h-10 mx-auto text-blue-500 mb-2" />
                        <p class="text-sm text-gray-600 mb-2">Arrastra tus archivos aquí o</p>
                        <Button type="button" variant="outline" size="sm" onclick={() => fileInput.click()}>Explorar</Button>
                    </div>

                    {#if selectedFiles.length > 0}
                        <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                            {#each selectedFiles as file, i}
                                <div class="flex items-center justify-between bg-white border p-2 rounded-md text-sm">
                                    <span class="truncate max-w-[180px]">{file.name}</span>
                                    <button type="button" class="text-red-500 hover:text-red-700 p-1" onclick={() => removeFile(i)}>
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            {/each}
                        </div>
                    {/if}

                    <div class="space-y-2 pt-2 border-t">
                        <Label>Notas/Descripción</Label>
                        <Textarea bind:value={form.description} rows={2} placeholder="Opcional..." />
                    </div>

                    <Button type="submit" class="w-full bg-blue-600 hover:bg-blue-700" disabled={selectedFiles.length === 0 || form.processing}>
                        {#if form.processing}
                            <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Subiendo...
                        {:else}
                            Subir {selectedFiles.length} archivo(s)
                        {/if}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>

    <!-- Columna Derecha: Galería -->
    <div class="lg:col-span-3 space-y-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-xl border shadow-sm">
            <div class="flex items-center gap-2">
                <Label class="whitespace-nowrap font-medium">Filtrar por:</Label>
                <select bind:value={selectedCategory} class="flex h-9 w-48 items-center justify-between rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="all">Todas las categorías</option>
                    {#each categories as cat}
                        <option value={cat.id.toString()}>{cat.name}</option>
                    {/each}
                </select>
            </div>
            
            <Button variant="outline" class="border-indigo-200 text-indigo-700 hover:bg-indigo-50" onclick={() => isComparisonOpen = true} disabled={images.length < 2}>
                <GitCompare class="w-4 h-4 mr-2" />
                Comparar Imágenes
            </Button>
        </div>

        {#if isLoading}
            <div class="flex items-center justify-center h-64">
                <Loader2 class="w-8 h-8 animate-spin text-blue-500" />
            </div>
        {:else if filteredImages.length === 0}
            <div class="flex flex-col items-center justify-center h-64 border-2 border-dashed rounded-xl bg-slate-50 text-slate-500">
                <ImageIcon class="w-12 h-12 mb-3 text-slate-300" />
                <p class="font-medium text-lg text-slate-600">No hay imágenes</p>
                <p class="text-sm">Sube la primera imagen desde el panel izquierdo.</p>
            </div>
        {:else}
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                {#each filteredImages as img, i}
                    <div class="group relative bg-white border rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                        <!-- Thumbnail -->
                        <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden cursor-pointer relative" onclick={() => openViewer(images.indexOf(img))}>
                            {#if img.mime_type === 'application/pdf'}
                                <div class="flex flex-col items-center text-red-500">
                                    <svg class="w-12 h-12 mb-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-1.25 17h-1.5v-6h1.5v6zm2.5 0h-1.5v-6h1.5v6zm2.5 0h-1.5v-6h1.5v6z"/></svg>
                                    <span class="font-bold text-sm">PDF</span>
                                </div>
                            {:else}
                                <img src={img.url} alt={img.title} class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            {/if}
                            
                            <!-- Overlay actions -->
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                                <Button size="icon" variant="secondary" class="rounded-full w-10 h-10 shadow-lg" onclick={(e) => { e.stopPropagation(); openViewer(images.indexOf(img)); }}>
                                    <Maximize2 class="w-5 h-5" />
                                </Button>
                                <a href={`/patient-images/${img.id}/download`} download class="flex items-center justify-center w-10 h-10 bg-white text-slate-900 rounded-full shadow-lg hover:bg-slate-100 transition-colors" onclick={(e) => e.stopPropagation()}>
                                    <Download class="w-5 h-5" />
                                </a>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="p-3">
                            <h4 class="font-semibold text-sm truncate text-slate-800" title={img.title}>{img.title || img.file_name}</h4>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">{img.category?.name}</span>
                                <span class="text-[10px] text-slate-400">{new Date(img.taken_at || img.created_at).toLocaleDateString()}</span>
                            </div>
                            <div class="flex items-center justify-between mt-3 pt-3 border-t">
                                <span class="text-[10px] text-slate-500 truncate" title={img.uploaded_by?.first_name}>
                                    Por: {img.uploaded_by?.first_name} {img.uploaded_by?.last_name}
                                </span>
                                <div class="flex gap-1">
                                    <button class="text-blue-400 hover:text-blue-600 p-1" onclick={() => openEditModal(img)} title="Editar">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button class="text-red-400 hover:text-red-600 p-1" onclick={() => deleteImage(img.id)} title="Eliminar">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</div>

<ImageViewerModal bind:isOpen={isViewerOpen} {images} bind:currentIndex={viewerIndex} />
<ImageComparisonModal bind:isOpen={isComparisonOpen} {images} />

{#if editingImage}
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onclick={() => editingImage = null}>
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] flex flex-col" onclick={e => e.stopPropagation()}>
            <div class="p-6 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold">Editar Imagen</h3>
                <button onclick={() => editingImage = null} class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5"/></button>
            </div>
            <div class="p-6 overflow-y-auto">
                <form id="edit-img-form" onsubmit={submitEdit} class="space-y-4">
                    <div class="space-y-2">
                        <Label>Título / Nombre</Label>
                        <Input bind:value={editForm.title} />
                    </div>
                    <div class="space-y-2">
                        <Label>Categoría</Label>
                        <select bind:value={editForm.category_id} required class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            {#each categories as cat}
                                <option value={cat.id}>{cat.name}</option>
                            {/each}
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label>Fecha de toma</Label>
                        <Input type="date" bind:value={editForm.taken_at} />
                    </div>
                    <div class="space-y-2">
                        <Label>Descripción</Label>
                        <Textarea bind:value={editForm.description} rows={3} />
                    </div>
                </form>
            </div>
            <div class="p-6 border-t flex justify-end gap-3 bg-slate-50 rounded-b-xl">
                <Button variant="outline" onclick={() => editingImage = null}>Cancelar</Button>
                <Button type="submit" form="edit-img-form" disabled={editForm.processing}>
                    {#if editForm.processing}<Loader2 class="w-4 h-4 mr-2 animate-spin"/>{/if}
                    Guardar
                </Button>
            </div>
        </div>
    </div>
{/if}

<!-- Import X at the bottom to fix the X reference inside the template -->
<script module>
    import { X } from 'lucide-svelte';
</script>
