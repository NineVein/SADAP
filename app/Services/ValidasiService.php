<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Panti;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ValidasiService
{
    /**
     * Mengajukan panti untuk diverifikasi dan divalidasi oleh Dinas Sosial.
     */
    public function ajukan(Panti $panti): void
    {
        if (! $panti->isLengkap()) {
            $kurang = collect($panti->checklist())->where('done', false)->pluck('label')->implode(', ');
            throw ValidationException::withMessages([
                'kelengkapan' => "Data panti belum lengkap ({$kurang}). Lengkapi seluruh poin sebelum mengajukan.",
            ]);
        }

        DB::transaction(function () use ($panti) {
            $panti->forceFill([
                'validation_status' => Panti::DIAJUKAN,
                'diajukan_at' => now(),
                'catatan_validasi' => null,
                'validated_by' => null,
                'validated_at' => null,
            ])->save();

            ActivityLog::record('validasi.diajukan', "Mengajukan data panti {$panti->nama} untuk validasi kelayakan", $panti);
        });
    }

    /**
     * Menyetujui validasi kelayakan data panti.
     */
    public function setujui(Panti $panti, User $reviewer): void
    {
        DB::transaction(function () use ($panti, $reviewer) {
            $panti->forceFill([
                'validation_status' => Panti::TERVALIDASI,
                'catatan_validasi' => null,
                'validated_by' => $reviewer->id,
                'validated_at' => now(),
            ])->save();

            ActivityLog::record('validasi.disetujui', "Menyetujui validasi data panti {$panti->nama}", $panti);
        });
    }

    /**
     * Menolak pengajuan validasi panti dengan catatan revisi yang jelas untuk operator.
     */
    public function tolak(Panti $panti, User $reviewer, string $catatan): void
    {
        DB::transaction(function () use ($panti, $reviewer, $catatan) {
            $panti->forceFill([
                'validation_status' => Panti::DITOLAK,
                'catatan_validasi' => $catatan,
                'validated_by' => $reviewer->id,
                'validated_at' => now(),
            ])->save();

            ActivityLog::record('validasi.ditolak', "Menolak validasi panti {$panti->nama}: {$catatan}", $panti);
        });
    }

    /**
     * Menemukan pengajuan panti berikutnya yang sedang menunggu validasi.
     */
    public function pengajuanBerikutnya(Panti $pantiSaatIni): ?Panti
    {
        return Panti::query()
            ->where('validation_status', Panti::DIAJUKAN)
            ->whereKeyNot($pantiSaatIni->id)
            ->orderBy('diajukan_at')
            ->first();
    }
}

