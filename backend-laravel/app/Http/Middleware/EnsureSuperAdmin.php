<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== 'Super Admin') {
            return redirect()->route('vouchers.index')->with('error', 'Access denied. This page is restricted to Super Admin users only.');
        }

        return $next($request);
    }
}
