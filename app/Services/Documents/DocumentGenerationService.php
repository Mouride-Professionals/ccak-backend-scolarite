<?php

namespace App\Services\Documents;

use App\Models\GeneratedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentGenerationService
{
    private DocumentNumberGenerator $numberGenerator;
    private TemplateRenderer $templateRenderer;
    private DocumentStorageService $fileStorage;

    public function __construct(
        DocumentNumberGenerator $numberGenerator,
        TemplateRenderer        $templateRenderer,
        DocumentStorageService  $fileStorage
    )
    {
        $this->numberGenerator = $numberGenerator;
        $this->templateRenderer = $templateRenderer;
        $this->fileStorage = $fileStorage;
    }

    /** @param array<string, mixed> $validatedData */
    public function generateDocument(array $validatedData, string $userId): GeneratedDocument
    {
        $documentType = $validatedData['type'];
        $studentId = $validatedData['student_id'];
        $metadata = $validatedData['metadata'] ?? [];

        // Générer le numéro de document
        $documentNumber = $validatedData['document_number']
            ?? $this->numberGenerator->generate($documentType);

        // Valider que le template existe
        $templatePath = $this->templateRenderer->getTemplatePath($documentType);

        if (!$this->templateRenderer->validateTemplate($templatePath)) {
            throw new \RuntimeException(
                "Template non disponible pour le type de document: {$documentType}"
            );
        }

        // Préparer les données avec QR code
        $templateData = $this->prepareTemplateData(
            $documentType,
            $metadata,
            $studentId,
            $documentNumber,
            $userId
        );

        // Générer le QR code
        $qrCodeData = $this->generateQrCodeData($documentNumber, $studentId, $documentType);
        $templateData['qr_code'] = $this->generateQrCodeSvg($qrCodeData);
        $templateData['verification_url'] = $this->getVerificationUrl($documentNumber);

        // Rendre le template HTML
        $htmlContent = $this->templateRenderer->render($documentType, $templateData);

        // Générer le PDF
        $pdfContent = $this->generatePdf($htmlContent);

        // Sauvegarder le PDF
        $filePath = $this->fileStorage->storeDocument(
            $pdfContent,
            $documentType,
            $documentNumber,
            $studentId,
            'pdf'
        );

        // Créer l'enregistrement
        $document = GeneratedDocument::create([
            'student_id' => $studentId,
            'type' => $documentType,
            'document_number' => $documentNumber,
            'file_path' => $filePath,
            'generated_by' => $userId,
            'metadata' => array_merge($metadata, [
                'qr_code_data' => $qrCodeData,
                'generated_at_timestamp' => now()->timestamp,
            ]),
            'status' => $validatedData['status'] ?? GeneratedDocument::STATUS_DRAFT,
            'generated_at' => now(),
        ]);

        Log::info('Document PDF généré', [
            'document_id' => $document->id,
            'type' => $documentType,
            'document_number' => $documentNumber,
            'file_size' => strlen($pdfContent),
            'generated_by' => $userId,
        ]);

        return $document;
    }

    private function generatePdf(string $htmlContent): string
    {
        $pdf = Pdf::loadHTML($htmlContent);

        // Configuration DomPDF
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
            'chroot' => [public_path(), storage_path()],
        ]);

        return $pdf->output();
    }

    /** @param array<string, mixed> $qrCodeData */
    private function generateQrCodeSvg(array $qrCodeData): string
    {
        $verificationUrl = $this->getVerificationUrl($qrCodeData['document_number']);

        return QrCode::size(150)
            ->format('svg')
            ->errorCorrection('H')
            ->generate($verificationUrl);
    }

    /** @return array<string, mixed> */
    private function generateQrCodeData(string $documentNumber, string $studentId, string $documentType): array
    {
        return [
            'document_number' => $documentNumber,
            'student_id' => $studentId,
            'document_type' => $documentType,
            'timestamp' => now()->timestamp,
            'hash' => hash('sha256', $documentNumber . $studentId . now()->timestamp . config('app.key')),
        ];
    }

    private function getVerificationUrl(string $documentNumber): string
    {
        return url("/api/documents/verify/{$documentNumber}");
    }

    private function prepareTemplateData(
        string $documentType,
        array  $metadata,
        string $studentId,
        string $documentNumber,
        string $userId
    ): array
    {
        $studentData = $this->getStudentData($studentId);

        return array_merge($studentData, $metadata, [
            'document_number' => $documentNumber,
            'document_type' => $documentType,
            'generation_date' => now()->format('d/m/Y'),
            'generation_timestamp' => now()->toIso8601String(),
            'current_year' => now()->year,
            'generated_by_user_id' => $userId,
        ]);
    }

    /** @return array<string, mixed> */
    private function getStudentData(string $studentId): array
    {
        $year = now()->year;
        //TODO: À adapter
        return [
            'student_name' => 'Nom Étudiant',
            'student_number' => 'ETU' . substr($studentId, 0, 8),
            'program' => 'Programme académique',
            'academic_year' => $year . '-' . ($year + 1),
            'semester' => 'S' . (now()->month <= 6 ? 1 : 2),
        ];
    }

    public function issueDocument(string $documentId, string $userId): GeneratedDocument
    {
        $document = GeneratedDocument::findOrFail($documentId);

        if ($document->status === GeneratedDocument::STATUS_ISSUED) {
            throw new \Exception('Ce document a déjà été émis.');
        }

        $document->update([
            'status' => GeneratedDocument::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        // Log de l'action d'émission
        Log::info("Document $document->document_number émis par l'utilisateur {$userId}");

        return $document;
    }

    public function revokeDocument(string $documentId, string $userId, string $reason): GeneratedDocument
    {
        $document = GeneratedDocument::findOrFail($documentId);

        $document->update([
            'status' => GeneratedDocument::STATUS_REVOKED,
            'metadata' => array_merge(
                $document->metadata ?? [],
                ['revocation_reason' => $reason, 'revoked_by' => $userId, 'revoked_at' => now()->toIso8601String()]
            ),
        ]);

        return $document;
    }

    public function getDocumentContent(GeneratedDocument $document): string
    {
        return $this->fileStorage->getDocumentContent($document->file_path);
    }

    public function downloadDocument(GeneratedDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->fileStorage->downloadDocument($document->file_path, $document->document_number);
    }
}
