<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blokir Akun
    |--------------------------------------------------------------------------
    | Akun (selain admin) diblokir setelah maks_gagal kali salah password berturut-turut.
    | Hitungan kembali ke nol bila percobaan gagal terakhir sudah lebih dari reset_setelah_jam jam lalu.
    | Hanya admin yang bisa membuka blokir. Akun admin tidak pernah diblokir.
    */
    'kunci_akun' => [
        'aktif' => (bool) env('KUNCI_AKUN_AKTIF', true),
        'maks_gagal' => (int) env('LOGIN_MAKS_GAGAL', 5),
        'reset_setelah_jam' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kontak Administrator
    |--------------------------------------------------------------------------
    | Tampil di halaman login dan pada pesan akun terblokir, supaya pengguna tahu
    | ke mana harus meminta bantuan. Isi lewat .env.
    */
    'kontak_admin' => [
        'kantor' => env('ADMIN_KANTOR', 'Kantor Dinas Sosial Kabupaten Minahasa'),
        'wa' => env('ADMIN_WA'),
        'telepon' => env('ADMIN_TELEPON'),
        'jam' => env('ADMIN_JAM_LAYANAN', 'Senin sampai Jumat, jam kerja'),
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
