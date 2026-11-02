<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Dokumen;
use App\Models\Panti;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DokumenService
{
    private function disk(): string
    {
        return config('diskat.dokumen.disk', 'local');
    }

    /**
     * Simpan dokumen instansi panti secara aman di disk privat dengan sanitasi nama berkas,
     * pembatasan kuota per jenis dokumen, transaksi DB, dan rollback berkas jika gagal.
     */
    public function simpan(Panti $panti, User $user, array $data, UploadedFile $file): Dokumen
    {
        $maksPerJenis = config('diskat.dokumen.maks_per_kategori', 10);
        $jumlahAda = Dokumen::where('panti_id', $panti->id)
            ->where('jenis', $data['jenis'])
            ->count();

        if ($jumlahAda >= $maksPerJenis) {
            throw ValidationException::withMessages([
                'jenis' => "Batas maksimal berkas untuk jenis ini ({$maksPerJenis} berkas) telah tercapai. Hapus berkas lama terlebih dahulu.",
            ]);
        }

        $mime = $file->getMimeType();
        $ukuran = $file->getSize();

        // Sanitasi nama asli berkas dari karakter anomali
        $rawBasename = basename($file->getClientOriginalName());
        $namaAsli = Str::limit(preg_replace('/[^\w\s.\-()]/u', '', $rawBasename) ?: 'dokumen', 150, '');

        // Simpan dengan path acak di direktori aman privat
        $path = $file->store("dokumen/{$panti->id}", $this->disk());
        abort_if($path === false, 500, 'Gagal menyimpan berkas ke server penyimpanan.');

        try {
            return DB::transaction(function () use ($panti, $user, $data, $namaAsli, $path, $mime, $ukuran) {
                $dokumen = Dokumen::create([
                    'panti_id' => $panti->id,
                    'jenis' => $data['jenis'],
                    'judul' => $data['judul'],
                    'nomor' => $data['nomor'] ?? null,
                    'berlaku_sampai' => $data['berlaku_sampai'] ?? null,
                    'path' => $path,
                    'nama_asli' => $namaAsli,
                    'mime' => $mime,
                    'ukuran' => $ukuran,
                    'uploaded_by' => $user->id,
                ]);

                ActivityLog::record(
                    'dokumen.diunggah',
                    "Mengunggah dokumen {$dokumen->judul} ({$panti->nama})",
                    $dokumen
                );

                return $dokumen;
            });
        } catch (Throwable $e) {
            // Hapus file yatim jika operasi database mengalami kegagalan
            Storage::disk($this->disk())->delete($path);
            throw $e;
        }
    }

    /**
     * Unduh dokumen dengan header keamanan nosniff dan verifikasi keberadaan berkas.
     */
    public function unduh(Dokumen $dokumen): StreamedResponse
    {
        abort_unless(
            Storage::disk($this->disk())->exists($dokumen->path),
            404,
            'Berkas tidak ditemukan di sistem penyimpanan.'
        );

        return Storage::disk($this->disk())->download($dokumen->path, $dokumen->nama_asli, [
            'Content-Type' => $dokumen->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Hapus berkas dari disk dan database dalam satu transaksi aman.
     */
    public function hapus(Dokumen $dokumen): void
    {
        DB::transaction(function () use ($dokumen) {
            $judul = $dokumen->judul;
            $path = $dokumen->path;
            $pantiNama = $dokumen->panti?->nama ?? 'Panti';

            $dokumen->delete();
            Storage::disk($this->disk())->delete($path);

            ActivityLog::record('dokumen.dihapus', "Menghapus dokumen {$judul} dari {$pantiNama}", $dokumen);
        });
    }
}

