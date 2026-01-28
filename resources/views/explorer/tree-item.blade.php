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

            @php $media = $item['media'] ?? null; @endphp

            <div class="flex items-center gap-2
        @if (!$media)
            text-gray-500
        @elseif (!$media->chemin_local)
            text-orange-400
        @else
            text-green-400 hover:text-green-200
        @endif">

                <span>🎬</span>

                {{-- encodée --}}
                @if ($media && $media->chemin_local)
                    <a href="{{ route('medias.show', $media) }}" class="underline">
                        {{ $item['name'] }}
                    </a>

                    {{-- en BDD mais pas encodée --}}
                @elseif ($media)
                    <span class="italic">
                {{ $item['name'] }} (En attente)
            </span>

                    {{-- pas en BDD --}}
                @else
                    <span>{{ $item['name'] }}</span>
                @endif
            </div>

        @endif

    </div>
@endforeach
