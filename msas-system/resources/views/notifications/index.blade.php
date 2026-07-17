<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
            <span class="text-2xl">🔔</span> Notifications
        </h2>
    </x-slot>

    <div class="space-y-3">
        @forelse($notifications as $n)
        @php
            $iconMap = ['success'=>'✅','warning'=>'⚠️','danger'=>'🚨','info'=>'ℹ️'];
            $colorMap = [
                'success'=>'border-emerald-200 bg-emerald-50',
                'warning'=>'border-amber-200 bg-amber-50',
                'danger' =>'border-red-200 bg-red-50',
                'info'   =>'border-blue-200 bg-blue-50',
            ];
            $icon  = $iconMap[$n->type]  ?? 'ℹ️';
            $color = $colorMap[$n->type] ?? 'border-slate-200 bg-white';
        @endphp
        <div class="rounded-2xl border {{ $color }} p-4 md:p-5 flex gap-4 items-start">
            <span class="text-2xl shrink-0">{{ $icon }}</span>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm">{{ $n->title }}</p>
                <p class="text-slate-600 text-sm mt-0.5 leading-relaxed">{{ $n->message }}</p>
                @if($n->link)
                <a href="{{ $n->link }}" class="text-xs text-emerald-600 font-semibold mt-1 inline-block hover:underline">View details →</a>
                @endif
            </div>
            <span class="text-xs text-slate-400 shrink-0">{{ $n->created_at->diffForHumans() }}</span>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">🔔</div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">All Caught Up</h3>
            <p class="text-slate-500 text-sm">You have no notifications right now.</p>
        </div>
        @endforelse

        @if($notifications->hasPages())
        <div class="mt-6 flex justify-center">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
