<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'account_inactive',
                    'message' => 'Il tuo account è stato disattivato. Contatta l\'amministratore.',
                ], Response::HTTP_FORBIDDEN);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Il tuo account è stato disattivato. Contatta l\'amministratore.']);
        }

        return $next($request);
    }
}
