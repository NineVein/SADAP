<?php

namespace App\Http\Requests;

use App\Models\Kelurahan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelurahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var Kelurahan|null $kelurahan */
        $kelurahan = $this->route('kelurahan');

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kelurahans', 'nama')
                    ->where('kecamatan', $this->input('kecamatan'))
                    ->ignore($kelurahan?->id),
            ],
            'kecamatan' => ['required', 'string', 'max:100'],
            'tipe' => ['required', Rule::in(['kelurahan', 'desa'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama kelurahan/desa',
            'kecamatan' => 'nama kecamatan',
            'tipe' => 'jenis wilayah',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kelurahan atau desa wajib diisi.',
            'nama.unique' => 'Nama kelurahan/desa ini sudah ada di kecamatan yang sama.',
            'kecamatan.required' => 'Nama kecamatan wajib diisi.',
            'tipe.required' => 'Pilih jenis wilayah (kelurahan atau desa).',
        ];
    }
}

