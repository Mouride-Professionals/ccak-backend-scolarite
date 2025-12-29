<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Services\DeliberationResultService;
use App\Http\Controllers\Controller;

class DeliberationResultController extends Controller
{
    public function __construct(
        protected DeliberationResultService $service)
    {
        // Add middleware here if needed, specially for permissions
        $this->middleware('permission:deliberation_results.view')->only(['index', 'show']);
        $this->middleware('permission:deliberation_results.create')->only('store');
        $this->middleware('permission:deliberation_results.update')->only('update');
        $this->middleware('permission:deliberation_results.delete')->only('destroy');
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'deliberation_session_id' => 'required|uuid',
            'student_id' => 'required|uuid',
            'decision' => 'required|string',
            'is_with_honors' => 'sometimes|boolean',
        ]);

        $result = $this->service->create($data);

        return response()->json($result, 201);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $result ? response()->json($result) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only([
            'decision', 'is_with_honors'
        ]);

        $result = $this->service->update($id, $data);

        return $result ? response()->json($result) : response()->json(['message' => 'Not found'], 404);
    }

    public function destroy($id)
    {
        return $this->service->delete($id)
            ? response()->json(['message' => 'Deleted'])
            : response()->json(['message' => 'Not found'], 404);
    }
}
