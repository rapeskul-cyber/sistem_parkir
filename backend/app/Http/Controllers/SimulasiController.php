<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SimulasiController extends Controller
{
    public function status()
    {
        $now = Member::effectiveNow();

        return response()->json([
            'status' => true,
            'data' => [
                'effective_now' => $now->toDateTimeString(),
                'mode' => Cache::has('parkir.simulation.now') ? 'simulasi' : 'realtime',
                'member_total' => Member::count(),
                'member_aktif' => Member::where('status', 'lunas')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('tanggal_expired')
                          ->orWhere('tanggal_expired', '>=', $now);
                    })->count(),
                'member_expired' => Member::whereNotNull('tanggal_expired')
                    ->where('tanggal_expired', '<', $now)->count(),
            ]
        ], 200);
    }

    public function advanceMonth()
    {
        $current = Member::effectiveNow();
        $next = $current->copy()->addMonth();
        Cache::put('parkir.simulation.now', $next->toDateTimeString(), now()->addDays(7));

        foreach (Member::all() as $member) {
            if ($member->tanggal_expired) {
                $member->status = Carbon::parse($member->tanggal_expired)->endOfDay()->lessThanOrEqualTo($next) ? 'belum lunas' : 'lunas';
                $member->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Waktu simulasi dipindahkan maju 1 bulan.',
            'data' => ['effective_now' => $next->toDateTimeString()]
        ], 200);
    }

    public function rewindMonth()
    {
        $current = Member::effectiveNow();
        $prev = $current->copy()->subMonth();
        Cache::put('parkir.simulation.now', $prev->toDateTimeString(), now()->addDays(7));

        foreach (Member::all() as $member) {
            if ($member->tanggal_expired) {
                $member->status = Carbon::parse($member->tanggal_expired)->endOfDay()->lessThanOrEqualTo($prev) ? 'belum lunas' : 'lunas';
                $member->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Waktu simulasi dipindahkan mundur 1 bulan.',
            'data' => ['effective_now' => $prev->toDateTimeString()]
        ], 200);
    }

    public function reset()
    {
        Cache::forget('parkir.simulation.now');

        return response()->json([
            'status' => true,
            'message' => 'Waktu simulasi direset ke waktu nyata.',
            'data' => ['effective_now' => Carbon::now()->toDateTimeString()]
        ], 200);
    }
}
