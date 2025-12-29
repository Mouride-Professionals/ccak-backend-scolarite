<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $diploma_title ?? 'Diplôme Universitaire' }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            background: #fff;
            color: #000;
        }
        
        .diploma-container {
            width: 29.7cm;
            height: 21cm;
            position: relative;
            background: #fff;
            border: 20px double #1a365d;
            padding: 2.5cm;
            box-sizing: border-box;
        }
        
        .background-design {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400"><path d="M200,50 Q300,100 350,200 T200,350 Q100,300 50,200 T200,50" fill="none" stroke="%231a365d" stroke-width="0.5" opacity="0.05"/></svg>');
            background-repeat: repeat;
            opacity: 0.3;
            z-index: 1;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 1.5cm;
            border-bottom: 3px solid #b8860b;
            padding-bottom: 0.5cm;
        }
        
        .university-name {
            font-size: 28pt;
            font-weight: bold;
            color: #1a365d;
            letter-spacing: 1px;
            margin: 0;
        }
        
        .university-motto {
            font-size: 12pt;
            font-style: italic;
            color: #666;
            margin-top: 5px;
            letter-spacing: 0.5px;
        }
        
        .diploma-title {
            text-align: center;
            margin: 1cm 0;
        }
        
        .title-main {
            font-size: 22pt;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
        }
        
        .title-sub {
            font-size: 14pt;
            color: #666;
            margin-top: 10px;
            font-style: italic;
        }
        
        .student-info-section {
            text-align: center;
            margin: 1.5cm 0;
            padding: 0.5cm;
            border-top: 2px solid #b8860b;
            border-bottom: 2px solid #b8860b;
        }
        
        .student-name {
            font-size: 24pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        
        .student-details {
            font-size: 11pt;
            color: #666;
            margin-top: 10px;
            line-height: 1.4;
        }
        
        .academic-info-section {
            text-align: center;
            margin: 1cm 0;
            padding: 0.5cm;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .program-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1a365d;
            margin: 0;
        }
        
        .program-details {
            font-size: 12pt;
            color: #444;
            margin-top: 10px;
            line-height: 1.6;
        }
        
        .degree-info {
            font-size: 14pt;
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .honors-section {
            text-align: center;
            margin: 1cm 0;
            padding: 0.5cm;
        }
        
        .honors-badge {
            display: inline-block;
            background: linear-gradient(45deg, #b8860b, #daa520);
            color: #fff;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .gpa-info {
            font-size: 12pt;
            color: #444;
            text-align: center;
            margin: 10px 0;
            font-style: italic;
        }
        
        .date-section {
            text-align: center;
            margin: 1cm 0;
            font-size: 13pt;
        }
        
        .date-text {
            font-style: italic;
            color: #444;
        }
        
        .signatures-section {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 1cm;
            border-top: 1px solid #ccc;
        }
        
        .signature-block {
            text-align: center;
            width: 23%;
        }
        
        .signature-image {
            height: 50px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 80%;
            margin: 5px auto;
        }
        
        .signatory-name {
            font-weight: bold;
            font-size: 11pt;
            margin: 5px 0;
        }
        
        .signatory-title {
            font-size: 9pt;
            color: #666;
            font-style: italic;
        }
        
        .university-seal {
            text-align: center;
            width: 23%;
        }
        
        .seal-image {
            width: 80px;
            height: 80px;
            opacity: 0.8;
        }
        
        .seal-text {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
        }
        
        .footer-section {
            position: absolute;
            bottom: 20px;
            right: 20px;
            text-align: right;
            font-size: 9pt;
            color: #666;
        }
        
        .document-number {
            font-family: monospace;
            letter-spacing: 1px;
        }
        
        .registration-number {
            font-size: 10pt;
            color: #444;
            margin-top: 5px;
        }
        
        .validity-info {
            font-size: 8pt;
            color: #888;
            margin-top: 10px;
            border-top: 1px dashed #ddd;
            padding-top: 5px;
        }
        
        .official-stamp {
            position: absolute;
            top: 50px;
            right: 50px;
            transform: rotate(15deg);
            opacity: 0.7;
        }
        
        .stamp-image {
            width: 120px;
            height: 120px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60pt;
            color: rgba(26, 54, 93, 0.05);
            font-weight: bold;
            white-space: nowrap;
            pointer-events: none;
            z-index: 1;
        }
        
        .deliberation-info {
            font-size: 10pt;
            color: #555;
            text-align: center;
            margin: 10px 0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="diploma-container">
        @if($with_watermark ?? false)
        <div class="watermark">UNIVERSITÉ UCAK</div>
        @endif
        
        <div class="background-design"></div>
        
        @if($with_qr_code ?? false)
        <div class="official-stamp">
            @if(isset($qr_code))
            <img src="{{ $qr_code }}" alt="QR Code de vérification" class="stamp-image">
            @endif
        </div>
        @endif
        
        <div class="content-wrapper">
            <!-- Header -->
            <div class="header-section">
                <h1 class="university-name">UNIVERSITÉ UCAK</h1>
                <p class="university-motto">Scientia et Virtus • Science et Vertu</p>
            </div>
            
            <!-- Diploma Title -->
            <div class="diploma-title">
                <h2 class="title-main">{{ $diploma_title ?? 'DIPLÔME UNIVERSITAIRE' }}</h2>
                <p class="title-sub">Délivré par l'Université UCAK</p>
            </div>
            
            <!-- Student Information -->
            <div class="student-info-section">
                <h3 class="student-name">{{ $student_info['full_name'] ?? 'NOM DE L\'ÉTUDIANT' }}</h3>
                <div class="student-details">
                    Né(e) le {{ $student_info['date_of_birth'] ?? 'N/A' }} à {{ $student_info['place_of_birth'] ?? 'N/A' }}<br>
                    Nationalité: {{ $student_info['nationality'] ?? 'N/A' }}<br>
                    Matricule: {{ $student_info['student_number'] ?? 'N/A' }}
                </div>
            </div>
            
            <!-- Academic Program Information -->
            <div class="academic-info-section">
                <h4 class="program-name">{{ $academic_info['program_name'] ?? 'PROGRAMME ACADÉMIQUE' }}</h4>
                <div class="program-details">
                    {{ $academic_info['faculty'] ?? 'Faculté' }} - {{ $academic_info['department'] ?? 'Département' }}<br>
                    Niveau: {{ $degree_level_fr ?? $academic_info['level'] ?? 'N/A' }}<br>
                    Durée: {{ $academic_info['duration_semesters'] ?? '0' }} semestres<br>
                    Crédits requis: {{ $academic_info['total_credits_required'] ?? '0' }}
                </div>
            </div>
            
            <!-- Degree Award Statement -->
            <div class="degree-info">
                <p>
                    A satisfait à toutes les exigences académiques et a réussi avec succès 
                    l'ensemble des épreuves du programme susmentionné.
                </p>
                <p>
                    L'Université UCAK confère par la présente le grade de 
                    <strong>{{ $degree_level_fr ?? $academic_info['level'] ?? 'LICENCE' }}</strong>.
                </p>
            </div>
            
            <!-- Deliberation Information -->
            @if(isset($deliberation_info))
            <div class="deliberation-info">
                Décision du jury: {{ $deliberation_info['decision'] ?? 'ADMIS' }}<br>
                Session: {{ $deliberation_info['session_name'] ?? 'Session normale' }}
            </div>
            @endif
            
            <!-- Academic Performance -->
            <div class="gpa-info">
                Moyenne générale: {{ $gpa_formatted ?? '0,00' }}/4.0
            </div>
            
            <!-- Honors Section -->
            @if(!empty($honors_level))
            <div class="honors-section">
                <div class="honors-badge">
                    {{ $honors_level }}
                </div>
            </div>
            @endif
            
            <!-- Date Section -->
            <div class="date-section">
                <p class="date-text">
                    Délivré {{ $issue_date_formatted ?? 'le date' }}<br>
                    Année académique: {{ $academic_info['academic_year'] ?? 'N/A' }}
                </p>
            </div>
            
            <!-- Signatures Section -->
            <div class="signatures-section">
                @foreach($signatures ?? [] as $signature)
                <div class="signature-block">
                    @if(isset($signature['signature_image']) && file_exists($signature['signature_image']))
                    <img src="{{ $signature['signature_image'] }}" alt="Signature" class="signature-image">
                    @else
                    <div class="signature-line"></div>
                    @endif
                    <div class="signatory-name">{{ $signature['name'] ?? 'Nom' }}</div>
                    <div class="signatory-title">{{ $signature['title'] ?? 'Titre' }}</div>
                </div>
                @endforeach
                
                <div class="university-seal">
                    @if(isset($university_seal) && file_exists($university_seal))
                    <img src="{{ $university_seal }}" alt="Sceau universitaire" class="seal-image">
                    @endif
                    <div class="seal-text">SCEAU OFFICIEL</div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer-section">
                <div class="document-number">
                    N° du diplôme: {{ $document_number ?? 'XXXX-XXXX-XXXX' }}
                </div>
                <div class="registration-number">
                    N° d'enregistrement: {{ $registration_number ?? 'UCAK-XXX-XXXX-XXXX' }}
                </div>
                <div class="validity-info">
                    Référence MESR: {{ $validity_info['reference_number'] ?? 'MESR-XXX-XXXX-XXXX' }}<br>
                    Vérifiable en ligne à: {{ $validity_info['verification_url'] ?? '#' }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include template-specific CSS -->
    <style>
        {!! $template->getStyles() !!}
    </style>
    
    <!-- Include branding CSS -->
    @if(isset($branding['colors']))
    <style>
        :root {
            --primary-color: {{ $branding['colors']['primary'] ?? '#1a365d' }};
            --secondary-color: {{ $branding['colors']['secondary'] ?? '#2d3748' }};
            --accent-color: {{ $branding['colors']['accent'] ?? '#3182ce' }};
        }
    </style>
    @endif
</body>
</html>