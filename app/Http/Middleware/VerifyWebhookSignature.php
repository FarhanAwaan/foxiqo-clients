<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;

class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * Simple signature verification: each company has their own signature string
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get signature from header
        $providedSignature = $request->header('X-Webhook-Signature');

        if (!$providedSignature) {
            \Log::warning('Webhook request missing signature', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json(['error' => 'Missing signature header'], 401);
        }

        // Extract company ID from the route
        $companyId = $request->route('company');

        if (!$companyId) {
            return response()->json(['error' => 'Company ID not found in request'], 400);
        }

        // Get the company and verify signature
        $company = Company::find($companyId);

        if (!$company) {
            \Log::warning('Webhook request for non-existent company', [
                'company_id' => $companyId,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Company not found'], 404);
        }

        if (!$company->webhook_signature) {
            \Log::warning('Company does not have webhook signature configured', [
                'company_id' => $companyId,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Webhook not configured for this company'], 403);
        }

        // Simple string comparison - provided signature must match company's signature
        if ($providedSignature !== $company->webhook_signature) {
            \Log::warning('Webhook signature verification failed', [
                'company_id' => $companyId,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Signature valid, proceed with request
        return $next($request);
    }
}
