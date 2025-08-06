<?php

namespace App\Http\Controllers;

use App\Models\TodoCategory;
use App\Models\TodoList;
use Illuminate\Http\Request;

class TodoCategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index()
    {
        $categories = TodoCategory::withCount(['todoLists' => function($query) {
            $query->where('user_id', auth()->id());
        }])->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:todo_categories,name',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7', // Hex color code
            'icon' => 'nullable|string|max:50',
        ]);

        $category = TodoCategory::create($request->all());

        return response()->json([
            'success' => true,
            'category' => $category,
            'message' => 'Kategori berhasil ditambahkan'
        ]);
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:todo_categories,name,' . $id,
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        $category = TodoCategory::findOrFail($id);
        $category->update($request->all());

        return response()->json([
            'success' => true,
            'category' => $category,
            'message' => 'Kategori berhasil diupdate'
        ]);
    }

    /**
     * Delete category
     */
    public function destroy($id)
    {
        $category = TodoCategory::findOrFail($id);

        // Check if category has todo lists
        if ($category->todoLists()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus kategori yang memiliki todo list'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus'
        ]);
    }

    /**
     * Get category with todo lists
     */
    public function show($id)
    {
        $category = TodoCategory::with(['todoLists' => function($query) {
            $query->where('user_id', auth()->id())
                  ->with(['items', 'course']);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'category' => $category
        ]);
    }

    /**
     * Get default categories
     */
    public function getDefaultCategories()
    {
        $defaultCategories = [
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

        return response()->json([
            'success' => true,
            'default_categories' => $defaultCategories
        ]);
    }

    /**
     * Create default categories
     */
    public function createDefaultCategories()
    {
        $defaultCategories = [
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

        $createdCategories = [];
        foreach ($defaultCategories as $categoryData) {
            $category = TodoCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
            $createdCategories[] = $category;
        }

        return response()->json([
            'success' => true,
            'categories' => $createdCategories,
            'message' => 'Kategori default berhasil dibuat'
        ]);
    }
} 