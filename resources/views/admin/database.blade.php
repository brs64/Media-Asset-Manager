@extends('layouts.admin')

@section('tab_content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800 border-b pb-2 mb-6">Sauvegarde de la base de données</h2>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <div class="lg:w-1/3 space-y-6">
            <div class="bg-gray-50 p-6 rounded-lg border shadow-sm">
                <h3 class="font-bold text-lg text-gray-700 mb-4">Paramètres des sauvegardes</h3>
                
                <form action="{{ route('admin.backup.save') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Heure d'exécution :</label>
                        <input type="time" name="backup_time" value="{{ env('BACKUP_TIME', '00:00') }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Jour d'exécution :</label>
                        <select name="backup_day" class="w-full rounded border-gray-300 shadow-sm">
                            <option value="*" {{ env('BACKUP_DAY') == '*' ? 'selected' : '' }}>Tous les jours</option>
                            <option value="1" {{ env('BACKUP_DAY') == '1' ? 'selected' : '' }}>Lundi</option>
                            <option value="2" {{ env('BACKUP_DAY') == '2' ? 'selected' : '' }}>Mardi</option>
                            <option value="3" {{ env('BACKUP_DAY') == '3' ? 'selected' : '' }}>Mercredi</option>
                            <option value="4" {{ env('BACKUP_DAY') == '4' ? 'selected' : '' }}>Jeudi</option>
                            <option value="5" {{ env('BACKUP_DAY') == '5' ? 'selected' : '' }}>Vendredi</option>
                            <option value="6" {{ env('BACKUP_DAY') == '6' ? 'selected' : '' }}>Samedi</option>
                            <option value="0" {{ env('BACKUP_DAY') == '0' ? 'selected' : '' }}>Dimanche</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Mois d'exécution :</label>
                        <select name="backup_month" class="w-full rounded border-gray-300 shadow-sm">
                            <option value="*" {{ env('BACKUP_MONTH') == '*' ? 'selected' : '' }}>Tous les mois</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ env('BACKUP_MONTH') == $m ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-4 flex flex-col gap-3">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow text-sm">
                            Enregistrer les paramètres
                        </button>
                    </div>
                </form>

                 <form action="{{ route('admin.backup.run') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-2 px-4 rounded shadow text-sm">
                        Réaliser une sauvegarde manuelle
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:w-2/3">
             <div class="bg-[#1e1e1e] text-green-400 font-mono text-xs p-4 rounded shadow-inner h-[500px] overflow-y-auto border border-gray-700">
                <p class="opacity-50 border-b border-gray-700 pb-2 mb-2">-- Logs de sauvegarde --</p>
                @if(isset($backupLogs) && count($backupLogs) > 0)
                    @foreach($backupLogs as $line)
                        <div class="mb-1">{{ $line }}</div>
                    @endforeach
                @else
                    <div class="mb-1">[System] Prêt à effectuer une sauvegarde.</div>
                    <div class="mb-1">[System] Aucune erreur détectée.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection