<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'kategori_id',
        'harga',
        'aktif',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    // ---- Relasi ----
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function resepItems()
    {
        return $this->hasMany(ResepItem::class);
    }

    public function itemPesanans()
    {
        return $this->hasMany(ItemPesanan::class);
    }

    // ---- Method sesuai class diagram ----
    public function hasRecipe(): bool
    {
        return $this->resepItems()->exists();
    }

    // Habis kalau bahan tidak cukup untuk satu porsi
    public function isHabis(): bool
    {
        foreach ($this->resepItems()->with('bahan')->get() as $item) {
            if ($item->bahan->stok < $item->takaran) {
                return true;
            }
        }
        return false;
    }

    public function hitungHPP(): float
    {
        $total = 0;
        foreach ($this->resepItems()->with('bahan')->get() as $item) {
            $total += $item->takaran * $item->bahan->harga_per_satuan;
        }
        return (float) $total;
    }

    public function hitungMargin(): float
    {
        return (float) $this->harga - $this->hitungHPP();
    }
}