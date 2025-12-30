<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Types de documents requis
    |--------------------------------------------------------------------------
    |
    | Liste des types de documents obligatoires pour un étudiant
    |
    */
    'required_types' => [
        'CNI',
        'BIRTH_CERT',
        'BAC_DIPLOMA',
        'PHOTO',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limitations d'upload
    |--------------------------------------------------------------------------
    */
    'max_per_type' => 3, // Maximum de documents par type par étudiant
    'max_total' => 20,   // Maximum total de documents par étudiant

    /*
    |--------------------------------------------------------------------------
    | Extensions autorisées par type
    |--------------------------------------------------------------------------
    */
    'allowed_extensions' => [
        'ATTESTATION' => ['pdf'],
        'CNI' => ['pdf', 'jpg', 'jpeg', 'png'],
        'BIRTH_CERT' => ['pdf', 'jpg', 'jpeg'],
        'BAC_DIPLOMA' => ['pdf'],
        'TRANSCRIPT' => ['pdf'],
        'PHOTO' => ['jpg', 'jpeg', 'png'],
        'MEDICAL' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tailles maximales (en kilo-octets)
    |--------------------------------------------------------------------------
    */
    'max_sizes' => [
        'ATTESTATION' => 5120, // 5MB
        'CNI' => 2048,      // 2MB
        'BIRTH_CERT' => 2048, // 2MB
        'BAC_DIPLOMA' => 5120, // 5MB
        'TRANSCRIPT' => 5120,  // 5MB
        'PHOTO' => 1024,      // 1MB
        'MEDICAL' => 2048,    // 2MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration du stockage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('DOCUMENT_STORAGE_DISK', 'documents'),
        'base_path' => 'documents',
        'generate_urls' => false,
        'visibility' => 'private',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration PDF
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'max_pages' => 50,
        'allowed_resolutions' => [72, 150, 300],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des images
    |--------------------------------------------------------------------------
    */
    'images' => [
        'min_width' => 300,
        'min_height' => 300,
        'max_width' => 4000,
        'max_height' => 4000,
        'allowed_aspect_ratios' => ['3:4', '4:3', '1:1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'on_upload' => true,
        'on_approval' => true,
        'on_rejection' => true,
        'channels' => ['database', 'email'], // database, email, sms, slack, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration du nettoyage
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'enabled' => true,
        'orphaned_files_older_than_days' => 30,
        'schedule' => 'daily', // hourly, daily, weekly, monthly
    ],
];
