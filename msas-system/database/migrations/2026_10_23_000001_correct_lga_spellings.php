<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eleven LGA names in App\Data\NigeriaLocations were misspelt (Fufure for
 * Fufore, Grie for Girei, ...). The dropdowns now use the official
 * spellings, which are also the boundary names LgaLookup returns, so rows
 * saved with the old spelling are renamed here. Otherwise a farmer
 * registered in "Fufure" would drop out of every State -> LGA filter.
 * Matched on state as well as LGA, since an LGA name is unique only within
 * its state.
 */
return new class extends Migration
{
    private const RENAMES = [
        ['Adamawa', 'Fufure', 'Fufore'],
        ['Adamawa', 'Gayuk', 'Guyuk'],
        ['Adamawa', 'Grie', 'Girei'],
        ['Bauchi', 'Damban', 'Dambam'],
        ['Cross River', 'Yakuur', 'Yakurr'],
        ['Imo', 'Unuimo', 'Onuimo'],
        ['Kebbi', 'Arewa', 'Arewa Dandi'],
        ['Niger', 'Moya', 'Munya'],
        ['Osun', 'Atakumosa East', 'Atakunmosa East'],
        ['Osun', 'Atakumosa West', 'Atakunmosa West'],
        ['Taraba', 'Kumi', 'Kurmi'],
    ];

    private const TABLES = ['users', 'collection_locations'];

    public function up(): void
    {
        $this->rename(fn (array $r) => [$r[0], $r[1], $r[2]]);
    }

    public function down(): void
    {
        $this->rename(fn (array $r) => [$r[0], $r[2], $r[1]]);
    }

    private function rename(callable $direction): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'lga') || !Schema::hasColumn($table, 'state')) {
                continue;
            }
            foreach (self::RENAMES as $r) {
                [$state, $from, $to] = $direction($r);
                DB::table($table)->where('state', $state)->where('lga', $from)->update(['lga' => $to]);
            }
        }
    }
};
