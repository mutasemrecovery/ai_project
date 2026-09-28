@extends('admin.layouts.app')
@section('title', __('messages.lg_leads'))

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">{{ __('messages.lg_leads') }}</h1>
        <p class="page-sub">{{ __('messages.lg_leads_subtitle') }}</p>
    </div>
    @can('lead-add')
    <a href="{{ route('admin.leads.create') }}" class="btn-primary-sm"><i class="bi bi-plus-circle"></i> {{ __('messages.lg_manual_lead') }}</a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="panel-card mb-3">
    <div class="panel-card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('messages.lg_search_company') }}"></div>
            <div class="col-6 col-md-2"><input name="country" value="{{ request('country') }}" class="form-control form-control-sm" placeholder="{{ __('messages.lg_country') }}"></div>
            <div class="col-6 col-md-2"><input name="industry" value="{{ request('industry') }}" class="form-control form-control-sm" placeholder="{{ __('messages.lg_industry') }}"></div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('messages.lg_any_status') }}</option>
                    @foreach(['new','analyzing','qualified','approved','contacted','replied','interested','meeting','proposal','won','lost','ignored'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('messages.lg_status_' . $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">{{ __('messages.lg_any_priority') }}</option>
                    @foreach(['low','medium','high','critical'] as $priority)
                        <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ __('messages.lg_priority_' . $priority) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn-primary-sm"><i class="bi bi-search"></i></button></div>
        </form>
    </div>
</div>

<div class="panel-card">
    <div class="panel-card-header d-flex align-items-center justify-content-between">
        <h2 class="panel-card-title"><i class="bi bi-bullseye"></i> {{ __('messages.lg_lead_list') }}</h2>
        <span class="pill pill-info">{{ __('messages.lg_leads_count', ['count' => $leads->total()]) }}</span>
    </div>
    <div class="panel-card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('messages.lg_company') }}</th><th>{{ __('messages.lg_industry') }}</th><th>{{ __('messages.lg_country') }}</th><th>{{ __('messages.lg_detected_need') }}</th><th>{{ __('messages.lg_score') }}</th><th>{{ __('messages.lg_priority') }}</th><th>{{ __('messages.Status') }}</th><th>{{ __('messages.lg_source') }}</th><th>{{ __('messages.lg_created') }}</th><th>{{ __('messages.lg_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td class="fw-semibold">{{ $lead->company_name }}</td>
                        <td>{{ $lead->industry ?: '-' }}</td>
                        <td>{{ $lead->country ?: '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($lead->detected_need ?: '-', 50) }}</td>
                        <td><span class="pill pill-info">{{ $lead->lead_score }}</span></td>
                        <td><span class="pill pill-{{ in_array($lead->priority, ['high','critical']) ? 'danger' : 'neutral' }}">{{ __('messages.lg_priority_' . $lead->priority) }}</span></td>
                        <td><span class="pill pill-neutral">{{ __('messages.lg_status_' . $lead->status) }}</span></td>
                        <td>{{ $lead->source ?: '-' }}</td>
                        <td>{{ $lead->created_at?->format('Y-m-d') }}</td>
                        <td><a href="{{ route('admin.leads.show', $lead) }}" class="btn-icon-sm btn-edit" title="View"><i class="bi bi-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ __('messages.lg_no_leads') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($leads->hasPages())
    <div class="panel-card-body border-top pt-3">{{ $leads->links() }}</div>
    @endif
</div>
@endsection
