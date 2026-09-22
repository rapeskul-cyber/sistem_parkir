<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\TiketParkir;
use App\Models\Member;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $now = Carbon::now();
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            // Hitung member aktif secara dinamis (lunas DAN belum expired)
            $memberAktif = Member::where('status', 'lunas')
                ->where(function ($q) use ($now) {
                    $q->whereNull('tanggal_expired')
                      ->orWhere('tanggal_expired', '>=', $now);
                })->count();

            $kendaraanHariIni = TiketParkir::whereDate('created_at', $today)->count();

            // Hitung pendapatan hari ini sinkron (cek tanggal_bayar maupun created_at)
            $pendapatanHariIni = Transaksi::where(function ($q) use ($today) {
                $q->whereDate('tanggal_bayar', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('tanggal_bayar')
                          ->whereDate('created_at', $today);
                  });
            })->sum('total_bayar');

            // Total akumulasi penerimaan kasir keseluruhan
            $totalPenerimaan = Transaksi::sum('total_bayar');

            // Sedang Parkir dihitung murni dari tiket yang masih berstatus 'masuk'
            $sedangParkir = TiketParkir::where('status', 'masuk')->count();

            $transaksiTerbaru = Transaksi::orderBy('id', 'desc')->take(5)->get();

            // ==========================================
            // 1. DATA REALTIME HARI INI (Per 2 Jam: 06:00 - 22:00)
            // ==========================================
            $hours = ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
            $todayHourly = [];
            $yesterdayHourly = [];

            foreach ($hours as $idx => $h) {
                $startHour = (int) substr($h, 0, 2);
                $endHour = $startHour + 1;

                $todayHourly[] = TiketParkir::whereDate('created_at', $today)
                    ->whereTime('created_at', '>=', sprintf('%02d:00:00', $startHour))
                    ->whereTime('created_at', '<=', sprintf('%02d:59:59', $endHour))
                    ->count();

                $yesterdayHourly[] = TiketParkir::whereDate('created_at', $yesterday)
                    ->whereTime('created_at', '>=', sprintf('%02d:00:00', $startHour))
                    ->whereTime('created_at', '<=', sprintf('%02d:59:59', $endHour))
                    ->count();
            }

            // ==========================================
            // 2. DATA MINGGU INI (Senin - Minggu)
            // ==========================================
            $daysOfWeek = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
            $thisWeekDaily = [];
            $lastWeekDaily = [];

            $startThisWeek = $now->copy()->startOfWeek();
            $startLastWeek = $now->copy()->subWeek()->startOfWeek();

            for ($i = 0; $i < 7; $i++) {
                $dayThisWeek = $startThisWeek->copy()->addDays($i);
                $dayLastWeek = $startLastWeek->copy()->addDays($i);

                $thisWeekDaily[] = TiketParkir::whereDate('created_at', $dayThisWeek->toDateString())->count();
                $lastWeekDaily[] = TiketParkir::whereDate('created_at', $dayLastWeek->toDateString())->count();
            }

            // ==========================================
            // 3. DATA BULAN INI (Minggu 1 s/d Minggu 4)
            // ==========================================
            $weeksOfMonth = ['Mgg 1', 'Mgg 2', 'Mgg 3', 'Mgg 4'];
            $thisMonthWeekly = [];
            $lastMonthWeekly = [];

            $startThisMonth = $now->copy()->startOfMonth();
            $startLastMonth = $now->copy()->subMonth()->startOfMonth();

            for ($w = 0; $w < 4; $w++) {
                $fromThis = $startThisMonth->copy()->addDays($w * 7);
                $toThis = $w === 3 ? $now->copy()->endOfMonth() : $fromThis->copy()->addDays(6)->endOfDay();

                $fromLast = $startLastMonth->copy()->addDays($w * 7);
                $toLast = $w === 3 ? $startLastMonth->copy()->endOfMonth() : $fromLast->copy()->addDays(6)->endOfDay();

                $thisMonthWeekly[] = TiketParkir::whereBetween('created_at', [$fromThis, $toThis])->count();
                $lastMonthWeekly[] = TiketParkir::whereBetween('created_at', [$fromLast, $toLast])->count();
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'member_aktif'        => $memberAktif,
                    'kendaraan_hari_ini'  => $kendaraanHariIni,
                    'pendapatan_hari_ini' => $pendapatanHariIni,
                    'total_penerimaan'    => $totalPenerimaan,
                    'sedang_parkir'       => $sedangParkir,
                    'transaksi_terbaru'   => $transaksiTerbaru,
                    'chart' => [
                        'today' => [
                            'labels'    => $hours,
                            'current'   => $todayHourly,
                            'previous'  => $yesterdayHourly,
                            'currLabel' => 'Hari Ini',
                            'prevLabel' => 'Kemarin'
                        ],
                        'week' => [
                            'labels'    => $daysOfWeek,
                            'current'   => $thisWeekDaily,
                            'previous'  => $lastWeekDaily,
                            'currLabel' => 'Minggu Ini',
                            'prevLabel' => 'Minggu Lalu'
                        ],
                        'month' => [
                            'labels'    => $weeksOfMonth,
                            'current'   => $thisMonthWeekly,
                            'previous'  => $lastMonthWeekly,
                            'currLabel' => 'Bulan Ini',
                            'prevLabel' => 'Bulan Lalu'
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error Dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}