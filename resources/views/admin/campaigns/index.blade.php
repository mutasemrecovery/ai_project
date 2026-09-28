@extends('admin.layouts.app')
@section('title', __('messages.lg_campaigns'))

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">{{ __('messages.lg_campaigns') }}</h1>
        <p class="page-sub">{{ __('messages.lg_campaigns_subtitle') }}</p>
    </div>
    @can('campaign-add')
    <a href="{{ route('admin.campaigns.create') }}" class="btn-primary-sm"><i class="bi bi-plus-circle"></i> {{ __('messages.lg_new_campaign') }}</a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="panel-card mb-3">
    <div class="panel-card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-6"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('messages.lg_search_campaigns') }}"></div>
            <div class="col-auto"><button class="btn-primary-sm"><i class="bi bi-search"></i></button></div>
        </form>
    </div>
</div>

<div class="panel-card">
    <div class="panel-card-header d-flex align-items-center justify-content-between">
        <h2 class="panel-card-title"><i class="bi bi-megaphone"></i> {{ __('messages.lg_campaign_list') }}</h2>
        <span class="pill pill-info">{{ __('messages.lg_campaigns_count', ['count' => $campaigns->total()]) }}</span>
    </div>
    <div class="panel-card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>{{ __('messages.lg_name') }}</th><th>{{ __('messages.lg_countries') }}</th><th>{{ __('messages.lg_industries') }}</th><th>{{ __('messages.lg_minimum_score') }}</th><th>{{ __('messages.lg_enabled') }}</th><th>{{ __('messages.lg_actions') }}</th></tr></thead>
                <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td class="fw-semibold">{{ $campaign->name }}</td>
                        <td>{{ implode(', ', $campaign->countries ?: []) ?: '-' }}</td>
                        <td>{{ implode(', ', $campaign->industries ?: []) ?: '-' }}</td>
                        <td>{{ $campaign->minimum_score }}</td>
                        <td><span class="pill pill-{{ $campaign->enabled ? 'success' : 'neutral' }}">{{ $campaign->enabled ? __('messages.Yes') : __('messages.No') }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('campaign-edit')<a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn-icon-sm btn-edit" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                                @can('campaign-delete')
                                <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('{{ __('messages.lg_delete_campaign_confirm') }}')">@csrf @method('DELETE')<button class="btn-icon-sm btn-delete"><i class="bi bi-trash"></i></button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('messages.lg_no_campaigns') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($campaigns->hasPages())<div class="panel-card-body border-top pt-3">{{ $campaigns->links() }}</div>@endif
</div>
@endsection
