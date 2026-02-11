<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte Étudiant - {{ $student_name ?? '' }}</title>
    @include('documents.partials.styles')
    <style>
        .id-card {
            width: 520px;
            border: 2px solid #2d3748;
            border-radius: 12px;
            padding: 20px;
            margin: 10px auto;
            background: #f8fafc;
        }

        .id-card-header {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
        }

        .id-card-grid {
            display: table;
            width: 100%;
        }

        .id-card-cell {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
    </style>
</head>
<body>
<div class="id-card">
    <div class="id-card-header">CARTE D'IDENTITÉ ÉTUDIANTE</div>
    <div class="id-card-grid">
        <div class="id-card-cell">
            <p><strong>Nom:</strong> {{ $student_name ?? 'N/A' }}</p>
            <p><strong>Matricule:</strong> {{ $student_number ?? 'N/A' }}</p>
            <p><strong>Programme:</strong> {{ $program ?? 'N/A' }}</p>
            <p><strong>Année:</strong> {{ $academic_year ?? 'N/A' }}</p>
            <p><strong>Statut:</strong> {{ $status ?? 'ACTIVE' }}</p>
        </div>
        <div class="id-card-cell">
            @if(!empty($photo_url))
                <img src="{{ $photo_url }}" alt="Photo étudiant" style="max-width: 140px; max-height: 170px; border: 1px solid #cbd5e0;">
            @else
                <div style="width: 140px; height: 170px; border: 1px solid #cbd5e0; text-align: center; line-height: 170px; color: #a0aec0;">
                    PHOTO
                </div>
            @endif
        </div>
    </div>

    @include('documents.partials.qrcode')
</div>
</body>
</html>
