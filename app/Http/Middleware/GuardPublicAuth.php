<?php

namespace App\Http\Middleware;

use App\Support\VerifyTurnstile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class GuardPublicAuth
{
    public function __construct(private VerifyTurnstile $turnstile) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post')) {
            return $next($request);
        }

        if ($request->is('register')) {
            $key = 'register:'.$request->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                return back()->withErrors(['email' => __('app.register_limited')])->withInput();
            }

            if ($this->turnstile->configured() && ! $this->turnstile->passes($request->input('cf-turnstile-response'))) {
                return back()->withErrors(['email' => __('app.turnstile_failed')])->withInput();
            }

            RateLimiter::hit($key, 3600);
        }

        if ($request->is('login') && $this->turnstile->configured()) {
            $attempts = RateLimiter::attempts('login-fails:'.$request->ip());

            if ($attempts > 0 && ! $this->turnstile->passes($request->input('cf-turnstile-response'))) {
                return back()->withErrors(['email' => __('app.turnstile_failed')])->withInput();
            }
        }

        return $next($request);
    }
}
