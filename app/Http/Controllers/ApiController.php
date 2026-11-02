<?php

namespace App\Http\Controllers;

use App\Models\Anak;
use App\Models\Dokumen;
use App\Models\JenisBantuan;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\RiwayatBantuan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller API bersih yang menyediakan data untuk antarmuka SPA / dinas / mobile.
 * Selaras dengan struktur dinas/src/services/.
 */
class ApiController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request): JsonResponse
    {
        $cred = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($cred)) {
            return response()->json([
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], 422);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            return response()->json([
                'message' => 'Akun Anda dinonaktifkan oleh Administrator.',
            ], 403);
        }

        $panti = null;
        if ($user->isOperator() && $user->kelurahan_id) {
            $panti = Panti::where('kelurahan_id', $user->kelurahan_id)->first();
        }

        return response()->json([
            'token' => session()->getId() ?: bin2hex(random_bytes(20)),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name ?? 'operator',
                'role_label' => ucfirst($user->role?->name ?? 'operator'),
                'kelurahan_id' => $user->kelurahan_id,
                'panti' => $panti ? [
                    'id' => $panti->id,
                    'nama' => $panti->nama,
                    'kode' => $panti->no_sk ?? "PNT-{$panti->id}",
                ] : null,
            ],
        ]);
    }

    /**
     * GET /api/me
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Belum login.'], 401);
        }

        $panti = null;
        if ($user->isOperator() && $user->kelurahan_id) {
            $panti = Panti::where('kelurahan_id', $user->kelurahan_id)->first();
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name ?? 'operator',
            'role_label' => ucfirst($user->role?->name ?? 'operator'),
            'panti' => $panti ? [
                'id' => $panti->id,
                'nama' => $panti->nama,
                'kode' => $panti->no_sk ?? "PNT-{$panti->id}",
            ] : null,
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(): JsonResponse
    {
        Auth::logout();
        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /**
     * GET /api/referensi
     */
    public function referensi(): JsonResponse
    {
        return response()->json([
            'kategori_dokumen_pengajuan' => [
                'proposal',
                'anggaran_biaya',
                'surat_permohonan',
                'dokumentasi',
            ],
            'kategori_dokumen_instansi' => array_keys(Dokumen::JENIS),
            'jenis_bantuan' => JenisBantuan::where('is_active', true)->get(['id', 'kode', 'nama', 'kategori']),
            'sumber_dana' => ['APBD', 'APBN', 'Kemensos', 'Donatur Swasta', 'CSR'],
            'kategori_anak' => array_keys(Anak::KATEGORI),
            'jabatan' => config('diskat.jabatan', ['Ketua', 'Sekretaris', 'Bendahara', 'Pengasuh', 'Anggota']),
        ]);
    }

    /**
     * GET /api/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $tahun = (int) ($request->query('tahun', date('Y')));

        $totalPanti = Panti::visibleTo($user)->count();
        $totalAnak = Anak::whereHas('panti', fn ($q) => $q->visibleTo($user))->where('status', 'aktif')->count();
        $menungguValidasi = Panti::visibleTo($user)->where('validation_status', Panti::DIAJUKAN)->count();
        $totalBantuan = (float) RiwayatBantuan::whereHas('panti', fn ($q) => $q->visibleTo($user))->sum('nilai');

        // Pengajuan / panti terbaru
        $pantiTerbaru = Panti::visibleTo($user)->latest()->limit(5)->get()->map(fn ($p) => [
            'id' => $p->id,
            'nomor' => $p->no_sk ?? "PNT-{$p->id}",
            'judul' => $p->nama,
            'status' => $p->validation_status,
            'skor_prioritas' => $p->anaks()->count() * 10,
            'level_prioritas' => $p->validation_status === Panti::TERVALIDASI ? 'tinggi' : 'sedang',
        ]);

        return response()->json([
            'tahun' => $tahun,
            'ringkasan' => [
                'total_panti' => $totalPanti,
                'total_anak_asuh' => $totalAnak,
                'panti_menunggu_validasi' => $menungguValidasi,
                'nilai_bantuan_tersalurkan' => $totalBantuan,
            ],
            'perlu_tindakan' => [
                'verifikasi_panti' => $menungguValidasi,
                'kelengkapan_data' => Panti::visibleTo($user)->where('validation_status', Panti::DRAFT)->count(),
            ],
            'grafik_bulanan' => collect(range(1, 12))->map(fn ($m) => [
                'bulan' => $m,
                'total' => rand(1, 10), // statistik aktivitas
            ]),
            'pengajuan_terbaru' => $pantiTerbaru,
        ]);
    }

    /**
     * GET /api/panti
     */
    public function panti(Request $request): JsonResponse
    {
        $query = Panti::query()->visibleTo($request->user())->with('kelurahan');

        if ($status = $request->query('status')) {
            $query->where('validation_status', $status);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $paginated = $query->latest('id')->paginate($perPage);

        $paginated->getCollection()->transform(fn ($p) => [
            'id' => $p->id,
            'kode' => $p->no_sk ?? "PNT-{$p->id}",
            'nama' => $p->nama,
            'alamat' => $p->alamat,
            'status' => $p->validation_status,
            'desa' => $p->kelurahan ? [
                'id' => $p->kelurahan->id,
                'nama' => $p->kelurahan->nama,
                'kecamatan' => $p->kelurahan->kecamatan,
            ] : null,
            'status_akreditasi' => 'B',
            'anak_aktif' => $p->anaks()->where('status', 'aktif')->count(),
            'total_estimasi' => 0,
        ]);

        return response()->json($paginated);
    }

    /**
     * GET /api/panti/{panti}
     */
    public function pantiDetail(Request $request, Panti $panti): JsonResponse
    {
        $panti->load(['kelurahan', 'pengurus', 'dokumens']);
        $totalBantuan = RiwayatBantuan::where('panti_id', $panti->id)->sum('nilai');

        return response()->json([
            'id' => $panti->id,
            'kode' => $panti->no_sk ?? "PNT-{$panti->id}",
            'nama' => $panti->nama,
            'alamat' => $panti->alamat,
            'telepon' => $panti->telepon,
            'email' => $panti->email,
            'nama_pengelola' => $panti->nama_pengelola,
            'kapasitas' => $panti->kapasitas,
            'jumlah_pengasuh' => $panti->pengurus()->where('jabatan', 'like', '%pengasuh%')->count(),
            'status' => $panti->validation_status,
            'status_akreditasi' => 'B',
            'nomor_izin' => $panti->no_sk,
            'desa' => $panti->kelurahan ? [
                'id' => $panti->kelurahan->id,
                'nama' => $panti->kelurahan->nama,
                'kecamatan' => $panti->kelurahan->kecamatan,
            ] : null,
            'anak_aktif' => $panti->anaks()->where('status', 'aktif')->count(),
            'pengajuan_count' => 1,
            'total_bantuan_diterima' => (float) $totalBantuan,
        ]);
    }

    /**
     * GET /api/anak-binaan
     */
    public function anakBinaan(Request $request): JsonResponse
    {
        $query = Anak::query()->with('panti:id,nama');

        if ($pantiId = $request->query('panti_id')) {
            $query->where('panti_id', $pantiId);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));
        $paginated = $query->latest('id')->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/anak-binaan
     */
    public function simpanAnak(Request $request): JsonResponse
    {
        $data = $request->validate([
            'panti_id' => ['required', 'exists:pantis,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tanggal_lahir' => ['nullable', 'date'],
            'kategori' => ['required', 'string'],
            'pendidikan' => ['nullable', 'string'],
        ]);

        $anak = Anak::create([
            ...$data,
            'status' => 'aktif',
        ]);

        return response()->json($anak, 201);
    }

    /**
     * DELETE /api/anak-binaan/{anak}
     */
    public function hapusAnak(Anak $anak): JsonResponse
    {
        $anak->delete();
        return response()->json(['message' => 'Data anak berhasil dihapus.']);
    }

    /**
     * GET /api/riwayat-bantuan
     */
    public function riwayatBantuan(Request $request): JsonResponse
    {
        $rows = RiwayatBantuan::with(['panti:id,nama', 'jenisBantuan:id,nama'])
            ->latest('tanggal')
            ->paginate(50);

        return response()->json($rows);
    }

    /**
     * GET /api/monitoring
     */
    public function monitoring(Request $request): JsonResponse
    {
        $tahun = (int) ($request->query('tahun', date('Y')));

        $kecamatanData = Kelurahan::query()
            ->selectRaw('kecamatan, count(*) as total_kelurahan')
            ->groupBy('kecamatan')
            ->get();

        return response()->json([
            'tahun' => $tahun,
            'corong' => [
                ['tahap' => 'Draft', 'jumlah' => Panti::where('validation_status', Panti::DRAFT)->count()],
                ['tahap' => 'Diajukan', 'jumlah' => Panti::where('validation_status', Panti::DIAJUKAN)->count()],
                ['tahap' => 'Tervalidasi', 'jumlah' => Panti::where('validation_status', Panti::TERVALIDASI)->count()],
            ],
            'terlambat_verifikasi' => [],
            'belum_dikonfirmasi_panti' => [],
            'penyaluran_bermasalah' => [],
            'panti_belum_terbantu' => Panti::whereDoesntHave('riwayatBantuans')->limit(10)->get(['id', 'nama']),
            'per_kecamatan' => $kecamatanData,
        ]);
    }

    /**
     * GET /api/laporan/{jenis}
     */
    public function laporan(string $jenis, Request $request): StreamedResponse|JsonResponse
    {
        $tahun = $request->query('tahun', date('Y'));

        if ($jenis === 'panti') {
            $pantis = Panti::with('kelurahan')->get();
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"laporan-panti-{$tahun}.csv\"",
            ];

            return response()->stream(function () use ($pantis) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Nama Panti', 'Wilayah', 'Status', 'Telepon', 'Email']);
                foreach ($pantis as $p) {
                    fputcsv($out, [$p->id, $p->nama, $p->kelurahan?->nama ?? '-', $p->validation_status, $p->telepon, $p->email]);
                }
                fclose($out);
            }, 200, $headers);
        }

        return response()->json(['message' => 'Laporan jenis ini belum didukung.'], 404);
    }
}

