<?php

/*
| Labelled photo contributions (Phase 12.2, admin page /admin/training-images).
| See docs/data/livestock-image-sources.md for the sources and the consent rules.
*/
return [
    // Render's container disk is wiped on every deploy, so photos belong on R2.
    'disk' => env('TRAINING_IMAGES_DISK', env('R2_BUCKET') ? 'r2' : 'local'),

    // Who may upload (partners) and who reviews, approves and exports.
    'uploader_roles' => ['vet', 'extension-officer', 'agronomist', 'admin', 'ceo'],
    'reviewer_roles' => ['admin', 'ceo'],

    // Licences that allow use in a commercial service. NC (non-commercial)
    // licences are deliberately absent. partner-consent = signed consent form.
    'licences' => [
        'partner-consent' => 'Written consent from owner and photographer (field photo)',
        'CC-BY-4.0'       => 'CC BY 4.0 (attribution)',
        'CC-BY-SA-4.0'    => 'CC BY-SA 4.0 (attribution, share-alike)',
        'CC0-1.0'         => 'CC0 / public domain dedication',
    ],

    'label_basis' => [
        'lab_confirmed' => 'Confirmed by a laboratory test',
        'vet_diagnosis' => 'Clinical diagnosis by a vet / agronomist',
        'suspected'     => 'Suspected only (not used for accuracy)',
    ],

    'species' => ['cattle', 'goat', 'sheep', 'poultry', 'camel', 'donkey', 'horse', 'pig', 'rabbit', 'other'],

    // Re-encoding strips EXIF (including GPS) and caps the size.
    'max_side'     => 2048,
    'jpeg_quality' => 88,
    'max_upload_kb' => 15360,
    'max_files'    => 20,
];
