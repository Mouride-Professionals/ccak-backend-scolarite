<?php

namespace App\Providers;

use App\Enums\DocumentType;
use App\Contracts\Services\DocumentGenerationServiceInterface;
use App\Services\PdfGenerationService;
use App\Services\Templates\DiplomaTemplate;
use App\Services\Templates\TemplateManager;
use App\Services\Templates\TranscriptTemplate;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateManager::class, function ($app) {
            $manager = new TemplateManager();

            // Register templates
            $manager->register(DocumentType::BAC_DIPLOMA, new DiplomaTemplate());
            $manager->register(DocumentType::TRANSCRIPT, new TranscriptTemplate());

            return $manager;
        });

        $this->app->singleton(DocumentGenerationServiceInterface::class, PdfGenerationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
