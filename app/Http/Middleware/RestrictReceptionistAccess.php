<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RestrictReceptionistAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('Receptionist') && ! $user->hasRole('Admin')) {
            $route = $request->route();
            $routeName = $route?->getName();

            $allowedRouteNames = [
                'reception.index',
                'reception.mark',
                'reception.renew',
                'account.index',
                'account.password',
                'shift.start',
                'shift.stop',
                'attendances.store',
                'payments.store',
                'enrollments.store',
                'sections.index',
                'sections.members.index',
                'sections.members.search',
                'sections.members.store',
                'logout',
            ];

            $allowedPrefixes = [
                'reception.',
                'verification.',
                'password.',
                'account.',
                'children.',
                'sections.members.',
            ];

            $isAllowed = false;

            if ($routeName) {
                if (in_array($routeName, $allowedRouteNames, true)) {
                    $isAllowed = true;
                } else {
                    foreach ($allowedPrefixes as $prefix) {
                        if (Str::startsWith($routeName, $prefix)) {
                            $isAllowed = true;
                            break;
                        }
                    }
                }
            } elseif ($request->is('reception')) {
                $isAllowed = true;
            }

            if (! $isAllowed) {
                abort(403);
            }
        }

        return $next($request);
    }
}
