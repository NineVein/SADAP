<?php

namespace App\Http\Controllers;

use App\Models\Panti;
use App\Services\ValidasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Aksi validasi kelayakan data panti. */
class ValidasiController extends Controller
{
    public function __construct(private ValidasiService $service) {}

    public function ajukan(Panti $panti): RedirectResponse
    {
        Gate::authorize('submit', $panti);

        $this->service->ajukan($panti);

        return back()->with('success', 'Data panti berhasil diajukan untuk proses validasi kelayakan.');
    }

    public function setujui(Request $request, Panti $panti): RedirectResponse
    {
        Gate::authorize('review', $panti);

        $this->service->setujui($panti, $request->user());

        return $this->berikutnya($panti, 'Data panti dinyatakan tervalidasi.');
    }

    public function tolak(Request $request, Panti $panti): RedirectResponse
    {
        Gate::authorize('review', $panti);

        $data = $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'catatan.required' => 'Tuliskan alasan penolakan agar operator tahu apa yang perlu diperbaiki.',
            'catatan.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $this->service->tolak($panti, $request->user(), $data['catatan']);

        return $this->berikutnya($panti, 'Pengajuan ditolak dan catatan dikirim ke operator.');
    }

    /** Setelah menyetujui/menolak, langsung buka pengajuan berikutnya agar reviewer tidak mencari lagi. */
    private function berikutnya(Panti $panti, string $pesan): RedirectResponse
    {
        $next = $this->service->pengajuanBerikutnya($panti);

        if ($next) {
            return redirect()->route('panti.show', $next)
                ->with('success', $pesan.' Berikut pengajuan berikutnya: '.$next->nama.'.');
        }

        return redirect()->route('dashboard')
            ->with('success', $pesan.' Tidak ada pengajuan lain yang menunggu.');
    }
}
