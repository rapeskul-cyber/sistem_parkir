<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\TiketParkir;
use Carbon\Carbon;

class MemberGateController extends Controller
{
    public function masukMember(Request $request)
    {
        try {
            $request->validate([
                'kode' => 'required'
            ]);

            $rawKode = trim(preg_replace('/[\r\n\t]+/', '', $request->kode));
            $cleanKode = $rawKode;

            if (str_starts_with($rawKode, 'MBR-')) {
                $parts = explode('-', $rawKode);
                if (count($parts) >= 2) {
                    $cleanKode = $parts[0] . '-' . $parts[1];
                }
            }

            $member = Member::where('kode_member', $cleanKode)->first();
            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Kartu Member Tidak Terdaftar!'
                ], 404);
            }

            $isExpired = false;
            if ($member->tanggal_expired) {
                $isExpired = Carbon::parse($member->tanggal_expired)->endOfDay()->lessThanOrEqualTo(Member::effectiveNow());
            }

            if ($member->status !== 'lunas' || $isExpired) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akses ditolak: Status belum lunas atau masa aktif telah berakhir!'
                ], 400);
            }

            $sedangParkir = TiketParkir::where('kode_tiket', 'LIKE', $member->kode_member . '%')
                ->where('status', 'masuk')
                ->first();

            if ($sedangParkir) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member sudah tercatat berada di dalam area parkir!'
                ], 422);
            }

            $kategori = strtolower($request->kategori ?: 'motor');
            $kodeSesi = $member->kode_member . '-' . date('ymdHis');

            // Simpan dengan aman mencakup kedua variasi kolom plat nomor
            $tiket = TiketParkir::create([
                'kode_tiket'   => $kodeSesi,
                'kategori'     => $kategori,
                'plat_nomor'   => $member->nama_member,
                'status'       => 'masuk',
                'waktu_masuk'  => Carbon::now(),
                'waktu_keluar' => null,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Akses Diterima! Selamat Datang, ' . $member->nama_member,
                'data'    => $tiket
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal verifikasi masuk: ' . $e->getMessage()
            ], 500);
        }
    }

    public function scanGate(Request $request)
    {
        try {
            $rawKode = trim(preg_replace('/[\r\n\t]+/', '', (string) $request->kode));

            if (!$rawKode) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Kode scan tidak boleh kosong!'
                ], 422);
            }

            // 1. Cari berdasarkan kode_tiket persis
            $tiket = TiketParkir::where('kode_tiket', $rawKode)
                ->where('status', 'masuk')
                ->latest('id')
                ->first();

            // Jika tidak ketemu persis, coba cari yang mengandung awalan kode tersebut (untuk member)
            if (!$tiket) {
                $tiket = TiketParkir::where('kode_tiket', 'LIKE', '%' . $rawKode . '%')
                    ->where('status', 'masuk')
                    ->latest('id')
                    ->first();
            }

            if ($tiket) {
                $waktuMasuk = $tiket->waktu_masuk ?: $tiket->created_at;
                $masuk = $waktuMasuk ? Carbon::parse($waktuMasuk) : Carbon::now();
                
                // Hitung durasi minimal 1 jam untuk mencegah division by zero atau nilai negatif
                $diffInHours = $masuk->diffInHours(Carbon::now());
                $durasiJam = max(1, (int) $diffInHours);
                
                $kategori = strtolower($tiket->kategori ?: 'motor');
                $tarifPerJam = ($kategori === 'mobil') ? 5000 : 2000;
                $totalTarif = $tarifPerJam * $durasiJam;

                $isMember = str_starts_with($tiket->kode_tiket, 'MBR-');
                $platNomor = $tiket->plat_nomor ?? '-';

                if ($isMember) {
                    $parts = explode('-', $tiket->kode_tiket);
                    $kodeMbr = isset($parts[0], $parts[1]) ? $parts[0] . '-' . $parts[1] : $rawKode;
                    $member = Member::where('kode_member', $kodeMbr)->first();

                    return response()->json([
                        'status' => true,
                        'type'   => 'member',
                        'data'   => [
                            'id'            => $tiket->id,
                            'kode_member'   => $kodeMbr,
                            'kode_tiket'    => $tiket->kode_tiket,
                            'nama_member'   => $member ? $member->nama_member : $platNomor,
                            'plat_nomor'    => $platNomor,
                            'kategori'      => $kategori,
                            'waktu_masuk'   => $masuk->toDateTimeString(),
                            'durasi_jam'    => $durasiJam,
                            'tarif_per_jam' => $tarifPerJam,
                            'total_tarif'   => $totalTarif,
                            'diskon_member' => $totalTarif
                        ]
                    ], 200);
                }

                return response()->json([
                    'status' => true,
                    'type'   => 'tiket',
                    'data'   => [
                        'id'            => $tiket->id,
                        'kode_tiket'    => $tiket->kode_tiket,
                        'plat_nomor'    => $platNomor,
                        'kategori'      => $kategori,
                        'waktu_masuk'   => $masuk->toDateTimeString(),
                        'durasi_jam'    => $durasiJam,
                        'tarif_per_jam' => $tarifPerJam,
                        'total_tarif'   => $totalTarif
                    ]
                ], 200);
            }

            // 2. Jika diinput kode member langsung (misal: MBR-XXXXXX)
            $cleanKode = $rawKode;
            if (str_starts_with($rawKode, 'MBR-')) {
                $parts = explode('-', $rawKode);
                if (count($parts) >= 2) {
                    $cleanKode = $parts[0] . '-' . $parts[1];
                }
            }

            $member = Member::where('kode_member', $cleanKode)->first();
            if ($member) {
                $tiketMember = TiketParkir::where('kode_tiket', 'LIKE', '%' . $member->kode_member . '%')
                    ->where('status', 'masuk')
                    ->latest('id')
                    ->first();

                if (!$tiketMember) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Member ini belum tercatat melakukan scan masuk di gerbang!'
                    ], 404);
                }

                $waktuMasuk = $tiketMember->waktu_masuk ?: $tiketMember->created_at;
                $masuk = $waktuMasuk ? Carbon::parse($waktuMasuk) : Carbon::now();
                $durasiJam = max(1, (int) $masuk->diffInHours(Carbon::now()));
                
                $kategori = strtolower($tiketMember->kategori ?: 'motor');
                $tarifPerJam = ($kategori === 'mobil') ? 5000 : 2000;
                $totalTarif = $tarifPerJam * $durasiJam;

                return response()->json([
                    'status' => true,
                    'type'   => 'member',
                    'data'   => [
                        'id'            => $tiketMember->id,
                        'kode_member'   => $member->kode_member,
                        'kode_tiket'    => $tiketMember->kode_tiket,
                        'nama_member'   => $member->nama_member,
                        'plat_nomor'    => $tiketMember->plat_nomor ?? '-',
                        'kategori'      => $kategori,
                        'waktu_masuk'   => $masuk->toDateTimeString(),
                        'durasi_jam'    => $durasiJam,
                        'tarif_per_jam' => $tarifPerJam,
                        'total_tarif'   => $totalTarif,
                        'diskon_member' => $totalTarif
                    ]
                ], 200);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Data tiket atau kartu member tidak ditemukan di database!'
            ], 404);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error Server: ' . $e->getMessage()
            ], 500);
        }
    }
}