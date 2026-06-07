<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CcakReferentialSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDegreeCycles();
        $this->seedNiveaux();
        $this->seedUfr();
        $this->seedDepartments();
        $this->seedPrograms();
        $this->seedAcademicYears();
    }

    private function seedDegreeCycles(): void
    {
        $cycles = [
            ['id' => '019904ba-7c0e-7f42-8119-7a4e555d3b80', 'name' => 'LICENCE',             'code' => 'L',  'type' => 'LICENCE'],
            ['id' => '019904bf-ff1c-7702-ae6a-51d1abaf968b', 'name' => 'MASTER',              'code' => 'M',  'type' => 'MASTER'],
            ['id' => '019904c0-4f4d-7e58-afb0-51f89d30de0d', 'name' => 'DOCTORAT',            'code' => 'D',  'type' => 'DOCTORAT'],
            ['id' => '01990503-7025-7e23-9de4-640b984ab7e3', 'name' => 'CLASSE PREPARATOIRE', 'code' => 'CP', 'type' => 'CLASSE_PREPARATOIRE'],
        ];

        foreach ($cycles as $cycle) {
            DB::table('degree_cycles')->updateOrInsert(
                ['id' => $cycle['id']],
                array_merge($cycle, ['synced_from' => 'CCAK', 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedNiveaux(): void
    {
        $niveaux = [
            ['id' => '019904c0-95b6-7343-bd37-9ec795bf9861', 'name' => 'LICENCE 1',             'code' => 'L1',  'numero' => 1, 'type' => 'LICENCE',             'degree_cycle_id' => '019904ba-7c0e-7f42-8119-7a4e555d3b80'],
            ['id' => '019904c0-c4e8-7c1f-b2a6-ddbdcc9bbeb4', 'name' => 'LICENCE 2',             'code' => 'L2',  'numero' => 2, 'type' => 'LICENCE',             'degree_cycle_id' => '019904ba-7c0e-7f42-8119-7a4e555d3b80'],
            ['id' => '019904c1-19ce-75bb-b474-7fa1a6494449', 'name' => 'LICENCE 3',             'code' => 'L3',  'numero' => 3, 'type' => 'LICENCE',             'degree_cycle_id' => '019904ba-7c0e-7f42-8119-7a4e555d3b80'],
            ['id' => '019904c1-5372-7576-897f-7e7b3dbce710', 'name' => 'MASTER 1',              'code' => 'M1',  'numero' => 1, 'type' => 'MASTER',              'degree_cycle_id' => '019904bf-ff1c-7702-ae6a-51d1abaf968b'],
            ['id' => '019904c1-ca9f-76ef-a129-effd0e4c301e', 'name' => 'MASTER 2',              'code' => 'M2',  'numero' => 2, 'type' => 'MASTER',              'degree_cycle_id' => '019904bf-ff1c-7702-ae6a-51d1abaf968b'],
            ['id' => '019904c2-6464-790a-9513-2c5b100924b7', 'name' => 'DOCTORAT',              'code' => 'D1',  'numero' => 1, 'type' => 'DOCTORAT',            'degree_cycle_id' => '019904c0-4f4d-7e58-afb0-51f89d30de0d'],
            ['id' => '019904c3-05b7-7cc5-a573-877d584478df', 'name' => 'DOCTORAT',              'code' => 'D2',  'numero' => 2, 'type' => 'DOCTORAT',            'degree_cycle_id' => '019904c0-4f4d-7e58-afb0-51f89d30de0d'],
            ['id' => '019904c3-8c91-7bf8-8571-e38a99f409b5', 'name' => 'DOCTORAT',              'code' => 'D3',  'numero' => 3, 'type' => 'DOCTORAT',            'degree_cycle_id' => '019904c0-4f4d-7e58-afb0-51f89d30de0d'],
            ['id' => '01990504-1095-7070-ae24-b6b735c6fe7b', 'name' => 'CLASSE PREPARATOIRE 1', 'code' => 'CP1', 'numero' => 1, 'type' => 'CLASSE_PREPARATOIRE', 'degree_cycle_id' => '01990503-7025-7e23-9de4-640b984ab7e3'],
        ];

        foreach ($niveaux as $niveau) {
            DB::table('levels')->updateOrInsert(
                ['id' => $niveau['id']],
                array_merge($niveau, ['synced_from' => 'CCAK', 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedDepartments(): void
    {
        // Note: CCAK data has two depts with code BTP in UFR MET.
        // "Artisanat et Industrie" is corrected to code AI to avoid unique(faculty_id, code) conflict.
        $departments = [
            ['id' => '019904cb-0e4a-741c-bc78-064ed07ab964', 'name' => 'Etudes islamiques',                          'code' => 'ETIS', 'faculty_id' => '019904ca-67a3-7153-b0af-328e437a43e7'],
            ['id' => '019904cb-7cd6-7f98-8c9b-6a8efd04fe32', 'name' => 'Langue et Littérature arabes',               'code' => 'LLA',  'faculty_id' => '019904ca-67a3-7153-b0af-328e437a43e7'],
            ['id' => '019904e8-d364-7c82-b2cb-ae470d03442a', 'name' => 'Agronomie-Productions végétales (APV)',       'code' => 'APV',  'faculty_id' => '019904d0-1f9e-7863-a30d-32a9823fd5dc'],
            ['id' => '019904ea-9b13-70a0-87d6-8d97ee23d4b2', 'name' => 'Élevage et Productions animales (EPA)',      'code' => 'LEPA', 'faculty_id' => '019904d0-1f9e-7863-a30d-32a9823fd5dc'],
            ['id' => 'c3b8f61a-5dfa-48ef-a981-a6864bc282d2', 'name' => 'Technologies Agroalimentaires',              'code' => 'TA',   'faculty_id' => '019904d0-1f9e-7863-a30d-32a9823fd5dc'],
            ['id' => '019904ee-ada1-7843-8a0c-1cb8a3728fce', 'name' => 'Artisanat et Industrie',                     'code' => 'AI',   'faculty_id' => '019904ed-5cbe-7e4f-aa9a-ec5b718108bb'],
            ['id' => '019904ef-4dad-7e6d-891a-5faa78c3e710', 'name' => 'Bâtiments et Travaux Publics',               'code' => 'BTP',  'faculty_id' => '019904ed-5cbe-7e4f-aa9a-ec5b718108bb'],
            ['id' => '019904ef-a882-76c2-b327-2200117ed23f', 'name' => 'Electromécanique',                           'code' => 'EM',   'faculty_id' => '019904ed-5cbe-7e4f-aa9a-ec5b718108bb'],
            ['id' => '019904f2-46ad-76e2-8429-a17b45d1ca91', 'name' => 'Informatique et Télécommunications',         'code' => 'IT',   'faculty_id' => '019904ed-5cbe-7e4f-aa9a-ec5b718108bb'],
            ['id' => '019904f7-022f-795b-9846-13d72264bfe3', 'name' => 'Hautes Études Commerciales',                 'code' => 'HEC',  'faculty_id' => '019904ed-5cbe-7e4f-aa9a-ec5b718108bb'],
            ['id' => '019904fa-92e1-78e7-a6da-f09fcb92dfa9', 'name' => 'Biologie et explorations fonctionnelles',   'code' => 'BEF',  'faculty_id' => '019904f9-e104-7508-8e1a-f764447cb1de'],
            ['id' => '019904fa-e282-7d70-93ab-84c75180f28b', 'name' => 'Chirurgie et spécialités chirurgicales',     'code' => 'CSC',  'faculty_id' => '019904f9-e104-7508-8e1a-f764447cb1de'],
            ['id' => '019904fb-e27c-7fdc-90b3-3a4a9b2e05fb', 'name' => 'Médecine et spécialités médicales',         'code' => 'MSM',  'faculty_id' => '019904f9-e104-7508-8e1a-f764447cb1de'],
            ['id' => '019904fc-81ff-76ca-b478-ab335a3b0229', 'name' => 'Odontostomatologie',                         'code' => 'ODO',  'faculty_id' => '019904f9-e104-7508-8e1a-f764447cb1de'],
            ['id' => '019904fc-b7b5-7706-b3f8-b65c5781107f', 'name' => 'Pharmacie',                                  'code' => 'PHAR', 'faculty_id' => '019904f9-e104-7508-8e1a-f764447cb1de'],
            ['id' => '019904fd-0464-78da-ba54-fe6be57550bf', 'name' => 'Sciences Infirmières et Obstétricales',      'code' => 'SIO',  'faculty_id' => '019904f9-e104-7508-8e1a-f764447cb1de'],
            ['id' => '019904ff-9bf4-7ee6-ae25-8d3a57f6ce96', 'name' => 'Langues',                                    'code' => 'LA',   'faculty_id' => '019904ff-4b12-7ee2-9044-26f787fb098b'],
            ['id' => '01990500-78ba-7a7b-9e31-039399ead7bd', 'name' => 'Métiers du Livre',                           'code' => 'ML',   'faculty_id' => '019904ff-4b12-7ee2-9044-26f787fb098b'],
            ['id' => '01990502-baa4-7979-a9d3-bcbe6fae6e17', 'name' => 'Cycle Préparatoire',                         'code' => 'CP',   'faculty_id' => '01990502-5b82-7002-89aa-481933aa2820'],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(
                ['id' => $dept['id']],
                array_merge($dept, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedPrograms(): void
    {
        // All CCAK programs are Licence level (6 semesters, 180 credits).
        // Cycle Préparatoire program is excluded — does not map to LICENCE/MASTER/DOCTORAT enum.
        $programs = [
            ['id' => '019bbb95-fb18-794c-88e0-be583f5fd99c', 'name' => 'Sciences Infirmières et Obstétricales - Option Infirmier',                                    'department_id' => '019904fd-0464-78da-ba54-fe6be57550bf'],
            ['id' => '019904f7-b48d-72c7-9712-830c5e11657b', 'name' => 'Licence HEC - Tronc Commun (L1–L2)',                                                         'department_id' => '019904f7-022f-795b-9846-13d72264bfe3'],
            ['id' => '1a9eca92-fe8f-462d-93ac-232c85c0967a', 'name' => 'Licence HEC - Comptabilité et Contrôle de Gestion',                                          'department_id' => '019904f7-022f-795b-9846-13d72264bfe3'],
            ['id' => '1737ad97-7522-49bc-bde3-485f42ce4a5c', 'name' => "Licence HEC - Entrepreneuriat et Création d'Entreprise",                                     'department_id' => '019904f7-022f-795b-9846-13d72264bfe3'],
            ['id' => '019904eb-9449-7671-ac06-2a236e779b03', 'name' => 'Licence en Productions animales et agroalimentaires - Option : Élevage et Productions animales', 'department_id' => '019904ea-9b13-70a0-87d6-8d97ee23d4b2'],
            ['id' => '019904fe-5659-74aa-a56a-d1f947241139', 'name' => 'Sciences infirmières et obstétricales (LSIO)',                                                  'department_id' => '019904fd-0464-78da-ba54-fe6be57550bf'],
            ['id' => '019904ec-e42f-7522-9a2f-b37785429f15', 'name' => 'Licence en Productions agricoles et agroalimentaires - Option : Technologies agroalimentaires', 'department_id' => 'c3b8f61a-5dfa-48ef-a981-a6864bc282d2'],
            ['id' => '0199776a-e2c7-7336-8267-0b4cf9a044f6', 'name' => 'Licence en Archives (ARC)',                                                                     'department_id' => '01990500-78ba-7a7b-9e31-039399ead7bd'],
            ['id' => '01990500-2cd1-7a24-bc2d-ae7554ab0c89', 'name' => 'Licence en Langues appliquées (LA)',                                                            'department_id' => '019904ff-9bf4-7ee6-ae25-8d3a57f6ce96'],
            ['id' => '01990501-99e3-7a6b-912c-626a171c366a', 'name' => 'Licence en Bibliothèque',                                                                       'department_id' => '01990500-78ba-7a7b-9e31-039399ead7bd'],
            ['id' => '019aa867-2682-7be6-aac0-73568e0b6b9c', 'name' => 'Licence en Documentation',                                                                     'department_id' => '01990500-78ba-7a7b-9e31-039399ead7bd'],
            ['id' => '019904cc-b996-7136-be2d-eea2e7ed68dd', 'name' => 'Études islamiques et arabes — Spécialité : Études islamiques',                                  'department_id' => '019904cb-0e4a-741c-bc78-064ed07ab964'],
            ['id' => '019904cd-b13e-70fe-93c6-ec9e6ce7f4fe', 'name' => 'Études islamiques et arabes — Spécialité : Langue et Littérature arabes',                      'department_id' => '019904cb-7cd6-7f98-8c9b-6a8efd04fe32'],
            ['id' => '019904e9-ecdb-7c3d-881f-2d10765b55ed', 'name' => 'Licence en Productions agricoles — Option : Agronomie et Productions végétales (LAPV)',         'department_id' => '019904e8-d364-7c82-b2cb-ae470d03442a'],
            ['id' => '019904f8-5d49-792b-9bba-5e1c42a9d0c8', 'name' => 'Informatique et Télécommunication',                                                            'department_id' => '019904f2-46ad-76e2-8429-a17b45d1ca91'],
            ['id' => '019bbb98-58ab-7e68-b1da-7c537bff6566', 'name' => 'Sciences Infirmières et Obstétricales — Option Sages-Femmes',                                  'department_id' => '019904fd-0464-78da-ba54-fe6be57550bf'],
        ];

        foreach ($programs as $program) {
            DB::table('academic_programs')->updateOrInsert(
                ['id' => $program['id']],
                array_merge($program, [
                    'level' => 'LICENCE',
                    'duration_semesters' => 6,
                    'total_credits_required' => 180,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedUfr(): void
    {
        $ufrs = [
            ['id' => '019904ca-67a3-7153-b0af-328e437a43e7', 'name' => 'Études islamiques et Arabes (UFR ETISAR)',                    'code' => 'ETISAR'],
            ['id' => '019904d0-1f9e-7863-a30d-32a9823fd5dc', 'name' => 'Sciences agronomiques et Technologies alimentaires (UFR SATA)', 'code' => 'SATA'],
            ['id' => '019904ed-5cbe-7e4f-aa9a-ec5b718108bb', 'name' => 'Métiers et Technologies (MET)',                                  'code' => 'MET'],
            ['id' => '019904f9-e104-7508-8e1a-f764447cb1de', 'name' => 'Sciences et Métiers de la Santé (SMS)',                          'code' => 'SMS'],
            ['id' => '01990502-5b82-7002-89aa-481933aa2820', 'name' => 'Cycle Préparatoire',                                             'code' => 'CP'],
            ['id' => '019904ff-4b12-7ee2-9044-26f787fb098b', 'name' => 'Institut des Langues et des Métiers du livre (ILAMEL)',          'code' => 'ILAMEL'],
        ];

        foreach ($ufrs as $ufr) {
            DB::table('faculties')->updateOrInsert(
                ['id' => $ufr['id']],
                array_merge($ufr, ['synced_from' => 'CCAK', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    private function seedAcademicYears(): void
    {
        $years = [
            ['id' => '019a4c1f-6eb4-7991-82fe-73f55d45fbd8', 'code' => '2023/2024', 'name' => '2023-2024', 'status' => 'F', 'is_current' => false],
            ['id' => '019a4c1f-b0b1-73f5-b62e-f6b016742811', 'code' => '2024/2025', 'name' => '2024-2025', 'status' => 'F', 'is_current' => false],
            ['id' => '019a4c20-05a8-7d4c-96ab-0935ead53f28', 'code' => '2025/2026', 'name' => '2025-2026', 'status' => 'O', 'is_current' => true],
        ];

        foreach ($years as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['id' => $year['id']],
                array_merge($year, ['synced_from' => 'CCAK', 'created_at' => now(), 'updated_at' => now()])
            );
        }
        // this is not in git ?
    }
}
