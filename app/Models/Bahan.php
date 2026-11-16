<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bahan extends Model
{
     protected $fillable = [
        'nama',
        'satuan_dasar',
        'stok',
        'batas_minimum',
        'harga_per_satuan',
        'harga_diperbarui_pada',
    ];

     protected $casts = [
        'stok' => 'decimal:2',
        'batas_minimum' => 'decimal:2',
        'harga_per_satuan' => 'decimal:2',
        'harga_diperbarui_pada' => 'date',
    ];

    public function isMenipis(): bool
    {
        return $this->stok <= $this->batas_minimum;
    }

    public function resepItems()
    {
        return $this->hasMany(ResepItem::class);
    }

    public function stokMasuks()
    {
        return $this->hasMany(StokMasuk::class);
    }
    
}
