<?php

namespace App\Http\Middleware;

use App\Models\Anak;
use App\Models\Panti;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => fn () => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name,
                    'role_label' => $user->role?->label,
                    'kelurahan' => $user->kelurahan?->nama,
                ] : null,
            ],

            // Angka kecil di menu Validasi: antrean untuk reviewer, penolakan untuk operator.
            'badges' => fn () => $user ? [
                'validasi' => $user->isOperator()
                    ? Panti::visibleTo($user)->where('validation_status', Panti::DITOLAK)->count()
                    : Panti::where('validation_status', Panti::DIAJUKAN)->count(),
            ] : null,

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
