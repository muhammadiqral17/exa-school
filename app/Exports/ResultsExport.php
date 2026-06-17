<?php

namespace App\Exports;

use App\Models\Exam;
use App\Models\Result;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ResultsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $exam;

    public function __construct(Exam $exam)
    {
        $this->exam = $exam;
    }

    public function collection()
    {
        return Result::with('user')->where('exam_id', $this->exam->id)->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Siswa',
            'NIS / NIP',
            'Kelas',
            'Mata Pelajaran',
            'Nilai Akhir',
            'Waktu Mulai',
            'Waktu Selesai',
            'Lama Pengerjaan (Menit)'
        ];
    }

    public function map($result): array
    {
        static $index = 0;
        $index++;

        $startTime = $result->start_time ? \Carbon\Carbon::parse($result->start_time) : null;
        $endTime = $result->end_time ? \Carbon\Carbon::parse($result->end_time) : \Carbon\Carbon::parse($result->created_at);
        
        $duration = '-';
        if ($startTime && $endTime) {
            $diffMins = $startTime->diffInMinutes($endTime);
            $duration = $diffMins;
        }

        return [
            $index,
            $result->user->name,
            $result->user->nis_nik ?? '-',
            $result->user->class ?? '-',
            $this->exam->subject->name ?? '-',
            $result->score,
            $startTime ? $startTime->format('d M Y, H:i:s') : '-',
            $endTime->format('d M Y, H:i:s'),
            $duration
        ];
    }
}
