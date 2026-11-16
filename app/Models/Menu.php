<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'nama',
        'kategori',
        'harga',
        'aktif',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    // Habis kalau bahan tidak cukup untuk satu porsi
    public function isHabis(): bool
    {
        foreach ($this->resepItems()->with('bahan')->get()as $item){
            if($item->bahan ->stok < $item->takaran){
                return true;
            }
        }
        return false;
    }

    public function hitungHPP(): float
    {
        $total = 0;
        foreach ($this->resepItem()->with('bahan')->get() as $item){
            $total += $item->takaran * $item->bahan->harga_per_satuan;
        }
        return $total;
    }

    public function hitungMargin(): float
    {
        return (float) $this->harga - $this->hitungHpp();
    }
}
