<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengurusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'panti_id' => ['required', 'exists:pantis,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'panti_id' => 'panti asuhan',
            'nama' => 'nama pengurus',
            'jabatan' => 'jabatan / posisi',
            'telepon' => 'nomor telepon',
            'email' => 'alamat email',
            'alamat' => 'alamat domisili',
        ];
    }

    public function messages(): array
    {
        return [
            'panti_id.required' => 'Pilih panti asuhan tempat pengurus bertugas.',
            'nama.required' => 'Nama lengkap pengurus wajib diisi.',
            'jabatan.required' => 'Jabatan pengurus wajib diisi.',
            'telepon.regex' => 'Format nomor telepon tidak valid.',
            'email.email' => 'Format alamat email tidak valid.',
        ];
    }
}

