<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnimalApiController extends Controller
{
    private static array $speciesCodes = [
        'Cattle'  => 'CTL', 'Goat'   => 'GOT', 'Sheep'  => 'SHP',
        'Pig'     => 'PIG', 'Camel'  => 'CAM', 'Donkey' => 'DNK',
        'Horse'   => 'HRS', 'Rabbit' => 'RBT',
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

    private function generateTag(string $species, ?string $state): string
    {
        $sc     = self::$speciesCodes[ucfirst(strtolower($species))] ?? 'OTH';
        $stCode = self::$stateCodes[strtolower(trim($state ?? ''))] ?? strtoupper(substr($state ?? 'NGA', 0, 3));
        $prefix = "{$sc}-{$stCode}-" . now()->format('ym') . '-';

        return DB::transaction(function () use ($prefix) {
            $next = Animal::select('id')->where('tag_number', 'like', $prefix . '%')->lockForUpdate()->get()->count() + 1;
            return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $animals = Animal::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $animals]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tag_number'    => ['sometimes', 'string', 'max:100'],
            'name'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'species'       => ['required', 'string', 'max:100'],
            'species_other' => ['sometimes', 'nullable', 'string', 'max:100'],
            'breed'         => ['sometimes', 'nullable', 'string', 'max:200'],
            'breed_other'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'gender'        => ['sometimes', 'string', 'in:Male,Female,Unknown'],
            'date_of_birth' => ['sometimes', 'date'],
            'weight_kg'     => ['sometimes', 'numeric', 'min:0'],
            'health_status' => ['sometimes', 'string', 'in:healthy,sick,under_treatment,deceased'],
            'notes'         => ['sometimes', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $tagNumber = $validated['tag_number'] ?? $this->generateTag($validated['species'], $user->state);

        $data = array_filter([
            'user_id'       => $user->id,
            'tag_number'    => $tagNumber,
            'species'       => $validated['species'],
            'breed'         => $validated['breed'] ?? null,
            'gender'        => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'weight_kg'     => $validated['weight_kg'] ?? null,
            'health_status' => $validated['health_status'] ?? 'healthy',
        ], fn ($v) => $v !== null);

        if (Schema::hasColumn('animals', 'name')) {
            $data['name'] = $validated['name'] ?? null;
        }
        if (Schema::hasColumn('animals', 'species_other')) {
            $data['species_other'] = $validated['species_other'] ?? null;
        }
        if (Schema::hasColumn('animals', 'breed_other')) {
            $data['breed_other'] = $validated['breed_other'] ?? null;
        }
        if (Schema::hasColumn('animals', 'needs_admin_review')) {
            $data['needs_admin_review'] = strtolower($validated['species']) === 'other';
        }

        $animal = Animal::create($data);

        return response()->json([
            'data'    => $animal,
            'message' => 'Animal registered.',
            'tag_id'  => $tagNumber,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $animal = Animal::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['data' => $animal]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $animal = Animal::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'tag_number'    => ['sometimes', 'string', 'max:100'],
            'species'       => ['sometimes', 'string', 'max:100'],
            'breed'         => ['sometimes', 'string', 'max:100'],
            'gender'        => ['sometimes', 'string', 'in:Male,Female,Unknown'],
            'age_months'    => ['sometimes', 'integer', 'min:0'],
            'weight_kg'     => ['sometimes', 'numeric', 'min:0'],
            'health_status' => ['sometimes', 'string', 'in:healthy,sick,under_treatment,deceased'],
            'notes'         => ['sometimes', 'string', 'max:1000'],
        ]);

        $animal->update($validated);

        return response()->json(['data' => $animal, 'message' => 'Animal record updated.']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $animal = Animal::where('user_id', $request->user()->id)->findOrFail($id);
        $animal->delete();

        return response()->json(['message' => 'Animal record deleted.']);
    }
}
