<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="flex flex-col items-center">
        <div class="bg-[#2c3e50] rounded-full p-4 mb-6 flex items-center justify-center h-24 w-24">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
            </svg>
        </div>

        <form method="POST" action="{{ route('login') }}" class="w-full">
            @csrf

            <div class="mb-4 flex flex-col items-start">
                <label for="name" class="font-bold text-gray-900 mb-2 text-sm">
                    {{ __("Nom d'utilisateur :") }}
                </label>
                
                <x-text-input id="name" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                              type="text" 
                              name="name" 
                              :value="old('name')" 
                              required autofocus autocomplete="username" />
                              
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mb-6 flex flex-col items-start">
                <label for="password" class="font-bold text-gray-900 mb-2 text-sm">
                    {{ __("Mot de passe :") }}
                </label>

                <x-text-input id="password" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="block mb-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-orange-500 shadow-sm focus:ring-orange-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Se souvenir de moi') }}</span>
                </label>
            </div>

            <div>
                <button type="submit" class="w-full justify-center bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    {{ __('Confirmer') }}
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>