<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\TiketParkir;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    public function index()
    {
        try {
            $data = Transaksi::where('no_plat', '!=', 'MEMBER-REGISTRATION')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengambil transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $totalBayar = (float) ($request->total_bayar ?? 0);
            $uangBayar  = (float) ($request->bayar ?? 0);
            $tipe       = strtolower($request->tipe ?? 'non-member');

            $rawKode = trim((string) ($request->kode ?? ''));
            $rawKode = preg_replace('/[\r\n\t]+/', '', $rawKode);

            // Cari tiket aktif: prioritas tiket_id, lalu fallback ke kode.
            // Tidak pernah melempar 422 hanya karena tiket_id tidak valid.
            $tiket = null;
            if ($request->filled('tiket_id')) {
                $tiket = TiketParkir::where('id', $request->tiket_id)
                    ->where('status', 'masuk')
                    ->first();
            }

            if (!$tiket && $rawKode !== '') {
                $tiket = TiketParkir::where('status', 'masuk')
                    ->where(function ($q) use ($rawKode) {
                        $q->where('kode_tiket', $rawKode)
                          ->orWhere('kode_tiket', 'LIKE', '%' . $rawKode . '%');
                    })
                    ->latest('id')
                    ->first();
            }

            // Non-member wajib punya tiket aktif agar tidak membuat transaksi "hantu".
            if (!$tiket && $tipe !== 'member') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tiket tidak ditemukan atau sudah keluar. Silakan scan ulang tiket.'
                ], 404);
            }

            $kodeFinal = $tiket ? $tiket->kode_tiket : ($rawKode !== '' ? $rawKode : ('TRX-' . time()));

            if ($tiket) {
                $tiket->status = 'keluar';
                $tiket->waktu_keluar = Carbon::now();
                $tiket->save();
            }

            $kembalian = max(0, $uangBayar - $totalBayar);
            $kategori  = strtolower($request->kategori ?: ($tiket?->kategori ?? 'motor'));
            $nopol     = $request->nopol ?: ($tiket?->plat_nomor ?? '-');

            $user = auth()->user();

            $transaksi = Transaksi::create([
                'kode_tiket'    => (string) $kodeFinal,
                'kategori'      => $kategori ?: 'motor',
                'no_plat'       => (string) ($nopol ?: '-'),
                'total_bayar'   => $totalBayar,
                'uang_bayar'    => $uangBayar,
                'kembalian'     => $kembalian,
                'status'        => 'lunas',
                'tanggal_bayar' => Carbon::now(),
                'user_id'       => $user?->id,
                'petugas'       => $user?->name ?? 'Petugas',
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Pembayaran berhasil, palang keluar dibuka!',
                'data'    => $transaksi
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal transaksi keluar: ' . $e->getMessage()
            ], 500);
        }
    }
}