<?php

namespace App\Http\Middleware;

use App\Connection;
use Closure;

class CheckVkConnection
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
        $conn = Connection::where('system', 'vk')->first();
        if (!$conn || !isset($vkConnection->data['account_id'])) {
            return redirect()->route('vkAuth.state', ['notify' => 1]);
        }

        return $next($request);
    }

}
