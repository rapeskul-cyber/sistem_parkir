<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TiketParkir;
use App\Models\Member;
use App\Models\Transaksi;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function scan(Request $request)
    {
        try {
            $request->validate([
                'kode' => 'required'
            ]);

            $kode = $request->kode;

            $member = Member::where('kode_member', $kode)->orWhere('token', $kode)->first();
            if ($member) {
                if ($member->tanggal_expired && Member::effectiveNow()->greaterThan(Carbon::parse($member->tanggal_expired))) {
                    return response()->json([
                        'status' => true,
                        'type' => 'member',
                        'message' => 'Member sudah expired! Harap perpanjang.'
                    ], 200);
                }
                if ($member->status != 'lunas') {
                    return response()->json([
                        'status' => true,
                        'type' => 'member',
                        'message' => 'Member belum melunasi pembayaran bulanan!'
                    ], 200);
                }

                return response()->json([
                    'status' => true,
                    'type' => 'member',
                    'message' => 'Akses Member Valid! Pintu Terbuka.',
                    'data' => $member
                ], 200);
            }

            $tiket = TiketParkir::where('kode_tiket', $kode)->first();
            if (!$tiket) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Tiket atau Kode Member tidak ditemukan!'
                ], 404);
            }

            if ($tiket->status === 'keluar') {
                return response()->json([
                    'status' => false, 
                    'message' => 'Tiket sudah pernah digunakan keluar!'
                ], 400);
            }

            $waktuMasuk = Carbon::parse($tiket->waktu_masuk);
            $waktuKeluar = Carbon::now();
            
            $durasiJam = ceil($waktuMasuk->diffInMinutes($waktuKeluar) / 60);
            if ($durasiJam < 1) $durasiJam = 1;

            $tarifPerJam = $request->jenis_kendaraan === 'mobil' ? 5000 : 2000;
            $totalTarif = $durasiJam * $tarifPerJam;

            return response()->json([
                'status' => true,
                'type' => 'non-member',
                'data' => [
                    'kode_tiket' => $tiket->kode_tiket,
                    'waktu_masuk' => $waktuMasuk,
                    'waktu_keluar' => $waktuKeluar,
                    'durasi_jam' => $durasiJam,
                    'total_tarif' => $totalTarif
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bayar(Request $request)
    {
        try {
            $request->validate([
                'kode_tiket' => 'required',
                'uang_bayar' => 'required|numeric',
                'total_tarif' => 'required|numeric'
            ]);

            $tiket = TiketParkir::where('kode_tiket', $request->kode_tiket)->first();
            if ($tiket && $tiket->status === 'keluar') {
                return response()->json(['status' => false, 'message' => 'Tiket sudah dibayar sebelumnya!'], 400);
            }

            $uangBayar = (float) $request->uang_bayar;
            $totalTarif = (float) $request->total_tarif;

            if ($uangBayar < $totalTarif) {
                return response()->json(['status' => false, 'message' => 'Uang bayar kurang!'], 400);
            }

            $kembalian = $uangBayar - $totalTarif;

            if ($tiket) {
                $tiket->update([
                    'status' => 'keluar',
                    'waktu_keluar' => Carbon::now()
                ]);
            }

            $user = auth()->user();

            $transaksi = Transaksi::create([
                'kode_tiket'    => (string) $request->kode_tiket,
                'kategori'      => strtolower($request->kategori ?: ($tiket->kategori ?? 'motor')),
                'no_plat'       => (string) ($request->no_plat ?: ($tiket->plat_nomor ?? '-')),
                'total_bayar'   => $totalTarif,
                'uang_bayar'    => $uangBayar,
                'kembalian'     => $kembalian,
                'status'        => 'lunas',
                'tanggal_bayar' => Carbon::now(),
                'user_id'       => $user?->id,
                'petugas'       => $user?->name ?? 'Petugas',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Pembayaran berhasil! Pintu terbuka.',
                'data' => $transaksi
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}