<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Procès-Verbal de Délibération</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .info {
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .signatures {
            margin-top: 50px;
        }

        .signature-block {
            display: inline-block;
            width: 45%;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">PROCÈS-VERBAL DE DÉLIBÉRATION</div>
        <div>N° {{ $sequential_number }}</div>
    </div>

    <div class="info">
        <p><strong>Programme:</strong> {{ $session->academicProgram->name }}</p>
        <p><strong>Année Académique:</strong> {{ $session->academicYear->name }}</p>
        <p><strong>Semestre:</strong> {{ $session->semester }}</p>
        <p><strong>Session:</strong> {{ $session->session_name }}</p>
        <p><strong>Date:</strong> {{ $session->session_date->format('d/m/Y') }}</p>
        <p><strong>Président du Jury:</strong> {{ $session->president->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Étudiant</th>
                <th>Décision</th>
                <th>Mention</th>
                <th>Observations</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($session->results as $index => $result)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $result->student_id }}</td>
                    <td>{{ $result->decision }}</td>
                    <td>{{ $result->honor_level ?? '-' }}</td>
                    <td>{{ $result->jury_remarks ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-block">
            <p>Le Président du Jury</p>
            <p>{{ $session->president->name }}</p>
            <p>Signature: _________________</p>
        </div>
        <div class="signature-block" style="float: right;">
            <p>Le Secrétaire</p>
            <p>Signature: _________________</p>
        </div>
    </div>

    <p style="margin-top: 80px; font-size: 10px; text-align: center;">
        Généré le {{ $generated_at->format('d/m/Y à H:i') }}
    </p>
</body>

</html>
