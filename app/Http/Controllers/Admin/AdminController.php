<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sort = $request->validate([
            'sort' => ['nullable', Rule::in([
                'name_asc', 'name_desc',
                'email_asc', 'email_desc',
                'registered_asc', 'registered_desc',
                'role_asc', 'role_desc',
            ])],
        ])['sort'] ?? 'name_asc';
        $sortDirection = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortKey = substr($sort, 0, -strlen('_'.$sortDirection));
        $sortColumn = match ($sortKey) {
            'email' => 'email',
            'registered' => 'created_at',
            'role' => 'is_admin',
            default => 'name',
        };

        return view('admin.index', [
            'users' => User::query()
                ->orderBy($sortColumn, $sortDirection)
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString(),
            'sort' => $sort,
            'userCount' => User::query()->count(),
            'profileCount' => SearchProfile::query()->count(),
            'activityCount' => SearchProfileActivity::query()->count(),
        ]);
    }
}
