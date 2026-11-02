<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Anak;
use App\Models\DesilLaporan;
use App\Models\Panti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman "Anak & Desil": daftar anak sekaligus status dan laporan desilnya. */
class AnakController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isReviewer = $user->isAdmin() || $user->isSupervisor();

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $kategori = $request->query('kategori');
        $desil = $request->query('desil');
        $pantiId = (int) $request->query('panti_id');

        $anaks = Anak::query()
            ->visibleTo($user)
            ->with(['panti:id,nama', 'laporanTerakhir'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('nama', 'like', $like)->orWhere('nik', 'like', $like));
            })
            ->when(in_array($status, ['aktif', 'keluar'], true), fn ($q) => $q->where('status', $status))
            ->when(array_key_exists((string) $kategori, Anak::KATEGORI), fn ($q) => $q->where('kategori', $kategori))
            ->when($pantiId > 0, fn ($q) => $q->where('panti_id', $pantiId))
            ->when($desil === 'belum', fn ($q) => $q->whereNull('desil'))
            ->when($desil === 'ada', fn ($q) => $q->whereNotNull('desil'))
            ->when($desil === 'menunggu', fn ($q) => $q->whereHas(
                'desilLaporans',
                fn ($l) => $l->where('status', DesilLaporan::MENUNGGU)
            ))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Anak $a) => [
                ...$a->only(['id', 'panti_id', 'nama', 'nik', 'jenis_kelamin', 'kategori', 'pendidikan', 'status', 'desil', 'umur']),
                'panti' => $a->panti?->only(['id', 'nama']),
                'laporan' => $a->laporanRingkas(),
            ]);

        // Antrean persetujuan desil tampil di halaman ini juga, jadi reviewer tidak perlu menu lain.
        $desilMenunggu = $isReviewer
            ? DesilLaporan::query()
                ->visibleTo($user)
                ->where('status', DesilLaporan::MENUNGGU)
                ->with(['anak:id,nama,panti_id,desil', 'anak.panti:id,nama', 'pelapor:id,name'])
                ->oldest()
                ->limit(50)
                ->get()
            : [];

        return Inertia::render('Anak/Index', [
            'anaks' => $anaks,
            'filters' => [
                'search' => $search,
                'status' => (string) $status,
                'kategori' => (string) $kategori,
                'desil' => (string) $desil,
                'panti_id' => $pantiId ?: '',
            ],
            'pantiOptions' => $this->pantiOptions($request),
            'options' => [
                'kategori' => Anak::KATEGORI,
                'pendidikan' => Anak::PENDIDIKAN,
                'sumber' => DesilLaporan::SUMBER,
            ],
            'desilMenunggu' => $desilMenunggu,
            'counts' => [
                'belum_desil' => Anak::visibleTo($user)->where('status', 'aktif')->whereNull('desil')->count(),
            ],
            'can' => [
                'manage' => $user->isAdmin() || $user->isOperator(),
                'report' => true,
                'review' => $isReviewer,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isOperator(), 403);

        return Inertia::render('Anak/Form', [
            'anak' => null,
            'defaultPantiId' => (int) $request->query('panti_id') ?: null,
            'pantiOptions' => $this->pantiOptions($request),
            'options' => ['kategori' => Anak::KATEGORI, 'pendidikan' => Anak::PENDIDIKAN],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        Gate::authorize('manageData', Panti::findOrFail($data['panti_id']));

        $anak = new Anak($this->normalize($data));
        $anak->created_by = $request->user()->id;
        $anak->save();

        ActivityLog::record('anak.dibuat', "Menambahkan data anak {$anak->nama}", $anak);

        return $this->selesai($request, $anak, 'Data anak berhasil ditambahkan.');
    }

    public function edit(Request $request, Anak $anak): Response
    {
        Gate::authorize('manageData', $anak->panti);

        return Inertia::render('Anak/Form', [
            'anak' => $anak->only([
                'id', 'panti_id', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
                'kategori', 'pendidikan', 'nama_wali', 'tanggal_masuk', 'tanggal_keluar',
                'status', 'keterangan',
            ]),
            'defaultPantiId' => null,
            'pantiOptions' => $this->pantiOptions($request),
            'options' => ['kategori' => Anak::KATEGORI, 'pendidikan' => Anak::PENDIDIKAN],
        ]);
    }

    public function update(Request $request, Anak $anak): RedirectResponse
    {
        Gate::authorize('manageData', $anak->panti);

        $data = $request->validate($this->rules($anak));
        Gate::authorize('manageData', Panti::findOrFail($data['panti_id']));

        $anak->update($this->normalize($data));

        ActivityLog::record('anak.diubah', "Memperbarui data anak {$anak->nama}", $anak);

        return $this->selesai($request, $anak, 'Data anak berhasil diperbarui.');
    }

    public function destroy(Anak $anak): RedirectResponse
    {
        Gate::authorize('manageData', $anak->panti);

        $anak->delete();

        ActivityLog::record('anak.dihapus', "Menghapus data anak {$anak->nama}", $anak);

        return back()->with('success', 'Data anak berhasil dihapus.');
    }

    /** Kembali ke tempat asal: detail panti jika dibuka dari sana, kalau tidak ke daftar anak. */
    private function selesai(Request $request, Anak $anak, string $pesan): RedirectResponse
    {
        $tujuan = $request->input('dari') === 'panti'
            ? route('panti.show', [$anak->panti_id, 'tab' => 'anak'])
            : route('anak.index', ['panti_id' => $anak->panti_id]);

        return redirect($tujuan)->with('success', $pesan);
    }

    private function rules(?Anak $anak = null): array
    {
        return [
            'panti_id' => ['required', 'exists:pantis,id'],
            'nik' => [
                'nullable', 'digits:16',
                Rule::unique('anaks', 'nik')->whereNull('deleted_at')->ignore($anak?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'kategori' => ['required', Rule::in(array_keys(Anak::KATEGORI))],
            'pendidikan' => ['nullable', Rule::in(array_keys(Anak::PENDIDIKAN))],
            'nama_wali' => ['nullable', 'string', 'max:255'],
            'tanggal_masuk' => ['nullable', 'date', 'before_or_equal:today'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'status' => ['required', Rule::in(['aktif', 'keluar'])],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function normalize(array $data): array
    {
        if ($data['status'] === 'aktif') {
            $data['tanggal_keluar'] = null;
        }

        return $data;
    }

    private function pantiOptions(Request $request)
    {
        return Panti::visibleTo($request->user())->orderBy('nama')->get(['id', 'nama']);
    }
}
