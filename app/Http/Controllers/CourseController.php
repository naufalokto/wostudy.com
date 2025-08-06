<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Instructor;
use App\Models\UserCourse;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Get all courses
     */
    public function index()
    {
        $courses = Course::with(['instructor', 'semester', 'userCourses.user'])
            ->orderBy('course_code')
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    /**
     * Get courses for current semester
     */
    public function getCurrentSemesterCourses()
    {
        $currentSemester = Semester::where('is_active', true)->first();
        
        if (!$currentSemester) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada semester aktif'
            ], 404);
        }

        $courses = Course::where('semester_id', $currentSemester->id)
            ->with(['instructor', 'semester', 'userCourses.user'])
            ->orderBy('course_code')
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'semester' => $currentSemester
        ]);
    }

    /**
     * Store new course
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|max:20',
            'course_name' => 'required|string|max:255',
            'course_type' => 'required|in:lab,lecture_only,lab_lecture',
            'instructor_id' => 'required|exists:instructors,id',
            'semester_id' => 'required|exists:semesters,id',
            'credits' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
            'schedule_day' => 'nullable|string|max:20',
            'schedule_time' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:50',
        ]);

        // Check if course code already exists in the same semester
        $existingCourse = Course::where('course_code', $request->course_code)
            ->where('semester_id', $request->semester_id)
            ->first();

        if ($existingCourse) {
            return response()->json([
                'success' => false,
                'message' => 'Kode matakuliah sudah ada di semester ini'
            ], 400);
        }

        $course = Course::create($request->all());

        return response()->json([
            'success' => true,
            'course' => $course->load(['instructor', 'semester']),
            'message' => 'Matakuliah berhasil ditambahkan'
        ]);
    }

    /**
     * Update course
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'course_code' => 'required|string|max:20',
            'course_name' => 'required|string|max:255',
            'course_type' => 'required|in:lab,lecture_only,lab_lecture',
            'instructor_id' => 'required|exists:instructors,id',
            'semester_id' => 'required|exists:semesters,id',
            'credits' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
            'schedule_day' => 'nullable|string|max:20',
            'schedule_time' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:50',
        ]);

        $course = Course::findOrFail($id);

        // Check if course code already exists in the same semester (excluding current course)
        $existingCourse = Course::where('course_code', $request->course_code)
            ->where('semester_id', $request->semester_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingCourse) {
            return response()->json([
                'success' => false,
                'message' => 'Kode matakuliah sudah ada di semester ini'
            ], 400);
        }

        $course->update($request->all());

        return response()->json([
            'success' => true,
            'course' => $course->load(['instructor', 'semester']),
            'message' => 'Matakuliah berhasil diupdate'
        ]);
    }

    /**
     * Delete course
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Check if course has enrolled students
        if ($course->userCourses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus matakuliah yang memiliki mahasiswa terdaftar'
            ], 400);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Matakuliah berhasil dihapus'
        ]);
    }

    /**
     * Get course detail
     */
    public function show($id)
    {
        $course = Course::with(['instructor', 'semester', 'userCourses.user', 'todoLists'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'course' => $course
        ]);
    }

    /**
     * Enroll user to course
     */
    public function enrollUser(Request $request, $courseId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $course = Course::findOrFail($courseId);
        $userId = $request->user_id;

        // Check if user is already enrolled
        $existingEnrollment = UserCourse::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'User sudah terdaftar dalam matakuliah ini'
            ], 400);
        }

        UserCourse::create([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil didaftarkan ke matakuliah'
        ]);
    }

    /**
     * Unenroll user from course
     */
    public function unenrollUser($courseId, $userId)
    {
        $enrollment = UserCourse::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terdaftar dalam matakuliah ini'
            ], 404);
        }

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dikeluarkan dari matakuliah'
        ]);
    }

    /**
     * Get courses by instructor
     */
    public function getCoursesByInstructor($instructorId)
    {
        $courses = Course::where('instructor_id', $instructorId)
            ->with(['semester', 'userCourses.user'])
            ->orderBy('course_code')
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    /**
     * Get courses by semester
     */
    public function getCoursesBySemester($semesterId)
    {
        $courses = Course::where('semester_id', $semesterId)
            ->with(['instructor', 'userCourses.user'])
            ->orderBy('course_code')
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    /**
     * Get user's enrolled courses
     */
    public function getUserCourses($userId)
    {
        $courses = Course::whereHas('userCourses', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with(['instructor', 'semester'])
        ->orderBy('course_code')
        ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    /**
     * Get available course types
     */
    public function getCourseTypes()
    {
        return response()->json([
            'success' => true,
            'course_types' => Course::getCourseTypes()
        ]);
    }
} 