@extends('admin.layouts.app')
@section('title', 'تعديل الموظف: ' . $employee->name)

@section('content')

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">تعديل الموظف</h1>
        <p class="page-sub">{{ $employee->name }}</p>
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

<form action="{{ route('admin.employee.update', $employee->id) }}" method="POST">
@csrf @method('PUT')

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
                        <input type="text" name="name" value="{{ old('name', $employee->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" name="username" value="{{ old('username', $employee->username) }}"
                               class="form-control @error('username') is-invalid @enderror" required>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            كلمة المرور الجديدة
                            <small class="text-muted">(اتركها فارغة للإبقاء على الحالية)</small>
                        </label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="password_confirmation"
                               class="form-control" autocomplete="new-password">
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
                @php $checked = old('roles') !== null ? array_map('intval', old('roles', [])) : $assignedRoles; @endphp
                <div class="d-flex flex-column gap-2">
                    @foreach($roles as $role)
                    @php $isChecked = in_array($role->id, $checked); @endphp
                    <label class="d-flex align-items-center gap-2 p-2 rounded border cursor-pointer role-item
                           {{ $isChecked ? 'selected' : '' }}">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               class="role-checkbox"
                               {{ $isChecked ? 'checked' : '' }}>
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
    <button type="submit" class="btn-primary-sm"><i class="bi bi-save"></i> حفظ التغييرات</button>
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
