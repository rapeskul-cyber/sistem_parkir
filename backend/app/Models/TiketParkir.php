<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiketParkir extends Model
{
    use HasFactory;

    protected $table = 'tiket_parkir';

    protected $fillable = [
        'kode_tiket',
        'qr_code',
        'kategori',
        'plat_nomor',
        'status',
        'waktu_masuk',
        'waktu_keluar',
    ];
}
