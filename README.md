# WoStudy.com - Todo List Application Backend

Backend untuk aplikasi todo list dengan fitur dashboard college dan personal, serta collaborative sharing.

## Fitur Utama

### 1. Dashboard College
- Manajemen matakuliah dengan kategori LAB dan LEC
- Input semester, pengajar, dan detail matakuliah
- Todo list untuk setiap matakuliah
- Upload dan download file untuk todo list
- Tracking deadline dan status tugas

### 2. Dashboard Personal
- Todo list pribadi (non-akademik)
- Kategorisasi todo list
- Prioritas dan status tracking
- File management untuk todo pribadi

### 3. Collaborative Dashboard
- Sharing todo list via link
- Permission system (can_edit, can_view)
- Real-time collaboration
- File sharing dengan permission

### 4. File Management
- Upload file (max 10MB)
- Download file dengan tracking
- File permission system
- File search dan filtering

## Struktur Database

### Tabel Utama
- `users` - User utama
- `semesters` - Data semester
- `instructors` - Data pengajar
- `courses` - Data matakuliah
- `user_courses` - Relasi user dengan matakuliah
- `todo_categories` - Kategori todo list
- `todo_lists` - Todo list utama
- `todo_items` - Item dalam todo list
- `files` - File yang diupload
- `shared_todo_lists` - Sharing todo list
- `activity_logs` - Log aktivitas
- `collaborative_participants` - Peserta collaborative
- `user_presence` - Status online user

## API Endpoints

### Authentication
Semua endpoint (kecuali shared) memerlukan authentication menggunakan Laravel Sanctum.

### College Dashboard API

#### GET `/api/college/`
Mendapatkan data dashboard college untuk user yang login.

#### GET `/api/college/course/{courseId}`
Mendapatkan detail matakuliah dengan todo list.

#### POST `/api/college/course`
Membuat matakuliah baru.
```json
{
    "course_code": "IF101",
    "course_name": "Pemrograman Web",
    "course_type": "LEC",
    "instructor_id": 1,
    "semester_id": 1,
    "credits": 3,
    "description": "Deskripsi matakuliah",
    "schedule_day": "Senin",
    "schedule_time": "08:00-10:30",
    "room": "Lab 1.1"
}
```

#### POST `/api/college/todo-list`
Membuat todo list untuk matakuliah.
```json
{
    "course_id": 1,
    "category_id": 1,
    "title": "Tugas Web",
    "description": "Deskripsi tugas",
    "task_type": "individual",
    "priority": "high",
    "deadline": "2024-12-31 23:59:59"
}
```

#### POST `/api/college/todo-item/{todoListId}`
Menambah item ke todo list.
```json
{
    "title": "Buat wireframe",
    "description": "Deskripsi item",
    "is_completed": false
}
```

#### POST `/api/college/upload/{todoListId}`
Upload file untuk todo list.
```multipart
file: [file]
description: "Deskripsi file"
```

### Personal Dashboard API

#### GET `/api/personal/`
Mendapatkan data dashboard personal.

#### POST `/api/personal/todo-list`
Membuat todo list pribadi.
```json
{
    "category_id": 1,
    "title": "Belanja",
    "description": "Belanja kebutuhan",
    "task_type": "individual",
    "priority": "medium",
    "deadline": "2024-12-31 23:59:59"
}
```

#### GET `/api/personal/overdue`
Mendapatkan todo list yang overdue.

### Semester Management API

#### GET `/api/semesters/`
Mendapatkan semua semester.

#### POST `/api/semesters/`
Membuat semester baru.
```json
{
    "academic_year": "2024/2025",
    "semester_number": 1,
    "semester_name": "Semester Ganjil",
    "start_date": "2024-09-01",
    "end_date": "2025-01-31",
    "is_active": true
}
```

#### GET `/api/semesters/current`
Mendapatkan semester yang aktif.

### Instructor Management API

#### GET `/api/instructors/`
Mendapatkan semua pengajar.

#### POST `/api/instructors/`
Membuat pengajar baru.
```json
{
    "name": "Dr. Ahmad Hidayat",
    "email": "ahmad@university.edu",
    "phone": "081234567890",
    "department": "Teknik Informatika",
    "specialization": "Pemrograman Web",
    "is_active": true
}
```

### Course Management API

#### GET `/api/courses/`
Mendapatkan semua matakuliah.

#### GET `/api/courses/current-semester`
Mendapatkan matakuliah semester aktif.

#### POST `/api/courses/{courseId}/enroll`
Mendaftarkan user ke matakuliah.
```json
{
    "user_id": 1
}
```

### Todo Category API

#### GET `/api/categories/`
Mendapatkan semua kategori.

#### POST `/api/categories/`
Membuat kategori baru.
```json
{
    "name": "Tugas",
    "description": "Tugas kuliah",
    "color": "#FF6B6B",
    "icon": "assignment"
}
```

### File Management API

#### GET `/api/files/todo-list/{todoListId}`
Mendapatkan file untuk todo list.

#### POST `/api/files/upload/{todoListId}`
Upload file.
```multipart
file: [file]
description: "Deskripsi file"
```

#### GET `/api/files/download/{fileId}`
Download file.

#### GET `/api/files/search`
Search file.
```
?query=nama_file
```

### Collaborative Dashboard API

#### POST `/api/collaborative/share/{todoListId}`
Share todo list.
```json
{
    "permission_type": "can_edit",
    "shared_with_email": "user@example.com",
    "expires_at": "2024-12-31 23:59:59"
}
```

#### GET `/api/shared/{shareToken}`
Akses todo list yang di-share (public).

## Setup dan Instalasi

### 1. Clone Repository
```bash
git clone <repository-url>
cd wostudy.com
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
# Setup database di .env
php artisan migrate
php artisan db:seed
```

### 5. Storage Setup
```bash
php artisan storage:link
```

### 6. Run Application
```bash
php artisan serve
npm run dev
```

## Permission System

### Todo List Permissions
- **Owner**: Full access (CRUD)
- **Can Edit**: Create, read, update (no delete)
- **Can View**: Read only

### File Permissions
- **Owner**: Full access
- **Can Edit**: Upload, download, update description
- **Can View**: Download only

## Response Format

Semua API response menggunakan format JSON:

```json
{
    "success": true,
    "data": {...},
    "message": "Success message"
}
```

Error response:
```json
{
    "success": false,
    "message": "Error message",
    "errors": {...}
}
```

## Authentication

Gunakan Laravel Sanctum untuk authentication:

```bash
# Login
POST /api/login
{
    "email": "user@example.com",
    "password": "password"
}

# Response
{
    "token": "1|abc123..."
}
```

Include token di header:
```
Authorization: Bearer 1|abc123...
```

## Testing

```bash
php artisan test
```

## Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## License

MIT License 