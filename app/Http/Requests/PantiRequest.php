<?php

namespace App\Http\Requests;

use App\Models\Panti;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PantiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        /** @var Panti|null $panti */
        $panti = $this->route('panti');
        $kelurahanId = $user->isOperator() ? $user->kelurahan_id : $this->input('kelurahan_id');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pantis', 'nama')
                    ->where('kelurahan_id', $kelurahanId)
                    ->whereNull('deleted_at')
                    ->ignore($panti?->id),
            ],
            'alamat' => ['required', 'string', 'max:1000'],
            'kelurahan_id' => $user->isOperator() ? ['nullable'] : ['required', 'exists:kelurahans,id'],
            'no_sk' => ['nullable', 'string', 'max:255'],
            'tanggal_berdiri' => ['nullable', 'date', 'before_or_equal:today'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'nama_pengelola' => ['nullable', 'string', 'max:255'],
            'kapasitas' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama panti',
            'alamat' => 'alamat panti',
            'kelurahan_id' => 'kelurahan/desa',
            'no_sk' => 'nomor SK',
            'tanggal_berdiri' => 'tanggal berdiri',
            'telepon' => 'nomor telepon',
            'email' => 'alamat email',
            'nama_pengelola' => 'nama pengelola',
            'kapasitas' => 'kapasitas daya tampung',
            'status' => 'status operasional',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama panti wajib diisi.',
            'nama.unique' => 'Nama panti sudah terdaftar pada kelurahan/desa ini.',
            'alamat.required' => 'Alamat lengkap panti wajib diisi.',
            'kelurahan_id.required' => 'Pilih kelurahan atau desa wilayah panti.',
            'kelurahan_id.exists' => 'Wilayah yang dipilih tidak valid.',
            'tanggal_berdiri.before_or_equal' => 'Tanggal berdiri tidak boleh melebihi hari ini.',
            'telepon.regex' => 'Format nomor telepon tidak valid (hanya angka, tanda plus, minus, atau spasi).',
            'email.email' => 'Format alamat email tidak valid.',
            'kapasitas.min' => 'Kapasitas daya tampung minimal 1 anak.',
            'status.required' => 'Pilih status operasional panti.',
        ];
    }
}

