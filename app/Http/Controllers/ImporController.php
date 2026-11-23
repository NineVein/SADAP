<?php

namespace App\Http\Controllers;

use App\Models\Anak;
use App\Models\Panti;
use App\Services\ImporService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Impor data LKS dan klien dari CSV. Admin dan operator (operator terbatas pada wilayahnya).
 * Hasil pratinjau disimpan terenkripsi di cache selama 30 menit, hanya bisa dibaca pembuatnya.
 */
class ImporController extends Controller
{
    private const TTL_MENIT = 30;

    public function __construct(private ImporService $service) {}

    public function index(Request $request): Response
    {
        $jenis = $request->query('jenis') === 'klien' ? 'klien' : 'lks';
        $token = (string) $request->query('token', '');

        $hasil = null;
        if ($token !== '') {
            $tersimpan = $this->ambil($request, $token);
            if ($tersimpan && $tersimpan['jenis'] === $jenis) {
                // Hanya sebagian baris dikirim ke browser; data lengkap tetap di server.
                $hasil = [
                    'token' => $token,
                    'ringkasan' => $tersimpan['hasil']['ringkasan'],
                    'baris' => array_map(
                        fn ($b) => ['baris' => $b['baris'], 'status' => $b['status'], 'ringkas' => $b['ringkas'], 'pesan' => $b['pesan']],
                        array_slice($tersimpan['hasil']['baris'], 0, 300)
                    ),
                    'terpotong' => count($tersimpan['hasil']['baris']) > 300,
                    'nama_berkas' => $tersimpan['nama_berkas'],
                ];
            }
        }

        return Inertia::render('Impor/Index', [
            'jenis' => $jenis,
            'hasil' => $hasil,
            'opsi' => [
                'kategoriLks' => Panti::KATEGORI_LKS,
                'latarBelakang' => collect(array_keys(Anak::KATEGORI_KLIEN))
                    ->mapWithKeys(fn ($k) => [$k => array_values(Anak::latarBelakang($k))])->all(),
                'pendidikan' => array_values(Anak::PENDIDIKAN),
                'ragam' => array_values(Anak::JENIS_DISABILITAS),
                'tingkat' => array_values(Anak::TINGKAT_KETERGANTUNGAN),
            ],
            'kolom' => [
                'lks' => ImporService::KOLOM_LKS,
                'klien' => ImporService::KOLOM_KLIEN,
            ],
            'maks' => ['baris' => ImporService::MAKS_BARIS, 'kb' => 2048],
            'isOperator' => $request->user()->isOperator(),
        ]);
    }

    public function templat(string $jenis): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print $this->service->templat($jenis),
            "templat-impor-{$jenis}.csv",
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function pratinjau(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:lks,klien'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Pilih berkas CSV terlebih dahulu.',
            'file.mimes' => 'Berkas harus berformat CSV. Dari Excel: Simpan Sebagai > CSV (pemisah koma atau titik koma).',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
        ]);

        $user = $request->user();

        try {
            $rows = $this->service->baca($request->file('file'), $data['jenis']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $hasil = $this->service->proses($data['jenis'], $rows, $user);

        $token = (string) Str::uuid();
        Cache::put(
            $this->kunci($user->id, $token),
            Crypt::encryptString(json_encode([
                'jenis' => $data['jenis'],
                'nama_berkas' => $request->file('file')->getClientOriginalName(),
                'hasil' => $hasil,
            ])),
            now()->addMinutes(self::TTL_MENIT)
        );

        return redirect()->route('impor.index', ['jenis' => $data['jenis'], 'token' => $token]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:lks,klien'],
            'token' => ['required', 'string'],
        ]);

        $tersimpan = $this->ambil($request, $data['token']);

        if (! $tersimpan || $tersimpan['jenis'] !== $data['jenis']) {
            return redirect()->route('impor.index', ['jenis' => $data['jenis']])
                ->with('error', 'Pratinjau sudah kedaluwarsa. Unggah ulang berkas Anda.');
        }

        if (($tersimpan['hasil']['ringkasan']['baru'] ?? 0) === 0) {
            return back()->with('error', 'Tidak ada baris baru yang bisa disimpan. Perbaiki berkas lalu unggah ulang.');
        }

        $hasil = $this->service->simpan($data['jenis'], $tersimpan['hasil'], $request->user());
        Cache::forget($this->kunci($request->user()->id, $data['token']));

        $tujuan = $data['jenis'] === 'lks' ? route('panti.index') : route('anak.index');

        return redirect($tujuan)->with(
            'success',
            "Impor selesai: {$hasil['dibuat']} baris disimpan, {$hasil['dilewati']} dilewati (duplikat atau galat)."
        );
    }

    private function kunci(int $userId, string $token): string
    {
        return "impor:{$userId}:{$token}";
    }

    private function ambil(Request $request, string $token): ?array
    {
        $isi = Cache::get($this->kunci($request->user()->id, $token));
        if (! $isi) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($isi), true);
        } catch (\Throwable) {
            return null;
        }
    }
}
