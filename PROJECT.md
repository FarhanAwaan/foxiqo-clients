# Foxiqo Client Portal

A Laravel 11 (PHP 8.2) SaaS billing and management portal for an AI voice-agent
business built on **Retell AI**. An agency admin manages client companies,
their AI phone agents, subscription plans, invoices, and payments (including
manual bank-transfer / Payoneer receipts). Each client company gets a portal
to view their agents, call recordings/transcripts, bills, and (where connected)
booked appointments.

This document exists to give an AI assistant (or a new developer) full
context on the product and codebase before making changes.

---

## 1. Portal Features (business / user-facing)

### Admin portal
- **Dashboard** — hero revenue metric, active subscriptions/companies counts, pending payments, this/last month revenue/cost/profit/margin comparison, recent invoices and subscriptions.
- **Company management** — full CRUD for client companies (billing info, address, status, notes), per-company webhook signature regeneration, **white-label branding** (logo upload, brand color — injected as CSS variables into that company's customer portal).
- **User management** — full CRUD for users, role assignment (admin/customer), resend invitation email (token-based signup links), **admin-initiated password reset** (generates a secure reset link and emails it to the user — no plaintext passwords are ever emailed).
- **Agent management** — full CRUD for AI voice agents (inbound/outbound/both, cost per minute, phone number), live "refresh" button for recent calls, per-agent call-volume and sentiment charts with date-range filters (today/yesterday/last 7/last 30/custom), **per-agent missed-call email alerts** (toggle + optional dedicated recipient, falls back to the company's billing email), **calendar connection** (Google Calendar OAuth or Cal.com API key) for auto-booking appointments Retell surfaces during a call.
- **Call history & playback** — call detail view/offcanvas with transcript, summary, sentiment, and an audio player for the recording (with an enlarge/expand view, safe-area-aware close button for notched phones).
- **Plans** — full CRUD for subscription plans/pricing tiers, including per-company custom plans.
- **Subscriptions** — CRUD, activate/cancel, usage tracking, **free trial support** (start/warn/expire), **circuit breaker** that flags a subscription when usage crosses a configurable overage threshold (default 150% of included minutes). Renewal is **blocked** (with a payment-reminder email sent instead) if the current period's invoice hasn't been paid — see Known Gaps history below.
- **Invoices** — list/view, send payment link, mark paid, automatic overdue marking.
- **Payment receipts** — review queue for manually uploaded bank-transfer receipts (approve/reject with reason, preview/download the uploaded file).
- **Revenue reporting** — per-agent / per-company / system-wide revenue, cost, and profit-margin reporting with date-range and per-company filtering. Company filter properly scopes the summary cards (not just the drill-down table); system-wide totals are computed live from call/invoice data and reconcile exactly with the per-company breakdown.
- **Appointments** — bookings Retell extracts from a call's post-call analysis (customer name/phone/email, requested date/time) are created automatically on the connected calendar (Google Calendar or Cal.com) via a shared provider interface.
- **System settings** — company branding defaults, Retell API key/webhook secret, Stripe keys (unused pending a Stripe account), Payoneer keys, Google Calendar OAuth client ID/secret, invoice due days, payment link expiry, circuit breaker threshold — all stored encrypted in the database, not `.env`.
- **Audit log** — human-readable log of admin/user actions across the system.
- **Log viewer** — in-browser application log viewer (`opcodesio/log-viewer`).
- **Dark mode** — portal-wide light/dark theme toggle (persisted in `localStorage`), applied before first paint to avoid a flash of the wrong theme.

### Customer portal (per client company)
- **Dashboard** — aggregate minutes used/included across their agents, active subscriptions, recent calls.
- **Agents** — read-only list/detail view with the same call-volume and sentiment charts, scoped to their own company.
- **Call history** — per-agent call list and call detail (transcript, recording playback).
- **Subscriptions** — read-only view of their plan and usage.
- **Invoices** — read-only list/detail of their billing history.
- **Self-service password change** — from their profile page, with current-password confirmation.
- **Branding** — if their company has a logo/brand color configured, the customer portal reflects it.

### Billing / payments (public, tokenized links — no login required)
- Tokenized payment link sent per invoice (via admin or automatically).
- Online payment flow, plus a manual **bank transfer / Payoneer** path showing all 6 bank fields (bank name, bank address, account holder, account number, routing number, account type — no SWIFT, domestic US only) and allowing the customer to upload a receipt for admin review.
- Handles already-paid, expired-link, and pending-receipt states with dedicated pages.

### Automated lifecycle (scheduled daily)
- Convert expired trials to paid subscriptions and issue invoices.
- Send "trial ending soon" warning emails.
- Process subscription renewals — **skips and sends a payment reminder instead of renewing** if the current period is unpaid.
- Send "subscription expiring soon" notifications.
- Mark overdue invoices.
- Full email lifecycle: welcome, invitation, password reset, trial started/ending/expired, subscription created/activated/renewed/cancelled/expiry-warning, payment link, payment reminder, payment confirmation, usage alert, missed-call alert, receipt uploaded/approved/rejected (19 Mailables total).

### Admin utilities
- `company:purge` CLI command — permanently deletes **all** data for a company (agents, subscriptions, calls, invoices, payments, receipts, billing cycles, audit logs, users, storage files). Supports `--dry-run` and `--force`. Marked for testing/admin use only — destructive.
- `calls:backfill-cost` CLI command — re-fetches `retell_cost` from the Retell API for existing call logs, correcting a historical unit-conversion bug (see Known Gaps history). Supports `--dry-run`.

---

## 2. Technical Architecture

### Stack
- **Backend**: Laravel 11, PHP 8.2
- **Database**: SQLite by default (`DB_CONNECTION=sqlite`), MySQL supported via config
- **Frontend**: Server-rendered Blade views. **Self-hosted via Vite** — `@tabler/core` (npm package, not CDN) built through `resources/css/app.css` + `resources/js/app.js`, plus `resources/css/tokens.css` (a full design-token system: color scale, spacing, typography, elevation, motion) layered on top of Tabler's own CSS variables. Tabler's bundled ESM JS (`tabler.esm.js`) provides Bootstrap 5 behavior (dropdowns, offcanvas, modals) — the standalone `bootstrap` npm package is deliberately **not** imported alongside it (it registers a duplicate set of data-api listeners that fight the bundled copy). jQuery 3.7.1 and Chart.js 4.4.0 remain CDN-loaded for legacy `public/js/custom.js` and the analytics charts. No SPA framework (no Livewire/Vue/React/Alpine).
- **Dark mode**: Bootstrap 5.3's `data-bs-theme` attribute mechanism; a synchronous inline `<script>` in `<head>` sets it before first paint (avoids a flash of the wrong theme); a header toggle button persists the choice to `localStorage`.
- **Page loader**: `position: fixed` full-viewport loader (logo + animated connecting dots) shown for a minimum ~1.8s on navigation, with a card stagger-in reveal timed to the loader's fade rather than `DOMContentLoaded`.
- **Queue**: `QUEUE_CONNECTION=database`; no persistent worker — `routes/console.php` schedules `queue:work database --tries=3 --timeout=90 --sleep=3 --stop-when-empty` to run every minute via cron/scheduler with `withoutOverlapping()`.
- **Cache/Session**: database-backed (`CACHE_STORE=database`, `SESSION_DRIVER=database`).
- **Log viewer**: `opcodesio/log-viewer` (default config, default route).

### Domain model (`app/Models`)
| Model | Purpose |
|---|---|
| `User` | Auth user; `role` (admin/customer), belongs to a `Company`, token-based signup invitation flow, password-reset token, 2FA flag |
| `Company` | Tenant. Billing info, status, per-company `webhook_signature`, `logo_path`/`brand_color` (white-label). Has many users/agents/subscriptions/invoices |
| `Agent` | A Retell AI voice agent (`retell_agent_id`, phone number, type, cost/minute, missed-call alert toggle + recipient). Has one subscription, many call logs, one calendar connection |
| `CallLog` | A single call: `retell_call_id`, status, direction, duration, transcript (JSON), summary, sentiment, recording URL, `retell_cost` (decimal:4, dollars), metadata |
| `Plan` | Pricing tier (price, included minutes, overage rate); supports per-company custom plans |
| `Subscription` | Links an Agent + Plan for a Company; usage tracking, trial fields, circuit-breaker fields, period/expiry/cancellation. `getEffectivePrice()` returns the *current* price (custom_price or plan price) — used for MRR, not for historical-period revenue (see below) |
| `Invoice` | Billing invoice; `amount` + `billing_period_start`/`end` snapshot the price actually charged for that period. Has payment links, payments, receipts |
| `PaymentLink` | Tokenized payment link (provider, token, URL, status, expiry) |
| `Payment` | Payment transaction record |
| `PaymentReceipt` | Manually uploaded bank-transfer receipt (file + review status/notes) |
| `BillingCycle` | Immutable snapshot of a completed billing period (cost, minutes, calls, profit, margin), written at subscription create/renew/cancel. Powers the "Billing History" table on subscription detail pages — **not** used for the live revenue dashboard (see below) |
| `Appointment` | A booking extracted from a call's post-call analysis: customer name/phone/email, start/end, status, provider, external event ID |
| `CalendarConnection` | One agent's connected calendar (`provider`: google/cal_com, encrypted `credentials`, status) |
| `SystemSetting` | Encrypted key/value app config store (Retell/Stripe/Payoneer keys, Google Calendar OAuth client ID/secret, thresholds, expiry windows) |
| `Notification` | In-app/email notification log |
| `AuditLog` | Action history (entity, old/new values, actor, IP) |
| `WebhookLog` | Raw log of every inbound webhook (Retell/Payoneer), enriched with resolved `_company_id`/`_agent_id` for traceability |

All UUID-bearing models share the `App\Traits\HasUuid` trait.

**Revenue/cost accuracy notes** (load-bearing — read before touching `RevenueService`):
- `CallLog.retell_cost` is derived from Retell's `call_cost.combined_cost`, which is in **cents**, converted via `round($combined_cost / 100, 4)`. Both `handleCallEnded` and `handleCallAnalyzed` in `RetellService` must apply this identically — historically one divided and one didn't, and a missing decimal-precision argument on `round()` silently floored nearly every call's cost to `$0`. `calls:backfill-cost` exists to repair historical rows.
- `RevenueService::getSystemStats()` aggregates the same live per-company data `getCompanyStats()` uses (call logs + invoices), rather than `BillingCycle` snapshots — a snapshot only exists at a lifecycle event, so a subscription sitting mid-cycle (the normal state most of the time) would otherwise contribute nothing to a period total despite having real activity.
- `getAgentStats()` computes revenue by summing `Invoice.amount` for invoices whose `billing_period_start` falls in the queried window — **not** `Subscription::getEffectivePrice()` — because the latter reflects today's price even when asked about a past period.
- Known remaining limitation: if a subscription's billing cycle doesn't align to calendar months, its invoice's `billing_period_start` can fall in a different calendar month than the one being viewed (no proration). Consistent with the rest of the app's non-prorated billing model, not something introduced by the above.

### Controllers (`app/Http/Controllers`)
- **Auth**: `LoginController`, `SignupController` (token-based invite completion), `PasswordResetController` (secure-link based reset flow)
- **Admin\***: `DashboardController`, `CompanyController`, `UserController` (+ admin-initiated password reset), `AgentController` (+ chart/refresh endpoints), `CallLogController`, `PlanController`, `SubscriptionController`, `InvoiceController`, `PaymentReceiptController` (review workflow), `RevenueController`, `SettingsController`, `AuditLogController`, `CalendarConnectionController` (Google OAuth flow + Cal.com API key connect/disconnect)
- **Customer\***: `DashboardController`, `AgentController`, `SubscriptionController`, `InvoiceController`, `CallLogController` (all read-only, scoped to the logged-in user's company)
- **Billing\PaymentController**: public, unauthenticated tokenized payment flow (pay, bank details, upload receipt, success/expired states)
- **Webhook\***: `RetellWebhookController` (per-company+per-agent URL, verified by `webhook.verify` middleware, logs + queues `ProcessRetellWebhook`, always returns 200), `PayoneerWebhookController` (payment completed/failed, idempotent)
- **Shared**: `ProfileController` (+ self-service password change), `CallLogController` (JSON call-detail endpoint that re-fetches a fresh recording URL from Retell since stored URLs expire ~10 min)

### Routes
- `routes/web.php` — guest auth routes (incl. `reset-password/{token}`), public billing routes (`billing/pay/{token}/...`), authenticated `profile/*` (incl. password change), shared `calls/{callLog}`, then `admin/*` (middleware `auth,admin`, incl. `users/{user}/reset-password` and `agents/{agent}/calendar/*`) and `customer/*` (middleware `auth,customer`) route groups.
- `routes/api.php` — `webhooks/retell/company/{company_uid}/agent/{agent_uid}` (protected by `webhook.verify` — verifies Retell's `X-Retell-Signature` HMAC when a signing secret is configured; fails open if unconfigured, fails closed on a mismatch) and `webhooks/payoneer`.
- `routes/console.php` — scheduled artisan commands + the every-minute `queue:work` call.

### Background processing (`app/Jobs`, `app/Events`, `app/Listeners`)
- **Jobs**: `ProcessRetellWebhook` (handles out-of-order `call_analyzed`/`call_ended` events with retry/backoff via `WebhookOutOfOrderException`), `SendEmailJob`, `SendPaymentReminder` (dispatched when a renewal is blocked on an unpaid invoice)
- **Events**: `CircuitBreakerTriggered`, `SubscriptionActivated`, `PaymentReceived`
- **Listeners**: `SendUsageAlertEmail`, `SendPaymentConfirmationEmail`, `SendSubscriptionActivatedEmail` (Laravel 11 auto-discovery)
- **Exceptions used for control flow**: `SubscriptionRenewalBlockedException` (renewal skipped — current period unpaid), `WebhookOutOfOrderException` (re-queue), `InvoiceAlreadyPaidException`, `SubscriptionHasPaidInvoiceException`

### Services (`app/Services`)
- `RetellService` — Retell API client + webhook event processor (call_started/ended/analyzed), updates `CallLog`, increments subscription minutes, triggers circuit breaker, dispatches missed-call alerts (`MISSED_CALL_DISCONNECTION_REASONS`), extracts and creates appointments from post-call analysis (`maybeCreateAppointment`)
- `AppointmentService` — orchestrates appointment creation against whichever calendar provider an agent has connected
- `Services\Calendar\CalendarProviderInterface` (+ `GoogleCalendarProvider`, `CalComProvider`) — adapter pattern for calendar sync; more providers (Jobber, ServiceTitan, Housecall Pro, GHL) can be added behind the same interface without touching the booking flow
- `PayoneerService` — Payoneer API client (payment request creation; placeholder pending full API integration)
- `SubscriptionService` — create/activate/renew/expire subscriptions, trial handling, blocks renewal on an unpaid current period and triggers a payment reminder instead, writes `BillingCycle` snapshots
- `InvoiceService` — invoice creation/numbering, due dates, mark-paid orchestration
- `RevenueService` — revenue/cost/profit aggregation for dashboard and reports (see accuracy notes above)
- `EmailService` — central dispatcher for all Mailables, logs `Notification` records
- `AuditService` — writes `AuditLog` entries for CRUD actions

### Middleware (`app/Http/Middleware`)
- `AdminMiddleware` (`admin`) — requires `isAdmin()`
- `CustomerMiddleware` (`customer`) — requires `isCustomer()`
- `VerifyWebhookSignature` (`webhook.verify`) — **applied** to the Retell webhook route. Verifies `X-Retell-Signature: v={timestamp_ms},d={hex_digest}` (HMAC-SHA256) when a signing secret is configured in `SystemSetting`; fails open (allows the request) if no secret is configured yet, fails closed (401) on a signature mismatch. Company/agent UUIDs embedded in the URL remain the primary routing mechanism either way.

### Console commands (`app/Console/Commands`) — scheduled daily, `America/New_York`, unless noted
| Command | Time | Purpose |
|---|---|---|
| `subscriptions:process-trial-expirations` | 08:00 | Converts expired trials to paid + invoices |
| `subscriptions:send-trial-ending-warnings` | 08:15 | Trial-ending-soon emails |
| `subscriptions:process-renewals` | 08:30 | Renews subscriptions past period end; skips + sends a payment reminder if the current period is unpaid |
| `subscriptions:send-expiry-notifications` | 09:00 | Expiring-within-7-days emails |
| `invoices:mark-overdue` | 12:00 | Bulk-marks past-due invoices as overdue |
| `company:purge {company}` | manual only | Destructive full company data wipe |
| `calls:backfill-cost` | manual only | Re-fetches and corrects `retell_cost` from the Retell API |

### Configuration
- `config/billing.php` — Payoneer bank-transfer details: bank name, bank address, account holder, account number, routing number, account type (domestic US, no SWIFT), sourced from `.env` (`PAYONEER_*`).
- Third-party **API credentials** (Retell, Stripe, Payoneer, Google Calendar OAuth) are stored **encrypted in the `system_settings` DB table**, managed via the admin Settings UI — not in `.env`.
- Per-agent calendar credentials (Google OAuth tokens, Cal.com API keys) are stored encrypted on `calendar_connections.credentials`, scoped to that one agent's connection.
- `config/services.php` — Postmark/SES/Resend/Slack scaffolding present; `.env.example` defaults `MAIL_MAILER=log`.
- `.env.example` also references `N8N_WEBHOOK_SECRET` (possible n8n automation integration, not wired into any `config/` file found) and unused `PUSHER_*` broadcasting vars (`BROADCAST_CONNECTION=log`).

### Database schema (migration order)
`companies` → `users` → `plans` → `agents` → `subscriptions` → `call_logs` → `invoices` → `payment_links` → `payments` → `billing_cycles` → `system_settings` → `notifications` → `audit_logs` → `webhook_logs`, followed by iterative additions: `payment_receipts` (manual receipt upload), webhook signature on companies, call type on agents, recording URL fix, trial fields on subscriptions, missed-call alert settings on agents, branding fields on companies, `calendar_connections`, `appointments`, password-reset token on users.

---

## 3. Known Gaps / Things to Be Aware Of
- **Stripe billing is deferred**, not built — no Stripe account exists yet. `stripe_api_key`/`stripe_webhook_secret` settings exist but are unused; manual bank transfer is the only payment rail today.
- **Calendar integrations** currently cover Google Calendar and Cal.com only, behind a shared adapter interface. The agency's actual target buyers mostly run Jobber/ServiceTitan/Housecall Pro/GHL day-to-day — those are deliberately not built yet, added on demand behind the same interface.
- **Multi-location / sub-account RBAC** was scoped out entirely (the most architecturally invasive candidate — touches `User::role`, both middleware classes, every `company_id`-scoped controller). Revisit only if actually needed.
- `PayoneerService` is a placeholder pending full Payoneer API documentation/integration.
- No persistent queue worker — background jobs only run when the scheduler's every-minute `queue:work --stop-when-empty` fires, so there can be up to ~1 minute of latency on webhook processing and emails.
- Historical per-period revenue has a known non-prorated edge case (see the revenue/cost accuracy note in Section 2) — a subscription whose billing cycle crosses a calendar-month boundary can show revenue in the "wrong" month relative to a strict calendar view.
- Default `welcome.blade.php` Laravel starter view is still present but unused (`/` always redirects).

**Resolved this cycle, kept here as context for anyone reading git blame:**
- A subscription would silently auto-renew (and email "your subscription is renewed") even when the current period had never been paid. `SubscriptionService::renew()` now checks `currentPeriodIsPaid()` first and throws `SubscriptionRenewalBlockedException`; the renewal command catches it and sends a payment reminder instead.
- `CallLog.retell_cost` was being rounded to whole dollars (`round($cents / 100)` with no decimal precision), zeroing out the cost of nearly every call and understating the revenue dashboard's cost/margin figures. Fixed; `calls:backfill-cost` repairs already-ingested rows.
- The revenue page's summary cards and per-company table read from two different, non-reconcilable data sources, and the company filter didn't actually scope the summary cards. Both fixed — see the accuracy notes in Section 2.
- `VerifyWebhookSignature` existed but its route group was commented out — now wired up and applied.

---

## 4. Where to Look First for Common Tasks
- **Add/change a billing rule** → `app/Services/InvoiceService.php`, `app/Services/SubscriptionService.php`, `app/Models/Subscription.php`
- **Change how calls are processed from Retell** → `app/Services/RetellService.php`, `app/Jobs/ProcessRetellWebhook.php`, `app/Http/Controllers/Webhook/RetellWebhookController.php`
- **Touch revenue/cost/margin numbers** → read the accuracy notes in Section 2 first, then `app/Services/RevenueService.php`, `app/Http/Controllers/Admin/RevenueController.php`
- **Add a calendar provider** → implement `App\Services\Calendar\CalendarProviderInterface`, wire it into `AppointmentService`
- **Change missed-call alert behavior** → `app/Services/RetellService.php` (`MISSED_CALL_DISCONNECTION_REASONS`, `handleMissedCall`), `app/Mail/MissedCallAlertMail.php`
- **Add an admin page** → `app/Http/Controllers/Admin/`, `resources/views/admin/`, route group in `routes/web.php`
- **Add/adjust an email** → `app/Mail/`, `resources/views/emails/`, dispatch via `app/Services/EmailService.php`
- **Change a scheduled/automated behavior** → `app/Console/Commands/`, schedule defined in `routes/console.php`
- **Change system-wide settings (API keys, thresholds)** → `app/Models/SystemSetting.php`, `app/Http/Controllers/Admin/SettingsController.php`, `resources/views/admin/settings/`
- **Change design tokens / theme / dark mode** → `resources/css/tokens.css`, `resources/css/app.css`, rebuild with `npm run build`
- **Change the page loader or micro-interactions** → `resources/views/components/page-loader.blade.php`, `resources/js/app.js`
