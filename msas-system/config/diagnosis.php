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

    // How many possible conditions to list on a "Not certain" result.
    'possible_conditions' => 3,
];
