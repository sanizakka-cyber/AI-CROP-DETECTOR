<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Consultation;
use App\Models\Finance;
use App\Models\PoultryRecord;
use App\Models\SubscriptionUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerController extends Controller
{
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

        // Enforce livestock limit for Basic plan
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
        } elseif (!$activeSub) {
            return back()->with('error',
                'You need an active subscription to register livestock. '
                . '<a href="' . route('subscription.plans') . '" style="color:#0F6B3E;font-weight:700;">Start your free trial</a>.'
            );
        }

        $validated = $request->validate([
            'tag_number'    => 'required|string|max:50',
            'species'       => 'required|string',
            'breed'         => 'nullable|string',
            'gender'        => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date',
            'weight_kg'     => 'nullable|numeric',
        ]);

        $validated['user_id'] = $user->id;
        Animal::create($validated);

        // Track usage for metering
        SubscriptionUsage::increment($user->id, 'livestock_records');

        return back()->with('success', 'Livestock registered successfully.');
    }

    // ── Poultry ────────────────────────────────────────────────────────

    public function poultry()
    {
        $flocks = PoultryRecord::where('user_id', Auth::id())->latest()->get();
        return view('farmer.poultry', compact('flocks'));
    }

    public function storePoultry(Request $request)
    {
        $validated = $request->validate([
            'batch_number' => 'required|string',
            'bird_type'    => 'required|string',
            'quantity'     => 'required|integer|min:1',
            'date_acquired'=> 'required|date',
        ]);

        $validated['user_id'] = Auth::id();
        PoultryRecord::create($validated);

        return back()->with('success', 'Poultry batch added successfully.');
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

    public function storeConsult(Request $request)
    {
        // Requires Pro+ feature
        $user      = Auth::user();
        $activeSub = $user->activeSubscription();

        if (!$activeSub || !$activeSub->hasFeature('vet_service_requests')) {
            return back()->with('error',
                'Veterinary consultation requests require the Pro Plan or higher. '
                . '<a href="' . route('subscription.plans') . '" style="color:#0F6B3E;font-weight:700;">Upgrade now</a>.'
            );
        }

        $validated = $request->validate([
            'animal_type' => 'required|string',
            'symptoms'    => 'required|string',
            'priority'    => 'required|in:low,medium,high,critical',
        ]);

        $validated['farmer_id'] = Auth::id();
        $validated['case_type'] = 'livestock';
        $validated['status']    = 'pending';

        Consultation::create($validated);

        return back()->with('success', 'Consultation requested successfully. A vet will review it shortly.');
    }

    public function viewConsult(Consultation $consultation)
    {
        if ($consultation->farmer_id !== Auth::id()) {
            abort(403);
        }
        return view('farmer.vet-report', compact('consultation'));
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
