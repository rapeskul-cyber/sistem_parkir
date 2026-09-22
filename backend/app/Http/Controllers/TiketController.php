<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TiketParkir;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TiketController extends Controller
{
    public function create(Request $request)
    {
        try {
            $kategori = $request->kategori ?: 'motor';
            $kodeTiket = 'TKT-' . strtoupper(bin2hex(random_bytes(4)));

            $qrSvg = QrCode::format('svg')->size(200)->generate($kodeTiket);
            $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

            $tiket = TiketParkir::create([
                'kode_tiket'  => $kodeTiket,
                'qr_code'     => $qrBase64,
                'kategori'    => $kategori,
                'plat_nomor'  => $request->plat_nomor ?? '-',
                'status'      => 'masuk',
                'waktu_masuk' => Carbon::now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Tiket berhasil dibuat',
                'data'    => $tiket
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal membuat tiket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showByKode($kode)
    {
        try {
            $tiket = TiketParkir::where('kode_tiket', $kode)->first();
            if (!$tiket) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tiket tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data'   => $tiket
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function qrcode($kode)
    {
        try {
            return response(QrCode::format('svg')->size(200)->generate($kode))
                ->header('Content-Type', 'image/svg+xml');
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error QR: ' . $e->getMessage()
            ], 500);
        }
    }

    public function kendaraanAktif()
    {
        try {
            $data = TiketParkir::where('status', 'masuk')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    $kat = strtolower($item->kategori ?: 'motor');
                    $tipe = str_starts_with($item->kode_tiket, 'MBR-') ? 'Member' : 'Non-Member';

                    return [
                        'id'          => $item->id,
                        'kode_tiket'  => $item->kode_tiket,
                        'tipe'        => $tipe,
                        'kategori'    => $kat,
                        'no_plat'     => $item->plat_nomor ?: '-',
                        'plat_nomor'  => $item->plat_nomor ?: '-',
                        'waktu_masuk' => $item->waktu_masuk ?: $item->created_at,
                        'status'      => $item->status,
                    ];
                });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}