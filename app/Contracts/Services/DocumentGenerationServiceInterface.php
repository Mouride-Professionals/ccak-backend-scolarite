<?php

namespace App\Contracts\Services;

use App\Enums\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\User;

interface DocumentGenerationServiceInterface
{
    /**
     * Generate a new document
     */
    /** @param array<string, mixed> $templateData */
    public function generate(
        User $student,
        DocumentType $type,
        User $generatedBy,
        array $templateData,
        ?bool $withWatermark = false,
        ?bool $withQrCode = false
    ): GeneratedDocument;

    /**
     * Get document by number
     */
    public function find(string $documentNumber): ?GeneratedDocument;

    /**
     * Verify document authenticity
     */
    /** @param array<string, mixed> $verificationData */
    public function verify(string $documentNumber, array $verificationData = []): bool;

    /**
     * Generate secure document number
     */
    public function generateDocumentNumber(DocumentType $type): string;

    /**
     * Get storage path for document
     */
    public function getStoragePath(DocumentType $type, string $documentNumber): string;
}
