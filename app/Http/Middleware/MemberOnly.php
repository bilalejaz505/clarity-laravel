<?php

namespace App\Http\Middleware;

use App\Services\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberOnly
{
    public function __construct(private EntitlementService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $this->access->admin($request->user())) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Member access required.'], 403)
                : redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
