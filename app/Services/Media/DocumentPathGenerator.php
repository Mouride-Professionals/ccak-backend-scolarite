<?php

namespace App\Services\Media;

use App\Models\Document;
use App\Models\FacultyDocument;
use App\Models\GeneratedDocument;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class DocumentPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->basePath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media) . 'responsive-images/';
    }

    private function basePath(Media $media): string
    {
        $model = $media->model;
        $collection = $media->collection_name;
        if ($model instanceof Document) {
            $studentId = $model->student_id;
            $type = $model->type?->value ?? $collection;
            return "documents/{$studentId}/{$type}/";
        }

        if ($model instanceof FacultyDocument) {
            $facultyId = $model->faculty_member_id;
            $type = $model->type ?? $collection;
            return "faculty-documents/{$facultyId}/{$type}/";
        }

        if ($model instanceof GeneratedDocument) {
            $studentId = $model->student_id;
            $number = $model->document_number ?? (string) ($media->uuid ?: $media->id);
            return "generated-documents/{$studentId}/{$number}/";
        }

        $modelType = Str::snake(class_basename($model));
        return "media/{$modelType}/{$collection}/";
    }
}
