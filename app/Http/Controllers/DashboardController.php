<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Result;
use App\Models\Subject;
use App\Models\User;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $stats = [];

        if ($user->role === 'admin') {
            $stats = [
                'users_count' => User::count(),
                'guru_count' => User::where('role', 'guru')->count(),
                'siswa_count' => User::where('role', 'siswa')->count(),
                'subjects_count' => Subject::count(),
                'exams_count' => Exam::count(),
            ];
        } elseif ($user->role === 'guru') {
            $allTeacherSubjects = Subject::where('user_id', $user->id)->get();
            $compatibleSubjects = $allTeacherSubjects->filter(function($s) use ($user) {
                return $this->isYearCompatible($user->tahun_ajaran, $s->academic_year);
            });
            $compatibleSubjectIds = $compatibleSubjects->pluck('id');

            $stats = [
                'subjects_count' => $compatibleSubjects->count(),
                'exams_count' => Exam::whereIn('subject_id', $compatibleSubjectIds)->count(),
                'questions_count' => Question::whereIn('subject_id', $compatibleSubjectIds)->count(),
                'results_count' => Result::whereHas('exam', function($q) use ($compatibleSubjectIds) {
                    $q->whereIn('subject_id', $compatibleSubjectIds);
                })->count(),
            ];
        } else {
            // Siswa
            $stats = [
                'available_exams' => Exam::where('is_active', true)
                    ->whereHas('questions')
                    ->whereHas('subject', function($query) use ($user) {
                        $query->where('class', 'LIKE', '%' . $user->class . '%')
                              ->where('academic_year', $user->tahun_ajaran);
                    })->count(),
                'completed_exams' => Result::where('user_id', $user->id)->count(),
                'latest_results' => Result::where('user_id', $user->id)
                    ->with('exam.subject')
                    ->latest()
                    ->take(25)
                    ->get()
                    ->map(function($res) {
                        return [
                            'id' => $res->id,
                            'score' => $res->score,
                            'exam_title' => $res->exam->title,
                            'subject_name' => $res->exam->subject->name ?? '-',
                            'material' => $res->exam->subject->material ?? null,
                            'academic_year' => $res->exam->subject->academic_year ?? null,
                            'semester' => $res->exam->subject->semester ?? null,
                            'date' => $res->created_at->format('d M Y, H:i'),
                        ];
                    }),
            ];
        }

        $latestActivitiesQuery = Exam::with('subject.user')
            ->where('is_active', true)
            ->latest('updated_at');

        if ($user->role === 'guru') {
            $teacherSubjectIds = Subject::where('user_id', $user->id)->get()
                ->filter(fn($s) => $this->isYearCompatible($user->tahun_ajaran, $s->academic_year))
                ->pluck('id');
            $latestActivitiesQuery->whereIn('subject_id', $teacherSubjectIds);
        } elseif ($user->role === 'siswa') {
            $latestActivitiesQuery->whereHas('subject', function($query) use ($user) {
                $query->where('class', 'LIKE', '%' . $user->class . '%')
                      ->where('academic_year', $user->tahun_ajaran);
            });
        }

        $latestActivities = $latestActivitiesQuery->take(25)
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'subject_name' => $exam->subject->name ?? 'Umum',
                    'material' => $exam->subject->material ?? null,
                    'academic_year' => $exam->subject->academic_year ?? null,
                    'semester' => $exam->subject->semester ?? null,
                    'teacher_name' => $exam->subject->user->name ?? 'Admin',
                    'time' => $exam->updated_at->format('H:i') . ' WIB',
                    'date' => $exam->updated_at->format('d M Y'),
                ];
            });

        $announcements = Announcement::with('user')
            ->latest()
            ->get()
            ->map(function ($ann) {
                return [
                    'id' => $ann->id,
                    'type' => $ann->type,
                    'text' => $ann->text,
                    'date' => $ann->created_at->format('d M Y'),
                    'creator_name' => $ann->user->name ?? 'System',
                ];
            });

        return Inertia::render('Dashboard', [
            'role' => $user->role,
            'stats' => $stats,
            'latest_activities' => $latestActivities,
            'announcements' => $announcements,
        ]);
    }

    public function storeAnnouncement(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'guru'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'type' => 'required|string|in:PENTING,INFO,PENGUMUMAN',
            'text' => 'required|string',
        ]);

        Announcement::create([
            'type' => $request->type,
            'text' => $request->text,
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Pengumuman berhasil ditambahkan.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'guru'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'type' => 'required|string|in:PENTING,INFO,PENGUMUMAN',
            'text' => 'required|string',
        ]);

        $announcement->update([
            'type' => $request->type,
            'text' => $request->text,
        ]);

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'guru'])) {
            abort(403, 'Unauthorized action.');
        }

        $announcement->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    private function isYearCompatible($userYear, $subjectYear)
    {
        if (!$userYear || !$subjectYear) return true;
        if ($userYear === $subjectYear) return true;

        $u = explode('/', $userYear);
        $s = explode('/', $subjectYear);

        if (count($u) === 2 && count($s) === 2) {
            return (int)$u[0] <= (int)$s[0] && (int)$u[1] >= (int)$s[1];
        }

        return $userYear === $subjectYear;
    }
}
