<?php

return [

    /*
    | Below this AI confidence (percent), a result is never presented as a
    | single diagnosis: web, PDF and app show "Not certain", the possible
    | conditions, and ask the farmer to confirm with a vet or extension
    | officer. Applied when a result is displayed, so changing it also
    | applies to older scans.
    */
    'min_confidence' => (float) env('DIAGNOSIS_MIN_CONFIDENCE', 65),

    /*
    | Buffer zone: from min_confidence up to (not including) this value, an
    | assessed result is shown as "Likely X" with "please confirm with a vet
    | or extension officer", not as a plain diagnosis. The AI's confidence
    | moved 65-78% between runs of one photo, so a hard single line made the
    | same photo flip between a diagnosis and "Not certain".
    */
    'confirm_below' => (float) env('DIAGNOSIS_CONFIRM_BELOW', 75),

    // How many possible conditions to list on a "Not certain" result.
    'possible_conditions' => 3,
];
