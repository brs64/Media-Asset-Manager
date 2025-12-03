<x-guest-layout>
    <div class="flex flex-col items-center">

        <div class="mb-6 text-sm text-gray-600 text-center">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="w-full">
            @csrf

            <div class="mb-6 flex flex-col items-start">
                <x-input-label for="email" :value="__('Email')" class="font-bold text-gray-900 mb-2 text-sm" />
                
                <x-text-input id="email" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-orange-400 focus:ring focus:ring-orange-200 focus:ring-opacity-50" 
                              type="email" 
                              name="email" 
                              :value="old('email')" 
                              required autofocus />
                              
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <button type="submit" class="w-full justify-center bg-[#E6A23C] hover:bg-[#d49230] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    {{ __('Email Password Reset Link') }}
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>