<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Redirect based on guard
                switch ($guard) {
                    case 'admin':
                        return redirect()->route('admin.home');
                    case 'teacher':
                        return redirect()->route('teacher.home');
                    case 'student':
                        return redirect()->route('student.home');
                    default:
                        // Default redirect for undefined guards
                        return redirect('/');
                }
            }
        }

        return $next($request);
    }
}