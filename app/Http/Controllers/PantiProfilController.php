<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Dokumen;
use App\Models\JenisBantuan;
use App\Models\Panti;
use App\Models\Pengurus;
use App\Models\RiwayatBantuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Controller endpoint profil panti: pengurus, dokumen instansi, dan kebutuhan jenis bantuan.
 * Mendukung akses API/JSON dan integrasi modular.
 */
class PantiProfilController extends Controller
{
    private function akses(Request $r, Panti $panti): void
    {
        abort_unless(
            $r->user()->isAdmin() || $r->user()->isSupervisor() || ($r->user()->isOperator() && $r->user()->kelurahan_id === $panti->kelurahan_id),
            403,
            'Data ini bukan milik wilayah panti Anda.'
        );
    }

    private function aturanPengurus(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'periode_mulai' => ['nullable', 'date'],
            'periode_selesai' => ['nullable', 'date', 'after_or_equal:periode_mulai'],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function pengurus(Request $r, Panti $panti): JsonResponse
    {
        $this->akses($r, $panti);

        return response()->json(
            $panti->pengurus()->orderBy('status')->orderBy('nama')->get()
        );
    }

    public function simpanPengurus(Request $r, Panti $panti): JsonResponse
    {
        Gate::authorize('manageData', $panti);

        $data = $r->validate($this->aturanPengurus());
        $data['panti_id'] = $panti->id;
        $data['status'] = $data['status'] ?? 'aktif';

        $pengurus = Pengurus::create($data);
        ActivityLog::record('pengurus.dibuat', "Menambahkan pengurus {$pengurus->nama}", $pengurus);

        return response()->json($pengurus, 201);
    }

    public function ubahPengurus(Request $r, Pengurus $pengurus): JsonResponse
    {
        Gate::authorize('manageData', $pengurus->panti);

        $data = $r->validate($this->aturanPengurus());
        $pengurus->update($data);
        ActivityLog::record('pengurus.diubah', "Memperbarui pengurus {$pengurus->nama}", $pengurus);

        return response()->json($pengurus->fresh());
    }

    public function hapusPengurus(Request $r, Pengurus $pengurus): HttpResponse
    {
        Gate::authorize('manageData', $pengurus->panti);

        $nama = $pengurus->nama;
        $pengurus->delete();
        ActivityLog::record('pengurus.dihapus', "Menghapus pengurus {$nama}", $pengurus);

        return response()->noContent();
    }

    public function dokumen(Request $r, Panti $panti): JsonResponse
    {
        $this->akses($r, $panti);

        return response()->json(
            $panti->dokumens()->with('uploader:id,name')->latest('id')->get()
        );
    }

    public function unggahDokumen(Request $r, Panti $panti): JsonResponse
    {
        Gate::authorize('manageData', $panti);

        $data = $r->validate([
            'jenis' => ['nullable', Rule::in(array_keys(Dokumen::JENIS))],
            'kategori' => ['nullable', Rule::in(array_keys(Dokumen::JENIS))],
            'judul' => ['nullable', 'string', 'max:255'],
            'nomor' => ['nullable', 'string', 'max:100'],
            'berlaku_sampai' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $jenis = $data['jenis'] ?? $data['kategori'] ?? 'lainnya';
        $judul = $data['judul'] ?? (Dokumen::JENIS[$jenis] ?? ucfirst(str_replace('_', ' ', $jenis)));
        $file = $r->file('file');

        $dokumen = Dokumen::create([
            'panti_id' => $panti->id,
            'jenis' => $jenis,
            'judul' => $judul,
            'nomor' => $data['nomor'] ?? null,
            'berlaku_sampai' => $data['berlaku_sampai'] ?? null,
            'path' => $file->store("dokumen/{$panti->id}", 'local'),
            'nama_asli' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'ukuran' => $file->getSize(),
            'uploaded_by' => $r->user()->id,
        ]);

        ActivityLog::record('dokumen.diunggah', "Mengunggah dokumen {$dokumen->judul} ({$panti->nama})", $dokumen);

        return response()->json($dokumen->fresh(), 201);
    }

    public function jenisBantuan(Request $r, Panti $panti): JsonResponse
    {
        $this->akses($r, $panti);

        $dipilih = $panti->jenisBantuan()->get()->keyBy('id');
        $riwayat = RiwayatBantuan::where('panti_id', $panti->id)
            ->selectRaw('jenis_bantuan_id, COUNT(*) as kali, SUM(nilai) as total, MAX(tanggal) as terakhir')
            ->groupBy('jenis_bantuan_id')->get()->keyBy('jenis_bantuan_id');

        $list = JenisBantuan::where('is_active', true)->orderBy('nama')->get()->map(fn ($j) => [
            'id' => $j->id,
            'kode' => $j->kode,
            'nama' => $j->nama,
            'kategori' => $j->kategori,
            'dibutuhkan' => $dipilih->has($j->id),
            'catatan' => $dipilih->get($j->id)?->pivot->catatan ?? null,
            'kali_diterima' => (int) ($riwayat[$j->id]->kali ?? 0),
            'total_diterima' => (float) ($riwayat[$j->id]->total ?? 0),
            'terakhir' => $riwayat[$j->id]->terakhir ?? null,
        ]);

        return response()->json($list);
    }

    public function simpanJenisBantuan(Request $r, Panti $panti): JsonResponse
    {
        Gate::authorize('manageData', $panti);

        $rawItems = $r->input('items', $r->input('jenis_bantuan', []));

        $items = collect($rawItems)->map(function ($item) {
            return [
                'jenis_bantuan_id' => $item['jenis_bantuan_id'] ?? $item['id'] ?? null,
                'catatan' => $item['catatan'] ?? null,
            ];
        })->filter(fn ($item) => ! empty($item['jenis_bantuan_id']))->values()->all();

        $d = \Illuminate\Support\Facades\Validator::make(['items' => $items], [
            'items' => ['present', 'array'],
            'items.*.jenis_bantuan_id' => ['required', 'exists:jenis_bantuans,id'],
            'items.*.catatan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $panti->jenisBantuan()->sync(collect($d['items'])->mapWithKeys(
            fn ($i) => [$i['jenis_bantuan_id'] => ['catatan' => $i['catatan'] ?? null]]
        )->all());

        return $this->jenisBantuan($r, $panti);
    }

    /**
     * Mengambil 50 riwayat aktivitas perubahan terakhir profil panti (jejak audit).
     * Mengadopsi pola endpoint dinas /panti/{id}/profil/aktivitas.
     */
    public function aktivitas(Request $r, Panti $panti): JsonResponse
    {
        $this->akses($r, $panti);

        $logs = ActivityLog::where(function ($q) use ($panti) {
                $q->where('subject_type', Panti::class)->where('subject_id', $panti->id);
            })
            ->orWhere(function ($q) use ($panti) {
                $q->where('subject_type', Pengurus::class)
                  ->whereIn('subject_id', $panti->pengurus()->pluck('id'));
            })
            ->orWhere(function ($q) use ($panti) {
                $q->where('subject_type', Dokumen::class)
                  ->whereIn('subject_id', $panti->dokumens()->pluck('id'));
            })
            ->orWhere(function ($q) use ($panti) {
                $q->where('subject_type', \App\Models\Anak::class)
                  ->whereIn('subject_id', $panti->anaks()->pluck('id'));
            })
            ->with(['user:id,name'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'waktu' => $log->created_at?->format('Y-m-d H:i') ?? '-',
                'oleh' => $log->user?->name ?? 'Sistem',
                'aksi' => explode('.', $log->aksi)[1] ?? $log->aksi,
                'objek' => explode('.', $log->aksi)[0] ?? 'panti',
                'bidang' => [$log->deskripsi],
                'deskripsi' => $log->deskripsi,
            ]);

        return response()->json(['data' => $logs]);
    }
}
