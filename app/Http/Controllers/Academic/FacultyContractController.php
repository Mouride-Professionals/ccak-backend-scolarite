<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreFacultyContractRequest;
use App\Http\Resources\Academic\FacultyContractResource;
use App\Models\FacultyContract;
use App\Models\FacultyMember;
use App\Services\Documents\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FacultyContractController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:faculty_contracts.view')->only('index');
        $this->middleware('permission:faculty_contracts.create')->only('store');
    }

    public function index(FacultyMember $faculty_member): JsonResponse
    {
        $contracts = $faculty_member->contracts()->latest()->get();

        return $this->success(FacultyContractResource::collection($contracts));
    }

    public function store(StoreFacultyContractRequest $request, FacultyMember $faculty_member): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('file');
        $storage = new DocumentStorageService(null, 'faculty_contracts');

        $contract = DB::transaction(function () use ($data, $file, $storage, $faculty_member) {
            $status = $data['status'] ?? null;
            $isCurrent = (bool) ($data['is_current'] ?? false);
            if ($isCurrent && $status === null) {
                $status = FacultyContract::STATUS_ACTIVE;
            }

            $payload = [
                'faculty_member_id' => $faculty_member->id,
                'contract_type' => $data['contract_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'salary' => $data['salary'] ?? null,
                'terms' => $data['terms'] ?? null,
                'status' => $status ?? FacultyContract::STATUS_DRAFT,
                'is_current' => $isCurrent,
            ];

            if ($file) {
                $info = $storage->prepareForStorage($file, $faculty_member->id, 'contract');
                $payload['file_path'] = $storage->store($file, $info['path']);
                $payload['file_name'] = $info['file_name'];
            }

            if ($payload['is_current']) {
                FacultyContract::where('faculty_member_id', $faculty_member->id)->update(['is_current' => false]);
            }

            return FacultyContract::create($payload);
        });

        return $this->success(new FacultyContractResource($contract), 'Contract created', 201);
    }
}
