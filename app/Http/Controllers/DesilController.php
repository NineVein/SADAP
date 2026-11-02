<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Anak;
use App\Models\DesilLaporan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DesilController extends Controller
{
    /** Operator melapor (menunggu persetujuan). Admin/supervisor langsung berlaku. */
    public function store(Request $request, Anak $anak): RedirectResponse
    {
        Gate::authorize('reportDesil', $anak->panti);

        $data = $request->validate([
            'desil' => ['required', 'integer', 'between:1,10'],
            'sumber' => ['required', Rule::in(array_keys(DesilLaporan::SUMBER))],
            'tanggal_data' => ['nullable', 'date', 'before_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'desil.required' => 'Pilih desil 1 sampai 10.',
            'desil.between' => 'Desil harus antara 1 sampai 10.',
        ]);

        if ($anak->desilLaporans()->where('status', DesilLaporan::MENUNGGU)->exists()) {
            return back()->with('error', 'Sudah ada laporan desil untuk anak ini yang menunggu persetujuan.');
        }

        $user = $request->user();
        $langsung = $user->isAdmin() || $user->isSupervisor();

        DB::transaction(function () use ($anak, $data, $user, $langsung) {
            DesilLaporan::create([
                ...$data,
                'anak_id' => $anak->id,
                'status' => $langsung ? DesilLaporan::DISETUJUI : DesilLaporan::MENUNGGU,
                'dilaporkan_by' => $user->id,
                'ditinjau_by' => $langsung ? $user->id : null,
                'ditinjau_at' => $langsung ? now() : null,
            ]);

            if ($langsung) {
                $anak->forceFill(['desil' => $data['desil']])->save();
            }
        });

        ActivityLog::record(
            'desil.dilaporkan',
            "Melaporkan desil {$data['desil']} untuk {$anak->nama}".($langsung ? ' (langsung berlaku)' : ''),
            $anak
        );

        return back()->with('success', $langsung
            ? 'Desil berhasil disimpan.'
            : 'Laporan desil terkirim dan menunggu persetujuan Dinas Sosial.');
    }

    public function setujui(Request $request, DesilLaporan $desilLaporan): RedirectResponse
    {
        Gate::authorize('reviewDesil', $desilLaporan->anak->panti);

        if ($desilLaporan->status !== DesilLaporan::MENUNGGU) {
            return back()->with('error', 'Laporan ini sudah ditinjau.');
        }

        DB::transaction(function () use ($desilLaporan, $request) {
            $desilLaporan->forceFill([
                'status' => DesilLaporan::DISETUJUI,
                'ditinjau_by' => $request->user()->id,
                'ditinjau_at' => now(),
            ])->save();

            $desilLaporan->anak->forceFill(['desil' => $desilLaporan->desil])->save();
        });

        ActivityLog::record('desil.disetujui', "Menyetujui desil {$desilLaporan->desil} untuk {$desilLaporan->anak->nama}", $desilLaporan);

        return back()->with('success', 'Laporan desil disetujui dan data anak diperbarui.');
    }

    public function tolak(Request $request, DesilLaporan $desilLaporan): RedirectResponse
    {
        Gate::authorize('reviewDesil', $desilLaporan->anak->panti);

        $data = $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:1000'],
        ], ['catatan.required' => 'Tuliskan alasan penolakan.']);

        if ($desilLaporan->status !== DesilLaporan::MENUNGGU) {
            return back()->with('error', 'Laporan ini sudah ditinjau.');
        }

        $desilLaporan->forceFill([
            'status' => DesilLaporan::DITOLAK,
            'catatan_review' => $data['catatan'],
            'ditinjau_by' => $request->user()->id,
            'ditinjau_at' => now(),
        ])->save();

        ActivityLog::record('desil.ditolak', "Menolak laporan desil {$desilLaporan->anak->nama}", $desilLaporan);

        return back()->with('success', 'Laporan desil ditolak.');
    }
}
