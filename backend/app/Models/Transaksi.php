<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_tiket',
        'kategori',
        'no_plat',
        'total_bayar',
        'uang_bayar',
        'kembalian',
        'status',
        'tanggal_bayar',
        'user_id',
        'petugas',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}