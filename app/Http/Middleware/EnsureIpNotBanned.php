<?php

namespace App\Http\Middleware;

use App\Models\IpBan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIpNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $ban = IpBan::query()->where('ip', $request->ip())->first();

        if ($ban?->isActive()) {
            abort(403, __('app.banned'));
        }

        return $next($request);
    }
}
