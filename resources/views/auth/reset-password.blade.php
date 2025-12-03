<x-guest-layout>
    <div class="flex flex-col items-center">

        <form method="POST" action="{{ route('password.store') }}" class="w-full">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-4 flex flex-col items-start">
                <x-input-label for="email" :value="__('Email')" class="font-bold text-gray-900 mb-2 text-sm" />
                
                <x-text-input id="email" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                              type="email" 
                              name="email" 
                              :value="old('email', $request->email)" 
                              required autofocus autocomplete="username" />
                              
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mb-4 flex flex-col items-start">
                <x-input-label for="password" :value="__('Password')" class="font-bold text-gray-900 mb-2 text-sm" />
                
                <x-text-input id="password" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                              type="password" 
                              name="password" 
                              required autocomplete="new-password" />
                              
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mb-6 flex flex-col items-start">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="font-bold text-gray-900 mb-2 text-sm" />

                <x-text-input id="password_confirmation" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                type="password"
                                name="password_confirmation" 
                                required autocomplete="new-password" />

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <button type="submit" class="w-full justify-center bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    {{ __('Reset Password') }}
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>