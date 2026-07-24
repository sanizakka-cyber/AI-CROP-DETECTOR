<x-app-layout>
    <x-slot name="header">Add New Rider</x-slot>

    <div class="max-w-xl mx-auto">
        <a href="{{ route('admin.riders.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-6">← Back to Riders</a>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            @if($errors->any())
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('admin.riders.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="+234…">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Vehicle Type</label>
                    <select name="vehicle_type" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select…</option>
                        <option value="Motorcycle" {{ old('vehicle_type')==='Motorcycle'?'selected':'' }}>Motorcycle</option>
                        <option value="Bicycle" {{ old('vehicle_type')==='Bicycle'?'selected':'' }}>Bicycle</option>
                        <option value="Car" {{ old('vehicle_type')==='Car'?'selected':'' }}>Car</option>
                        <option value="Van" {{ old('vehicle_type')==='Van'?'selected':'' }}>Van</option>
                        <option value="Truck" {{ old('vehicle_type')==='Truck'?'selected':'' }}>Truck</option>
                    </select>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    A temporary password will be generated and displayed once. The rider will be required to change it on first login.
                </div>

                <button type="submit" class="w-full py-2.5 bg-[#0F6B3E] text-white rounded-xl font-bold hover:bg-[#047857]">
                    Create Rider Account
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
