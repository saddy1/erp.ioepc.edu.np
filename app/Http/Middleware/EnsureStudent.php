<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('student_id')) {
            return redirect()->route('student.login.form')
                ->with('error', 'Please login as Student.');
        }

        return $next($request);
    }
}
