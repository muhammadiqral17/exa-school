<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    protected $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function collection()
    {
        $users = User::where('role', $this->role)->orderBy('name')->get();
        
        return $users->map(function($user) {
            return [
                'nama' => $user->name,
                'nis_nik' => $user->nis_nik,
                'email' => $user->email,
                'kelas' => $user->class,
                'tahun_ajaran' => $user->tahun_ajaran
            ];
        });
    }

    public function headings(): array
    {
        $idHeader = $this->role === 'guru' ? 'nik' : 'nis';
        $classHeader = $this->role === 'guru' ? 'kelas_mengajar' : 'kelas';
        return ['nama', $idHeader, 'email', $classHeader, 'tahun_ajaran'];
    }
}
