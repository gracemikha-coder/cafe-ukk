<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;

class MenuApiController extends Controller
{
    /** Daftar menu untuk pelanggan: tanpa resep, HPP, dan margin. */
    public function index(): JsonResponse
    {
        $menus = Menu::with(['kategori', 'resepItems.bahan'])->orderBy('nama')->get()->map(function ($m) {
            if (!$m->aktif) {
                $status = 'Nonaktif';
            } elseif (!$m->hasRecipe()) {
                $status = 'Belum tersedia';
            } elseif ($m->isHabis()) {
                $status = 'Habis';
            } else {
                $status = 'Tersedia';
            }

            return [
                'id'           => $m->id,
                'nama'         => $m->nama,
                'kategori'     => $m->kategori->nama ?? null,
                'harga'        => (float) $m->harga,
                'status'       => $status,
                'bisa_dipesan' => $status === 'Tersedia',
            ];
        })->values();

        return response()->json(['data' => $menus]);
    }
}