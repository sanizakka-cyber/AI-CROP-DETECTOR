<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center gap-2">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg> Notifications
        </h2>
    </x-slot>

    <div class="space-y-3">
        @forelse($notifications as $n)
        @php
            $iconMap = [
                'success' => '<svg width="22" height="22" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'warning' => '<svg width="22" height="22" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
                'danger'  => '<svg width="22" height="22" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'info'    => '<svg width="22" height="22" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            ];
            $colorMap = [
                'success'=>'border-emerald-200 bg-emerald-50',
                'warning'=>'border-amber-200 bg-amber-50',
                'danger' =>'border-red-200 bg-red-50',
                'info'   =>'border-blue-200 bg-blue-50',
            ];
            $icon  = $iconMap[$n->type]  ?? $iconMap['info'];
            $color = $colorMap[$n->type] ?? 'border-slate-200 bg-white';
        @endphp
        <div class="rounded-2xl border {{ $color }} p-4 md:p-5 flex gap-4 items-start">
            <span class="shrink-0">{!! $icon !!}</span>
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
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4"><svg width="32" height="32" fill="none" stroke="#94a3b8" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">All Caught Up</h3>
            <p class="text-slate-500 text-sm">You have no notifications right now.</p>
        </div>
        @endforelse

        @if($notifications->hasPages())
        <div class="mt-6 flex justify-center">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
