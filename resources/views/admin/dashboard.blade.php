@extends('layouts.app')

@section('content')
<div class="pt-32 pb-12 bg-gray-50 min-h-screen" x-data="{ activeTab: 'settings' }">
    <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8">
        
        <h1 class="text-3xl font-bold text-gray-800 text-center mb-10">
            Administration de BTSPlay
        </h1>

        <div class="flex flex-wrap justify-center gap-3 mb-8">
            
            <template x-for="tab in ['database', 'reconciliation', 'transfert', 'settings', 'logs', 'users']">
                <button 
                    @click="activeTab = tab"
                    :class="activeTab === tab ? 'bg-[#b91c1c] ring-2 ring-offset-2 ring-red-700 scale-105' : 'bg-[#E6A23C] hover:bg-[#d49230]'"
                    class="text-white font-bold py-2 px-4 rounded shadow transition-all capitalize duration-200"
                    x-text="formatTabName(tab)">
                </button>
            </template>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 min-h-[600px]">

            <div x-show="activeTab === 'database'" x-cloak>
                @include('admin.partials.database')
            </div>

            <div x-show="activeTab === 'reconciliation'" x-cloak>
                 @include('admin.partials.reconciliation')
            </div>

            <div x-show="activeTab === 'transfert'" x-cloak>
                 @include('admin.partials.transfert')
            </div>

            <div x-show="activeTab === 'settings'" x-cloak>
                 @include('admin.partials.settings')
            </div>

            <div x-show="activeTab === 'logs'" x-cloak>
                 @include('admin.partials.logs')
            </div>

            <div x-show="activeTab === 'users'" x-cloak>
                 @include('admin.partials.users')
            </div>

        </div>
    </div>
</div>

<script>
    function formatTabName(name) {
        const labels = {
            'database': 'Base de données',
            'reconciliation': 'Réconciliation',
            'transfert': 'Fonction de transfert',
            'settings': 'Paramétrage du site',
            'logs': 'Consulter les logs',
            'users': 'Gérer les utilisateurs'
        };
        return labels[name] || name;
    }
</script>
@endsection