<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OutreachController;
use App\Http\Controllers\Admin\RoleController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Spatie\Permission\Models\Permission;

Route::group(['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']], function () {

    Route::group(['prefix' => 'admin', 'middleware' => 'auth:admin'], function () {

        // ── Dashboard ─────────────────────────────────────────────────
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');

        // ── Admin profile ─────────────────────────────────────────────
        Route::get('/admin/edit/{id}',    [LoginController::class, 'editlogin'])->name('admin.login.edit');
        Route::post('/admin/update/{id}', [LoginController::class, 'updatelogin'])->name('admin.login.update');

        // ── Roles & Employees ─────────────────────────────────────────
        Route::resource('employee', EmployeeController::class, ['as' => 'admin'])->except(['show']);
        Route::get('role',               [RoleController::class, 'index'])->name('admin.role.index');
        Route::get('role/create',        [RoleController::class, 'create'])->name('admin.role.create');
        Route::get('role/{id}/edit',     [RoleController::class, 'edit'])->name('admin.role.edit');
        Route::patch('role/{id}',        [RoleController::class, 'update'])->name('admin.role.update');
        Route::post('role',              [RoleController::class, 'store'])->name('admin.role.store');
        Route::post('admin/role/delete',  [RoleController::class, 'delete'])->name('admin.role.delete');
        Route::delete('role/{id}',        [RoleController::class, 'destroy'])->name('admin.role.destroy');

        // Lead generation system
        Route::resource('campaigns', CampaignController::class, ['as' => 'admin'])->except(['show']);

        Route::resource('leads', LeadController::class, ['as' => 'admin'])->only(['index', 'create', 'store', 'show']);
        Route::post('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('admin.leads.status');
        Route::post('leads/{lead}/analyze', [LeadController::class, 'analyze'])->name('admin.leads.analyze');
        Route::post('leads/{lead}/generate-outreach', [LeadController::class, 'generateOutreach'])->name('admin.leads.generate-outreach');
        Route::post('leads/{lead}/approve', [LeadController::class, 'approve'])->name('admin.leads.approve');
        Route::post('leads/{lead}/ignore', [LeadController::class, 'ignore'])->name('admin.leads.ignore');

        Route::resource('outreaches', OutreachController::class, ['as' => 'admin'])->only(['index', 'edit', 'update']);
        Route::post('outreaches/{outreach}/approve', [OutreachController::class, 'approve'])->name('admin.outreaches.approve');
        Route::post('outreaches/{outreach}/reject', [OutreachController::class, 'reject'])->name('admin.outreaches.reject');
        Route::post('outreaches/{outreach}/send', [OutreachController::class, 'send'])->name('admin.outreaches.send');

        Route::get('/permissions/{guard_name}', function ($guard_name) {
            return response()->json(Permission::where('guard_name', $guard_name)->get());
        });

       

    });

       
});

Route::group(['namespace' => 'Admin', 'prefix' => 'admin', 'middleware' => 'guest:admin'], function () {
    Route::get('login',  [LoginController::class, 'show_login_view'])->name('admin.showlogin');
    Route::post('login', [LoginController::class, 'login'])->name('admin.login');
});
