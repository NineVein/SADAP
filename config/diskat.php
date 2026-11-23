<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Verifikasi Dua Langkah (2FA)
    |--------------------------------------------------------------------------
    | wajib : bila true, peran di bawah dipaksa mengaktifkan 2FA sebelum bisa
    |         memakai sistem. Atur lewat TWO_FACTOR_WAJIB di .env (aktifkan di produksi).
    | peran : daftar peran yang diwajibkan. Peran lain boleh mengaktifkan sendiri.
    */
    'two_factor' => [
        'wajib' => (bool) env('TWO_FACTOR_WAJIB', false),
        'peran' => ['admin', 'supervisor'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Kepengurusan Panti
    |--------------------------------------------------------------------------
    */
    'jabatan' => [
        'Ketua',
        'Sekretaris',
        'Bendahara',
        'Pengasuh',
        'Staf Administrasi',
        'Anggota',
    ],

    // Jabatan yang hanya boleh ada 1 orang berstatus 'aktif' dalam satu panti
    'jabatan_tunggal' => [
        'Ketua',
        'Sekretaris',
        'Bendahara',
    ],

    'wajib_pengurus_aktif' => true,

    /*
    |--------------------------------------------------------------------------
    | Batas Usia Klien per Kategori LKS
    |--------------------------------------------------------------------------
    | CONTOH awal. Sesuaikan dengan klasifikasi dan peraturan Dinas Sosial.
    | anak_maks  : klien kategori Anak tidak boleh lebih tua dari ini (tahun).
    | lansia_min : klien kategori Lanjut Usia minimal berusia ini (tahun).
    | Kategori Disabilitas tidak dibatasi usia.
    */
    'usia' => [
        'anak_maks' => 18,
        'lansia_min' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Dokumen Instansi Panti
    |--------------------------------------------------------------------------
    */
    'kategori_dokumen_instansi' => [
        'sk_pendirian' => 'SK Pendirian',
        'akta_pendirian' => 'Akta Pendirian',
        'akta_notaris' => 'Akta Notaris',
        'izin_operasional' => 'Izin Operasional',
        'akreditasi' => 'Akreditasi Lembaga',
        'npwp' => 'NPWP Lembaga',
        'sk_kemenkumham' => 'SK Kemenkumham',
        'struktur_organisasi' => 'Struktur Organisasi',
        'struktur_pengurus' => 'Struktur Pengurus',
        'foto_gedung' => 'Foto Gedung/Panti',
        'foto_panti' => 'Foto Panti',
        'lainnya' => 'Lainnya',
    ],

    // Dokumen wajib yang harus ada dan belum kedaluwarsa sebelum boleh diajukan validasi
    'dokumen_instansi_wajib' => [
        'akta_pendirian', // atau sk_pendirian / akta_notaris
        'izin_operasional',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Unggah Berkas
    |--------------------------------------------------------------------------
    */
    'dokumen' => [
        'disk' => 'local', // Disk privat untuk keamanan berkas
        'maks_kb' => 5120, // 5 MB
        'mimes' => 'pdf,jpg,jpeg,png,doc,docx',
        'mimetypes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'maks_per_kategori' => 10,
    ],
];
