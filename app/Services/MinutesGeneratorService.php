<?php

namespace App\Services;

use App\Models\DeliberationSession;

class MinutesGeneratorService
{
    /**
     * Generate minutes for a deliberation session and return the file path.
     */
    public function generate(DeliberationSession $session): string
    {
        $directory = storage_path('app/deliberation_minutes');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = "minutes_{$session->id}.pdf";
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        // TODO: replace with actual PDF generation logic
        file_put_contents($path, "Minutes for deliberation session #{$session->id}");

        return $path;
    }
}
