<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.knowledge.index') }}" class="text-slate-500 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($article) ? 'Edit Article' : 'New Article' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-xl border border-slate-100 p-8">

                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ isset($article) ? route('admin.knowledge.update', $article) : route('admin.knowledge.store') }}"
                      method="POST" class="space-y-6">
                    @csrf
                    @if(isset($article)) @method('PATCH') @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Title *</label>
                        <input type="text" name="title" value="{{ old('title', $article->title ?? '') }}" required
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Excerpt (short summary)</label>
                        <input type="text" name="excerpt" value="{{ old('excerpt', $article->excerpt ?? '') }}" maxlength="400"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="One-sentence summary shown on the listing page">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Category *</label>
                        <select name="category" required
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category', $article->category ?? 'general') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Article Body *</label>
                        <textarea name="body" rows="16" required
                                  class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-mono leading-relaxed focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                  placeholder="Write the full article content here. Use blank lines to separate paragraphs.">{{ old('body', $article->body ?? '') }}</textarea>
                        <p class="text-xs text-slate-400 mt-1">Plain text. Blank lines create paragraph breaks.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Tags (comma-separated)</label>
                        <input type="text" name="tags"
                               value="{{ old('tags', isset($article->tags) ? implode(', ', $article->tags) : '') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="e.g. cassava, blight, treatment">
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" id="is_published" value="1"
                               @checked(old('is_published', $article->is_published ?? false))
                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="is_published" class="text-sm font-medium text-slate-700">Publish immediately</label>
                    </div>

                    <div class="flex gap-4 pt-2">
                        <button type="submit"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-colors shadow">
                            {{ isset($article) ? 'Save Changes' : 'Create Article' }}
                        </button>
                        <a href="{{ route('admin.knowledge.index') }}"
                           class="text-slate-500 hover:text-slate-700 px-4 py-2.5 text-sm font-medium">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
