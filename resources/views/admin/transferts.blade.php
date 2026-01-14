@extends('layouts.admin')

@section('tab_content')
<div 
    class="bg-white border border-gray-200 shadow-sm rounded-lg p-8 min-h-[500px]"
    x-data="transferList()"
    x-init="fetchData()"
>
    
    <h2 class="text-xl text-center text-gray-800 font-medium mb-4">
        Vidéos en cours de transfert
    </h2>
    <hr class="border-gray-300 mb-8">

    <div x-show="loading" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin h-10 w-10 text-orange-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-500 font-medium">Connexion au serveur...</p>
    </div>

    <div x-show="!loading && error" x-cloak class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg text-center">
        <span class="font-medium">Erreur :</span> Impossible de récupérer la liste des transferts.
    </div>

    <div x-show="!loading && !error && transfers.length === 0" x-cloak class="flex flex-col items-center justify-center py-10 text-gray-400">
        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
        </svg>
        <p class="text-sm italic">Aucun transfert actif pour le moment.</p>
    </div>

    <div x-show="!loading && transfers.length > 0" x-cloak class="space-y-8">
        <template x-for="transfer in transfers" :key="transfer.id">
            
            <div 
                x-data="transferRow(transfer.id, transfer.progress, transfer.status)"
                x-init="init()"
                class="flex flex-col md:flex-row items-center md:space-x-6 space-y-4 md:space-y-0"
            >
                <div class="flex items-center w-full md:w-1/3">
                    <div class="shrink-0 mr-4">
                        <svg class="w-10 h-10 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                        </svg>
                    </div>
                    <div class="font-bold text-gray-800 truncate" :title="transfer.filename" x-text="transfer.filename"></div>
                </div>

                <div class="flex items-center w-full md:w-1/2 space-x-4">
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

                <div class="w-full md:w-1/6 text-right md:text-left pl-4">
                    <span 
                        class="font-bold text-sm"
                        :class="statusColorClass"
                        x-text="status"
                    ></span>

                    <template x-if="!finished">
                        <button @click="cancelJob" class="ml-2 text-xs text-red-400 hover:text-red-600 underline cursor-pointer">
                            (Annuler)
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    // --- 1. Main List Logic (Fetches data via AJAX) ---
    function transferList() {
        return {
            loading: true,
            error: false,
            transfers: [], // Starts empty, filled by fetch
            fetchData() {
                fetch('{{ route("admin.transfers.list") }}')
                    .then(res => {
                        if(!res.ok) throw new Error("API Error");
                        return res.json();
                    })
                    .then(data => {
                        this.transfers = data;
                        this.loading = false;
                    })
                    .catch(err => {
                        console.error(err);
                        this.error = true;
                        this.loading = false;
                    });
            }
        }
    }

    // --- 2. Row Logic (Polls status updates) ---
    function transferRow(jobId, initialProgress, initialStatus) {
        return {
            progress: initialProgress,
            status: initialStatus,
            finished: ['Terminé', 'Echoué', 'Success', 'Error', 'Done', 'Finished'].includes(initialStatus),

            get statusColorClass() {
                let s = String(this.status).toLowerCase();
                if (s.includes('termin') || s.includes('success')) return 'text-green-600'; 
                if (s.includes('attente')) return 'text-orange-500'; 
                if (s.includes('echou') || s.includes('error')) return 'text-red-600';
                return 'text-[#2C3E50]'; 
            },

            init() {
                if (this.finished) return;

                let poller = setInterval(() => {
                    if (this.finished) { clearInterval(poller); return; }

                    fetch(`/admin/transferts/status/${jobId}`)
                        .then(res => res.json())
                        .then(data => {
                            this.progress = data.progress;
                            this.status = data.label;
                            this.finished = data.finished;
                        })
                        .catch(err => console.error(err));
                }, 2000);
            },

            cancelJob() {
                if(!confirm('Voulez-vous vraiment annuler ce transfert ?')) return;
                
                fetch(`/admin/transferts/cancel/${jobId}`, {
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