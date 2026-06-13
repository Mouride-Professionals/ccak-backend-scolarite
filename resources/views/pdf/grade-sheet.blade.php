<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }

        .page { padding: 18px 22px; }

        /* University block */
        .university-header { text-align: center; border-bottom: 2px solid #00365F; padding-bottom: 8px; margin-bottom: 12px; }
        .university-name { font-size: 13px; font-weight: bold; color: #00365F; letter-spacing: 0.5px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #00365F; margin: 4px 0 2px; text-transform: uppercase; }

        /* Meta block */
        .meta-block { background: #F0F6FC; border: 1px solid #cde; border-radius: 3px; padding: 8px 12px; margin-bottom: 12px; }
        .meta-row { margin-bottom: 3px; }
        .meta-label { font-weight: bold; color: #444; }
        .meta-row-inline { display: inline-block; margin-right: 24px; }

        /* Grade table */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #00365F; color: #fff; }
        thead th { padding: 5px 6px; font-size: 9px; text-align: center; border: 1px solid #00365F; }
        tbody tr:nth-child(even) { background: #F7FAFD; }
        tbody td { padding: 4px 6px; border: 1px solid #D8E4F0; font-size: 9px; }
        td.center { text-align: center; }
        td.name { max-width: 160px; }

        /* Totals / stats */
        .stats { margin-top: 8px; font-size: 9px; color: #555; }

        /* Signature block */
        .signature-block { margin-top: 24px; }
        .signature-row { display: table; width: 100%; }
        .signature-cell { display: table-cell; width: 50%; }
        .sig-line { border-top: 1px solid #333; margin-top: 32px; width: 60%; }
        .sig-label { font-size: 9px; color: #555; margin-top: 3px; }
    </style>
</head>
<body>
<div class="page">

    {{-- University block --}}
    <div class="university-header">
        <div class="university-name">UNIVERSITÉ CHEIKH AHMADOU KABS (UCAK)</div>
        <div class="doc-title">Fiche de Notes</div>
    </div>

    {{-- Meta info --}}
    <div class="meta-block">
        <div class="meta-row">
            <span class="meta-label">Matière :</span>
            {{ $ctx['course_code'] ?? '' }} — {{ $ctx['course_name'] ?? '' }}
        </div>
        <div class="meta-row">
            <span class="meta-label">Session / Évaluation :</span>
            {{ $ctx['session_label'] ?? '' }}
        </div>
        <div class="meta-row">
            <span class="meta-label">Année académique :</span>
            {{ $ctx['academic_year_name'] ?? '' }}
            @if(!empty($ctx['semester_number']))
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span class="meta-label">Semestre :</span> S{{ $ctx['semester_number'] }}
            @endif
        </div>
        @if(!empty($ctx['date']))
        <div class="meta-row">
            <span class="meta-label">Date :</span>
            {{ \Carbon\Carbon::parse($ctx['date'])->format('d/m/Y') }}
        </div>
        @endif
        @if($ctx['use_exam_number'])
        <div class="meta-row" style="color:#B45309; font-style:italic;">
            &#x1F512; Mode anonymat activé — les noms ne sont pas affichés.
        </div>
        @endif
    </div>

    {{-- Grade table --}}
    <table>
        <thead>
            <tr>
                <th style="width:30px;">N°</th>
                @if($ctx['use_exam_number'])
                    <th>Code anonymat</th>
                @else
                    <th>Nom complet</th>
                    <th style="width:80px;">N° Carte</th>
                @endif
                <th style="width:50px;">Note</th>
                <th style="width:40px;">/Max</th>
                <th style="width:60px;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $s)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                @if($ctx['use_exam_number'])
                    <td>{{ $s['exam_number'] ?? '—' }}</td>
                @else
                    <td class="name">{{ $s['full_name'] ?? '—' }}</td>
                    <td class="center">{{ $s['student_number'] ?? '—' }}</td>
                @endif
                <td class="center">{{ isset($s['score']) ? number_format($s['score'], 2) : '' }}</td>
                <td class="center">{{ $s['max_score'] ?? 20 }}</td>
                <td class="center">{{ $s['status'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="stats">
        Total étudiants : {{ count($students) }} &nbsp;|&nbsp;
        Notes saisies : {{ collect($students)->filter(fn($s) => isset($s['score']))->count() }}
    </div>

    {{-- Signature block --}}
    <div class="signature-block">
        <div class="signature-row">
            <div class="signature-cell">
                <div class="sig-line"></div>
                <div class="sig-label">L'enseignant responsable</div>
            </div>
            <div class="signature-cell">
                <div class="sig-line"></div>
                <div class="sig-label">Le Chef de Département</div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
