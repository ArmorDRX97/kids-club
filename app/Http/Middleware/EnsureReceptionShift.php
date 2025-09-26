<?php

namespace App\Http\Middleware;

use App\Services\ShiftManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReceptionShift
{
    public function __construct(private readonly ShiftManager $shiftManager)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->hasRole('Admin')) {
            return $next($request);
        }

        if (!$user->hasRole('Receptionist')) {
            return $next($request);
        }

        $shift = $this->shiftManager->getActiveShift($user);

        if (!$shift) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Смена не начата. Начните смену, чтобы выполнить действие.',
                ], 423);
            }

            return back()->with('error', 'Смена не начата. Начните смену, чтобы выполнить действие.');
        }

        return $next($request);
    }
}
