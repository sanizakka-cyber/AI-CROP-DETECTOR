<x-app-layout>
    <x-slot name="header">Glossary — {{ $language?->name_english ?? 'All languages' }}</x-slot>

    <div class="space-y-6 text-sm">
        <a href="{{ route('admin.speech.index') }}" class="text-emerald-700 font-semibold">&larr; Languages</a>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl font-semibold">&#10003; {{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-2">
            <h3 class="font-bold text-slate-800 text-lg">{{ $language?->name_native ?? 'All languages (*)' }}</h3>
            <ul class="list-disc ml-5 text-slate-600">
                <li><strong>glossary</strong>: English term &rarr; the word the translator must use (crop, pest, disease, livestock terms).</li>
                <li><strong>pronunciation</strong>: written form &rarr; how the voice should say it (e.g. MSAS FarmAI &rarr; em es ey es fam ey ay).</li>
                <li><strong>unit</strong>: unit symbol &rarr; spoken word (e.g. kg &rarr; kilo). Overrides the built-in default.</li>
                <li><strong>protected</strong>: product or active-ingredient name that must stay exactly as written. Use the "*" list for all languages.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('admin.speech.glossary.store', $lang) }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            @csrf
            <label>Kind<select name="kind" class="w-full rounded-lg border-slate-300">@foreach($kinds as $k)<option>{{ $k }}</option>@endforeach</select></label>
            <label>English / written term<input name="english_term" class="w-full rounded-lg border-slate-300" required></label>
            <label>Local term / spoken form<input name="local_term" class="w-full rounded-lg border-slate-300"></label>
            <label>Notes<input name="notes" class="w-full rounded-lg border-slate-300"></label>
            <button class="bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg">Save term</button>
        </form>

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.speech.glossary.import', $lang) }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-wrap gap-3 items-end">
            @csrf
            <label>Import CSV (kind, english_term, local_term, notes)<input type="file" name="file" accept=".csv,text/csv" class="block mt-1" required></label>
            <button class="bg-slate-800 text-white font-bold px-4 py-2 rounded-lg">Import</button>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 overflow-x-auto">
            <table class="min-w-full">
                <thead><tr class="text-left text-slate-500"><th class="py-2 pr-4">Kind</th><th class="pr-4">Term</th><th class="pr-4">Local / spoken</th><th class="pr-4">Notes</th><th></th></tr></thead>
                <tbody>
                @forelse($terms as $t)
                    <tr class="border-t border-slate-100">
                        <td class="py-2 pr-4">{{ $t->kind }}</td><td class="pr-4 font-semibold">{{ $t->english_term }}</td>
                        <td class="pr-4">{{ $t->local_term }}</td><td class="pr-4 text-slate-500">{{ $t->notes }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.speech.glossary.destroy', $t) }}">@csrf @method('DELETE')<button class="text-red-700">Remove</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-slate-500">No terms yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $terms->links() }}
        </div>
    </div>
</x-app-layout>
