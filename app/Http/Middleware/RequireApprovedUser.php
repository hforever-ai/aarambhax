<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pending users are READ-ONLY: they can browse the app but cannot perform
 * any DB-write or LLM-triggering action until an admin approves them.
 *
 * Rule: GET/HEAD requests pass through; everything else (POST/PUT/PATCH/DELETE)
 * is rejected with 403 unless the user is approved (or is an admin — admins
 * are always allowed in case of edge cases like an admin downgrading themselves).
 *
 * Allowlist exists for routes that pending users genuinely need to write to
 * (logout, profile updates if we add them later).
 */
class RequireApprovedUser
{
    /**
     * Route names that pending users may submit (POST etc.) even before approval.
     * Use route names so URL structure changes don't silently break the allowlist.
     */
    private const PENDING_ALLOWED_ROUTES = [
        'app.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not authenticated — let auth middleware handle it
        if (! $user) {
            return $next($request);
        }

        // Approved + admin: always allowed
        if ($user->isApproved() || $user->isAdmin()) {
            return $next($request);
        }

        // Read-only requests pass for pending users
        if (in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Allowlist for essential pending-user writes
        $routeName = optional($request->route())->getName();
        if ($routeName && in_array($routeName, self::PENDING_ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // Block: user is pending or rejected, attempting a write
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'approval_required',
                'message' => 'Your account is pending admin approval. Write actions are disabled until approved.',
                'status' => $user->status,
            ], 403);
        }

        return back()
            ->withErrors([
                'approval' => $user->isRejected()
                    ? 'Your account was not approved. Contact admin if you believe this is a mistake.'
                    : 'Your account is pending admin approval — Ajay or Vikash bhai needs to approve you before you can use this feature.',
            ])
            ->withInput();
    }
}
