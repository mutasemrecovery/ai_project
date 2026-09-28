<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->perm('employee-table'))->only(['index']);
        $this->middleware($this->perm('employee-add'))->only(['create', 'store']);
        $this->middleware($this->perm('employee-edit'))->only(['edit', 'update']);
        $this->middleware($this->perm('employee-delete'))->only(['destroy']);
    }

    public function index(Request $request)
    {
        $employees = Admin::where('is_super', false)
            ->with('roles')
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($q2) => $q2
                    ->where('name', 'like', "%$s%")
                    ->orWhere('username', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")
                )
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.employee.index', compact('employees'));
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->orderBy('name')->get();
        return view('admin.employee.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:200',
            'username'              => 'required|string|max:100|unique:admins,username',
            'email'                 => 'nullable|email|max:200|unique:admins,email',
            'password'              => 'required|string|min:8|confirmed',
            'roles'                 => 'nullable|array',
            'roles.*'               => 'integer|exists:roles,id',
        ]);

        $employee = Admin::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->filled('roles')) {
            $employee->syncRoles(
                Role::whereIn('id', $request->roles)->where('guard_name', 'admin')->get()
            );
        }

        return redirect()->route('admin.employee.index')
            ->with('success', 'تم إنشاء الموظف بنجاح.');
    }

    public function edit(int $id)
    {
        $employee     = Admin::where('is_super', false)->findOrFail($id);
        $roles        = Role::where('guard_name', 'admin')->orderBy('name')->get();
        $assignedRoles = $employee->roles->pluck('id')->toArray();

        return view('admin.employee.edit', compact('employee', 'roles', 'assignedRoles'));
    }

    public function update(Request $request, int $id)
    {
        $employee = Admin::where('is_super', false)->findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:200',
            'username' => 'required|string|max:100|unique:admins,username,' . $id,
            'email'    => 'nullable|email|max:200|unique:admins,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles'    => 'nullable|array',
            'roles.*'  => 'integer|exists:roles,id',
        ]);

        $data = [
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);
        $employee->syncRoles(
            Role::whereIn('id', $request->input('roles', []))->where('guard_name', 'admin')->get()
        );

        return redirect()->route('admin.employee.index')
            ->with('success', 'تم تحديث الموظف بنجاح.');
    }

    public function destroy(int $id)
    {
        $employee = Admin::where('is_super', false)->findOrFail($id);
        $employee->syncRoles([]);
        $employee->delete();

        return back()->with('success', 'تم حذف الموظف.');
    }
}
