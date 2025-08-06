<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{
    /**
     * Get all semesters
     */
    public function index()
    {
        $semesters = Semester::orderBy('academic_year', 'desc')
            ->orderBy('semester_number', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'semesters' => $semesters
        ]);
    }

    /**
     * Store new semester
     */
    public function store(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|string|max:20',
            'semester_number' => 'required|integer|in:1,2,3',
            'semester_name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // If this semester is set as active, deactivate others
        if ($request->is_active) {
            Semester::where('is_active', true)->update(['is_active' => false]);
        }

        $semester = Semester::create($request->all());

        return response()->json([
            'success' => true,
            'semester' => $semester,
            'message' => 'Semester berhasil ditambahkan'
        ]);
    }

    /**
     * Update semester
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'academic_year' => 'required|string|max:20',
            'semester_number' => 'required|integer|in:1,2,3',
            'semester_name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $semester = Semester::findOrFail($id);

        // If this semester is set as active, deactivate others
        if ($request->is_active) {
            Semester::where('id', '!=', $id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $semester->update($request->all());

        return response()->json([
            'success' => true,
            'semester' => $semester,
            'message' => 'Semester berhasil diupdate'
        ]);
    }

    /**
     * Delete semester
     */
    public function destroy($id)
    {
        $semester = Semester::findOrFail($id);

        // Check if semester has courses
        if ($semester->courses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus semester yang memiliki matakuliah'
            ], 400);
        }

        $semester->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semester berhasil dihapus'
        ]);
    }

    /**
     * Get current active semester
     */
    public function getCurrentSemester()
    {
        $semester = Semester::where('is_active', true)->first();

        return response()->json([
            'success' => true,
            'semester' => $semester
        ]);
    }

    /**
     * Set semester as active
     */
    public function setActive($id)
    {
        DB::transaction(function() use ($id) {
            // Deactivate all semesters
            Semester::where('is_active', true)->update(['is_active' => false]);
            
            // Activate selected semester
            Semester::where('id', $id)->update(['is_active' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Semester berhasil diaktifkan'
        ]);
    }
} 