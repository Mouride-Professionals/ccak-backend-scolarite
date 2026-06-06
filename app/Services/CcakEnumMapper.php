<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\IDType;
use App\Enums\PaymentStatus;
use App\Enums\Provenance;
use App\Enums\RegistrationStatus;
use App\Enums\StudentStatus;
use InvalidArgumentException;

/**
 * Maps CCAK AdminService integer enum IDs to our string-backed PHP enums.
 *
 * CCAK serializes enums as 0-based integers (C# default).
 * Source: AdminService.Domain.Enums
 */
class CcakEnumMapper
{
    public static function registrationStatus(int $id): RegistrationStatus
    {
        return match ($id) {
            0 => RegistrationStatus::DRAFT,
            1 => RegistrationStatus::PENDING_VALIDATION,
            2 => RegistrationStatus::VALIDATED,
            3 => RegistrationStatus::SUSPENDED,
            4 => RegistrationStatus::CANCELLED,
            default => throw new InvalidArgumentException("Unknown CCAK RegistrationStatus ID: {$id}"),
        };
    }

    public static function documentStatus(int $id): DocumentStatus
    {
        return match ($id) {
            0 => DocumentStatus::PENDING,
            1 => DocumentStatus::APPROVED,
            2 => DocumentStatus::REJECTED,
            default => throw new InvalidArgumentException("Unknown CCAK DocumentStatus ID: {$id}"),
        };
    }

    public static function paymentStatus(int $id): PaymentStatus
    {
        return match ($id) {
            0 => PaymentStatus::PENDING,
            1 => PaymentStatus::PARTIALLY_PAID,
            2 => PaymentStatus::FULLY_PAID,
            3 => PaymentStatus::OVERDUE,
            4 => PaymentStatus::CANCELLED,
            default => throw new InvalidArgumentException("Unknown CCAK PaymentStatus ID: {$id}"),
        };
    }

    public static function documentType(int $id): DocumentType
    {
        // CCAK: 0=BacDiploma, 1=NationalId, 2=Photo, 3=BirthCertificate, 4=MedicalCertificate
        // Our names differ — explicit mapping required.
        return match ($id) {
            0 => DocumentType::BAC_DIPLOMA,
            1 => DocumentType::CNI,
            2 => DocumentType::PHOTO,
            3 => DocumentType::BIRTH_CERT,
            4 => DocumentType::MEDICAL,
            default => throw new InvalidArgumentException("Unknown CCAK DocumentType ID: {$id}"),
        };
    }

    public static function idType(int $id): IDType
    {
        return match ($id) {
            0 => IDType::PASSPORT,
            1 => IDType::NATIONAL_ID,
            2 => IDType::DRIVING_LICENSE,
            3 => IDType::OTHER,
            default => throw new InvalidArgumentException("Unknown CCAK IDType ID: {$id}"),
        };
    }

    public static function studentStatus(int $id): StudentStatus
    {
        // CCAK: 0=Pending, 1=Active, 2=Suspended, 3=Graduated, 4=Inactive
        // We have extra cases (WITHDRAWN, CANCELLED) with no CCAK equivalent — explicit mapping required.
        return match ($id) {
            0 => StudentStatus::PENDING,
            1 => StudentStatus::ACTIVE,
            2 => StudentStatus::SUSPENDED,
            3 => StudentStatus::GRADUATED,
            4 => StudentStatus::INACTIVE,
            default => throw new InvalidArgumentException("Unknown CCAK StudentStatus ID: {$id}"),
        };
    }

    public static function provenance(int $id): Provenance
    {
        return match ($id) {
            0 => Provenance::ETAT,
            1 => Provenance::PLATEFORME,
            default => throw new InvalidArgumentException("Unknown CCAK Provenance ID: {$id}"),
        };
    }
}
