<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetApiLocale
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            App::setLocale('ja_api');
        }

        return $next($request);
    }
}
