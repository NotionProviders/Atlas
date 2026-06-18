<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the private /console backend behind a session login.
 */
class ConsoleAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('console_authed') !== true) {
            // guest() records the current URL as "intended" so login can return here.
            return redirect()->guest(route('console.login'));
        }

        return $next($request);
    }
}
