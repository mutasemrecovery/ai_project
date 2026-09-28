@extends('admin.layouts.app')
@section('title', $contactMessage->first_name)

@section('content')

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">{{ __('messages.message_details') }}</h1>
        <p class="page-sub">{{ __('messages.from_label') }} {{ $contactMessage->first_name }} {{ $contactMessage->last_name }}</p>
    </div>
    <a href="{{ route('admin.contact_messages.index') }}" class="btn-outline-sm"><i class="bi bi-arrow-left"></i> {{ __('messages.Back') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3">

    <div class="col-12 col-xl-8">

        {{-- Original Message --}}
        <div class="panel-card mb-3">
            <div class="panel-card-header d-flex justify-content-between align-items-center">
                <h2 class="panel-card-title">{{ $contactMessage->subject }}</h2>
                @php $statusConfig = ['new'=>['pill-warning','new_status'],'read'=>['pill-info','read_status'],'replied'=>['pill-success','replied_status'],'closed'=>['pill-neutral','closed_status']]; @endphp
                <span class="pill {{ $statusConfig[$contactMessage->status][0] }}">{{ __('messages.'.$statusConfig[$contactMessage->status][1]) }}</span>
            </div>
            <div class="panel-card-body">
                <div style="font-size:.87rem;line-height:1.8;white-space:pre-wrap;color:var(--text)">{{ $contactMessage->message }}</div>
            </div>
        </div>

        {{-- Reply Form --}}
        @if($contactMessage->status !== 'closed')
        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.reply_label') }}</h2></div>
            <div class="panel-card-body">
                @if($contactMessage->admin_reply)
                <div class="p-3 mb-3" style="background:rgba(124,58,237,.05);border-radius:8px;border-left:3px solid var(--primary)">
                    <div style="font-size:.75rem;font-weight:600;color:var(--primary);margin-bottom:6px">{{ __('messages.previous_reply') }} · {{ $contactMessage->replied_at?->format('M d, Y H:i') }}</div>
                    <div style="font-size:.87rem;white-space:pre-wrap">{{ $contactMessage->admin_reply }}</div>
                </div>
                @endif
                <form action="{{ route('admin.contact_messages.reply', $contactMessage->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <textarea name="admin_reply" rows="5" class="form-control @error('admin_reply') is-invalid @enderror" placeholder="{{ __('messages.write_reply_ph') }}" required>{{ old('admin_reply', $contactMessage->admin_reply) }}</textarea>
                        @error('admin_reply')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn-primary-sm"><i class="bi bi-send"></i> {{ __('messages.send_reply') }}</button>
                </form>
            </div>
        </div>
        @else
        <div class="panel-card mb-3">
            <div class="panel-card-body" style="color:var(--muted);text-align:center;padding:24px">
                <i class="bi bi-x-circle" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                {{ __('messages.message_closed_notice') }}
            </div>
        </div>
        @endif

    </div>

    {{-- Sender Info --}}
    <div class="col-12 col-xl-4">
        <div class="panel-card mb-3">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.sender') }}</h2></div>
            <div class="panel-card-body">
                <div style="font-size:.87rem">
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">{{ __('messages.name_field') }}</span>
                        <strong>{{ $contactMessage->first_name }} {{ $contactMessage->last_name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">{{ __('messages.email_label') }}</span>
                        <a href="mailto:{{ $contactMessage->email }}" style="color:var(--primary)">{{ $contactMessage->email }}</a>
                    </div>
                    @if($contactMessage->phone)
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">{{ __('messages.phone_label') }}</span>
                        <span>{{ $contactMessage->phone }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color:var(--muted)">{{ __('messages.received_label') }}</span>
                        <span>{{ $contactMessage->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span style="color:var(--muted)">{{ __('messages.Status') }}</span>
                        <span class="pill {{ $statusConfig[$contactMessage->status][0] }}">{{ __('messages.'.$statusConfig[$contactMessage->status][1]) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-card-header"><h2 class="panel-card-title">{{ __('messages.Actions') }}</h2></div>
            <div class="panel-card-body d-flex flex-column gap-2">
                <a href="mailto:{{ $contactMessage->email }}?subject=Re: {{ $contactMessage->subject }}" class="btn-outline-sm justify-content-center">
                    <i class="bi bi-envelope"></i> {{ __('messages.email_directly') }}
                </a>
                @if($contactMessage->status !== 'closed')
                <form action="{{ route('admin.contact_messages.destroy', $contactMessage->id) }}" method="POST" onsubmit="return confirm('{{ __('messages.close_message_confirm') }}')">
                    @csrf @method('DELETE')
                    <button class="btn-outline-sm w-100 justify-content-center" style="color:#dc2626;border-color:#fecaca">
                        <i class="bi bi-x-circle"></i> {{ __('messages.close_message') }}
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
