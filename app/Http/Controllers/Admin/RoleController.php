<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->perm('role-table'))->only(['index', 'show']);
        $this->middleware($this->perm('role-add'))->only(['create', 'store']);
        $this->middleware($this->perm('role-edit'))->only(['edit', 'update']);
        $this->middleware($this->perm('role-delete'))->only(['destroy', 'delete']);
    }

    // Permissions grouped by module (used in create/edit UI)
    public static function permGroups(): array
    {
        return [
            'الأدوار والموظفون'    => ['role-table', 'role-add', 'role-edit', 'role-delete', 'employee-table', 'employee-add', 'employee-edit', 'employee-delete'],
            'Lead Generation' => ['lead-table', 'lead-add', 'lead-edit', 'lead-delete', 'campaign-table', 'campaign-add', 'campaign-edit', 'campaign-delete', 'outreach-table', 'outreach-approve'],
        ];
    }

    public function index(Request $request)
    {
        $roles = Role::withCount('permissions')
            ->where('guard_name', 'admin')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permGroups = self::permGroups();
        $allPerms   = Permission::where('guard_name', 'admin')->pluck('id', 'name');
        return view('admin.roles.create', compact('permGroups', 'allPerms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100|unique:roles,name',
            'perms'   => 'nullable|array',
            'perms.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'admin']);
        $role->syncPermissions(Permission::whereIn('id', $request->input('perms', []))->get());

        return redirect()->route('admin.role.index')->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function edit(int $id)
    {
        $role       = Role::findOrFail($id);
        $permGroups = self::permGroups();
        $allPerms   = Permission::where('guard_name', 'admin')->pluck('id', 'name');
        $assigned   = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role', 'permGroups', 'allPerms', 'assigned'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'    => 'required|string|max:100|unique:roles,name,' . $id,
            'perms'   => 'nullable|array',
            'perms.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);
        $role->syncPermissions(Permission::whereIn('id', $request->input('perms', []))->get());

        return redirect()->route('admin.role.index')->with('success', 'تم تحديث الدور بنجاح.');
    }

    public function destroy(int $id)
    {
        Role::findOrFail($id)->delete();
        return back()->with('success', 'تم حذف الدور.');
    }

    // Legacy AJAX delete endpoint (kept for backward compat)
    public function delete(Request $request)
    {
        Role::where('id', $request->id)->delete();
        return 1;
    }
}
