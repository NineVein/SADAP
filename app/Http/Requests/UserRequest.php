<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');
        $creating = $user === null;

        $isOperatorRole = fn () => Role::whereKey($this->input('role_id'))->value('name') === Role::OPERATOR;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'kelurahan_id' => [Rule::requiredIf($isOperatorRole), 'nullable', 'exists:kelurahans,id'],
            'password' => [
                $creating ? 'required' : 'nullable',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'alamat email',
            'role_id' => 'peran (role)',
            'kelurahan_id' => 'wilayah kelurahan/desa',
            'password' => 'kata sandi',
            'is_active' => 'status aktif',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'role_id.required' => 'Pilih peran untuk pengguna ini.',
            'kelurahan_id.required' => 'Operator kelurahan/desa wajib dipilihkan wilayah tugas.',
            'password.required' => 'Kata sandi wajib diisi untuk akun baru.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }

    public function validatedNormalized(): array
    {
        $data = $this->validated();
        $data['email'] = strtolower($data['email']);

        $roleName = Role::whereKey($data['role_id'])->value('name');
        if ($roleName !== Role::OPERATOR) {
            $data['kelurahan_id'] = null;
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}

