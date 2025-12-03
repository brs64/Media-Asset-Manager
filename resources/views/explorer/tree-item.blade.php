{{-- resources/views/explorer/tree-item.blade.php --}}

@foreach ($items as $item)
    <div class="tree-item {{ $item['type'] }} mb-1">
        
        {{-- CASE: FOLDER --}}
        @if ($item['type'] === 'folder')
            {{-- 
               CHANGES: 
               1. 'cursor-pointer': Adds the hand cursor.
               2. 'text-gray-200': Makes text visible (white-ish) on dark background.
               3. 'select-none': Prevents text highlighting when clicking repeatedly.
               4. 'flex items-center gap-2': Aligns the icon and text nicely.
            --}}
            <div class="dossier-label cursor-pointer text-gray-200 hover:text-white select-none flex items-center gap-2" 
                 onclick="toggleFolder(this)">
                <span class="icon text-yellow-500">📁</span> 
                <span class="font-medium">{{ $item['name'] }}</span>
            </div>

            {{-- 
               CHANGES:
               1. 'border-gray-600': Darker border line to match dark theme.
               2. 'ml-1 mt-1': Slight spacing for hierarchy.
            --}}
            <div class="dossier-content hidden pl-4 border-l border-gray-600 ml-1 mt-1">
                {{-- RECURSION --}}
                @include('explorer.tree-item', ['items' => $item['children']])
            </div>

        {{-- CASE: VIDEO --}}
        @elseif ($item['type'] === 'video')
            {{--
               CHANGES:
               1. Added href to the 'stream.file' route.
               2. 'text-blue-400': Lighter blue so it is readable on dark background.
               TODO: stream.file route not yet implemented
            --}}
            {{-- <a href="{{ route('stream.file', ['disk' => $item['disk'] ?? 'external_local', 'path' => $item['path']]) }}"
               target="_blank"
               class="video-link text-blue-400 hover:text-blue-200 hover:underline flex items-center gap-2">
                <span class="icon">🎬</span>
                <span>{{ $item['name'] }}</span>
            </a> --}}
            <div class="video-link text-blue-400 flex items-center gap-2">
                <span class="icon">🎬</span>
                <span>{{ $item['name'] }}</span>
            </div>

        {{-- CASE: FILE --}}
        @else
            <div class="file-item text-gray-500 text-sm flex items-center gap-2">
                <span class="icon opacity-70">📄</span> 
                <span>{{ $item['name'] }}</span>
            </div>
        @endif
        
    </div>
@endforeach