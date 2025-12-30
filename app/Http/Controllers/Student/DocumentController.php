<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Student\ReviewDocumentRequest;
use App\Http\Requests\Student\StoreDocumentRequest;
use App\Models\Admin;
use App\Models\Document;
use App\Models\Student;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DocumentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:documents.create')->only('store');
        $this->middleware('permission:documents.view')->only(['index', 'show']);
        $this->middleware('permission:documents.update')->only('update');
        $this->middleware('permission:documents.delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $studentId): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);

            $documents = QueryBuilder::for(Document::where('student_id', $student->id))
                ->with('reviewer')
                ->allowedIncludes(['reviewer'])
                ->allowedFilters([
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('status'),
                ])
                ->allowedSorts(['id', 'type', 'status', 'uploaded_at', 'created_at'])
                ->defaultSort('-created_at')
                ->paginate($request->get('per_page', 15));

            return $this->success($documents, 'Documents récupérés avec succès.');
        } catch (\Exception $e) {
            return $this->error('Étudiant non trouvé.', 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDocumentRequest $request, string $studentId): JsonResponse
    {
        try {
            // Vérifier que l'étudiant existe
            $student = Student::findOrFail($studentId);

            // Générer un nom de fichier unique
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            // Stocker le fichier de manière sécurisée
            $path = $file->storeAs('documents', $fileName, 'externeStorage');

            // Créer l'enregistrement du document
            $document = Document::create([
                'student_id' => $student->id,
                'type' => $request->type,
                'file_path' => $path,
                'file_name' => $originalName,
                'status' => 'PENDING',
                'uploaded_at' => now(),
            ]);

            return $this->success(
                $document->load('student'),
                'Document uploadé avec succès.',
                201
            );
        } catch (\Exception $e) {
            // Supprimer le fichier en cas d'erreur
            if (isset($path) && Storage::disk('externeStorage')->exists($path)) {
                Storage::disk('externeStorage')->delete($path);
            }

            return $this->error('Erreur lors de l\'upload du document.', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $studentId, string $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);
            $document = $student->documents()->with('reviewer')->findOrFail($id);

            return $this->success($document, 'Document récupéré avec succès.');
        } catch (\Exception $e) {
            return $this->error('Document non trouvé.', 404);
        }
    }

    /**
     * Review the specified document.
     */
    public function review(ReviewDocumentRequest $request, string $id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);

            // Autoriser la revue
            $this->authorize('review', $document);

            // Vérifier que le document est en attente
            if (!$document->isPending()) {
                return $this->error('Ce document a déjà été revu.', 400);
            }

            // Obtenir ou créer l'admin
            $admin = $request->user()->getOrCreateAdmin();

            // Approuver ou rejeter selon le statut
            if ($request->status === 'APPROVED') {
                $document->approve($admin, $request->notes);
            } else {
                $document->reject($admin, $request->notes);
            }

            return $this->success(
                $document->load('student', 'reviewer'),
                'Document revu avec succès.'
            );
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            return $this->error('Document non trouvé.', 404);
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la revue du document.', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $studentId, string $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);
            $document = $student->documents()->findOrFail($id);

            // Only allow updating pending documents
            if (!$document->isPending()) {
                return $this->error('Seuls les documents en attente peuvent être modifiés.', 400);
            }

            $validated = $request->validate([
                'type' => 'sometimes|required|string|in:' . implode(',', array_keys(Document::typeLabels())),
                'document' => 'sometimes|required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            ]);

            if ($request->hasFile('document')) {
                // Delete old file
                if (Storage::disk('externeStorage')->exists($document->file_path)) {
                    Storage::disk('externeStorage')->delete($document->file_path);
                }

                // Upload new file
                $file = $request->file('document');
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $path = $file->storeAs('documents', $fileName, 'externeStorage');

                $document->update([
                    'file_path' => $path,
                    'file_name' => $originalName,
                    'uploaded_at' => now(),
                ]);
            }

            if ($request->has('type')) {
                $document->update(['type' => $request->type]);
            }

            return $this->success(
                $document->load('student'),
                'Document mis à jour avec succès.'
            );
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la mise à jour du document.', 500);
        }
    }


    public function destroy(string $studentId, string $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);
            $document = $student->documents()->findOrFail($id);


            if (!$document->isPending()) {
                return $this->error('Seuls les documents en attente peuvent être supprimés.', 400);
            }


            if (Storage::disk('externeStorage')->exists($document->file_path)) {
                Storage::disk('externeStorage')->delete($document->file_path);
            }


            $document->delete();

            return $this->success(null, 'Document supprimé avec succès.');
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la suppression du document.', 500);
        }
    }
}
