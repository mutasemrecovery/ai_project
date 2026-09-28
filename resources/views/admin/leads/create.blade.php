@extends('admin.layouts.app')
@section('title', __('messages.lg_manual_lead'))

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ __('messages.lg_manual_lead') }}</h1>
    <p class="page-sub">{{ __('messages.lg_manual_lead_subtitle') }}</p>
</div>

<div class="panel-card">
    <div class="panel-card-body">
        <form method="POST" action="{{ route('admin.leads.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6"><label class="form-label">{{ __('messages.lg_company') }}</label><input name="company_name" class="form-control" required value="{{ old('company_name') }}"></div>
            <div class="col-md-6"><label class="form-label">{{ __('messages.lg_campaigns') }}</label><select name="campaign_id" class="form-select"><option value="">{{ __('messages.lg_none') }}</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}">{{ $campaign->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('messages.lg_website') }}</label><input name="website" class="form-control" value="{{ old('website') }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('messages.lg_email') }}</label><input name="email" type="email" class="form-control" value="{{ old('email') }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('messages.lg_phone') }}</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('messages.lg_country') }}</label><input name="country" class="form-control" value="{{ old('country') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('messages.lg_city') }}</label><input name="city" class="form-control" value="{{ old('city') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('messages.lg_industry') }}</label><input name="industry" class="form-control" value="{{ old('industry') }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('messages.lg_location') }}</label><input name="location" class="form-control" value="{{ old('location') }}"></div>
            <div class="col-12"><label class="form-label">{{ __('messages.lg_source_url') }}</label><input name="source_url" class="form-control" value="{{ old('source_url') }}"></div>
            <div class="col-12"><label class="form-label">{{ __('messages.lg_description') }}</label><textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea></div>
            <div class="col-12 d-flex gap-2"><button class="btn-primary-sm"><i class="bi bi-save"></i> {{ __('messages.lg_save') }}</button><a href="{{ route('admin.leads.index') }}" class="btn-outline-sm">{{ __('messages.lg_cancel') }}</a></div>
        </form>
    </div>
</div>
@endsection
