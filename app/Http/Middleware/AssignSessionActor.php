<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignSessionActor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 401, 'Authentication required.');

        abort_unless(
            in_array($user->role, ['CLINICIAN', 'DISTRICT_OFFICER'], true),
            403,
            'Your account has no assigned CareLink role.'
        );

        if ($user->role === 'CLINICIAN') {
            abort_unless(
                filled($user->facility_id),
                403,
                'No facility has been assigned to your account.'
            );
        }

        $request->attributes->set('actor', [
            'role' => $user->role,
            'facility_id' => $user->facility_id,
        ]);

        return $next($request);
    }
}
