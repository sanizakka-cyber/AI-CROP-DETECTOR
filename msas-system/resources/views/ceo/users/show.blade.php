<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('ceo.users') }}" class="text-slate-400 hover:text-slate-600 transition">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h2 class="font-extrabold text-xl text-gray-800">User Profile</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $user->staffId }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('ceo.users.edit', $user) }}"
                   class="px-4 py-2 bg-[#0F6B3E] text-white rounded-xl text-sm font-semibold hover:bg-[#047857] transition">
                    ✏ Edit Profile
                </a>
                @if($user->id !== auth()->id() && !session('impersonate.original_id'))
                <a href="{{ route('impersonate.start', $user) }}"
                   onclick="return confirm('Login as {{ addslashes($user->name) }}?')"
                   class="px-4 py-2 bg-violet-100 text-violet-700 rounded-xl text-sm font-semibold hover:bg-violet-200 transition">
                    Login As
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-5">

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-semibold">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm font-semibold">⚠ {{ session('error') }}</div>
        @endif

        {{-- Profile Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="bg-gradient-to-r from-[#0B2447] to-[#0F3460] h-24 relative">
                <div class="absolute -bottom-10 left-6">
                    <img src="{{ $user->avatarUrl }}" alt="{{ $user->name }}"
                         class="w-20 h-20 rounded-2xl object-cover border-4 border-white shadow-lg">
                </div>
            </div>
            <div class="pt-14 pb-6 px-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-extrabold text-slate-800">{{ $user->name ?: '(No name set)' }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $user->roleLabel }}</p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Suspended' }}
                            </span>
                            @if($user->is_verified)
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-700">✓ Verified</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500">Unverified</span>
                            @endif
                            @if($user->application_status)
                            @php $appColors = ['approved'=>'bg-emerald-100 text-emerald-700','pending'=>'bg-amber-100 text-amber-700','rejected'=>'bg-red-100 text-red-700']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $appColors[$user->application_status] ?? 'bg-slate-100 text-slate-600' }} capitalize">
                                App: {{ $user->application_status }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        {{-- Suspend / Activate --}}
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('ceo.users.toggle', $user) }}">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('{{ $user->is_active ? 'Suspend' : 'Activate' }} this account?')"
                                class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $user->is_active ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }}">
                                {{ $user->is_active ? '⏸ Suspend' : '▶ Activate' }}
                            </button>
                        </form>
                        {{-- Delete --}}
                        <form method="POST" action="{{ route('ceo.users.delete', $user) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                onclick="return confirm('PERMANENTLY delete {{ addslashes($user->name) }}? This action CANNOT be undone.')"
                                class="px-4 py-2 bg-red-100 text-red-600 rounded-xl text-sm font-semibold hover:bg-red-200 transition">
                                🗑 Delete
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Personal Details --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-700 mb-4 text-sm uppercase tracking-wide">Personal Details</h4>
                <dl class="space-y-3">
                    @php
                    $fields = [
                        'First Name'   => $user->first_name,
                        'Middle Name'  => $user->middle_name,
                        'Last Name'    => $user->last_name,
                        'Email'        => $user->email,
                        'Phone'        => $user->phone,
                        'Staff ID'     => $user->staffId,
                    ];
                    @endphp
                    @foreach($fields as $label => $value)
                    <div class="flex justify-between items-start gap-2">
                        <dt class="text-xs text-slate-400 font-semibold flex-shrink-0">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-slate-700 text-right">{{ $value ?: '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

            {{-- Location & Account --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h4 class="font-bold text-slate-700 mb-4 text-sm uppercase tracking-wide">Location & Account</h4>
                <dl class="space-y-3">
                    @php
                    $fields2 = [
                        'Role'               => $user->roleLabel,
                        'State'              => $user->state,
                        'LGA'                => $user->lga,
                        'Country'            => $user->country ?? 'Nigeria',
                        'Language'           => strtoupper($user->language ?? 'en'),
                        'Registered'         => $user->created_at->format('d M Y, H:i'),
                        'Last Seen'          => $user->last_seen ? \Carbon\Carbon::parse($user->last_seen)->diffForHumans() : 'Never',
                    ];
                    @endphp
                    @foreach($fields2 as $label => $value)
                    <div class="flex justify-between items-start gap-2">
                        <dt class="text-xs text-slate-400 font-semibold flex-shrink-0">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-slate-700 text-right">{{ $value ?: '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

        </div>

        {{-- Subscription --}}
        @php $sub = $user->activeSubscription(); @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h4 class="font-bold text-slate-700 mb-4 text-sm uppercase tracking-wide">Subscription</h4>
            @if($sub)
            <div class="flex flex-wrap gap-4">
                <div><span class="text-xs text-slate-400">Plan</span><p class="font-bold text-slate-800 capitalize mt-0.5">{{ str_replace(['_','-'],' ',$sub->plan) }}</p></div>
                <div><span class="text-xs text-slate-400">Status</span><p class="font-bold text-emerald-600 mt-0.5 capitalize">{{ $sub->status }}</p></div>
                <div><span class="text-xs text-slate-400">Cycle</span><p class="font-bold text-slate-800 mt-0.5 capitalize">{{ $sub->billing_cycle }}</p></div>
                <div><span class="text-xs text-slate-400">Expires</span><p class="font-bold text-slate-800 mt-0.5">{{ $sub->ends_at?->format('d M Y') ?? '—' }}</p></div>
            </div>
            @else
            <p class="text-sm text-slate-400">No active subscription.</p>
            @endif
        </div>

    </div>
</x-app-layout>
