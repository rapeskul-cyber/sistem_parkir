<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        'kode_member',
        'token',
        'nama_member',
        'nama_perusahaan',
        'total_harga',
        'jumlah_bayar',
        'kembalian',
        'status',
        'tanggal_mulai',
        'tanggal_expired',
        'tanggal_bayar',
        'tanggal_reset',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'datetime',
        'tanggal_expired' => 'datetime',
        'tanggal_bayar'   => 'datetime',
        'tanggal_reset'   => 'date',
    ];

    public static function effectiveNow(): Carbon
    {
        $simulation = Cache::get('parkir.simulation.now');

        return $simulation ? Carbon::parse($simulation) : Carbon::now();
    }

    /**
     * Hitung status secara dinamis berdasarkan tanggal expired saat ini
     */
    public function getStatusAttribute($value)
    {
        if ($this->tanggal_expired) {
            $effectiveNow = self::effectiveNow();
            $isPast = Carbon::parse($this->tanggal_expired)->endOfDay()->lessThanOrEqualTo($effectiveNow);
            return $isPast ? 'belum lunas' : 'lunas';
        }
        return $value ?? 'belum lunas';
    }
}