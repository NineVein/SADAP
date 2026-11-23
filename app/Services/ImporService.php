<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Anak;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Impor data LKS dan klien dari berkas CSV.
 *
 * Alurnya dua tahap: (1) baca + validasi semua baris tanpa menyimpan apa pun (pratinjau),
 * (2) simpan hanya baris berstatus "baru". Baris "duplikat" dilewati dan baris "galat"
 * tidak disimpan, sehingga data lama tidak tertimpa dan data buruk tidak masuk.
 *
 * CSV dipilih karena tidak butuh paket tambahan. Dari Excel: Simpan Sebagai > CSV.
 */
class ImporService
{
    public const MAKS_BARIS = 1000;

    public const KOLOM_LKS = [
        'nama', 'kategori', 'alamat', 'kelurahan', 'kecamatan', 'no_sk', 'tanggal_berdiri',
        'telepon', 'email', 'nama_pengelola', 'kapasitas', 'status',
    ];

    public const KOLOM_KLIEN = [
        'nama_lks', 'nama', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'latar_belakang',
        'pendidikan', 'ragam_disabilitas', 'tingkat_ketergantungan', 'nama_wali', 'tanggal_masuk',
        'status', 'keterangan',
    ];

    private const WAJIB = [
        'lks' => ['nama', 'kategori', 'alamat'],
        'klien' => ['nama_lks', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'latar_belakang'],
    ];

    /** Nama kolom lain yang dikenali, dipetakan ke nama baku. */
    private const ALIAS = [
        'nama_panti' => 'nama', 'nama_lks_klien' => 'nama', 'kategori_lks' => 'kategori',
        'kelurahan_desa' => 'kelurahan', 'kelurahandesa' => 'kelurahan', 'nomor_sk' => 'no_sk',
        'jk' => 'jenis_kelamin', 'lks' => 'nama_lks', 'panti' => 'nama_lks',
        'kategori_sosial' => 'latar_belakang', 'ragam' => 'ragam_disabilitas',
        'tingkat' => 'tingkat_ketergantungan', 'wali' => 'nama_wali',
    ];

    /* ================================================================== */
    /* Templat                                                            */
    /* ================================================================== */

    public function templat(string $jenis): string
    {
        $kolom = $jenis === 'lks' ? self::KOLOM_LKS : self::KOLOM_KLIEN;
        $contoh = $jenis === 'lks'
            ? ['LKS Contoh', 'Anak', 'Jl. Contoh No. 1', 'Nama Kelurahan', 'Nama Kecamatan', 'SK-001/2020', '2020-01-15', '081234567890', 'lks@contoh.id', 'Nama Pengelola', '40', 'aktif']
            : ['LKS Contoh', 'Klien Contoh', '', 'L', 'Tondano', '2012-05-20', 'dhuafa', 'sd', '', '', 'Nama Wali', '2023-07-01', 'aktif', 'Catatan'];

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $kolom, ';', '"', '\\');
        fputcsv($out, $contoh, ';', '"', '\\');
        rewind($out);

        return stream_get_contents($out);
    }

    /* ================================================================== */
    /* Membaca berkas                                                     */
    /* ================================================================== */

    /**
     * @return array<int, array<string, string>>  baris asosiatif; kunci '_baris' = nomor baris di berkas
     */
    public function baca(UploadedFile $file, string $jenis): array
    {
        $isi = (string) file_get_contents($file->getRealPath());
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);

        if (! mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }

        $barisPertama = strtok($isi, "\r\n") ?: '';
        $pemisah = collect([';' => substr_count($barisPertama, ';'), ',' => substr_count($barisPertama, ','), "\t" => substr_count($barisPertama, "\t")])
            ->sortDesc()->keys()->first();

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $isi);
        rewind($fh);

        $header = null;
        $rows = [];
        $no = 0;

        while (($r = fgetcsv($fh, 0, $pemisah, '"', '\\')) !== false) {
            $no++;

            if ($header === null) {
                $header = array_map(fn ($h) => $this->namaKolom((string) $h, $jenis), $r);
                $this->cekHeader($header, $jenis);

                continue;
            }

            if (count(array_filter($r, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = ['_baris' => $no];
            foreach ($header as $i => $nama) {
                if ($nama !== '') {
                    $row[$nama] = trim((string) ($r[$i] ?? ''));
                }
            }
            $rows[] = $row;

            if (count($rows) > self::MAKS_BARIS) {
                throw new RuntimeException('Berkas melebihi '.self::MAKS_BARIS.' baris. Pecah menjadi beberapa berkas.');
            }
        }
        fclose($fh);

        if ($header === null || $rows === []) {
            throw new RuntimeException('Berkas kosong atau hanya berisi baris judul.');
        }

        return $rows;
    }

    private function namaKolom(string $h, string $jenis): string
    {
        $h = mb_strtolower(trim($h));
        $h = preg_replace('/[\s\-\/]+/', '_', $h);
        $h = preg_replace('/[^a-z0-9_]/', '', $h);

        // Di berkas klien, "nama_panti" berarti LKS tempat klien tinggal, bukan nama klien.
        if ($jenis === 'klien' && $h === 'nama_panti') {
            return 'nama_lks';
        }

        return self::ALIAS[$h] ?? $h;
    }

    private function cekHeader(array $header, string $jenis): void
    {
        $hilang = array_diff(self::WAJIB[$jenis], $header);

        if ($hilang) {
            throw new RuntimeException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $hilang).'. Gunakan templat yang disediakan agar nama kolom sesuai.'
            );
        }
    }

    /* ================================================================== */
    /* Pemeriksaan (pratinjau)                                            */
    /* ================================================================== */

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array{baris: array<int, array<string, mixed>>, ringkasan: array<string, int>}
     */
    public function proses(string $jenis, array $rows, User $user): array
    {
        $baris = $jenis === 'lks' ? $this->prosesLks($rows, $user) : $this->prosesKlien($rows, $user);

        $ringkasan = ['total' => count($baris), 'baru' => 0, 'duplikat' => 0, 'galat' => 0];
        foreach ($baris as $b) {
            $ringkasan[$b['status']]++;
        }

        return ['baris' => $baris, 'ringkasan' => $ringkasan];
    }

    private function prosesLks(array $rows, User $user): array
    {
        $kelurahans = Kelurahan::all();
        $sudahAda = Panti::query()->get(['nama', 'kelurahan_id'])
            ->mapWithKeys(fn ($p) => [mb_strtolower($p->nama).'|'.$p->kelurahan_id => true])->all();
        $hasil = [];

        foreach ($rows as $r) {
            $pesan = [];
            $kat = $this->cariKunci(Panti::KATEGORI_LKS, $r['kategori'] ?? '');
            if (($r['kategori'] ?? '') !== '' && ! $kat) {
                $pesan[] = 'Kategori "'.$r['kategori'].'" tidak dikenal. Gunakan: Anak, Lanjut Usia, atau Disabilitas.';
            }

            // Kelurahan: operator selalu terkunci pada wilayahnya sendiri.
            $kelId = null;
            if ($user->isOperator()) {
                $kelId = $user->kelurahan_id;
                $input = mb_strtolower($r['kelurahan'] ?? '');
                if ($kelId === null) {
                    $pesan[] = 'Akun operator belum terhubung ke kelurahan/desa.';
                } elseif ($input !== '' && $kelurahans->firstWhere('id', $kelId)?->nama && mb_strtolower($kelurahans->firstWhere('id', $kelId)->nama) !== $input) {
                    $pesan[] = 'Operator hanya boleh mengimpor LKS di kelurahan/desanya sendiri.';
                }
            } else {
                $cocok = $kelurahans->filter(fn ($k) => mb_strtolower($k->nama) === mb_strtolower($r['kelurahan'] ?? '')
                    && (($r['kecamatan'] ?? '') === '' || mb_strtolower($k->kecamatan) === mb_strtolower($r['kecamatan'])));
                if (($r['kelurahan'] ?? '') === '') {
                    $pesan[] = 'Kelurahan/desa wajib diisi.';
                } elseif ($cocok->count() === 0) {
                    $pesan[] = 'Kelurahan/desa "'.$r['kelurahan'].'" tidak ada di daftar Wilayah.';
                } elseif ($cocok->count() > 1) {
                    $pesan[] = 'Nama kelurahan/desa ada di lebih dari satu kecamatan. Isi kolom kecamatan.';
                } else {
                    $kelId = $cocok->first()->id;
                }
            }

            $tgl = $this->tanggal($r['tanggal_berdiri'] ?? '', $pesan, 'Tanggal berdiri');
            $status = $this->cariKunci(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'], $r['status'] ?? '') ?? (($r['status'] ?? '') === '' ? 'aktif' : null);
            if ($status === null) {
                $pesan[] = 'Status harus aktif atau nonaktif.';
            }

            $data = [
                'nama' => $r['nama'] ?? '',
                'kategori_lks' => $kat,
                'alamat' => $r['alamat'] ?? '',
                'kelurahan_id' => $kelId,
                'no_sk' => $this->kosong($r['no_sk'] ?? ''),
                'tanggal_berdiri' => $tgl,
                'telepon' => $this->kosong($r['telepon'] ?? ''),
                'email' => $this->kosong($r['email'] ?? ''),
                'nama_pengelola' => $this->kosong($r['nama_pengelola'] ?? ''),
                'kapasitas' => $this->kosong($r['kapasitas'] ?? ''),
                'status' => $status,
            ];

            $v = Validator::make($data, [
                'nama' => ['required', 'string', 'max:255'],
                'kategori_lks' => ['required', Rule::in(array_keys(Panti::KATEGORI_LKS))],
                'alamat' => ['required', 'string', 'max:1000'],
                'kelurahan_id' => ['required', 'exists:kelurahans,id'],
                'no_sk' => ['nullable', 'string', 'max:255'],
                'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
                'email' => ['nullable', 'email', 'max:255'],
                'nama_pengelola' => ['nullable', 'string', 'max:255'],
                'kapasitas' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            ], [], [
                'nama' => 'Nama', 'kategori_lks' => 'Kategori', 'alamat' => 'Alamat', 'kelurahan_id' => 'Kelurahan/desa',
                'telepon' => 'Telepon', 'email' => 'Email', 'kapasitas' => 'Kapasitas', 'status' => 'Status',
            ]);
            $pesan = array_values(array_unique(array_merge($pesan, $v->errors()->all())));

            $status2 = 'baru';
            $kunci = mb_strtolower($data['nama']).'|'.$kelId;
            if ($pesan) {
                $status2 = 'galat';
            } elseif (isset($sudahAda[$kunci])) {
                $status2 = 'duplikat';
                $pesan[] = 'LKS dengan nama dan kelurahan/desa yang sama sudah ada atau muncul lebih dari sekali di berkas.';
            } else {
                $sudahAda[$kunci] = true;
            }

            $hasil[] = [
                'baris' => $r['_baris'],
                'status' => $status2,
                'ringkas' => [$data['nama'], $kat ? Panti::KATEGORI_LKS[$kat] : '-', $r['kelurahan'] ?? ''],
                'pesan' => $pesan,
                'data' => $status2 === 'baru' ? $data : null,
            ];
        }

        return $hasil;
    }

    private function prosesKlien(array $rows, User $user): array
    {
        // Hanya LKS yang boleh dikelola user (operator: wilayahnya sendiri).
        $lks = Panti::visibleTo($user)->get(['id', 'nama', 'kategori_lks'])
            ->groupBy(fn ($p) => mb_strtolower($p->nama));

        $nikAda = Anak::whereNotNull('nik_hash')->pluck('nik_hash')->flip()->all();
        $kembar = Anak::query()->get(['panti_id', 'nama', 'tanggal_lahir'])
            ->mapWithKeys(fn ($a) => [mb_strtolower($a->nama).'|'.$a->panti_id.'|'.$a->tanggal_lahir?->format('Y-m-d') => true])->all();

        $hasil = [];

        foreach ($rows as $r) {
            $pesan = [];
            $panti = null;
            $kandidat = $lks->get(mb_strtolower($r['nama_lks'] ?? ''));
            if (! $kandidat) {
                $pesan[] = 'LKS "'.($r['nama_lks'] ?? '').'" tidak ditemukan atau berada di luar wilayah Anda.';
            } elseif ($kandidat->count() > 1) {
                $pesan[] = 'Nama LKS "'.$r['nama_lks'].'" dipakai lebih dari satu LKS. Ubah salah satunya agar unik sebelum impor.';
            } else {
                $panti = $kandidat->first();
            }

            $kat = $panti?->kategori_lks;
            $jk = $this->cariKunci(['L' => 'Laki-laki', 'P' => 'Perempuan'], $r['jenis_kelamin'] ?? '');
            $tglLahir = $this->tanggal($r['tanggal_lahir'] ?? '', $pesan, 'Tanggal lahir');
            $tglMasuk = $this->tanggal($r['tanggal_masuk'] ?? '', $pesan, 'Tanggal masuk');

            $latar = $kat ? $this->cariKunci(Anak::latarBelakang($kat), $r['latar_belakang'] ?? '') : null;
            $didik = $this->cariKunci(Anak::PENDIDIKAN, $r['pendidikan'] ?? '');
            $ragam = $this->cariKunci(Anak::JENIS_DISABILITAS, $r['ragam_disabilitas'] ?? '');
            $tingkat = $this->cariKunci(Anak::TINGKAT_KETERGANTUNGAN, $r['tingkat_ketergantungan'] ?? '');
            $status = $this->cariKunci(['aktif' => 'Aktif', 'keluar' => 'Keluar'], $r['status'] ?? '') ?? (($r['status'] ?? '') === '' ? 'aktif' : null);

            if (($r['pendidikan'] ?? '') !== '' && ! $didik) {
                $pesan[] = 'Pendidikan "'.$r['pendidikan'].'" tidak dikenal.';
            }
            if (($r['ragam_disabilitas'] ?? '') !== '' && ! $ragam) {
                $pesan[] = 'Ragam disabilitas "'.$r['ragam_disabilitas'].'" tidak dikenal.';
            }
            if (($r['tingkat_ketergantungan'] ?? '') !== '' && ! $tingkat) {
                $pesan[] = 'Tingkat ketergantungan "'.$r['tingkat_ketergantungan'].'" tidak dikenal.';
            }
            if ($status === null) {
                $pesan[] = 'Status harus aktif atau keluar.';
            }
            if ($kat && ($r['latar_belakang'] ?? '') !== '' && ! $latar) {
                $pesan[] = 'Latar belakang "'.$r['latar_belakang'].'" tidak berlaku untuk LKS kategori '.Panti::KATEGORI_LKS[$kat].'.';
            }

            $data = [
                'panti_id' => $panti?->id,
                'kategori_klien' => $kat,
                'nama' => $r['nama'] ?? '',
                'nik' => $this->kosong($r['nik'] ?? ''),
                'jenis_kelamin' => $jk,
                'tempat_lahir' => $this->kosong($r['tempat_lahir'] ?? ''),
                'tanggal_lahir' => $tglLahir,
                'kategori' => $latar,
                'pendidikan' => $kat === 'anak' ? $didik : null,
                'jenis_disabilitas' => $kat === 'disabilitas' ? $ragam : null,
                'tingkat_ketergantungan' => in_array($kat, ['lanjut_usia', 'disabilitas'], true) ? $tingkat : null,
                'nama_wali' => $this->kosong($r['nama_wali'] ?? ''),
                'tanggal_masuk' => $tglMasuk,
                'tanggal_keluar' => null,
                'status' => $status,
                'keterangan' => $this->kosong($r['keterangan'] ?? ''),
            ];

            $v = Validator::make($data, [
                'panti_id' => ['required'],
                'nama' => ['required', 'string', 'max:255'],
                'nik' => ['nullable', 'digits:16'],
                'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
                'tempat_lahir' => ['nullable', 'string', 'max:255'],
                'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
                'kategori' => ['required'],
                'jenis_disabilitas' => $kat === 'disabilitas' ? ['required'] : ['nullable'],
                'tingkat_ketergantungan' => in_array($kat, ['lanjut_usia', 'disabilitas'], true) ? ['required'] : ['nullable'],
                'nama_wali' => ['nullable', 'string', 'max:255'],
                'tanggal_masuk' => ['nullable', 'date', 'before_or_equal:today'],
                'status' => ['required', Rule::in(['aktif', 'keluar'])],
                'keterangan' => ['nullable', 'string', 'max:1000'],
            ], [
                'panti_id.required' => 'LKS tujuan belum valid.',
                'kategori.required' => 'Latar belakang wajib diisi dengan nilai yang berlaku untuk kategori LKS.',
                'jenis_disabilitas.required' => 'Ragam disabilitas wajib untuk klien kategori Disabilitas.',
                'tingkat_ketergantungan.required' => 'Tingkat ketergantungan wajib untuk klien Lanjut Usia dan Disabilitas.',
            ], [
                'nama' => 'Nama', 'nik' => 'NIK', 'jenis_kelamin' => 'Jenis kelamin', 'tanggal_lahir' => 'Tanggal lahir',
                'tanggal_masuk' => 'Tanggal masuk', 'status' => 'Status',
            ]);
            $pesan = array_merge($pesan, $v->errors()->all(), $this->aturanUsia($kat, $tglLahir));
            $pesan = array_values(array_unique($pesan));

            $status2 = 'baru';
            $kunciNama = mb_strtolower($data['nama']).'|'.$data['panti_id'].'|'.$tglLahir;
            if ($pesan) {
                $status2 = 'galat';
            } elseif ($data['nik'] && isset($nikAda[Anak::hashNik($data['nik'])])) {
                $status2 = 'duplikat';
                $pesan[] = 'NIK sudah terdaftar atau muncul lebih dari sekali di berkas.';
            } elseif (isset($kembar[$kunciNama])) {
                $status2 = 'duplikat';
                $pesan[] = 'Klien dengan nama dan tanggal lahir yang sama sudah ada di LKS ini.';
            } else {
                if ($data['nik']) {
                    $nikAda[Anak::hashNik($data['nik'])] = true;
                }
                $kembar[$kunciNama] = true;
            }

            $hasil[] = [
                'baris' => $r['_baris'],
                'status' => $status2,
                'ringkas' => [$data['nama'], $r['nama_lks'] ?? '', $kat ? Panti::KATEGORI_LKS[$kat] : '-'],
                'pesan' => $pesan,
                'data' => $status2 === 'baru' ? $data : null,
            ];
        }

        return $hasil;
    }

    /* ================================================================== */
    /* Menyimpan                                                          */
    /* ================================================================== */

    /** @return array{dibuat: int, dilewati: int} */
    public function simpan(string $jenis, array $hasil, User $user): array
    {
        $dibuat = 0;
        $dilewati = 0;

        DB::transaction(function () use ($jenis, $hasil, $user, &$dibuat, &$dilewati) {
            foreach ($hasil['baris'] as $b) {
                if ($b['status'] !== 'baru' || ! $b['data']) {
                    $dilewati++;

                    continue;
                }

                if ($jenis === 'lks') {
                    // Periksa ulang saat menyimpan, jaga-jaga ada data masuk di antara pratinjau dan simpan.
                    $ada = Panti::where('nama', $b['data']['nama'])->where('kelurahan_id', $b['data']['kelurahan_id'])->exists();
                    if ($ada) {
                        $dilewati++;

                        continue;
                    }
                    $m = new Panti($b['data']);
                } else {
                    if ($b['data']['nik'] && Anak::where('nik_hash', Anak::hashNik($b['data']['nik']))->exists()) {
                        $dilewati++;

                        continue;
                    }
                    $m = new Anak($b['data']);
                }

                $m->created_by = $user->id;
                $m->save();
                $dibuat++;
            }
        });

        ActivityLog::record('impor.selesai', "Impor {$jenis}: {$dibuat} baris disimpan, {$dilewati} dilewati (duplikat/galat).");

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /* ================================================================== */
    /* Pembantu                                                           */
    /* ================================================================== */

    private function kosong(string $v): ?string
    {
        return trim($v) === '' ? null : trim($v);
    }

    /** Cocokkan nilai dengan kode atau label (tanpa membedakan huruf besar/kecil, spasi, garis bawah). */
    private function cariKunci(array $daftar, string $nilai): ?string
    {
        $norm = fn (string $s) => preg_replace('/[\s_\-\/]+/', '', mb_strtolower(trim($s)));
        $target = $norm($nilai);
        if ($target === '') {
            return null;
        }

        $alias = ['lansia' => 'lanjutusia', 'lakilaki' => 'l', 'perempuan' => 'p', 'wanita' => 'p', 'pria' => 'l'];
        $target = $alias[$target] ?? $target;

        foreach ($daftar as $kode => $label) {
            if ($norm((string) $kode) === $target || $norm((string) $label) === $target) {
                return (string) $kode;
            }
        }

        return null;
    }

    private function tanggal(string $nilai, array &$pesan, string $label): ?string
    {
        $nilai = trim($nilai);
        if ($nilai === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y'] as $format) {
            try {
                $t = Carbon::createFromFormat('!'.$format, $nilai);
            } catch (\Throwable) {
                continue; // Carbon 3 melempar galat bila format tidak cocok
            }

            if ($t && $t->format($format) === $nilai) {
                return $t->format('Y-m-d');
            }
        }

        $pesan[] = "{$label} \"{$nilai}\" tidak valid. Gunakan format 2020-01-31 atau 31/01/2020.";

        return null;
    }

    private function aturanUsia(?string $kat, ?string $tglLahir): array
    {
        if (! $kat || ! $tglLahir) {
            return [];
        }

        $umur = Carbon::parse($tglLahir)->age;
        $anakMaks = (int) config('diskat.usia.anak_maks', 18);
        $lansiaMin = (int) config('diskat.usia.lansia_min', 60);

        if ($kat === 'anak' && $umur > $anakMaks) {
            return ["Klien kategori Anak berusia maksimal {$anakMaks} tahun (usia dari tanggal lahir ini {$umur} tahun)."];
        }
        if ($kat === 'lanjut_usia' && $umur < $lansiaMin) {
            return ["Klien kategori Lanjut Usia berusia minimal {$lansiaMin} tahun (usia dari tanggal lahir ini {$umur} tahun)."];
        }

        return [];
    }
}
