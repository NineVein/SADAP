<?php

namespace App\Policies;

use App\Models\Panti;
use App\Models\User;

class PantiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== null;
    }

    public function view(User $user, Panti $panti): bool
    {
        if ($user->isOperator()) {
            return $user->kelurahan_id !== null && $user->kelurahan_id === $panti->kelurahan_id;
        }

        return $user->isAdmin() || $user->isSupervisor();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->isOperator() && $user->kelurahan_id !== null);
    }

    /** Profil panti: operator hanya bisa mengubah selagi draft atau ditolak. */
    public function update(User $user, Panti $panti): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOperator()
            && $this->view($user, $panti)
            && in_array($panti->validation_status, [Panti::DRAFT, Panti::DITOLAK], true);
    }

    public function delete(User $user, Panti $panti): bool
    {
        return $user->isAdmin();
    }

    /** Kelola anak, pengurus, dan dokumen milik panti. */
    public function manageData(User $user, Panti $panti): bool
    {
        return $user->isAdmin() || ($user->isOperator() && $this->view($user, $panti));
    }

    public function submit(User $user, Panti $panti): bool
    {
        return $this->update($user, $panti);
    }

    /** Melaporkan desil anak: operator di wilayahnya, admin, dan supervisor. */
    public function reportDesil(User $user, Panti $panti): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || ($user->isOperator() && $this->view($user, $panti));
    }

    public function reviewDesil(User $user, Panti $panti): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    /** Menyetujui atau menolak pengajuan validasi. */
    public function review(User $user, Panti $panti): bool
    {
        return ($user->isAdmin() || $user->isSupervisor())
            && $panti->validation_status === Panti::DIAJUKAN;
    }
}
