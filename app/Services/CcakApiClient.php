<?php

namespace App\Services;

use App\Exceptions\CcakApiException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class CcakApiClient
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ccak.base_url'), '/');
        $this->apiKey = (string) config('services.ccak.api_key');
    }

    public function getStudentsBulk(): array
    {
        $all = [];
        $page = 1;

        do {
            $response = $this->get('/api/admin-service/student', timeout: 120, query: ['page' => $page, 'pageSize' => 100]);
            $all = array_merge($all, $response['data'] ?? []);
            $hasNext = $response['hasNextPage'] ?? false;
            $page++;
        } while ($hasNext);

        return $all;
    }

    /**
     * Fetches all pages from /Student/with-registrations and returns a flat array
     * of registration objects, each augmented with 'studentUuid' (the student CCAK UUID).
     */
    public function getRegistrationsBulk(): array
    {
        $all = [];
        $page = 1;

        do {
            $response = $this->get('/api/admin-service/Student/with-registrations', 120, [
                'page' => $page,
                'pageSize' => 100,
            ]);

            foreach ($response['data'] ?? [] as $student) {
                $studentUuid = $student['id'];
                foreach ($student['registrations'] ?? [] as $registration) {
                    $all[] = array_merge($registration, ['studentUuid' => $studentUuid]);
                }
            }

            $hasNext = $response['hasNextPage'] ?? false;
            $page++;
        } while ($hasNext);

        return $all;
    }

    /**
     * Fetches registrations for a single student by their CCAK UUID.
     */
    public function getStudentRegistrations(string $studentUuid): array
    {
        $response = $this->get("/api/admin-service/Student/{$studentUuid}/registrations");

        return $response['registrations'] ?? [];
    }

    // No endpoint available yet — degree cycles are seeded statically.
    // public function getGrades(): array {}

    // Depends on degree_cycles FK — kept commented until getGrades() is available.
    // public function getNiveaux(): array {}

    public function getUfr(): array
    {
        $response = $this->get('/api/admin-service/pedagogique/ufrs');

        return $response['data'] ?? [];
    }

    public function getDepartements(): array
    {
        $response = $this->get('/api/admin-service/pedagogique/departments');

        return $response['data'] ?? [];
    }

    public function getProgrammes(): array
    {
        $response = $this->get('/api/admin-service/pedagogique/programs');

        return $response['data'] ?? [];
    }

    public function getAcademicYears(): array
    {
        $response = $this->get('/api/admin-service/pedagogique/accademic-years');

        return $response['data'] ?? [];
    }

    private function get(string $path, int $timeout = 30, array $query = []): array
    {
        try {
            $response = Http::timeout($timeout)
                ->withHeader('X-API-Key', $this->apiKey)
                ->get("{$this->baseUrl}{$path}", $query);

            if ($response->failed()) {
                throw new CcakApiException(
                    "CCAK API error on {$path}: HTTP {$response->status()}",
                    $response->status()
                );
            }

            return $response->json();
        } catch (CcakApiException $e) {
            throw $e;
        } catch (RequestException $e) {
            throw new CcakApiException("CCAK API connection failed on {$path}: {$e->getMessage()}", 0, $e);
        } catch (\Throwable $e) {
            throw new CcakApiException("CCAK API unexpected error on {$path}: {$e->getMessage()}", 0, $e);
        }
    }
}
