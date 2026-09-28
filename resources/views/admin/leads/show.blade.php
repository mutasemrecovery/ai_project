@extends('admin.layouts.app')
@section('title', $lead->company_name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">{{ $lead->company_name }}</h1>
        <p class="page-sub">{{ $lead->industry ?: 'Unknown industry' }} - {{ $lead->city ?: 'Unknown city' }} {{ $lead->country ? ', '.$lead->country : '' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.leads.analyze', $lead) }}">@csrf<button class="btn-outline-sm"><i class="bi bi-cpu"></i> Analyze</button></form>
        <form method="POST" action="{{ route('admin.leads.generate-outreach', $lead) }}">@csrf<button class="btn-primary-sm"><i class="bi bi-magic"></i> Generate Outreach</button></form>
        <form method="POST" action="{{ route('admin.leads.approve', $lead) }}">@csrf<button class="btn-outline-sm"><i class="bi bi-check-circle"></i> Approve</button></form>
        <form method="POST" action="{{ route('admin.leads.ignore', $lead) }}">@csrf<button class="btn-outline-sm"><i class="bi bi-slash-circle"></i> Ignore</button></form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">Company Information</h2></div>
            <div class="panel-card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Website</div>@if($lead->website)<a href="{{ $lead->website }}" target="_blank" rel="noopener">{{ $lead->website }}</a>@else - @endif</div>
                    <div class="col-md-6"><div class="text-muted small">Contact</div>{{ $lead->contact_name ?: '-' }} {{ $lead->contact_role ? '- '.$lead->contact_role : '' }}</div>
                    <div class="col-md-6"><div class="text-muted small">Email</div>{{ $lead->email ?: '-' }}</div>
                    <div class="col-md-6"><div class="text-muted small">Phone</div>{{ $lead->phone ?: '-' }}</div>
                    <div class="col-12"><div class="text-muted small">Description</div>{{ $lead->description ?: '-' }}</div>
                    <div class="col-12"><div class="text-muted small">Source</div>{{ $lead->source ?: '-' }} @if($lead->source_url) - <a href="{{ $lead->source_url }}" target="_blank" rel="noopener">Evidence</a>@endif</div>
                </div>
            </div>
        </div>

        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">AI Analysis</h2></div>
            <div class="panel-card-body">
                <p>{{ $lead->ai_summary ?: 'No analysis yet.' }}</p>
                <div class="text-muted small mb-1">Reasoning</div>
                <p>{{ $lead->ai_reasoning ?: '-' }}</p>
                <div class="text-muted small mb-1">Detected Needs</div>
                <p>{{ $lead->detected_need ?: '-' }}</p>
                <div class="text-muted small mb-1">Recommended Services</div>
                @foreach($lead->detected_services ?: [] as $service)<span class="pill pill-info">{{ $service }}</span>@endforeach
                <div class="mt-3 text-muted small mb-1">Signals and Evidence</div>
                @forelse($lead->business_signals ?: [] as $signal)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $signal['signal'] ?? '-' }}</div>
                        <div class="small text-muted">{{ $signal['evidence'] ?? 'No evidence URL supplied' }} - {{ $signal['confidence'] ?? '-' }}</div>
                    </div>
                @empty
                    <div class="text-muted">No signals yet.</div>
                @endforelse
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">Outreach Messages</h2></div>
            <div class="panel-card-body">
                @forelse($lead->outreaches as $outreach)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <div><strong>{{ $outreach->subject ?: ucfirst($outreach->message_type) }}</strong><div class="small text-muted">{{ ucfirst($outreach->channel) }} - {{ $outreach->status }}</div></div>
                            <a href="{{ route('admin.outreaches.edit', $outreach) }}" class="btn-outline-sm">Review</a>
                        </div>
                        <hr>
                        <div>{!! nl2br(e($outreach->body)) !!}</div>
                    </div>
                @empty
                    <div class="text-muted">No outreach generated yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">Score</h2></div>
            <div class="panel-card-body">
                <div class="display-6 fw-semibold">{{ $lead->lead_score }}</div>
                <div class="mb-2"><span class="pill pill-info">{{ ucfirst($lead->priority) }}</span> <span class="pill pill-neutral">{{ ucfirst($lead->status) }}</span></div>
                <div>Intent: {{ $lead->intent_score }}/30</div>
                <div>Business Fit: {{ $lead->business_fit_score }}/25</div>
                <div>Project Value: {{ $lead->project_value_score }}/20</div>
                <div>Digital Gap: {{ $lead->digital_gap_score }}/25</div>
            </div>
        </div>

        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">Change Status</h2></div>
            <div class="panel-card-body">
                <form method="POST" action="{{ route('admin.leads.status', $lead) }}" class="row g-2">
                    @csrf
                    <div class="col-12"><select name="status" class="form-select">@foreach(['new','analyzing','qualified','approved','contacted','replied','interested','meeting','proposal','won','lost','ignored'] as $status)<option value="{{ $status }}" @selected($lead->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                    <div class="col-12"><select name="priority" class="form-select">@foreach(['low','medium','high','critical'] as $priority)<option value="{{ $priority }}" @selected($lead->priority === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
                    <div class="col-12 form-check ms-2"><input type="checkbox" name="do_not_contact" value="1" class="form-check-input" @checked($lead->do_not_contact)><label class="form-check-label">Do not contact</label></div>
                    <div class="col-12"><button class="btn-primary-sm">Update</button></div>
                </form>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">Timeline</h2></div>
            <div class="panel-card-body">
                <div class="small text-muted">Created: {{ $lead->created_at?->format('Y-m-d H:i') }}</div>
                <div class="small text-muted">Last contacted: {{ $lead->last_contacted_at?->format('Y-m-d H:i') ?: '-' }}</div>
                <div class="small text-muted">Next follow-up: {{ $lead->next_follow_up_at?->format('Y-m-d H:i') ?: '-' }}</div>
                <hr>
                <div class="small">AI calls: {{ $lead->aiUsages->count() }}</div>
                <div class="small">Raw sources: {{ $lead->rawLeads->count() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
