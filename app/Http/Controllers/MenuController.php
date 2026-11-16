<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        [$data, $items] = $this->validasi($request);

        DB::transaction(function () use ($data, $items) {
            $menu = Menu::create($data);
            $this->simpanResep($menu, $items);
        });

        return redirect('/manajer/menu')->with('sukses', 'Menu dan resep berhasil disimpan');
    }

    public function edit(Menu $menu)
    {
        return view('manajer.menu.form', $this->dataForm($menu));
    }

    public function update(Request $request, Menu $menu)
    {
        [$data, $items] = $this->validasi($request);

        DB::transaction(function () use ($menu, $data, $items) {
            $menu->update($data);
            $menu->resepItems()->delete();
            $this->simpanResep($menu, $items);
        });

        return redirect('/manajer/menu')->with('sukses', 'Menu dan resep berhasil diperbarui');
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
            'bahans'    => Bahan::orderBy('nama')->get(),
        ];
    }

    private function simpanResep(Menu $menu, array $items): void
    {
        foreach ($items as $it) {
            $menu->resepItems()->create([
                'bahan_id' => $it['bahan_id'],
                'takaran'  => $it['takaran'],
            ]);
        }
    }

    private function validasi(Request $request): array
    {
        $validated = $request->validate([
            'nama'             => 'required|string|max:255',
            'kategori_id'      => 'required|exists:kategoris,id',
            'harga'            => 'required|numeric|min:0',
            'items'            => 'nullable|array',
            'items.*.bahan_id' => 'required|distinct|exists:bahans,id',
            'items.*.takaran'  => 'required|numeric|gt:0',
        ], [
            'items.*.bahan_id.distinct' => 'Satu bahan hanya boleh sekali dalam satu resep.',
            'items.*.takaran.required'  => 'Takaran wajib diisi.',
            'items.*.takaran.gt'        => 'Takaran harus lebih dari 0.',
        ]);

        $data = [
            'nama'        => $validated['nama'],
            'kategori_id' => $validated['kategori_id'],
            'harga'       => $validated['harga'],
            'aktif'       => $request->boolean('aktif'),
        ];

        return [$data, $validated['items'] ?? []];
    }
}