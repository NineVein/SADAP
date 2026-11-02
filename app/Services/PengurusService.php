<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Panti;
use App\Models\Pengurus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PengurusService
{
    /**
     * Simpan pengurus baru atau perbarui pengurus yang sudah ada.
     * Mengamankan aturan jabatan tunggal aktif per panti dan audit logging.
     */
    public function simpan(Panti $panti, array $data, ?Pengurus $pengurus = null): Pengurus
    {
        $data['panti_id'] = $panti->id;
        $data['status'] = $data['status'] ?? $pengurus?->status ?? 'aktif';

        $this->pastikanJabatanTunggal($panti, $data, $pengurus);

        return DB::transaction(function () use ($panti, $data, $pengurus) {
            $isBaru = $pengurus === null;

            if ($isBaru) {
                $pengurus = Pengurus::create($data);
                ActivityLog::record('pengurus.dibuat', "Menambahkan pengurus {$pengurus->nama} ({$pengurus->jabatan}) di {$panti->nama}", $pengurus);
            } else {
                $pengurus->update($data);
                ActivityLog::record('pengurus.diubah', "Memperbarui data pengurus {$pengurus->nama} di {$panti->nama}", $pengurus);
            }

            return $pengurus;
        });
    }

    /**
     * Hapus pengurus dari panti.
     */
    public function hapus(Pengurus $pengurus): void
    {
        DB::transaction(function () use ($pengurus) {
            $nama = $pengurus->nama;
            $pantiNama = $pengurus->panti?->nama ?? 'Panti';
            $pengurus->delete();
            ActivityLog::record('pengurus.dihapus', "Menghapus pengurus {$nama} dari {$pantiNama}", $pengurus);
        });
    }

    /**
     * Validasi: Jabatan inti (Ketua, Sekretaris, Bendahara) hanya boleh 1 orang aktif per panti.
     */
    private function pastikanJabatanTunggal(Panti $panti, array $data, ?Pengurus $pengurus): void
    {
        $status = $data['status'] ?? 'aktif';
        if ($status !== 'aktif') {
            return;
        }

        $jabatanTunggal = config('diskat.jabatan_tunggal', ['Ketua', 'Sekretaris', 'Bendahara']);
        $jabatanInput = trim((string) ($data['jabatan'] ?? ''));

        // Cek kecocokan jabatan tunggal (case-insensitive)
        $isTunggal = collect($jabatanTunggal)->contains(
            fn ($j) => strcasecmp(trim($j), $jabatanInput) === 0
        );

        if (! $isTunggal) {
            return;
        }

        $bentrok = Pengurus::where('panti_id', $panti->id)
            ->where('jabatan', $jabatanInput)
            ->where('status', 'aktif')
            ->when($pengurus, fn ($q) => $q->whereKeyNot($pengurus->id))
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'jabatan' => "Jabatan '{$jabatanInput}' berstatus aktif sudah terisi di panti ini. Silakan ubah status pengurus sebelumnya menjadi nonaktif terlebih dahulu.",
            ]);
        }
    }
}

