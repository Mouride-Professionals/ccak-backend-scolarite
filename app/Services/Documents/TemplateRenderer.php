<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class TemplateRenderer
{
    private const TEMPLATE_PATHS = [
        'TRANSCRIPT' => 'documents.transcripts.default',
        'CERTIFICATE' => 'documents.certificates.default',
        'ATTESTATION' => 'documents.attestations.default',
        'ID_CARD' => 'documents.id_cards.default',
        'DIPLOMA' => 'documents.diplomas.default',
    ];

    /** @param array<string, mixed> $metadata */
    public function render(string $documentType, array $metadata = []): string
    {
        $templatePath = $this->getTemplatePath($documentType);

        if (!View::exists($templatePath)) {
            throw new \RuntimeException(
                "Template non trouvé pour le type de document: {$documentType}. " .
                "Chemin recherché: {$templatePath}"
            );
        }

        $data = $this->prepareTemplateData($documentType, $metadata);

        return View::make($templatePath, $data)->render();
    }

    /** @param array<string, mixed> $metadata */
    public function renderWithCustomTemplate(string $templatePath, array $metadata = []): string
    {
        if (!View::exists($templatePath)) {
            throw new \RuntimeException(
                "Template personnalisé non trouvé: {$templatePath}"
            );
        }

        $data = $this->prepareTemplateData('CUSTOM', $metadata);

        return View::make($templatePath, $data)->render();
    }

    /** @return array<string, array<string, mixed>> */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        foreach (self::TEMPLATE_PATHS as $type => $path) {
            $templates[$type] = [
                'path' => $path,
                'exists' => View::exists($path),
            ];
        }

        return $templates;
    }

    public function validateTemplate(string $templatePath): bool
    {
        return View::exists($templatePath);
    }

    /** @return array<int, string> */
    public function getTemplateVariables(string $templatePath): array
    {
        if (!View::exists($templatePath)) {
            return [];
        }

        $content = View::file(View::getFinder()->find($templatePath))->render();

        return $this->extractVariablesFromBlade($content);
    }

    public function getTemplatePath(string $documentType): string
    {
        return self::TEMPLATE_PATHS[$documentType] ?? 'documents.default';
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function prepareTemplateData(string $documentType, array $metadata): array
    {
        $baseData = [
            'metadata' => $metadata,
            'documentType' => $documentType,
            'generationDate' => now()->format('d/m/Y'),
            'generationTimestamp' => now()->toIso8601String(),
            'currentYear' => now()->year,
        ];

        return array_merge($baseData, $metadata);
    }

    /** @return array<int, string> */
    private function extractVariablesFromBlade(string $bladeContent): array
    {
        $variables = [];

        // Trouver les variables Blade simples {{ $variable }}
        preg_match_all('/\{\{\s*\$(\w+)\s*\}\}/', $bladeContent, $matches);
        if (!empty($matches[1])) {
            $variables = array_merge($variables, $matches[1]);
        }

        // Trouver les variables dans les structures de contrôle
        preg_match_all('/@(?:if|foreach|for|while)\(.*?\$(\w+).*?\)/', $bladeContent, $matches);
        if (!empty($matches[1])) {
            $variables = array_merge($variables, $matches[1]);
        }

        // Trouver les variables dans les appels de fonctions
        preg_match_all('/\{\{\s*.*?\$(\w+).*?\s*\}\}/', $bladeContent, $matches);
        if (!empty($matches[1])) {
            $variables = array_merge($variables, $matches[1]);
        }

        return array_unique($variables);
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    public function compileTemplate(string $documentType, array $metadata): array
    {
        $templatePath = $this->getTemplatePath($documentType);
        $requiredVariables = $this->getTemplateVariables($templatePath);

        $missingVariables = array_diff(
            $requiredVariables,
            array_keys($metadata)
        );

        if (!empty($missingVariables)) {
            throw new \InvalidArgumentException(
                "Variables manquantes dans les métadonnées: " .
                implode(', ', $missingVariables) .
                ". Variables requises par le template: " .
                implode(', ', $requiredVariables)
            );
        }

        $renderedContent = $this->render($documentType, $metadata);

        return [
            'content' => $renderedContent,
            'template_path' => $templatePath,
            'variables_used' => $requiredVariables,
            'document_type' => $documentType,
            'compiled_at' => now()->toIso8601String(),
        ];
    }
}
