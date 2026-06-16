<script lang="ts">
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let { 
        value = $bindable(), 
        options = [], 
        placeholder = 'Buscar...', 
        label = '',
        error = '',
        onChange = (val: string) => {}
    } = $props();

    let searchQuery = $state('');
    let showDropdown = $state(false);

    // Initial setup
    $effect(() => {
        if (value) {
            const selected = options.find(o => o.value.toString() === value.toString());
            if (selected && !showDropdown) {
                searchQuery = selected.label;
            }
        } else if (!showDropdown) {
            searchQuery = '';
        }
    });

    let filteredOptions = $derived(
        options.filter(o => {
            if (!searchQuery) return true;
            return o.label.toLowerCase().includes(searchQuery.toLowerCase());
        })
    );

    function selectOption(val: string, lbl: string) {
        value = val;
        searchQuery = lbl;
        showDropdown = false;
        onChange(val);
    }

    function clearSelection() {
        value = '';
        searchQuery = '';
        onChange('');
    }
</script>

<div class="space-y-2 relative">
    {#if label}
        <Label>{label}</Label>
    {/if}
    <div class="relative">
        <Input 
            type="text" 
            {placeholder} 
            bind:value={searchQuery}
            class="h-11 rounded-xl pr-8 bg-white"
            onfocus={() => showDropdown = true}
            onblur={() => setTimeout(() => showDropdown = false, 200)}
        />
        {#if value}
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick={clearSelection}>
                ×
            </button>
        {/if}
    </div>
    {#if showDropdown}
        <div class="absolute z-50 w-full mt-1 max-h-60 overflow-auto rounded-xl border bg-popover text-popover-foreground shadow-md outline-none">
            {#if filteredOptions.length === 0}
                <div class="p-3 text-sm text-gray-500 text-center">No se encontraron opciones.</div>
            {:else}
                {#each filteredOptions as opt}
                    <button 
                        type="button" 
                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-accent hover:text-accent-foreground flex flex-col"
                        onclick={() => selectOption(opt.value.toString(), opt.label)}
                    >
                        <span class="font-medium">{opt.label}</span>
                        {#if opt.description}
                            <span class="text-xs text-muted-foreground">{opt.description}</span>
                        {/if}
                    </button>
                {/each}
            {/if}
        </div>
    {/if}
    {#if error}<p class="text-xs text-red-500">{error}</p>{/if}
</div>
