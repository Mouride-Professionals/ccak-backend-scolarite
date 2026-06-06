<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseUnit;
use Illuminate\Database\Seeder;

/**
 * Seeds the real HEC maquette (from CCAK spreadsheet).
 *
 * Program structure:
 *   - Tronc Commun  (019904f7-b48d-72c7-9712-830c5e11657b)  → S1–S4  (18 UE, 37 ECUE)
 *   - COMPTA        (1a9eca92-fe8f-462d-93ac-232c85c0967a)   → S5–S6  (8  UE, 16 ECUE)
 *   - ENTREP        (1737ad97-7522-49bc-bde3-485f42ce4a5c)   → S5–S6  (8  UE, 15 ECUE)
 *
 * Total: 3 programs, 34 UEs, 68 ECUEs.
 */
class HecMaquetteSeeder extends Seeder
{
    private const TRONC_COMMUN = '019904f7-b48d-72c7-9712-830c5e11657b';

    private const COMPTA = '1a9eca92-fe8f-462d-93ac-232c85c0967a';

    private const ENTREP = '1737ad97-7522-49bc-bde3-485f42ce4a5c';

    public function run(): void
    {
        $blocks = [
            // Tronc commun — S1
            [self::TRONC_COMMUN, 1, $this->s1()],
            // Tronc commun — S2
            [self::TRONC_COMMUN, 2, $this->s2()],
            // Tronc commun — S3
            [self::TRONC_COMMUN, 3, $this->s3()],
            // Tronc commun — S4
            [self::TRONC_COMMUN, 4, $this->s4()],
            // COMPTA — S5
            [self::COMPTA, 5, $this->s5Compta()],
            // COMPTA — S6
            [self::COMPTA, 6, $this->s6Compta()],
            // ENTREP — S5
            [self::ENTREP, 5, $this->s5Entrep()],
            // ENTREP — S6
            [self::ENTREP, 6, $this->s6Entrep()],
        ];

        foreach ($blocks as [$programId, $semester, $units]) {
            foreach ($units as $u) {
                $unit = CourseUnit::firstOrCreate(
                    ['code' => $u['code']],
                    [
                        'academic_program_id' => $programId,
                        'name' => $u['name'],
                        'semester_number' => $semester,
                        'credits' => $u['credits'],
                        'coefficient' => $u['coefficient'],
                        'type' => 'OBLIGATOIRE',
                        'is_active' => true,
                    ]
                );

                foreach ($u['courses'] as $c) {
                    Course::firstOrCreate(
                        ['code' => $c['code']],
                        [
                            'course_unit_id' => $unit->id,
                            'name' => $c['name'],
                            'description' => null,
                            'credits' => $c['credits'],
                            'hours_lecture' => $c['cm'],
                            'hours_td' => $c['td'],
                            'hours_tp' => 0,
                            'hours_tpe' => $c['tpe'],
                            'vht' => $c['vht'],
                            'coefficient' => $c['coefficient'],
                            'prerequisites' => [],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // S1 — Tronc Commun (5 UE, 10 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s1(): array
    {
        return [
            [
                'code' => 'HEC-111', 'name' => 'Droit-Organisation', 'credits' => 6, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-1111', 'name' => "Introduction à l'étude du droit", 'cm' => 20, 'td' => 0,  'tpe' => 20, 'vht' => 40,  'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-1112', 'name' => "Economie d'entreprise",            'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80,  'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-112', 'name' => 'Mathématiques', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-1121', 'name' => 'Mathématiques', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-1122', 'name' => 'Statistiques',  'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-113', 'name' => 'Eco-Gestion', 'credits' => 9, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-1131', 'name' => 'Economie générale',       'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80,  'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-1132', 'name' => 'Comptabilité Générale I', 'cm' => 30, 'td' => 20, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                ],
            ],
            [
                'code' => 'HEC-114', 'name' => 'Informatique-Langue-Communication', 'credits' => 5, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-1141', 'name' => "Initiation à l'informatique", 'cm' => 20, 'td' => 10, 'tpe' => 30, 'vht' => 60, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-1142', 'name' => 'Techniques de communication', 'cm' => 20, 'td' => 0,  'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-1143', 'name' => 'Anglais',                     'cm' => 10, 'td' => 0,  'tpe' => 10, 'vht' => 20, 'credits' => 1, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-115', 'name' => 'Ethique', 'credits' => 2, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-1151', 'name' => 'Etudes Islamiques', 'cm' => 10, 'td' => 0, 'tpe' => 10, 'vht' => 20, 'credits' => 2, 'coefficient' => 2],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S2 — Tronc Commun (4 UE, 9 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s2(): array
    {
        return [
            [
                'code' => 'HEC-121', 'name' => 'Outils de Gestion', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-1211', 'name' => "Stratégie et gouvernance d'entreprises", 'cm' => 20, 'td' => 10, 'tpe' => 30, 'vht' => 60,  'credits' => 3, 'coefficient' => 2],
                    ['code' => 'HEC-1212', 'name' => 'Gestion des ressources humaines',        'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-122', 'name' => 'Mathématiques Appliquées', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-1221', 'name' => 'Mathématiques appliquées à la gestion', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-1222', 'name' => 'Informatique appliquée à la gestion',   'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-123', 'name' => 'Techniques de Gestion', 'credits' => 8, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-1231', 'name' => 'Comptabilité Générale II', 'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-1232', 'name' => 'Marketing',                'cm' => 30, 'td' => 0,  'tpe' => 30, 'vht' => 60,  'credits' => 3, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-124', 'name' => 'Communication-Leadership', 'credits' => 6, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-1241', 'name' => 'Anglais des affaires I',                'cm' => 20, 'td' => 0, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-1242', 'name' => 'Etudes Islamiques',                     'cm' => 20, 'td' => 0, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-1243', 'name' => 'Développement personnel et leadership', 'cm' => 20, 'td' => 0, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S3 — Tronc Commun (4 UE, 8 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s3(): array
    {
        return [
            [
                'code' => 'HEC-231', 'name' => 'Comptabilité', 'credits' => 9, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-2311', 'name' => 'Comptabilité générale III', 'cm' => 30, 'td' => 20, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-2312', 'name' => 'Comptabilité Analytique I', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80,  'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-232', 'name' => 'Techniques Quantitatives', 'credits' => 7, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-2321', 'name' => 'Gestion prévisionnelle',    'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-2322', 'name' => 'Informatique de gestion I', 'cm' => 5,  'td' => 25, 'tpe' => 30, 'vht' => 60, 'credits' => 3, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-233', 'name' => 'Droit-Ethique', 'credits' => 6, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-2331', 'name' => 'Droit des contrats', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-2332', 'name' => 'Etude Islamique',    'cm' => 20, 'td' => 0,  'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-234', 'name' => 'Finance-Fiscalité', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-2341', 'name' => 'Analyse financière I',   'cm' => 30, 'td' => 20, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-2342', 'name' => 'Fiscalité entreprise I', 'cm' => 20, 'td' => 10, 'tpe' => 30, 'vht' => 60,  'credits' => 3, 'coefficient' => 1],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S4 — Tronc Commun (5 UE, 10 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s4(): array
    {
        return [
            [
                'code' => 'HEC-221', 'name' => 'Finance', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-2211', 'name' => 'Analyse Financière II', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-2212', 'name' => "Finance d'entreprise",  'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-222', 'name' => 'Entreprise', 'credits' => 6, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-2221', 'name' => 'Microéconomie',           'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-2222', 'name' => 'Fiscalité entreprise II', 'cm' => 20, 'td' => 0,  'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-223', 'name' => 'Techniques Quantitatives', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-2231', 'name' => 'Analyse de données',        'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-2232', 'name' => 'Mathématiques financières', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-224', 'name' => 'Ethique-Langue', 'credits' => 4, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-2241', 'name' => 'Etude Islamique',         'cm' => 0, 'td' => 20, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-2242', 'name' => 'Anglais des affaires II', 'cm' => 0, 'td' => 20, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-225', 'name' => 'Droit et Organisation', 'credits' => 4, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-2251', 'name' => 'Droit du travail',                         'cm' => 20, 'td' => 0, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-2252', 'name' => 'Management stratégique des organisations', 'cm' => 20, 'td' => 0, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S5 — Comptabilité et Contrôle de Gestion (4 UE, 8 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s5Compta(): array
    {
        return [
            [
                'code' => 'HEC-311', 'name' => 'Comptabilité', 'credits' => 9, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-3111', 'name' => 'Comptabilité Approfondie', 'cm' => 30, 'td' => 20, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-3112', 'name' => 'Comptabilité bancaire',    'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80,  'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-312', 'name' => 'Outils Audit et Finance', 'credits' => 6, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-3121', 'name' => 'Initiation Audit',  'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-3122', 'name' => 'Finance Islamique', 'cm' => 15, 'td' => 5,  'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-313', 'name' => 'Outils Gestion', 'credits' => 10, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-3131', 'name' => 'Comptabilité analytique II', 'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-3132', 'name' => 'Contrôle de Gestion I',      'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                ],
            ],
            [
                'code' => 'HEC-314', 'name' => 'Outils de Gestion et Leadership', 'credits' => 5, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-3141', 'name' => "Comptabilité appliquée à l'ordinateur", 'cm' => 10, 'td' => 20, 'tpe' => 30, 'vht' => 60, 'credits' => 3, 'coefficient' => 2],
                    ['code' => 'HEC-3142', 'name' => 'Projets Personnel Professionnel I',      'cm' => 10, 'td' => 10, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S6 — Comptabilité et Contrôle de Gestion (4 UE, 8 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s6Compta(): array
    {
        return [
            [
                'code' => 'HEC-321', 'name' => 'Comptabilité', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-3211', 'name' => 'Comptabilité spéciale (SFD, assurances, ONG)', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-3212', 'name' => 'Comptabilité des sociétés',                    'cm' => 25, 'td' => 15, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-322', 'name' => 'Contrôle-Audit', 'credits' => 9, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-3221', 'name' => 'Contrôle de gestion II', 'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-3222', 'name' => 'Audit de gestion',       'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80,  'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-323', 'name' => 'Leadership', 'credits' => 3, 'coefficient' => 2,
                'courses' => [
                    ['code' => 'HEC-3231', 'name' => 'Conférence',      'cm' => 0,  'td' => 20, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                    ['code' => 'HEC-3232', 'name' => 'Etude islamique', 'cm' => 10, 'td' => 0,  'tpe' => 10, 'vht' => 20, 'credits' => 1, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-324', 'name' => 'Recherche et Applications', 'credits' => 10, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-3241', 'name' => 'Méthodologie',                 'cm' => 10, 'td' => 0,  'tpe' => 10,  'vht' => 20,  'credits' => 1, 'coefficient' => 1],
                    ['code' => 'HEC-3242', 'name' => 'Projet de Fin de Cycle (PFC)', 'cm' => 0,  'td' => 20, 'tpe' => 160, 'vht' => 180, 'credits' => 9, 'coefficient' => 4],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S5 — Entrepreneuriat et Création d'Entreprise (4 UE, 8 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s5Entrep(): array
    {
        return [
            [
                'code' => 'HEC-331', 'name' => 'Entrepreneuriat', 'credits' => 7, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-3311', 'name' => 'Entrepreneuriat et innovation', 'cm' => 30, 'td' => 20, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-3312', 'name' => 'Incubateur / Conférences',      'cm' => 0,  'td' => 20, 'tpe' => 20, 'vht' => 40,  'credits' => 2, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-332', 'name' => 'Economie Publique et Sociale', 'credits' => 8, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-3321', 'name' => 'Marchés publics',               'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-3322', 'name' => 'Economie sociale et solidaire', 'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-333', 'name' => 'Comptabilité et Finance', 'credits' => 9, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-3331', 'name' => 'Finance Islamique',       'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80,  'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-3332', 'name' => 'Comptabilité Analytique', 'cm' => 30, 'td' => 20, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-334', 'name' => 'Gestion des Projets', 'credits' => 6, 'coefficient' => 3,
                'courses' => [
                    ['code' => 'HEC-3341', 'name' => 'Gestion de projets',              'cm' => 20, 'td' => 20, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                    ['code' => 'HEC-3342', 'name' => 'Projets Personnel Professionnel', 'cm' => 10, 'td' => 10, 'tpe' => 20, 'vht' => 40, 'credits' => 2, 'coefficient' => 1],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // S6 — Entrepreneuriat et Création d'Entreprise (4 UE, 7 ECUE)
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function s6Entrep(): array
    {
        return [
            [
                'code' => 'HEC-341', 'name' => 'Fiscalité-Fonds', 'credits' => 7, 'coefficient' => 4,
                'courses' => [
                    ['code' => 'HEC-3411', 'name' => 'Techniques de levées de fonds (fundrising)', 'cm' => 15, 'td' => 15, 'tpe' => 30, 'vht' => 60, 'credits' => 3, 'coefficient' => 2],
                    ['code' => 'HEC-3412', 'name' => 'Fiscalité des Entreprises',                  'cm' => 25, 'td' => 15, 'tpe' => 40, 'vht' => 80, 'credits' => 4, 'coefficient' => 2],
                ],
            ],
            [
                'code' => 'HEC-342', 'name' => 'Gestion', 'credits' => 10, 'coefficient' => 5,
                'courses' => [
                    ['code' => 'HEC-3421', 'name' => 'Comptabilité des sociétés', 'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                    ['code' => 'HEC-3422', 'name' => 'Marketing Approfondi',      'cm' => 25, 'td' => 25, 'tpe' => 50, 'vht' => 100, 'credits' => 5, 'coefficient' => 3],
                ],
            ],
            [
                'code' => 'HEC-343', 'name' => 'Ethique', 'credits' => 1, 'coefficient' => 1,
                'courses' => [
                    ['code' => 'HEC-3431', 'name' => 'Etude Islamique', 'cm' => 10, 'td' => 0, 'tpe' => 10, 'vht' => 20, 'credits' => 1, 'coefficient' => 1],
                ],
            ],
            [
                'code' => 'HEC-344', 'name' => 'Recherche et Applications', 'credits' => 12, 'coefficient' => 6,
                'courses' => [
                    ['code' => 'HEC-3441', 'name' => 'Méthodologie',                 'cm' => 10, 'td' => 10, 'tpe' => 20,  'vht' => 40,  'credits' => 2,  'coefficient' => 2],
                    ['code' => 'HEC-3442', 'name' => 'Projet de Fin de Cycle (PFC)', 'cm' => 0,  'td' => 30, 'tpe' => 170, 'vht' => 200, 'credits' => 10, 'coefficient' => 4],
                ],
            ],
        ];
    }
}
