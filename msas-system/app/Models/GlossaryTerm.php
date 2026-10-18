<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-language terms managed by admins:
 *  - glossary:      English term -> local term the translator must use;
 *  - pronunciation: written form -> how the voice should say it ("MSAS FarmAI" -> "em es ey es fam ey ay");
 *  - unit:          unit symbol -> spoken word ("kg" -> "kilo"), overriding the defaults;
 *  - protected:     product / active-ingredient names kept exactly as written (lang "*" = all languages).
 */
class GlossaryTerm extends Model
{
    public const KINDS = ['glossary', 'pronunciation', 'unit', 'protected'];

    protected $fillable = ['lang', 'kind', 'english_term', 'local_term', 'notes', 'created_by'];
}
