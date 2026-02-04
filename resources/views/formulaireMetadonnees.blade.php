@extends('layouts.app')

@push('styles')
    @vite(['resources/css/formulaire.css'])
@endpush

@section('content')

<div class="form-container">
    <form method="POST" action="{{ isset($media) ? route('medias.update', $media->id) : route('medias.store') }}" class="metadata-form">
        @csrf
        @if(isset($media))
            @method('PUT')
        @endif

        {{-- Display validation errors --}}
        @if($errors->any())
            <div style="background: #fee; border: 1px solid #f00; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <strong>Erreurs:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Thumbnail Section (top) --}}
        @isset($media)
            <div class="thumbnail-container" style="max-width: 600px; margin: 0 auto 30px;">
                <img src="{{ route('thumbnails.show', $media->id) }}" alt="Miniature" class="thumbnail-image">
            </div>
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 1.5em;">{{ $media->mtd_tech_titre }}</h2>
        @else
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 1.5em;">Nouveau média</h2>
        @endisset

        {{-- Form Fields (below) --}}
        <div style="width: 100%; max-width: 900px; margin: 0 auto; padding: 0 20px;">
            <div style="width: 100%; display: flex; flex-direction: column;">
                <h2 class="team-title">Métadonnées du média</h2>

                {{-- Titre --}}
                <div class="form-field">
                    <label for="mtd_tech_titre" class="form-label">Titre *</label>
                    <input type="text" id="mtd_tech_titre" name="mtd_tech_titre" class="form-input" required maxlength="255"
                           value="{{ old('mtd_tech_titre', $media->mtd_tech_titre ?? '') }}">
                </div>

                {{-- Professeur référent --}}
                <div class="form-field">
                    <label for="professeur_id" class="form-label">Professeur référent</label>
                    <select id="professeur_id" name="professeur_id" class="form-select">
                        <option value="">-- Aucun --</option>
                        @foreach($professeurs as $prof)
                            <option value="{{ $prof->id }}"
                                {{ old('professeur_id', $media->professeur_id ?? '') == $prof->id ? 'selected' : '' }}>
                                {{ $prof->nom }} {{ $prof->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Projets --}}
                <div class="form-field">
                    <label class="form-label">Projets</label>
                    <div id="projets-container">
                        @php
                            $oldProjetIds = old('projet_ids', []);
                            if (isset($media) && empty($oldProjetIds)) {
                                $oldProjetIds = $media->projets->pluck('id')->toArray();
                            }
                        @endphp

                        @forelse($oldProjetIds as $index => $projetId)
                            <div class="projet-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                <select name="projet_ids[]" class="form-select" style="flex: 1;">
                                    <option value="">-- Sélectionner un projet --</option>
                                    @foreach($projets as $projet)
                                        <option value="{{ $projet->id }}" {{ $projetId == $projet->id ? 'selected' : '' }}>
                                            {{ $projet->libelle }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="remove-projet" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
                            </div>
                        @empty
                            {{-- Empty state --}}
                        @endforelse
                    </div>
                    <button type="button" id="add-projet" class="form-button" style="margin-top: 10px; background: #28a745;">+ Ajouter un projet</button>
                </div>

                {{-- Description --}}
                <div class="form-field">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-input" rows="4" maxlength="5000">{{ old('description', $media->description ?? '') }}</textarea>
                </div>

                {{-- Promotion --}}
                <div class="form-field">
                    <label for="promotion" class="form-label">Promotion</label>
                    <input type="text" id="promotion" name="promotion" class="form-input" maxlength="255"
                           value="{{ old('promotion', $media->promotion ?? '') }}">
                </div>

                {{-- Type --}}
                <div class="form-field">
                    <label for="type" class="form-label">Type</label>
                    <input type="text" id="type" name="type" class="form-input" maxlength="255"
                           value="{{ old('type', $media->type ?? '') }}">
                </div>

                {{-- Theme --}}
                <div class="form-field">
                    <label for="theme" class="form-label">Thème</label>
                    <input type="text" id="theme" name="theme" class="form-input" maxlength="255"
                           value="{{ old('theme', $media->theme ?? '') }}">
                </div>

                {{-- Participations --}}
                <div class="form-field">
                    <label class="form-label">Participations (Élèves & Rôles)</label>
                    <div id="participations-container">
                        @php
                            $oldParticipations = old('participations', []);
                            if (isset($media) && empty($oldParticipations)) {
                                $oldParticipations = $media->participations->map(fn($p) => [
                                    'eleve_id' => $p->eleve_id,
                                    'role_id' => $p->role_id
                                ])->toArray();
                            }
                        @endphp

                        @forelse($oldParticipations as $index => $participation)
                            <div class="participation-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                <select name="participations[{{ $index }}][eleve_id]" class="form-select" style="flex: 1;" required>
                                    <option value="">-- Élève --</option>
                                    @foreach($eleves as $eleve)
                                        <option value="{{ $eleve->id }}" {{ $participation['eleve_id'] == $eleve->id ? 'selected' : '' }}>
                                            {{ $eleve->nom }} {{ $eleve->prenom }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="participations[{{ $index }}][role_id]" class="form-select" style="flex: 1;" required>
                                    <option value="">-- Rôle --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ $participation['role_id'] == $role->id ? 'selected' : '' }}>
                                            {{ $role->libelle }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="button" class="remove-participation" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
                            </div>
                        @empty
                            {{-- Empty state --}}
                        @endforelse
                    </div>
                    <button type="button" id="add-participation" class="form-button" style="margin-top: 10px; background: #28a745;">+ Ajouter une participation</button>
                </div>

                {{-- Propriétés libres --}}
                <div class="form-field">
                    <label class="form-label">Propriétés personnalisées</label>

                    <div id="properties-container">
                        @php
                            $oldProperties = old('properties', []);

                            if (isset($media) && empty($oldProperties)) {
                                $oldProperties = collect($media->properties ?? [])
                                    ->map(fn ($value, $key) => ['key' => $key, 'value' => $value])
                                    ->values()
                                    ->toArray();
                            }
                        @endphp

                        @foreach($oldProperties as $index => $property)
                            <div class="property-item" style="display:flex; gap:10px; margin-bottom:10px;">
                                <input
                                        type="text"
                                        name="properties[{{ $index }}][key]"
                                        class="form-input"
                                        placeholder="Nom du champ"
                                        value="{{ $property['key'] ?? '' }}"
                                >

                                <input
                                        type="text"
                                        name="properties[{{ $index }}][value]"
                                        class="form-input"
                                        placeholder="Valeur"
                                        value="{{ $property['value'] ?? '' }}"
                                >

                                <button type="button" class="remove-property">×</button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-property" class="form-button">
                        + Ajouter une propriété
                    </button>
                </div>

            </div>{{-- Close flex column --}}
        </div>{{-- Close form wrapper --}}

        {{-- Buttons --}}
        <div class="form-buttons-container">
            @isset($media)
                <a href="{{ route('medias.show', $media->id) }}" class="form-button form-button-secondary">Retour</a>
            @else
                <a href="{{ route('home') }}" class="form-button form-button-secondary">Retour</a>
            @endisset

            <div class="bouton-droit">
                <button type="submit" class="form-button">Enregistrer</button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data for dynamic generation
    const projets = @json($projets);
    const eleves = @json($eleves);
    const roles = @json($roles);

    // ==== PROJETS ====
    const projetsContainer = document.getElementById('projets-container');
    const addProjetButton = document.getElementById('add-projet');

    addProjetButton.addEventListener('click', function() {
        const item = document.createElement('div');
        item.className = 'projet-item';
        item.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';

        item.innerHTML = `
            <select name="projet_ids[]" class="form-select" style="flex: 1;">
                <option value="">-- Sélectionner un projet --</option>
                ${projets.map(p => `<option value="${p.id}">${p.libelle}</option>`).join('')}
            </select>
            <button type="button" class="remove-projet" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
        `;

        projetsContainer.appendChild(item);
    });

    // Remove projet (delegated event)
    projetsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-projet')) {
            e.target.closest('.projet-item').remove();
        }
    });

    // ==== PARTICIPATIONS ====
    const participationsContainer = document.getElementById('participations-container');
    const addParticipationButton = document.getElementById('add-participation');

    let participationIndex = {{ count($oldParticipations ?? []) }};

    addParticipationButton.addEventListener('click', function() {
        const item = document.createElement('div');
        item.className = 'participation-item';
        item.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';

        item.innerHTML = `
            <select name="participations[${participationIndex}][eleve_id]" class="form-select" style="flex: 1;" required>
                <option value="">-- Élève --</option>
                ${eleves.map(e => `<option value="${e.id}">${e.nom} ${e.prenom}</option>`).join('')}
            </select>

            <select name="participations[${participationIndex}][role_id]" class="form-select" style="flex: 1;" required>
                <option value="">-- Rôle --</option>
                ${roles.map(r => `<option value="${r.id}">${r.libelle}</option>`).join('')}
            </select>

            <button type="button" class="remove-participation" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
        `;

        participationsContainer.appendChild(item);
        participationIndex++;
    });

    // Remove participation (delegated event)
    participationsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-participation')) {
            e.target.closest('.participation-item').remove();
        }
    });
});

const propertiesContainer = document.getElementById('properties-container');
const addPropertyButton = document.getElementById('add-property');

let propertyIndex = {{ count($oldProperties ?? []) }};

addPropertyButton.addEventListener('click', () => {
    const div = document.createElement('div');
    div.className = 'property-item';
    div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px;';

    div.innerHTML = `
        <input type="text"
               name="properties[${propertyIndex}][key]"
               class="form-input"
               placeholder="Nom libre">

        <input type="text"
               name="properties[${propertyIndex}][value]"
               class="form-input"
               placeholder="Valeur libre">

        <button type="button" class="remove-property">×</button>
    `;

    propertiesContainer.appendChild(div);
    propertyIndex++;
});

propertiesContainer.addEventListener('click', e => {
    if (e.target.classList.contains('remove-property')) {
        e.target.closest('.property-item').remove();
    }
});

</script>
@endpush
