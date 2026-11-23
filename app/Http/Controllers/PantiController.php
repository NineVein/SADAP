<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Anak;
use App\Models\JenisBantuan;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\RiwayatBantuan;
use App\Services\BantuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PantiController extends Controller
{
    public function __construct(private ?BantuanService $bantuanService = null)
    {
        $this->bantuanService = $bantuanService ?? app(BantuanService::class);
    }
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $validasi = $request->query('validasi');
        $kelurahanId = (int) $request->query('kelurahan_id');
        $kategori = $request->query('kategori');

        $pantis = Panti::query()
            ->visibleTo($user)
            ->kategori($kategori)
            ->with('kelurahan:id,nama,kecamatan')
            ->withCount(['anaks', 'pengurus', 'dokumens', 'jenisBantuan'])
            ->when($request->boolean('tanpa_wilayah'), fn ($q) => $q->whereNull('kelurahan_id'))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn ($q) => $q
                    ->where('nama', 'like', $like)
                    ->orWhere('alamat', 'like', $like)
                    ->orWhere('no_sk', 'like', $like));
            })
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status', $status))
            ->when(in_array($validasi, Panti::VALIDASI, true), fn ($q) => $q->where('validation_status', $validasi))
            ->when($kelurahanId > 0, fn ($q) => $q->where('kelurahan_id', $kelurahanId))
            ->when(
                $validasi === Panti::DIAJUKAN,
                fn ($q) => $q->orderBy('diajukan_at'),
                fn ($q) => $q->orderBy('nama')
            )
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Panti $panti) => [
                'id' => $panti->id,
                'nama' => $panti->nama,
                'kategori_lks' => $panti->kategori_lks,
                'alamat' => $panti->alamat,
                'status' => $panti->status,
                'validation_status' => $panti->validation_status,
                'catatan_validasi' => $panti->catatan_validasi,
                'anaks_count' => $panti->anaks_count,
                'kelengkapan' => $panti->kelengkapan(),
                'kurang' => collect($panti->checklist())->where('done', false)->pluck('label')->values()->all(),
                'kelurahan' => $panti->kelurahan?->only(['nama', 'kecamatan']),
                'can' => [
                    'update' => $user->can('update', $panti),
                    'delete' => $user->can('delete', $panti),
                ],
            ]);

        return Inertia::render('Panti/Index', [
            'pantis' => $pantis,
            'filters' => [
                'search' => $search,
                'status' => (string) $status,
                'validasi' => (string) $validasi,
                'kelurahan_id' => $kelurahanId ?: '',
                'kategori' => array_key_exists((string) $kategori, Panti::KATEGORI_LKS) ? $kategori : '',
            ],
            'kelurahans' => $this->kelurahanOptions($request),
            'counts' => $this->validasiCounts($request, $kategori),
            'kategoriCounts' => $this->kategoriCounts($request),
            'options' => ['kategori' => Panti::KATEGORI_LKS],
            'can' => ['create' => $user->can('create', Panti::class)],
        ]);
    }

    /** Jumlah LKS per kategori (Anak, Lanjut Usia, Disabilitas), untuk tab kategori. */
    private function kategoriCounts(Request $request): array
    {
        $rows = Panti::query()
            ->visibleTo($request->user())
            ->selectRaw('kategori_lks, count(*) as total')
            ->groupBy('kategori_lks')
            ->pluck('total', 'kategori_lks');

        $hasil = ['semua' => (int) $rows->sum()];
        foreach (array_keys(Panti::KATEGORI_LKS) as $k) {
            $hasil[$k] = (int) ($rows[$k] ?? 0);
        }

        return $hasil;
    }

    /** Jumlah LKS per status validasi, untuk tab di daftar LKS. */
    private function validasiCounts(Request $request, ?string $kategori = null): array
    {
        $rows = Panti::query()
            ->visibleTo($request->user())
            ->kategori($kategori)
            ->selectRaw('validation_status, count(*) as total')
            ->groupBy('validation_status')
            ->pluck('total', 'validation_status');

        return [
            'semua' => (int) $rows->sum(),
            'draft' => (int) ($rows[Panti::DRAFT] ?? 0),
            'diajukan' => (int) ($rows[Panti::DIAJUKAN] ?? 0),
            'tervalidasi' => (int) ($rows[Panti::TERVALIDASI] ?? 0),
            'ditolak' => (int) ($rows[Panti::DITOLAK] ?? 0),
        ];
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Panti::class);

        $kategori = $request->query('kategori');

        return Inertia::render('Panti/Form', [
            'panti' => null,
            'defaultKategori' => array_key_exists((string) $kategori, Panti::KATEGORI_LKS) ? $kategori : 'anak',
            'kelurahans' => $this->kelurahanOptions($request),
            'options' => ['kategori' => Panti::KATEGORI_LKS],
            'jumlahKlien' => 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Panti::class);

        $user = $request->user();
        $data = $request->validate($this->rules($request));

        if ($user->isOperator()) {
            $data['kelurahan_id'] = $user->kelurahan_id;
        }

        $panti = new Panti($data);
        $panti->created_by = $user->id;
        $panti->save();

        ActivityLog::record('panti.dibuat', "Menambahkan LKS {$panti->nama} (kategori {$panti->kategori_lks})", $panti);

        return redirect()
            ->route('panti.show', $panti)
            ->with('success', 'Data LKS berhasil ditambahkan. Lengkapi klien, pengurus, dokumen, dan kebutuhan jenis bantuan sebelum diajukan.');
    }

    public function show(Request $request, Panti $panti): Response
    {
        Gate::authorize('view', $panti);

        $user = $request->user();
        $panti->load(['kelurahan:id,nama,kecamatan,tipe', 'validator:id,name', 'dokumens'])
            ->loadCount(['pengurus', 'anaks', 'dokumens', 'jenisBantuan']);

        $tab = in_array($request->query('tab'), ['profil', 'anak', 'pengurus', 'dokumen', 'jenis', 'aktivitas'], true)
            ? $request->query('tab')
            : 'anak';

        $dipilih = $panti->jenisBantuan()->get()->keyBy('id');
        $riwayat = RiwayatBantuan::where('panti_id', $panti->id)
            ->selectRaw('jenis_bantuan_id, COUNT(*) as kali, SUM(nilai) as total, MAX(tanggal) as terakhir')
            ->groupBy('jenis_bantuan_id')
            ->get()
            ->keyBy('jenis_bantuan_id');

        $activityLogs = \App\Models\ActivityLog::where(function ($q) use ($panti) {
                $q->where('subject_type', Panti::class)->where('subject_id', $panti->id);
            })
            ->orWhere(function ($q) use ($panti) {
                $q->where('subject_type', \App\Models\Pengurus::class)
                  ->whereIn('subject_id', $panti->pengurus()->pluck('id'));
            })
            ->orWhere(function ($q) use ($panti) {
                $q->where('subject_type', \App\Models\Dokumen::class)
                  ->whereIn('subject_id', $panti->dokumens()->pluck('id'));
            })
            ->orWhere(function ($q) use ($panti) {
                $q->where('subject_type', \App\Models\Anak::class)
                  ->whereIn('subject_id', $panti->anaks()->pluck('id'));
            })
            ->with(['user:id,name,role_id', 'user.role:id,name'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'created_at' => $log->created_at?->toIso8601String(),
                'waktu' => $log->created_at?->format('Y-m-d H:i') ?? '-',
                'oleh' => $log->user?->name ?? 'Sistem',
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'role' => $log->user->role ? ['name' => $log->user->role->name] : null,
                ] : null,
                'aksi' => $log->aksi,
                'deskripsi' => $log->deskripsi,
            ]);

        $jenisBantuanList = JenisBantuan::where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get()
            ->map(fn (JenisBantuan $j) => [
                'id' => $j->id,
                'kode' => $j->kode,
                'nama' => $j->nama,
                'kategori' => $j->kategori,
                'deskripsi' => $j->deskripsi,
                'dibutuhkan' => $dipilih->has($j->id),
                'catatan' => $dipilih->get($j->id)?->pivot->catatan ?? '',
                'kali_diterima' => (int) ($riwayat[$j->id]->kali ?? 0),
                'total_diterima' => (float) ($riwayat[$j->id]->total ?? 0),
                'terakhir' => $riwayat[$j->id]->terakhir ?? null,
            ]);

        $dokumens = $panti->dokumens;
        $dokumenWajibStatus = [
            'akta' => [
                'ada' => $dokumens->contains(fn ($d) => in_array($d->jenis, ['akta_pendirian', 'sk_pendirian', 'akta_notaris'], true)),
                'kadaluarsa' => $dokumens->contains(fn ($d) => in_array($d->jenis, ['akta_pendirian', 'sk_pendirian', 'akta_notaris'], true) && $d->kadaluarsa),
            ],
            'izin' => [
                'ada' => $dokumens->contains(fn ($d) => $d->jenis === 'izin_operasional'),
                'kadaluarsa' => $dokumens->contains(fn ($d) => $d->jenis === 'izin_operasional' && $d->kadaluarsa),
            ],
        ];

        return Inertia::render('Panti/Show', [
            'panti' => [
                ...Arr::only($panti->attributesToArray(), [
                    'id', 'nama', 'kategori_lks', 'alamat', 'no_sk', 'tanggal_berdiri', 'telepon', 'email',
                    'nama_pengelola', 'kapasitas', 'status', 'validation_status',
                    'catatan_validasi', 'diajukan_at', 'validated_at', 'created_at',
                    'anaks_count', 'pengurus_count', 'dokumens_count',
                ]),
                'kelurahan' => $panti->kelurahan,
                'validator' => $panti->validator?->name,
                'kelengkapan' => $panti->kelengkapan(),
                'checklist' => $panti->checklist(),
                'jenis_bantuan_count' => $panti->jenis_bantuan_count ?? $dipilih->count(),
            ],
            'tab' => $tab,
            'anaks' => $panti->anaks()->orderBy('nama')->limit(300)->get()
                ->map(fn ($a) => [
                    ...$a->only([
                        'id', 'nama', 'jenis_kelamin', 'umur', 'kategori', 'kategori_klien',
                        'jenis_disabilitas', 'tingkat_ketergantungan', 'status',
                    ]),
                    'nik_tersamar' => \App\Models\Anak::samarkanNik($a->nik),
                ]),
            'pengurus' => $panti->pengurus()->orderBy('nama')
                ->get(['id', 'panti_id', 'nama', 'jabatan', 'telepon', 'email', 'alamat', 'periode_mulai', 'periode_selesai', 'status']),
            'dokumens' => $panti->dokumens()->latest()
                ->get(['id', 'jenis', 'judul', 'nomor', 'berlaku_sampai', 'nama_asli', 'ukuran', 'created_at'])
                ->map(fn ($d) => [
                    ...$d->attributesToArray(),
                    'kadaluarsa' => $d->kadaluarsa,
                ]),
            'jenisBantuan' => $jenisBantuanList,
            'dokumenWajibStatus' => $dokumenWajibStatus,
            'activityLogs' => $activityLogs,
            'options' => [
                'kategori' => \App\Models\Anak::KATEGORI,
                'kategoriLks' => Panti::KATEGORI_LKS,
                'jenisDisabilitas' => \App\Models\Anak::JENIS_DISABILITAS,
                'tingkat' => \App\Models\Anak::TINGKAT_KETERGANTUNGAN,
                'jenis' => \App\Models\Dokumen::JENIS,
            ],
            'can' => [
                'update' => $user->can('update', $panti),
                'delete' => $user->can('delete', $panti),
                'manage' => $user->can('manageData', $panti),
                'submit' => $user->can('submit', $panti),
                'review' => $user->can('review', $panti),
            ],
        ]);
    }

    public function simpanJenisBantuan(Request $request, Panti $panti): RedirectResponse
    {
        Gate::authorize('manageData', $panti);

        $rawItems = $request->input('items', $request->input('jenis_bantuan', []));

        $items = collect($rawItems)->map(function ($item) {
            return [
                'jenis_bantuan_id' => $item['jenis_bantuan_id'] ?? $item['id'] ?? null,
                'catatan' => $item['catatan'] ?? null,
            ];
        })->filter(fn ($item) => ! empty($item['jenis_bantuan_id']))->values()->all();

        $validator = \Illuminate\Support\Facades\Validator::make(['items' => $items], [
            'items' => ['present', 'array'],
            'items.*.jenis_bantuan_id' => ['required', 'exists:jenis_bantuans,id'],
            'items.*.catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $validator->validate();

        $this->bantuanService->simpanKebutuhan($panti, $data['items']);

        return back()->with('success', 'Kebutuhan jenis bantuan berhasil diperbarui.');
    }

    public function edit(Request $request, Panti $panti): Response
    {
        Gate::authorize('update', $panti);

        return Inertia::render('Panti/Form', [
            'panti' => Arr::only($panti->attributesToArray(), [
                'id', 'nama', 'kategori_lks', 'alamat', 'kelurahan_id', 'no_sk', 'tanggal_berdiri',
                'telepon', 'email', 'nama_pengelola', 'kapasitas', 'status',
            ]),
            'defaultKategori' => $panti->kategori_lks,
            'kelurahans' => $this->kelurahanOptions($request),
            'options' => ['kategori' => Panti::KATEGORI_LKS],
            'jumlahKlien' => $panti->anaks()->count(),
        ]);
    }

    public function update(Request $request, Panti $panti): RedirectResponse
    {
        Gate::authorize('update', $panti);

        $data = $request->validate($this->rules($request, $panti));

        // Kategori LKS terkunci selama masih ada klien, agar data klien tidak tercampur kategori.
        if (isset($data['kategori_lks']) && $data['kategori_lks'] !== $panti->kategori_lks && $panti->anaks()->exists()) {
            throw ValidationException::withMessages([
                'kategori_lks' => 'Kategori LKS tidak dapat diubah karena sudah memiliki klien. Pindahkan atau hapus klien terlebih dahulu.',
            ]);
        }

        // Operator tidak boleh memindahkan panti ke wilayah lain.
        if ($request->user()->isOperator()) {
            unset($data['kelurahan_id']);
        }

        $panti->update($data);

        ActivityLog::record('panti.diubah', "Memperbarui data LKS {$panti->nama}", $panti);

        return redirect()
            ->route('panti.show', $panti)
            ->with('success', 'Data LKS berhasil diperbarui.');
    }

    public function destroy(Panti $panti): RedirectResponse
    {
        Gate::authorize('delete', $panti);

        $panti->delete(); // soft delete: data masih bisa dipulihkan dari database

        ActivityLog::record('panti.dihapus', "Menghapus LKS {$panti->nama}", $panti);

        return redirect()
            ->route('panti.index')
            ->with('success', 'Data LKS berhasil dihapus.');
    }

    private function rules(Request $request, ?Panti $panti = null): array
    {
        $user = $request->user();
        $kelurahanId = $user->isOperator() ? $user->kelurahan_id : $request->input('kelurahan_id');

        return [
            'nama' => [
                'required', 'string', 'max:255',
                Rule::unique('pantis', 'nama')
                    ->where('kelurahan_id', $kelurahanId)
                    ->whereNull('deleted_at')
                    ->ignore($panti?->id),
            ],
            // 'sometimes': bila tidak dikirim (mis. klien API lama) dipakai nilai bawaan/yang sudah ada.
            'kategori_lks' => ['sometimes', 'required', Rule::in(array_keys(Panti::KATEGORI_LKS))],
            'alamat' => ['required', 'string', 'max:1000'],
            'kelurahan_id' => $user->isOperator() ? ['nullable'] : ['required', 'exists:kelurahans,id'],
            'no_sk' => ['nullable', 'string', 'max:255'],
            'tanggal_berdiri' => ['nullable', 'date', 'before_or_equal:today'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'nama_pengelola' => ['nullable', 'string', 'max:255'],
            'kapasitas' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    private function kelurahanOptions(Request $request)
    {
        return Kelurahan::visibleTo($request->user())
            ->orderBy('kecamatan')
            ->orderBy('nama')
            ->get(['id', 'nama', 'kecamatan', 'tipe']);
    }
}
