@extends('admin.layouts.app')
@section('title', 'Edit Campaign')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Campaign</h1>
        <p class="page-sub">{{ $campaign->name }}</p>
    </div>
    <div class="panel-card">
        <div class="panel-card-body">
            <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">@method('PATCH')
                @include('admin.campaigns._form')</form>
        </div>
    </div>
@endsection
