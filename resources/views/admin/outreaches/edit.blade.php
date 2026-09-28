@extends('admin.layouts.app')
@section('title', __('messages.lg_review_outreach'))

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ __('messages.lg_review_outreach') }}</h1>
    <p class="page-sub">{{ $outreach->lead->company_name }} - {{ __('messages.lg_message_type_' . $outreach->message_type) }}</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_message') }}</h2></div>
            <div class="panel-card-body">
                <form method="POST" action="{{ route('admin.outreaches.update', $outreach) }}" class="row g-3">
                    @csrf @method('PATCH')
                    <div class="col-12"><label class="form-label">{{ __('messages.lg_subject') }}</label><input name="subject" class="form-control" value="{{ old('subject', $outreach->subject) }}"></div>
                    <div class="col-12"><label class="form-label">{{ __('messages.lg_body') }}</label><textarea name="body" class="form-control" rows="12" required>{{ old('body', $outreach->body) }}</textarea></div>
                    <div class="col-12 d-flex gap-2"><button class="btn-outline-sm"><i class="bi bi-save"></i> {{ __('messages.lg_save_edits') }}</button></div>
                </form>
                <hr>
                <form method="POST" action="{{ route('admin.outreaches.approve', $outreach) }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="subject" value="{{ old('subject', $outreach->subject) }}">
                    <input type="hidden" name="body" value="{{ old('body', $outreach->body) }}">
                    <div class="col-12 form-check ms-2"><input type="checkbox" name="send_now" value="1" class="form-check-input"><label class="form-check-label">{{ __('messages.lg_send_now') }}</label></div>
                    <div class="col-auto"><button class="btn-primary-sm"><i class="bi bi-check-circle"></i> {{ __('messages.lg_approve') }}</button></div>
                </form>
                <form method="POST" action="{{ route('admin.outreaches.reject', $outreach) }}" class="mt-2">@csrf<button class="btn-outline-sm"><i class="bi bi-x-circle"></i> {{ __('messages.lg_reject') }}</button></form>
                @if($outreach->status === \App\Models\Outreach::STATUS_APPROVED)
                    <form method="POST" action="{{ route('admin.outreaches.send', $outreach) }}" class="mt-2">@csrf<button class="btn-primary-sm"><i class="bi bi-send"></i> {{ __('messages.lg_send_approved_message') }}</button></form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.lg_evidence_used') }}</h2></div>
            <div class="panel-card-body">
                <div class="mb-2"><span class="pill pill-neutral">{{ __('messages.lg_status_' . $outreach->status) }}</span></div>
                @forelse($outreach->personalization_evidence ?: [] as $evidence)
                    <div class="small border rounded p-2 mb-2">{{ $evidence }}</div>
                @empty
                    <div class="text-muted">{{ __('messages.lg_no_evidence_listed') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
