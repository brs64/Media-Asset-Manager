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
                <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}" data-prof-id="{{ $prof->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        {{ $prof->nom }} {{ $prof->prenom }}
                    </td>

                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        <input type="checkbox"
                               data-permission="can_edit_video"
                               {{ $prof->is_admin || $prof->can_edit_video ? 'checked' : '' }}
                               {{ $prof->is_admin ? 'disabled' : '' }}
                               class="permission-toggle h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        <input type="checkbox"
                               data-permission="can_broadcast_video"
                               {{ $prof->is_admin || $prof->can_broadcast_video ? 'checked' : '' }}
                               {{ $prof->is_admin ? 'disabled' : '' }}
                               class="permission-toggle h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                        <input type="checkbox"
                               data-permission="can_delete_video"
                               {{ $prof->is_admin || $prof->can_delete_video ? 'checked' : '' }}
                               {{ $prof->is_admin ? 'disabled' : '' }}
                               class="permission-toggle h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="text-center px-3 py-4 text-sm text-gray-500">
                         <input type="checkbox"
                               data-permission="can_administer"
                               {{ $prof->is_admin || $prof->can_administer ? 'checked' : '' }}
                               {{ $prof->is_admin ? 'disabled' : '' }}
                               class="permission-toggle h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>

                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @if(!$prof->is_admin)
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
@endsection

@push('admin_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer les changements de permissions
    const permissionToggles = document.querySelectorAll('.permission-toggle');

    permissionToggles.forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const row = this.closest('tr');
            const profId = row.dataset.profId;
            const permission = this.dataset.permission;
            const value = this.checked;

            try {
                const response = await fetch(`/admin/professeurs/${profId}/permissions`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        permission: permission,
                        value: value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Si l'utilisateur devient admin, désactiver et cocher tous les toggles
                    if (data.is_admin) {
                        row.querySelectorAll('.permission-toggle').forEach(t => {
                            t.checked = true;
                            t.disabled = true;
                        });
                        // Cacher le bouton supprimer
                        const deleteForm = row.querySelector('form');
                        if (deleteForm) {
                            deleteForm.style.display = 'none';
                        }
                    } else if (permission === 'can_administer' && !value) {
                        // Si on retire le droit d'administrer, réactiver les toggles
                        row.querySelectorAll('.permission-toggle').forEach(t => {
                            if (t.dataset.permission !== 'can_administer') {
                                t.disabled = false;
                            }
                        });
                        // Réafficher le bouton supprimer
                        const deleteForm = row.querySelector('form');
                        if (deleteForm) {
                            deleteForm.style.display = 'block';
                        }
                    }

                    // Afficher un message de succès (optionnel)
                    console.log('Permission mise à jour avec succès');
                } else {
                    // En cas d'erreur, remettre le toggle dans son état précédent
                    this.checked = !value;
                    alert('Erreur: ' + (data.message || 'Impossible de mettre à jour la permission'));
                }
            } catch (error) {
                // En cas d'erreur réseau, remettre le toggle dans son état précédent
                this.checked = !value;
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        });
    });
});
</script>
@endpush