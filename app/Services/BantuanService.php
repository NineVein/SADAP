<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Panti;
use Illuminate\Support\Facades\DB;

class BantuanService
{
    /**
     * Simpan pemetaan kebutuhan jenis bantuan sosial panti dengan catatan spesifiknya.
     */
    public function simpanKebutuhan(Panti $panti, array $items): void
    {
        DB::transaction(function () use ($panti, $items) {
            $syncData = collect($items)->mapWithKeys(function ($item) {
                $id = $item['jenis_bantuan_id'] ?? $item['id'] ?? null;
                return $id ? [$id => ['catatan' => $item['catatan'] ?? null]] : [];
            })->all();

            $panti->jenisBantuan()->sync($syncData);

            $jumlah = count($syncData);
            ActivityLog::record(
                'panti.jenis_bantuan',
                "Memperbarui profil kebutuhan jenis bantuan panti {$panti->nama} ({$jumlah} jenis bantuan dicatat)",
                $panti
            );
        });
    }
}

