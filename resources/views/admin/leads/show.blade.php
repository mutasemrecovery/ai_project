@extends('admin.layouts.app')
@section('title', $lead->company_name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">{{ $lead->company_name }}</h1>
        <p class="page-sub">{{ $lead->industry ?: __('messages.lg_unknown_industry') }} - {{ $lead->city ?: __('messages.lg_unknown_city') }} {{ $lead->country ? ', '.$lead->country : '' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ route('admin.leads.analyze', $lead) }}">@csrf<button class="btn-outline-sm"><i class="bi bi-cpu"></i> {{ __('messages.lg_analyze') }}</button></form>
        <form method="POST" action="{{ route('admin.leads.generate-outreach', $lead) }}">@csrf<button class="btn-primary-sm"><i class="bi bi-magic"></i> {{ __('messages.lg_generate_outreach') }}</button></form>
        <form method="POST" action="{{ route('admin.leads.approve', $lead) }}">@csrf<button class="btn-outline-sm"><i class="bi bi-check-circle"></i> {{ __('messages.lg_approve') }}</button></form>
        <form method="POST" action="{{ route('admin.leads.ignore', $lead) }}">@csrf<button class="btn-outline-sm"><i class="bi bi-slash-circle"></i> {{ __('messages.lg_ignore') }}</button></form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_company_information') }}</h2></div>
            <div class="panel-card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">{{ __('messages.lg_website') }}</div>@if($lead->website)<a href="{{ $lead->website }}" target="_blank" rel="noopener">{{ $lead->website }}</a>@else - @endif</div>
                    <div class="col-md-6"><div class="text-muted small">{{ __('messages.lg_contact') }}</div>{{ $lead->contact_name ?: '-' }} {{ $lead->contact_role ? '- '.$lead->contact_role : '' }}</div>
                    <div class="col-md-6"><div class="text-muted small">{{ __('messages.lg_email') }}</div>{{ $lead->email ?: '-' }}</div>
                    <div class="col-md-6"><div class="text-muted small">{{ __('messages.lg_phone') }}</div>{{ $lead->phone ?: '-' }}</div>
                    <div class="col-12"><div class="text-muted small">{{ __('messages.lg_description') }}</div>{{ $lead->description ?: '-' }}</div>
                    <div class="col-12"><div class="text-muted small">{{ __('messages.lg_source') }}</div>{{ $lead->source ?: '-' }} @if($lead->source_url) - <a href="{{ $lead->source_url }}" target="_blank" rel="noopener">{{ __('messages.lg_evidence') }}</a>@endif</div>
                </div>
            </div>
        </div>

        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_ai_analysis') }}</h2></div>
            <div class="panel-card-body">
                <p>{{ $lead->ai_summary ?: __('messages.lg_no_analysis') }}</p>
                <div class="text-muted small mb-1">{{ __('messages.lg_reasoning') }}</div>
                <p>{{ $lead->ai_reasoning ?: '-' }}</p>
                <div class="text-muted small mb-1">{{ __('messages.lg_detected_need') }}</div>
                <p>{{ $lead->detected_need ?: '-' }}</p>
                <div class="text-muted small mb-1">{{ __('messages.lg_recommended_services') }}</div>
                @foreach($lead->detected_services ?: [] as $service)<span class="pill pill-info">{{ $service }}</span>@endforeach
                <div class="mt-3 text-muted small mb-1">{{ __('messages.lg_signals_evidence') }}</div>
                @forelse($lead->business_signals ?: [] as $signal)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $signal['signal'] ?? '-' }}</div>
                        <div class="small text-muted">{{ $signal['evidence'] ?? __('messages.lg_no_evidence_url') }} - {{ $signal['confidence'] ?? '-' }}</div>
                    </div>
                @empty
                    <div class="text-muted">{{ __('messages.lg_no_signals') }}</div>
                @endforelse
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_outreach_messages') }}</h2></div>
            <div class="panel-card-body">
                @forelse($lead->outreaches as $outreach)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <div><strong>{{ $outreach->subject ?: __('messages.lg_message_type_' . $outreach->message_type) }}</strong><div class="small text-muted">{{ __('messages.lg_channel_' . $outreach->channel) }} - {{ __('messages.lg_status_' . $outreach->status) }}</div></div>
                            <a href="{{ route('admin.outreaches.edit', $outreach) }}" class="btn-outline-sm">{{ __('messages.lg_review') }}</a>
                        </div>
                        <hr>
                        <div>{!! nl2br(e($outreach->body)) !!}</div>
                    </div>
                @empty
                    <div class="text-muted">{{ __('messages.lg_no_outreach') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_score') }}</h2></div>
            <div class="panel-card-body">
                <div class="display-6 fw-semibold">{{ $lead->lead_score }}</div>
                <div class="mb-2"><span class="pill pill-info">{{ __('messages.lg_priority_' . $lead->priority) }}</span> <span class="pill pill-neutral">{{ __('messages.lg_status_' . $lead->status) }}</span></div>
                <div>{{ __('messages.lg_intent_score') }}: {{ $lead->intent_score }}/30</div>
                <div>{{ __('messages.lg_business_fit_score') }}: {{ $lead->business_fit_score }}/25</div>
                <div>{{ __('messages.lg_project_value_score') }}: {{ $lead->project_value_score }}/20</div>
                <div>{{ __('messages.lg_digital_gap_score') }}: {{ $lead->digital_gap_score }}/25</div>
            </div>
        </div>

        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_change_status') }}</h2></div>
            <div class="panel-card-body">
                <form method="POST" action="{{ route('admin.leads.status', $lead) }}" class="row g-2">
                    @csrf
                    <div class="col-12"><select name="status" class="form-select">@foreach(['new','analyzing','qualified','approved','contacted','replied','interested','meeting','proposal','won','lost','ignored'] as $status)<option value="{{ $status }}" @selected($lead->status === $status)>{{ __('messages.lg_status_' . $status) }}</option>@endforeach</select></div>
                    <div class="col-12"><select name="priority" class="form-select">@foreach(['low','medium','high','critical'] as $priority)<option value="{{ $priority }}" @selected($lead->priority === $priority)>{{ __('messages.lg_priority_' . $priority) }}</option>@endforeach</select></div>
                    <div class="col-12 form-check ms-2"><input type="checkbox" name="do_not_contact" value="1" class="form-check-input" @checked($lead->do_not_contact)><label class="form-check-label">{{ __('messages.lg_do_not_contact') }}</label></div>
                    <div class="col-12"><button class="btn-primary-sm">{{ __('messages.lg_update') }}</button></div>
                </form>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_timeline') }}</h2></div>
            <div class="panel-card-body">
                <div class="small text-muted">{{ __('messages.lg_created') }}: {{ $lead->created_at?->format('Y-m-d H:i') }}</div>
                <div class="small text-muted">{{ __('messages.lg_last_contacted') }}: {{ $lead->last_contacted_at?->format('Y-m-d H:i') ?: '-' }}</div>
                <div class="small text-muted">{{ __('messages.lg_next_follow_up') }}: {{ $lead->next_follow_up_at?->format('Y-m-d H:i') ?: '-' }}</div>
                <hr>
                <div class="small">{{ __('messages.lg_ai_calls') }}: {{ $lead->aiUsages->count() }}</div>
                <div class="small">{{ __('messages.lg_raw_sources') }}: {{ $lead->rawLeads->count() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
