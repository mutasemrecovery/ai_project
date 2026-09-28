@extends('admin.layouts.app')
@section('title', __('messages.contact_messages_title'))

@section('content')

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div><h1 class="page-title">{{ __('messages.contact_messages_title') }}</h1><p class="page-sub">{{ __('messages.manage_contact_desc') }}</p></div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Status Counts --}}
<div class="row g-3 mb-3">
    @php $statusConfig = ['new'=>['pill-warning','new_status'],'read'=>['pill-info','read_status'],'replied'=>['pill-success','replied_status'],'closed'=>['pill-neutral','closed_status']]; @endphp
    @foreach($counts as $s => $count)
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-value">{{ $count }}</div>
            <div class="stat-label"><span class="pill {{ $statusConfig[$s][0] }}">{{ __('messages.'.$statusConfig[$s][1]) }}</span></div>
        </div>
    </div>
    @endforeach
</div>

<div class="panel-card mb-3">
    <div class="panel-card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('messages.search_contact_ph') }}">
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('messages.All Status') }}</option>
                    @foreach(['new','read','replied','closed'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('messages.'.$statusConfig[$s][1]) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn-primary-sm w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="panel-card">
    <div class="panel-card-body p-0">
        <table class="data-table">
            <thead>
                <tr><th>{{ __('messages.sender') }}</th><th>{{ __('messages.subject_label') }}</th><th>{{ __('messages.message_label') }}</th><th>{{ __('messages.date') }}</th><th>{{ __('messages.Status') }}</th><th>{{ __('messages.Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr>
                    <td>
                        <div style="font-weight:500">{{ $msg->first_name }} {{ $msg->last_name }}</div>
                        <div style="font-size:.75rem;color:var(--muted)">{{ $msg->email }}</div>
                        @if($msg->phone)<div style="font-size:.75rem;color:var(--muted)">{{ $msg->phone }}</div>@endif
                    </td>
                    <td>{{ Str::limit($msg->subject, 35) }}</td>
                    <td style="color:var(--muted);font-size:.83rem">{{ Str::limit($msg->message, 60) }}</td>
                    <td style="color:var(--muted)">{{ $msg->created_at->format('M d, Y') }}</td>
                    <td><span class="pill {{ $statusConfig[$msg->status][0] }}">{{ __('messages.'.$statusConfig[$msg->status][1]) }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.contact_messages.show', $msg->id) }}" class="btn-outline-sm" style="padding:4px 8px" title="{{ __('messages.view_reply') }}">
                                <i class="bi bi-envelope-open"></i>
                            </a>
                            <form action="{{ route('admin.contact_messages.destroy', $msg->id) }}" method="POST" onsubmit="return confirm('{{ __('messages.close_message_confirm') }}')">
                                @csrf @method('DELETE')
                                <button class="btn-outline-sm" style="padding:4px 8px;color:#dc2626;border-color:#fecaca" title="{{ __('messages.close_message') }}"><i class="bi bi-x-circle"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4" style="color:var(--muted)">{{ __('messages.no_messages_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $messages->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
