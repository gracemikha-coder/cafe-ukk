<?php

namespace App\Http\Controllers;

use App\Models\ResepItem;
use App\Models\Bahan;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResepController extends Controller
{
    public function index()
    {
        $menus = Menu::with('resepItems.bahan')->orderBy('nama')->get();
        return view('manajer.resep.index', compact('menus'));
    }

    public function show(Menu $menu)
    {
        $menu->load('resepItems.bahan');
        $bahans = Bahan::orderBy('nama')->get();
        return view('manajer.resep.show', compact('menu', 'bahans'));
    }

    public function store(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.bahan_id' => 'required|exists:bahans,id',
            'items.*.takaran'  => 'required|numeric|gt:0',
        ], [
            'items.*.takaran.required' => 'Takaran wajib diisi.',
            'items.*.takaran.gt'       => 'Takaran harus lebih dari 0.',
        ]);

        DB::transaction(function () use ($data, $menu) {
            foreach ($data['items'] as $it) {
                ResepItem::updateOrCreate(
                    ['menu_id' => $menu->id, 'bahan_id' => $it['bahan_id']],
                    ['takaran' => $it['takaran']]
                );
            }
        });

        return redirect("/manajer/resep/{$menu->id}")->with('sukses', 'Resep disimpan');
    }

    public function destroy(Menu $menu, ResepItem $item)
    {
        abort_unless((int) $item->menu_id === (int) $menu->id, 404);
        $item->delete();
        return redirect("/manajer/resep/{$menu->id}")->with('sukses', 'Bahan dihapus dari resep');
    }
}