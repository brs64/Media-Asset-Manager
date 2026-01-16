@foreach ($items as $item)
    <div class="tree-item {{ $item['type'] }} mb-1">

        {{-- 📁 DOSSIER --}}
        @if ($item['type'] === 'folder')
            <div class="dossier-label cursor-pointer text-gray-200 hover:text-white select-none flex items-center gap-2"
                 data-disk="{{ $item['disk'] }}"
                 data-path="{{ $item['path'] }}"
                 onclick="loadFolder(this)">
                <span class="icon text-yellow-500">📁</span>
                <span class="font-medium">{{ $item['name'] }}</span>
            </div>

            {{-- Container vide pour lazy-loading --}}
            <div class="dossier-content hidden pl-4 border-l border-gray-600 ml-1 mt-1"></div>

        {{-- 🎬 VIDÉO --}}
        @elseif ($item['type'] === 'video')
            <div class="video-link text-blue-400 hover:text-blue-200 flex items-center gap-2 cursor-pointer">
                <span class="icon">🎬</span>
                <span>{{ $item['name'] }}</span>
            </div>

        @endif

    </div>
@endforeach
