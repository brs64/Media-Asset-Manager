<div class="main-menuArbo">
    <div class="dossiers">
        
        <!-- LOCAL STORAGE -->
        <div class="menuArbo local">
            {{-- TODO: Décommenter une fois que la fonction est disponible
            @php echo controleurArborescence($directory_local ?? '', 'ESPACE_LOCAL'); @endphp
            --}}
            <p style="color:white; padding: 20px;">(Contenu Local - À implémenter)</p>
        </div>

        <!-- NAS PAD -->
        {{-- TODO: Remplacer par des variables passées depuis le controller --}}
        @if (defined('NAS_PAD') && false) 
            <div class="menuArbo PAD">
                @php echo controleurArborescence("", NAS_PAD); @endphp
            </div>
        @endif

        <!-- NAS ARCH -->
        @if (defined('NAS_ARCH') && false)
            <div class="menuArbo ARCH">
                @php echo controleurArborescence("", NAS_ARCH); @endphp
            </div>
        @endif
    </div>

    <!-- TABS / RADIO BUTTONS -->
    <div class="radio">
        <label>
            Stockage local
            <input type="radio" name="a" id="local" checked>
        </label>

        {{-- Mockup statique pour le design --}}
        <label>
            NAS PAD
            <input type="radio" name="a" id="PAD">
        </label>
    </div>

    <!-- TOGGLE BUTTON -->
    <button onclick="toggleMenuArbo()">
        <svg fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"/>
        </svg>
    </button>
</div>

<!-- OVERLAY (Click to close) -->
<div class="voile" onclick="toggleMenuArbo()"></div>

@push('scripts')
<script>
    // Fonction globale pour le bouton onclick
    window.toggleMenuArbo = function() {
        const menu = document.querySelector('.main-menuArbo');
        const voile = document.querySelector('.voile');
        
        menu.classList.toggle('ouvert');
        voile.classList.toggle('ouvert');
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Placeholder pour vos anciennes fonctions JS
        if(typeof gestion_click_dossier === 'function') gestion_click_dossier();
        if(typeof gestionOngletsArborescence === 'function') gestionOngletsArborescence();
    });    
</script>
@endpush