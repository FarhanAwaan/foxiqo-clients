# Foxiqo Client Portal

A Laravel 11 (PHP 8.2) SaaS billing and management portal for an AI voice-agent
business built on **Retell AI**. An agency admin manages client companies,
their AI phone agents, subscription plans, invoices, and payments across
multiple toggleable rails: **Paddle** (Merchant of Record, automated) and
**Nsave** (manual bank-transfer, receipt upload + admin review). A **closer**
role runs sales calls from a dedicated deal screen that creates the Paddle
customer/transaction and hands back a portal-hosted checkout link — payment
confirmation auto-provisions the client's company and first login. Each client
company gets a portal to view their agents, call recordings/transcripts,
bills, and (where connected) booked appointments.

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
- **System settings** — company branding defaults, Retell API key/webhook secret, Stripe keys (unused pending a Stripe account), Paddle keys/environment/product ID, Google Calendar OAuth client ID/secret, invoice due days, payment link expiry, circuit breaker threshold — all stored encrypted in the database, not `.env`. Each payment rail (Nsave, Paddle, Stripe later) has its own **enabled toggle**, independent of whether it's fully configured — flip one off at any time without a deploy if that rail is having issues (`PaymentProviderService`).
- **Deals** — a closer's (or admin's) pipeline of prospective customers: business info + agreed consultation price, a generated Paddle checkout link, and status (sent/paid/expired). Admin sees all deals; a closer sees only their own.
- **Roles & Permissions** — admin-configurable visibility (`spatie/laravel-permission`, layered on top of the existing `users.role` enum — admin/customer access is untouched). From `admin/roles` you can: rename any non-admin role's **display label** anytime (e.g. "Closer" → "Manager" — this only changes what's shown, the internal slug `closer` that code checks never changes); define **new permissions** on the fly (auto-granted to admin); and toggle which permissions each role has. Admin's own permission set is shown read-only for visibility. To grant a permission to one specific person instead of a whole role, use the **Permissions** panel on that user's own page (`admin/users/{user}`) — a direct grant there doesn't touch their role.
- **Audit log** — human-readable log of admin/user actions across the system.
- **Log viewer** — in-browser application log viewer (`opcodesio/log-viewer`).
- **Dark mode** — portal-wide light/dark theme toggle (persisted in `localStorage`), applied before first paint to avoid a flash of the wrong theme.

### Closer portal (sales)
- Minimal dedicated area (`deals/*`, own layout/sidebar) for the `closer` role: create a deal (business info + agreed monthly/activation price), get back a portal-hosted Paddle checkout link to send during the call, and track its status. A **server-side price floor** ($499/mo, $999 activation) is enforced unless the submitter has the `deals.override-price-floor` permission (admin, by default).

### Customer portal (per client company)
- **Dashboard** — aggregate minutes used/included across their agents, active subscriptions, recent calls.
- **Agents** — read-only list/detail view with the same call-volume and sentiment charts, scoped to their own company.
- **Call history** — per-agent call list and call detail (transcript, recording playback).
- **Subscriptions** — read-only view of their plan and usage.
- **Invoices** — read-only list/detail of their billing history.
- **Self-service password change** — from their profile page, with current-password confirmation.
- **Branding** — if their company has a logo/brand color configured, the customer portal reflects it.

### Billing / payments (public, tokenized links — no login required)
- **Paddle** (`billing/deal/{deal}`) — the checkout a closer's deal link points to. Loads Paddle.js inline on the portal itself (never redirects to foxiqo.com or a bare Paddle-hosted page); Paddle is the Merchant of Record. Gated by the `paddle_enabled` toggle.
- **Nsave** (`billing/pay/{token}` → bank transfer) — tokenized payment link sent per invoice (via admin or automatically), showing all 6 bank fields (bank name, bank address, account holder, account number, routing number, account type — no SWIFT, domestic US only) and allowing the customer to upload a receipt for admin review. Gated by the `nsave_enabled` toggle.
- Handles already-paid, expired-link, pending-receipt, and provider-disabled states with dedicated pages.

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
| `User` | Auth user; `role` (admin/customer/closer — DB enum), belongs to a `Company` (nullable for admin/closer — internal staff), token-based signup invitation flow, password-reset token, 2FA flag. Also carries a Spatie `HasRoles` role kept in sync with `role` (see Middleware) |
| `Company` | Tenant. Billing info, status, per-company `webhook_signature`, `logo_path`/`brand_color` (white-label), `paddle_customer_id`. Has many users/agents/subscriptions/invoices |
| `Deal` | Pre-payment record a closer (or admin) creates: prospect/business info, agreed monthly + activation price, `paddle_transaction_id`, status (sent/paid/expired). Deliberately separate from Subscription/Invoice — those require a real Agent (`retell_agent_id` is required+unique), which doesn't exist until a human builds it in Retell's own console, so nothing in that chain can exist pre-payment |
| `Agent` | A Retell AI voice agent (`retell_agent_id`, phone number, type, cost/minute, missed-call alert toggle + recipient). Has one subscription, many call logs, one calendar connection |
| `CallLog` | A single call: `retell_call_id`, status, direction, duration, transcript (JSON), summary, sentiment, recording URL, `retell_cost` (decimal:4, dollars), metadata |
| `Plan` | Pricing tier (price, included minutes, overage rate); supports per-company custom plans |
| `Subscription` | Links an Agent + Plan for a Company; usage tracking, trial fields, circuit-breaker fields, period/expiry/cancellation. `getEffectivePrice()` returns the *current* price (custom_price or plan price) — used for MRR, not for historical-period revenue (see below). `SubscriptionService::create()` takes an optional `$alreadyPaid` array so a Deal's already-collected Paddle payment doesn't get double-invoiced once an admin attaches the real Agent |
| `Invoice` | Billing invoice; `amount` + `billing_period_start`/`end` snapshot the price actually charged for that period. Has payment links, payments, receipts, `paddle_transaction_id` |
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
- **Auth**: `LoginController` (redirects by role: admin/closer/customer), `SignupController` (token-based invite completion, same redirect-by-role), `PasswordResetController` (secure-link based reset flow)
- **Admin\***: `DashboardController`, `CompanyController`, `UserController` (+ admin-initiated password reset; role select now includes closer, keeps `users.role` and the Spatie role in sync via `syncRoles()`), `AgentController` (+ chart/refresh endpoints), `CallLogController`, `PlanController`, `SubscriptionController`, `InvoiceController`, `PaymentReceiptController` (review workflow), `RevenueController`, `SettingsController` (now also owns Nsave/Paddle toggles + Paddle credentials), `AuditLogController`, `CalendarConnectionController` (Google OAuth flow + Cal.com API key connect/disconnect), `RoleController` (admin-editable permissions per role)
- **Customer\***: `DashboardController`, `AgentController`, `SubscriptionController`, `InvoiceController`, `CallLogController` (all read-only, scoped to the logged-in user's company)
- **`DealController`** (unnamespaced — shared between admin and closer, see Middleware): index/create/store/show. Scoping (own deals vs all) is inline, not per-route — `where('closer_id', auth()->id())` unless `can('deals.view-all')`.
- **Billing\PaymentController**: public, unauthenticated Nsave payment flow (pay, bank details, upload receipt, success/expired states), gated by `PaymentProviderService::isEnabled('nsave')`
- **Billing\DealCheckoutController**: public, unauthenticated portal-hosted Paddle checkout for a Deal — opens Paddle.js inline, gated by `PaymentProviderService::isEnabled('paddle')`
- **Webhook\***: `RetellWebhookController` (per-company+per-agent URL, verified by `webhook.verify` middleware, logs + queues `ProcessRetellWebhook`, always returns 200), `PaddleWebhookController` (`transaction.completed` → auto-provisions Company + first customer User for a paid Deal, reusing the existing signup-invitation flow; `transaction.payment_failed` → marks the Deal expired; verified by `webhook.verify.paddle`)
- **Shared**: `ProfileController` (+ self-service password change), `CallLogController` (JSON call-detail endpoint that re-fetches a fresh recording URL from Retell since stored URLs expire ~10 min)

### Routes
- `routes/web.php` — guest auth routes (incl. `reset-password/{token}`), public billing routes (`billing/pay/{token}/...` for Nsave, `billing/deal/{deal}` for Paddle), authenticated `profile/*` (incl. password change), shared `calls/{callLog}`, `deals/*` (middleware `auth,role_or_permission:admin|closer` — note Spatie's middleware ORs multiple roles with `|`, not `,`; a comma is parsed as a guard name and will throw `Auth guard [x] is not defined`), then `admin/*` (middleware `auth,admin`, incl. `users/{user}/reset-password`, `agents/{agent}/calendar/*`, and `roles/*`) and `customer/*` (middleware `auth,customer`) route groups.
- `routes/api.php` — `webhooks/retell/company/{company_uid}/agent/{agent_uid}` (protected by `webhook.verify` — verifies Retell's `X-Retell-Signature` HMAC when a signing secret is configured; fails open if unconfigured, fails closed on a mismatch) and `webhooks/paddle` (same fail-open/fail-closed convention via `webhook.verify.paddle`, verifying Paddle's `Paddle-Signature` header).
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
- `PaddleService` — Paddle Billing API client (same `Http`-facade-based shape as the old PayoneerService, no SDK dependency): `findOrCreateCustomer()`, `createTransaction()` (two non-catalog price items — recurring monthly + one-time activation — against one catalog product, `collection_mode: automatic`), `getTransaction()`
- `DealService` — the only thing that calls `PaddleService::createTransaction()`. Enforces the server-side price floor (unless `deals.override-price-floor`), then creates the `Deal` row *after* Paddle confirms — never before, so a failed Paddle call never leaves an orphaned Deal with no transaction to pay
- `PaymentProviderService` — `isEnabled($provider)` / `isConfigured($provider)` for the Nsave/Paddle/Stripe toggles in `SystemSetting`; check both at link-creation time and at render time so disabling a rail takes effect immediately
- `SubscriptionService` — create/activate/renew/expire subscriptions, trial handling, blocks renewal on an unpaid current period and triggers a payment reminder instead, writes `BillingCycle` snapshots; `create()`'s `$alreadyPaid` param handles a Deal's pre-collected Paddle payment
- `InvoiceService` — invoice creation/numbering, due dates, mark-paid orchestration (the one place all payment rails converge — Nsave receipt approval and the Paddle webhook both call `markAsPaid()`)
- `RevenueService` — revenue/cost/profit aggregation for dashboard and reports (see accuracy notes above)
- `EmailService` — central dispatcher for all Mailables, logs `Notification` records
- `AuditService` — writes `AuditLog` entries for CRUD actions

### Middleware (`app/Http/Middleware`)
- `AdminMiddleware` (`admin`) — requires `isAdmin()`
- `CustomerMiddleware` (`customer`) — requires `isCustomer()`
- No dedicated `CloserMiddleware` — the `deals/*` route group uses Spatie's built-in `role_or_permission:admin|closer` instead (see Roles/Permissions below). `isCloser()` exists on `User` for use in redirects/views.
- `VerifyWebhookSignature` (`webhook.verify`) — **applied** to the Retell webhook route. Verifies `X-Retell-Signature: v={timestamp_ms},d={hex_digest}` (HMAC-SHA256) when a signing secret is configured in `SystemSetting`; fails open (allows the request) if no secret is configured yet, fails closed (401) on a signature mismatch. Company/agent UUIDs embedded in the URL remain the primary routing mechanism either way.
- `VerifyPaddleWebhookSignature` (`webhook.verify.paddle`) — same fail-open/fail-closed convention, for Paddle's `Paddle-Signature: ts=...;h1=...` scheme (HMAC-SHA256 over `"{ts}:{raw_body}"`).

### Roles & Permissions (`spatie/laravel-permission`)
Adopted narrowly: existing admin/customer access is entirely unchanged (still the plain `users.role` enum + `AdminMiddleware`/`CustomerMiddleware`). Spatie is layered on top specifically so the **closer** role's visibility is admin-editable without a deploy.

- **Role identity vs. label** — `roles.name` is the fixed internal slug (`admin`/`customer`/`closer`) that `users.role`, `User::isCloser()`, and the `role_or_permission:admin|closer` route middleware all key off — never edit this. `roles.label` is what's actually displayed everywhere (via `User::getRoleLabelAttribute()` / `$user->role_label`, falling back to `ucfirst($role->name)` if unset) and is freely admin-editable from `admin/roles` — e.g. renaming "Closer" to "Manager" is a one-field save, nothing else changes.
- **Defining new permissions** — `admin/roles` has a "Define a New Permission" form (`RoleController::storePermission()`) that creates a `Permission` row and auto-grants it to admin. It does nothing on its own — actually gate something by checking `auth()->user()->can('your.permission')` in the relevant controller/view.
- **Assigning to a role vs. a specific user** — toggling a permission on a role (`admin/roles`) affects everyone with that role. To grant a permission to one person without changing their role, use the "Permissions" panel on their own page (`admin/users/{user}`, `UserController::updatePermissions()`), which calls Spatie's `$user->syncPermissions()` — a direct grant independent of `role_has_permissions`. `auth()->user()->can()` already checks both direct and role-derived permissions, so no other code needs to change for a direct grant to take effect. The per-user panel shows role-derived permissions as a disabled/read-only reference (edit those on the role instead) alongside the actual editable direct-grant toggles.
- `Admin\UserController` calls `$user->syncRoles([$user->role])` on create/update so the enum and Spatie's role table never drift apart; `RolePermissionSeeder` does the same for existing users and seeds initial labels/permissions (`Role::firstOrCreate` won't backfill `label` onto rows that already existed before that column was added — a one-time concern only if you're looking at a DB seeded before this feature shipped).

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
- `config/billing.php` — Nsave bank-transfer details (renamed from the retired Payoneer): bank name, bank address, account holder, account number, routing number, account type (domestic US, no SWIFT), sourced from `.env` — the env var names (`BANK_NAME`, `ACCOUNT_NUMBER`, etc.) are already generic, no `.env` change needed when Payoneer was retired.
- Third-party **API credentials** (Retell, Stripe, Paddle, Google Calendar OAuth) are stored **encrypted in the `system_settings` DB table**, managed via the admin Settings UI — not in `.env`. Paddle's client-side token is the one deliberate exception to "encrypted" — it's designed to be safe in browser code, stored as plain `string` type, not `encrypted`.
- Every payment rail also has an `{provider}_enabled` boolean in `system_settings` (`nsave_enabled`, `paddle_enabled`) — see `PaymentProviderService`. Payoneer (the actual third-party service) is retired entirely: its placeholder `PayoneerService`/`PayoneerWebhookController` were deleted since nothing called them — the real manual-payment flow was already provider-generic (`Payment.provider = 'bank_transfer'`), it just needed relabeling to Nsave.
- Per-agent calendar credentials (Google OAuth tokens, Cal.com API keys) are stored encrypted on `calendar_connections.credentials`, scoped to that one agent's connection.
- `config/services.php` — Postmark/SES/Resend/Slack scaffolding present; `.env.example` defaults `MAIL_MAILER=log`.
- `config/permission.php` — Spatie's default config (guard `web`, no changes made).
- `.env.example` also references `N8N_WEBHOOK_SECRET` (possible n8n automation integration, not wired into any `config/` file found) and unused `PUSHER_*` broadcasting vars (`BROADCAST_CONNECTION=log`).

### Database schema (migration order)
`companies` → `users` → `plans` → `agents` → `subscriptions` → `call_logs` → `invoices` → `payment_links` → `payments` → `billing_cycles` → `system_settings` → `notifications` → `audit_logs` → `webhook_logs`, followed by iterative additions: `payment_receipts` (manual receipt upload), webhook signature on companies, call type on agents, recording URL fix, trial fields on subscriptions, missed-call alert settings on agents, branding fields on companies, `calendar_connections`, `appointments`, password-reset token on users, `paddle_customer_id` on companies, `paddle_transaction_id` on invoices, `closer` added to the `users.role` enum (raw `ALTER TABLE ... MODIFY` — MySQL only, no-op on SQLite), `deals`, Spatie's `permissions`/`roles`/`model_has_permissions`/`model_has_roles`/`role_has_permissions`.

---

## 3. Known Gaps / Things to Be Aware Of
- **Stripe billing is deferred**, not built — no Stripe account exists yet. `stripe_api_key`/`stripe_webhook_secret` settings exist but are unused. `PaymentProviderService`'s toggle plumbing already leaves room for a `stripe_enabled` flag so this slots in later without rework.
- **Paddle-driven recurring renewals aren't reconciled with the portal's own renewal cron yet.** Once a Subscription exists for a Paddle-paid client, `subscriptions:process-renewals` still runs its own 30-day cycle independent of whatever Paddle itself is charging on its subscription schedule. Needs a decision on which system is the source of truth for renewals before that scenario comes up for real.
- **The post-payment onboarding wizard isn't built.** A Deal only captures pre-payment info (old `onboard.php` Step 1). The client's own business hours / AI instructions / phone system (Step 2) still need a home — open question is whether those fields belong on `Company` or per-`Agent` — and a first-login wizard UI to collect them.
- **Attaching a real Agent to a paid Deal is a manual admin step**, not automated — and can't be otherwise, since `agents.retell_agent_id` is required+unique and only gets a value once a human builds the agent in Retell's own console. The Paddle webhook (`PaddleWebhookController`) provisions everything that *can* be automated (Company, first customer User + invitation) and stops there; `SubscriptionService::create()`'s `$alreadyPaid` param is what lets the admin's later Agent/Subscription creation pick up the payment already collected instead of re-invoicing.
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
- `SystemSetting::setValue()` only updated the `value` column on an already-existing row — `type` and `is_sensitive` were silently frozen at whatever they were on first creation. Flipping a field to encrypted after the row already existed (e.g. `paddle_client_side_token`, `paddle_product_id`) appeared to work (the raw value got encrypted) but `getValue()` never decrypted it back, since the flag never actually changed. Fixed to sync all three columns on every save; the two affected rows were re-encrypted correctly under the fix.

---

## 4. Where to Look First for Common Tasks
- **Add/change a billing rule** → `app/Services/InvoiceService.php`, `app/Services/SubscriptionService.php`, `app/Models/Subscription.php`
- **Change Paddle behavior (transactions, checkout)** → `app/Services/PaddleService.php`, `app/Services/DealService.php`, `app/Http/Controllers/Webhook/PaddleWebhookController.php`
- **Add/adjust a payment rail toggle** → `app/Services/PaymentProviderService.php`, `app/Http/Controllers/Admin/SettingsController.php`, `resources/views/admin/settings/index.blade.php`
- **Change what a closer can see** → `admin/roles` in the running app (no code change needed for existing permissions); to add a *new* permission, seed it in `database/seeders/RolePermissionSeeder.php` and check it with `auth()->user()->can('...')` wherever it should gate something
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
