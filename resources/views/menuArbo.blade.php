<div class="main-menuArbo">
    <div class="dossiers">
        
        <div class="menuArbo local" id="tree-local">
            <h3 class="text-white p-2 font-bold bg-gray-800">Espace Local</h3>
            <div class="tree-container">
                {{-- @dump($localTree) --}}

                @if(isset($localTree) && count($localTree) > 0)
                    @include('explorer.tree-item', ['items' => $localTree])
                @else
                    <p class="text-white p-4 italic text-sm">Dossier vide ou chemin incorrect.</p>
                @endif
            </div>
        </div>

        <div class="menuArbo PAD hidden" id="tree-pad">
            <h3 class="text-white p-2 font-bold bg-blue-900">NAS PAD</h3>
            <div class="tree-container">
                @if(isset($nasPadTree) && count($nasPadTree) > 0)
                    @include('explorer.tree-item', ['items' => $nasPadTree])
                @else
                    <p class="text-white p-4 italic text-sm">Non connecté ou vide.</p>
                @endif
            </div>
        </div>

        <div class="menuArbo ARCH hidden" id="tree-arch">
            <h3 class="text-white p-2 font-bold bg-green-900">NAS ARCH</h3>
            <div class="tree-container">
                @if(isset($nasArchTree) && count($nasArchTree) > 0)
                    @include('explorer.tree-item', ['items' => $nasArchTree])
                @else
                    <p class="text-white p-4 italic text-sm">Non connecté ou vide.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="radio flex gap-2 p-2">
        <label class="cursor-pointer text-white">
            <input type="radio" name="source_choix" value="local" checked onclick="changerSource('local')">
            Stockage Local
        </label>

        <label class="cursor-pointer text-white">
            <input type="radio" name="source_choix" value="pad" onclick="changerSource('pad')">
            NAS PAD
        </label>
        
        {{-- Optional: Uncomment if you want the button for ARCH --}}
        {{-- 
        <label class="cursor-pointer text-white">
            <input type="radio" name="source_choix" value="arch" onclick="changerSource('arch')">
            NAS ARCH
        </label> 
        --}}
    </div>

    <button onclick="toggleMenuArbo()" class="absolute top-2 right-[-30px] bg-gray-800 text-white p-2 rounded-r">
        <svg fill="currentColor" viewBox="0 0 16 16" width="20" height="20">
            <path fill-rule="evenodd" d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"/>
        </svg>
    </button>
</div>

<div class="voile" onclick="toggleMenuArbo()"></div>

@push('scripts')
<script>
    // OPEN/CLOSE MENU
    window.toggleMenuArbo = function() {
        document.querySelector('.main-menuArbo').classList.toggle('ouvert');
        document.querySelector('.voile').classList.toggle('ouvert');
    };

    // OPEN/CLOSE FOLDERS (Recursive)
    window.toggleFolder = function(element) {
        let childrenContainer = element.nextElementSibling;
        if(childrenContainer) {
            childrenContainer.classList.toggle('hidden');
        }
    };

    // SWITCH TABS (Local vs PAD vs ARCH)
    window.changerSource = function(source) {
        // 1. Define all possible IDs
        const ids = ['tree-local', 'tree-pad', 'tree-arch'];

        // 2. Hide ALL of them safely
        ids.forEach(function(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
            }
        });
        
        // 3. Show only the selected one safely
        const target = document.getElementById('tree-' + source);
        if (target) {
            target.classList.remove('hidden');
        } else {
            console.warn("Target tab not found: " + source);
        }
    }
</script>
@endpush