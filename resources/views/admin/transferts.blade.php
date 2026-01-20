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
    <div class="relative w-full flex items-center justify-end mb-4 min-h-[40px]">
        
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <h2 class="text-xl text-gray-800 font-medium">
                Vidéos en cours de transfert
            </h2>
        </div>

        <button 
            @click="fetchData(true)" 
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
    <hr class="border-gray-300 mb-8">

    <div x-show="loading" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin h-10 w-10 text-orange-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <div  class="text-gray-500 font-medium">
            <p>Chargement du contenu du NAS...</p>
            <p>Status: <span id="scan-status">en attente...</span></p>
            <p>Fichiers scannés: <span id="scan-count">0</span></p>
        </div>
    </div>

    <div x-show="!loading && error" x-cloak class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg text-center">
        <span class="font-medium">Erreur :</span> Impossible de récupérer la liste des transferts.
    </div>

    <div x-show="!loading && !error && files.length === 0" x-cloak class="flex flex-col items-center justify-center py-10 text-gray-400">
        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
        </svg>
        <p class="text-sm italic">Aucun transfert actif pour le moment.</p>
    </div>

    <div x-show="!loading && files.length > 0" x-cloak class="space-y-8">
        <template x-for="file in files" :key="file.path">
            
            <div 
                x-data="transferRow(file)"
                x-init="init()"
                x-bind:data-active="job_id && !finished" 
                @confirm-cancel-event.window="if($event.detail.id === job_id) executeCancel()"
                class="flex flex-col md:flex-row items-center md:space-x-6 space-y-4 md:space-y-0"
            >
                <div class="flex items-center w-full md:w-1/3">
                    <div class="shrink-0 mr-4">
                        <svg class="w-10 h-10 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                        </svg>
                    </div>
                    
                    <div class="flex flex-col overflow-hidden">
                        <div class="font-bold text-gray-800 truncate" :title="file.filename" x-text="file.filename"></div>
                        
                        <div class="text-xs text-gray-500 truncate" :title="file.path" x-text="file.path"></div>
                    </div>
                </div>

                <template x-if="!job_id">
                    <div class="flex items-center justify-end w-full md:w-2/3 pr-12">
                        <button 
                            @click="startJob"
                            class="bg-[#2C3E50] hover:bg-[#34495e] text-white font-bold py-2 px-6 rounded shadow flex items-center transition"
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
                    <div class="contents md:w-2/3"> 
                        <div class="flex items-center w-full md:w-3/4 space-x-4 md:ml-12">
                            <div class="grow h-3 bg-gray-200 rounded-full overflow-hidden border border-gray-300">
                                <div 
                                    class="h-full bg-[#2C3E50] rounded-full transition-all duration-1000 ease-out"
                                    :style="`width: ${progress}%`"
                                ></div>
                            </div>
                            <div class="w-12 text-right font-bold text-gray-800 text-sm">
                                <span x-text="Math.round(progress)"></span>%
                            </div>
                        </div>

                        <div class="flex items-center justify-center w-full md:w-1/4 text-right md:text-left pl-4 pr-12">
                            <span 
                                class="font-bold text-sm"
                                :class="statusColorClass"
                                x-text="status"
                            ></span>

                            <template x-if="!finished">
                                <button 
                                    @click="askCancel" 
                                    class="ml-2 text-xs text-red-600 font-bold hover:text-red-800 underline cursor-pointer"
                                >
                                    (Annuler)
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <div 
        x-show="modalOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
        x-cloak
    >
        <div 
            class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden transform transition-all"
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.away="modalOpen = false"
        >
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-2">Confirmation</h3>
                <p class="text-sm text-gray-500">
                    Voulez-vous vraiment annuler ce transfert en cours ?
                </p>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row-reverse gap-2">
                <button 
                    @click="confirmCancel"
                    type="button" 
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm"
                >
                    Oui, Annuler
                </button>
                <button 
                    @click="modalOpen = false" 
                    type="button" 
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                >
                    Retour
                </button>
            </div>
        </div>
    </div>

    <div 
        x-show="limitModalOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
        x-cloak
    >
        <div 
            class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden transform transition-all"
            x-show="limitModalOpen"
            @click.away="limitModalOpen = false"
        >
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 mb-4">
                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-bold text-gray-900 mb-2">Limite atteinte</h3>
                <p class="text-sm text-gray-500">
                    Vous ne pouvez lancer que <strong>{{ env('MAX_CONCURRENT_TRANSFERS', 2) }}</strong> transferts à la fois.
                    <br>Veuillez patienter qu'un transfert se termine.
                </p>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6">
                <button 
                    @click="limitModalOpen = false" 
                    type="button" 
                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm"
                >
                    D'accord
                </button>
            </div>
        </div>
    </div>
</div>

<script>

    function transferList() {
        return {
            loading: true,
            error: false,
            files: [],

            // Scan async
            scanId: null,
            scanPoller: null,

            // Modals
            modalOpen: false,
            limitModalOpen: false,
            cancelId: null,

            /**
             * INIT → démarre le scan (rapide, sans timeout)
             */
            fetchData(force = false) {
                this.loading = true;
                this.error = false;
                this.files = [];

                fetch('{{ route("admin.scan.start") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ disk: 'ftp_pad', path: '', force: force })
                })
                .then(res => res.json())
                .then(data => {
                    this.scanId = data.scan_id;

                    if(data.cached) {
                        console.log("Using Cached Scan");
                    } else {
                        document.getElementById("scan-status").textContent = "Démarré";
                    }
                    this.startScanPolling();
                })
                .catch(err => {
                    console.error(err);
                    this.error = true;
                    this.loading = false;
                });
            },

            /**
             * POLLING → surveille l’état du scan
             */
            startScanPolling() {
                this.scanPoller = setInterval(() => {
                    fetch(`/admin/scan/${this.scanId}/status`)
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById("scan-status").textContent = data.status;
                            document.getElementById("scan-count").textContent = data.count;

                            if (data.status === 'done') {
                                clearInterval(this.scanPoller);
                                this.fetchScanResults();
                            }
                            if (data.status === 'failed') {
                                clearInterval(this.scanPoller);
                                this.error = true;
                                this.loading = false;
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            clearInterval(this.scanPoller);
                            this.error = true;
                            this.loading = false;
                        });
                }, 2000);
            },

            /**
             * RÉCUPÈRE LES RÉSULTATS (rapide)
             */
            fetchScanResults() {
                fetch(`/admin/scan/${this.scanId}/results`)
                    .then(res => {
                        if (!res.ok) throw new Error('Scan results failed');
                        return res.json();
                    })
                    .then(data => {
                        this.files = data.results ?? [];
                        this.loading = false;
                    })
                    .catch(err => {
                        console.error(err);
                        this.error = true;
                        this.loading = false;
                    });
            },

            /**
             * Modales (inchangées)
             */
            openModal(id) {
                this.cancelId = id;
                this.modalOpen = true;
            },

            confirmCancel() {
                this.modalOpen = false;
                window.dispatchEvent(new CustomEvent('confirm-cancel-event', {
                    detail: { id: this.cancelId }
                }));
            }
        }
    }


    function transferRow(fileData) {
        return {
            job_id: fileData.job_id,
            progress: fileData.progress,
            status: fileData.status,
            finished: fileData.finished,
            starting: false,

            get statusColorClass() {
                let s = String(this.status).toLowerCase();
                if (s.includes('termin') || s.includes('success')) return 'text-green-600'; 
                if (s.includes('attente')) return 'text-orange-500'; 
                if (s.includes('echou') || s.includes('error')) return 'text-red-600';
                return 'text-[#2C3E50]'; 
            },

            init() {
                if (this.job_id && !this.finished) {
                    this.startPolling();
                }
            },

            startJob() {
                // 1. READ LIMIT FROM HTML (Safe & Clean)
                // We look for the parent div that holds the data-limit attribute
                const container = document.querySelector('[data-limit]');
                const limitRaw = container ? container.getAttribute('data-limit') : '';
                const maxConcurrent = limitRaw ? parseInt(limitRaw) : null;

                // 2. CHECK LIMIT
                const activeCount = document.querySelectorAll('[data-active="true"]').length;
                
                // Only enforce if maxConcurrent is a valid number (not null)
                if (maxConcurrent !== null && activeCount >= maxConcurrent) {
                    this.$dispatch('open-limit-modal');
                    return; 
                }

                // 3. PROCEED
                this.starting = true;

                fetch('{{ route("admin.transferts.start") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: fileData.path, disk: fileData.disk })
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
                .catch(err => {
                    alert("Erreur technique");
                    this.starting = false;
                });
            },

            startPolling() {
                let poller = setInterval(() => {
                    if (this.finished) { clearInterval(poller); return; }

                    fetch(`/admin/transferts/status/${this.job_id}`)
                        .then(res => res.json())
                        .then(data => {
                            this.progress = data.progress;
                            this.status = data.label;
                            this.finished = data.finished;
                        })
                        .catch(() => clearInterval(poller));
                }, 2000);
            },

            askCancel() {
                this.$dispatch('open-cancel-modal', { id: this.job_id });
            },

            executeCancel() {
                fetch(`/admin/transferts/cancel/${this.job_id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                }).then(() => {
                    this.status = "Annulation...";
                    setTimeout(() => window.location.reload(), 1000);
                });
            }
        }
    }
</script>
@endsection