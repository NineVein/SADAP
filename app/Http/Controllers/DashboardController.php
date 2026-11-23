<?php

namespace App\Http\Controllers;

use App\Models\Anak;
use App\Models\Panti;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $row = Panti::query()
            ->visibleTo($user)
            ->selectRaw("count(*) as total,
                sum(status = 'aktif') as aktif,
                sum(status = 'nonaktif') as nonaktif,
                sum(validation_status = 'diajukan') as diajukan,
                sum(validation_status = 'tervalidasi') as tervalidasi,
                sum(validation_status = 'ditolak') as ditolak")
            ->first();

        $stats = [
            'total' => (int) $row->total,
            'aktif' => (int) $row->aktif,
            'nonaktif' => (int) $row->nonaktif,
            'diajukan' => (int) $row->diajukan,
            'tervalidasi' => (int) $row->tervalidasi,
            'ditolak' => (int) $row->ditolak,
            'anak_aktif' => Anak::visibleTo($user)->where('status', 'aktif')->count(),
            // Rincian per kategori: LKS dan klien aktif (Anak, Lanjut Usia, Disabilitas).
            'per_kategori' => $this->perKategori($user),
        ];

        $pantiTerbaru = Panti::query()
            ->visibleTo($user)
            ->latest()
            ->limit(5)
            ->get(['id', 'nama', 'kategori_lks', 'alamat', 'status', 'validation_status', 'created_at']);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'tugas' => $this->tugas($user, $stats),
            'pantiTerbaru' => $pantiTerbaru,
        ]);
    }

    /** @return array<string, array{lks:int, klien:int}> */
    private function perKategori(User $user): array
    {
        $lks = Panti::query()->visibleTo($user)
            ->selectRaw('kategori_lks, count(*) as total')->groupBy('kategori_lks')
            ->pluck('total', 'kategori_lks');

        $klien = Anak::query()->visibleTo($user)->where('status', 'aktif')
            ->selectRaw('kategori_klien, count(*) as total')->groupBy('kategori_klien')
            ->pluck('total', 'kategori_klien');

        $hasil = [];
        foreach (array_keys(Panti::KATEGORI_LKS) as $k) {
            $hasil[$k] = ['lks' => (int) ($lks[$k] ?? 0), 'klien' => (int) ($klien[$k] ?? 0)];
        }

        return $hasil;
    }

    /**
     * Pusat tugas: semua hal yang menunggu tindakan user, dari semua modul,
     * dalam satu daftar dengan tautan langsung ke tempat mengerjakannya.
     */
    private function tugas(User $user, array $stats): array
    {
        $tugas = [];
        $tambah = function (int $jumlah, string $judul, string $deskripsi, string $href, string $tone) use (&$tugas) {
            if ($jumlah > 0) {
                $tugas[] = compact('jumlah', 'judul', 'deskripsi', 'href', 'tone');
            }
        };

        $isReviewer = $user->isAdmin() || $user->isSupervisor();

        if ($isReviewer) {
            $tambah($stats['diajukan'], 'Pengajuan validasi menunggu', 'Periksa kelengkapan data LKS dari kelurahan/desa.', '/lks?validasi=diajukan', 'gold');
        }

        if (! $user->isSupervisor()) {
            $tambah($stats['ditolak'], 'Pengajuan ditolak, perlu diperbaiki', 'Baca catatan reviewer, perbaiki, lalu ajukan ulang.', '/lks?validasi=ditolak', 'rose');

            $draft = Panti::query()
                ->visibleTo($user)
                ->where('validation_status', Panti::DRAFT)
                ->withCount(['pengurus', 'anaks', 'dokumens'])
                ->get();

            $siap = $draft->filter(fn (Panti $p) => $p->isLengkap())->count();
            $tambah($siap, 'LKS siap diajukan', 'Data sudah lengkap. Tinggal ajukan untuk divalidasi.', '/lks?validasi=draft', 'green');
            $tambah($draft->count() - $siap, 'Data LKS belum lengkap', 'Buka LKS dan ikuti daftar kelengkapan.', '/lks?validasi=draft', 'ink');
        }

        if ($user->isAdmin()) {
            $tambah(
                User::whereNotNull('terblokir_at')->count(),
                'Akun terblokir menunggu dibuka',
                'Pengguna salah password berkali-kali. Pastikan identitasnya, lalu buka blokir.',
                '/users',
                'rose'
            );
            $tambah(Panti::whereNull('kelurahan_id')->count(), 'LKS belum punya wilayah', 'Tentukan kelurahan/desa agar bisa dikelola operator.', '/lks?tanpa_wilayah=1', 'rose');
        }

        return $tugas;
    }
}
