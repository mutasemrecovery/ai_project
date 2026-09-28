@extends('admin.layouts.app')
@section('title', __('messages.lg_new_campaign'))

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ __('messages.lg_new_campaign') }}</h1>
        <p class="page-sub">{{ __('messages.lg_campaign_create_subtitle') }}</p>
    </div>
    <div class="panel-card">
        <div class="panel-card-body">
            <form method="POST" action="{{ route('admin.campaigns.store') }}">@include('admin.campaigns._form')</form>
        </div>
    </div>
@endsection
