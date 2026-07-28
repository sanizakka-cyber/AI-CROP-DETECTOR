<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('knowledge.index') }}" class="text-slate-500 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">{{ $article->title }}</h2>
        </div>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 flex gap-8">

            {{-- Main article --}}
            <article class="flex-1 min-w-0">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                    {{-- Meta bar --}}
                    <div class="bg-emerald-600 px-8 py-6 text-white">
                        <span class="text-xs font-bold uppercase tracking-widest opacity-75">{{ $article->category_label }}</span>
                        <h1 class="text-2xl font-black mt-2 leading-tight">{{ $article->title }}</h1>
                        @if($article->excerpt)
                            <p class="text-emerald-100 mt-2 text-sm leading-relaxed">{{ $article->excerpt }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-4 text-xs text-emerald-200">
                            <span>By {{ $article->author?->first_name }} {{ $article->author?->last_name }}</span>
                            <span>&bull;</span>
                            <span>{{ $article->published_at?->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span>{{ $article->view_count }} views</span>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="px-8 py-8 prose prose-slate prose-sm max-w-none leading-relaxed text-slate-700">
                        {!! nl2br(e($article->body)) !!}
                    </div>

                    {{-- Tags --}}
                    @if($article->tags)
                        <div class="px-8 pb-8 flex flex-wrap gap-2">
                            @foreach($article->tags as $tag)
                                <span class="text-xs bg-slate-100 text-slate-500 rounded-full px-3 py-1">#{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Admin actions --}}
                    @if(in_array(auth()->user()->role, ['admin','ceo','extension-officer']))
                        <div class="px-8 pb-6 flex gap-3 border-t border-slate-100 pt-5">
                            <a href="{{ route('admin.knowledge.edit', $article) }}"
                               class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Edit Article</a>
                            <form action="{{ route('admin.knowledge.destroy', $article) }}" method="POST"
                                  onsubmit="return confirm('Delete this article?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-500 hover:text-red-700">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>

            {{-- Sidebar: related --}}
            @if($related->isNotEmpty())
                <aside class="w-64 flex-shrink-0 hidden lg:block">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-4">Related Articles</p>
                        <ul class="space-y-3">
                            @foreach($related as $rel)
                                <li>
                                    <a href="{{ route('knowledge.show', $rel->slug) }}"
                                       class="text-sm font-medium text-slate-700 hover:text-emerald-700 leading-snug block">
                                        {{ $rel->title }}
                                    </a>
                                    <span class="text-xs text-slate-400">{{ $rel->published_at?->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>
            @endif

        </div>
    </div>
</x-app-layout>
