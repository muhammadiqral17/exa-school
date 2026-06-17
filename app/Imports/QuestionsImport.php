<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Exam;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToModel, WithHeadingRow
{
    private $examId;
    private $subjectId;

    public function __construct($examId, $subjectId)
    {
        $this->examId = $examId;
        $this->subjectId = $subjectId;
    }

    public function model(array $row)
    {
        // Require type and question_text
        if (empty($row['tipe']) || empty($row['soal'])) {
            return null;
        }

        $type = strtolower(trim($row['tipe'])) === 'essay' ? 'essay' : 'pg';

        $options = [];
        $optionKeys = [];
        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'opsi_')) {
                $optionKeys[] = $key;
            }
        }
        sort($optionKeys); // Ensuring opsi_a, opsi_b, opsi_c, etc.

        foreach ($optionKeys as $key) {
            if ($row[$key] !== null && $row[$key] !== '') {
                $options[] = $row[$key];
            }
        }

        $answerStr = $row['jawaban_benar'] ?? null;
        if ($answerStr !== null && $type === 'pg') {
            $answers = array_map('trim', explode(',', $answerStr));
            $answerIndices = [];
            foreach ($answers as $ans) {
                if (!is_numeric($ans)) {
                    $ans = strtolower($ans);
                    $index = ord($ans) - 97; // a -> 0, b -> 1, c -> 2
                    if ($index >= 0) {
                        $answerIndices[] = $index;
                    }
                } else {
                    $answerIndices[] = $ans;
                }
            }
            $answerStr = implode(',', $answerIndices);
        }

        $images = [];
        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'gambar') && $value !== null && $value !== '') {
                $images[] = $value;
            }
        }
        $imageStr = !empty($images) ? implode(',', $images) : null;

        $question = Question::create([
            'subject_id' => $this->subjectId,
            'type' => $type,
            'question_text' => $row['soal'],
            'options' => $options,
            'option_images' => null,
            'option_a' => $type === 'pg' ? ($options[0] ?? null) : null,
            'option_b' => $type === 'pg' ? ($options[1] ?? null) : null,
            'option_c' => $type === 'pg' ? ($options[2] ?? null) : null,
            'option_d' => $type === 'pg' ? ($options[3] ?? null) : null,
            'answer' => $answerStr,
            'explanation' => $row['pembahasan'] ?? null,
            'reference_note' => $row['referensi_bab_halaman'] ?? null,
            'image' => $imageStr,
        ]);

        // Attach to exam
        $exam = Exam::find($this->examId);
        if ($exam) {
            $exam->questions()->attach($question->id);
        }

        return $question;
    }
}
