<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\QuestionsImport;
use App\Exports\TemplateExport;
use Inertia\Inertia;

class ExamController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'pg_weight' => 'required|integer|min:0|max:100',
            'essay_weight' => 'required|integer|min:0|max:100',
            'start_time' => 'nullable|date',
            'is_active' => 'boolean',
            'random_order' => 'boolean',
            'input_method' => 'required|in:excel,manual',
            'excel_file' => 'nullable|file|mimes:xlsx,xls,csv'
        ]);

        if ((int)$request->pg_weight + (int)$request->essay_weight !== 100) {
            return back()->withErrors(['pg_weight' => 'Total bobot harus 100%']);
        }

        $exam = Exam::create([
            'subject_id' => $request->subject_id,
            'title' => $request->title,
            'duration_minutes' => $request->duration_minutes,
            'pg_weight' => $request->pg_weight,
            'essay_weight' => $request->essay_weight,
            'start_time' => $request->start_time,
            'is_active' => $request->is_active ?? false,
            'random_order' => $request->random_order ?? false,
        ]);

        if ($request->input_method === 'excel' && $request->hasFile('excel_file')) {
            try {
                Excel::import(new QuestionsImport($exam->id, $request->subject_id), $request->file('excel_file'));
            } catch (\Exception $e) {
                $exam->delete();
                return back()->withErrors(['excel_file' => 'Gagal memproses file Excel. Pastikan format sesuai dengan template. Error: ' . $e->getMessage()]);
            }
            return redirect()->route('admin.subjects.index')->with('success', 'Ujian dan Soal berhasil diimport dari Excel.');
        }

        return redirect()->route('admin.exams.questions.create', $exam->id);
    }

    public function createQuestion(Exam $exam)
    {
        $exam->load(['subject', 'questions']);
        return Inertia::render('Admin/Exams/CreateQuestion', [
            'exam' => $exam
        ]);
    }

    public function storeQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            'type' => 'required|in:pg,essay',
            'question_text' => 'required|string',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'option_images' => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'option_a' => 'nullable|string',
            'option_b' => 'nullable|string',
            'option_c' => 'nullable|string',
            'option_d' => 'nullable|string',
            'answer' => 'nullable|string',
            'reference_note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions', 'public');
        }

        $optionImages = null;
        if ($request->has('option_images')) {
            $optionImages = [];
            foreach ($request->option_images as $key => $file) {
                if ($request->hasFile("option_images.{$key}")) {
                    $optionImages[$key] = $request->file("option_images.{$key}")->store('questions/options', 'public');
                } else {
                    $optionImages[$key] = null;
                }
            }
        }

        $question = Question::create([
            'subject_id' => $exam->subject_id,
            'type' => $request->type,
            'question_text' => $request->question_text,
            'options' => $request->options,
            'option_images' => $optionImages,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,
            'answer' => $request->answer,
            'reference_note' => $request->reference_note,
            'image' => $imagePath,
        ]);

        $exam->questions()->attach($question->id);

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'pg_weight' => 'required|integer|min:0|max:100',
            'essay_weight' => 'required|integer|min:0|max:100',
            'start_time' => 'nullable|date',
            'is_active' => 'boolean',
            'random_order' => 'boolean',
        ]);

        if ((int)$request->pg_weight + (int)$request->essay_weight !== 100) {
            return back()->withErrors(['pg_weight' => 'Total bobot harus 100%']);
        }

        $exam->update([
            'title' => $request->title,
            'duration_minutes' => $request->duration_minutes,
            'pg_weight' => $request->pg_weight,
            'essay_weight' => $request->essay_weight,
            'start_time' => $request->start_time,
            'is_active' => $request->is_active ?? false,
            'random_order' => $request->random_order ?? false,
        ]);

        return back()->with('success', 'Pengaturan ujian berhasil diperbarui.');
    }

    public function template()
    {
        $headings = [
            'tipe',
            'soal',
            'opsi_a',
            'opsi_b',
            'opsi_c',
            'opsi_d',
            'opsi_e',
            'jawaban_benar',
            'pembahasan',
            'referensi_bab_halaman',
            'gambar_1',
            'gambar_2',
            'gambar_3'
        ];
        return Excel::download(new TemplateExport($headings), 'template_soal.xlsx');
    }

    public function destroy(Exam $exam)
    {
        // Detach questions mapping to avoid constraint error
        $exam->questions()->detach();
        // Delete associated results
        $exam->results()->delete();
        
        $exam->delete();
        return back()->with('success', 'Ujian berhasil dihapus.');
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $request->validate([
            'type' => 'required|in:pg,essay',
            'question_text' => 'required|string',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'option_images' => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'option_a' => 'nullable|string',
            'option_b' => 'nullable|string',
            'option_c' => 'nullable|string',
            'option_d' => 'nullable|string',
            'answer' => 'nullable|string',
            'reference_note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except(['image', 'option_images']);

        if ($request->hasFile('image')) {
            if ($question->image && \Storage::disk('public')->exists($question->image)) {
                \Storage::disk('public')->delete($question->image);
            }
            $data['image'] = $request->file('image')->store('questions', 'public');
        }

        $existingOptionImages = $question->option_images ?? [];
        if ($request->has('option_images')) {
            foreach ($request->option_images as $key => $file) {
                if ($request->hasFile("option_images.{$key}")) {
                    if (isset($existingOptionImages[$key]) && \Storage::disk('public')->exists($existingOptionImages[$key])) {
                        \Storage::disk('public')->delete($existingOptionImages[$key]);
                    }
                    $existingOptionImages[$key] = $request->file("option_images.{$key}")->store('questions/options', 'public');
                }
            }
        }
        $data['option_images'] = $existingOptionImages;

        $question->update($data);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroyQuestion(Exam $exam, Question $question)
    {
        $exam->questions()->detach($question->id);
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }
}
