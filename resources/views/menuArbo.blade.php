{{-- ATTENTION : Le bloc PHP initial doit être retiré car les variables locales 
    (comme $directory_local) doivent être définies dans le Controller ou un View Composer 
    et passées à la vue. --}}

<div class="main-menuArbo">
    <div class="dossiers">
        <div class="menuArbo local">
            {{-- La variable $directory_local doit être passée par le contrôleur --}}
            @php echo controleurArborescence($directory_local, ESPACE_LOCAL); @endphp
        </div>

        {{-- Logique pour NAS PAD (NAS_PAD, LOGIN_NAS_PAD, PASSWORD_NAS_PAD doivent être disponibles comme constantes) --}}
        @if (controleurVerifierFTP(NAS_PAD, LOGIN_NAS_PAD, PASSWORD_NAS_PAD))
            <div class="menuArbo PAD">
                @php echo controleurArborescence("", NAS_PAD); @endphp
            </div>
        @endif

        {{-- Logique pour NAS ARCH --}}
        @if (controleurVerifierFTP(NAS_ARCH, LOGIN_NAS_ARCH, PASSWORD_NAS_ARCH))
            <div class="menuArbo ARCH">
                @php echo controleurArborescence("", NAS_ARCH); @endphp
            </div>
        @endif
    </div>

    <div class="radio">
        <label>
            Stockage local
            <input type="radio" name="a" id="local">
        </label>

        @if (controleurVerifierFTP(NAS_PAD, LOGIN_NAS_PAD, PASSWORD_NAS_PAD))
            <label>
                NAS PAD
                <input type="radio" name="a" id="PAD">
            </label>
        @endif

        @if (controleurVerifierFTP(NAS_ARCH, LOGIN_NAS_ARCH, PASSWORD_NAS_ARCH))
            <label>
                NAS ARCH
                <input type="radio" name="a" id="ARCH">
            </label>
        @endif
    </div>

    <button onclick="ouvrirMenuArbo()">
        <svg fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"/>
        </svg>
    </button>
</div>


<div class="voile"></div>

{{-- Les scripts sont déplacés dans le stack 'scripts' --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        gestion_click_dossier();
        gestionOngletsArborescence();
    });    
</script>
@endpush