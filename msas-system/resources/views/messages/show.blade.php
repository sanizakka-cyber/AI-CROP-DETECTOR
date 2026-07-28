<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('messages.index') }}" class="text-slate-500 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs uppercase">
                {{ substr($user->first_name ?? '?', 0, 1) }}{{ substr($user->last_name ?? '', 0, 1) }}
            </div>
            <div>
                <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                    {{ $user->first_name }} {{ $user->last_name }}
                </h2>
                <p class="text-xs text-slate-500 capitalize">{{ str_replace('-', ' ', $user->role) }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 bg-slate-50 min-h-screen">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 flex flex-col" style="height: calc(100vh - 140px);">

            {{-- Message thread --}}
            <div id="thread" class="flex-1 overflow-y-auto space-y-3 pb-4 pr-1">
                @forelse($messages as $msg)
                    @if($msg->from_user_id === auth()->id())
                        {{-- Sent --}}
                        <div class="flex justify-end">
                            <div class="max-w-xs lg:max-w-md">
                                <div class="bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-2.5 text-sm shadow-sm">
                                    {{ $msg->body }}
                                </div>
                                <p class="text-right text-xs text-slate-400 mt-1">{{ $msg->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                    @else
                        {{-- Received --}}
                        <div class="flex justify-start gap-2">
                            <div class="flex-shrink-0 h-7 w-7 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs uppercase mt-1">
                                {{ substr($user->first_name ?? '?', 0, 1) }}
                            </div>
                            <div class="max-w-xs lg:max-w-md">
                                <div class="bg-white border border-slate-200 text-slate-800 rounded-2xl rounded-tl-sm px-4 py-2.5 text-sm shadow-sm">
                                    {{ $msg->body }}
                                </div>
                                <p class="text-left text-xs text-slate-400 mt-1">{{ $msg->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex items-center justify-center h-full">
                        <p class="text-slate-400 text-sm">No messages yet. Say hello!</p>
                    </div>
                @endforelse
            </div>

            @if(session('success'))
                <div class="mb-2 text-xs text-green-600 text-right">{{ session('success') }}</div>
            @endif

            {{-- Send form --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-3 mt-2">
                <form action="{{ route('messages.send', $user) }}" method="POST" class="flex gap-2 items-end">
                    @csrf
                    <textarea
                        name="body"
                        rows="2"
                        placeholder="Type a message…"
                        class="flex-1 resize-none text-sm border-0 focus:ring-0 focus:outline-none p-1 text-slate-800 placeholder-slate-400"
                        required
                        maxlength="2000"
                        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
                    >{{ old('body') }}</textarea>
                    <button type="submit"
                        class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2 text-sm font-semibold transition-colors">
                        Send
                    </button>
                </form>
                @error('body')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    <script>
        // Scroll to bottom of thread on load
        const thread = document.getElementById('thread');
        if (thread) thread.scrollTop = thread.scrollHeight;
    </script>
</x-app-layout>
