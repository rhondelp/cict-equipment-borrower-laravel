<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('user')->get();

        return view('admin.notification', compact('notifications'));
    }
}
