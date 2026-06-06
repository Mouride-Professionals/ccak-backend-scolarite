Plan to implement                                                                                                                                       │
│                                                                                                                                                         │
│ Plan: CCAK × MP Interoperability — Sync Infrastructure                                                                                                  │
│                                                                                                                                                         │
│ Context                                                                                                                                                 │
│                                                                                                                                                         │
│ This project (CCAK Backend Scolarité, Laravel 12 / PHP 8.2 / PostgreSQL) needs a data sync layer to receive student and level data from an external     │
│ CCAK API. The implementation adds the required DB schema, models, PHP enums, a typed API client, a sync orchestration service, and an Artisan command — │
│  without touching any existing MP business logic.                                                                                                       │
│                                                                                                                                                         │
│ Focus: API integration layer first (CcakApiClient → SyncService → Artisan command), with migrations/models/enums as necessary dependencies.             │
│                                                                                                                                                         │
│ Project Details                                                                                                                                         │
│                                                                                                                                                         │
│ - Root: ccak-backend-scolarite/                                                                                                                         │
│ - UUID trait: App\Models\Concerns\UsesUuidV7 (UUIDv7, auto-generated) — reuse for SyncLog                                                               │
│ - Enum pattern: app/Enums/DocumentStatus.php — backed string enum with values() helper                                                                  │
│ - No Kernel.php (Laravel 11+): scheduling via routes/console.php                                                                                        │
│ - Existing student statuses in DB: ACTIVE, SUSPENDED, GRADUATED, WITHDRAWN, EXPELLED                                                                    │
│ - keycloak_user_id already exists on the model (fillable) but not in base migration — likely added separately                                           │
│                                                                                                                                                         │
│ Implementation Order                                                                                                                                    │
│                                                                                                                                                         │
│ Step 1 — PHP Enums (app/Enums/)                                                                                                                         │
│                                                                                                                                                         │
│ Create four backed string enums, following DocumentStatus.php pattern (with values() helper):                                                           │
│                                                                                                                                                         │
│ StudentStatus.php                                                                                                                                       │
│ PENDING, ACTIVE, SUSPENDED, GRADUATED, WITHDRAWN, CANCELLED                                                                                             │
│ Note: DB column currently also includes EXPELLED — do NOT alter existing status column in this PR to avoid breaking existing data.                      │
│                                                                                                                                                         │
│ RegistrationStatus.php                                                                                                                                  │
│ DRAFT, PENDING_VALIDATION, VALIDATED, SUSPENDED, CANCELLED                                                                                              │
│                                                                                                                                                         │
│ LevelType.php                                                                                                                                           │
│ LICENCE, MASTER, DOCTORAT                                                                                                                               │
│                                                                                                                                                         │
│ Gender.php (already implied by DB enum('gender', ['M','F']))                                                                                            │
│ M, F                                                                                                                                                    │
│                                                                                                                                                         │
│ Provenance.php (needed for students alter migration)                                                                                                    │
│ ETAT, PLATEFORME                                                                                                                                        │
│                                                                                                                                                         │
│ SyncStatus.php (needed for sync_logs)                                                                                                                   │
│ SUCCESS, PARTIAL, FAILED                                                                                                                                │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 2 — Custom Exception                                                                                                                               │
│                                                                                                                                                         │
│ app/Exceptions/CcakApiException.php                                                                                                                     │
│ - Extends \RuntimeException                                                                                                                             │
│ - Constructor accepts message + optional HTTP status code                                                                                               │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 3 — Migrations                                                                                                                                     │
│                                                                                                                                                         │
│ 3a. create_levels_table                                                                                                                                 │
│ $table->uuid('id')->primary();          // from CCAK, no auto-gen                                                                                       │
│ $table->string('name');                                                                                                                                 │
│ $table->string('code');                                                                                                                                 │
│ $table->string('type');                  // LevelType enum values                                                                                       │
│ $table->integer('duration_semesters');                                                                                                                  │
│ $table->boolean('is_active')->default(true);                                                                                                            │
│ $table->string('synced_from')->default('CCAK');                                                                                                         │
│ $table->timestamp('last_synced_at')->nullable();                                                                                                        │
│ $table->timestamps();                                                                                                                                   │
│ No UsesUuidV7 — UUID is set from CCAK data.                                                                                                             │
│                                                                                                                                                         │
│ 3b. create_sync_logs_table                                                                                                                              │
│ $table->uuid('id')->primary();                                                                                                                          │
│ $table->date('batch_date');                                                                                                                             │
│ $table->string('source')->default('CCAK');                                                                                                              │
│ $table->string('entity_type');                                                                                                                          │
│ $table->integer('total_received')->default(0);                                                                                                          │
│ $table->integer('total_created')->default(0);                                                                                                           │
│ $table->integer('total_updated')->default(0);                                                                                                           │
│ $table->integer('total_errors')->default(0);                                                                                                            │
│ $table->json('error_details')->nullable();                                                                                                              │
│ $table->timestamp('started_at')->nullable();                                                                                                            │
│ $table->timestamp('completed_at')->nullable();                                                                                                          │
│ $table->string('status');               // SyncStatus enum values                                                                                       │
│ $table->timestamps();                                                                                                                                   │
│                                                                                                                                                         │
│ 3c. alter_students_table_add_ccak_fields                                                                                                                │
│ Adds columns (no drops):                                                                                                                                │
│ $table->string('first_name')->nullable()->after('full_name');                                                                                           │
│ $table->string('last_name')->nullable()->after('first_name');                                                                                           │
│ $table->string('ine')->nullable();                                                                                                                      │
│ $table->string('registration_number')->nullable();                                                                                                      │
│ $table->uuid('level_id')->nullable();                                                                                                                   │
│ $table->string('provenance')->nullable();   // Provenance enum                                                                                          │
│ $table->string('synced_from')->nullable();                                                                                                              │
│ $table->timestamp('last_synced_at')->nullable();                                                                                                        │
│ $table->foreign('level_id')->references('id')->on('levels')->onDelete('set null');                                                                      │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 4 — Models                                                                                                                                         │
│                                                                                                                                                         │
│ app/Models/Level.php                                                                                                                                    │
│ - $incrementing = false, $keyType = 'string' (UUID from CCAK, no UsesUuidV7)                                                                            │
│ - Fillable: id, name, code, type, duration_semesters, is_active, synced_from, last_synced_at                                                            │
│ - Casts: type → LevelType::class, is_active → boolean                                                                                                   │
│ - Relationship: students() → hasMany(Student::class, 'level_id')                                                                                        │
│                                                                                                                                                         │
│ app/Models/SyncLog.php                                                                                                                                  │
│ - Uses UsesUuidV7 (local UUID generation)                                                                                                               │
│ - Fillable: all fields                                                                                                                                  │
│ - Casts: status → SyncStatus::class, error_details → array, batch_date → date                                                                           │
│                                                                                                                                                         │
│ Update app/Models/Student.php                                                                                                                           │
│ - Add to $fillable: first_name, last_name, ine, registration_number, level_id, provenance, synced_from, last_synced_at                                  │
│ - Add to $casts: status → StudentStatus::class, provenance → Provenance::class                                                                          │
│ - Add relationship: level() → belongsTo(Level::class)                                                                                                   │
│ - Keep full_name, existing constants, and all existing relationships untouched                                                                          │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 5 — Config & Environment                                                                                                                           │
│                                                                                                                                                         │
│ config/services.php — append:                                                                                                                           │
│ 'ccak' => [                                                                                                                                             │
│     'base_url' => env('CCAK_API_URL'),                                                                                                                  │
│     'api_key'  => env('CCAK_API_KEY'),                                                                                                                  │
│ ],                                                                                                                                                      │
│                                                                                                                                                         │
│ .env.example — append:                                                                                                                                  │
│ CCAK_API_URL=                                                                                                                                           │
│ CCAK_API_KEY=                                                                                                                                           │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 6 — CcakApiClient (app/Services/CcakApiClient.php)                                                                                                 │
│                                                                                                                                                         │
│ - Constructor: reads config('services.ccak.base_url') and config('services.ccak.api_key')                                                               │
│ - getStudentsBulk(): array — GET /api/v1/students/bulk, header X-API-Key: {api_key}, timeout 30s                                                        │
│ - On HTTP error or connection failure: throws CcakApiException                                                                                          │
│ - Returns decoded JSON array directly                                                                                                                   │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 7 — SyncService (app/Services/SyncService.php)                                                                                                     │
│                                                                                                                                                         │
│ Injected dependency: CcakApiClient                                                                                                                      │
│                                                                                                                                                         │
│ Methods:                                                                                                                                                │
│ - fetchStudentsFromCcak(): array — delegates to CcakApiClient::getStudentsBulk()                                                                        │
│ - mapCcakStudentToMp(array $s): array — field mapping as specified; always sets synced_from='CCAK', last_synced_at=now()                                │
│ - mapStudentStatus(string $ccakStatus): string — Pending→PENDING, Active→ACTIVE, Suspended→SUSPENDED, Graduated→GRADUATED, Inactive→WITHDRAWN,          │
│ default→PENDING                                                                                                                                         │
│ - upsertStudent(array $mpStudent): string — updateOrCreate on UUID; on update only overwrites CCAK-owned fields (first_name, last_name, full_name, ine, │
│  registration_number, status, provenance, last_synced_at, synced_from); returns 'CREATED' or 'UPDATED'                                                  │
│ - syncStudents(): SyncLog — orchestrates full sync, creates SyncLog, returns it                                                                         │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 8 — Artisan Command (app/Console/Commands/SyncCcakStudents.php)                                                                                    │
│                                                                                                                                                         │
│ - Signature: sync:ccak-students                                                                                                                         │
│ - Injects SyncService via constructor                                                                                                                   │
│ - Calls syncStudents(), outputs progress with $this->info() / $this->error()                                                                            │
│ - Displays totals (created, updated, errors) on completion                                                                                              │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Step 9 — Scheduling (routes/console.php)                                                                                                                │
│                                                                                                                                                         │
│ Schedule::command('sync:ccak-students')->dailyAt('00:00');                                                                                              │
│                                                                                                                                                         │
│ ---                                                                                                                                                     │
│ Critical Files to Modify                                                                                                                                │
│                                                                                                                                                         │
│ ┌────────────────────────┬───────────────────────────────────────────┐                                                                                  │
│ │          File          │                  Action                   │                                                                                  │
│ ├────────────────────────┼───────────────────────────────────────────┤                                                                                  │
│ │ app/Models/Student.php │ Add fillable, casts, level() relationship │                                                                                  │
│ ├────────────────────────┼───────────────────────────────────────────┤                                                                                  │
│ │ config/services.php    │ Add ccak block                            │                                                                                  │
│ ├────────────────────────┼───────────────────────────────────────────┤                                                                                  │
│ │ .env.example           │ Add CCAK_API_URL, CCAK_API_KEY            │                                                                                  │
│ ├────────────────────────┼───────────────────────────────────────────┤                                                                                  │
│ │ routes/console.php     │ Add schedule                              │                                                                                  │
│ └────────────────────────┴───────────────────────────────────────────┘                                                                                  │
│                                                                                                                                                         │
│ Files to Create                                                                                                                                         │
│                                                                                                                                                         │
│ - app/Enums/StudentStatus.php                                                                                                                           │
│ - app/Enums/RegistrationStatus.php                                                                                                                      │
│ - app/Enums/LevelType.php                                                                                                                               │
│ - app/Enums/Gender.php                                                                                                                                  │
│ - app/Enums/Provenance.php                                                                                                                              │
│ - app/Enums/SyncStatus.php                                                                                                                              │
│ - app/Exceptions/CcakApiException.php                                                                                                                   │
│ - app/Models/Level.php                                                                                                                                  │
│ - app/Models/SyncLog.php                                                                                                                                │
│ - app/Services/CcakApiClient.php                                                                                                                        │
│ - app/Services/SyncService.php                                                                                                                          │
│ - app/Console/Commands/SyncCcakStudents.php                                                                                                             │
│ - database/migrations/*_create_levels_table.php                                                                                                         │
│ - database/migrations/*_create_sync_logs_table.php                                                                                                      │
│ - database/migrations/*_alter_students_table_add_ccak_fields.php                                                                                        │
│                                                                                                                                                         │
│ Verification                                                                                                                                            │
│                                                                                                                                                         │
│ 1. php artisan migrate — all 3 migrations run without error                                                                                             │
│ 2. php artisan sync:ccak-students — command runs, outputs sync summary (will fail gracefully if CCAK_API_URL not set)                                   │
│ 3. Check sync_logs table for a created record with status FAILED/SUCCESS                                                                                │
│ 4. Confirm students table has new columns via \d students in psql                                                                                       │
│ 5. Confirm levels table exists with correct schema     
