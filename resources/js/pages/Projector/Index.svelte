<script module lang="ts">
    import ProjectorLayout from '@/layouts/ProjectorLayout.svelte';
    export const layout = ProjectorLayout;
</script>

<script lang="ts">
    import { onMount, onDestroy } from 'svelte';
    import { Volume2 } from 'lucide-svelte';
    import { page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';

    let state = $state({
        calling: null as any,
        inProgress: [] as any[],
        waiting: [] as any[]
    });

    let now = $state(new Date());

    let currentCallingId = $state<number | null>(null);
    let interval: ReturnType<typeof setInterval>;

    onMount(() => {
        fetchState();
        interval = setInterval(fetchState, 3000);

        const timeInterval = setInterval(() => {
            now = new Date();
        }, 1000);

        return () => {
            clearInterval(interval);
            clearInterval(timeInterval);
        };
    });

    // Cleanup is handled by the onMount return function in Svelte 5

    function playChime() {
        try {
            const ctx = new (window.AudioContext || (window as any).webkitAudioContext)();
            
            // First chime
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(880, ctx.currentTime); // A5
            gain1.gain.setValueAtTime(0, ctx.currentTime);
            gain1.gain.linearRampToValueAtTime(0.5, ctx.currentTime + 0.05);
            gain1.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 1);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 1);

            // Second chime (higher pitch)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1108.73, ctx.currentTime + 0.3); // C#6
            gain2.gain.setValueAtTime(0, ctx.currentTime + 0.3);
            gain2.gain.linearRampToValueAtTime(0.5, ctx.currentTime + 0.35);
            gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 1.3);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.3);
            osc2.stop(ctx.currentTime + 1.3);
        } catch (e) {
            console.error("Web Audio API not supported", e);
        }
    }

    async function fetchState() {
        try {
            const res = await fetch('/api/projector/state');
            const data = await res.json();
            state = data;

            // Play sound if a new patient is called
            if (data.calling && data.calling.id !== currentCallingId) {
                currentCallingId = data.calling.id;
                try {
                    playChime();
                    
                    // Voice announcement (Text to Speech)
                    const utterance = new SpeechSynthesisUtterance(
                        `Turno para el paciente ${data.calling.patient.first_name} ${data.calling.patient.last_name}. Por favor, pasar al ${data.calling.room || 'consultorio'}.`
                    );
                    utterance.lang = 'es-ES';
                    utterance.rate = 0.9; // Slightly slower for clarity
                    
                    // Wait a bit for the bell to ring before speaking
                    setTimeout(() => {
                        window.speechSynthesis.speak(utterance);
                    }, 1500);

                } catch(e) {
                    console.error("Audio/Voice play failed, user might need to interact first", e);
                }
            } else if (!data.calling) {
                currentCallingId = null;
            }
        } catch (e) {
            console.error(e);
        }
    }

    function requestFullScreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.error(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
            });
        } else {
            document.exitFullscreen();
        }
    }
</script>

<!-- Projector UI Theme -->
<div class="fixed inset-0 bg-slate-900 overflow-hidden flex flex-col font-sans text-white select-none" onclick={requestFullScreen}>
    
    <!-- Background elements for Glassmorphism -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/30 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/30 rounded-full blur-[120px]"></div>

    <!-- Header -->
    <header class="relative z-10 w-full p-8 flex justify-between items-center bg-slate-900/50 backdrop-blur-md border-b border-slate-700/50 shadow-lg">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-blue-500 rounded-2xl flex items-center justify-center shadow-blue-500/50 shadow-lg">
                <AppLogoIcon class="size-10 fill-current text-white" />
            </div>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white">{page.props.appName || 'Clínica Dental'}</h1>
                <p class="text-blue-300 font-medium text-xl">Sala de Espera</p>
            </div>
        </div>
        <div class="text-right">
            <h2 class="text-2xl text-slate-300 font-semibold">{now.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h2>
            <div class="text-5xl font-bold font-mono tracking-wider mt-1" id="clock">
                {now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
            </div>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="relative z-10 flex-1 grid grid-cols-12 gap-8 p-8 h-full">
        
        <!-- Calling Now Panel (Focus) -->
        <div class="col-span-7 flex flex-col">
            <div class="bg-slate-800/60 backdrop-blur-xl border border-slate-700 shadow-2xl rounded-3xl p-10 flex-1 flex flex-col items-center justify-center relative overflow-hidden transition-all duration-500">
                
                {#if state.calling}
                    <!-- Pulse effect when calling -->
                    <div class="absolute inset-0 bg-blue-500/10 animate-pulse"></div>
                    
                    <div class="relative z-10 text-center w-full">
                        <div class="inline-flex items-center justify-center gap-3 bg-red-500 text-white px-6 py-2 rounded-full font-bold text-xl uppercase tracking-widest mb-8 shadow-lg shadow-red-500/30 animate-bounce">
                            <Volume2 class="w-6 h-6" />
                            Turno Actual
                        </div>
                        
                        <h2 class="text-6xl font-black mb-8 text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 break-words leading-tight">
                            {state.calling.patient.first_name} {state.calling.patient.last_name}
                        </h2>
                        
                        <div class="grid grid-cols-2 gap-6 w-full max-w-3xl mx-auto mt-12">
                            <div class="bg-slate-900/50 p-6 rounded-2xl border border-slate-700">
                                <p class="text-slate-400 text-lg mb-1 uppercase tracking-wider font-semibold">Doctor(a)</p>
                                <p class="text-3xl font-bold text-white">{state.calling.dentist.first_name} {state.calling.dentist.last_name}</p>
                            </div>
                            <div class="bg-blue-600/20 p-6 rounded-2xl border border-blue-500/30 shadow-[0_0_30px_rgba(37,99,235,0.2)]">
                                <p class="text-blue-300 text-lg mb-1 uppercase tracking-wider font-semibold">Dirigirse a</p>
                                <p class="text-4xl font-black text-blue-400 uppercase tracking-wide">{state.calling.room || 'Consultorio'}</p>
                            </div>
                        </div>
                    </div>
                {:else if state.inProgress && state.inProgress.length > 0}
                    <div class="w-full h-full flex flex-col">
                        <h2 class="text-3xl font-bold text-slate-300 mb-8 border-b border-slate-700 pb-4">En Consultorio</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto w-full pr-2">
                            {#each state.inProgress as apt}
                                <div class="bg-slate-900/50 p-6 rounded-2xl border border-slate-700 shadow-lg flex flex-col gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(34,197,94,0.6)]"></div>
                                        <span class="text-green-400 font-bold uppercase tracking-wide text-sm">Atendiendo</span>
                                    </div>
                                    <h3 class="text-2xl font-bold text-white truncate">{apt.patient.first_name} {apt.patient.last_name}</h3>
                                    <div class="mt-auto grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-slate-400 uppercase">Doctor(a)</p>
                                            <p class="text-sm font-semibold text-slate-200 truncate">Dr. {apt.dentist.first_name}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-400 uppercase">Sala</p>
                                            <p class="text-sm font-semibold text-blue-400">{apt.room || 'Consultorio'}</p>
                                        </div>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                {:else}
                    <div class="text-center opacity-50">
                        <div class="w-32 h-32 mx-auto mb-6 opacity-50 text-7xl flex items-center justify-center">📺</div>
                        <h2 class="text-4xl font-bold text-slate-300">En espera de pacientes</h2>
                        <p class="text-xl text-slate-400 mt-4">El próximo turno aparecerá aquí en breve.</p>
                    </div>
                {/if}
            </div>
        </div>

        <!-- Waiting List -->
        <div class="col-span-5 flex flex-col">
            <div class="bg-slate-800/40 backdrop-blur-md border border-slate-700/50 shadow-xl rounded-3xl p-8 flex-1 flex flex-col">
                <h3 class="text-2xl font-bold mb-6 text-slate-200 border-b border-slate-700 pb-4 flex items-center justify-between">
                    Siguientes en la fila
                    <span class="bg-slate-700 text-sm py-1 px-3 rounded-full">{state.waiting.length}</span>
                </h3>
                
                <div class="flex-1 overflow-hidden">
                    <div class="space-y-4">
                        {#each state.waiting as apt, i}
                            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 flex items-center gap-5 transition-transform hover:scale-[1.02] {i === 0 ? 'bg-slate-700/60 border-slate-600' : ''}">
                                <div class="w-14 h-14 bg-slate-700 rounded-xl flex items-center justify-center text-xl font-bold text-slate-300 shrink-0">
                                    {i + 1}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xl font-bold text-white truncate">{apt.patient.first_name} {apt.patient.last_name}</h4>
                                    <div class="flex items-center justify-between mt-1 gap-2">
                                        <p class="text-slate-400 text-sm flex items-center gap-1 truncate">
                                            <span class="w-2 h-2 rounded-full bg-blue-400 inline-block shrink-0"></span>
                                            Dr. {apt.dentist.first_name} {apt.dentist.last_name}
                                        </p>
                                        <span class="bg-slate-700/60 text-blue-300 text-xs px-2 py-1 rounded-md shrink-0 border border-slate-600">
                                            {apt.room || 'Sala de espera'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        {/each}
                        
                        {#if state.waiting.length === 0}
                            <div class="text-center py-12 opacity-50">
                                <p class="text-xl text-slate-400">No hay pacientes en espera en este momento.</p>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
        
    </main>
</div>

<style>
    :global(body) {
        margin: 0;
        padding: 0;
        background-color: #0f172a; /* tailwind slate-900 */
    }
</style>
