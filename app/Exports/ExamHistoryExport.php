<?php

namespace App\Exports;

use App\Models\Result;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExamHistoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Result::with(['user', 'exam.subject']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'like', "%{$search}%")
                       ->orWhere('nis_nik', 'like', "%{$search}%")
                       ->orWhere('class', 'like', "%{$search}%");
                })->orWhereHas('exam.subject', function ($qs) use ($search) {
                    $qs->where('name', 'like', "%{$search}%")
                       ->orWhere('academic_year', 'like', "%{$search}%");
                })->orWhereHas('exam', function ($qe) use ($search) {
                    $qe->where('title', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($this->filters['class'])) {
            $class = $this->filters['class'];
            $query->whereHas('exam.subject', function ($q) use ($class) {
                $q->where('class', $class);
            });
        }

        if (!empty($this->filters['academic_year'])) {
            $year = $this->filters['academic_year'];
            $query->whereHas('exam.subject', function ($q) use ($year) {
                $q->where('academic_year', $year);
            });
        }

        if (!empty($this->filters['subject_name'])) {
            $subjectName = $this->filters['subject_name'];
            $query->whereHas('exam.subject', function ($q) use ($subjectName) {
                $q->where('name', $subjectName);
            });
        }

        if (!empty($this->filters['semester'])) {
            $semester = $this->filters['semester'];
            $query->whereHas('exam.subject', function ($q) use ($semester) {
                $q->where('semester', $semester);
            });
        }

        if (!empty($this->filters['score_range'])) {
            $range = $this->filters['score_range'];
            if ($range === '<60') {
                $query->where('score', '<', 60);
            } elseif ($range === '60-80') {
                $query->whereBetween('score', [60, 80]);
            } elseif ($range === '>80') {
                $query->where('score', '>', 80);
            }
        }

        return $query->latest()->get();
    }


    public function headings(): array
    {
        return [
            'No',
            'NIS/NIK',
            'Nama Siswa',
            'Kelas',
            'Tahun Ajaran',
            'Mata Pelajaran',
            'Ujian',
            'Nilai Akhir',
            'Semester',
            'Tanggal Ujian'
        ];
    }

    public function map($result): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $result->user->nis_nik ?? '-',
            $result->user->name ?? '-',
            $result->exam->subject->class ?? '-',
            $result->exam->subject->academic_year ?? '-',
            $result->exam->subject->name ?? '-',
            $result->exam->title ?? '-',
            $result->score,
            $result->exam->subject->semester ?? '-',
            $result->created_at ? $result->created_at->format('d-m-Y H:i') : '-'
        ];
    }
}
