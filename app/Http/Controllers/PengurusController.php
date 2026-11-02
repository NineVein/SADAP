<?php

namespace App\Http\Controllers;

use App\Models\Panti;
use App\Models\Pengurus;
use App\Services\PengurusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Dikelola dari tab "Pengurus" di halaman detail panti. */
class PengurusController extends Controller
{
    public function __construct(private PengurusService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $panti = Panti::findOrFail($data['panti_id']);
        Gate::authorize('manageData', $panti);

        $this->service->simpan($panti, $data);

        return back()->with('success', 'Pengurus berhasil ditambahkan.');
    }

    public function update(Request $request, Pengurus $pengurus): RedirectResponse
    {
        Gate::authorize('manageData', $pengurus->panti);

        $data = $request->validate($this->rules());
        $panti = $pengurus->panti;
        Gate::authorize('manageData', $panti);

        $this->service->simpan($panti, $data, $pengurus);

        return back()->with('success', 'Data pengurus berhasil diperbarui.');
    }

    public function destroy(Pengurus $pengurus): RedirectResponse
    {
        Gate::authorize('manageData', $pengurus->panti);

        $this->service->hapus($pengurus);

        return back()->with('success', 'Pengurus berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'panti_id' => ['required', 'exists:pantis,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'periode_mulai' => ['nullable', 'date'],
            'periode_selesai' => ['nullable', 'date', 'after_or_equal:periode_mulai'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ];
    }
}
