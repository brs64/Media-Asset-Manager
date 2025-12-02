<section>

    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Mise à Jour du mot de passe
        </h2>

        <p class="mt-1 text-sm text-red-600">
            Pensez à mettre un mot de passe sécurisé avec :
            <ul class="mt-3" >
                <li>
                    - Des chiffres
                </li>
                <li>
                    - Des caractères minuscules/majuscules
                </li>
                <li>
                    - Des caractères spéciaux
                </li>
            </ul>
        </p>
    </div>

    <div class="flex items-center gap-4">
        <button onclick="{{ route('home') }}">Retour</button>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <p>Mot de passe actuel</p>
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <p>Nouveau mot de passe</p>
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <p>Confirmer le nouveau mot de passe</p>
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>


    </form>
</section>
