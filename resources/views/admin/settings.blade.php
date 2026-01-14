@extends('layouts.admin')

@section('tab_content')
<style>
    .hover-orange-force:hover {
        color: #E6A23C !important;
        font-weight: bold;
        padding-left: 0.5rem; /* Equivalent to pl-2 */
    }
</style>

<div class="flex flex-col md:flex-row gap-8 relative items-start">
    
    <div class="md:w-1/4" 
         x-data="{ scroll: 0, startOffset: 0 }" 
         x-init="startOffset = $el.getBoundingClientRect().top + window.scrollY"
         @scroll.window="scroll = window.scrollY">
        
        <div class="bg-gray-100 p-4 rounded-lg shadow-sm border border-gray-200 transition-transform duration-75 ease-out"
             :style="'transform: translateY(' + (scroll > 100 ? (scroll - 100) : 0) + 'px)'">
             
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-300 pb-2">Accès rapide</h3>
            
            <nav class="flex flex-col space-y-2 text-sm font-medium">
                <a href="#section-uris" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">URIs</a>
                <a href="#section-ftp" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">Connexions FTP</a>
                <a href="#section-bd" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">Base de données</a>
                <a href="#section-sauvegarde" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">Sauvegarde</a>
                <a href="#section-logs" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">Logs</a>
                <a href="#section-multi" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">Multiprocessing</a>
                <a href="#section-perso" class="text-gray-600 hover-orange-force transition-all duration-200 block py-1">Personnalisation</a>
            </nav>
        </div>
    </div>

    <div class="md:w-3/4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">Paramétrage du site</h2>

        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-12">
            @csrf
            
            <section id="section-uris" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">URIs</h3>
                <div class="space-y-5">
                    @foreach([
                        'uri_nas_pad' => 'URI racine du NAS PAD:',
                        'uri_nas_arch' => 'URI racine du NAS ARCH:',
                        'uri_local' => 'URI racine du stockage local:',
                        'uri_nas_diff' => 'URI racine du NAS DIFF:'
                    ] as $name => $label)
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">{{ $label }}</label>
                        <input type="text" name="{{ $name }}" value="{{ $settings[$name] ?? '' }}" 
                            class="w-full rounded border-gray-300 focus:border-[#E6A23C] focus:ring focus:ring-orange-200 shadow-sm">
                    </div>
                    @endforeach
                </div>
            </section>

            <section id="section-ftp" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">Connexions FTP</h3>
                @foreach(['nas_pad' => 'NAS PAD', 'nas_arch' => 'NAS ARCH', 'nas_diff' => 'NAS DIFF'] as $prefix => $title)
                <div class="mb-8 bg-gray-50 p-5 rounded-lg border border-gray-200">
                    <h4 class="font-bold text-gray-800 mb-4 uppercase tracking-wide border-b pb-1 text-sm">{{ $title }}</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1 text-sm">IP du serveur:</label>
                            <input type="text" name="{{ $prefix }}_ip" value="{{ $settings[$prefix.'_ip'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1 text-sm">Identifiant:</label>
                                <input type="text" name="{{ $prefix }}_user" value="{{ $settings[$prefix.'_user'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                            </div>
                            <div x-data="{ show: false }">
                                <label class="block font-bold text-gray-700 mb-1 text-sm">Mot de passe:</label>
                                <div class="relative">
                                    <input :type="show ? 'text' : 'password'" name="{{ $prefix }}_pass" value="{{ $settings[$prefix.'_pass'] ?? '' }}" class="w-full rounded border-gray-300 pr-10 shadow-sm">
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                        <span x-show="!show">👁️</span><span x-show="show">🚫</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @if($prefix !== 'nas_diff')
                        <div class="pt-4 border-t border-gray-200 mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1 text-sm">Identifiant (Modif):</label>
                                    <input type="text" name="{{ $prefix }}_user_sup" value="{{ $settings[$prefix.'_user_sup'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                                </div>
                                <div x-data="{ show: false }">
                                    <label class="block font-bold text-gray-700 mb-1 text-sm">Mot de passe (Modif):</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'" name="{{ $prefix }}_pass_sup" value="{{ $settings[$prefix.'_pass_sup'] ?? '' }}" class="w-full rounded border-gray-300 pr-10 shadow-sm">
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                            <span x-show="!show">👁️</span><span x-show="show">🚫</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </section>

            <section id="section-bd" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">Base de données</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1 text-sm">Serveur de la BD:</label>
                            <input type="text" name="db_host" value="{{ $settings['db_host'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1 text-sm">Port de la BD:</label>
                            <input type="text" name="db_port" value="{{ $settings['db_port'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Nom de la BD:</label>
                        <input type="text" name="db_name" value="{{ $settings['db_name'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1 text-sm">Utilisateur de la BD:</label>
                            <input type="text" name="db_user" value="{{ $settings['db_user'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                        </div>
                        <div x-data="{ show: false }">
                            <label class="block font-bold text-gray-700 mb-1 text-sm">Mot de passe de la BD:</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="db_pass" value="{{ $settings['db_pass'] ?? '' }}" class="w-full rounded border-gray-300 pr-10 shadow-sm">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500">
                                     <span x-show="!show">👁️</span><span x-show="show">🚫</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="section-sauvegarde" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">Sauvegarde</h3>
                <div class="space-y-4">
                    @foreach([
                        'backup_gen' => 'URI des fichiers générés:',
                        'backup_dump' => 'URI du dump de sauvegarde:',
                        'backup_const' => 'URI des constantes:',
                        'backup_suf_dump' => 'Suffixe fichier dump:',
                        'backup_suf_const' => 'Suffixe fichier constantes:'
                    ] as $name => $label)
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">{{ $label }}</label>
                        <input type="text" name="{{ $name }}" value="{{ $settings[$name] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                    @endforeach
                </div>
            </section>

             <section id="section-logs" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">Logs</h3>
                <div class="space-y-4">
                     <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Fichier log général:</label>
                        <input type="text" name="log_general" value="{{ $settings['log_general'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Fichier log sauvegarde:</label>
                        <input type="text" name="log_backup" value="{{ $settings['log_backup'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                     <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Nb lignes max:</label>
                        <input type="number" name="log_lines" value="{{ $settings['log_lines'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                     <div class="flex items-center">
                        <input type="checkbox" name="log_recent" id="log_recent" class="rounded text-[#E6A23C] focus:ring-orange-500 h-5 w-5"
                               {{ ($settings['log_recent'] ?? false) ? 'checked' : '' }}>
                        <label for="log_recent" class="ml-2 font-bold text-gray-700">Afficher les plus récents en premier</label>
                    </div>
                </div>
            </section>
            
            <section id="section-multi" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">Multiprocessing</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Nombre maximum de processus de transfert:</label>
                        <input type="number" name="proc_transfer" value="{{ $settings['proc_transfer'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Nombre maximum de sous-processus de transfert:</label>
                        <input type="number" name="proc_sub" value="{{ $settings['proc_sub'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                </div>
            </section>

            <section id="section-perso" class="scroll-mt-32">
                <h3 class="text-lg font-bold text-[#b91c1c] border-b border-[#b91c1c] mb-4">Personnalisation</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Nombre de vidéos dans le carrousel de la page d'accueil:</label>
                        <input type="number" name="disp_swiper" value="{{ $settings['disp_swiper'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                     <div>
                        <label class="block font-bold text-gray-700 mb-1 text-sm">Nombre de vidéos dans l'historique:</label>
                        <input type="number" name="disp_history" value="{{ $settings['disp_history'] ?? '' }}" class="w-full rounded border-gray-300 shadow-sm">
                    </div>
                </div>
            </section>

            <div class="sticky bottom-0 bg-white pt-4 pb-2 border-t mt-8 flex justify-end z-10">
                <button type="submit" class="bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-3 px-8 rounded shadow-md transition-transform transform hover:scale-105">
                    Mettre à jour les paramètres
                </button>
            </div>
        </form>
    </div>
</div>
@endsection