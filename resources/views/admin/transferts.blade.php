@extends('layouts.admin')

@section('tab_content')
<div 
    class="bg-white border border-gray-200 shadow-sm rounded-lg p-8 min-h-[500px] relative"
    x-data="transferList()"
    x-init="init()"
    data-limit="{{ $maxConcurrent ?? 2 }}"
    @open-cancel-modal="openModal($event.detail.id)"
>
    <div class="relative w-full flex items-center justify-end mb-6 min-h-10">
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <h2 class="text-xl text-gray-800 font-medium">Vidéos en cours de transfert</h2>
        </div>
        <button 
            @click="fetchData()" 
            class="relative z-10 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded inline-flex items-center transition"
            :class="{'opacity-50 cursor-not-allowed': loading}"
            :disabled="loading"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Actualiser
        </button>
    </div>

    <div x-show="loading" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin h-10 w-10 text-orange-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <div class="text-gray-500 font-medium">Chargement de la liste...</div>
    </div>
    <div x-show="!loading && error" x-cloak class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg text-center"><span class="font-medium">Erreur :</span> Impossible de récupérer la liste des transferts.</div>
    <div x-show="!loading && !error && files.length === 0" x-cloak class="flex flex-col items-center justify-center py-10 text-gray-400"><svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg><p class="text-sm italic">Aucune vidéo en attente de traitement.</p></div>

    <div x-show="!loading && files.length > 0" x-cloak class="space-y-8">
        <template x-for="file in files" :key="file.id">
            <div 
                x-data="transferRow(file)"
                x-init="initRow()"
                :id="'row-' + file.id"
                :class="{'is-active-job': starting || (job_id && !finished), 'is-queued-job': isQueued}"
                @execute-start="executeStart()"
                @confirm-cancel-event.window="if($event.detail.id === job_id) executeCancel()"
                class="flex flex-col md:flex-row items-center w-full py-4 border-b border-gray-100 last:border-0"
            >
                <div class="flex items-center w-full md:w-7/12 pr-4">
                    <div class="shrink-0 mr-4">
                        <svg class="w-10 h-10 text-gray-800" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path></svg>
                    </div>
                    <div class="flex flex-col overflow-hidden min-w-0"> 
                        <div class="font-bold text-gray-800 truncate" :title="filename" x-text="filename"></div>
                        <div class="flex flex-col mt-1 space-y-1">
                            <template x-for="p in available_paths" :key="p.label">
                                <div class="text-xs text-gray-500 truncate" :title="p.path">
                                    <span class="font-bold" :class="p.label === 'NAS_ARCH' ? 'text-blue-600' : 'text-orange-600'" x-text="p.label + ' : '"></span>
                                    <span x-text="p.path"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-5/12 pl-4 flex items-center h-10 justify-end">
                    
                    <template x-if="!job_id && !isCancelled && !isQueued && !starting">
                        <button 
                            @click="requestStart"
                            class="bg-[#2C3E50] hover:bg-[#34495e] text-white font-bold py-2 px-6 rounded shadow flex items-center transition whitespace-nowrap"
                        >
                            Commencer Transcodage
                        </button>
                    </template>

                    <template x-if="isCancelled && !isQueued">
                        <div class="flex items-center space-x-3">
                            <button 
                                @click="requestStart"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-1 px-3 text-sm rounded border border-gray-300 shadow-sm flex items-center transition mr-4"
                            >
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Recommencer
                            </button>
                            
                            <span class="font-bold text-sm text-red-600" x-text="status"></span>
                        </div>
                    </template>

                    <template x-if="isQueued">
                        <div class="flex items-center space-x-3 w-full justify-end">
                            <svg class="animate-spin h-5 w-5 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="font-bold text-sm text-orange-500" x-text="status"></span>
                            <button @click="askCancel" class="ml-4 text-xs text-red-600 hover:text-red-800 underline cursor-pointer">Retirer</button>
                        </div>
                    </template>

                    <template x-if="(starting || job_id) && !isQueued&& !isCancelled">
                        <div class="w-full flex items-center space-x-4"> 
                            <div class="grow h-4 bg-gray-200 rounded-full overflow-hidden border border-gray-300 relative">
                                <div 
                                    class="h-full bg-[#2C3E50] transition-all duration-500 ease-out flex items-center justify-end pr-2"
                                    :class="{'bg-green-500': finished && status === 'Terminé', 'bg-red-500': finished && status.includes('Echoué')}"
                                    :style="`width: ${progress}%`"
                                ></div>
                            </div>
                            
                            <div class="w-12 text-right font-bold text-gray-800 text-sm shrink-0">
                                <span x-text="Math.round(progress)"></span>%
                            </div>

                            <div class="w-28 text-right shrink-0 flex flex-col items-end">
                                <span class="font-bold text-sm truncate w-full" :class="statusColorClass" x-text="status" :title="status"></span>

                                <template x-if="!finished && job_id">
                                    <button @click="askCancel" class="text-xs text-red-600 hover:text-red-800 underline cursor-pointer mt-1">Annuler</button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm p-4" style="background-color: rgba(0, 0, 0, 0.5);" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden p-6 text-center" @click.away="modalOpen = false">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Confirmation</h3>
            <p class="text-sm text-gray-500 mb-6">Voulez-vous vraiment annuler ce transfert ?</p>
            <div class="flex flex-col sm:flex-row-reverse gap-2 justify-center">
                <button @click="confirmCancel" class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700">Oui, Annuler</button>
                <button @click="modalOpen = false" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow hover:bg-gray-50">Retour</button>
            </div>
        </div>
    </div>
</div>

<script>
    function transferList() {
        return {
            loading: true, error: false, files: [], modalOpen: false, cancelId: null,
            
            init() {
                this.fetchData();
            },

            fetchData() {
                fetch('{{ route("admin.transferts.list") }}')
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(data => { 
                        this.files = data.results ?? []; 
                        this.loading = false; 
                    })
                    .catch(() => { this.error = true; this.loading = false; });
            },

            openModal(id) { this.cancelId = id; this.modalOpen = true; },
            confirmCancel() { 
                this.modalOpen = false; 
                window.dispatchEvent(new CustomEvent('confirm-cancel-event', { detail: { id: this.cancelId } })); 
            }
        }
    }

    function transferRow(fileData) {
        return {
            id: fileData.id,
            filename: fileData.filename,
            path: fileData.path, 
            disk: fileData.disk, 
            available_paths: fileData.available_paths || [],
            job_id: fileData.job_id,
            progress: Number(fileData.progress) || 0,
            status: fileData.status,
            finished: fileData.finished,
            starting: false,
            syncTimer: null,
            poller: null,
            isCancelled: (String(fileData.status).toLowerCase().includes('annul')), 
            isQueued: fileData.is_queued, 

            get statusColorClass() {
                let s = String(this.status).toLowerCase();
                if (s.includes('termin') || s.includes('success')) return 'text-green-600'; 
                if (s.includes('echou') || s.includes('erreur') || s.includes('annul')) return 'text-red-600';
                if (s.includes('attente') || s.includes('file')) return 'text-orange-500'; 
                return 'text-blue-600';
            },

            initRow() {
                if (this.isCancelled) { this.job_id = null; }
                
                if (this.job_id && !this.finished) { 
                    this.startPolling(); // Ask FFAStrans for %
                } else if (this.isQueued && !this.finished) {
                    this.waitForJobId(); // Ask DB for Job ID
                }
            },

            waitForJobId() {
                if (this.syncTimer) {
                    console.log("Sync: Already monitoring Media " + this.id);
                    return; 
                }

                console.log("Sync: Monitoring Media " + this.id);
                
                this.syncTimer = setInterval(() => {
                    if (!document.getElementById('row-' + this.id)) {
                        clearInterval(this.syncTimer);
                        this.syncTimer = null;
                        return;
                    }

                    if (this.job_id && !this.finished) {
                        console.log("Sync: ID found for Media " + this.id + ". Transitioning...");
                        this.isQueued = false;
                        this.startPolling(); 
                        clearInterval(this.syncTimer);
                        this.syncTimer = null;
                        return;
                    }

                    fetch(`/admin/transferts/db-status/${this.id}`)
                        .then(res => res.ok ? res.json() : Promise.reject())
                        .then(data => {
                            if (data.job_id) {
                                this.job_id = data.job_id;
                                this.status = data.status;
                            }
                        })
                        .catch(err => console.warn("Sync: Retrying Media " + this.id));
                }, 5000); 
            },

            requestStart() {
                // Reset state to ensure we look for a FRESH start
                this.job_id = null;
                this.finished = false;
                this.isCancelled = false;
                this.isQueued = true;
                this.status = "En file d'attente";

                fetch('{{ route("admin.transferts.start") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify({ id: this.id, action: 'queue' })
                });

                this.waitForJobId();
            },

            startPolling() {
                if (this.poller) clearInterval(this.poller);
                this.poller = setInterval(() => {
                    if (!document.getElementById('row-' + this.id) || this.finished) {
                        clearInterval(this.poller); return; 
                    }

                    fetch(`/admin/transferts/status/${this.job_id}`)
                        .then(res => res.ok ? res.json() : Promise.reject())
                        .then(data => {
                            this.progress = Number(data.progress);
                            this.status = data.label;
                            this.finished = data.finished;

                            if (this.finished && (data.progress == 100 || data.label === 'Terminé')) {
                                this.progress = 100;
                                // Sync path once finished
                                fetch('/admin/media/sync-local-path', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                    body: JSON.stringify({ path: this.path })
                                });
                            }
                        })
                        .catch(() => {});
                }, 3000); 
            },
            
            askCancel() { 
                if (this.isQueued) {
                    this.executeCancel(); 
                    return; 
                }
                this.$dispatch('open-cancel-modal', { id: this.job_id }); 
            },

            executeCancel() {
                const cancelId = this.job_id || `queue-${this.id}`;
                fetch(`/admin/transferts/cancel/${cancelId}`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
                }).then(() => {
                    this.status = "Annulé";
                    this.isQueued = false;
                    this.isCancelled = true;
                    this.finished = true;
                    this.job_id = null; 
                    this.progress = 0;
                    if (this.poller) clearInterval(this.poller);
                });
            }
        }
    }
</script>
@endsection