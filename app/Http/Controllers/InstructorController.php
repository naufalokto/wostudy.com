<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\Course;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * Get all instructors
     */
    public function index()
    {
        $instructors = Instructor::with(['courses' => function($query) {
            $query->with('semester');
        }])->get();

        return response()->json([
            'success' => true,
            'instructors' => $instructors
        ]);
    }

    /**
     * Store new instructor
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:instructors,email',
            'phone' => 'nullable|string|max:20',
            'department' => 'required|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $instructor = Instructor::create($request->all());

        return response()->json([
            'success' => true,
            'instructor' => $instructor,
            'message' => 'Dosen berhasil ditambahkan'
        ]);
    }

    /**
     * Update instructor
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:instructors,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'department' => 'required|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $instructor = Instructor::findOrFail($id);
        $instructor->update($request->all());

        return response()->json([
            'success' => true,
            'instructor' => $instructor,
            'message' => 'Dosen berhasil diupdate'
        ]);
    }

    /**
     * Delete instructor
     */
    public function destroy($id)
    {
        $instructor = Instructor::findOrFail($id);

        // Check if instructor has courses
        if ($instructor->courses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus dosen yang memiliki matakuliah'
            ], 400);
        }

        $instructor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dosen berhasil dihapus'
        ]);
    }

    /**
     * Get instructor with courses
     */
    public function show($id)
    {
        $instructor = Instructor::with(['courses' => function($query) {
            $query->with(['semester', 'userCourses.user']);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'instructor' => $instructor
        ]);
    }

    /**
     * Get active instructors
     */
    public function getActiveInstructors()
    {
        $instructors = Instructor::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'instructors' => $instructors
        ]);
    }

    /**
     * Get instructor courses
     */
    public function getInstructorCourses($id)
    {
        $courses = Course::where('instructor_id', $id)
            ->with(['semester', 'userCourses.user'])
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }
} 