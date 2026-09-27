@extends('layouts.admin')

@section('title', 'Emails')

@section('page-pretitle')
    System
@endsection

@section('page-header')
    Emails
@endsection

@section('content')
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Sent</div>
                    <div class="h1 mb-0 text-green">{{ number_format($counts['sent']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Queued</div>
                    <div class="h1 mb-0 text-yellow">{{ number_format($counts['queued']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Failed</div>
                    <div class="h1 mb-0 {{ $counts['failed'] > 0 ? 'text-red' : '' }}">{{ number_format($counts['failed']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Emails</h3>
            <div class="card-actions">
                <form method="GET" action="{{ route('admin.notifications.index') }}" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" class="form-control form-control-sm" style="width: 200px;" placeholder="Search subject..." value="{{ request('search') }}">
                    <select name="type" class="form-select form-select-sm" style="width: 200px;" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if(request('search') || request('type') || request('status'))
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Sent At</th>
                        <th>Type</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr class="email-row" style="cursor: pointer;">
                            <td class="text-muted">
                                {{ ($notification->sent_at ?? $notification->created_at)?->format('M d, Y h:i A') }}
                            </td>
                            <td>{{ str_replace('_', ' ', ucfirst($notification->type)) }}</td>
                            <td>{{ $notification->recipient_email ?? '-' }}</td>
                            <td class="text-truncate" style="max-width: 300px;">{{ $notification->subject ?? '-' }}</td>
                            <td>
                                @if($notification->company)
                                    <a href="{{ route('admin.companies.show', $notification->company) }}" class="text-reset">{{ $notification->company->name }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @switch($notification->status)
                                    @case('sent')
                                        <span class="badge bg-green-lt">Sent</span>
                                        @break
                                    @case('queued')
                                        <span class="badge bg-yellow-lt">Queued</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-red-lt" title="{{ $notification->error }}">Failed</span>
                                        @if($notification->error)
                                            <div class="text-danger small text-truncate" style="max-width: 250px;" title="{{ $notification->error }}">{{ $notification->error }}</div>
                                        @endif
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($notification->status) }}</span>
                                @endswitch
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary view-email-btn"
                                        data-bs-toggle="offcanvas" data-bs-target="#emailOffcanvas"
                                        data-url="{{ route('admin.notifications.show', $notification) }}">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No emails sent yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notifications->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $notifications->firstItem() }}</span> to <span>{{ $notifications->lastItem() }}</span> of <span>{{ $notifications->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $notifications->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Email viewer: headers first, then the subject, then the message itself --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="emailOffcanvas" style="width: min(760px, 100vw);">
        <div class="offcanvas-header">
            <div>
                <h5 class="offcanvas-title">Email</h5>
                <div class="text-muted small" id="emailOcType"></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="text-center py-5" id="emailOcLoader">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                <p class="text-muted mt-2">Loading email...</p>
            </div>

            <div id="emailOcContent" class="d-none">
                <div id="emailOcError" class="alert alert-danger d-none"></div>

                <dl class="row mb-3 gy-2">
                    <dt class="col-sm-3 text-muted fw-normal">Status</dt>
                    <dd class="col-sm-9 mb-0"><span class="badge" id="emailOcStatus"></span></dd>
                    <dt class="col-sm-3 text-muted fw-normal">From</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcFrom"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">To</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcTo"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">Cc</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcCc"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">Bcc</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcBcc"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">Reply-To</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcReplyTo"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">Queued</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcQueued"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">Sent</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcSent"></dd>
                    <dt class="col-sm-3 text-muted fw-normal">Customer</dt>
                    <dd class="col-sm-9 mb-0" id="emailOcCustomer"></dd>
                </dl>

                <hr>

                <div class="text-muted small text-uppercase fw-semibold">Subject</div>
                <div class="h3 mb-3" id="emailOcSubject"></div>

                <div id="emailOcNote" class="alert alert-info d-none"></div>
                <div id="emailOcSummary" class="text-muted small mb-3 d-none"></div>

                <div id="emailOcBodyWrap" class="rounded overflow-hidden border d-none" style="background: #f4f6f9;">
                    <iframe id="emailOcFrame" title="Email body" class="w-100 border-0 d-block" style="min-height: 320px;"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = (id) => document.getElementById(id);
    const canvas = $('emailOffcanvas');
    const loader = $('emailOcLoader');
    const content = $('emailOcContent');
    const frame = $('emailOcFrame');
    const bodyWrap = $('emailOcBodyWrap');
    let latest = 0; // ignores a slow response for an email the admin has already moved on from

    const NOTES = {
        queued: 'This email is still in the queue. Its full content is saved the moment it is sent — usually within a minute; reopen it then.',
        private_link: 'The content of this email is not kept because it contains a private sign-in link. Only who it went to and when is recorded.',
        not_recorded: 'This email was sent before the portal started keeping a copy of each message, so only its summary is available.',
    };
    const STATUS = { sent: ['Sent', 'bg-green-lt'], queued: ['Queued', 'bg-yellow-lt'], failed: ['Failed', 'bg-red-lt'] };

    function setText(id, value) {
        const node = $(id);
        node.textContent = value || '—';
        node.classList.toggle('text-muted', !value);
    }

    function setPeople(id, list) {
        const node = $(id);
        node.replaceChildren();
        node.classList.toggle('text-muted', !list || list.length === 0);
        if (!list || list.length === 0) { node.textContent = '—'; return; }
        list.forEach((a) => {
            const line = document.createElement('div');
            line.textContent = a.name ? a.name + ' <' + a.email + '>' : a.email;
            node.appendChild(line);
        });
    }

    function render(d) {
        const [label, cls] = STATUS[d.status] || [d.status, 'bg-secondary-lt'];
        const status = $('emailOcStatus');
        status.textContent = label;
        status.className = 'badge ' + cls;

        $('emailOcType').textContent = d.type;
        setPeople('emailOcFrom', d.from);
        setPeople('emailOcTo', d.to);
        setPeople('emailOcCc', d.cc);
        setPeople('emailOcBcc', d.bcc);
        setPeople('emailOcReplyTo', d.reply_to);
        setText('emailOcQueued', d.queued_at);
        setText('emailOcSent', d.sent_at);
        setText('emailOcSubject', d.subject);

        const customer = $('emailOcCustomer');
        customer.replaceChildren();
        if (d.customer) {
            const a = document.createElement('a');
            a.href = d.customer.url;
            a.textContent = d.customer.name;
            customer.classList.remove('text-muted');
            customer.appendChild(a);
        } else {
            customer.textContent = '—';
            customer.classList.add('text-muted');
        }

        const error = $('emailOcError');
        error.classList.toggle('d-none', !d.error);
        error.textContent = d.error ? 'Failed to send: ' + d.error : '';

        const note = $('emailOcNote');
        const summary = $('emailOcSummary');

        if (d.body_url) {
            note.classList.add('d-none');
            summary.classList.add('d-none');
            bodyWrap.classList.remove('d-none');
            frame.src = d.body_url;
        } else {
            bodyWrap.classList.add('d-none');
            note.textContent = NOTES[d.no_body_reason] || NOTES.not_recorded;
            note.classList.remove('d-none');
            summary.textContent = d.summary ? 'Summary: ' + d.summary : '';
            summary.classList.toggle('d-none', !d.summary);
        }

        loader.classList.add('d-none');
        content.classList.remove('d-none');
    }

    function load(url) {
        const mine = ++latest;
        loader.classList.remove('d-none');
        content.classList.add('d-none');
        frame.removeAttribute('src');

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then((d) => { if (mine === latest) render(d); })
            .catch(() => {
                if (mine !== latest) return;
                loader.classList.add('d-none');
                content.classList.remove('d-none');
                bodyWrap.classList.add('d-none');
                const note = $('emailOcNote');
                note.textContent = 'Could not load this email. Please try again.';
                note.classList.remove('d-none');
            });
    }

    // The message is same-origin (served by NotificationController::body under a strict CSP, no
    // scripts), so its real height can be read and the panel scrolls as one page, not a box in a box.
    frame.addEventListener('load', function () {
        try { frame.style.height = frame.contentDocument.documentElement.scrollHeight + 'px'; } catch (e) { /* keep min-height */ }
    });

    canvas.addEventListener('hidden.bs.offcanvas', function () { frame.removeAttribute('src'); });

    document.querySelectorAll('.view-email-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { load(btn.dataset.url); });
    });

    // The whole row opens it, not just the button — but a click on a link inside the row still follows the link.
    document.querySelectorAll('tr.email-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) return;
            row.querySelector('.view-email-btn')?.click();
        });
    });
});
</script>
@endpush
