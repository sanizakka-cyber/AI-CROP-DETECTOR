<x-app-layout>
    <x-slot name="header">Consultation #{{ $consultation->id }}</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">

        @foreach(['success','error','info'] as $t)
        @if(session($t))
        <div class="px-4 py-3 rounded-xl text-sm font-medium {{ $t==='success'?'bg-green-50 border border-green-200 text-green-800':($t==='error'?'bg-red-50 border border-red-200 text-red-700':'bg-blue-50 border border-blue-200 text-blue-800') }}">
            {{ session($t) }}
        </div>
        @endif
        @endforeach

        <a href="{{ route('admin.consultations.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700">← Back to Consultations</a>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            @php
                $sc = ['open'=>'bg-amber-100 text-amber-700','resolved'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
                $tc = $consultation->case_type === 'crop' ? 'bg-emerald-100 text-emerald-700' : 'bg-purple-100 text-purple-700';
            @endphp
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-800">Consultation #{{ $consultation->id }}</h2>
                    <p class="text-slate-500 text-sm mt-1">Submitted {{ $consultation->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $tc }}">{{ ucfirst($consultation->case_type) }}</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $sc[$consultation->status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($consultation->status) }}</span>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            {{-- Farmer info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-3">Farmer</h3>
                <div class="font-semibold text-slate-800">{{ $consultation->farmer?->first_name }} {{ $consultation->farmer?->last_name }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ $consultation->farmer?->phone }}</div>
                <div class="text-sm text-slate-500">{{ $consultation->farmer?->email }}</div>
            </div>

            {{-- Case info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-3">Case Details</h3>
                @if($consultation->animal_type)
                <div class="text-sm text-slate-600"><span class="font-semibold">Animal:</span> {{ $consultation->animal_type }}</div>
                @endif
                @if($consultation->crop_type)
                <div class="text-sm text-slate-600"><span class="font-semibold">Crop:</span> {{ $consultation->crop_type }}</div>
                @endif
                @if($consultation->priority)
                <div class="text-sm text-slate-600 mt-1"><span class="font-semibold">Priority:</span> {{ ucfirst($consultation->priority) }}</div>
                @endif
                @if($consultation->fee)
                <div class="text-sm text-slate-600 mt-1"><span class="font-semibold">Fee:</span> ₦{{ number_format($consultation->fee, 2) }} ({{ $consultation->payment_status ?? 'unpaid' }})</div>
                @endif
            </div>
        </div>

        {{-- Symptoms --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-3">Symptoms / Description</h3>
            <p class="text-slate-700 text-sm leading-relaxed">{{ $consultation->symptoms }}</p>
            @if($consultation->photo)
            <div class="mt-4">
                <img src="{{ asset('storage/' . $consultation->photo) }}" alt="Case photo" class="rounded-xl max-h-64 object-cover border border-slate-200">
            </div>
            @endif
        </div>

        {{-- AI Diagnosis --}}
        @if($consultation->ai_diagnosis)
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5">
            <h3 class="font-bold text-indigo-700 text-sm uppercase tracking-wide mb-2">AI Pre-Diagnosis</h3>
            <p class="text-indigo-800 text-sm leading-relaxed">{{ $consultation->ai_diagnosis }}</p>
            @if($consultation->ai_confidence)
            <div class="text-xs text-indigo-500 mt-2">Confidence: {{ $consultation->ai_confidence }}%</div>
            @endif
        </div>
        @endif

        {{-- Expert Assignment --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-4">
                {{ $consultation->case_type === 'crop' ? 'Agronomist' : 'Veterinarian' }} Assignment
            </h3>

            @if($consultation->expert)
            <div class="flex items-center justify-between mb-4 p-4 bg-slate-50 rounded-xl">
                <div>
                    <div class="font-semibold text-slate-800">{{ $consultation->expert->first_name }} {{ $consultation->expert->last_name }}</div>
                    <div class="text-sm text-slate-500">{{ ucfirst($consultation->expert->role) }}</div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Assigned</span>
            </div>
            @endif

            @if($consultation->status === 'open')
            <form method="POST" action="{{ route('admin.consultations.assign', $consultation) }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">
                        {{ $consultation->expert_id ? 'Reassign' : 'Assign' }} Expert
                    </label>
                    <select name="expert_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select {{ $consultation->case_type === 'crop' ? 'agronomist' : 'vet' }}…</option>
                        @foreach($experts as $e)
                        <option value="{{ $e->id }}" {{ $consultation->expert_id === $e->id ? 'selected' : '' }}>
                            {{ $e->first_name }} {{ $e->last_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-[#0F6B3E] text-white rounded-lg text-sm font-bold hover:bg-[#047857]">
                    {{ $consultation->expert_id ? 'Reassign' : 'Assign Expert' }}
                </button>
            </form>
            @endif
        </div>

        {{-- Expert Response --}}
        @if($consultation->expert_response)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
            <h3 class="font-bold text-green-700 text-sm uppercase tracking-wide mb-2">Expert Response</h3>
            <p class="text-green-800 text-sm leading-relaxed">{{ $consultation->expert_response }}</p>
            @if($consultation->completed_at)
            <div class="text-xs text-green-500 mt-2">Resolved {{ $consultation->completed_at->format('M d, Y H:i') }}</div>
            @endif
        </div>
        @endif

        {{-- Status override --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide mb-4">Update Status</h3>
            <form method="POST" action="{{ route('admin.consultations.status', $consultation) }}" class="flex gap-3 items-end">
                @csrf @method('PATCH')
                <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    @foreach(['open','resolved','cancelled'] as $s)
                    <option value="{{ $s }}" {{ $consultation->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700">Update</button>
            </form>
        </div>

    </div>
</x-app-layout>
