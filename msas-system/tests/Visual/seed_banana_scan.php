<?php

/**
 * Seeds the banana scan (shared-fixtures/banana-consensus-diagnosis.json) for
 * the result-screen screenshot job (.github/workflows/result-screen-screenshots.yml).
 * Runs against main too (the "before" screenshots), so columns that main does
 * not have yet are left out. Never run against a real database.
 *
 *   php tests/Visual/seed_banana_scan.php <fixture.json> <inconclusive|normalAgeing>
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

if (config('database.default') !== 'sqlite') {
    fwrite(STDERR, "Refusing to seed: not the CI sqlite database.\n");
    exit(1);
}

[$fixturePath, $which] = [$argv[1], $argv[2] ?? 'inconclusive'];
$record = json_decode(file_get_contents($fixturePath), true)[$which]['record'];

$user = User::firstOrCreate(['email' => 'umar.demo@example.test'], [
    'first_name' => 'Umar', 'last_name' => 'Demo', 'phone' => '08000000001', 'role' => 'farmer',
    'password' => Hash::make('screenshot-only'), 'is_verified' => true, 'is_active' => true, 'email_verified_at' => now(),
]);

$row = array_filter($record, fn ($col) => Schema::hasColumn('diagnoses', $col), ARRAY_FILTER_USE_KEY);
foreach (['image_issues', 'photo_tips', 'alternative_findings', 'ai_consensus'] as $json) {
    if (isset($row[$json]) && is_array($row[$json])) {
        $row[$json] = json_encode($row[$json]);
    }
}
$row += ['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(), 'scan_ref' => 'MSAS-SCN-20261019-000123'];
// Raw insert: the same stored row on both branches, whatever the model does on write.
Illuminate\Support\Facades\DB::table('diagnoses')->insert($row);
echo "seeded {$which} for {$user->email}\n";
