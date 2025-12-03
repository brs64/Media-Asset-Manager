<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="min-h-screen flex flex-col justify-center items-center pt-24 pb-12 bg-gray-100">
        
        <div class="w-full max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="p-8 bg-white shadow sm:rounded-lg">
                
                <div class="max-w-xl mx-auto">
                    @include('profile.partials.update-password-form')
                </div>

            </div>
        </div>
    </div>
</x-app-layout>