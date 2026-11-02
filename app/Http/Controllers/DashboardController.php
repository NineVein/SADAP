<?php

namespace App\Http\Controllers;

use App\Models\Anak;
use App\Models\DesilLaporan;
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
        ];

        $pantiTerbaru = Panti::query()
            ->visibleTo($user)
            ->latest()
            ->limit(5)
            ->get(['id', 'nama', 'alamat', 'status', 'validation_status', 'created_at']);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'tugas' => $this->tugas($user, $stats),
            'pantiTerbaru' => $pantiTerbaru,
        ]);
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
            $tambah($stats['diajukan'], 'Pengajuan validasi menunggu', 'Periksa kelengkapan data panti dari kelurahan/desa.', '/panti?validasi=diajukan', 'gold');
            $tambah(
                DesilLaporan::visibleTo($user)->where('status', DesilLaporan::MENUNGGU)->count(),
                'Laporan desil menunggu persetujuan',
                'Setujui atau tolak laporan desil dari operator.',
                '/anak?desil=menunggu',
                'gold'
            );
        }

        if (! $user->isSupervisor()) {
            $tambah($stats['ditolak'], 'Pengajuan ditolak, perlu diperbaiki', 'Baca catatan reviewer, perbaiki, lalu ajukan ulang.', '/panti?validasi=ditolak', 'rose');

            $draft = Panti::query()
                ->visibleTo($user)
                ->where('validation_status', Panti::DRAFT)
                ->withCount(['pengurus', 'anaks', 'dokumens'])
                ->get();

            $siap = $draft->filter(fn (Panti $p) => $p->isLengkap())->count();
            $tambah($siap, 'Panti siap diajukan', 'Data sudah lengkap. Tinggal ajukan untuk divalidasi.', '/panti?validasi=draft', 'green');
            $tambah($draft->count() - $siap, 'Data panti belum lengkap', 'Buka panti dan ikuti daftar kelengkapan.', '/panti?validasi=draft', 'ink');
        }

        $tambah(
            Anak::visibleTo($user)->where('status', 'aktif')->whereNull('desil')->count(),
            'Anak belum punya desil',
            $user->isSupervisor() ? 'Isi desil langsung atau minta operator melapor.' : 'Laporkan desil anak yang belum terdata.',
            '/anak?desil=belum',
            'ink'
        );

        if ($user->isAdmin()) {
            $tambah(Panti::whereNull('kelurahan_id')->count(), 'Panti belum punya wilayah', 'Tentukan kelurahan/desa agar bisa dikelola operator.', '/panti?tanpa_wilayah=1', 'rose');
        }

        return $tugas;
    }
}
