<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\UserController;
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Announcement Routes
    Route::post('/admin/announcements', [DashboardController::class, 'storeAnnouncement'])->name('admin.announcements.store');
    Route::put('/admin/announcements/{announcement}', [DashboardController::class, 'updateAnnouncement'])->name('admin.announcements.update');
    Route::delete('/admin/announcements/{announcement}', [DashboardController::class, 'destroyAnnouncement'])->name('admin.announcements.destroy');

    // Admin Routes
    Route::get('/admin/pengguna/tambah', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/pengguna', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    
    // Student Exam History Routes
    Route::get('/admin/riwayat-ujian', [\App\Http\Controllers\ExamHistoryController::class, 'index'])->name('admin.exams.history');
    Route::get('/admin/riwayat-ujian/export', [\App\Http\Controllers\ExamHistoryController::class, 'export'])->name('admin.exams.history.export');
    
    Route::get('/admin/siswa', [UserController::class, 'students'])->name('admin.students.index');
    Route::post('/admin/siswa/import', [UserController::class, 'importStudents'])->name('admin.students.import');
    Route::get('/admin/siswa/template', [UserController::class, 'templateStudents'])->name('admin.students.template');
    Route::get('/admin/siswa/export', [UserController::class, 'exportStudents'])->name('admin.students.export');
    
    Route::get('/admin/guru', [UserController::class, 'teachers'])->name('admin.teachers.index');
    Route::post('/admin/guru/import', [UserController::class, 'importTeachers'])->name('admin.teachers.import');
    Route::get('/admin/guru/template', [UserController::class, 'templateTeachers'])->name('admin.teachers.template');
    Route::get('/admin/guru/export', [UserController::class, 'exportTeachers'])->name('admin.teachers.export');
    Route::get('/admin/data-admin', [UserController::class, 'admins'])->name('admin.admins.index');

    
    Route::get('/admin/banksoal', [\App\Http\Controllers\SubjectController::class, 'index'])->name('admin.subjects.index');
    Route::get('/admin/matapelajaran', [\App\Http\Controllers\SubjectController::class, 'assignments'])->name('admin.assignments.index');
    Route::post('/admin/banksoal', [\App\Http\Controllers\SubjectController::class, 'store'])->name('admin.subjects.store');
    Route::put('/admin/banksoal/{subject}', [\App\Http\Controllers\SubjectController::class, 'update'])->name('admin.subjects.update');
    Route::delete('/admin/banksoal/{subject}', [\App\Http\Controllers\SubjectController::class, 'destroy'])->name('admin.subjects.destroy');

    Route::post('/admin/exams', [\App\Http\Controllers\ExamController::class, 'store'])->name('admin.exams.store');
    Route::get('/admin/exams/template', [\App\Http\Controllers\ExamController::class, 'template'])->name('admin.exams.template');
    Route::put('/admin/exams/{exam}', [\App\Http\Controllers\ExamController::class, 'update'])->name('admin.exams.update');
    Route::delete('/admin/exams/{exam}', [\App\Http\Controllers\ExamController::class, 'destroy'])->name('admin.exams.destroy');
    Route::get('/admin/exams/{exam}/questions/create', [\App\Http\Controllers\ExamController::class, 'createQuestion'])->name('admin.exams.questions.create');
    Route::post('/admin/exams/{exam}/questions', [\App\Http\Controllers\ExamController::class, 'storeQuestion'])->name('admin.exams.questions.store');
    Route::put('/admin/questions/{question}', [\App\Http\Controllers\ExamController::class, 'updateQuestion'])->name('admin.questions.update');
    Route::delete('/admin/exams/{exam}/questions/{question}', [\App\Http\Controllers\ExamController::class, 'destroyQuestion'])->name('admin.questions.destroy');

    // Student exam routes
    Route::get('/siswa/ikutiujian', [\App\Http\Controllers\StudentExamController::class, 'quickExam'])->name('student.exams.quick');
    Route::get('/student/ujian', [\App\Http\Controllers\StudentExamController::class, 'index'])->name('student.exams.index');
    Route::get('/student/ujian/{exam}/kerjakan', [\App\Http\Controllers\StudentExamController::class, 'take'])->name('student.exams.take');
    Route::post('/student/ujian/{exam}/ping', [\App\Http\Controllers\StudentExamController::class, 'ping'])->name('student.exams.ping');
    Route::post('/student/ujian/{exam}/autosave', [\App\Http\Controllers\StudentExamController::class, 'autosave'])->name('student.exams.autosave');
    Route::post('/student/ujian/{exam}/cheat', [\App\Http\Controllers\StudentExamController::class, 'reportCheating'])->name('student.exams.cheat');
    Route::post('/student/ujian/{exam}/submit', [\App\Http\Controllers\StudentExamController::class, 'submit'])->name('student.exams.submit');
    Route::get('/student/ujian/{exam}/hasil', [\App\Http\Controllers\StudentExamController::class, 'result'])->name('student.exams.result');
    Route::post('/student/ujian/{exam}/signature', [\App\Http\Controllers\StudentExamController::class, 'saveSignature'])->name('student.exams.signature');
    Route::delete('/student/ujian/{exam}/signature', [\App\Http\Controllers\StudentExamController::class, 'deleteSignature'])->name('student.exams.delete-signature');
    Route::get('/student/nilaiujian', [\App\Http\Controllers\StudentExamController::class, 'results'])->name('student.exams.results');

    // Teacher routes
    Route::get('/guru/jadwal', [\App\Http\Controllers\TeacherController::class, 'schedule'])->name('teacher.schedule');
    Route::get('/guru/ujian', [\App\Http\Controllers\TeacherController::class, 'exams'])->name('teacher.exams');
    Route::get('/guru/ujian/{exam}/monitoring', [\App\Http\Controllers\TeacherController::class, 'monitoring'])->name('teacher.exams.monitoring');
    Route::get('/guru/ujian/{exam}/hasil', [\App\Http\Controllers\TeacherController::class, 'examResults'])->name('teacher.exams.results');
    Route::get('/guru/ujian/{exam}/hasil/export', [\App\Http\Controllers\TeacherController::class, 'exportResults'])->name('teacher.exams.results.export');
    Route::get('/guru/ujian/{exam}/hasil/{result}', [\App\Http\Controllers\TeacherController::class, 'reviewResult'])->name('teacher.exams.review');
    Route::put('/guru/hasil/{result}', [\App\Http\Controllers\TeacherController::class, 'updateScore'])->name('teacher.results.update');
    Route::patch('/guru/hasil/{result}/reactivate', [\App\Http\Controllers\TeacherController::class, 'reactivateResult'])->name('teacher.results.reactivate');
    Route::patch('/guru/hasil/{result}/reset-status', [\App\Http\Controllers\TeacherController::class, 'resetCheatStatus'])->name('teacher.results.reset_status');
    Route::patch('/guru/hasil/{result}/mark-cheat', [\App\Http\Controllers\TeacherController::class, 'markAsCheating'])->name('teacher.results.mark_cheat');
});

Route::get('/foto-profile/{filename}', [\App\Http\Controllers\FilesController::class, 'handleFile'])->name('profile.photo');
Route::get('/gambarsoal/{filename}', [\App\Http\Controllers\FilesController::class, 'handleQuestionFile'])->where('filename', '.*')->name('questions.file');

require __DIR__.'/auth.php';
