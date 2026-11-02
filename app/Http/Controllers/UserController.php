<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Kelurahan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Manajemen pengguna: hanya administrator (dijaga middleware role:admin). */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $roleId = (int) $request->query('role_id');

        $users = User::query()
            ->with(['role:id,name,label', 'kelurahan:id,nama'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($roleId > 0, fn ($q) => $q->where('role_id', $roleId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => $u->is_active,
                'role' => $u->role?->only(['id', 'name', 'label']),
                'kelurahan' => $u->kelurahan?->nama,
                'is_self' => $u->id === auth()->id(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search, 'role_id' => $roleId ?: ''],
            'roles' => Role::orderBy('id')->get(['id', 'name', 'label']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Form', [
            'user' => null,
            'roles' => Role::orderBy('id')->get(['id', 'name', 'label']),
            'kelurahans' => $this->kelurahans(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request, null, true));
        $data['email'] = strtolower($data['email']);
        $data = $this->normalize($data);

        $user = User::create($data);

        ActivityLog::record('user.dibuat', "Membuat akun {$user->email}", $user);

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Form', [
            'user' => $user->only(['id', 'name', 'email', 'role_id', 'kelurahan_id', 'is_active']),
            'roles' => Role::orderBy('id')->get(['id', 'name', 'label']),
            'kelurahans' => $this->kelurahans(),
            'isSelf' => $user->id === auth()->id(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $user, false));
        $data['email'] = strtolower($data['email']);
        $data = $this->normalize($data);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Admin tidak boleh menonaktifkan atau menurunkan role dirinya sendiri.
        if ($user->is($request->user())) {
            $data['is_active'] = true;
            $data['role_id'] = $user->role_id;
            $data['kelurahan_id'] = $user->kelurahan_id;
        }

        $user->update($data);

        ActivityLog::record('user.diubah', "Memperbarui akun {$user->email}", $user);

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLog::record(
            $user->is_active ? 'user.diaktifkan' : 'user.dinonaktifkan',
            ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." akun {$user->email}",
            $user
        );

        return back()->with('success', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    private function rules(Request $request, ?User $user, bool $creating): array
    {
        $isOperatorRole = fn () => Role::whereKey($request->input('role_id'))->value('name') === Role::OPERATOR;

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

    /** Hanya operator yang terikat ke kelurahan/desa. */
    private function normalize(array $data): array
    {
        $roleName = Role::whereKey($data['role_id'])->value('name');

        if ($roleName !== Role::OPERATOR) {
            $data['kelurahan_id'] = null;
        }

        return $data;
    }

    private function kelurahans()
    {
        return Kelurahan::orderBy('kecamatan')->orderBy('nama')->get(['id', 'nama', 'kecamatan', 'tipe']);
    }
}
