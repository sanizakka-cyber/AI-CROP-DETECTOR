<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Labelled photo contributions for the evaluation set (Phase 12.2).
 *
 * Partners (vets, extension officers, agronomists) upload photos with one
 * label set; admins review, approve and export an eval manifest. Rules:
 * - a field photo needs written consent (reference + confirmation);
 * - a dataset photo needs a licence that allows commercial use (no NC);
 * - photos are re-encoded on upload, which removes EXIF data including GPS;
 * - photos are private: served only to signed-in uploaders and reviewers;
 * - the same photo cannot be added twice (SHA-256 of the stored file).
 */
class TrainingImageController extends Controller
{
    private function isReviewer(): bool
    {
        return \App\Support\Roles::isAny(auth()->user()->role, config('training_images.reviewer_roles'));
    }

    private function disk()
    {
        return Storage::disk(config('training_images.disk'));
    }

    public function index(Request $request)
    {
        $q = TrainingImage::with('uploader:id,first_name,last_name,role')->latest();
        if (!$this->isReviewer()) {
            $q->where('uploaded_by', auth()->id());
        }
        foreach (['review_status', 'mode', 'species', 'label_basis', 'source_type'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->input($f));
            }
        }
        if ($request->filled('condition')) {
            $q->where('condition_label', 'like', '%' . $request->input('condition') . '%');
        }

        $counts = TrainingImage::evalReady()
            ->selectRaw('mode, condition_label, count(*) as n')->groupBy('mode', 'condition_label')
            ->orderBy('mode')->orderBy('condition_label')->get();

        return view('admin.training-images.index', [
            'images'     => $q->paginate(30)->withQueryString(),
            'counts'     => $counts,
            'isReviewer' => $this->isReviewer(),
            'diskIsLocal' => config('training_images.disk') === 'local',
        ]);
    }

    public function store(Request $request)
    {
        $licences = array_keys(config('training_images.licences'));
        $data = $request->validate([
            'images'            => 'required|array|min:1|max:' . config('training_images.max_files'),
            'images.*'          => 'file|mimes:jpeg,jpg,png,webp|max:' . config('training_images.max_upload_kb'),
            'mode'              => 'required|in:crop,livestock,soil,pest',
            'species'           => ['nullable', Rule::in(config('training_images.species'))],
            'condition_label'   => 'required_without:expected_outcome|nullable|string|max:150',
            'synonyms'          => 'nullable|string|max:300',
            'expected_outcome'  => 'nullable|in:poor_image,unsupported_subject',
            'label_basis'       => ['required', Rule::in(array_keys(config('training_images.label_basis')))],
            'body_part'         => 'nullable|string|max:60',
            'state'             => 'nullable|string|max:60',
            'lga'               => 'nullable|string|max:80',
            'captured_on'       => 'nullable|date|before_or_equal:today',
            'source_type'       => 'required|in:field_partner,public_dataset',
            'source_name'       => 'required_if:source_type,public_dataset|nullable|string|max:200',
            'source_url'        => 'required_if:source_type,public_dataset|nullable|url|max:300',
            'licence'           => ['required', Rule::in($licences)],
            'consent_reference' => 'required_if:source_type,field_partner|nullable|string|max:100',
            'consent_confirmed' => 'required_if:source_type,field_partner|accepted_if:source_type,field_partner',
            'consent_form'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'consent_confirmed.accepted_if' => 'Field photos need written consent from the animal owner and the photographer.',
        ]);

        // A field photo is covered by its consent form; a dataset photo by an open licence.
        if ($data['source_type'] === 'field_partner' && $data['licence'] !== 'partner-consent') {
            throw ValidationException::withMessages(['licence' => 'A field photo is covered by the partner consent form: choose "Written consent".']);
        }
        if ($data['source_type'] === 'public_dataset' && $data['licence'] === 'partner-consent') {
            throw ValidationException::withMessages(['licence' => 'Choose the dataset\'s own licence.']);
        }

        $consentPath = $request->hasFile('consent_form')
            ? $request->file('consent_form')->store('training-consent', config('training_images.disk'))
            : null;

        $added = $duplicates = 0;
        foreach ($request->file('images') as $file) {
            [$jpeg, $w, $h] = $this->reencode($file);
            $sha = hash('sha256', $jpeg);
            if (TrainingImage::where('sha256', $sha)->exists()) {
                $duplicates++;
                continue;
            }
            $path = 'training-images/' . $data['mode'] . '/' . $sha . '.jpg';
            $this->disk()->put($path, $jpeg);

            $img = new TrainingImage(collect($data)->except(['images', 'consent_form'])->all());
            $img->forceFill([
                'uploaded_by' => auth()->id(), 'disk' => config('training_images.disk'), 'image_path' => $path,
                'sha256' => $sha, 'width' => $w, 'height' => $h, 'consent_form_path' => $consentPath,
                'consent_confirmed' => $data['source_type'] === 'field_partner',
                'review_status' => 'pending',
            ])->save();
            $added++;
        }

        return redirect()->route('admin.training-images.index')
            ->with('success', "{$added} photo(s) added for review" . ($duplicates ? ", {$duplicates} already in the set were skipped" : '') . '.');
    }

    /**
     * Decode, apply the camera orientation, cap the longest side and save as
     * JPEG. The new file has no EXIF block, so GPS and device data are gone.
     */
    private function reencode(UploadedFile $file): array
    {
        $bytes = file_get_contents($file->getRealPath());
        $im = @imagecreatefromstring($bytes);
        if (!$im) {
            throw ValidationException::withMessages(['images' => "{$file->getClientOriginalName()} could not be read as an image."]);
        }
        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $exif = @exif_read_data($file->getRealPath());
            $im = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => imagerotate($im, 180, 0), 6 => imagerotate($im, -90, 0), 8 => imagerotate($im, 90, 0), default => $im,
            };
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $max = (int) config('training_images.max_side');
        if (max($w, $h) > $max) {
            $scale = $max / max($w, $h);
            $im = imagescale($im, (int) round($w * $scale), (int) round($h * $scale));
            [$w, $h] = [imagesx($im), imagesy($im)];
        }
        ob_start();
        imagejpeg($im, null, (int) config('training_images.jpeg_quality'));
        imagedestroy($im);
        return [ob_get_clean(), $w, $h];
    }

    public function image(TrainingImage $trainingImage)
    {
        abort_unless($this->isReviewer() || $trainingImage->uploaded_by === auth()->id(), 403);
        abort_if($trainingImage->review_status === 'withdrawn', 410);
        $disk = Storage::disk($trainingImage->disk);
        abort_unless($disk->exists($trainingImage->image_path), 404);
        return response($disk->get($trainingImage->image_path), 200, [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=600', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function review(Request $request, TrainingImage $trainingImage)
    {
        abort_unless($this->isReviewer(), 403);
        $data = $request->validate([
            'decision'        => 'required|in:approved,rejected,withdrawn',
            'review_note'     => 'nullable|string|max:500',
            'condition_label' => 'nullable|string|max:150',
            'label_basis'     => ['nullable', Rule::in(array_keys(config('training_images.label_basis')))],
        ]);

        if ($data['decision'] === 'withdrawn') {
            // Consent withdrawn: the photo itself is deleted; the row stays as a record of it.
            Storage::disk($trainingImage->disk)->delete($trainingImage->image_path);
        }
        $trainingImage->forceFill(array_filter([
            'review_status' => $data['decision'], 'review_note' => $data['review_note'] ?? null,
            'condition_label' => $data['condition_label'] ?? null, 'label_basis' => $data['label_basis'] ?? null,
            'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
        ], fn ($v) => $v !== null))->save();

        return back()->with('success', "Photo #{$trainingImage->id}: {$data['decision']}.");
    }

    /**
     * Approved, lab- or vet-confirmed photos as a zip with manifest.csv in
     * the format ai-engine/eval/run_eval.py reads, plus ATTRIBUTION.txt.
     */
    public function export()
    {
        abort_unless($this->isReviewer(), 403);
        $rows = TrainingImage::evalReady()->orderBy('mode')->orderBy('condition_label')->get();
        abort_if($rows->isEmpty(), 404, 'No approved photos yet.');

        $zipPath = tempnam(sys_get_temp_dir(), 'evalset');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['image', 'mode', 'expected', 'synonyms', 'expected_outcome', 'licence', 'source', 'label_basis']);
        $attrib = [];
        foreach ($rows as $r) {
            $disk = Storage::disk($r->disk);
            if (!$disk->exists($r->image_path)) {
                continue;
            }
            $name = "{$r->mode}/{$r->id}.jpg";
            $zip->addFromString($name, $disk->get($r->image_path));
            fputcsv($csv, [$name, $r->mode, $r->condition_label, $r->synonyms, $r->expected_outcome, $r->licence,
                           $r->source_type === 'public_dataset' ? $r->source_name : 'MSAS field partner (consent ' . $r->consent_reference . ')', $r->label_basis]);
            if ($r->source_type === 'public_dataset') {
                $attrib[$r->source_name . ' — ' . $r->source_url . ' — ' . $r->licence] = true;
            }
        }
        rewind($csv);
        $zip->addFromString('manifest.csv', stream_get_contents($csv));
        $zip->addFromString('ATTRIBUTION.txt', "Images from these datasets, used under their licences:\n" . implode("\n", array_keys($attrib)) . "\n");
        $zip->close();

        return response()->download($zipPath, 'msas-eval-set-' . now()->format('Ymd') . '.zip')->deleteFileAfterSend();
    }
}
