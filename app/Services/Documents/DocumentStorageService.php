<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStorageService
{
    private const DISK = 'documents';
    private const STORAGE_PATHS = [
        'TRANSCRIPT' => 'transcripts',
        'CERTIFICATE' => 'certificates',
        'ATTESTATION' => 'attestations',
        'ID_CARD' => 'id_cards',
        'DIPLOMA' => 'diplomas',
    ];
    private const BASE_PATH = 'student_documents';

    private string $disk;
    private string $basePath;

    public function __construct(?string $disk = null, ?string $basePath = null)
    {
        $this->disk = $disk ?? self::DISK;
        $this->basePath = $basePath ?? self::BASE_PATH;
    }

    public function storeDocument(
        string $content,
        string $documentType,
        string $documentNumber,
        string $studentId,
        string $format = 'pdf'
    ): string
    {
        $filename = $this->generateFilename($documentNumber, $format);
        $path = $this->getStoragePath($documentType, $studentId, $filename);

        Storage::disk(self::DISK)->put($path, $content);

        return $path;
    }

    public function getDocumentContent(string $filePath): string
    {
        if (!$this->documentExists($filePath)) {
            throw new RuntimeException("Le fichier n'existe pas: $filePath");
        }

        return Storage::disk(self::DISK)->get($filePath);
    }

    public function downloadDocument(string $filePath, string $downloadName): StreamedResponse
    {
        if (!$this->documentExists($filePath)) {
            throw new RuntimeException("Le fichier n'existe pas: $filePath");
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return Storage::disk(self::DISK)->download(
            $filePath,
            $this->sanitizeFilename($downloadName . '.' . $extension)
        );
    }

    public function documentExists(string $filePath): bool
    {
        return Storage::disk(self::DISK)->exists($filePath);
    }

    private function generateFilename(string $documentNumber, string $format): string
    {
        $timestamp = now()->format('Ymd_His');
        $safeDocumentNumber = preg_replace('/[^A-Z0-9-]/', '', $documentNumber);

        return $safeDocumentNumber . '_' . $timestamp . '.' . $format;
    }

    private function getStoragePath(string $documentType, string $studentId, string $filename): string
    {
        $typeFolder = self::STORAGE_PATHS[$documentType] ?? 'other';
        $studentFolder = 'students/' . $studentId;

        return "$studentFolder/$typeFolder/$filename";
    }


    /**
     * Préparer les informations de stockage
     */
    /** @return array<string, mixed> */
    public function prepareForStorage(UploadedFile $file, string $studentId, string $documentType): array
    {
        $originalName = $this->sanitizeFileName($file->getClientOriginalName());
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);

        $fileName = "{$documentType}_{$studentId}_{$timestamp}_{$random}.{$extension}";
        $path = $this->buildPath($studentId, $documentType, $fileName);

        return [
            'path' => $path,
            'original_name' => $originalName,
            'file_name' => $fileName,
            'full_path' => Storage::disk($this->disk)->path($path),
            'directory' => dirname($path),
        ];
    }

    /**
     * Stocker un fichier
     */
    public function store(UploadedFile $file, string $path): string
    {
        $directory = dirname($path);

        // Créer le répertoire s'il n'existe pas
        if (!Storage::disk($this->disk)->exists($directory)) {
            Storage::disk($this->disk)->makeDirectory($directory);
        }

        // Stocker le fichier
        $stored = Storage::disk($this->disk)->putFileAs(
            $directory,
            $file,
            basename($path)
        );

        if (!$stored) {
            throw new \RuntimeException('Échec du stockage du fichier');
        }

        return $stored;
    }

    /**
     * Récupérer un fichier
     */
    public function get(string $path): string
    {
        if (!$this->exists($path)) {
            throw new \RuntimeException("Le fichier n'existe pas: {$path}");
        }

        return Storage::disk($this->disk)->get($path);
    }

    /**
     * Vérifier l'existence d'un fichier
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Supprimer un fichier
     */
    public function delete(string $path): bool
    {
        if ($this->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }

        return false;
    }

    /**
     * Obtenir le type MIME
     */
    public function mimeType(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->mimeType($path);
    }

    /**
     * Obtenir la taille
     */
    public function size(string $path): ?int
    {
        if (!$this->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->size($path);
    }

    /**
     * Obtenir l'URL publique (si configuré)
     */
    public function url(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }

        if (config('documents.storage.generate_urls', false)) {
            return Storage::disk($this->disk)->url($path);
        }

        return null;
    }

    /**
     * Nettoyer un nom de fichier
     */
    private function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '_', $fileName);
        $fileName = preg_replace('/\_+/', '_', $fileName);

        return substr($fileName, 0, 255);
    }

    /**
     * Construire le chemin de stockage
     */
    private function buildPath(string $studentId, string $documentType, string $fileName): string
    {
        $typeFolder = strtolower($documentType);

        return implode('/', [
            trim($this->basePath, '/'),
            $studentId,
            $typeFolder,
            $fileName,
        ]);
    }

    /**
     * Nettoyer les fichiers orphelins
     */
    /** @return array<string, mixed> */
    public function cleanupOrphanedFiles(int $olderThanDays = 30): array
    {
        $allFiles = Storage::disk($this->disk)->allFiles($this->basePath);
        $documents = Document::pluck('file_path')->toArray();

        $orphaned = array_diff($allFiles, $documents);
        $deleted = [];

        foreach ($orphaned as $file) {
            $lastModified = Storage::disk($this->disk)->lastModified($file);
            $ageInDays = (time() - $lastModified) / (60 * 60 * 24);

            if ($ageInDays > $olderThanDays) {
                if (Storage::disk($this->disk)->delete($file)) {
                    $deleted[] = $file;
                }
            }
        }

        return [
            'scanned' => count($allFiles),
            'orphaned' => count($orphaned),
            'deleted' => count($deleted),
            'deleted_files' => $deleted,
        ];
    }
}
