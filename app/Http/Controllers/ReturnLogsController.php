<?php

namespace App\Http\Controllers;

use App\Models\ReturnLog;

class ReturnLogsController extends Controller
{
    public function index()
    {
        $logs = ReturnLog::with(['borrower', 'receiver', 'equipment', 'borrowTransaction'])
            ->orderBy('return_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.logs', compact('logs'));
    }
}
