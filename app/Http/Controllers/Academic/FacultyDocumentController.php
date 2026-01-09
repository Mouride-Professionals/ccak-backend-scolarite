<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreFacultyDocumentRequest;
use App\Http\Resources\Academic\FacultyDocumentResource;
use App\Models\FacultyDocument;
use App\Models\FacultyMember;
use App\Services\Documents\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FacultyDocumentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:faculty_documents.view')->only('index');
        $this->middleware('permission:faculty_documents.create')->only('store');
    }

    public function index(FacultyMember $faculty_member): JsonResponse
    {
        $documents = $faculty_member->documents()->latest()->get();

        return $this->success(FacultyDocumentResource::collection($documents));
    }

    public function store(StoreFacultyDocumentRequest $request, FacultyMember $faculty_member): JsonResponse
    {
        $file = $request->file('file');
        $type = $request->string('type')->toString();

        $storage = new DocumentStorageService(null, 'faculty_documents');
        $info = $storage->prepareForStorage($file, $faculty_member->id, $type);

        $document = DB::transaction(function () use ($faculty_member, $type, $file, $storage, $info) {
            $path = $storage->store($file, $info['path']);

            return FacultyDocument::create([
                'faculty_member_id' => $faculty_member->id,
                'type' => $type,
                'file_path' => $path,
                'file_name' => $info['file_name'],
                'status' => FacultyDocument::STATUS_PENDING,
            ]);
        });

        return $this->success(new FacultyDocumentResource($document), 'Document uploaded', 201);
    }
}
