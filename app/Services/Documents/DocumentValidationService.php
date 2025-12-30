<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DocumentValidationService
{
    /**
     * Valider les données d'upload
     */
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validateUploadData(array $data): array
    {
        $rules = [
            'student_id' => ['required', 'uuid'],
            'type' => ['required', 'string', 'in:' . implode(',', DocumentType::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        $validator = Validator::make($data, $rules, $this->getValidationMessages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Valider un fichier
     */
    public function validateFile(UploadedFile $file, string $documentType): void
    {
        $type = DocumentType::tryFrom($documentType);

        if (!$type) {
            throw new \InvalidArgumentException("Type de document invalide: {$documentType}");
        }

        $rules = [
            'document_file' => [
                'required',
                'file',
                'mimes:' . implode(',', $type->allowedExtensions()),
                'max:' . $type->maxSizeInKB(),
            ]
        ];

        // Règles supplémentaires pour les images
        if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
            $rules['document_file'][] = 'image';

            // Dimensions minimales pour les photos d'identité
            if ($type === DocumentType::PHOTO) {
                $rules['document_file'][] = 'dimensions:min_width=300,min_height=300';
            }
        }

        $validator = Validator::make(['document_file' => $file], $rules, $this->getValidationMessages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validation de contenu pour les PDF
        if ($file->getClientOriginalExtension() === 'pdf') {
            $this->validatePdfContent($file);
        }
    }

    /**
     * Vérifier les limites d'upload
     */
    public function checkUploadLimits(string $studentId, string $documentType): void
    {
        $maxPerType = config('documents.max_per_type', 3);
        $maxTotal = config('documents.max_total', 20);

        // Vérifier limite par type
        $typeCount = Document::where('student_id', $studentId)
            ->where('type', $documentType)
            ->count();

        if ($typeCount >= $maxPerType) {
            throw new \InvalidArgumentException(
                "Limite atteinte pour ce type de document. Maximum: {$maxPerType}"
            );
        }

        // Vérifier limite totale
        $totalCount = Document::where('student_id', $studentId)->count();

        if ($totalCount >= $maxTotal) {
            throw new \InvalidArgumentException(
                "Limite totale de documents atteinte. Maximum: {$maxTotal}"
            );
        }
    }

    /**
     * Valider une action de review
     */
    public function validateReview(Document $document, string $reviewedBy): void
    {
        if (!$document->status->canBeReviewed()) {
            throw new \InvalidArgumentException(
                "Ce document ne peut pas être revu. Statut actuel: {$document->status->label()}"
            );
        }

        // Vérifier que le reviewer n'est pas l'uploader
        // (implémentation dépend de votre système d'authentification)
        if ($this->isSamePerson($document->student_id, $reviewedBy)) {
            throw new \InvalidArgumentException(
                "Vous ne pouvez pas revoir vos propres documents"
            );
        }
    }

    /**
     * Valider le contenu d'un PDF
     */
    private function validatePdfContent(UploadedFile $file): void
    {
        $content = file_get_contents($file->getPathname());

        // Vérifier la taille (pages max)
        $maxPages = config('documents.pdf.max_pages', 50);

        // Note: Pour compter les pages, vous aurez besoin d'une bibliothèque PDF
        // $pageCount = $this->countPdfPages($file);
        // if ($pageCount > $maxPages) {
        //     throw new \InvalidArgumentException("Le PDF ne doit pas dépasser {$maxPages} pages");
        // }
    }

    /**
     * Vérifier si deux identifiants représentent la même personne
     */
    private function isSamePerson(string $personA, string $personB): bool
    {
        // Implémentation dépendante de votre système
        // Pour l'instant, simple comparaison
        return $personA === $personB;
    }

    /**
     * Messages de validation
     */
    /** @return array<string, string> */
    private function getValidationMessages(): array
    {
        return [
            'student_id.required' => 'L\'identifiant de l\'étudiant est requis',
            'student_id.uuid' => 'L\'identifiant de l\'étudiant doit être un UUID valide',
            'type.required' => 'Le type de document est requis',
            'type.in' => 'Le type de document n\'est pas valide',
            'document_file.required' => 'Un fichier est requis',
            'document_file.file' => 'Le fichier n\'est pas valide',
            'document_file.mimes' => 'Type de fichier non autorisé',
            'document_file.max' => 'Le fichier est trop volumineux',
            'document_file.image' => 'Le fichier doit être une image',
            'document_file.dimensions' => 'Les dimensions de l\'image ne sont pas valides',
        ];
    }
}
