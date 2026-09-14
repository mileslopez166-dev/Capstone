<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $query = trim((string) $request->query('q', ''));
        $normalized = Str::lower($query);

        if ($query === '') {
            return redirect()->route('admin.dashboard');
        }

        if (Str::contains($normalized, ['token', 'request', 'pending', 'approval'])) {
            return redirect()->route('admin.token-requests.index', ['search' => $query]);
        }

        $role = match (true) {
            Str::contains($normalized, 'student') => 'student',
            Str::contains($normalized, 'teacher') => 'teacher',
            Str::contains($normalized, 'admin') => 'admin',
            default => null,
        };

        $parameters = $role ? ['role' => $role] : ['search' => $query];

        return redirect()->to(route('admin.dashboard', $parameters).'#user-management');
    }
}