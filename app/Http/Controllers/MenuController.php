<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with(['kategori', 'resepItems.bahan'])->orderBy('nama')->get();
        return view('manajer.menu.index', compact('menus'));
    }

    public function create()
    {
        return view('manajer.menu.form', $this->dataForm(new Menu()));
    }

    public function store(Request $request)
    {
        $menu = Menu::create($this->validasi($request));

        // resep dikelola di modul Resep: arahkan ke sana supaya menu baru bisa dipesan
        return redirect('/manajer/resep/' . $menu->id)
            ->with('sukses', 'Menu berhasil disimpan. Tambahkan resepnya agar menu bisa dipesan.');
    }

    public function edit(Menu $menu)
    {
        return view('manajer.menu.form', $this->dataForm($menu));
    }

    public function update(Request $request, Menu $menu)
    {
        // hanya data menu; resep tidak disentuh (diubah lewat modul Resep)
        $menu->update($this->validasi($request));

        return redirect('/manajer/menu')->with('sukses', 'Menu berhasil diperbarui');
    }

    public function toggle(Menu $menu)
    {
        $menu->update(['aktif' => !$menu->aktif]);
        return redirect('/manajer/menu')->with('sukses', 'Status menu diubah');
    }

    private function dataForm(Menu $menu): array
    {
        return [
            'menu'      => $menu,
            'kategoris' => Kategori::orderBy('nama')->get(),
        ];
    }

    private function validasi(Request $request): array
    {
        $validated = $request->validate([
            'nama'        => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategoris,id',
            'harga'       => 'required|numeric|min:0',
        ]);

        return [
            'nama'        => $validated['nama'],
            'kategori_id' => $validated['kategori_id'],
            'harga'       => $validated['harga'],
            'aktif'       => $request->boolean('aktif'),
        ];
    }
}