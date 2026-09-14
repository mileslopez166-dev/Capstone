<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request): RedirectResponse
    {
        $request->user()
            ->appNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        return back();
    }
}