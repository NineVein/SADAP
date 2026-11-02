<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Kelurahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Master data kelurahan/desa: hanya administrator. */
class KelurahanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $kelurahans = Kelurahan::query()
            ->withCount(['pantis', 'users'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('nama', 'like', $like)->orWhere('kecamatan', 'like', $like));
            })
            ->orderBy('kecamatan')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Kelurahan/Index', [
            'kelurahans' => $kelurahans,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kelurahan = Kelurahan::create($this->validated($request));

        ActivityLog::record('wilayah.dibuat', "Menambahkan wilayah {$kelurahan->nama}", $kelurahan);

        return back()->with('success', 'Kelurahan/desa berhasil ditambahkan.');
    }

    public function update(Request $request, Kelurahan $kelurahan): RedirectResponse
    {
        $kelurahan->update($this->validated($request, $kelurahan));

        ActivityLog::record('wilayah.diubah', "Memperbarui wilayah {$kelurahan->nama}", $kelurahan);

        return back()->with('success', 'Kelurahan/desa berhasil diperbarui.');
    }

    public function destroy(Kelurahan $kelurahan): RedirectResponse
    {
        if ($kelurahan->pantis()->exists() || $kelurahan->users()->exists()) {
            return back()->with('error', 'Wilayah masih dipakai panti atau akun operator, tidak bisa dihapus.');
        }

        $kelurahan->delete();

        ActivityLog::record('wilayah.dihapus', "Menghapus wilayah {$kelurahan->nama}", $kelurahan);

        return back()->with('success', 'Kelurahan/desa berhasil dihapus.');
    }

    private function validated(Request $request, ?Kelurahan $kelurahan = null): array
    {
        return $request->validate([
            'nama' => [
                'required', 'string', 'max:255',
                Rule::unique('kelurahans', 'nama')
                    ->where('kecamatan', $request->input('kecamatan'))
                    ->ignore($kelurahan?->id),
            ],
            'kecamatan' => ['required', 'string', 'max:255'],
            'tipe' => ['required', Rule::in(['kelurahan', 'desa'])],
        ], [
            'nama.unique' => 'Nama tersebut sudah terdaftar di kecamatan yang sama.',
        ]);
    }
}
