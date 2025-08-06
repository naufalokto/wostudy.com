<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TodoCategory;

class TodoCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tugas',
                'description' => 'Tugas kuliah dan pekerjaan rumah',
                'color' => '#FF6B6B',
                'icon' => 'assignment'
            ],
            [
                'name' => 'Ujian',
                'description' => 'Ujian tengah semester dan akhir semester',
                'color' => '#4ECDC4',
                'icon' => 'quiz'
            ],
            [
                'name' => 'Proyek',
                'description' => 'Proyek kelompok dan individu',
                'color' => '#45B7D1',
                'icon' => 'group_work'
            ],
            [
                'name' => 'Presentasi',
                'description' => 'Presentasi dan seminar',
                'color' => '#96CEB4',
                'icon' => 'present_to_all'
            ],
            [
                'name' => 'Pribadi',
                'description' => 'Kegiatan pribadi dan non-akademik',
                'color' => '#FFEAA7',
                'icon' => 'person'
            ]
        ];

        foreach ($categories as $category) {
            TodoCategory::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
