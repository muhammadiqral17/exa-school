<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class TemplateExport implements FromArray
{
    private $headings;

    public function __construct(array $headings)
    {
        $this->headings = $headings;
    }

    public function array(): array
    {
        return [
            $this->headings,
            [
                'pg', // tipe
                'Contoh soal pilihan ganda dengan 2 jawaban benar dan 2 gambar.', // soal
                'Opsi A', // opsi_a
                'Opsi B', // opsi_b
                'Opsi C', // opsi_c
                'Opsi D', // opsi_d
                'Opsi E', // opsi_e
                'A,C', // jawaban_benar
                'Penjelasan jawaban', // pembahasan
                'BAB 1 HALAMAN 10', // referensi_bab_halaman
                'https://example.com/gambar1.jpg', // gambar_1
                'https://example.com/gambar2.jpg', // gambar_2
                '' // gambar_3
            ]
        ];
    }
}
