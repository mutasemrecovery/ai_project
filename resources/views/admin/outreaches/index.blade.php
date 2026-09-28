@extends('admin.layouts.app')
@section('title', 'Outreach')

@section('content')
<div class="page-header">
    <h1 class="page-title">Outreach Approval</h1>
    <p class="page-sub">Generated messages stay here until an admin reviews and approves them.</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="panel-card mb-3">
    <div class="panel-card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="">Any status</option>@foreach(['pending_approval','approved','rejected','sent','failed','replied'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn-primary-sm"><i class="bi bi-filter"></i></button></div>
        </form>
    </div>
</div>

<div class="panel-card">
    <div class="panel-card-header"><h2 class="panel-card-title"><i class="bi bi-send-check"></i> Messages</h2></div>
    <div class="panel-card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Lead</th><th>Channel</th><th>Type</th><th>Status</th><th>Subject</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($outreaches as $outreach)
                    <tr>
                        <td><a href="{{ route('admin.leads.show', $outreach->lead) }}">{{ $outreach->lead->company_name }}</a></td>
                        <td>{{ ucfirst($outreach->channel) }}</td>
                        <td>{{ str_replace('_', ' ', $outreach->message_type) }}</td>
                        <td><span class="pill pill-neutral">{{ str_replace('_', ' ', $outreach->status) }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($outreach->subject ?: '-', 50) }}</td>
                        <td>{{ $outreach->created_at?->format('Y-m-d') }}</td>
                        <td><a href="{{ route('admin.outreaches.edit', $outreach) }}" class="btn-icon-sm btn-edit"><i class="bi bi-pencil"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No outreach messages yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($outreaches->hasPages())<div class="panel-card-body border-top pt-3">{{ $outreaches->links() }}</div>@endif
</div>
@endsection
