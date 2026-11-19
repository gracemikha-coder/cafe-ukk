<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
     public function daftar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama'     => 'required|string|max:100',
            // nomor HP: hanya angka, 10-15 digit, tidak boleh dipakai dua akun
            'nomor_hp' => ['bail', 'required', 'regex:/^[0-9]+$/', 'min:10', 'max:15', 'unique:users,username'],
            'password' => 'required|string|min:8',
        ], [
            'nama.required'     => 'Nama wajib diisi.',
            'nomor_hp.required' => 'Nomor HP wajib diisi.',
            'nomor_hp.regex'    => 'Nomor HP hanya boleh berisi angka.',
            'nomor_hp.min'      => 'Nomor HP minimal 10 digit.',
            'nomor_hp.max'      => 'Nomor HP maksimal 15 digit.',
            'nomor_hp.unique'   => 'Nomor HP sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 8 karakter.',
        ]);

        User::create([
            'nama' => $data['nama'],
            'username' => $data['nomor_hp'],
            'password' => $data['password'],
            'role' => 'pelanggan',
            'wajib_ganti_password' => false,
        ]);

        return response()->json(['message' => 'Pendaftaran berhasil. Silakan login'], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'nomor_hp' =>'required',
            'password' => 'required', 
        ],[
            'nomor_hp.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

         $user = User::where('username', $request->input('nomor_hp'))
            ->where('role', 'pelanggan')
            ->first();

        if (!$user || !Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json(['message' => 'Nomor HP atau password salah.'], 401);
        }

        $token = Str::random(60);
        $user->forceFill(['api_token' => hash('sha256', $token)])->save();

        return response()->json([
            'message'              => 'Login berhasil.',
            'token'                => $token,
            'wajib_ganti_password' => (bool) $user->wajib_ganti_password,
            'pengguna'             => ['nama' => $user->nama, 'nomor_hp' => $user->username],
        ]);
    }

    public function gantiPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password_lama' => 'required',
            'password_baru' => 'required|string|min:8',
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min'      => 'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();

        if (!Hash::check((string) $request->input('password_lama'), $user->password)) {
            throw ValidationException::withMessages(['password_lama' => ['Password lama salah.']]);
        }
        if (Hash::check((string) $request->input('password_baru'), $user->password)) {
            throw ValidationException::withMessages(['password_baru' => ['Password baru tidak boleh sama dengan password lama.']]);
        }

        $user->password = $request->input('password_baru');   // otomatis di-hash oleh model
        $user->wajib_ganti_password = false;
        $user->save();

        return response()->json(['message' => 'Password berhasil diganti.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Anda sudah keluar.']);
    }
}
