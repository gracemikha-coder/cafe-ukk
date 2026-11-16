<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPesanan extends Model
{
    protected $fillable = [
        'pesanan_id',
        'menu_id',
        'nama_menu_saat_pesan',
        'harga_satuan_saat_pesan',
        'jumlah',
        'subtotal_item',

    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pesanan::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
