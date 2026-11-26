<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function userActivity(Request $request): View
    {
        $query = ActivityLog::query()
            ->where('type', 'user_action')
            ->with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by action type (HTTP method)
        if ($request->filled('action_type')) {
            $query->where('action', $request->input('action_type'));
        }

        // Filter by guard
        if ($request->filled('guard')) {
            $query->where('guard', $request->input('guard'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->input('search').'%');
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        // Get distinct action types for the filter dropdown
        $actionTypes = ActivityLog::query()
            ->where('type', 'user_action')
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('admin.logs.user-activity', [
            'logs' => $logs,
            'guard' => $request->input('guard'),
            'actionTypes' => $actionTypes,
        ]);
    }

    public function systemErrors(Request $request): View
    {
        $query = ActivityLog::query()
            ->where('type', 'system_error');

        // Filter by error type (exception class)
        if ($request->filled('error_type')) {
            $query->where('action', $request->input('error_type'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search by exception class or message
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request): void {
                $search = $request->input('search');
                $q->where('action', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        // Get distinct error types for the filter dropdown
        $errorTypes = ActivityLog::query()
            ->where('type', 'system_error')
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('admin.logs.system-errors', [
            'logs' => $logs,
            'errorTypes' => $errorTypes,
        ]);
    }
}
