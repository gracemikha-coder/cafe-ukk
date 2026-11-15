<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
    $user = $request->user();
    
    // support kalau role berupa Enum atau string
    $role = $user?->role instanceof \BackedEnum ? $user->role->value : $user?->role;

    if (!$user || !in_array($role, $roles)){
        $pesan = 'Anda tidak memiliki akses ke fitur ini.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan], 403);
        }

        abort(403, $pesan);
    }
return $next($request);
}
}
