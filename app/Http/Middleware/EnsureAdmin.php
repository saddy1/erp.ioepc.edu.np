<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Still use your existing session-based check
        $adminId = session('admin_id');

        if (!$adminId) {
            return redirect()
                ->route('admin.login.form')
                ->with('error', 'Please login as Admin.');
        }

        // Load admin, with department + faculties for HOD scoping
        $admin = Admin::with(['department.faculties'])->find($adminId);

        // If admin record is missing (deleted from DB), force re-login
        if (!$admin) {
            session()->forget('admin_id');

            return redirect()
                ->route('admin.login.form')
                ->with('error', 'Your admin account was not found. Please login again.');
        }

        // Make admin available to controllers
        $request->attributes->set('admin', $admin);

        // Make admin available in all Blade views as $authAdmin
        view()->share('authAdmin', $admin);

        return $next($request);
    }
}
