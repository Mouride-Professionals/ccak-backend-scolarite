<?php

return [
    'disk_name' => env(
        'MEDIA_DISK',
        env('APP_ENV') === 'testing' ? 'documents' : env('FILESYSTEM_DISK', 'local')
    ),
    'max_file_size' => 1024 * 1024 * 10,
    'path_generator' => App\Services\Media\DocumentPathGenerator::class,
];
