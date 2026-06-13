<?php

namespace App\Providers;

use App\Services\Search\SearchService;
use App\Services\Search\Searchers\AcademicProgramSearcher;
use App\Services\Search\Searchers\CourseSearcher;
use App\Services\Search\Searchers\CourseUnitSearcher;
use App\Services\Search\Searchers\DepartmentSearcher;
use App\Services\Search\Searchers\ExamSessionSearcher;
use App\Services\Search\Searchers\FacultyMemberSearcher;
use App\Services\Search\Searchers\FacultySearcher;
use App\Services\Search\Searchers\StudentSearcher;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchService::class, fn () => new SearchService([
            new StudentSearcher(),
            new FacultyMemberSearcher(),
            new CourseSearcher(),
            new CourseUnitSearcher(),
            new AcademicProgramSearcher(),
            new DepartmentSearcher(),
            new FacultySearcher(),
            new ExamSessionSearcher(),
        ]));
    }
}
