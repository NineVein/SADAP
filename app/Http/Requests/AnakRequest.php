<?php

namespace App\Http\Requests;

use App\Models\Anak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nik') && $this->input('nik') !== null) {
            $cleaned = preg_replace('/\D/', '', (string) $this->input('nik'));
            $this->merge(['nik' => $cleaned !== '' ? $cleaned : null]);
        }
    }

    public function rules(): array
    {
        /** @var Anak|null $anak */
        $anak = $this->route('anak');

        return [
            'panti_id' => ['required', 'exists:pantis,id'],
            'nik' => [
                'nullable',
                'digits:16',
                Rule::unique('anaks', 'nik')
                    ->whereNull('deleted_at')
                    ->ignore($anak?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'kategori' => ['required', Rule::in(array_keys(Anak::KATEGORI))],
            'pendidikan' => ['nullable', Rule::in(array_keys(Anak::PENDIDIKAN))],
            'nama_wali' => ['nullable', 'string', 'max:255'],
            'tanggal_masuk' => ['nullable', 'date', 'before_or_equal:today'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'status' => ['required', Rule::in(['aktif', 'keluar'])],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'panti_id' => 'panti asuhan',
            'nik' => 'NIK',
            'nama' => 'nama lengkap anak',
            'jenis_kelamin' => 'jenis kelamin',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'kategori' => 'kategori anak',
            'pendidikan' => 'pendidikan terakhir',
            'nama_wali' => 'nama orang tua / wali',
            'tanggal_masuk' => 'tanggal masuk panti',
            'tanggal_keluar' => 'tanggal keluar panti',
            'status' => 'status tinggal',
            'keterangan' => 'catatan / keterangan',
        ];
    }

    public function messages(): array
    {
        return [
            'panti_id.required' => 'Pilih panti asuhan tempat anak bernaung.',
            'nik.digits' => 'Nomor Induk Kependudukan (NIK) harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem.',
            'nama.required' => 'Nama lengkap anak wajib diisi.',
            'jenis_kelamin.required' => 'Pilih jenis kelamin anak.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'tanggal_masuk.before_or_equal' => 'Tanggal masuk tidak boleh di masa mendatang.',
            'tanggal_keluar.after_or_equal' => 'Tanggal keluar harus sama atau setelah tanggal masuk.',
            'kategori.required' => 'Pilih kategori anak.',
            'status.required' => 'Pilih status anak (aktif atau keluar).',
        ];
    }

    /**
     * Data ternormalisasi siap simpan
     */
    public function validatedNormalized(): array
    {
        $data = $this->validated();

        if (empty($data['nik'])) {
            $data['nik'] = null;
        }

        if ($data['status'] === 'aktif') {
            $data['tanggal_keluar'] = null;
        }

        return $data;
    }
}

