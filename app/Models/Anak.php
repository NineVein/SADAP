<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPanti;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anak extends Model
{
    use BelongsToPanti, SoftDeletes;

    /** Kategori klien = kategori LKS tempat ia tinggal. */
    public const KATEGORI_KLIEN = [
        'anak' => 'Anak',
        'lanjut_usia' => 'Lanjut Usia',
        'disabilitas' => 'Disabilitas',
    ];

    /** Seluruh latar belakang (untuk label). Pilihan per kategori ada di LATAR_BELAKANG. */
    public const KATEGORI = [
        'yatim' => 'Yatim',
        'piatu' => 'Piatu',
        'yatim_piatu' => 'Yatim piatu',
        'dhuafa' => 'Dhuafa',
        'terlantar' => 'Terlantar',
        'sebatang_kara' => 'Sebatang kara',
    ];

    /** Pilihan latar belakang sosial menurut kategori klien. Sesuaikan dengan klasifikasi Dinas Sosial. */
    public const LATAR_BELAKANG = [
        'anak' => ['yatim', 'piatu', 'yatim_piatu', 'dhuafa', 'terlantar'],
        'lanjut_usia' => ['terlantar', 'dhuafa', 'sebatang_kara'],
        'disabilitas' => ['terlantar', 'dhuafa', 'sebatang_kara'],
    ];

    /** Ragam disabilitas (mengacu pada UU 8/2016, dipecah agar lebih rinci). */
    public const JENIS_DISABILITAS = [
        'fisik' => 'Disabilitas fisik',
        'sensorik_netra' => 'Disabilitas sensorik (netra)',
        'sensorik_rungu_wicara' => 'Disabilitas sensorik (rungu/wicara)',
        'intelektual' => 'Disabilitas intelektual',
        'mental' => 'Disabilitas mental',
        'ganda' => 'Disabilitas ganda/multi',
    ];

    /** Tingkat bantuan perawatan harian, untuk lanjut usia dan disabilitas. */
    public const TINGKAT_KETERGANTUNGAN = [
        'mandiri' => 'Mandiri',
        'sebagian' => 'Perlu bantuan sebagian',
        'total' => 'Perlu bantuan total',
    ];

    public const PENDIDIKAN = [
        'belum_sekolah' => 'Belum sekolah',
        'paud' => 'PAUD/TK',
        'sd' => 'SD',
        'smp' => 'SMP',
        'sma' => 'SMA/SMK',
        'kuliah' => 'Perguruan tinggi',
        'putus_sekolah' => 'Putus sekolah',
    ];

    protected $fillable = [
        'panti_id', 'kategori_klien', 'jenis_disabilitas', 'tingkat_ketergantungan', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'kategori', 'pendidikan', 'nama_wali', 'tanggal_masuk', 'tanggal_keluar',
        'status', 'keterangan',
    ];

    protected $appends = ['umur'];

    /** Hash NIK tidak pernah ikut terkirim ke browser. */
    protected $hidden = ['nik_hash'];

    protected static function booted(): void
    {
        // nik_hash selalu mengikuti nik, dipakai untuk pencarian persis dan cek duplikat.
        static::saving(function (Anak $anak) {
            $anak->nik_hash = self::hashNik($anak->nik);
        });
    }

    /** HMAC-SHA256 dengan APP_KEY. Nilai kosong menghasilkan null. */
    public static function hashNik(?string $nik): ?string
    {
        $nik = preg_replace('/\D/', '', (string) $nik);

        return $nik === '' ? null : hash_hmac('sha256', $nik, (string) config('app.key'));
    }

    /** 7102••••••••1234, untuk daftar dan tampilan umum. NIK utuh hanya muncul di formulir edit. */
    public static function samarkanNik(?string $nik): ?string
    {
        $nik = (string) $nik;

        return $nik === '' ? null : substr($nik, 0, 4).str_repeat('•', max(strlen($nik) - 8, 0)).substr($nik, -4);
    }

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'tanggal_lahir' => 'date:Y-m-d',
            'tanggal_masuk' => 'date:Y-m-d',
            'tanggal_keluar' => 'date:Y-m-d',
        ];
    }

    /** Pilihan latar belakang [kode => label] untuk satu kategori klien. */
    public static function latarBelakang(string $kategoriKlien): array
    {
        $kode = self::LATAR_BELAKANG[$kategoriKlien] ?? [];

        return array_intersect_key(self::KATEGORI, array_flip($kode));
    }

    protected function umur(): Attribute
    {
        return Attribute::get(fn () => $this->tanggal_lahir?->age);
    }
}
