<?php

namespace App\Services;

use App\Contracts\Services\DocumentGenerationServiceInterface;
use App\Contracts\Templates\DocumentTemplateInterface;
use App\Enums\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Services\Templates\TemplateManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PdfGenerationService implements DocumentGenerationServiceInterface
{
    private const DOCUMENT_PREFIX = 'UCAK';
    private const QR_CODE_SIZE = 150;
    private const WATERMARK_OPACITY = 0.1;

    public function __construct(
        private TemplateManager $templateManager,
        private string $storageDisk = 'documents'
    ) {}

    /** @param array<string, mixed> $templateData */
    public function generate(
        User $student,
        DocumentType $type,
        User $generatedBy,
        array $templateData,
        ?bool $withWatermark = false,
        ?bool $withQrCode = false
    ): GeneratedDocument {
        // Get template for document type
        $template = $this->templateManager->get($type);

        // Validate template data
        if (!$template->validateData($templateData)) {
            throw new \InvalidArgumentException('Invalid template data provided');
        }

        // Generate secure document number
        $documentNumber = $this->generateDocumentNumber($type);

        // Prepare data for template
        $processedData = $template->processData(array_merge($templateData, [
            'student' => $student,
            'document_number' => $documentNumber,
            'generated_by' => $generatedBy,
            'generated_at' => now(),
            'with_qr_code' => $withQrCode,
            'with_watermark' => $withWatermark,
        ]));

        // Add UCAK branding data
        $processedData['branding'] = $this->getBrandingData();

        // Generate QR code if requested
        if ($withQrCode) {
            $processedData['qr_code'] = $this->generateQrCode($documentNumber, $student);
        }

        // Generate PDF
        $pdf = $this->generatePdf($template, $processedData, $withWatermark);

        // Store PDF file
        // Create document record
        $document = GeneratedDocument::create([
            'student_id' => $student->id,
            'type' => $type,
            'document_number' => $documentNumber,
            'file_path' => '',
            'generated_by' => $generatedBy->id,
            'metadata' => $this->prepareMetadata($processedData, $withWatermark, $withQrCode),
            'status' => GeneratedDocument::STATUS_DRAFT,
            'generated_at' => now(),
        ]);

        $media = $this->storeGeneratedPdf($document, $pdf->output(), $documentNumber);
        $document->update([
            'media_id' => $media->id,
            'file_path' => $media->getPathRelativeToRoot(),
        ]);

        return $document;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function generatePdf(
        DocumentTemplateInterface $template,
        array $data,
        bool $withWatermark
    ): \Barryvdh\DomPDF\PDF {
        $pdf = Pdf::loadView($template->getView(), $data);

        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isPhpEnabled', true);

        // Add watermark if requested
        if ($withWatermark) {
            $this->addWatermark($pdf);
        }

        return $pdf;
    }

    private function addWatermark(\Barryvdh\DomPDF\PDF $pdf): void
    {
        $watermarkSvg = view('pdf.watermark', [
            'text' => 'UCAK OFFICIAL',
            'opacity' => self::WATERMARK_OPACITY,
        ])->render();

        $pdf->setOption('header-html', $watermarkSvg);
        $pdf->setOption('footer-html', $watermarkSvg);
    }

    private function generateQrCode(string $documentNumber, User $student): string
    {
        $verificationUrl = route('documents.verify', [
            'number' => $documentNumber,
            'student_id' => $student->id,
        ]);

        return 'data:image/svg+xml;base64,' . base64_encode(
                QrCode::size(self::QR_CODE_SIZE)
                    ->format('svg')
                    ->generate($verificationUrl)
            );
    }

    private function storeGeneratedPdf(
        GeneratedDocument $document,
        string $pdfContent,
        string $documentNumber
    ): Media {
        $tmpPath = tempnam(sys_get_temp_dir(), 'ucak_pdf_');
        if ($tmpPath === false) {
            throw new \RuntimeException('Impossible de créer un fichier temporaire');
        }

        $tmpFile = $tmpPath . '.pdf';
        rename($tmpPath, $tmpFile);
        file_put_contents($tmpFile, $pdfContent);

        try {
            return $document->addMedia($tmpFile)
                ->usingFileName($documentNumber . '.pdf')
                ->usingName($documentNumber)
                ->toMediaCollection('official_documents');
        } finally {
            @unlink($tmpFile);
        }
    }

    public function getStoragePath(DocumentType $type, string $documentNumber): string
    {
        $date = now()->format('Y/m/d');
        $filename = "{$documentNumber}.pdf";

        return "{$type->value}/{$date}/{$filename}";
    }

    public function generateDocumentNumber(DocumentType $type): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(6);
        $typeCode = strtoupper(substr($type->value, 0, 3));

        return self::DOCUMENT_PREFIX . "-{$typeCode}-{$timestamp}-{$random}";
    }

    private function getBrandingData(): array
    {
        return [
            'name' => 'UCAK University',
            'logo' => storage_path('app/branding/logo.png'),
            'address' => '123 University Ave, City, Country',
            'website' => 'https://ucak.edu.tr',
            'phone' => '+90 212 123 4567',
            'email' => 'info@ucak.edu.tr',
            'colors' => [
                'primary' => '#1a365d',
                'secondary' => '#2d3748',
                'accent' => '#3182ce',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareMetadata(
        array $data,
        bool $withWatermark,
        bool $withQrCode
    ): array {
        return [
            'generation_data' => [
                'with_watermark' => $withWatermark,
                'with_qr_code' => $withQrCode,
                'template' => $data['template_name'] ?? null,
                'version' => '1.0',
            ],
            'security' => [
                'hash' => hash('sha256', json_encode($data)),
                'timestamp' => now()->toISOString(),
            ],
            'data_snapshot' => [
                'student_email' => $data['student']->email ?? null,
                'document_type' => $data['document_type'] ?? null,
            ],
        ];
    }

    public function find(string $documentNumber): ?GeneratedDocument
    {
        return GeneratedDocument::where('document_number', $documentNumber)->first();
    }

    /** @param array<string, mixed> $verificationData */
    public function verify(string $documentNumber, array $verificationData = []): bool
    {
        $document = $this->find($documentNumber);

        if (!$document) {
            return false;
        }

        // Check if document is valid
        if ($document->status !== GeneratedDocument::STATUS_ISSUED) {
            return false;
        }

        // Check if student ID matches
        if (isset($verificationData['student_id'])) {
            return $document->student_id === $verificationData['student_id'];
        }

        // Additional verification logic can be added here

        return true;
    }

    /**
     * Issue a generated document
     */
    public function issue(string $documentNumber): GeneratedDocument
    {
        $document = $this->find($documentNumber);

        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        if ($document->status !== GeneratedDocument::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Document cannot be issued');
        }

        $document->update([
            'status' => GeneratedDocument::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        return $document->fresh();
    }

    /**
     * Get document download URL
     */
    public function getDownloadUrl(GeneratedDocument $document): string
    {
        if ($document->media_id) {
            $media = $document->media()->whereKey($document->media_id)->first();
            if ($media instanceof Media) {
                try {
                    return $media->getTemporaryUrl(now()->addMinutes(30));
                } catch (\Throwable) {
                    return '';
                }
            }
        }

        return Storage::disk($this->storageDisk)->exists($document->file_path) ? url($document->file_path) : '';
    }

    /**
     * Get document as base64
     */
    public function getAsBase64(GeneratedDocument $document): string
    {
        if ($document->media_id) {
            $media = $document->media()->whereKey($document->media_id)->first();
            if ($media instanceof Media) {
                $content = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
                return base64_encode($content);
            }
        }

        $content = Storage::disk($this->storageDisk)->get($document->file_path);

        return base64_encode($content);
    }
}
