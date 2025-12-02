<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('teacher_id')) {
            return redirect()->route('teacher.login.form')
                ->with('error', 'Please login as Teacher.');
        }

        return $next($request);
    }
}
