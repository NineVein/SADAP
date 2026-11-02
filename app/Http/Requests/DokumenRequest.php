<?php

namespace App\Http\Requests;

use App\Models\Dokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'panti_id' => ['required', 'exists:pantis,id'],
            'jenis' => ['required', Rule::in(array_keys(Dokumen::JENIS))],
            'judul' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    public function attributes(): array
    {
        return [
            'panti_id' => 'panti asuhan',
            'jenis' => 'jenis dokumen',
            'judul' => 'judul dokumen',
            'file' => 'berkas dokumen',
        ];
    }

    public function messages(): array
    {
        return [
            'panti_id.required' => 'Pilih panti asuhan pemilik dokumen.',
            'jenis.required' => 'Pilih jenis dokumen (SK pendirian, akta, dll).',
            'judul.required' => 'Judul dokumen wajib diisi.',
            'file.required' => 'Pilih berkas file yang akan diunggah.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
            'file.mimes' => 'Format file yang didukung: PDF, JPG, PNG, DOC, atau DOCX.',
        ];
    }
}

