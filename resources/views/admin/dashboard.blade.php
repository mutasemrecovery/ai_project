@extends('admin.layouts.app')

@section('title', __('messages.dashboard'))

@section('content')

@php
    $stats = $stats ?? [];
    $charts = $charts ?? [];
@endphp

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">{{ __('messages.lg_dashboard_title') }}</h1>
        <p class="page-sub">{{ __('messages.lg_dashboard_subtitle') }}</p>
    </div>
    @can('lead-add')
    <a href="{{ route('admin.leads.create') }}" class="btn-primary-sm"><i class="bi bi-plus-circle"></i> {{ __('messages.lg_add_lead') }}</a>
    @endcan
</div>

<div class="row g-3 mb-3">
    @foreach([
        'total_leads' => __('messages.lg_total_leads'),
        'new_leads' => __('messages.lg_new_leads'),
        'qualified_leads' => __('messages.lg_qualified_leads'),
        'high_priority_leads' => __('messages.lg_high_priority_leads'),
        'contacted_leads' => __('messages.lg_contacted_leads'),
        'interested_leads' => __('messages.lg_interested_leads'),
        'meetings' => __('messages.lg_meetings'),
        'proposals' => __('messages.lg_proposals'),
        'won_deals' => __('messages.lg_won_deals'),
        'estimated_pipeline_value' => __('messages.lg_pipeline_value'),
        'ai_cost_today' => __('messages.lg_ai_cost_today'),
        'ai_cost_month' => __('messages.lg_ai_cost_month'),
    ] as $key => $label)
    <div class="col-6 col-lg-3">
        <div class="panel-card h-100">
            <div class="panel-card-body">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-4 fw-semibold">{{ number_format((float) ($stats[$key] ?? 0), in_array($key, ['ai_cost_today', 'ai_cost_month']) ? 4 : 0) }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    @foreach(['funnel' => __('messages.lg_conversion_funnel'), 'by_country' => __('messages.lg_leads_by_country'), 'by_source' => __('messages.lg_leads_by_source'), 'by_score' => __('messages.lg_leads_by_score')] as $chartKey => $title)
    <div class="col-12 col-lg-6">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <h2 class="panel-card-title">{{ $title }}</h2>
            </div>
            <div class="panel-card-body">
                @php $chart = collect($charts[$chartKey] ?? []); $max = max(1, (int) $chart->max()); @endphp
                @forelse($chart as $label => $total)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div style="width:130px" class="small text-muted">{{ $label }}</div>
                        <div class="progress flex-grow-1" style="height:8px">
                            <div class="progress-bar" style="width: {{ ((int) $total / $max) * 100 }}%"></div>
                        </div>
                        <div class="small fw-semibold" style="width:40px">{{ $total }}</div>
                    </div>
                @empty
                    <div class="text-muted">{{ __('messages.lg_no_data_yet') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
