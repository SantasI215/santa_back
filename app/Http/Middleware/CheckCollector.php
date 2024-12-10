<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckCollector
{
    public function handle(Request $request, Closure $next)
    {

        if (Auth::check() && Auth::user()->role === 'collector') {
            return $next($request);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }
}
