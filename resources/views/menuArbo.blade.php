<div class="main-menuArbo">
    <div class="dossiers">

        {{-- Local --}}
        <div class="menuArbo local"
             id="tree-local"
             data-disk="external_local"
             data-loaded="0">
            <h3 class="text-white p-2 font-bold bg-gray-800">Espace Local</h3>
            <div class="tree-container">
                @if(isset($localTree) && count($localTree) > 0)
                    @include('explorer.tree-item', ['items' => $localTree])
                @else
                    <p class="text-white p-4 italic text-sm">Dossier vide ou chemin incorrect.</p>
                @endif
            </div>
        </div>

        {{-- NAS PAD --}}
        <div class="menuArbo PAD hidden"
             id="tree-pad"
             data-disk="ftp_pad"
             data-loaded="0">
            <h3 class="text-white p-2 font-bold bg-blue-900">NAS PAD</h3>
            <div class="tree-container"></div>
        </div>

        {{-- NAS ARCH --}}
        <div class="menuArbo ARCH hidden"
             id="tree-arch"
             data-disk="ftp_arch"
             data-loaded="0">
            <h3 class="text-white p-2 font-bold bg-green-900">NAS ARCH</h3>
            <div class="tree-container"></div>
        </div>

    </div>

    {{-- Radios pour changer la source --}}
    <div class="radio flex gap-2 p-2">
        <label class="cursor-pointer text-white">
            <input type="radio" name="source_choix" value="local" checked onclick="changerSource('local')">
            Stockage Local
        </label>

        <label class="cursor-pointer text-white">
            <input type="radio" name="source_choix" value="pad" onclick="changerSource('pad')">
            NAS PAD
        </label>
        <label class="cursor-pointer text-white">
            <input type="radio" name="source_choix" value="arch" onclick="changerSource('arch')">
            NAS ARCH
        </label>
    </div>

    {{-- Bouton pour ouvrir/fermer le menu --}}
    <button onclick="toggleMenuArbo()" class="absolute top-2 right-[-30px] bg-gray-800 text-white p-2 rounded-r">
        <svg fill="currentColor" viewBox="0 0 16 16" width="20" height="20">
            <path fill-rule="evenodd" d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"/>
        </svg>
    </button>
</div>

<div class="voile" onclick="toggleMenuArbo()"></div>

{{-- Alpine.js Transcode Modal --}}
<div x-data="transcodeModal()" 
     @open-transcode-modal.window="open($event.detail)"
     class="relative">
    
    <div x-show="isOpen" 
         x-transition.opacity
         class="fixed inset-0 z-[2000] flex items-center justify-center backdrop-blur-sm p-4" 
         style="background-color: rgba(0, 0, 0, 0.5);" 
         x-cloak>
        
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden p-6 text-center" 
             @click.away="if(!loading) isOpen = false">
            
            {{-- ICON LOGIC --}}
            <div class="flex justify-center mb-4">
                <template x-if="!resultMode">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </div>
                </template>
                <template x-if="resultMode === 'success'">
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </template>
            </div>

            {{-- TEXT CONTENT --}}
            <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="title"></h3>
            
            <div class="text-sm text-gray-500 mb-6">
                <template x-if="!resultMode">
                    <p>
                        Voulez-vous vraiment lancer le transcodage de tous les fichiers du dossier : <br>
                        <span class="font-mono text-blue-600 break-all" x-text="path"></span> ?
                    </p>
                </template>
                <template x-if="resultMode">
                    <p x-text="message"></p>
                </template>
            </div>

            {{-- BUTTONS --}}
            <div class="flex flex-col sm:flex-row-reverse gap-2 justify-center">
                <template x-if="!resultMode">
                    <div class="flex gap-2">
                        <button @click="confirm()" 
                                :disabled="loading"
                                class="bg-[#2C3E50] text-white px-6 py-2 rounded shadow hover:bg-[#34495e] font-bold transition disabled:opacity-50">
                            <span x-show="!loading">Oui, Transcoder</span>
                            <span x-show="loading">Traitement...</span>
                        </button>
                        <button @click="isOpen = false" 
                                class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded shadow hover:bg-gray-50 transition">
                            Annuler
                        </button>
                    </div>
                </template>
                <template x-if="resultMode">
                    <button @click="isOpen = false" 
                            class="bg-[#2C3E50] text-white px-8 py-2 rounded shadow hover:bg-[#34495e] font-bold transition">
                        Fermer
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>

    window.fileExplorerConfig = {
        local: '/',
        pad: @json(config('filesystems.disks.ftp_pad.root')),
        arch: @json(config('filesystems.disks.ftp_arch.root'))
    };

    // OPEN/CLOSE MENU
    window.toggleMenuArbo = function() {
        document.querySelector('.main-menuArbo').classList.toggle('ouvert');
        document.querySelector('.voile').classList.toggle('ouvert');
    };

    // SWITCH TABS + AJAX LOAD
    window.changerSource = function(source) {
        const ids = ['local', 'pad', 'arch'];

        ids.forEach(id => {
            const el = document.getElementById('tree-' + id);
            if (el) el.classList.add('hidden');
        });

        const target = document.getElementById('tree-' + source);
        if (!target) return;

        target.classList.remove('hidden');
        if (target.dataset.loaded === '1') return;

        const disk = target.dataset.disk;
        const container = target.querySelector('.tree-container');
        const path = window.fileExplorerConfig[source] || '/';

        container.innerHTML = '<div class="text-gray-400 text-sm p-4">Chargement…</div>';

        fetch(`/explorer/scan?disk=${encodeURIComponent(disk)}&path=${encodeURIComponent(path)}`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                target.dataset.loaded = '1';
            })
            .catch(err => {
                console.error(err);
                container.innerHTML =
                    '<div class="text-red-400 text-sm p-4">Erreur de chargement</div>';
            });
    };


    // LOAD FOLDER AU CLIC (lazy-loading des sous-dossiers)
    window.loadFolder = function(el) {
        const container = el.nextElementSibling;

        // Toggle si déjà chargé
        if (container.dataset.loaded === '1') {
            container.classList.toggle('hidden');
            return;
        }

        const disk = el.dataset.disk;
        const path = el.dataset.path;

        container.classList.remove('hidden');
        container.innerHTML = '<div class="text-gray-400 text-sm p-2">Chargement…</div>';

        fetch(`/explorer/scan?disk=${encodeURIComponent(disk)}&path=${encodeURIComponent(path)}`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.dataset.loaded = '1';
            })
            .catch(err => {
                console.error('Erreur fetch:', err);
                container.innerHTML = '<div class="text-red-400 text-sm p-2">Erreur de chargement</div>';
            });
    };

    window.toggleActionMenu = function(e, btn) {
        e.stopPropagation();
        const dropdown = btn.nextElementSibling;
        
        // Close other open menus
        document.querySelectorAll('.action-dropdown').forEach(el => {
            if (el !== dropdown) el.classList.add('hidden');
        });
        
        const isHidden = dropdown.classList.toggle('hidden');
        
        // If we just opened it, calculate the "break out" position
        if (!isHidden) {
            const rect = btn.getBoundingClientRect();
            
            // 1. Position slightly under the "+" (5px)
            dropdown.style.top = (rect.bottom + 5) + 'px';
            
            // 2. Position it to protrude halfway (Sidebar is 400px, menu is 128px)
            // We set the left so that 64px (half) is inside and 64px is outside
            dropdown.style.left = '336px'; 
        }
    };

    // Close the menu if the user scrolls the sidebar (so the menu doesn't "float" away)
    document.querySelector('.menuArbo').addEventListener('scroll', () => {
        document.querySelectorAll('.action-dropdown').forEach(el => el.classList.add('hidden'));
    }, { passive: true });

    // Close the menu if you click anywhere else on the page
    document.addEventListener('click', function() {
        document.querySelectorAll('.action-dropdown').forEach(el => el.classList.add('hidden'));
    });

    // Function to bridge our plain JS sidebar with the Alpine Modal
    window.openTranscodeModal = function(disk, path) {
        window.dispatchEvent(new CustomEvent('open-transcode-modal', { 
            detail: { disk: disk, path: path } 
        }));
    };

    function transcodeModal() {
        return {
            isOpen: false,
            loading: false,
            resultMode: null, // 'success' or 'error'
            title: 'Confirmation de transcodage',
            message: '',
            disk: '',
            path: '',

            open(data) {
                this.disk = data.disk;
                this.path = data.path;
                this.resultMode = null;
                this.loading = false;
                this.title = 'Confirmation de transcodage';
                this.isOpen = true;
            },

            confirm() {
                this.loading = true;
                
                fetch('/explorer/transcode-folder', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                    },
                    body: JSON.stringify({ disk: this.disk, path: this.path })
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    this.resultMode = 'success';
                    this.title = 'Succès';
                    this.message = data.message;
                    
                    // Refresh background lists if present
                    window.dispatchEvent(new CustomEvent('refresh-transfers'));
                })
                .catch(err => {
                    this.loading = false;
                    this.resultMode = 'error';
                    this.title = 'Erreur';
                    this.message = "Une erreur est survenue lors de la communication avec le serveur.";
                    console.error("Erreur:", err);
                });
            }
        }
    }
    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // 1. On s'assure que le bouton radio "local" est coché
        const radioLocal = document.querySelector('input[value="local"]');
        if (radioLocal) radioLocal.checked = true;

        // 2. On appelle la fonction pour synchroniser l'affichage
        // Cela va gérer les classes 'hidden' correctement dès le départ
        changerSource('local');
    });
</script>
@endpush
