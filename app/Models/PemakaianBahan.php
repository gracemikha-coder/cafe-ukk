<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemakaianBahan extends Model
{
    protected$fillable = [
        'pesanan_id',
        'bahan_id',
        'jumlah',
    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class);
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}
