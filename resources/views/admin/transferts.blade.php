@extends('layouts.admin')

@section('tab_content')
<div 
    class="bg-white border border-gray-200 shadow-sm rounded-lg p-8 min-h-[500px] relative"
    x-data="transferList()"
    x-init="fetchData()"
    data-limit="{{ $maxConcurrent ?? '' }}"
    @open-cancel-modal="openModal($event.detail.id)"
    @open-limit-modal="limitModalOpen = true"
>
    <div class="relative w-full flex items-center justify-end mb-6 min-h-10">
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <h2 class="text-xl text-gray-800 font-medium">
                Vidéos en cours de transfert
            </h2>
        </div>

        <button 
            @click="fetchData()" 
            class="relative z-10 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded inline-flex items-center transition"
            :class="{'opacity-50 cursor-not-allowed': loading}"
            :disabled="loading"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualiser
        </button>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded shadow-sm">
        <div class="flex items-start">
            <div class="shrink-0">
                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    Où sont mes fichiers ?
                </h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>
                        Cette liste affiche uniquement les vidéos indexées en base de données.<br>
                        Si vous venez d'uploader un fichier, vous devez effectuer une indexation manuelle 
                        pour le voir apparaître immédiatement.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <hr class="border-gray-300 mb-8">

    <div x-show="loading" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin h-10 w-10 text-orange-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <div class="text-gray-500 font-medium">
            Chargement de la liste...
        </div>
    </div>

    <div x-show="!loading && error" x-cloak class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg text-center">
        <span class="font-medium">Erreur :</span> Impossible de récupérer la liste des transferts.
    </div>

    <div x-show="!loading && !error && files.length === 0" x-cloak class="flex flex-col items-center justify-center py-10 text-gray-400">
        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
        </svg>
        <p class="text-sm italic">Aucune vidéo en attente de traitement.</p>
    </div>

    <div x-show="!loading && files.length > 0" x-cloak class="space-y-8">
        <template x-for="file in files" :key="file.id">
            
        <div 
                x-data="transferRow(file)"
                x-init="init()"
                x-bind:data-active="job_id && !finished" 
                @confirm-cancel-event.window="if($event.detail.id === job_id) executeCancel()"
                class="flex flex-col md:flex-row items-center w-full py-4 border-b border-gray-100 last:border-0"
            >
                <div class="flex items-center w-full md:w-7/12 pr-4">
                    <div class="shrink-0 mr-4">
                        <svg class="w-10 h-10 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                        </svg>
                    </div>
                    
                    <div class="flex flex-col overflow-hidden min-w-0"> <div class="font-bold text-gray-800 truncate" :title="filename" x-text="filename"></div>
                        
                        <div class="text-xs text-gray-500 truncate" :title="path">
                            <span 
                                class="font-bold" 
                                :class="file.source === 'NAS_ARCH' ? 'text-blue-600' : 'text-orange-600'" 
                                x-text="file.source + ':'">
                            </span>
                            <span x-text="path"></span>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-5/12 pl-4 flex items-center h-10">
                    
                    <template x-if="!job_id">
                        <div class="w-full flex justify-end"> <button 
                                @click="startJob"
                                class="bg-[#2C3E50] hover:bg-[#34495e] text-white font-bold py-2 px-6 rounded shadow flex items-center transition whitespace-nowrap"
                                :class="{'opacity-75 cursor-wait': starting}"
                                :disabled="starting"
                            >
                                <span x-show="!starting">Commencer Transcodage</span>
                                <span x-show="starting" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Lancement...
                                </span>
                            </button>
                        </div>
                    </template>

                    <template x-if="job_id">
                        <div class="w-full flex items-center space-x-4"> 
                            <div class="grow h-4 bg-gray-200 rounded-full overflow-hidden border border-gray-300 relative">
                                <div 
                                    class="h-full bg-[#2C3E50] transition-all duration-500 ease-out flex items-center justify-end pr-2"
                                    :class="{'bg-green-500': finished && status === 'Terminé', 'bg-red-500': finished && status === 'Echoué'}"
                                    :style="`width: ${progress}%`"
                                >
                                </div>
                            </div>
                            
                            <div class="w-12 text-right font-bold text-gray-800 text-sm shrink-0">
                                <span x-text="Math.round(progress)"></span>%
                            </div>

                            <div class="w-28 text-right shrink-0 flex flex-col items-end">
                                <span 
                                    class="font-bold text-sm truncate w-full"
                                    :class="statusColorClass"
                                    x-text="status"
                                ></span>

                                <template x-if="!finished">
                                    <button 
                                        @click="askCancel" 
                                        class="text-xs text-red-600 hover:text-red-800 underline cursor-pointer mt-1"
                                    >
                                        Annuler
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div 
        x-show="modalOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4"
        style="background-color: rgba(0, 0, 0, 0.5);"
        x-cloak
    >
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden p-6 text-center" @click.away="modalOpen = false">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Confirmation</h3>
            <p class="text-sm text-gray-500 mb-6">Voulez-vous vraiment annuler ce transfert ?</p>
            <div class="flex flex-col sm:flex-row-reverse gap-2 justify-center">
                <button @click="confirmCancel" class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700">Oui, Annuler</button>
                <button @click="modalOpen = false" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow hover:bg-gray-50">Retour</button>
            </div>
        </div>
    </div>

    <div 
        x-show="limitModalOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4"
        style="background-color: rgba(0, 0, 0, 0.5);"
        x-cloak
    >
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden p-6 text-center" @click.away="limitModalOpen = false">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Limite atteinte</h3>
            <p class="text-sm text-gray-500 mb-6">
                Limite de <strong>{{ env('MAX_CONCURRENT_TRANSFERS', 2) }}</strong> transferts simultanés atteinte.
            </p>
            <button @click="limitModalOpen = false" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow hover:bg-gray-50">D'accord</button>
        </div>
    </div>
</div>

<script>
    function transferList() {
        return {
            loading: true,
            error: false,
            files: [],
            modalOpen: false,
            limitModalOpen: false,
            cancelId: null,

            fetchData() {
                this.loading = true;
                this.error = false;
                fetch('{{ route("admin.transferts.list") }}')
                .then(res => res.ok ? res.json() : Promise.reject(res))
                .then(data => { this.files = data.results ?? []; this.loading = false; })
                .catch(() => { this.error = true; this.loading = false; });
            },

            openModal(id) { this.cancelId = id; this.modalOpen = true; },
            confirmCancel() { this.modalOpen = false; window.dispatchEvent(new CustomEvent('confirm-cancel-event', { detail: { id: this.cancelId } })); }
        }
    }

    function transferRow(fileData) {
        return {
            id: fileData.id,
            filename: fileData.filename,
            path: fileData.path,
            source: fileData.source,
            job_id: fileData.job_id,
            progress: Number(fileData.progress) || 0,
            status: fileData.status,
            finished: fileData.finished,
            starting: false,
            fakeInterval: null,
            localPathSynced: false,

            get statusColorClass() {
                let s = String(this.status).toLowerCase();
                if (s.includes('termin') || s.includes('success')) return 'text-green-600'; 
                if (s.includes('echou') || s.includes('error')) return 'text-red-600';
                return 'text-blue-600';
            },

            init() {
                if (this.job_id && !this.finished) this.startPolling();
            },

            startJob() {
                const container = document.querySelector('[data-limit]');
                const maxConcurrent = container && container.getAttribute('data-limit') ? parseInt(container.getAttribute('data-limit')) : 2;
                
                const busyCount = document.querySelectorAll('[data-active="true"], [data-starting="true"]').length;
                
                if (busyCount > maxConcurrent) {
                    this.$dispatch('open-limit-modal');
                    return; 
                }

                this.starting = true;

                fetch('{{ route("admin.transferts.start") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify({ path: this.path, disk: (this.source === 'NAS_ARCH' ? 'nas_arch' : 'ftp_pad') })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.job_id = data.job_id;
                        this.status = "Démarrage...";
                        this.progress = 0;
                        this.startPolling(); 
                    } else { 
                        alert("Erreur: " + data.message); 
                        this.starting = false; 
                    }
                })
                .catch(() => { 
                    alert("Erreur technique"); 
                    this.starting = false; 
                });
            },

            startPolling() {
                if (this.fakeInterval) clearInterval(this.fakeInterval);
                this.fakeInterval = setInterval(() => {
                    if (!this.finished && this.progress < 90) {
                        this.progress += 0.5;
                    }
                }, 500);

                let poller = setInterval(() => {
                    if (this.finished) { 
                        clearInterval(poller); 
                        clearInterval(this.fakeInterval);
                        return; 
                    }

                    fetch(`/admin/transferts/status/${this.job_id}`)
                        .then(res => res.json())
                        .then(data => {
                            let apiProgress = Number(data.progress);

                            if (apiProgress > 0) {
                                this.progress = apiProgress;
                            }

                            this.status = data.label;
                            this.finished = data.finished;

                            if (this.finished && (data.label === 'Terminé' || data.label === 'Success')) {
                                this.progress = 100;
                                this.starting = false;

                                this.localPathSynced = true;
                                this.syncLocalPath();
                            } else if (this.finished) {
                                this.starting = false;
                            }
                        })
                        .catch(() => { 
                            clearInterval(poller); 
                            clearInterval(this.fakeInterval);
                        });
                }, 1000); 
            },

            askCancel() {
                this.$dispatch('open-cancel-modal', { id: this.job_id });
            },

            executeCancel() {
                fetch(`/admin/transferts/cancel/${this.job_id}`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
                }).then(() => {
                    this.status = "Annulé";
                    this.finished = true;
                    this.job_id = null;
                    this.progress = 0;
                    this.starting = false;
                    if (this.fakeInterval) clearInterval(this.fakeInterval);
                });
            },

            syncLocalPath() {
                fetch('/admin/media/sync-local-path', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        path: this.path
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.message !== 'Chemin local synchronisé') {
                            console.warn('Sync local path: réponse inattendue', data);
                        }
                    })
                    .catch(err => {
                        console.error('Erreur syncLocalPath', err);
                    });
            }

        }
    }
</script>
@endsection