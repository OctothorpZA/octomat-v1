<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        // Build query with filters
        $query = AuditLog::with(['admin', 'targetUser'])
            ->orderBy('timestamp', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        // Filter by admin
        if ($request->filled('admin_id')) {
            $query->byAdmin($request->admin_id);
        }

        // Filter by target user
        if ($request->filled('target_user_id')) {
            $query->byTargetUser($request->target_user_id);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->where('timestamp', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('timestamp', '<=', $request->to_date.' 23:59:59');
        }

        $auditLogs = $query->paginate(25)->withQueryString();

        return Inertia::render('admin/audit-log', [
            'auditLogs' => $auditLogs,
            'filters' => $request->only([
                'action', 'admin_id', 'target_user_id', 'role', 'from_date', 'to_date',
            ]),
            'stats' => app(\App\Services\AuditService::class)->getAuditStats(),
        ]);
    }
}
