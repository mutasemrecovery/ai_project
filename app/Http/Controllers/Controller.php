<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Returns a middleware closure that aborts with 403 if the admin
     * guard user does not have the given Spatie permission.
     * Super-admins bypass this automatically via gate->before in AuthServiceProvider.
     */
    protected function perm(string $permission): \Closure
    {
        return function ($request, \Closure $next) use ($permission) {
            abort_unless(
                auth()->guard('admin')->user()?->can($permission),
                403,
                'غير مصرح لك بهذا الإجراء.'
            );
            return $next($request);
        };
    }
}
