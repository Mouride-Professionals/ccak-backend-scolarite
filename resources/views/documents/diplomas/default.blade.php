<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diplôme - {{ $student_name ?? '' }}</title>
    @include('documents.partials.styles')
    <style>
        .diploma-container {
            border: 12px solid #1a365d;
            padding: 36px;
            margin: 20px;
            text-align: center;
        }

        .diploma-title {
            font-size: 30px;
            font-weight: 700;
            color: #1a365d;
            margin-bottom: 24px;
        }

        .diploma-student-name {
            font-size: 26px;
            font-weight: 700;
            margin: 24px 0;
        }
    </style>
</head>
<body>
<div class="diploma-container">
    <div class="diploma-title">DIPLÔME OFFICIEL</div>

    <p>Le Complexe Educatif Cheikh Ahmadoul Khadim certifie que :</p>
    <div class="diploma-student-name">{{ $student_name ?? 'Nom Étudiant' }}</div>

    <p>Matricule : <strong>{{ $student_number ?? 'N/A' }}</strong></p>
    <p>Programme : <strong>{{ $program ?? 'N/A' }}</strong></p>
    <p>Année académique : <strong>{{ $academic_year ?? 'N/A' }}</strong></p>
    <p>Mention : <strong>{{ $mention ?? 'PASSABLE' }}</strong></p>

    <p style="margin-top: 30px;">
        Le présent diplôme est délivré pour servir et valoir ce que de droit.
    </p>

    <div class="signature-box">
        <p>Fait le {{ $generationDate }}</p>
        <p><strong>Le Recteur</strong></p>
        <p class="stamp">(Signature et cachet)</p>
    </div>
</div>

@include('documents.partials.qrcode')
</body>
</html>
