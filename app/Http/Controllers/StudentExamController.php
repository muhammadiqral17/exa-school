<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class StudentExamController extends Controller
{
    // Quick access to latest exam landing page
    public function quickExam()
    {
        $userClass = Auth::user()->class;

        $exam = Exam::with('subject')
            ->whereHas('subject', function ($query) use ($userClass) {
                $query->where('class', 'LIKE', '%' . $userClass . '%')
                      ->where('academic_year', Auth::user()->tahun_ajaran);
            })
            ->whereHas('questions')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_time')
                      ->orWhere('start_time', '<=', now());
            })
            ->where(function ($query) {
                // Exclude only if they have finished (have end_time)
                $query->whereDoesntHave('results', function ($q) {
                    $q->where('user_id', Auth::id())->whereNotNull('end_time');
                });
            })
            ->latest()
            ->first();

        if (!$exam) {
            return redirect()->route('dashboard')->with('error', 'Belum ada soal yang tersedia.');
        }

        return Inertia::render('Student/QuickExam', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject_name' => $exam->subject->name ?? 'Mata Pelajaran',
                'duration_minutes' => $exam->duration_minutes,
                'question_count' => $exam->questions()->count(),
                'has_finished' => $exam->start_time ? Carbon::parse($exam->start_time)->copy()->addMinutes($exam->duration_minutes)->lt(now()) : false,
                'start_time' => $exam->start_time ? Carbon::parse($exam->start_time)->format('d M Y, H:i') : null,
                'end_time' => $exam->start_time ? Carbon::parse($exam->start_time)->copy()->addMinutes($exam->duration_minutes)->format('d M Y, H:i') : null,
                'is_locked' => (bool) ($exam->results->where('user_id', Auth::id())->first()->is_cheating ?? false),
                'academic_year' => $exam->subject->academic_year ?? null,
                'semester' => $exam->subject->semester ?? null,
                'material' => $exam->subject->material ?? null,
            ]
        ]);
    }

    // List all available exams for student
    public function index()
    {
        $userClass = Auth::user()->class;
        $userId = Auth::id();
        
        $exams = Exam::with(['subject.user', 'questions', 'results' => function($q) use ($userId) {
            $q->where('user_id', $userId);
        }])
            ->whereHas('subject', function ($query) use ($userClass) {
                $query->where('class', 'LIKE', '%' . $userClass . '%')
                      ->where('academic_year', Auth::user()->tahun_ajaran);
            })
            ->whereHas('questions')
            ->where('is_active', true)
            ->where(function ($query) {
                // Allow exams that haven't been started OR have been started but not finished
                $query->whereDoesntHave('results', function ($q) {
                    $q->where('user_id', Auth::id())->whereNotNull('end_time');
                });
            })
            ->get()
            ->map(function ($exam) {
                $startTime = $exam->start_time ? Carbon::parse($exam->start_time) : null;
                $endTime = $startTime ? $startTime->copy()->addMinutes($exam->duration_minutes) : null;
                $can_start = !$startTime || $startTime->lte(now());
                $has_finished = $endTime && now()->gt($endTime);
                
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'subject_name' => $exam->subject->name ?? 'Mata Pelajaran',
                    'teacher_name' => $exam->subject->user->name ?? 'Admin',
                    'class' => $exam->subject->class ?? '-',
                    'duration_minutes' => $exam->duration_minutes,
                    'pg_weight' => $exam->pg_weight,
                    'essay_weight' => $exam->essay_weight,
                    'question_count' => $exam->questions->count(),
                    'has_finished' => $has_finished,
                    'can_start' => $can_start,
                    'start_time' => $startTime ? $startTime->format('d M Y, H:i') : null,
                    'academic_year' => $exam->subject->academic_year ?? null,
                    'semester' => $exam->subject->semester ?? null,
                    'material' => $exam->subject->material ?? null,
                    'end_time' => $endTime ? $endTime->format('d M Y, H:i') : null,
                    'is_locked' => (bool) ($exam->results->first()->is_cheating ?? false),
                ];
            });

        return Inertia::render('Student/ExamList', [
            'exams' => $exams,
        ]);
    }

    // Show exam questions to student
    public function take(Exam $exam)
    {
        if (!$exam->is_active) {
            return redirect()->route('student.exams.index')->with('error', 'Ujian belum dapat dikerjakan.');
        }

        $durationSeconds = $exam->duration_minutes * 60;
        $hasStartTime = false;

        if ($exam->start_time) {
            $hasStartTime = true;
            $startTime = Carbon::parse($exam->start_time);
            $endTime = $startTime->copy()->addMinutes($exam->duration_minutes);

            if (now()->lt($startTime)) {
                return redirect()->route('student.exams.index')->with('error', 'Ujian belum dimulai.');
            }

            if (now()->gt($endTime)) {
                return redirect()->route('student.exams.index')->with('error', 'Waktu ujian telah berakhir.');
            }

            $remainingSeconds = (int) now()->diffInSeconds($endTime);
            $durationSeconds = min($durationSeconds, $remainingSeconds);
        }

        $result = Result::where('exam_id', $exam->id)->where('user_id', Auth::id())->first();
        
        // Redirect only if they have already FINISHED (submitted)
        if ($result && $result->end_time) {
            return redirect()->route('student.exams.result', $exam->id)->with('error', 'Anda sudah menyelesaikan ujian ini.');
        }

        $exam->load(['subject', 'questions']);

        if (!str_contains($exam->subject->class, Auth::user()->class)) {
            return redirect()->route('student.exams.index')->with('error', 'Anda tidak memiliki akses ke ujian ini karena berbeda kelas.');
        }

        if ($exam->subject->academic_year !== Auth::user()->tahun_ajaran) {
            return redirect()->route('student.exams.index')->with('error', 'Anda tidak memiliki akses ke ujian ini karena berbeda tahun ajaran.');
        }

        $questions = $exam->questions->map(function ($q) {
            return [
                'id' => $q->id,
                'type' => $q->type,
                'question_text' => $q->question_text,
                'options' => $q->options,
                'option_images' => $q->option_images,
                'option_a' => $q->option_a,
                'option_b' => $q->option_b,
                'option_c' => $q->option_c,
                'option_d' => $q->option_d,
                'image' => $q->image,
            ];
        })->toArray();

        if ($exam->random_order && count($questions) >= 2) {
            shuffle($questions);
        }

        // Initialize or update result for monitoring
        $currentResult = Result::updateOrCreate(
            ['exam_id' => $exam->id, 'user_id' => Auth::id()],
            [
                'start_time' => $result ? $result->start_time : now(),
                'last_activity' => now(),
                'is_cheating' => $result ? (bool)$result->is_cheating : false, // Preserve lock status from DB
                'answers' => $result ? $result->answers : [],
            ]
        );

        return Inertia::render('Student/TakeExam', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject_name' => $exam->subject->name ?? 'Mata Pelajaran',
                'duration_minutes' => $exam->duration_minutes,
                'remaining_seconds' => $durationSeconds,
                'has_start_time' => $hasStartTime,
                'pg_weight' => $exam->pg_weight,
                'essay_weight' => $exam->essay_weight,
                'is_locked' => (bool) ($currentResult->is_cheating ?? false),
            ],
            'questions' => $questions,
            'initialAnswers' => $currentResult->answers ?? [],
            'initialNotes' => $currentResult->student_notes ?? [],
        ]);
    }

    // Monitoring heartbeat
    public function ping(Exam $exam)
    {
        Result::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->update(['last_activity' => now()]);
        
        return response()->json(['status' => 'ok']);
    }

    // Autosave progress
    public function autosave(Request $request, Exam $exam)
    {
        $request->validate([
            'answers' => 'nullable|array',
            'student_notes' => 'nullable|array',
        ]);

        Result::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->update([
                'answers' => $request->answers ?? [],
                'student_notes' => $request->student_notes ?? [],
                'last_activity' => now(),
            ]);

        return response()->json(['status' => 'saved']);
    }

    // Report cheating
    public function reportCheating(Exam $exam)
    {
        Result::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->update(['is_cheating' => true]);
        
        return response()->json(['status' => 'reported']);
    }

    // Submit exam answers
    public function submit(Request $request, Exam $exam)
    {
        $request->validate([
            'answers' => 'nullable|array',
        ]);

        $exam->load('questions');
        $answers = $request->answers ?? [];

        $pgTotal = 0;
        $pgCorrect = 0;
        $essayCount = 0;

        foreach ($exam->questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;

            if ($question->type === 'pg') {
                $pgTotal++;
                $correctAnswer = $question->answer;
                
                if ($studentAnswer !== null) {
                    // Split the correct answer by comma to support multiple answers
                    $correctAnswersArray = array_map('trim', explode(',', strtolower((string)$correctAnswer)));
                    $studentAnsStr = strtolower((string)$studentAnswer);
                    
                    // Normalize student answer to numeric index if it's a letter
                    $studentAnsIndex = is_numeric($studentAnsStr) ? $studentAnsStr : (string)(ord($studentAnsStr) - 97);
                    
                    $isMatch = false;
                    foreach ($correctAnswersArray as $ans) {
                        $ansIndex = is_numeric($ans) ? (string)$ans : (string)(ord($ans) - 97);
                        if ($studentAnsIndex === $ansIndex) {
                            $isMatch = true;
                            break;
                        }
                    }
                    
                    if ($isMatch) {
                        $pgCorrect++;
                    }
                }
            } elseif ($question->type === 'essay') {
                $essayCount++;
            }
        }

        $pgScore = $pgTotal > 0 ? ($pgCorrect / $pgTotal) * 100 : 0;
        $finalScore = round($pgScore * ($exam->pg_weight / 100), 2);

        Result::updateOrCreate(
            ['exam_id' => $exam->id, 'user_id' => Auth::id()],
            [
                'score' => $finalScore,
                'computer_score' => round($pgScore, 2),
                'answers' => $answers,
                'student_notes' => $request->student_notes ?? [],
                'end_time' => now(),
                'last_activity' => now(),
            ]
        );

        return redirect()->route('student.exams.result', $exam->id);
    }

    // Show result
    public function result(Exam $exam)
    {
        $result = Result::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $exam->load('subject');

        return Inertia::render('Student/ExamResult', [
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject_name' => $exam->subject->name,
                'pg_weight' => $exam->pg_weight,
                'essay_weight' => $exam->essay_weight,
                'is_locked' => (bool) (Result::where('exam_id', $exam->id)->where('user_id', Auth::id())->first()->is_cheating ?? false),
            ],
            'result' => [
                'score' => $result->score,
                'created_at' => $result->created_at,
                'student_signature' => $result->student_signature,
            ]
        ]);
    }

    public function results()
    {
        $results = Result::with(['exam.subject.user'])
            ->where('user_id', Auth::id())
            ->whereNotNull('end_time')
            ->latest()
            ->get()
            ->map(function ($result) {
                return [
                    'id' => $result->id,
                    'score' => $result->score,
                    'created_at' => $result->created_at->format('d M Y, H:i'),
                    'exam_id' => $result->exam->id,
                    'exam_title' => $result->exam->title,
                    'subject_name' => $result->exam->subject->name ?? 'Mata Pelajaran',
                    'teacher_name' => $result->exam->subject->user->name ?? 'Admin',
                    'class' => $result->exam->subject->class ?? '-',
                    'academic_year' => $result->exam->subject->academic_year ?? '-',
                    'semester' => $result->exam->subject->semester ?? '-',
                    'material' => $result->exam->subject->material ?? '-',
                    'start_time' => $result->exam->start_time ? Carbon::parse($result->exam->start_time)->format('d M Y, H:i') : '-',
                    'end_time' => $result->exam->start_time ? Carbon::parse($result->exam->start_time)->copy()->addMinutes($result->exam->duration_minutes)->format('d M Y, H:i') : '-',
                ];
            });

        return Inertia::render('Student/ExamResultsList', [
            'results' => $results,
        ]);
    }

    // Save student signature
    public function saveSignature(Request $request, Exam $exam)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $result = Result::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $result->update([
            'student_signature' => $request->signature,
        ]);

        return back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    // Delete student signature
    public function deleteSignature(Exam $exam)
    {
        $result = Result::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $result->update([
            'student_signature' => null,
        ]);

        return back()->with('success', 'Tanda tangan berhasil dihapus.');
    }
}
