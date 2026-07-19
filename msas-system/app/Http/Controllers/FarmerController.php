<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Consultation;
use App\Models\EggProduction;
use App\Models\Finance;
use App\Models\PoultryRecord;
use App\Models\SubscriptionUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FarmerController extends Controller
{
    // ── Species / breed reference data ─────────────────────────────────
    private static array $speciesCodes = [
        'Cattle'  => 'CTL', 'Goat'   => 'GOT', 'Sheep'  => 'SHP',
        'Pig'     => 'PIG', 'Camel'  => 'CAM', 'Donkey' => 'DNK',
        'Horse'   => 'HRS', 'Rabbit' => 'RBT', 'Other'  => 'OTH',
    ];

    private static array $birdCodes = [
        'Chicken'     => 'CHK', 'Turkey'      => 'TKY', 'Duck'    => 'DCK',
        'Guinea Fowl' => 'GNF', 'Quail'       => 'QAL', 'Pigeon'  => 'PGN',
        'Ostrich'     => 'OST', 'Other'        => 'OTH',
    ];

    private static array $stateCodes = [
        'abia' => 'ABI', 'adamawa' => 'ADA', 'akwa ibom' => 'AKI', 'anambra' => 'ANA',
        'bauchi' => 'BAU', 'bayelsa' => 'BAY', 'benue' => 'BEN', 'borno' => 'BOR',
        'cross river' => 'CRS', 'delta' => 'DEL', 'ebonyi' => 'EBO', 'edo' => 'EDO',
        'ekiti' => 'EKI', 'enugu' => 'ENU', 'fct' => 'FCT', 'gombe' => 'GOM',
        'imo' => 'IMO', 'jigawa' => 'JIG', 'kaduna' => 'KAD', 'kano' => 'KAN',
        'katsina' => 'KTS', 'kebbi' => 'KEB', 'kogi' => 'KOG', 'kwara' => 'KWA',
        'lagos' => 'LAG', 'nasarawa' => 'NAS', 'niger' => 'NIG', 'ogun' => 'OGU',
        'ondo' => 'OND', 'osun' => 'OSU', 'oyo' => 'OYO', 'plateau' => 'PLA',
        'rivers' => 'RIV', 'sokoto' => 'SOK', 'taraba' => 'TAR', 'yobe' => 'YOB',
        'zamfara' => 'ZAM',
    ];

    private function stateCode(?string $state): string
    {
        $key = strtolower(trim($state ?? ''));
        return self::$stateCodes[$key] ?? strtoupper(substr($state ?? 'NGA', 0, 3));
    }

    // ── Livestock ──────────────────────────────────────────────────────

    public function livestock()
    {
        $livestock = Animal::where('user_id', Auth::id())->latest()->get();
        return view('farmer.livestock', compact('livestock'));
    }

    public function storeLivestock(Request $request)
    {
        $user      = Auth::user();
        $activeSub = $user->activeSubscription();

        if ($activeSub) {
            $limit = $activeSub->getLimit('livestock_records');
            if ($limit !== -1) {
                $current = Animal::where('user_id', $user->id)->count();
                if ($current >= $limit) {
                    return back()->with('error',
                        "You've reached your {$limit}-animal limit on the Basic Plan. "
                        . '<a href="' . route('subscription.plans') . '" style="color:#0F6B3E;font-weight:700;">Upgrade to Pro</a> for unlimited records.'
                    );
                }
            }
        } else {
            return back()->with('error',
                'You need an active subscription to register livestock. '
                . '<a href="' . route('subscription.plans') . '" style="color:#0F6B3E;font-weight:700;">Start your free trial</a>.'
            );
        }

        $validated = $request->validate([
            'name'          => 'nullable|string|max:100',
            'species'       => 'required|string|in:Cattle,Goat,Sheep,Pig,Camel,Donkey,Horse,Rabbit,Other',
            'species_other' => 'required_if:species,Other|nullable|string|max:100',
            'breed'         => 'nullable|string|max:200',
            'breed_other'   => 'nullable|string|max:100',
            'gender'        => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'weight_kg'     => 'nullable|numeric|min:0',
        ]);

        $actualSpecies = $validated['species'] === 'Other'
            ? ($validated['species_other'] ?? 'Other')
            : $validated['species'];

        $actualBreed = ($validated['breed'] ?? null) === 'Other'
            ? ($validated['breed_other'] ?? null)
            : ($validated['breed'] ?? null);

        $speciesCode = self::$speciesCodes[$validated['species']] ?? 'OTH';
        $stateCode   = $this->stateCode($user->state);
        $yymm        = now()->format('ym');
        $prefix      = "{$speciesCode}-{$stateCode}-{$yymm}-";

        $tagNumber = DB::transaction(function () use ($prefix) {
            $next = Animal::select('id')->where('tag_number', 'like', $prefix . '%')->lockForUpdate()->get()->count() + 1;
            return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        });

        $animalData = [
            'user_id'       => $user->id,
            'tag_number'    => $tagNumber,
            'species'       => $actualSpecies,
            'breed'         => $actualBreed,
            'gender'        => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'weight_kg'     => $validated['weight_kg'] ?? null,
            'health_status' => 'healthy',
        ];
        if (Schema::hasColumn('animals', 'name')) {
            $animalData['name'] = $validated['name'] ?? null;
        }
        if (Schema::hasColumn('animals', 'needs_admin_review')) {
            $animalData['needs_admin_review'] = $validated['species'] === 'Other';
        }
        if (Schema::hasColumn('animals', 'species_other')) {
            $animalData['species_other'] = $validated['species_other'] ?? null;
        }
        if (Schema::hasColumn('animals', 'breed_other')) {
            $animalData['breed_other'] = $validated['breed_other'] ?? null;
        }

        try {
            Animal::create($animalData);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->with('error',
                'Registration failed. Please try again. If the problem persists, contact support.'
            );
        }

        try {
            SubscriptionUsage::increment($user->id, 'livestock_records');
        } catch (\Exception $e) {
            // Usage tracking failure should not block the farmer
        }

        return back()->with('success', "Animal registered. Tag ID: <strong>{$tagNumber}</strong>");
    }

    // ── Poultry ────────────────────────────────────────────────────────

    public function poultry()
    {
        $flocks = PoultryRecord::where('user_id', Auth::id())->latest()->get();
        return view('farmer.poultry', compact('flocks'));
    }

    public function storePoultry(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bird_type'       => 'required|string|in:Chicken,Turkey,Duck,Guinea Fowl,Quail,Pigeon,Ostrich,Other',
            'bird_type_other' => 'required_if:bird_type,Other|nullable|string|max:100',
            'breed'           => 'nullable|string|max:200',
            'quantity'        => 'required|integer|min:1',
            'date_acquired'   => 'required|date',
            'purpose'         => 'nullable|in:meat,egg,breeding,dual-purpose',
        ]);

        $actualBirdType = $validated['bird_type'] === 'Other'
            ? ($validated['bird_type_other'] ?? 'Other')
            : $validated['bird_type'];

        $birdCode  = self::$birdCodes[$validated['bird_type']] ?? 'OTH';
        $stateCode = $this->stateCode($user->state);
        $yymm      = now()->format('ym');
        $prefix    = "PLT-{$birdCode}-{$stateCode}-{$yymm}-";

        $batchNumber = DB::transaction(function () use ($prefix) {
            $next = PoultryRecord::select('id')->where('batch_number', 'like', $prefix . '%')->lockForUpdate()->get()->count() + 1;
            return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        });

        $poultryData = [
            'user_id'       => $user->id,
            'batch_number'  => $batchNumber,
            'bird_type'     => $actualBirdType,
            'quantity'      => $validated['quantity'],
            'date_acquired' => $validated['date_acquired'],
        ];
        if (Schema::hasColumn('poultry_records', 'breed')) {
            $poultryData['breed'] = $validated['breed'] ?? null;
        }
        if (Schema::hasColumn('poultry_records', 'purpose')) {
            $poultryData['purpose'] = $validated['purpose'] ?? null;
        }
        if (Schema::hasColumn('poultry_records', 'needs_admin_review')) {
            $poultryData['needs_admin_review'] = $validated['bird_type'] === 'Other';
        }
        if (Schema::hasColumn('poultry_records', 'bird_type_other')) {
            $poultryData['bird_type_other'] = $validated['bird_type_other'] ?? null;
        }

        try {
            PoultryRecord::create($poultryData);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->with('error',
                'Registration failed. Please try again. If the problem persists, contact support.'
            );
        }

        return back()->with('success', "Flock registered. Batch ID: <strong>{$batchNumber}</strong>");
    }

    public function updateLivestock(Request $request, int $id)
    {
        $animal = Animal::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name'      => 'nullable|string|max:100',
            'breed'     => 'nullable|string|max:200',
            'gender'    => 'nullable|in:Male,Female',
            'weight_kg' => 'nullable|numeric|min:0',
        ]);

        $animal->update(array_filter($validated, fn($v) => $v !== null && $v !== ''));

        return back()->with('success', 'Animal record updated successfully.');
    }

    public function updatePoultry(Request $request, int $id)
    {
        $flock = PoultryRecord::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'breed'         => 'nullable|string|max:200',
            'quantity'      => 'nullable|integer|min:1',
            'date_acquired' => 'nullable|date',
            'purpose'       => 'nullable|in:meat,egg,breeding,dual-purpose',
        ]);

        $flock->update(array_filter($validated, fn($v) => $v !== null && $v !== ''));

        return back()->with('success', 'Flock record updated successfully.');
    }

    public function logMortality(Request $request, int $id)
    {
        $flock = PoultryRecord::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'count' => 'required|integer|min:1',
            'date'  => 'required|date',
            'cause' => 'nullable|string|max:500',
        ]);

        $currentLive = $flock->quantity - ($flock->mortality ?? 0);
        if ($validated['count'] > $currentLive) {
            return back()->with('error', "Cannot log {$validated['count']} deaths — only {$currentLive} live birds in this batch.");
        }

        $flock->increment('mortality', $validated['count']);

        return back()->with('success', "{$validated['count']} mortality logged for batch <strong>{$flock->batch_number}</strong>. Live count: <strong>" . ($currentLive - $validated['count']) . "</strong>.");
    }

    public function logEggs(Request $request, int $id)
    {
        $flock = PoultryRecord::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'quantity'        => 'required|integer|min:1',
            'broken'          => 'nullable|integer|min:0',
            'production_date' => 'required|date',
            'unit_price'      => 'nullable|numeric|min:0',
        ]);

        $qty       = $validated['quantity'];
        $broken    = $validated['broken'] ?? 0;
        $unitPrice = $validated['unit_price'] ?? 0;

        $eggData = [
            'user_id'         => Auth::id(),
            'production_date' => $validated['production_date'],
            'quantity'        => $qty,
            'broken'          => $broken,
            'unit_price'      => $unitPrice,
        ];
        if (Schema::hasColumn('egg_productions', 'poultry_record_id')) {
            $eggData['poultry_record_id'] = $flock->id;
        }

        EggProduction::create($eggData);

        return back()->with('success', "{$qty} eggs logged for batch <strong>{$flock->batch_number}</strong>.");
    }

    // ── Finance ────────────────────────────────────────────────────────

    public function finance()
    {
        $finances     = Finance::where('user_id', Auth::id())->latest('transaction_date')->get();
        $totalIncome  = Finance::where('user_id', Auth::id())->where('type', 'Income')->sum('amount');
        $totalExpense = Finance::where('user_id', Auth::id())->where('type', 'Expense')->sum('amount');

        return view('farmer.finance', compact('finances', 'totalIncome', 'totalExpense'));
    }

    public function storeFinance(Request $request)
    {
        $validated = $request->validate([
            'type'             => 'required|in:Income,Expense',
            'category'         => 'required|string',
            'amount'           => 'required|numeric|min:0',
            'description'      => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $validated['user_id'] = Auth::id();
        Finance::create($validated);

        return back()->with('success', 'Financial record added successfully.');
    }

    // ── Vet Consultation ───────────────────────────────────────────────

    public function vetConsult()
    {
        $consultations = Consultation::where('farmer_id', Auth::id())->latest()->get();
        return view('farmer.vet', compact('consultations'));
    }

    private const CHANNEL_FEES = [
        'in_app'     => 1500,
        'whatsapp'   => 2500,
        'phone_call' => 3500,
    ];

    public function storeConsult(Request $request)
    {
        $user      = Auth::user();
        $activeSub = $user->activeSubscription();

        if (!$activeSub || !$activeSub->hasFeature('vet_service_requests')) {
            return back()->with('error',
                'Veterinary consultation requests require the Pro Plan or higher. '
                . '<a href="' . route('subscription.plans') . '" style="color:#0F6B3E;font-weight:700;">Upgrade now</a>.'
            );
        }

        $validated = $request->validate([
            'animal_type' => 'required|string|max:100',
            'symptoms'    => 'required|string|max:2000',
            'priority'    => 'required|in:low,medium,high,critical',
            'channel'     => 'required|in:in_app,whatsapp,phone_call',
        ]);

        $fee  = self::CHANNEL_FEES[$validated['channel']];
        $ref  = 'MSAS-CONSULT-' . strtoupper(Str::random(10));

        $consultation = Consultation::create([
            'farmer_id'      => Auth::id(),
            'case_type'      => 'livestock',
            'animal_type'    => $validated['animal_type'],
            'symptoms'       => $validated['symptoms'],
            'priority'       => $validated['priority'],
            'channel'        => $validated['channel'],
            'fee'            => $fee,
            'status'         => 'awaiting_payment',
            'payment_status' => 'unpaid',
            'payment_reference' => $ref,
        ]);

        return $this->redirectToConsultationPayment($user, $consultation, $fee, $ref, 'farmer.vet');
    }

    public function viewConsult(Consultation $consultation)
    {
        if ($consultation->farmer_id !== Auth::id()) {
            abort(403);
        }
        return view('farmer.vet-report', compact('consultation'));
    }

    // ── Agronomist Request ─────────────────────────────────────────────

    public function agroRequest()
    {
        $requests = Consultation::where('farmer_id', Auth::id())
            ->where('case_type', 'crop')
            ->latest()
            ->get();
        return view('farmer.agro', compact('requests'));
    }

    public function storeAgroRequest(Request $request)
    {
        $user      = Auth::user();
        $activeSub = $user->activeSubscription();

        if (!$activeSub || !$activeSub->hasFeature('vet_service_requests')) {
            return back()->with('error',
                'Agronomist advisory requests require the Pro Plan or higher. '
                . '<a href="' . route('subscription.plans') . '" style="color:#0F6B3E;font-weight:700;">Upgrade now</a>.'
            );
        }

        $validated = $request->validate([
            'crop_type' => 'required|string|max:100',
            'symptoms'  => 'required|string|max:2000',
            'priority'  => 'required|in:low,medium,high,critical',
            'channel'   => 'required|in:in_app,whatsapp,phone_call',
        ]);

        $fee = self::CHANNEL_FEES[$validated['channel']];
        $ref = 'MSAS-AGRO-' . strtoupper(Str::random(10));

        $consultation = Consultation::create([
            'farmer_id'         => Auth::id(),
            'case_type'         => 'crop',
            'crop_type'         => $validated['crop_type'],
            'symptoms'          => $validated['symptoms'],
            'priority'          => $validated['priority'],
            'channel'           => $validated['channel'],
            'fee'               => $fee,
            'status'            => 'awaiting_payment',
            'payment_status'    => 'unpaid',
            'payment_reference' => $ref,
        ]);

        return $this->redirectToConsultationPayment($user, $consultation, $fee, $ref, 'farmer.agro');
    }

    private function redirectToConsultationPayment($user, Consultation $consultation, int $fee, string $ref, string $fallbackRoute)
    {
        $paystackKey = config('services.paystack.secret_key');
        $paystackUrl = config('services.paystack.payment_url');

        if ($paystackKey && !str_contains($paystackKey, 'REPLACE')) {
            $response = Http::withToken($paystackKey)
                ->post("{$paystackUrl}/transaction/initialize", [
                    'email'        => $user->email,
                    'amount'       => $fee * 100,
                    'reference'    => $ref,
                    'currency'     => 'NGN',
                    'callback_url' => route('consultation.payment.callback'),
                    'metadata'     => [
                        'user_id'         => $user->id,
                        'consultation_id' => $consultation->id,
                        'cancel_action'   => route($fallbackRoute),
                    ],
                ]);

            if ($response->successful() && $response->json('status')) {
                return redirect($response->json('data.authorization_url'));
            }
        }

        // Dev fallback: mark paid immediately
        $consultation->update(['payment_status' => 'paid', 'status' => 'open']);
        return redirect()->route($fallbackRoute)
            ->with('success', 'Request submitted successfully. A specialist will review it shortly.');
    }

    public function consultationPaymentCallback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return redirect()->route('farmer.vet')->with('error', 'Invalid payment reference.');
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

        $consultation = Consultation::where('payment_reference', $reference)->first();

        if (!$consultation || !$response->successful() || $response->json('data.status') !== 'success') {
            $cancelRoute = $consultation?->case_type === 'crop' ? 'farmer.agro' : 'farmer.vet';
            return redirect()->route($cancelRoute)
                ->with('error', 'Payment could not be confirmed. Please try again or contact support. Ref: ' . $reference);
        }

        if ($consultation->payment_status !== 'paid') {
            $consultation->update(['payment_status' => 'paid', 'status' => 'open']);
        }

        $route = $consultation->case_type === 'crop' ? 'farmer.agro' : 'farmer.vet';
        return redirect()->route($route)
            ->with('success', 'Payment confirmed! Your request is now in the specialist queue.');
    }

    // ── Reports ────────────────────────────────────────────────────────

    public function reports()
    {
        $user      = Auth::user();
        $activeSub = $user->activeSubscription();

        // Requires Pro+ subscription
        if (!$activeSub || !$activeSub->hasFeature('pdf_excel_reports')) {
            return redirect()->route('subscription.plans')
                ->with('warning', 'Farm reports require the Pro Plan. Upgrade to download PDF, Excel, and CSV reports.');
        }

        $livestock    = Animal::where('user_id', $user->id)->latest()->get();
        $consultations= Consultation::where('farmer_id', $user->id)->latest()->get();
        $finances     = Finance::where('user_id', $user->id)->latest('transaction_date')->get();
        $totalIncome  = Finance::where('user_id', $user->id)->where('type', 'Income')->sum('amount');
        $totalExpense = Finance::where('user_id', $user->id)->where('type', 'Expense')->sum('amount');
        $poultry      = PoultryRecord::where('user_id', $user->id)->latest()->get();

        return view('farmer.reports', compact(
            'livestock', 'consultations', 'finances',
            'totalIncome', 'totalExpense', 'poultry', 'activeSub'
        ));
    }

    public function downloadReport(Request $request)
    {
        $user      = Auth::user();
        $activeSub = $user->activeSubscription();

        if (!$activeSub || !$activeSub->hasFeature('pdf_excel_reports')) {
            abort(403, 'Pro plan required.');
        }

        $format = $request->input('format', 'csv');
        $type   = $request->input('type', 'livestock');

        if (! in_array($format, ['csv', 'pdf'])) {
            return back()->with('error', 'Unsupported format.');
        }
        if (! in_array($type, ['livestock', 'finance', 'consultations', 'poultry'])) {
            return back()->with('error', 'Unsupported report type.');
        }

        // Track usage
        SubscriptionUsage::increment($user->id, 'reports_generated');

        if ($format === 'csv') {
            return $this->downloadCsv($user, $type);
        }

        // PDF: return print view
        if ($format === 'pdf') {
            $livestock     = Animal::where('user_id', $user->id)->get();
            $finances      = Finance::where('user_id', $user->id)->get();
            $consultations = Consultation::where('farmer_id', $user->id)->get();
            $totalIncome   = $finances->where('type', 'Income')->sum('amount');
            $totalExpense  = $finances->where('type', 'Expense')->sum('amount');
            return view('farmer.report-pdf', compact(
                'user', 'livestock', 'finances', 'consultations',
                'totalIncome', 'totalExpense', 'type'
            ));
        }

        return back()->with('error', 'Unsupported format.');
    }

    private function downloadCsv($user, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = "msas-{$type}-" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($user, $type) {
            $fh = fopen('php://output', 'w');

            if ($type === 'livestock') {
                fputcsv($fh, ['ID', 'Tag Number', 'Name', 'Species', 'Breed', 'Gender', 'Date of Birth', 'Weight (kg)', 'Health Status', 'Registered At']);
                Animal::where('user_id', $user->id)->each(function ($a) use ($fh) {
                    fputcsv($fh, [$a->id, $a->tag_number, $a->name, $a->species, $a->breed, $a->gender, $a->date_of_birth, $a->weight_kg, $a->health_status, $a->created_at->format('Y-m-d')]);
                });
            } elseif ($type === 'finance') {
                fputcsv($fh, ['ID', 'Type', 'Category', 'Amount (₦)', 'Description', 'Date']);
                Finance::where('user_id', $user->id)->each(function ($f) use ($fh) {
                    fputcsv($fh, [$f->id, $f->type, $f->category, $f->amount, $f->description, $f->transaction_date]);
                });
            } elseif ($type === 'consultations') {
                fputcsv($fh, ['ID', 'Animal Type', 'Symptoms', 'Priority', 'Status', 'Date']);
                Consultation::where('farmer_id', $user->id)->each(function ($c) use ($fh) {
                    fputcsv($fh, [$c->id, $c->animal_type, $c->symptoms, $c->priority, $c->status, $c->created_at->format('Y-m-d')]);
                });
            } elseif ($type === 'poultry') {
                fputcsv($fh, ['ID', 'Batch Number', 'Bird Type', 'Quantity', 'Date Acquired']);
                PoultryRecord::where('user_id', $user->id)->each(function ($p) use ($fh) {
                    fputcsv($fh, [$p->id, $p->batch_number, $p->bird_type, $p->quantity, $p->date_acquired]);
                });
            }

            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
