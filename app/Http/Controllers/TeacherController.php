<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function schedule(Request $request)
    {
        if ($request->user()->role !== 'guru') {
            abort(403, 'Unauthorized action.');
        }

        $subjects = $request->user()->subjects()->get()->filter(function($subject) use ($request) {
            return $this->isYearCompatible($request->user()->tahun_ajaran, $subject->academic_year);
        });

        return Inertia::render('Teacher/Schedule', [
            'subjects' => $subjects->values()
        ]);
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

    public function exams(Request $request)
    {
        if ($request->user()->role !== 'guru') {
            abort(403, 'Unauthorized action.');
        }

        $subjects = $request->user()->subjects()
            ->with(['exams' => function($query) {
                $query->latest();
            }])->latest()->get()->filter(function($subject) use ($request) {
                return $this->isYearCompatible($request->user()->tahun_ajaran, $subject->academic_year);
            })->sortByDesc(function ($subject) {
                return $subject->exams->max('created_at');
            })->values();

        return Inertia::render('Teacher/Exams', [
            'subjects' => $subjects
        ]);
    }

    public function examResults(Request $request, \App\Models\Exam $exam)
    {
        $exam->load('subject');
        if ($request->user()->role !== 'guru' || $exam->subject->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $results = $exam->results()->with('user')->latest()->get();

        return Inertia::render('Teacher/ExamResults', [
            'exam' => $exam,
            'results' => $results
        ]);
    }

    public function monitoring(Request $request, \App\Models\Exam $exam)
    {
        $exam->load('subject');
        if ($request->user()->role !== 'guru' || $exam->subject->user_id !== $request->user()->id) {
            return response()->json([
                "data" => $request->user(),
                "examp" => $exam
            ]);
            abort(403, 'Unauthorized action.');
        }

        $results = $exam->results()->with('user')->get()->map(function($result) {
            // A student is considered "online" if they had activity in the last 2 minutes
            $is_online = $result->last_activity && \Carbon\Carbon::parse($result->last_activity)->gt(now()->subMinutes(2));
            $result->is_online = $is_online;
            return $result;
        });

        return Inertia::render('Teacher/Monitoring', [
            'exam' => $exam,
            'results' => $results
        ]);
    }

    public function reviewResult(Request $request, \App\Models\Exam $exam, \App\Models\Result $result)
    {
        $exam->load(['subject', 'questions']);
        if ($request->user()->role !== 'guru' || $exam->subject->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $result->load('user');
        
        // Decode answers if they are stored as string JSON, although typically Laravel handles JSON casts
        // If not casted, we might need to json_decode it. It is cast in the migration but check model
        $answers = is_string($result->answers) ? json_decode($result->answers, true) : $result->answers;

        return Inertia::render('Teacher/ReviewResult', [
            'exam' => $exam,
            'result' => $result,
            'answers' => $answers
        ]);
    }

    public function updateScore(Request $request, \App\Models\Result $result)
    {
        if ($request->user()->role !== 'guru') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'essay_score' => 'nullable|numeric|min:0|max:100',
            'teacher_notes' => 'nullable|array',
            'teacher_signature' => 'nullable|string'
        ]);
        
        $result->update([
            'score' => $request->score,
            'essay_score' => $request->essay_score ?? 0,
            'teacher_notes' => $request->teacher_notes,
            'teacher_signature' => $request->teacher_signature
        ]);

        return back()->with('success', 'Nilai berhasil diperbarui.');
    }

    public function exportResults(Request $request, \App\Models\Exam $exam)
    {
        $exam->load('subject');
        if ($request->user()->role !== 'guru' || $exam->subject->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $fileName = 'Rekap_Nilai_' . $exam->subject->name . '_' . $exam->subject->class . '_' . date('Ymd') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ResultsExport($exam), $fileName);
    }

    public function reactivateResult(Request $request, \App\Models\Result $result)
    {
        if ($request->user()->role !== 'guru') {
            abort(403, 'Unauthorized action.');
        }

        $exam = $result->exam;
        if ($exam->start_time) {
            $endTime = \Carbon\Carbon::parse($exam->start_time)->addMinutes($exam->duration_minutes);
            if (now()->gt($endTime)) {
                return back()->with('error', 'Tidak dapat mengaktifkan kembali ujian karena waktu pengerjaan ujian telah berakhir secara resmi.');
            }
        }

        $result->update([
            'end_time' => null,
            'is_cheating' => false,
            'last_activity' => now(), 
        ]);

        return back()->with('success', 'Ujian siswa berhasil dibuka kembali.');
    }

    public function resetCheatStatus(Request $request, \App\Models\Result $result)
    {
        if ($request->user()->role !== 'guru') {
            abort(403, 'Unauthorized action.');
        }

        $result->update([
            'is_cheating' => false,
            'last_activity' => now(),
        ]);

        return back()->with('success', 'Status siswa berhasil diubah menjadi Aman.');
    }

    public function markAsCheating(Request $request, \App\Models\Result $result)
    {
        if ($request->user()->role !== 'guru') {
            abort(403, 'Unauthorized action.');
        }

        $result->update([
            'is_cheating' => true,
            'last_activity' => now(),
        ]);

        return back()->with('success', 'Siswa berhasil ditandai sebagai Curang.');
    }
}
