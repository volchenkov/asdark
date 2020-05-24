<?php

namespace App\Http\Middleware;

use Closure;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $hasSuppliedCreds = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        $isNotAuthenticated = (
            !$hasSuppliedCreds ||
            $_SERVER['PHP_AUTH_USER'] != getenv('AUTH_USER') ||
            $_SERVER['PHP_AUTH_PW']   != getenv('AUTH_PWD')
        );
        if ($isNotAuthenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            exit;
        }

        return $next($request);
    }
}
