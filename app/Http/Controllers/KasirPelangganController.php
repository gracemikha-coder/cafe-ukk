<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KasirPelangganController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));

        $pelanggans = User::where('role', 'pelanggan')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($w) use ($cari) {
                    $w->where('nama', 'like', "%{$cari}%")
                      ->orWhere('username', 'like', "%{$cari}%");
                });
            })
            ->orderBy('nama')
            ->get();

        return view('kasir.pelanggan', compact('pelanggans', 'cari'));
    }

    public function resetPassword(User $pelanggan)
    {
        abort_unless($pelanggan->role === 'pelanggan', 404);

        $sementara = Str::password(10, true, true, false, false);

        $pelanggan->password = $sementara;               // di-hash otomatis oleh model
        $pelanggan->wajib_ganti_password = true;         // wajib ganti saat login berikutnya
        $pelanggan->forceFill(['api_token' => null]);    // keluarkan sesi yang masih aktif
        $pelanggan->save();

        return back()
            ->with('sukses', 'Password ' . $pelanggan->nama . ' berhasil direset.')
            ->with('password_sementara', $sementara)
            ->with('nama_reset', $pelanggan->nama);
    }
}