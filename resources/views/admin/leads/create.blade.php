@extends('admin.layouts.app')
@section('title', 'Manual Lead')

@section('content')
<div class="page-header">
    <h1 class="page-title">Manual Lead</h1>
    <p class="page-sub">Add a public business lead for review and analysis.</p>
</div>

<div class="panel-card">
    <div class="panel-card-body">
        <form method="POST" action="{{ route('admin.leads.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6"><label class="form-label">Company</label><input name="company_name" class="form-control" required value="{{ old('company_name') }}"></div>
            <div class="col-md-6"><label class="form-label">Campaign</label><select name="campaign_id" class="form-select"><option value="">None</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}">{{ $campaign->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Website</label><input name="website" class="form-control" value="{{ old('website') }}"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email') }}"></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
            <div class="col-md-3"><label class="form-label">Country</label><input name="country" class="form-control" value="{{ old('country') }}"></div>
            <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city') }}"></div>
            <div class="col-md-3"><label class="form-label">Industry</label><input name="industry" class="form-control" value="{{ old('industry') }}"></div>
            <div class="col-md-3"><label class="form-label">Location</label><input name="location" class="form-control" value="{{ old('location') }}"></div>
            <div class="col-12"><label class="form-label">Source URL</label><input name="source_url" class="form-control" value="{{ old('source_url') }}"></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea></div>
            <div class="col-12 d-flex gap-2"><button class="btn-primary-sm"><i class="bi bi-save"></i> Save</button><a href="{{ route('admin.leads.index') }}" class="btn-outline-sm">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
