<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PoultryRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PoultryApiController extends Controller
{
    private static array $birdCodes = [
        'Chicken'     => 'CHK', 'Turkey'      => 'TKY', 'Duck'    => 'DCK',
        'Guinea Fowl' => 'GNF', 'Quail'       => 'QAL', 'Pigeon'  => 'PGN',
        'Ostrich'     => 'OST',
    ];

    private static array $stateCodes = [
        'abia'=>'ABI','adamawa'=>'ADA','akwa ibom'=>'AKI','anambra'=>'ANA',
        'bauchi'=>'BAU','bayelsa'=>'BAY','benue'=>'BEN','borno'=>'BOR',
        'cross river'=>'CRS','delta'=>'DEL','ebonyi'=>'EBO','edo'=>'EDO',
        'ekiti'=>'EKI','enugu'=>'ENU','fct'=>'FCT','gombe'=>'GOM',
        'imo'=>'IMO','jigawa'=>'JIG','kaduna'=>'KAD','kano'=>'KAN',
        'katsina'=>'KTS','kebbi'=>'KEB','kogi'=>'KOG','kwara'=>'KWA',
        'lagos'=>'LAG','nasarawa'=>'NAS','niger'=>'NIG','ogun'=>'OGU',
        'ondo'=>'OND','osun'=>'OSU','oyo'=>'OYO','plateau'=>'PLA',
        'rivers'=>'RIV','sokoto'=>'SOK','taraba'=>'TAR','yobe'=>'YOB','zamfara'=>'ZAM',
    ];

    private function generateBatch(string $birdType, ?string $state): string
    {
        $bc     = self::$birdCodes[ucwords(strtolower($birdType))] ?? 'OTH';
        $stCode = self::$stateCodes[strtolower(trim($state ?? ''))] ?? strtoupper(substr($state ?? 'NGA', 0, 3));
        $prefix = "PLT-{$bc}-{$stCode}-" . now()->format('ym') . '-';

        return DB::transaction(function () use ($prefix) {
            $next = PoultryRecord::select('id')->where('batch_number', 'like', $prefix . '%')->lockForUpdate()->get()->count() + 1;
            return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $flocks = PoultryRecord::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $flocks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_number'    => ['sometimes', 'string', 'max:100'],
            'bird_type'       => ['required', 'string', 'max:100'],
            'bird_type_other' => ['sometimes', 'nullable', 'string', 'max:100'],
            'breed'           => ['sometimes', 'nullable', 'string', 'max:200'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'date_acquired'   => ['required', 'date'],
            'purpose'         => ['sometimes', 'nullable', 'in:meat,egg,breeding,dual-purpose'],
            'notes'           => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $actualBirdType = strtolower($validated['bird_type']) === 'other'
            ? ($validated['bird_type_other'] ?? 'Other')
            : $validated['bird_type'];

        $batchNumber = $validated['batch_number']
            ?? $this->generateBatch($validated['bird_type'], $user->state);

        $data = [
            'user_id'      => $user->id,
            'batch_number' => $batchNumber,
            'bird_type'    => $actualBirdType,
            'quantity'     => $validated['quantity'],
            'date_acquired'=> $validated['date_acquired'],
        ];

        if (Schema::hasColumn('poultry_records', 'breed')) {
            $data['breed'] = $validated['breed'] ?? null;
        }
        if (Schema::hasColumn('poultry_records', 'purpose')) {
            $data['purpose'] = $validated['purpose'] ?? null;
        }
        if (Schema::hasColumn('poultry_records', 'bird_type_other')) {
            $data['bird_type_other'] = $validated['bird_type_other'] ?? null;
        }
        if (Schema::hasColumn('poultry_records', 'needs_admin_review')) {
            $data['needs_admin_review'] = strtolower($validated['bird_type']) === 'other';
        }

        $flock = PoultryRecord::create($data);

        return response()->json([
            'data'     => $flock,
            'message'  => 'Flock registered.',
            'batch_id' => $batchNumber,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $flock = PoultryRecord::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['data' => $flock]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $flock = PoultryRecord::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'bird_type'    => ['sometimes', 'string', 'max:100'],
            'breed'        => ['sometimes', 'nullable', 'string', 'max:200'],
            'quantity'     => ['sometimes', 'integer', 'min:0'],
            'date_acquired'=> ['sometimes', 'date'],
            'purpose'      => ['sometimes', 'nullable', 'in:meat,egg,breeding,dual-purpose'],
            'mortality'    => ['sometimes', 'integer', 'min:0'],
            'notes'        => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $flock->update($validated);

        return response()->json(['data' => $flock, 'message' => 'Flock updated.']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $flock = PoultryRecord::where('user_id', $request->user()->id)->findOrFail($id);
        $flock->delete();

        return response()->json(['message' => 'Flock record deleted.']);
    }

    public function logMortality(Request $request, $id): JsonResponse
    {
        $flock = PoultryRecord::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'count'  => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $newMortality = ($flock->mortality ?? 0) + $validated['count'];

        if ($newMortality > $flock->quantity) {
            return response()->json(['error' => 'Mortality count exceeds flock quantity.'], 422);
        }

        $flock->update(['mortality' => $newMortality]);

        return response()->json([
            'data'    => $flock->fresh(),
            'message' => "Mortality of {$validated['count']} logged.",
        ]);
    }
}
