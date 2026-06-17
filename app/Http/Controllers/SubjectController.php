<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with(['user', 'exams'])->latest()->get();
        $gurus = User::where('role', 'guru')->get();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
            'gurus' => $gurus
        ]);
    }

    public function assignments()
    {
        $subjects = Subject::with(['user'])->latest()->get();
        $gurus = User::where('role', 'guru')->get();

        return Inertia::render('Admin/Subjects/Assignments', [
            'subjects' => $subjects,
            'gurus' => $gurus
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'schedule_time' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:subjects',
            'user_id' => 'required|exists:users,id',
            'academic_year' => 'nullable|string|max:50',
            'semester' => 'nullable|string|max:20',
            'material' => 'nullable|string|max:255'
        ]);

        Subject::create($validated);

        return redirect()->back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'schedule_time' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'user_id' => 'required|exists:users,id',
            'academic_year' => 'nullable|string|max:50',
            'semester' => 'nullable|string|max:20',
            'material' => 'nullable|string|max:255'
        ]);

        $subject->update($validated);

        return redirect()->back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
