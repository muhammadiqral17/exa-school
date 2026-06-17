<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Imports\UsersImport;
use App\Exports\TemplateExport;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        // Ensure only admin can access
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $subjects = \App\Models\Subject::all();

        return Inertia::render('Admin/CreateUser', [
            'subjects' => $subjects
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // Ensure only admin can access
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:guru,siswa,admin',
            'nis_nik' => 'nullable|string|max:50|unique:'.User::class,
            'class' => 'nullable|string|max:20',
            'tahun_ajaran' => 'nullable|string|max:20',
            'manual_subjects' => 'nullable|array',
            'manual_subjects.*.name' => $request->role === 'guru' ? 'required|string|max:255' : 'nullable',
            'manual_subjects.*.code' => $request->role === 'guru' ? 'required|string|max:50' : 'nullable',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'nis_nik' => $request->nis_nik,
            'class' => $request->class,
            'tahun_ajaran' => $request->tahun_ajaran,
        ]);

        if ($request->role === 'guru' && $request->has('manual_subjects')) {
            foreach ($request->manual_subjects as $subjectData) {
                if (!empty($subjectData['name']) && !empty($subjectData['code'])) {
                    \App\Models\Subject::updateOrCreate(
                        ['code' => $subjectData['code']],
                        [
                            'name' => $subjectData['name'],
                            'user_id' => $user->id
                        ]
                    );
                }
            }
        }

        return redirect()->route('admin.users.create')->with('success', 'Pengguna berhasil ditambahkan!');
    }

    public function students(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        
        $query = User::where('role', 'siswa');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nis_nik', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $students = $query->orderBy($sort, $direction)->paginate(5)->withQueryString();

        return Inertia::render('Admin/Students', [
            'students' => $students,
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    public function teachers(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        
        $query = User::where('role', 'guru')->with('subjects');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nis_nik', 'like', "%{$search}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $teachers = $query->orderBy($sort, $direction)->paginate(5)->withQueryString();

        return Inertia::render('Admin/Teachers', [
            'teachers' => $teachers,
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    public function admins(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        
        $query = User::where('role', 'admin');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $admins = $query->orderBy($sort, $direction)->paginate(5)->withQueryString();

        return Inertia::render('Admin/Admins', [
            'admins' => $admins,
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class.',email,'.$user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:guru,siswa,admin',
            'nis_nik' => 'nullable|string|max:50|unique:'.User::class.',nis_nik,'.$user->id,
            'class' => 'nullable|string|max:20',
            'tahun_ajaran' => 'nullable|string|max:20',
            'manual_subjects' => 'nullable|array',
            'manual_subjects.*.name' => $request->role === 'guru' ? 'required|string|max:255' : 'nullable',
            'manual_subjects.*.code' => $request->role === 'guru' ? 'required|string|max:50' : 'nullable',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->role = $request->role;
        $user->nis_nik = $request->nis_nik;
        $user->class = $request->class;
        $user->tahun_ajaran = $request->tahun_ajaran;
        $user->save();

        if ($user->role === 'guru' && $request->has('manual_subjects')) {
            // Delete old subjects that are not in the new list (or just delete all and recreate for simplicity)
            // But better to updateOrCreate to preserve IDs if possible, or just delete and recreate.
            // Let's delete subjects that are NOT in the submitted list by code.
            $submittedCodes = collect($request->manual_subjects)->pluck('code')->filter()->toArray();
            \App\Models\Subject::where('user_id', $user->id)->whereNotIn('code', $submittedCodes)->delete();

            foreach ($request->manual_subjects as $subjectData) {
                if (!empty($subjectData['name']) && !empty($subjectData['code'])) {
                    \App\Models\Subject::updateOrCreate(
                        ['code' => $subjectData['code']],
                        [
                            'name' => $subjectData['name'],
                            'user_id' => $user->id
                        ]
                    );
                }
            }
        }

        return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui!');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Pengguna berhasil dihapus!');
    }

    public function importStudents(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt']);
        Excel::import(new UsersImport('siswa'), $request->file('file'));
        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diimport!');
    }

    public function templateStudents()
    {
        return Excel::download(new TemplateExport(['nama', 'nis', 'email', 'password', 'kelas', 'tahun_ajaran']), 'template_siswa.xlsx');
    }

    public function exportStudents(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        return Excel::download(new UsersExport('siswa'), 'data_siswa_terdaftar.xlsx');
    }

    public function importTeachers(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt']);
        Excel::import(new UsersImport('guru'), $request->file('file'));
        return redirect()->route('admin.teachers.index')->with('success', 'Data guru berhasil diimport!');
    }

    public function templateTeachers()
    {
        return Excel::download(new TemplateExport(['nama', 'nik', 'email', 'password', 'kelas_mengajar', 'tahun_ajaran']), 'template_guru.xlsx');
    }

    public function exportTeachers(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        return Excel::download(new UsersExport('guru'), 'data_guru_terdaftar.xlsx');
    }
}
