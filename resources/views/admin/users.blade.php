@extends('layouts.admin')

@section('tab_content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800 border-b pb-2">Gérer les utilisateurs</h2>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"></th>
                    <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Modifier la vidéo</th>
                    <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Diffuser la vidéo</th>
                    <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Supprimer la vidéo</th>
                    <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Administrer le site</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach($professeurs as $prof)
                <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        {{ $prof->nom }} {{ $prof->prenom }}
                    </td>

                    @php 
                        $isAdmin = $prof->role == 'admin'; // Adjust based on your Role logic
                    @endphp

                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        <input type="checkbox" {{ $isAdmin ? 'disabled checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        <input type="checkbox" {{ $isAdmin ? 'disabled checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        <input type="checkbox" {{ $isAdmin ? 'disabled checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                         <input type="checkbox" {{ $isAdmin ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>

                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @if(!$isAdmin)
                        <form action="{{ route('admin.professeurs.delete', $prof->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-8 p-6 bg-gray-50 border rounded-lg shadow-sm">
        <h3 class="text-lg font-bold mb-6 text-gray-800 border-b pb-2">Ajouter un professeur</h3>
        
        <form action="{{ route('admin.professeurs.create') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
            @csrf
            
            <div class="flex flex-col">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nom</label>
                <input type="text" name="nom" required 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 h-10">
            </div>

            <div class="flex flex-col">
                <label class="block text-sm font-bold text-gray-700 mb-2">Prénom</label>
                <input type="text" name="prenom" required 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 h-10">
            </div>

            <div class="flex flex-col">
                <label class="block text-sm font-bold text-gray-700 mb-2">Identifiant</label>
                <input type="text" name="identifiant" required 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 h-10">
            </div>

            <div class="flex flex-col">
                <label class="block text-sm font-bold text-gray-700 mb-2">Mot de passe</label>
                <input type="password" name="mot_de_passe" required 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 h-10">
            </div>

            <div class="flex flex-col">
                <button type="submit" class="bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 h-10 shadow transition-colors">
                    Ajouter
                </button>
            </div>
        </form>
    </div>
</div>
<hr class="my-10 border-gray-300">

<div class="space-y-6 mt-10">
    <h2 class="text-2xl font-bold text-gray-800 border-b pb-2">Gérer les élèves</h2>

    <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nom & Prénom</th>
                    <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Nombre de participations</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach($eleves as $eleve)
                <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        {{ $eleve->nom }} {{ $eleve->prenom }}
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        {{ $eleve->participations_count ?? 0 }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <form action="{{ route('admin.eleves.delete', $eleve->id) }}" method="POST" onsubmit="return confirm('Supprimer cet élève ? Cela ne supprimera pas les vidéos associées.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Formulaire d'ajout d'élève --}}
    <div class="mt-8 p-6 bg-gray-50 border rounded-lg shadow-sm">
        <h3 class="text-lg font-bold mb-6 text-gray-800 border-b pb-2">Ajouter un élève</h3>
        
        <form action="{{ route('admin.eleves.create') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            @csrf
            <div class="flex flex-col">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nom</label>
                <input type="text" name="nom" required 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 h-10">
            </div>

            <div class="flex flex-col">
                <label class="block text-sm font-bold text-gray-700 mb-2">Prénom</label>
                <input type="text" name="prenom" required 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 h-10">
            </div>

            <div class="flex flex-col">
                <button type="submit" class="bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 h-10 shadow transition-colors">
                    Ajouter
                </button>
            </div>
        </form>
    </div>
</div>
@endsection