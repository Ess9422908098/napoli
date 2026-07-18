<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        return ActivityLog::query()
            ->with('user')
            ->when($request->query('subject_type'), fn ($q, $type) => $q->where('subject_type', $type))
            ->orderByDesc('created_at')
            ->paginate(50);
    }
}
