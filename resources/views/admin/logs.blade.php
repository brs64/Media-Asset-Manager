@extends('layouts.admin')

@section('tab_content')
<div class="flex flex-col h-full">
    <div class="border-b border-gray-200 pb-4 mb-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">Consulter les logs</h2>
        <span class="text-sm text-gray-500">Fichier : {{ config('btsplay.logs.general') }}</span>
    </div>
    
    <div class="bg-[#1e1e1e] text-green-400 font-mono text-sm p-4 rounded shadow-inner h-[600px] overflow-y-auto border-4 border-gray-800">
        @forelse($logs as $line)
            <div class="mb-1 border-b border-gray-800 pb-0.5 hover:bg-gray-800 transition-colors">
                {{ $line }}
            </div>
        @empty
            <div class="text-gray-500 italic">Aucun log disponible ou fichier vide.</div>
        @endforelse
    </div>

    <div class="mt-4 text-right">
        <a href="{{ route('admin.logs.download') }}" class="text-blue-600 hover:underline text-sm font-bold">
            Télécharger le fichier complet (.log)
        </a>
    </div>
</div>
@endsection