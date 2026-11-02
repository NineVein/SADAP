<?php

return [
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
