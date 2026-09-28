@extends('admin.layouts.app')
@section('title', 'إضافة موظف جديد')

@section('content')

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">إضافة موظف جديد</h1>
        <p class="page-sub">أنشئ حساباً جديداً للموظف وحدد دوره في النظام</p>
    </div>
    <a href="{{ route('admin.employee.index') }}" class="btn-outline-sm">
        <i class="bi bi-arrow-right"></i> العودة للقائمة
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.employee.store') }}" method="POST">
@csrf

<div class="row g-4">

    {{-- Account info --}}
    <div class="col-12 col-xl-7">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <h2 class="panel-card-title"><i class="bi bi-person-badge"></i> بيانات الحساب</h2>
            </div>
            <div class="panel-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" name="username" value="{{ old('username') }}"
                               class="form-control @error('username') is-invalid @enderror"
                               autocomplete="off" required>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               autocomplete="off">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation"
                               class="form-control" autocomplete="new-password" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Roles --}}
    <div class="col-12 col-xl-5">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <h2 class="panel-card-title"><i class="bi bi-shield-lock"></i> الأدوار الوظيفية</h2>
            </div>
            <div class="panel-card-body">
                @if($roles->isEmpty())
                    <p class="text-muted small mb-0">
                        لا توجد أدوار مضافة بعد.
                        <a href="{{ route('admin.role.create') }}">أنشئ دوراً الآن</a>
                    </p>
                @else
                <div class="d-flex flex-column gap-2">
                    @foreach($roles as $role)
                    <label class="d-flex align-items-center gap-2 p-2 rounded border cursor-pointer role-item
                           {{ in_array($role->id, old('roles', [])) ? 'selected' : '' }}">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               class="role-checkbox"
                               {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                        <span class="fw-semibold">{{ $role->name }}</span>
                    </label>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

<div class="d-flex gap-2 mt-4 pb-4">
    <button type="submit" class="btn-primary-sm"><i class="bi bi-save"></i> حفظ الموظف</button>
    <a href="{{ route('admin.employee.index') }}" class="btn-outline-sm">إلغاء</a>
</div>

</form>

@push('styles')
<style>
.cursor-pointer { cursor: pointer; }
.role-item { cursor: pointer; transition: background .15s, border-color .15s; }
.role-item:hover, .role-item.selected { background: var(--primary-50, #eff6ff); border-color: var(--primary-400, #60a5fa) !important; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.role-checkbox').forEach(function (cb) {
    cb.addEventListener('change', function () {
        this.closest('.role-item').classList.toggle('selected', this.checked);
    });
});
</script>
@endpush

@endsection
