<?php

namespace App\Http\Middleware;

use App\Models\ExtensionToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token gate for the extension-facing API. The token was issued when the
 * extension paired against an authenticated console session, so possessing a
 * valid one is the authorization. The resolved token is stashed on the request
 * as `extension_token` for controllers to attribute runs.
 */
class AuthenticateExtension
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        $token = $bearer ? ExtensionToken::resolve($bearer) : null;

        if (! $token) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'Pair the extension with the Atlas console to get a token.',
            ], 401);
        }

        $token->touchUsage();
        $request->attributes->set('extension_token', $token);

        return $next($request);
    }
}
