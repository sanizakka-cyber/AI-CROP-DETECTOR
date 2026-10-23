<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Labelled photos for AI testing</h2>
            @if($isReviewer)
            <a href="{{ route('admin.training-images.export') }}"
               class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-slate-900">
                Export approved set (zip + manifest)
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl font-semibold">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-800 p-4 rounded-xl text-sm" data-errors="1">
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @if($diskIsLocal && $isReviewer)
                <div class="bg-amber-50 border border-amber-300 text-amber-900 p-4 rounded-xl text-sm" data-local-disk-warning="1">
                    Photos are being stored on the server's own disk, which Render wipes on every deploy.
                    Set <code>R2_BUCKET</code> (or <code>TRAINING_IMAGES_DISK</code>) before partners start uploading.
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 p-5 text-sm text-slate-700 space-y-1">
                <p><strong>Rules.</strong> Field photos need <strong>written consent</strong> from the animal owner and the photographer
                   (enter the consent form number). Photos from a dataset need its licence; non-commercial (NC) licences are not accepted.
                   No scraped images. Uploads are re-saved without location or camera data and are visible only to you and the MSAS admins.</p>
                <p>One upload = several photos with the <strong>same label</strong>. Only photos approved by an admin and confirmed by a lab or a vet are used to measure accuracy.</p>
            </div>

            {{-- ── Upload ── --}}
            <form method="POST" action="{{ route('admin.training-images.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-xl border border-slate-200 p-5 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                @csrf
                <div class="md:col-span-3">
                    <label class="font-bold block mb-1">Photos (up to {{ config('training_images.max_files') }}; JPEG, PNG, WebP or HEIC)</label>
                    <input type="file" name="images[]" multiple required accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" class="w-full">
                </div>
                <div>
                    <label class="font-bold block mb-1">Scan type</label>
                    <select name="mode" class="w-full rounded-lg border-slate-300">
                        @foreach(['livestock' => 'Livestock', 'crop' => 'Crop / plant', 'pest' => 'Pest', 'soil' => 'Soil'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('mode', 'livestock') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="font-bold block mb-1">Species</label>
                    <select name="species" class="w-full rounded-lg border-slate-300">
                        <option value="">—</option>
                        @foreach(config('training_images.species') as $s)<option value="{{ $s }}" @selected(old('species') === $s)>{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="font-bold block mb-1">Condition (the confirmed label)</label>
                    <input name="condition_label" value="{{ old('condition_label') }}" placeholder="e.g. Lumpy skin disease" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="font-bold block mb-1">Other accepted names</label>
                    <input name="synonyms" value="{{ old('synonyms') }}" placeholder="LSD|Neethling" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="font-bold block mb-1">How the label was confirmed</label>
                    <select name="label_basis" class="w-full rounded-lg border-slate-300">
                        @foreach(config('training_images.label_basis') as $v => $l)<option value="{{ $v }}" @selected(old('label_basis', 'vet_diagnosis') === $v)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="font-bold block mb-1">Or: the photo should be rejected as</label>
                    <select name="expected_outcome" class="w-full rounded-lg border-slate-300">
                        <option value="">— (it shows a condition)</option>
                        <option value="poor_image" @selected(old('expected_outcome') === 'poor_image')>Too unclear to assess</option>
                        <option value="unsupported_subject" @selected(old('expected_outcome') === 'unsupported_subject')>Not an animal / plant / soil</option>
                    </select>
                </div>
                <div><label class="font-bold block mb-1">Body part</label><input name="body_part" value="{{ old('body_part') }}" placeholder="skin, mouth, hoof…" class="w-full rounded-lg border-slate-300"></div>
                <div><label class="font-bold block mb-1">State</label><input name="state" value="{{ old('state', 'Katsina') }}" class="w-full rounded-lg border-slate-300"></div>
                <div><label class="font-bold block mb-1">LGA</label><input name="lga" value="{{ old('lga') }}" class="w-full rounded-lg border-slate-300"></div>
                <div><label class="font-bold block mb-1">Date taken</label><input type="date" name="captured_on" value="{{ old('captured_on') }}" class="w-full rounded-lg border-slate-300"></div>
                <div>
                    <label class="font-bold block mb-1">Source</label>
                    <select name="source_type" class="w-full rounded-lg border-slate-300">
                        <option value="field_partner" @selected(old('source_type', 'field_partner') === 'field_partner')>My own field photo</option>
                        <option value="public_dataset" @selected(old('source_type') === 'public_dataset')>A published dataset</option>
                    </select>
                </div>
                <div>
                    <label class="font-bold block mb-1">Licence</label>
                    <select name="licence" class="w-full rounded-lg border-slate-300">
                        @foreach(config('training_images.licences') as $v => $l)<option value="{{ $v }}" @selected(old('licence', 'partner-consent') === $v)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-slate-100 pt-4">
                    <div><label class="font-bold block mb-1">Field photo: consent form number</label><input name="consent_reference" value="{{ old('consent_reference') }}" placeholder="e.g. KT-2026-014" class="w-full rounded-lg border-slate-300"></div>
                    <div><label class="font-bold block mb-1">Field photo: scanned consent form (optional)</label><input type="file" name="consent_form" accept=".pdf,image/jpeg,image/png" class="w-full"></div>
                    <label class="flex items-start gap-2 mt-6">
                        <input type="checkbox" name="consent_confirmed" value="1" @checked(old('consent_confirmed')) class="mt-1">
                        <span>I have written consent from the animal's owner and the photographer for MSAS to use these photos to test and improve its AI.</span>
                    </label>
                    <div><label class="font-bold block mb-1">Dataset: name and authors</label><input name="source_name" value="{{ old('source_name') }}" placeholder="Lumpy Skin Images Dataset, Kumar & Shastri 2022" class="w-full rounded-lg border-slate-300"></div>
                    <div class="md:col-span-2"><label class="font-bold block mb-1">Dataset: link / DOI</label><input name="source_url" value="{{ old('source_url') }}" placeholder="https://data.mendeley.com/datasets/w36hpf86j2/1" class="w-full rounded-lg border-slate-300"></div>
                </div>
                <div class="md:col-span-3">
                    <button class="bg-emerald-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-emerald-700">Upload for review</button>
                </div>
            </form>

            {{-- ── Progress toward 20 per condition ── --}}
            @if($isReviewer && $counts->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 p-5 text-sm">
                <h3 class="font-bold mb-2">Ready for testing (approved, lab- or vet-confirmed), target 20 per condition</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    @foreach($counts as $c)
                    <div class="flex justify-between border rounded-lg px-3 py-1.5 {{ $c->n >= 20 ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
                        <span>{{ $c->mode }} / {{ $c->condition_label ?? 'rejection photos' }}</span><strong>{{ $c->n }}/20</strong>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ── Photos ── --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr><th class="px-4 py-3">Photo</th><th class="px-4 py-3">Label</th><th class="px-4 py-3">Source</th><th class="px-4 py-3">Status</th>@if($isReviewer)<th class="px-4 py-3">Review</th>@endif</tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($images as $img)
                        <tr data-training-image="{{ $img->id }}">
                            <td class="px-4 py-3">
                                @if($img->review_status !== 'withdrawn')
                                <a href="{{ route('admin.training-images.image', $img) }}" target="_blank"><img src="{{ route('admin.training-images.image', $img) }}" alt="Photo {{ $img->id }}" class="w-20 h-20 object-cover rounded-lg border" loading="lazy"></a>
                                @else <span class="text-xs text-slate-400">deleted (consent withdrawn)</span> @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $img->condition_label ?? ('rejection: ' . $img->expected_outcome) }}</div>
                                <div class="text-xs">{{ $img->mode }}{{ $img->species ? ' · ' . $img->species : '' }}{{ $img->body_part ? ' · ' . $img->body_part : '' }} · {{ config('training_images.label_basis.' . $img->label_basis) }}</div>
                                <div class="text-xs text-slate-400">{{ $img->lga ? $img->lga . ', ' : '' }}{{ $img->state }} {{ $img->captured_on?->format('Y-m-d') }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $img->source_type === 'field_partner' ? 'Field photo, consent ' . $img->consent_reference : $img->source_name }}<br>
                                <span class="font-mono">{{ $img->licence }}</span> · by {{ $img->uploader?->first_name }} {{ $img->uploader?->last_name }}
                            </td>
                            <td class="px-4 py-3"><span class="text-xs font-bold uppercase" data-status="{{ $img->review_status }}">{{ $img->review_status }}</span>
                                @if($img->review_note)<div class="text-xs text-slate-500">{{ $img->review_note }}</div>@endif</td>
                            @if($isReviewer)
                            <td class="px-4 py-3">
                                @if($img->review_status !== 'withdrawn')
                                <form method="POST" action="{{ route('admin.training-images.review', $img) }}" class="flex flex-wrap gap-1 items-center">
                                    @csrf
                                    <input name="review_note" placeholder="note" class="rounded border-slate-300 text-xs w-28">
                                    <button name="decision" value="approved" class="bg-emerald-600 text-white text-xs px-2 py-1 rounded">Approve</button>
                                    <button name="decision" value="rejected" class="bg-slate-500 text-white text-xs px-2 py-1 rounded">Reject</button>
                                    <button name="decision" value="withdrawn" class="bg-red-600 text-white text-xs px-2 py-1 rounded"
                                            onclick="return confirm('Consent withdrawn: delete this photo permanently?')">Consent withdrawn</button>
                                </form>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No photos yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $images->links() }}
        </div>
    </div>
</x-app-layout>
