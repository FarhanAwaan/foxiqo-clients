<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies Paddle's webhook signature: header "Paddle-Signature: ts={timestamp},h1={hex_digest}",
 * digest = HMAC-SHA256("{timestamp}:{raw_body}", paddle_webhook_secret), hex-encoded.
 * See https://developer.paddle.com/webhooks/signature-verification
 *
 * Same convention as VerifyWebhookSignature (Retell): fails open when no secret is
 * configured yet, fails closed only when a signature is present but wrong.
 */
class VerifyPaddleWebhookSignature
{
    protected const MAX_CLOCK_SKEW_SECONDS = 5 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = SystemSetting::getValue('paddle_webhook_secret');

        if (!$secret) {
            Log::info('Paddle webhook received before a webhook secret is configured — skipping signature verification.', [
                'ip' => $request->ip(),
            ]);
            return $next($request);
        }

        $header = $request->header('Paddle-Signature');

        if (!$header || !preg_match('/ts=(\d+);h1=([a-f0-9]+)/', $header, $matches)) {
            Log::warning('Paddle webhook missing or malformed Paddle-Signature header.', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        [, $timestamp, $providedDigest] = $matches;

        if (abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW_SECONDS) {
            Log::warning('Paddle webhook signature timestamp outside the allowed window — rejecting.', [
                'ip' => $request->ip(),
                'timestamp' => $timestamp,
            ]);
            return response()->json(['error' => 'Signature expired'], 401);
        }

        $expectedDigest = hash_hmac('sha256', "{$timestamp}:{$request->getContent()}", $secret);

        if (!hash_equals($expectedDigest, $providedDigest)) {
            Log::warning('Paddle webhook signature verification failed — rejecting.', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
