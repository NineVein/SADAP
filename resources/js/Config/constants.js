// Konstanta sistem DISKAT / SADAP
// Sumber kebenaran tunggal untuk status, peran, kategori dokumen, dan konfigurasi panti.

export const ST = [
    'draft',
    'menunggu_verifikasi',
    'diajukan',
    'perlu_perbaikan',
    'ditolak',
    'terverifikasi',
    'tervalidasi',
    'ditetapkan',
    'dalam_penyaluran',
    'selesai',
];

export const STATUS_LABELS = {
    draft: 'Draft',
    menunggu_verifikasi: 'Menunggu Verifikasi',
    diajukan: 'Menunggu Validasi',
    perlu_perbaikan: 'Perlu Perbaikan',
    ditolak: 'Ditolak',
    terverifikasi: 'Terverifikasi',
    tervalidasi: 'Tervalidasi',
    ditetapkan: 'Ditetapkan',
    dalam_penyaluran: 'Dalam Penyaluran',
    selesai: 'Selesai',
    aktif: 'Aktif',
    nonaktif: 'Nonaktif',
    layak: 'Layak',
    bermasalah: 'Bermasalah',
    dikirim: 'Dikirim',
    diterima: 'Diterima',
};

export const JABATAN = [
    'Ketua',
    'Wakil Ketua',
    'Sekretaris',
    'Bendahara',
    'Pengasuh',
    'Staf Administrasi',
    'Anggota',
];

export const KATEGORI_DOK = [
    'akta_pendirian',
    'izin_operasional',
    'akreditasi',
    'npwp',
    'sk_kemenkumham',
    'struktur_organisasi',
    'foto_gedung',
    'lainnya',
];

export const WAJIB = ['akta_pendirian', 'izin_operasional'];

export const KATEGORI_ANAK = [
    'yatim',
    'piatu',
    'yatim_piatu',
    'terlantar',
    'disabilitas',
    'dhuafa',
];

export const MAKS_MB = 5;

export const EKSTENSI = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

export const TABS_PANTI = {
    profil: 'Profil',
    anak: 'Anak Binaan',
    pengurus: 'Pengurus',
    dokumen: 'Dokumen Instansi',
    jenis: 'Jenis Bantuan',
    aktivitas: 'Aktivitas',
};

export const SUMBER_DANA = [
    'APBD',
    'APBN',
    'Kemensos',
    'Donatur Swasta',
    'CSR',
];

