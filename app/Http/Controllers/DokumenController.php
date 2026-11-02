<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\Panti;
use App\Services\DokumenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenController extends Controller
{
    public function __construct(private DokumenService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'panti_id' => ['required', 'exists:pantis,id'],
            'jenis' => ['required', Rule::in(array_keys(Dokumen::JENIS))],
            'judul' => ['required', 'string', 'max:255'],
            'nomor' => ['nullable', 'string', 'max:100'],
            'berlaku_sampai' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ], [
            'file.max' => 'Ukuran file maksimal 5 MB.',
            'file.mimes' => 'Format file harus PDF, JPG, PNG, DOC, atau DOCX.',
        ]);

        $panti = Panti::findOrFail($data['panti_id']);
        Gate::authorize('manageData', $panti);

        $this->service->simpan($panti, $request->user(), $data, $request->file('file'));

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function download(Dokumen $dokumen): StreamedResponse
    {
        Gate::authorize('view', $dokumen->panti);

        return $this->service->unduh($dokumen);
    }

    public function destroy(Dokumen $dokumen): RedirectResponse
    {
        Gate::authorize('manageData', $dokumen->panti);

        $this->service->hapus($dokumen);

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
