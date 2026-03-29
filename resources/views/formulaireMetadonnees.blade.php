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
                <img loading="lazy" src="{{ route('thumbnails.show', $media->id) }}" alt="Miniature" class="thumbnail-image" onerror="this.onerror=null;this.src='{{ asset('images/placeholder-miniature.webp') }}'">
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
                            $projetList = $projets->pluck('libelle')->toArray();
                            $oldProjetNoms = old('projet_noms', (isset($media) ? $media->projets->pluck('libelle')->toArray() : []));
                        @endphp

                        @foreach($oldProjetNoms as $projetLibelle)
                            <div class="projet-item autocomplete-wrapper" 
                                data-options="{{ json_encode($projetList) }}"
                                x-data="searchableInput(@js($projetLibelle))" 
                                style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                
                                <div class="grow relative">
                                    <input type="text" name="projet_noms[]" class="form-select w-full" 
                                        autocomplete="off" x-model="value" @focus="open = true" 
                                        @click.away="open = false" placeholder="Nom du projet">
                                    
                                    <div x-show="open && filteredOptions.length > 0" class="autocomplete-list" x-cloak>
                                        <template x-for="opt in filteredOptions" :key="opt">
                                            <div class="autocomplete-item" x-text="opt" @click="selectOption(opt)"></div>
                                        </template>
                                    </div>
                                </div>
                                <button type="button" class="remove-projet" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
                            </div>
                        @endforeach
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

                <div class="form-field">
                    <label class="form-label">Participations (Élèves & Rôles)</label>
                    <div id="participations-container">
                        @php
                            $eleveList = $eleves->map(fn($e) => trim($e->nom . ' ' . $e->prenom))->toArray();
                            $roleList = $roles->pluck('libelle')->toArray();

                            $oldParticipations = old('participations', []);
                            if (isset($media) && empty($oldParticipations)) {
                                $oldParticipations = $media->participations->map(fn($p) => [
                                    'eleve_nom' => trim(($p->eleve->nom ?? '') . ' ' . ($p->eleve->prenom ?? '')),
                                    'role_nom' => $p->role->libelle ?? ''
                                ])->toArray();
                            }
                        @endphp

                        @foreach($oldParticipations as $index => $participation)
                            <div class="participation-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start;">
                                
                                {{-- Student --}}
                                <div class="grow autocomplete-wrapper" 
                                    data-options="{{ json_encode($eleveList) }}"
                                    x-data="searchableInput(@js($participation['eleve_nom'] ?? ''))">
                                    <input type="text" name="participations[{{ $index }}][eleve_nom]" 
                                        class="form-select w-full" autocomplete="off" placeholder="Nom de l'élève"
                                        x-model="value" @focus="open = true" @click.away="open = false" required>
                                    
                                    <div x-show="open && filteredOptions.length > 0" class="autocomplete-list" x-cloak>
                                        <template x-for="opt in filteredOptions" :key="opt">
                                            <div class="autocomplete-item" x-text="opt" @click="selectOption(opt)"></div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Role --}}
                                <div class="grow autocomplete-wrapper" 
                                    data-options="{{ json_encode($roleList) }}"
                                    x-data="searchableInput(@js($participation['role_nom'] ?? ''))">
                                    <input type="text" name="participations[{{ $index }}][role_nom]" 
                                        class="form-select w-full" autocomplete="off" placeholder="Rôle"
                                        x-model="value" @focus="open = true" @click.away="open = false" required>
                                    
                                    <div x-show="open && filteredOptions.length > 0" class="autocomplete-list" x-cloak>
                                        <template x-for="opt in filteredOptions" :key="opt">
                                            <div class="autocomplete-item" x-text="opt" @click="selectOption(opt)"></div>
                                        </template>
                                    </div>
                                </div>

                                <button type="button" class="remove-participation" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer; height: 38px;">×</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-participation" class="form-button" style="margin-top: 10px; background: #28a745;">+ Ajouter une participation</button>
                </div>

                {{-- Liste de suggestions (à placer juste ici) --}}
                <datalist id="eleves-list">
                    @foreach($eleves as $eleve)
                        <option value="{{ $eleve->nom }} {{ $eleve->prenom }}">
                    @endforeach
                </datalist>

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
                                <input type="text" name="properties[{{ $index }}][key]" class="form-input" 
                                       placeholder="Nom du champ" value="{{ $property['key'] ?? '' }}">
                                <input type="text" name="properties[{{ $index }}][value]" class="form-input" 
                                       placeholder="Valeur" value="{{ $property['value'] ?? '' }}">
                                <button type="button" class="remove-property" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
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
document.addEventListener('alpine:init', () => {
    Alpine.data('searchableInput', (initialValue) => ({
        value: initialValue,
        open: false,
        options: [],
        init() {
            const raw = this.$el.getAttribute('data-options');
            this.options = raw ? JSON.parse(raw) : [];
        },
        get filteredOptions() {
            if (!this.value) return this.options.slice(0, 10);
            const search = this.value.toLowerCase();
            return this.options
                .filter(opt => opt.toLowerCase().includes(search))
                .slice(0, 15); 
        },
        selectOption(opt) {
            this.value = opt;
            this.open = false;
        }
    }));
});

document.addEventListener('DOMContentLoaded', function() {
    // 1. Prepare clean data arrays
    const projets = @json($projets->pluck('libelle'));
    const eleves = @json($eleves->map(fn($e) => trim($e->nom . ' ' . $e->prenom)));
    const roles = @json($roles->pluck('libelle'));

    // ==== PROJETS ====
    const projetsContainer = document.getElementById('projets-container');
    const addProjetButton = document.getElementById('add-projet');

    addProjetButton.addEventListener('click', function() {
        const item = document.createElement('div');
        item.className = 'projet-item';
        item.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';

        const wrapper = document.createElement('div');
        wrapper.className = 'grow autocomplete-wrapper';
        wrapper.setAttribute('data-options', JSON.stringify(projets));
        wrapper.setAttribute('x-data', "searchableInput('')");

        wrapper.innerHTML = `
            <div class="grow relative">
                <input type="text" name="projet_noms[]" class="form-select w-full" autocomplete="off" 
                    x-model="value" @focus="open = true" @click.away="open = false" placeholder="Nom du projet">
                <div x-show="open && filteredOptions.length > 0" class="autocomplete-list" x-cloak>
                    <template x-for="opt in filteredOptions" :key="opt">
                        <div class="autocomplete-item" x-text="opt" @click="selectOption(opt)"></div>
                    </template>
                </div>
            </div>
        `;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-projet';
        removeBtn.style.cssText = 'background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;';
        removeBtn.innerText = '×';

        item.appendChild(wrapper);
        item.appendChild(removeBtn);
        projetsContainer.appendChild(item);
        
        if (window.Alpine) { window.Alpine.initTree(item); }
    });

    // ==== PARTICIPATIONS ====
    const participationsContainer = document.getElementById('participations-container');
    const addParticipationButton = document.getElementById('add-participation');

    let participationIndex = document.querySelectorAll('.participation-item').length;

    addParticipationButton.addEventListener('click', function() {
        const item = document.createElement('div');
        item.className = 'participation-item';
        item.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start;';

        const createField = (name, dataList) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'grow autocomplete-wrapper';
            wrapper.setAttribute('data-options', JSON.stringify(dataList));
            wrapper.setAttribute('x-data', "searchableInput('')");

            wrapper.innerHTML = `
                <input type="text" name="${name}" 
                    class="form-select w-full" autocomplete="off" 
                    placeholder="${name.includes('role') ? 'Rôle' : 'Nom de l\'élève'}" 
                    x-model="value" @focus="open = true" @click.away="open = false" required>
                <div x-show="open && filteredOptions.length > 0" class="autocomplete-list" x-cloak>
                    <template x-for="opt in filteredOptions" :key="opt">
                        <div class="autocomplete-item" x-text="opt" @click="selectOption(opt)"></div>
                    </template>
                </div>
            `;
            return wrapper;
        };

        const studentField = createField(`participations[${participationIndex}][eleve_nom]`, eleves);
        const roleField = createField(`participations[${participationIndex}][role_nom]`, roles);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-participation';
        removeBtn.style.cssText = 'background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer; height: 38px;';
        removeBtn.innerText = '×';

        item.appendChild(studentField);
        item.appendChild(roleField);
        item.appendChild(removeBtn);

        participationsContainer.appendChild(item);
        
        if (window.Alpine) { window.Alpine.initTree(item); }
        participationIndex++;
    });

    // ==== GLOBAL EVENT DELEGATION ====
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-projet')) {
            e.target.closest('.projet-item').remove();
        }
        if (e.target.classList.contains('remove-participation')) {
            e.target.closest('.participation-item').remove();
        }
        if (e.target.classList.contains('remove-property')) {
            e.target.closest('.property-item').remove();
        }
    });

    // ==== PROPERTIES ====
    const propertiesContainer = document.getElementById('properties-container');
    const addPropertyButton = document.getElementById('add-property');
    let propertyIndex = document.querySelectorAll('.property-item').length;

    addPropertyButton.addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'property-item';
        div.style.cssText = 'display:flex; gap:10px; margin-bottom:10px;';
        div.innerHTML = `
            <input type="text" name="properties[${propertyIndex}][key]" class="form-input" placeholder="Nom du champ">
            <input type="text" name="properties[${propertyIndex}][value]" class="form-input" placeholder="Valeur">
            <button type="button" class="remove-property" style="background: #dc3545; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer;">×</button>
        `;
        propertiesContainer.appendChild(div);
        propertyIndex++;
    });
});
</script>
@endpush
