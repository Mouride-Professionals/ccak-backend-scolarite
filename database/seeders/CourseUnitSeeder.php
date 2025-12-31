<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\CourseUnit;
use Illuminate\Database\Seeder;

class CourseUnitSeeder extends Seeder
{
    public function run(): void
    {
        $programs = AcademicProgram::with('department')->get();
        if ($programs->isEmpty()) {
            $this->call(AcademicProgramSeeder::class);
            $programs = AcademicProgram::with('department')->get();
        }

        foreach ($programs as $program) {
            $units = $this->unitsForProgram($program->name, $program->level, $program->department?->code ?? 'GEN');
            foreach ($units as $unit) {
                CourseUnit::firstOrCreate(
                    ['code' => $unit['code']],
                    [
                        'academic_program_id' => $program->id,
                        'name' => $unit['name'],
                        'semester_number' => $unit['semester'],
                        'credits' => $unit['credits'],
                        'type' => $unit['type'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array{code: string, name: string, semester: int, credits: int, type: string}>
     */
    private function unitsForProgram(string $programName, string $level, string $deptCode): array
    {
        $key = strtolower($programName);
        $prefix = $deptCode . '-' . ($level === 'MASTER' ? 'M' : ($level === 'DOCTORAT' ? 'D' : 'L'));

        $catalog = [
            'licence informatique' => [
                ['ALG', 'Algorithmique', 1, 6],
                ['PROG1', 'Programmation 1', 1, 6],
                ['MATHI', 'Mathematiques pour informatique', 1, 6],
                ['ARCHI', 'Architecture des ordinateurs', 1, 6],
                ['PROG2', 'Programmation 2', 2, 6],
                ['SD', 'Structures de donnees', 2, 6],
                ['BDD', 'Bases de donnees', 2, 6],
                ['SE', 'Systemes d exploitation', 2, 6],
            ],
            'master informatique' => [
                ['DATA', 'Bases de donnees avancees', 1, 6],
                ['IA', 'Intelligence artificielle', 1, 6],
                ['RESEAUX', 'Reseaux avances', 1, 6],
                ['SECU', 'Securite informatique', 1, 6],
                ['CLOUD', 'Informatique en nuage', 2, 6],
                ['ML', 'Apprentissage automatique', 2, 6],
                ['PROJ', 'Projet de master', 2, 6],
                ['RECH', 'Methodologie de recherche', 2, 6],
            ],
            'licence mathematiques' => [
                ['AN1', 'Analyse 1', 1, 6],
                ['AL1', 'Algebre 1', 1, 6],
                ['GEOM', 'Geometrie', 1, 6],
                ['INFO', 'Informatique pour mathematiques', 1, 6],
                ['AN2', 'Analyse 2', 2, 6],
                ['AL2', 'Algebre 2', 2, 6],
                ['PROBA', 'Probabilites', 2, 6],
                ['STAT', 'Statistiques', 2, 6],
            ],
            'licence physique' => [
                ['MEC', 'Mecanique', 1, 6],
                ['EM', 'Electromagnetisme', 1, 6],
                ['THERMO', 'Thermodynamique', 1, 6],
                ['MATHP', 'Mathematiques pour physiciens', 1, 6],
                ['OPT', 'Optique', 2, 6],
                ['MOD', 'Physique moderne', 2, 6],
                ['ELEC', 'Electronique', 2, 6],
                ['EXP', 'Methodes experimentales', 2, 6],
            ],
            'licence chimie' => [
                ['CHGEN', 'Chimie generale', 1, 6],
                ['CHORG', 'Chimie organique', 1, 6],
                ['CHPHY', 'Chimie physique', 1, 6],
                ['ANAL', 'Analyse chimique', 1, 6],
                ['CHAN', 'Chimie analytique', 2, 6],
                ['BIOC', 'Biochimie', 2, 6],
                ['SOL', 'Chimie des solutions', 2, 6],
                ['THERM', 'Thermochimie', 2, 6],
            ],
            'licence biologie' => [
                ['CELL', 'Biologie cellulaire', 1, 6],
                ['GEN', 'Genetique', 1, 6],
                ['BIOC', 'Biochimie', 1, 6],
                ['ECO', 'Ecologie', 1, 6],
                ['MICRO', 'Microbiologie', 2, 6],
                ['PHYS', 'Physiologie', 2, 6],
                ['BOTA', 'Botanique', 2, 6],
                ['ZOO', 'Zoologie', 2, 6],
            ],
            'licence lettres modernes' => [
                ['LITF', 'Litterature francaise', 1, 6],
                ['LING', 'Linguistique', 1, 6],
                ['EXPR', 'Expression ecrite', 1, 6],
                ['CULT', 'Culture generale', 1, 6],
                ['LITA', 'Litterature africaine', 2, 6],
                ['STYL', 'Stylistique', 2, 6],
                ['METH', 'Methodologie', 2, 6],
                ['HID', 'Histoire des idees', 2, 6],
            ],
            'licence histoire' => [
                ['HANC', 'Histoire ancienne', 1, 6],
                ['HMOD', 'Histoire moderne', 1, 6],
                ['HSEN', 'Histoire du Senegal', 1, 6],
                ['METH', 'Methodologie historique', 1, 6],
                ['HCON', 'Histoire contemporaine', 2, 6],
                ['HAFR', 'Histoire africaine', 2, 6],
                ['ARCH', 'Sources et archives', 2, 6],
                ['GEO', 'Geopolitique', 2, 6],
            ],
            'licence geographie' => [
                ['GPH', 'Geographie physique', 1, 6],
                ['GHU', 'Geographie humaine', 1, 6],
                ['CART', 'Cartographie', 1, 6],
                ['SIG', 'Systemes d information geographique', 1, 6],
                ['GSEN', 'Geographie du Senegal', 2, 6],
                ['AMEN', 'Amenagement du territoire', 2, 6],
                ['CLIM', 'Climatologie', 2, 6],
                ['GEOM', 'Geomorphologie', 2, 6],
            ],
            'licence philosophie' => [
                ['LOG', 'Logique', 1, 6],
                ['ETH', 'Ethique', 1, 6],
                ['HPHI', 'Histoire de la philosophie', 1, 6],
                ['EPI', 'Epistemologie', 1, 6],
                ['PHIM', 'Philosophie moderne', 2, 6],
                ['PHIA', 'Philosophie africaine', 2, 6],
                ['META', 'Metaphysique', 2, 6],
                ['EST', 'Esthetique', 2, 6],
            ],
            'licence droit public' => [
                ['INTRO', 'Introduction au droit', 1, 6],
                ['CONST', 'Droit constitutionnel', 1, 6],
                ['INST', 'Institutions politiques', 1, 6],
                ['METH', 'Methodologie juridique', 1, 6],
                ['ADMIN', 'Droit administratif', 2, 6],
                ['CIV', 'Droit civil', 2, 6],
                ['PEN', 'Droit penal', 2, 6],
                ['OBL', 'Droit des obligations', 2, 6],
            ],
            'licence droit prive' => [
                ['INTRO', 'Introduction au droit', 1, 6],
                ['CIV1', 'Droit civil 1', 1, 6],
                ['CONST', 'Droit constitutionnel', 1, 6],
                ['METH', 'Methodologie juridique', 1, 6],
                ['CIV2', 'Droit civil 2', 2, 6],
                ['PEN', 'Droit penal', 2, 6],
                ['COM', 'Droit commercial', 2, 6],
                ['FAM', 'Droit de la famille', 2, 6],
            ],
            'licence sciences politiques' => [
                ['THEO', 'Theorie politique', 1, 6],
                ['RI', 'Relations internationales', 1, 6],
                ['INST', 'Institutions politiques', 1, 6],
                ['SOC', 'Sociologie politique', 1, 6],
                ['PP', 'Politiques publiques', 2, 6],
                ['METH', 'Methodes d analyse', 2, 6],
                ['GEO', 'Geopolitique', 2, 6],
                ['COM', 'Communication politique', 2, 6],
            ],
            'licence economie' => [
                ['MICRO', 'Microeconomie', 1, 6],
                ['MACRO', 'Macroeconomie', 1, 6],
                ['COMPN', 'Comptabilite nationale', 1, 6],
                ['STAT', 'Statistiques', 1, 6],
                ['DEV', 'Economie du developpement', 2, 6],
                ['MON', 'Monnaie et finance', 2, 6],
                ['ECO', 'Econometrie', 2, 6],
                ['GEST', 'Introduction a la gestion', 2, 6],
            ],
            'licence gestion' => [
                ['MNG', 'Management', 1, 6],
                ['COMG', 'Comptabilite generale', 1, 6],
                ['MKT', 'Marketing', 1, 6],
                ['MATHG', 'Mathematiques pour la gestion', 1, 6],
                ['FIN', 'Finance d entreprise', 2, 6],
                ['CDG', 'Controle de gestion', 2, 6],
                ['GRH', 'Gestion des ressources humaines', 2, 6],
                ['DRA', 'Droit des affaires', 2, 6],
            ],
            'licence comptabilite' => [
                ['COMG', 'Comptabilite generale', 1, 6],
                ['COMA', 'Comptabilite analytique', 1, 6],
                ['FISC', 'Fiscalite', 1, 6],
                ['AUD', 'Audit', 1, 6],
                ['COMA2', 'Comptabilite approfondie', 2, 6],
                ['GBUD', 'Gestion budgetaire', 2, 6],
                ['DRF', 'Droit fiscal', 2, 6],
                ['LOG', 'Logiciels comptables', 2, 6],
            ],
            'master sante publique' => [
                ['EPI', 'Epidemiologie', 1, 6],
                ['BIO', 'Biostatistiques', 1, 6],
                ['COMM', 'Sante communautaire', 1, 6],
                ['POL', 'Politiques de sante', 1, 6],
                ['GEST', 'Gestion des programmes', 2, 6],
                ['SMI', 'Sante maternelle et infantile', 2, 6],
                ['NUT', 'Nutrition', 2, 6],
                ['HYG', 'Hygiene et prevention', 2, 6],
            ],
            'doctorat medecine' => [
                ['ANA', 'Anatomie', 1, 6],
                ['PHY', 'Physiologie', 1, 6],
                ['BIOC', 'Biochimie', 1, 6],
                ['HISTO', 'Histologie', 1, 6],
                ['MICRO', 'Microbiologie', 2, 6],
                ['PATH', 'Pathologie generale', 2, 6],
                ['PHAR', 'Pharmacologie', 2, 6],
                ['IMM', 'Immunologie', 2, 6],
            ],
            'doctorat pharmacie' => [
                ['ANA', 'Anatomie', 1, 6],
                ['BIOC', 'Biochimie', 1, 6],
                ['BOT', 'Botanique', 1, 6],
                ['CHORG', 'Chimie organique', 1, 6],
                ['MICRO', 'Microbiologie', 2, 6],
                ['PHAR', 'Pharmacologie', 2, 6],
                ['GNO', 'Galeniques et formulations', 2, 6],
                ['ANAL', 'Analyse pharmaceutique', 2, 6],
            ],
        ];

        $rows = $catalog[$key] ?? $this->defaultUnitsForLevel($level);

        return array_map(function (array $row) use ($prefix) {
            return [
                'code' => $prefix . '-' . $row[0],
                'name' => $row[1],
                'semester' => $row[2],
                'credits' => $row[3],
                'type' => 'OBLIGATOIRE',
            ];
        }, $rows);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: int, 3: int}>
     */
    private function defaultUnitsForLevel(string $level): array
    {
        if ($level === 'MASTER') {
            return [
                ['RECH', 'Methodologie de recherche', 1, 6],
                ['PROJ', 'Projet applique', 1, 6],
                ['SPEC1', 'Specialisation 1', 1, 6],
                ['SPEC2', 'Specialisation 2', 1, 6],
                ['STAGE', 'Stage professionnel', 2, 6],
                ['MEM', 'Memoire', 2, 6],
                ['SEMI', 'Seminaire', 2, 6],
                ['ETH', 'Ethique professionnelle', 2, 6],
            ];
        }

        if ($level === 'DOCTORAT') {
            return [
                ['MET', 'Methodes avancees', 1, 6],
                ['RES', 'Recherche appliquee', 1, 6],
                ['THE', 'Theories avancees', 1, 6],
                ['SEM1', 'Seminaire doctoral 1', 1, 6],
                ['SEM2', 'Seminaire doctoral 2', 2, 6],
                ['PUBL', 'Publication scientifique', 2, 6],
                ['ETH', 'Ethique de la recherche', 2, 6],
                ['PROJ', 'Projet doctoral', 2, 6],
            ];
        }

        return [
            ['UE1', 'Outils fondamentaux', 1, 6],
            ['UE2', 'Communication', 1, 6],
            ['UE3', 'Methodologie universitaire', 1, 6],
            ['UE4', 'Culture generale', 1, 6],
            ['UE5', 'Approfondissement 1', 2, 6],
            ['UE6', 'Approfondissement 2', 2, 6],
            ['UE7', 'Projet tuteur', 2, 6],
            ['UE8', 'Langues', 2, 6],
        ];
    }
}
