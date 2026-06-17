<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    private $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function model(array $row)
    {
        // Normalize keys: lowercase and replace spaces/dashes with underscores
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace([' ', '-'], '_', trim($key)));
            $normalizedRow[$normalizedKey] = $value;
        }

        Log::info('Importing row for role: ' . $this->role, $normalizedRow);
        
        $name = $normalizedRow['nama'] ?? $normalizedRow['name'] ?? $normalizedRow['nama_lengkap'] ?? null;
        $email = $normalizedRow['email'] ?? $normalizedRow['alamat_email'] ?? null;
        $password = $normalizedRow['password'] ?? $normalizedRow['kata_sandi'] ?? $normalizedRow['pass'] ?? null;
        $nisNip = $normalizedRow['nis_nik'] ?? $normalizedRow['nik'] ?? $normalizedRow['nip'] ?? $normalizedRow['nis'] ?? $normalizedRow['id_admin'] ?? null;
        $tahunAjaran = $normalizedRow['tahun_ajaran'] ?? $normalizedRow['thn_ajaran'] ?? null;
        $class = $normalizedRow['kelas_mengajar'] ?? $normalizedRow['kelas'] ?? $normalizedRow['class'] ?? $normalizedRow['kel'] ?? null;

        if (!$name || !$email || !$password) {
            Log::warning('Skipping row: missing required fields', [
                'has_name' => !!$name, 
                'has_email' => !!$email, 
                'has_password' => !!$password,
                'available_keys' => array_keys($normalizedRow)
            ]);
            return null;
        }

        // Prevent duplicate email
        if (User::where('email', $email)->exists()) {
            Log::warning('Skipping row: duplicate email', ['email' => $email]);
            return null;
        }

        // Prevent duplicate nis_nik
        if ($nisNip && User::where('nis_nik', $nisNip)->exists()) {
            Log::warning('Skipping row: duplicate nis_nik', ['nis_nik' => $nisNip]);
            return null;
        }

        return new User([
            'name'     => trim($name),
            'nis_nik'  => $nisNip ? trim($nisNip) : null,
            'email'    => trim($email),
            'password' => Hash::make($password),
            'role'     => $this->role,
            'class'    => $class ? trim($class) : null,
            'tahun_ajaran' => $tahunAjaran ? trim($tahunAjaran) : null,
        ]);
    }
}
