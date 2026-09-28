@extends('admin.layouts.app')
@section('title', 'Review Outreach')

@section('content')
<div class="page-header">
    <h1 class="page-title">Review Outreach</h1>
    <p class="page-sub">{{ $outreach->lead->company_name }} - {{ str_replace('_', ' ', $outreach->message_type) }}</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">Message</h2></div>
            <div class="panel-card-body">
                <form method="POST" action="{{ route('admin.outreaches.update', $outreach) }}" class="row g-3">
                    @csrf @method('PATCH')
                    <div class="col-12"><label class="form-label">Subject</label><input name="subject" class="form-control" value="{{ old('subject', $outreach->subject) }}"></div>
                    <div class="col-12"><label class="form-label">Body</label><textarea name="body" class="form-control" rows="12" required>{{ old('body', $outreach->body) }}</textarea></div>
                    <div class="col-12 d-flex gap-2"><button class="btn-outline-sm"><i class="bi bi-save"></i> Save Edits</button></div>
                </form>
                <hr>
                <form method="POST" action="{{ route('admin.outreaches.approve', $outreach) }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="subject" value="{{ old('subject', $outreach->subject) }}">
                    <input type="hidden" name="body" value="{{ old('body', $outreach->body) }}">
                    <div class="col-12 form-check ms-2"><input type="checkbox" name="send_now" value="1" class="form-check-input"><label class="form-check-label">Send immediately after approval</label></div>
                    <div class="col-auto"><button class="btn-primary-sm"><i class="bi bi-check-circle"></i> Approve</button></div>
                </form>
                <form method="POST" action="{{ route('admin.outreaches.reject', $outreach) }}" class="mt-2">@csrf<button class="btn-outline-sm"><i class="bi bi-x-circle"></i> Reject</button></form>
                @if($outreach->status === \App\Models\Outreach::STATUS_APPROVED)
                    <form method="POST" action="{{ route('admin.outreaches.send', $outreach) }}" class="mt-2">@csrf<button class="btn-primary-sm"><i class="bi bi-send"></i> Send Approved Message</button></form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">Evidence Used</h2></div>
            <div class="panel-card-body">
                <div class="mb-2"><span class="pill pill-neutral">{{ $outreach->status }}</span></div>
                @forelse($outreach->personalization_evidence ?: [] as $evidence)
                    <div class="small border rounded p-2 mb-2">{{ $evidence }}</div>
                @empty
                    <div class="text-muted">No evidence listed.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
