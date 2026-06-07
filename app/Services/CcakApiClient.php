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

    public function getGrades(): array
    {
        return $this->get('/api/v1/grades');
    }

    public function getNiveaux(): array
    {
        return $this->get('/api/v1/niveaux');
    }

    public function getUfr(): array
    {
        return $this->get('/api/v1/ufr');
    }

    public function getDepartements(): array
    {
        return $this->get('/api/v1/departements');
    }

    public function getProgrammes(): array
    {
        return $this->get('/api/v1/programmes');
    }

    public function getAcademicYears(): array
    {
        return $this->get('/api/v1/academic-years');
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
