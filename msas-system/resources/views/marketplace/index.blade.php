<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Marketplace') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-8 text-center border border-slate-100">
                <div class="text-6xl mb-4">🛒</div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Digital Marketplace</h3>
                <p class="text-slate-600">The marketplace feature is currently being populated with products. Check back soon!</p>
            </div>
        </div>
    </div>
</x-app-layout>
