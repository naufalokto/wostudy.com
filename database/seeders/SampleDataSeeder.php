<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Semester;
use App\Models\Instructor;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample semester
        $semester = Semester::firstOrCreate(
            ['academic_year' => '2024/2025'],
            [
                'name' => 'Semester Ganjil 2024/2025',
                'semester_number' => 1,
                'start_date' => '2024-09-01',
                'end_date' => '2025-01-31',
                'is_active' => true
            ]
        );

        // Create sample instructors
        $instructors = [
            [
                'name' => 'Dr. Ahmad Hidayat',
                'email' => 'ahmad.hidayat@university.edu',
                'phone' => '081234567890',
                'department' => 'Teknik Informatika',
                'specialization' => 'Pemrograman Web',
                'is_active' => true
            ],
            [
                'name' => 'Dr. Sarah Wijaya',
                'email' => 'sarah.wijaya@university.edu',
                'phone' => '081234567891',
                'department' => 'Teknik Informatika',
                'specialization' => 'Basis Data',
                'is_active' => true
            ],
            [
                'name' => 'Dr. Budi Santoso',
                'email' => 'budi.santoso@university.edu',
                'phone' => '081234567892',
                'department' => 'Teknik Informatika',
                'specialization' => 'Algoritma dan Struktur Data',
                'is_active' => true
            ]
        ];

        foreach ($instructors as $instructorData) {
            Instructor::firstOrCreate(
                ['email' => $instructorData['email']],
                $instructorData
            );
        }

        // Get instructors
        $ahmad = Instructor::where('email', 'ahmad.hidayat@university.edu')->first();
        $sarah = Instructor::where('email', 'sarah.wijaya@university.edu')->first();
        $budi = Instructor::where('email', 'budi.santoso@university.edu')->first();

        // Create sample courses
        $courses = [
            [
                'course_code' => 'IF101',
                'course_name' => 'Pemrograman Web Dasar',
                'course_type' => 'lecture_only',
                'instructor_id' => $ahmad->id,
                'semester_id' => $semester->id,
                'credits' => 3,
                'description' => 'Mata kuliah dasar pemrograman web menggunakan HTML, CSS, dan JavaScript',
                'schedule_day' => 'Senin',
                'schedule_time' => '08:00-10:30',
                'room' => 'Lab 1.1'
            ],
            [
                'course_code' => 'IF102',
                'course_name' => 'Pemrograman Web Lanjutan',
                'course_type' => 'lab_lecture',
                'instructor_id' => $ahmad->id,
                'semester_id' => $semester->id,
                'credits' => 2,
                'description' => 'Praktikum pemrograman web lanjutan dengan framework modern',
                'schedule_day' => 'Selasa',
                'schedule_time' => '13:00-15:30',
                'room' => 'Lab 1.2'
            ],
            [
                'course_code' => 'IF201',
                'course_name' => 'Basis Data',
                'course_type' => 'lab_lecture',
                'instructor_id' => $sarah->id,
                'semester_id' => $semester->id,
                'credits' => 3,
                'description' => 'Konsep dan implementasi basis data relasional',
                'schedule_day' => 'Rabu',
                'schedule_time' => '10:00-12:30',
                'room' => 'Ruang 2.1'
            ],
            [
                'course_code' => 'IF202',
                'course_name' => 'Praktikum Basis Data',
                'course_type' => 'lab',
                'instructor_id' => $sarah->id,
                'semester_id' => $semester->id,
                'credits' => 1,
                'description' => 'Praktikum implementasi basis data menggunakan MySQL',
                'schedule_day' => 'Kamis',
                'schedule_time' => '15:00-17:30',
                'room' => 'Lab 2.1'
            ],
            [
                'course_code' => 'IF301',
                'course_name' => 'Algoritma dan Struktur Data',
                'course_type' => 'lecture_only',
                'instructor_id' => $budi->id,
                'semester_id' => $semester->id,
                'credits' => 3,
                'description' => 'Konsep algoritma dan struktur data untuk pemecahan masalah',
                'schedule_day' => 'Jumat',
                'schedule_time' => '08:00-10:30',
                'room' => 'Ruang 3.1'
            ]
        ];

        foreach ($courses as $courseData) {
            Course::firstOrCreate(
                ['course_code' => $courseData['course_code'], 'semester_id' => $semester->id],
                $courseData
            );
        }

        // Enroll sample user to courses (if user exists)
        $user = User::first();
        if ($user) {
            $sampleCourses = Course::take(3)->get();
            foreach ($sampleCourses as $course) {
                UserCourse::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => $course->id],
                    [
                        'enrolled_at' => now(),
                        'status' => 'active'
                    ]
                );
            }
        }
    }
}
