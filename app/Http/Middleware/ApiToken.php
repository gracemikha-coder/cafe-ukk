<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiToken
{
    public function handle (Request $request, Closure $next, string $mode = ''): Response
    {
        $token = $request->bearerToken();
        $user  = $token ? User::where('api_token', hash('sha256', $token))->first() : null;

        if (!$user) {
            return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
        }

        if ($user->role !== 'pelanggan') {
            return response()->json(['message' => 'Anda tidak memiliki akses ke fitur ini.'], 403);
        }

        if ($user->wajib_ganti_password && $mode !== 'bebas') {
            return response()->json([
                'message'              => 'Anda harus mengganti password sementara terlebih dahulu.',
                'wajib_ganti_password' => true,
            ], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

