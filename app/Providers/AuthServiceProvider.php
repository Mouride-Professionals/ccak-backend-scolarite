<?php

namespace App\Providers;

use App\Models\DeliberationSession;
use App\Models\Document;
use App\Models\FacultyContract;
use App\Models\FacultyDocument;
use App\Models\FacultyMember;
use App\Models\GeneratedDocument;
use App\Models\Guardian;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use App\Policies\DeliberationSessionPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FacultyContractPolicy;
use App\Policies\FacultyDocumentPolicy;
use App\Policies\FacultyMemberPolicy;
use App\Policies\GeneratedDocumentPolicy;
use App\Policies\GuardianPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\RolePolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        DeliberationSession::class => DeliberationSessionPolicy::class,
        Document::class => DocumentPolicy::class,
        FacultyContract::class => FacultyContractPolicy::class,
        FacultyDocument::class => FacultyDocumentPolicy::class,
        FacultyMember::class => FacultyMemberPolicy::class,
        GeneratedDocument::class => GeneratedDocumentPolicy::class,
        Guardian::class => GuardianPolicy::class,
        Notification::class => NotificationPolicy::class,
        Role::class => RolePolicy::class,
        Student::class => StudentPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('viewApiDocs', function (?User $user): bool {
            if (! config('scramble.require_auth')) {
                return true;
            }

            if (! $user) {
                return false;
            }

            return $user->hasRole('ADMIN')
                || $user->can('permissions.view')
                || $user->can('roles.view');
        });
    }
}
