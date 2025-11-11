<?php
namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class EnsureAdmin
{
public function handle(Request $request, Closure $next): Response
{
if (!session()->has('admin_id')) {
return redirect()->route('admin.login.form')->with('error','Please login as Admin.');
}
return $next($request);
}
}