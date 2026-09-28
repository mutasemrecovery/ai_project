@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

@php
    $stats = $stats ?? [];
    $charts = $charts ?? [];
@endphp

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">Lead Generation Dashboard</h1>
        <p class="page-sub">Pipeline health, AI cost, and lead conversion overview.</p>
    </div>
    @can('lead-add')
    <a href="{{ route('admin.leads.create') }}" class="btn-primary-sm"><i class="bi bi-plus-circle"></i> Add Lead</a>
    @endcan
</div>

<div class="row g-3 mb-3">
    @foreach([
        'total_leads' => 'Total Leads',
        'new_leads' => 'New',
        'qualified_leads' => 'Qualified',
        'high_priority_leads' => 'High Priority',
        'contacted_leads' => 'Contacted',
        'interested_leads' => 'Interested',
        'meetings' => 'Meetings',
        'proposals' => 'Proposals',
        'won_deals' => 'Won',
        'estimated_pipeline_value' => 'Pipeline Value',
        'ai_cost_today' => 'AI Cost Today',
        'ai_cost_month' => 'AI Cost Month',
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
    @foreach(['funnel' => 'Conversion Funnel', 'by_country' => 'Leads by Country', 'by_source' => 'Leads by Source', 'by_score' => 'Leads by Score'] as $chartKey => $title)
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
                    <div class="text-muted">No data yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
