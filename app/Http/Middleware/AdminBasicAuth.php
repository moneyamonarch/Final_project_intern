<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        $u = (string) config('admin.user');
        $p = (string) config('admin.pass');

        if ($u === '' || $p === '' || ! hash_equals($u, (string) $request->getUser()) || ! hash_equals($p, (string) $request->getPassword())) {
            return response('Authentication required', 401, ['WWW-Authenticate' => 'Basic realm="Admin"']);
        }

        $r = $next($request);
        $r->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $r;
    }
}
