@extends('admin.layouts.app')
@section('title', __('messages.lg_edit_campaign'))

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ __('messages.lg_edit_campaign') }}</h1>
        <p class="page-sub">{{ $campaign->name }}</p>
    </div>
    <div class="panel-card">
        <div class="panel-card-body">
            <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">@method('PATCH')
                @include('admin.campaigns._form')</form>
        </div>
    </div>
@endsection
