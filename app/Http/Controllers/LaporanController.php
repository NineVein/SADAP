<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Anak;
use App\Models\Panti;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $pantis = Panti::query()
            ->visibleTo($user)
            ->with('kelurahan:id,nama,kecamatan')
            ->withCount([
                'anaks as anak_aktif' => fn ($q) => $q->where('status', 'aktif'),
                'anaks as anak_laki' => fn ($q) => $q->where('status', 'aktif')->where('jenis_kelamin', 'L'),
                'anaks as anak_perempuan' => fn ($q) => $q->where('status', 'aktif')->where('jenis_kelamin', 'P'),
                'anaks as klien_anak' => fn ($q) => $q->where('status', 'aktif')->where('kategori_klien', 'anak'),
                'anaks as klien_lansia' => fn ($q) => $q->where('status', 'aktif')->where('kategori_klien', 'lanjut_usia'),
                'anaks as klien_disabilitas' => fn ($q) => $q->where('status', 'aktif')->where('kategori_klien', 'disabilitas'),
            ])
            ->get();

        $perWilayah = $pantis
            ->groupBy(fn (Panti $p) => $p->kelurahan_id ?? 0)
            ->map(function ($group) {
                $kel = $group->first()->kelurahan;

                return [
                    'kelurahan' => $kel?->nama ?? 'Belum ditentukan',
                    'kecamatan' => $kel?->kecamatan ?? '-',
                    'panti' => $group->count(),
                    'panti_aktif' => $group->where('status', 'aktif')->count(),
                    'lks_anak' => $group->where('kategori_lks', 'anak')->count(),
                    'lks_lansia' => $group->where('kategori_lks', 'lanjut_usia')->count(),
                    'lks_disabilitas' => $group->where('kategori_lks', 'disabilitas')->count(),
                    'klien_anak' => (int) $group->sum('klien_anak'),
                    'klien_lansia' => (int) $group->sum('klien_lansia'),
                    'klien_disabilitas' => (int) $group->sum('klien_disabilitas'),
                    'tervalidasi' => $group->where('validation_status', Panti::TERVALIDASI)->count(),
                    'anak' => (int) $group->sum('anak_aktif'),
                    'laki' => (int) $group->sum('anak_laki'),
                    'perempuan' => (int) $group->sum('anak_perempuan'),
                ];
            })
            ->sortBy([['kecamatan', 'asc'], ['kelurahan', 'asc']])
            ->values();

        $anakAktif = Anak::visibleTo($user)->where('status', 'aktif');

        return Inertia::render('Laporan/Index', [
            'ringkasan' => [
                'panti' => $pantis->count(),
                'panti_aktif' => $pantis->where('status', 'aktif')->count(),
                'tervalidasi' => $pantis->where('validation_status', Panti::TERVALIDASI)->count(),
                'anak' => (int) $pantis->sum('anak_aktif'),
                'laki' => (int) $pantis->sum('anak_laki'),
                'perempuan' => (int) $pantis->sum('anak_perempuan'),
                'lks_anak' => $pantis->where('kategori_lks', 'anak')->count(),
                'lks_lansia' => $pantis->where('kategori_lks', 'lanjut_usia')->count(),
                'lks_disabilitas' => $pantis->where('kategori_lks', 'disabilitas')->count(),
                'klien_anak' => (int) $pantis->sum('klien_anak'),
                'klien_lansia' => (int) $pantis->sum('klien_lansia'),
                'klien_disabilitas' => (int) $pantis->sum('klien_disabilitas'),
            ],
            'perWilayah' => $perWilayah,
            'perKategori' => (clone $anakAktif)->selectRaw('kategori, count(*) as total')
                ->groupBy('kategori')->pluck('total', 'kategori'),
            'perPendidikan' => (clone $anakAktif)->where('kategori_klien', 'anak')->selectRaw('pendidikan, count(*) as total')
                ->groupBy('pendidikan')->pluck('total', 'pendidikan'),
            'options' => ['kategori' => Anak::KATEGORI, 'pendidikan' => Anak::PENDIDIKAN],
            'dicetak' => now()->toIso8601String(),
        ]);
    }

    /** Ekspor CSV (pemisah titik koma, dibuka rapi di Excel versi Indonesia). */
    public function export(Request $request, string $jenis): StreamedResponse
    {
        $jenis = match ($jenis) {
            'panti' => 'lks',
            'anak' => 'klien',
            default => $jenis,
        };
        $user = $request->user();

        ActivityLog::record('laporan.ekspor', "Mengekspor data {$jenis} ke CSV");

        $filename = "diskat-{$jenis}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($jenis, $user) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar karakter UTF-8 terbaca di Excel

            $write = fn (array $row) => fputcsv($out, array_map($this->safe(...), $row), ';', '"', '\\');

            if ($jenis === 'lks') {
                $write(['Nama LKS', 'Kategori LKS', 'Alamat', 'Kelurahan/Desa', 'Kecamatan', 'No. SK', 'Tanggal Berdiri',
                    'Telepon', 'Email', 'Pengelola', 'Kapasitas', 'Status', 'Validasi', 'Klien Aktif']);

                Panti::query()->visibleTo($user)->with('kelurahan:id,nama,kecamatan')
                    ->withCount(['anaks as anak_aktif' => fn ($q) => $q->where('status', 'aktif')])
                    ->lazyById(500)
                    ->each(fn (Panti $p) => $write([
                        $p->nama, Panti::KATEGORI_LKS[$p->kategori_lks] ?? $p->kategori_lks, $p->alamat, $p->kelurahan?->nama, $p->kelurahan?->kecamatan, $p->no_sk,
                        $p->tanggal_berdiri?->format('Y-m-d'), $p->telepon, $p->email, $p->nama_pengelola,
                        $p->kapasitas, $p->status, $p->validation_status, $p->anak_aktif,
                    ]));
            } else {
                // NIK sengaja tidak diekspor: data pribadi klien.
                $write(['Nama', 'Kategori Klien', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Umur', 'Kategori',
                    'Jenis Disabilitas', 'Tingkat Ketergantungan', 'Pendidikan', 'Wali/Keluarga', 'LKS', 'Kelurahan/Desa', 'Tanggal Masuk', 'Status']);

                Anak::query()->visibleTo($user)->with('panti.kelurahan:id,nama')
                    ->lazyById(500)
                    ->each(fn (Anak $a) => $write([
                        $a->nama, Anak::KATEGORI_KLIEN[$a->kategori_klien] ?? $a->kategori_klien, $a->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan', $a->tempat_lahir,
                        $a->tanggal_lahir?->format('Y-m-d'), $a->umur,
                        Anak::KATEGORI[$a->kategori] ?? $a->kategori,
                        Anak::JENIS_DISABILITAS[$a->jenis_disabilitas] ?? $a->jenis_disabilitas,
                        Anak::TINGKAT_KETERGANTUNGAN[$a->tingkat_ketergantungan] ?? $a->tingkat_ketergantungan,
                        Anak::PENDIDIKAN[$a->pendidikan] ?? $a->pendidikan,
                        $a->nama_wali, $a->panti?->nama, $a->panti?->kelurahan?->nama,
                        $a->tanggal_masuk?->format('Y-m-d'), $a->status,
                    ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Cegah CSV injection: sel yang diawali = + - @ dianggap rumus oleh Excel. */
    private function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
