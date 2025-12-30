<?php

namespace App\Enums;

enum DocumentType: string
{
    case ATTESTATION = 'ATTESTATION';
    case CNI = 'CNI';
    case BIRTH_CERT = 'BIRTH_CERT';
    case BAC_DIPLOMA = 'BAC_DIPLOMA';
    case TRANSCRIPT = 'TRANSCRIPT';
    case PHOTO = 'PHOTO';
    case MEDICAL = 'MEDICAL';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::ATTESTATION => 'Attestation',
            self::CNI => 'Carte Nationale d\'Identité',
            self::BIRTH_CERT => 'Extrait d\'Acte de Naissance',
            self::BAC_DIPLOMA => 'Diplôme du Baccalauréat',
            self::TRANSCRIPT => 'Relevé de Notes',
            self::PHOTO => 'Photo d\'Identité',
            self::MEDICAL => 'Certificat Médical',
        };
    }

    public function isRequired(): bool
    {
        $requiredTypes = config('documents.required_types', [
            self::CNI->value,
            self::BIRTH_CERT->value,
            self::BAC_DIPLOMA->value,
            self::PHOTO->value,
        ]);

        return in_array($this->value, $requiredTypes);
    }

    public function allowedExtensions(): array
    {
        return config("documents.allowed_extensions.{$this->value}", ['pdf', 'jpg', 'jpeg', 'png']);
    }

    public function maxSizeInKB(): int
    {
        return config("documents.max_sizes.{$this->value}", 10240); // 10MB par défaut
    }
}
