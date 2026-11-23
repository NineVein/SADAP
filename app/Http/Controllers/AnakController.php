<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Anak;
use App\Models\Panti;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Klien": klien LKS (Anak, Lanjut Usia, Disabilitas).
 * Nama kelas tetap Anak/AnakController agar data, relasi, dan tes yang ada tidak rusak.
 * Kategori klien SELALU mengikuti kategori LKS tempat klien tinggal.
 */
class AnakController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $kategori = $request->query('kategori');
        $jenis = $request->query('jenis');
        $pantiId = (int) $request->query('panti_id');

        $jenisValid = array_key_exists((string) $jenis, Anak::KATEGORI_KLIEN) ? $jenis : null;

        $anaks = Anak::query()
            ->visibleTo($user)
            ->with('panti:id,nama,kategori_lks')
            ->when($jenisValid, fn ($q) => $q->where('kategori_klien', $jenisValid))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                // NIK tersimpan terenkripsi: hanya bisa dicari dengan 16 digit lengkap (lewat hash).
                $digit = preg_replace('/\D/', '', $search);
                $query->where(function ($q) use ($like, $digit) {
                    $q->where('nama', 'like', $like);
                    if (strlen($digit) === 16) {
                        $q->orWhere('nik_hash', Anak::hashNik($digit));
                    }
                });
            })
            ->when(in_array($status, ['aktif', 'keluar'], true), fn ($q) => $q->where('status', $status))
            ->when(array_key_exists((string) $kategori, Anak::KATEGORI), fn ($q) => $q->where('kategori', $kategori))
            ->when($pantiId > 0, fn ($q) => $q->where('panti_id', $pantiId))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Anak $a) => [
                ...$a->only([
                    'id', 'panti_id', 'nama', 'jenis_kelamin', 'kategori', 'kategori_klien',
                    'jenis_disabilitas', 'tingkat_ketergantungan', 'pendidikan', 'status', 'umur',
                ]),
                'nik_tersamar' => Anak::samarkanNik($a->nik),
                'panti' => $a->panti?->only(['id', 'nama', 'kategori_lks']),
            ]);

        return Inertia::render('Anak/Index', [
            'anaks' => $anaks,
            'filters' => [
                'search' => $search,
                'status' => (string) $status,
                'kategori' => (string) $kategori,
                'jenis' => (string) ($jenisValid ?? ''),
                'panti_id' => $pantiId ?: '',
            ],
            'pantiOptions' => $this->pantiOptions($request, $jenisValid),
            'options' => $this->options(),
            'jenisCounts' => $this->jenisCounts($request),
            'can' => [
                'manage' => $user->isAdmin() || $user->isOperator(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isOperator(), 403);

        $jenis = $request->query('jenis');

        return Inertia::render('Anak/Form', [
            'anak' => null,
            'defaultPantiId' => (int) $request->query('panti_id') ?: null,
            'defaultJenis' => array_key_exists((string) $jenis, Anak::KATEGORI_KLIEN) ? $jenis : null,
            'pantiOptions' => $this->pantiOptions($request),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $panti = $this->pantiDariRequest($request);
        Gate::authorize('manageData', $panti);

        $data = $request->validate($this->rules($panti->kategori_lks));
        $data['kategori_klien'] = $panti->kategori_lks;

        $anak = new Anak($this->normalize($data));
        $anak->created_by = $request->user()->id;
        $anak->save();

        ActivityLog::record('anak.dibuat', "Menambahkan klien {$anak->nama} ({$anak->kategori_klien}) di LKS {$panti->nama}", $anak);

        return $this->selesai($request, $anak, 'Data klien berhasil ditambahkan.');
    }

    public function edit(Request $request, Anak $anak): Response
    {
        Gate::authorize('manageData', $anak->panti);

        return Inertia::render('Anak/Form', [
            'anak' => $anak->only([
                'id', 'panti_id', 'kategori_klien', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
                'kategori', 'jenis_disabilitas', 'tingkat_ketergantungan', 'pendidikan', 'nama_wali',
                'tanggal_masuk', 'tanggal_keluar', 'status', 'keterangan',
            ]),
            'defaultPantiId' => null,
            'defaultJenis' => $anak->kategori_klien,
            'pantiOptions' => $this->pantiOptions($request, $anak->kategori_klien),
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, Anak $anak): RedirectResponse
    {
        Gate::authorize('manageData', $anak->panti);

        $panti = $this->pantiDariRequest($request);
        Gate::authorize('manageData', $panti);

        // Klien tidak boleh dipindah ke LKS dengan kategori berbeda.
        if ($panti->kategori_lks !== $anak->kategori_klien) {
            throw ValidationException::withMessages([
                'panti_id' => 'Klien kategori '.(Anak::KATEGORI_KLIEN[$anak->kategori_klien] ?? $anak->kategori_klien)
                    .' hanya dapat berada di LKS kategori yang sama.',
            ]);
        }

        $data = $request->validate($this->rules($panti->kategori_lks, $anak));
        $data['kategori_klien'] = $panti->kategori_lks;

        $anak->update($this->normalize($data));

        ActivityLog::record('anak.diubah', "Memperbarui data klien {$anak->nama}", $anak);

        return $this->selesai($request, $anak, 'Data klien berhasil diperbarui.');
    }

    public function destroy(Anak $anak): RedirectResponse
    {
        Gate::authorize('manageData', $anak->panti);

        $anak->delete();

        ActivityLog::record('anak.dihapus', "Menghapus data klien {$anak->nama}", $anak);

        return back()->with('success', 'Data klien berhasil dihapus.');
    }

    /** Kembali ke tempat asal: detail LKS jika dibuka dari sana, kalau tidak ke daftar klien. */
    private function selesai(Request $request, Anak $anak, string $pesan): RedirectResponse
    {
        $tujuan = $request->input('dari') === 'panti'
            ? route('panti.show', [$anak->panti_id, 'tab' => 'anak'])
            : route('anak.index', ['panti_id' => $anak->panti_id]);

        return redirect($tujuan)->with('success', $pesan);
    }

    /** LKS tujuan menentukan kategori klien, jadi harus diketahui sebelum aturan lain disusun. */
    private function pantiDariRequest(Request $request): Panti
    {
        $request->validate(
            ['panti_id' => ['required', 'exists:pantis,id']],
            ['panti_id.required' => 'Pilih LKS tempat klien tinggal.', 'panti_id.exists' => 'LKS yang dipilih tidak valid.']
        );

        return Panti::findOrFail($request->input('panti_id'));
    }

    private function rules(string $kategori, ?Anak $anak = null): array
    {
        return [
            'panti_id' => ['required', 'exists:pantis,id'],
            'nik' => [
                'nullable', 'digits:16',
                // NIK terenkripsi tidak bisa diperiksa dengan aturan unique; dibandingkan lewat hash.
                function (string $attr, mixed $value, \Closure $fail) use ($anak) {
                    if (blank($value)) {
                        return;
                    }

                    $ada = Anak::where('nik_hash', Anak::hashNik((string) $value))
                        ->when($anak, fn ($q) => $q->whereKeyNot($anak->id))
                        ->exists();

                    if ($ada) {
                        $fail('NIK ini sudah terdaftar di sistem.');
                    }
                },
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['bail', 'required', 'date', 'before_or_equal:today', $this->aturanUsia($kategori)],
            'kategori' => ['required', Rule::in(array_keys(Anak::latarBelakang($kategori)))],
            'pendidikan' => ['nullable', Rule::in(array_keys(Anak::PENDIDIKAN))],
            'jenis_disabilitas' => $kategori === 'disabilitas'
                ? ['required', Rule::in(array_keys(Anak::JENIS_DISABILITAS))]
                : ['nullable'],
            'tingkat_ketergantungan' => in_array($kategori, ['lanjut_usia', 'disabilitas'], true)
                ? ['required', Rule::in(array_keys(Anak::TINGKAT_KETERGANTUNGAN))]
                : ['nullable'],
            'nama_wali' => ['nullable', 'string', 'max:255'],
            'tanggal_masuk' => ['nullable', 'date', 'before_or_equal:today'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'status' => ['required', Rule::in(['aktif', 'keluar'])],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Batas usia per kategori; nilainya ada di config/diskat.php. */
    private function aturanUsia(string $kategori): \Closure
    {
        return function (string $attr, mixed $value, \Closure $fail) use ($kategori) {
            try {
                $umur = Carbon::parse($value)->age;
            } catch (\Throwable) {
                return;
            }

            $anakMaks = (int) config('diskat.usia.anak_maks', 18);
            $lansiaMin = (int) config('diskat.usia.lansia_min', 60);

            if ($kategori === 'anak' && $umur > $anakMaks) {
                $fail("Klien kategori Anak berusia maksimal {$anakMaks} tahun (usia dari tanggal lahir ini {$umur} tahun). Gunakan LKS Lanjut Usia atau Disabilitas bila sesuai.");
            }

            if ($kategori === 'lanjut_usia' && $umur < $lansiaMin) {
                $fail("Klien kategori Lanjut Usia berusia minimal {$lansiaMin} tahun (usia dari tanggal lahir ini {$umur} tahun).");
            }
        };
    }

    /** Hapus isian yang tidak relevan untuk kategori terpilih agar data tetap bersih. */
    private function normalize(array $data): array
    {
        if ($data['status'] === 'aktif') {
            $data['tanggal_keluar'] = null;
        }

        if ($data['kategori_klien'] !== 'anak') {
            $data['pendidikan'] = null;
        }

        if ($data['kategori_klien'] !== 'disabilitas') {
            $data['jenis_disabilitas'] = null;
        }

        if ($data['kategori_klien'] === 'anak') {
            $data['tingkat_ketergantungan'] = null;
        }

        return $data;
    }

    private function options(): array
    {
        return [
            'kategori' => Anak::KATEGORI,
            'latarBelakang' => collect(array_keys(Anak::KATEGORI_KLIEN))
                ->mapWithKeys(fn ($k) => [$k => Anak::latarBelakang($k)])
                ->all(),
            'kategoriKlien' => Anak::KATEGORI_KLIEN,
            'pendidikan' => Anak::PENDIDIKAN,
            'jenisDisabilitas' => Anak::JENIS_DISABILITAS,
            'tingkat' => Anak::TINGKAT_KETERGANTUNGAN,
            'usia' => [
                'anak_maks' => (int) config('diskat.usia.anak_maks', 18),
                'lansia_min' => (int) config('diskat.usia.lansia_min', 60),
            ],
        ];
    }

    private function jenisCounts(Request $request): array
    {
        $rows = Anak::query()
            ->visibleTo($request->user())
            ->selectRaw('kategori_klien, count(*) as total')
            ->groupBy('kategori_klien')
            ->pluck('total', 'kategori_klien');

        $hasil = ['semua' => (int) $rows->sum()];
        foreach (array_keys(Anak::KATEGORI_KLIEN) as $k) {
            $hasil[$k] = (int) ($rows[$k] ?? 0);
        }

        return $hasil;
    }

    private function pantiOptions(Request $request, ?string $jenis = null)
    {
        return Panti::visibleTo($request->user())
            ->kategori($jenis)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kategori_lks']);
    }
}
