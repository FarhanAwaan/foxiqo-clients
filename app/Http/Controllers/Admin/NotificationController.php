<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every outbound email the portal has sent, queued, or failed to send — see
 * EmailService::createNotificationAndDispatch() (where the row is created) and
 * SendEmailJob (where its status/error get filled in once the job actually runs).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Notification::with(['company', 'user'])->where('channel', 'email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('data', 'like', "%{$search}%");
            });
        }

        $notifications = $query->latest('created_at')->paginate(25)->withQueryString();
        $types = Notification::where('channel', 'email')->distinct()->orderBy('type')->pluck('type');

        $counts = [
            'sent' => Notification::where('channel', 'email')->where('status', 'sent')->count(),
            'queued' => Notification::where('channel', 'email')->where('status', 'queued')->count(),
            'failed' => Notification::where('channel', 'email')->where('status', 'failed')->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'types', 'counts'));
    }
}
