<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Messages') }}
                @if($unreadTotal > 0)
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">{{ $unreadTotal }}</span>
                @endif
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-xl border border-slate-100 overflow-hidden">
                @if($conversations->isEmpty())
                    <div class="p-12 text-center text-slate-400">
                        <svg class="mx-auto h-12 w-12 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-sm font-medium">No messages yet</p>
                        <p class="text-xs mt-1">Start a conversation by visiting a user's profile.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach($conversations as $msg)
                            @php
                                $partner = $msg->from_user_id === auth()->id() ? $msg->toUser : $msg->fromUser;
                                $isUnread = $msg->to_user_id === auth()->id() && is_null($msg->read_at);
                            @endphp
                            <li>
                                <a href="{{ route('messages.show', $partner) }}"
                                   class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 transition-colors {{ $isUnread ? 'bg-indigo-50' : '' }}">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm uppercase">
                                        {{ substr($partner->first_name ?? '?', 0, 1) }}{{ substr($partner->last_name ?? '', 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-semibold text-slate-800 truncate {{ $isUnread ? 'font-bold' : '' }}">
                                                {{ $partner->first_name }} {{ $partner->last_name }}
                                                <span class="ml-1 text-xs font-normal text-slate-400 capitalize">{{ str_replace('-', ' ', $partner->role) }}</span>
                                            </p>
                                            <span class="text-xs text-slate-400 flex-shrink-0 ml-2">{{ $msg->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 truncate mt-0.5">
                                            @if($msg->from_user_id === auth()->id())<span class="text-slate-400">You: </span>@endif
                                            {{ $msg->body }}
                                        </p>
                                    </div>
                                    @if($isUnread)
                                        <span class="flex-shrink-0 h-2 w-2 rounded-full bg-indigo-500"></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
