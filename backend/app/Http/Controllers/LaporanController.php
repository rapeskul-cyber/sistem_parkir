<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Member;

class LaporanController extends Controller
{
    public function member(Request $request)
    {
        try {
            $data = Transaksi::where('kode_tiket', 'LIKE', 'MBR-%')
                ->orWhere('no_plat', 'MEMBER-REGISTRATION')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    $member = Member::where('kode_member', $item->kode_tiket)->first();
                    $memberFromRegistration = Member::where('kode_member', $item->kode_tiket)->first();

                    $namaMember = $memberFromRegistration?->nama_member ?? $item->nama_member ?? 'Member';
                    $item->nama_member = $namaMember;
                    $item->tipe = 'Member';
                    $item->total_harga = (float) ($item->total_bayar ?? $memberFromRegistration?->total_harga ?? 0);
                    $item->jumlah_bayar = (float) ($item->uang_bayar ?? $memberFromRegistration?->jumlah_bayar ?? 0);
                    $item->kembalian = (float) ($item->kembalian ?? $memberFromRegistration?->kembalian ?? 0);
                    $item->petugas = $item->user?->name ?? $item->petugas ?? 'Petugas';

                    if ($item->no_plat === 'MEMBER-REGISTRATION') {
                        $item->nama_member = $memberFromRegistration?->nama_member ?? 'Pendaftaran Member Baru';
                        $item->total_harga = (float) ($memberFromRegistration?->total_harga ?? $item->total_bayar ?? 0);
                        $item->jumlah_bayar = (float) ($memberFromRegistration?->jumlah_bayar ?? $item->uang_bayar ?? 0);
                    }

                    return $item;
                });

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengambil laporan member: ' . $e->getMessage()
            ], 500);
        }
    }

    public function nonMember(Request $request)
    {
        try {
            // Mengambil semua transaksi keluar selain member (tiket umum)
            $data = Transaksi::where('kode_tiket', 'NOT LIKE', 'MBR-%')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengambil laporan non-member: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rekapGlobal(Request $request)
    {
        try {
            $totalPendapatan = Transaksi::sum('total_bayar');
            $data = Transaksi::orderBy('id', 'desc')->get();

            return response()->json([
                'status' => true,
                'data'   => [
                    'total_pendapatan' => (float) $totalPendapatan,
                    'riwayat'          => $data
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengambil rekap: ' . $e->getMessage()
            ], 500);
        }
    }
}