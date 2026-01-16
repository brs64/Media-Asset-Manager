@extends('layouts.admin')

@section('tab_content')
<div class="text-center max-w-4xl mx-auto py-10">
    
    <div class="border-b-2 border-[#b91c1c] mb-10 pb-4">
        <h2 class="text-3xl font-bold text-gray-800">Fonction de réconciliation</h2>
    </div>

    @if(session('reconciliation_result'))
        <div class="mb-8 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 text-left shadow-sm">
            <p class="font-bold">Résultat :</p>
            <p>{{ session('reconciliation_result') }}</p>
        </div>
    @endif

    <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-100">
        <p class="text-gray-600 mb-8 text-lg">
            Cette fonction permet de synchroniser la base de données avec les fichiers physiques présents sur les serveurs de stockage.
            <br>
            <span class="text-sm italic text-gray-500">(Cette opération peut prendre quelques minutes)</span>
        </p>

        <form action="{{ route('admin.reconciliation.run') }}" method="POST">
            @csrf
            <button type="submit" class="bg-[#b91c1c] hover:bg-red-800 text-white font-bold text-xl py-4 px-10 rounded shadow-lg transform transition hover:scale-105">
                Lancer la réconciliation
            </button>
        </form>
    </div>
</div>
@endsection