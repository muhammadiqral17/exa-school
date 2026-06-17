<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\ExamHistoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class ExamHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Ensure only admin can access
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $query = Result::with(['user', 'exam.subject']);

        // Filter: Global Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'like', "%{$search}%")
                       ->orWhere('nis_nik', 'like', "%{$search}%")
                       ->orWhere('class', 'like', "%{$search}%");
                })->orWhereHas('exam.subject', function ($qs) use ($search) {
                    $qs->where('name', 'like', "%{$search}%");
                })->orWhereHas('exam', function ($qe) use ($search) {
                    $qe->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Filter: NIS
        if ($request->filled('nis')) {
            $nis = $request->nis;
            $query->whereHas('user', function ($q) use ($nis) {
                $q->where('nis_nik', 'like', "%{$nis}%");
            });
        }

        // Filter: Name
        if ($request->filled('name')) {
            $name = $request->name;
            $query->whereHas('user', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        // Filter: Class (Kelas)
        if ($request->filled('class')) {
            $class = $request->class;
            $query->whereHas('exam.subject', function ($q) use ($class) {
                $q->where('class', $class);
            });
        }

        // Filter: Academic Year (Tahun Ajaran)
        if ($request->filled('academic_year')) {
            $year = $request->academic_year;
            $query->whereHas('exam.subject', function ($q) use ($year) {
                $q->where('academic_year', $year);
            });
        }

        // Filter: Subject (Mata Pelajaran)
        if ($request->filled('subject_name')) {
            $subjectName = $request->subject_name;
            $query->whereHas('exam.subject', function ($q) use ($subjectName) {
                $q->where('name', $subjectName);
            });
        }

        // Filter: Semester
        if ($request->filled('semester')) {
            $semester = $request->semester;
            $query->whereHas('exam.subject', function ($q) use ($semester) {
                $q->where('semester', $semester);
            });
        }

        // Filter: Score Range
        if ($request->filled('score_range')) {
            $range = $request->score_range;
            if ($range === '<60') {
                $query->where('score', '<', 60);
            } elseif ($range === '60-80') {
                $query->whereBetween('score', [60, 80]);
            } elseif ($range === '>80') {
                $query->where('score', '>', 80);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'results.created_at');
        $direction = $request->get('direction', 'desc');

        if ($sort === 'name') {
            $query->join('users', 'results.user_id', '=', 'users.id')
                  ->select('results.*')
                  ->orderBy('users.name', $direction);
        } elseif ($sort === 'nis_nik') {
            $query->join('users', 'results.user_id', '=', 'users.id')
                  ->select('results.*')
                  ->orderBy('users.nis_nik', $direction);
        } elseif ($sort === 'class') {
            $query->join('exams', 'results.exam_id', '=', 'exams.id')
                  ->join('subjects', 'exams.subject_id', '=', 'subjects.id')
                  ->select('results.*')
                  ->orderBy('subjects.class', $direction);
        } elseif ($sort === 'subject') {
            $query->join('exams', 'results.exam_id', '=', 'exams.id')
                  ->join('subjects', 'exams.subject_id', '=', 'subjects.id')
                  ->select('results.*')
                  ->orderBy('subjects.name', $direction);
        } else {
            // Default sorting should be on results table to prevent ambiguity
            $actualSort = str_contains($sort, '.') ? $sort : 'results.' . $sort;
            $query->orderBy($actualSort, $direction);
        }

        $results = $query->paginate(10)->withQueryString();

        // Retrieve dropdown dynamic filters
        $classes = Subject::whereNotNull('class')->where('class', '!=', '')->distinct()->orderBy('class')->pluck('class');
        $academicYears = Subject::whereNotNull('academic_year')->where('academic_year', '!=', '')->distinct()->orderBy('academic_year')->pluck('academic_year');
        $semesters = Subject::whereNotNull('semester')->where('semester', '!=', '')->distinct()->orderBy('semester')->pluck('semester');
        $subjects = Subject::whereNotNull('name')->where('name', '!=', '')->distinct()->orderBy('name')->pluck('name');
        $students = User::where('role', 'siswa')->orderBy('name')->get(['id', 'name', 'nis_nik', 'class', 'tahun_ajaran']);

        return Inertia::render('Admin/ExamHistory', [
            'results' => $results,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'subjects' => $subjects,
            'students' => $students,
            'filters' => $request->only(['search', 'nis', 'name', 'class', 'academic_year', 'subject_name', 'semester', 'score_range', 'sort', 'direction'])
        ]);
    }

    public function export(Request $request)
    {
        // Ensure only admin can access
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only(['search', 'class', 'academic_year', 'subject_name', 'semester', 'score_range']);

        $filename = 'rekap_hasil_ujian.xlsx';

        if ($request->filled('academic_year')) {
            $cleanYear = str_replace('/', '_', $request->academic_year);
            $filename = "rekap_hasil_ujian_ta_{$cleanYear}.xlsx";
        } elseif ($request->filled('subject_name')) {
            $cleanMapel = strtolower(str_replace(' ', '_', $request->subject_name));
            $filename = "rekap_hasil_ujian_mapel_{$cleanMapel}.xlsx";
        } elseif ($request->filled('search')) {
            $cleanSearch = strtolower(str_replace(' ', '_', $request->search));
            $filename = "rekap_hasil_ujian_search_{$cleanSearch}.xlsx";
        }

        return Excel::download(new ExamHistoryExport($filters), $filename);
    }
}

