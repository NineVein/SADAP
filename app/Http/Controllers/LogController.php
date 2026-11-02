<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('deskripsi', 'like', $like)->orWhere('aksi', 'like', $like));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Log/Index', [
            'logs' => $logs,
            'filters' => ['search' => $search],
        ]);
    }
}
