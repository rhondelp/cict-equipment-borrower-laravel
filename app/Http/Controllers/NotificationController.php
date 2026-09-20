<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('user')
            ->orderByDesc('send_date')
            ->orderByDesc('id')
            ->get();

        return view('admin.notification', compact('notifications'));
    }
}
