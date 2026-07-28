<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Knowledge Base</h2>
            @if(in_array(auth()->user()->role, ['admin','ceo','extension-officer']))
                <a href="{{ route('admin.knowledge.create') }}"
                   class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-emerald-700">
                    + New Article
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            {{-- Search + filter --}}
            <form method="GET" action="{{ route('knowledge.index') }}" class="flex flex-wrap gap-3 mb-8">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search articles…"
                       class="flex-1 min-w-[200px] border border-slate-200 rounded-xl px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                <select name="category"
                        class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow hover:bg-emerald-700">
                    Search
                </button>
                @if(request('q') || request('category'))
                    <a href="{{ route('knowledge.index') }}"
                       class="text-slate-500 hover:text-slate-700 px-3 py-2.5 text-sm font-medium">Clear</a>
                @endif
            </form>

            @if($articles->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-100 p-16 text-center text-slate-400 shadow-sm">
                    <p class="text-sm font-medium">No articles found.</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($articles as $article)
                        <a href="{{ route('knowledge.show', $article->slug) }}"
                           class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow p-6 flex flex-col gap-3 group">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 uppercase tracking-wide">
                                    {{ $article->category_label }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $article->view_count }} views</span>
                            </div>
                            <h3 class="text-base font-bold text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-snug">
                                {{ $article->title }}
                            </h3>
                            @if($article->excerpt)
                                <p class="text-sm text-slate-500 line-clamp-3 flex-1">{{ $article->excerpt }}</p>
                            @endif
                            <div class="flex items-center gap-2 text-xs text-slate-400 mt-auto pt-2 border-t border-slate-50">
                                <span>{{ $article->author?->first_name }} {{ $article->author?->last_name }}</span>
                                <span>&bull;</span>
                                <span>{{ $article->published_at?->diffForHumans() }}</span>
                            </div>
                            @if($article->tags)
                                <div class="flex flex-wrap gap-1">
                                    @foreach(array_slice($article->tags, 0, 3) as $tag)
                                        <span class="text-[10px] bg-slate-100 text-slate-500 rounded px-2 py-0.5">#{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $articles->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
