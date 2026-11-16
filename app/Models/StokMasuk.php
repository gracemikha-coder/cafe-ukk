<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokMasuk extends Model
{
    protected $fillable = [
        'bahan_id',
        'user_id',
        'tanggal',
        'jumlah',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
