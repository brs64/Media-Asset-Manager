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
             data-disk="ftp_mpeg"
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

@push('scripts')
<script>

    window.fileExplorerConfig = {
        local: @json(config('filesystems.disks.external_local.root')),
        pad: @json(config('filesystems.disks.ftp_pad.root')),
        arch: @json(config('filesystems.disks.ftp_mpeg.root'))
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
</script>
@endpush
