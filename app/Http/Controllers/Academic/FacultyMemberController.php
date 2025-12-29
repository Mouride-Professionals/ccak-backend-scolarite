<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\FacultyMember;
use Illuminate\Http\Request;

class FacultyMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(FacultyMember::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:faculty_members,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $facultyMember = FacultyMember::create($data);

        return response()->json($facultyMember, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FacultyMember $facultyMember)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FacultyMember $facultyMember)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FacultyMember $facultyMember)
    {
        //
    }
}
