<?php

declare(strict_types=1);

/*
 * Pesan validasi bahasa Indonesia. Kunci yang tidak ada di sini
 * otomatis jatuh ke lang/en/validation.php (fallback_locale).
 */
return [
    'required' => 'Kolom :attribute wajib diisi.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'email' => 'Kolom :attribute harus berupa alamat surel yang valid.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'unique' => 'Nilai :attribute sudah digunakan.',
    'exists' => 'Nilai :attribute yang dipilih tidak valid.',
    'in' => 'Nilai :attribute yang dipilih tidak valid.',
    'boolean' => 'Kolom :attribute harus bernilai benar atau salah.',
    'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
    'array' => 'Kolom :attribute harus berupa larik.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => 'Nilai :attribute tidak boleh lebih dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => 'Kolom :attribute harus memiliki minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobita.',
        'numeric' => 'Nilai :attribute minimal :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'between' => [
        'array' => 'Kolom :attribute harus memiliki antara :min dan :max item.',
        'file' => 'Ukuran :attribute harus antara :min dan :max kilobita.',
        'numeric' => 'Nilai :attribute harus antara :min dan :max.',
        'string' => 'Kolom :attribute harus antara :min dan :max karakter.',
    ],
    'custom' => [],
    'attributes' => [],
];
