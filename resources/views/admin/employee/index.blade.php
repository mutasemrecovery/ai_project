@extends('admin.layouts.app')
@section('title', 'الموظفون')

@section('content')

<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <h1 class="page-title">الموظفون</h1>
        <p class="page-sub">إدارة حسابات الموظفين وأدوارهم</p>
    </div>
    @can('employee-add')
    <a href="{{ route('admin.employee.create') }}" class="btn-primary-sm">
        <i class="bi bi-person-plus"></i> إضافة موظف جديد
    </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="panel-card mb-3">
    <div class="panel-card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="form-control form-control-sm" placeholder="ابحث بالاسم أو اسم المستخدم أو البريد...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn-primary-sm"><i class="bi bi-search"></i></button>
            </div>
            @if(request('search'))
            <div class="col-auto">
                <a href="{{ route('admin.employee.index') }}" class="btn-outline-sm"><i class="bi bi-x"></i> مسح</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="panel-card">
    <div class="panel-card-header d-flex align-items-center justify-content-between">
        <h2 class="panel-card-title"><i class="bi bi-people"></i> قائمة الموظفين</h2>
        <span class="pill pill-info">{{ $employees->total() }} موظف</span>
    </div>
    <div class="panel-card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>اسم المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الأدوار</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr>
                        <td>{{ $loop->iteration + ($employees->currentPage() - 1) * $employees->perPage() }}</td>
                        <td><span class="fw-semibold">{{ $emp->name }}</span></td>
                        <td><span class="text-muted">{{ $emp->username }}</span></td>
                        <td>{{ $emp->email ?: '—' }}</td>
                        <td>
                            @forelse($emp->roles as $role)
                                <span class="pill pill-info">{{ $role->name }}</span>
                            @empty
                                <span class="pill pill-neutral">بدون دور</span>
                            @endforelse
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('employee-edit')
                                <a href="{{ route('admin.employee.edit', $emp->id) }}"
                                   class="btn-icon-sm btn-edit" title="تعديل">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('employee-delete')
                                <form action="{{ route('admin.employee.destroy', $emp->id) }}" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف موظف «{{ $emp->name }}»؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon-sm btn-delete" title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            لا يوجد موظفون مسجلون
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
    <div class="panel-card-body border-top pt-3">
        {{ $employees->links() }}
    </div>
    @endif
</div>

@endsection
