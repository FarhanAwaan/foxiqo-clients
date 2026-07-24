<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies Retell AI's webhook signature: header "X-Retell-Signature: v={timestamp_ms},d={hex_digest}",
 * digest = HMAC-SHA256(raw_body + timestamp, retell_api_key), hex-encoded.
 * See https://docs.retellai.com/features/secure-webhook
 *
 * Fails open when no key is configured yet (nothing to verify against — this happens
 * before an agency has set up Retell integration at all) so it never blocks setup or
 * breaks existing traffic. Fails closed only when a signature is present but wrong,
 * which is unambiguously a forged or corrupted request.
 */
class VerifyWebhookSignature
{
    protected const MAX_CLOCK_SKEW_MS = 5 * 60 * 1000;

    public function handle(Request $request, Closure $next): Response
    {
        $key = SystemSetting::getValue('retell_webhook_secret') ?: SystemSetting::getValue('retell_api_key');

        if (!$key) {
            Log::info('Retell webhook received before an API key is configured — skipping signature verification.', [
                'ip' => $request->ip(),
            ]);
            return $next($request);
        }

        $header = $request->header('X-Retell-Signature');

        if (!$header || !preg_match('/^v=(\d+),d=(.+)$/', $header, $matches)) {
            Log::warning('Retell webhook missing or malformed X-Retell-Signature header.', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return $next($request);
        }

        [, $timestamp, $providedDigest] = $matches;
        $nowMs = (int) round(microtime(true) * 1000);

        if (abs($nowMs - (int) $timestamp) > self::MAX_CLOCK_SKEW_MS) {
            Log::warning('Retell webhook signature timestamp outside the allowed window — rejecting.', [
                'ip' => $request->ip(),
                'timestamp' => $timestamp,
            ]);
            return response()->json(['error' => 'Signature expired'], 401);
        }

        $expectedDigest = hash_hmac('sha256', $request->getContent() . $timestamp, $key);

        if (!hash_equals($expectedDigest, $providedDigest)) {
            Log::warning('Retell webhook signature verification failed — rejecting.', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
