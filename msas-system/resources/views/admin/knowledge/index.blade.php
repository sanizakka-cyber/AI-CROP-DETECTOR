<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Knowledge Base — Manage Articles</h2>
            <a href="{{ route('admin.knowledge.create') }}"
               class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-emerald-700">
                + New Article
            </a>
        </div>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-xl border border-slate-100 overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="px-5 py-3">Title</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Views</th>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($articles as $article)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-semibold text-slate-800 max-w-xs truncate">
                                    <a href="{{ route('knowledge.show', $article->slug) }}" class="hover:text-emerald-700">
                                        {{ $article->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $article->category_label }}</td>
                                <td class="px-4 py-3">
                                    @if($article->is_published)
                                        <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Published</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Draft</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ number_format($article->view_count) }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $article->author?->first_name }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.knowledge.edit', $article) }}"
                                       class="text-indigo-600 font-semibold text-xs hover:underline mr-3">Edit</a>
                                    <form action="{{ route('admin.knowledge.destroy', $article) }}" method="POST"
                                          class="inline" onsubmit="return confirm('Delete this article?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 font-semibold text-xs hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400 text-sm">No articles yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($articles->hasPages())
                    <div class="px-5 py-4 border-t border-slate-100">{{ $articles->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
