@extends('admin.layouts.app')
@section('title', 'New Campaign')

@section('content')
    <div class="page-header">
        <h1 class="page-title">New Campaign</h1>
        <p class="page-sub">Define a focused discovery target.</p>
    </div>
    <div class="panel-card">
        <div class="panel-card-body">
            <form method="POST" action="{{ route('admin.campaigns.store') }}">@include('admin.campaigns._form')</form>
        </div>
    </div>
@endsection
