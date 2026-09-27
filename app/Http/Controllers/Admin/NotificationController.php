<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /**
     * Everything the Emails page's side panel shows for one email: who it went from/to/cc/bcc,
     * when, its status and — separately, via body() — the message itself. JSON, fetched when a
     * row is opened, so the list page stays light.
     */
    public function show(Notification $notification): JsonResponse
    {
        $notification->load('company');

        // Why there's no message to show, so the panel can say something useful instead of nothing.
        $noBodyReason = match (true) {
            $notification->html_body !== null => null,
            !$notification->keepsContent() => 'private_link',
            $notification->status === 'queued' => 'queued',
            default => 'not_recorded',
        };

        return response()->json([
            'type' => str_replace('_', ' ', ucfirst($notification->type)),
            'status' => $notification->status,
            'error' => $notification->error,
            'subject' => $notification->subject,
            'from' => $notification->addresses('from'),
            'to' => $notification->addresses('to'),
            'cc' => $notification->addresses('cc'),
            'bcc' => $notification->addresses('bcc'),
            'reply_to' => $notification->addresses('reply_to'),
            'queued_at' => $notification->created_at?->format('M d, Y h:i:s A'),
            'sent_at' => $notification->sent_at?->format('M d, Y h:i:s A'),
            'customer' => $notification->company ? [
                'name' => $notification->company->name,
                'url' => route('admin.companies.show', $notification->company),
            ] : null,
            // The one-line summary every email has had all along — the fallback when there's no copy.
            'summary' => $notification->body,
            'body_url' => $notification->html_body !== null ? route('admin.notifications.body', $notification) : null,
            'no_body_reason' => $noBodyReason,
        ]);
    }

    /**
     * The stored message as a standalone HTML page, meant only for the panel's iframe. It is
     * untrusted content (it holds customer-supplied names, notes, URLs), so it's served locked
     * down: no scripts, no network access except images/fonts, no forms, only framable by us.
     * Links are forced to open in a new tab so a click can't navigate the iframe (or the portal).
     */
    public function body(Notification $notification): Response
    {
        abort_if($notification->html_body === null, 404);

        $html = preg_replace('/<a\s/i', '<a target="_blank" rel="noopener noreferrer" ', $notification->html_body);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src https: data:; font-src https: data:; form-action 'none'; frame-ancestors 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
