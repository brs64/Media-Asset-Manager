<section class="w-full max-w-md mx-auto">
    <div class="flex flex-col items-center mb-6">

            <h2 class="text-xl font-bold text-gray-900">
                {{ __('Mise à Jour du mot de passe') }}
            </h2>

            <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded p-3 text-left inline-block">
                <p class="font-semibold">Pensez à mettre un mot de passe sécurisé avec :</p>
                <ul class="list-disc list-inside mt-1 ml-1 space-y-1 text-xs">
                    <li>Des chiffres</li>
                    <li>Des caractères minuscules/majuscules</li>
                    <li>Des caractères spéciaux</li>
                </ul>
            </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6 w-full">
        @csrf
        @method('put')

        <div class="flex flex-col items-start">
            <label for="update_password_current_password" class="font-bold text-gray-900 mb-2 text-sm">
                {{ __('Mot de passe actuel') }}
            </label>
            <x-text-input id="update_password_current_password" 
                          name="current_password" 
                          type="password" 
                          class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="flex flex-col items-start">
            <label for="update_password_password" class="font-bold text-gray-900 mb-2 text-sm">
                {{ __('Nouveau mot de passe') }}
            </label>
            <x-text-input id="update_password_password" 
                          name="password" 
                          type="password" 
                          class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-col items-start">
            <label for="update_password_password_confirmation" class="font-bold text-gray-900 mb-2 text-sm">
                {{ __('Confirmer le nouveau mot de passe') }}
            </label>
            <x-text-input id="update_password_password_confirmation" 
                          name="password_confirmation" 
                          type="password" 
                          class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col items-center gap-4">
            <button type="submit" class="w-full justify-center bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                {{ __('Sauvegarder') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 font-bold"
                >{{ __('Mot de passe modifié avec succès.') }}</p>
            @endif
        </div>
    </form>
</section>