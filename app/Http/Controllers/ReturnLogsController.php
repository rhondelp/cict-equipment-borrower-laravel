<?php

namespace App\Http\Controllers;

use App\Models\ReturnLog;

class ReturnLogsController extends Controller
{
    public function index()
    {
        $logs = ReturnLog::with(['borrower', 'receiver', 'equipment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.logs', compact('logs'));
    }
}
