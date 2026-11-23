<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CekProduksi extends Command
{
    protected $signature = 'sistem:cek-produksi';

    protected $description = 'Memeriksa kesiapan keamanan dan konfigurasi sebelum sistem dipakai dengan data nyata';

    /** @var array<int, array{0:string,1:string,2:string,3:string}> */
    private array $hasil = [];

    public function handle(): int
    {
        $this->konfigurasi();
        $this->akun();
        $this->penyimpananDanBackup();

        $this->table(
            ['Status', 'Pemeriksaan', 'Saran'],
            array_map(fn ($r) => [match ($r[0]) {
                'ok' => '<fg=green>OK</>',
                'peringatan' => '<fg=yellow>PERIKSA</>',
                default => '<fg=red>GAGAL</>',
            }, $r[1], $r[2]], $this->hasil)
        );

        $gagal = count(array_filter($this->hasil, fn ($r) => $r[0] === 'gagal'));
        $peringatan = count(array_filter($this->hasil, fn ($r) => $r[0] === 'peringatan'));

        if ($gagal > 0) {
            $this->error("{$gagal} pemeriksaan GAGAL dan {$peringatan} perlu diperiksa. Perbaiki sebelum memasukkan data nyata.");

            return self::FAILURE;
        }

        $this->info($peringatan > 0
            ? "Tidak ada yang gagal, tetapi {$peringatan} hal perlu Anda periksa."
            : 'Semua pemeriksaan lolos.');

        return self::SUCCESS;
    }

    private function catat(string $status, string $label, string $saran = ''): void
    {
        $this->hasil[] = [$status, $label, $saran];
    }

    private function cek(bool $lulus, string $label, string $saran, string $kalauTidak = 'gagal'): void
    {
        $this->catat($lulus ? 'ok' : $kalauTidak, $label, $lulus ? '' : $saran);
    }

    private function konfigurasi(): void
    {
        $this->cek(app()->environment('production'), 'APP_ENV = production', 'Set APP_ENV=production di .env.');
        $this->cek(! config('app.debug'), 'APP_DEBUG = false', 'APP_DEBUG=true membocorkan isi kode, kunci, dan data saat galat. Set false.');
        $this->cek(filled(config('app.key')), 'APP_KEY terisi', 'Jalankan php artisan key:generate (sekali saja; jangan diganti setelah ada data terenkripsi).');
        $this->cek(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL memakai https://', 'Pasang sertifikat HTTPS dan ubah APP_URL.');
        $this->cek(config('session.secure') === true, 'SESSION_SECURE_COOKIE = true', 'Set SESSION_SECURE_COOKIE=true agar cookie sesi hanya lewat HTTPS.');
        $this->cek((int) config('session.lifetime') <= 60, 'Masa sesi 60 menit atau kurang', 'Turunkan SESSION_LIFETIME (data pribadi klien).', 'peringatan');
        $this->cek((bool) config('session.encrypt'), 'SESSION_ENCRYPT = true', 'Enkripsi isi sesi di database.', 'peringatan');
        $this->cek(in_array(config('session.same_site'), ['lax', 'strict'], true), 'Cookie SameSite lax/strict', 'Set SESSION_SAME_SITE=lax.', 'peringatan');
        $this->cek(config('logging.channels.'.config('logging.default').'.level', 'debug') !== 'debug' || config('app.env') !== 'production', 'LOG_LEVEL bukan debug', 'Gunakan LOG_LEVEL=warning agar log tidak memuat data pribadi.', 'peringatan');
        $this->cek(config('database.default') !== 'sqlite', 'Database produksi (bukan SQLite)', 'Gunakan MySQL/MariaDB atau PostgreSQL untuk banyak pengguna.', 'peringatan');
        $this->cek(config('cache.default') !== 'array', 'Cache bukan driver array', 'Pakai database, file, atau redis. Driver array hilang tiap permintaan dan merusak pembatas percobaan login.');
        $this->cek((bool) config('diskat.two_factor.wajib'), 'Verifikasi dua langkah (2FA) diwajibkan', 'Set TWO_FACTOR_WAJIB=true di .env.');
        $this->cek(
            ! in_array(config('mail.default'), ['log', 'array'], true) && filled(config('mail.from.address')) && ! str_contains((string) config('mail.from.address'), 'example.com'),
            'Email terkirim sungguhan (OTP 2FA lewat email)',
            'MAIL_MAILER masih log/array atau MAIL_FROM_ADDRESS belum diisi. Tanpa email yang jalan, pengguna ber-2FA tidak bisa masuk. Isi MAIL_* di .env.'
        );
    }

    private function akun(): void
    {
        $this->cek(
            User::where('is_active', true)->whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->exists(),
            'Ada minimal satu admin aktif',
            'Buat akun admin lewat php artisan tinker atau AdminSeeder.'
        );

        $demo = User::query()
            ->where(fn ($q) => $q->where('email', 'like', '%@admin.test')->orWhere('email', 'like', '%@example.%')->orWhere('email', 'like', '%@test.%'))
            ->pluck('email');
        $this->cek($demo->isEmpty(), 'Tidak ada akun demo', 'Hapus atau nonaktifkan: '.$demo->take(5)->implode(', '));

        $lemah = User::query()->limit(300)->get(['id', 'email', 'password'])
            ->filter(fn ($u) => Hash::check('password', $u->password) || Hash::check('admin', $u->password))
            ->pluck('email');
        $this->cek($lemah->isEmpty(), 'Tidak ada akun ber-password bawaan', 'Ganti password: '.$lemah->take(5)->implode(', '));

        $tanpa2fa = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('name', (array) config('diskat.two_factor.peran')))
            ->whereNull('two_factor_confirmed_at')
            ->count();
        $this->cek($tanpa2fa === 0, 'Admin dan supervisor sudah aktif 2FA', "{$tanpa2fa} akun belum mengaktifkan 2FA (diminta saat login berikutnya).", 'peringatan');
    }

    private function penyimpananDanBackup(): void
    {
        $dir = storage_path('app/private');
        if (! is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $this->cek(is_dir($dir) && is_writable($dir), 'Folder dokumen (storage/app/private) bisa ditulis', 'Beri izin tulis untuk user web server dan pastikan folder ini TIDAK bisa diakses dari URL.');

        if (! class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
            $this->catat('gagal', 'Paket backup terpasang', 'composer require spatie/laravel-backup (lihat docs/BACKUP-DAN-PEMULIHAN.md).');

            return;
        }

        $terbaru = null;
        try {
            foreach ((array) config('backup.backup.destination.disks', []) as $disk) {
                foreach (Storage::disk($disk)->allFiles((string) config('backup.backup.name')) as $f) {
                    if (str_ends_with($f, '.zip')) {
                        $terbaru = max($terbaru ?? 0, Storage::disk($disk)->lastModified($f));
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->catat('gagal', 'Backup dapat dibaca', 'Disk backup bermasalah: '.$e->getMessage());

            return;
        }

        if ($terbaru === null) {
            $this->catat('gagal', 'Sudah ada berkas backup', 'Belum ada backup. Jalankan php artisan backup:run lalu uji pulihkan.');

            return;
        }

        $jam = (int) round((time() - $terbaru) / 3600);
        $this->cek($jam <= 36, "Backup terakhir {$jam} jam lalu (maks. 36 jam)", 'Pastikan scheduler (cron schedule:run) berjalan tiap menit.');
    }
}
