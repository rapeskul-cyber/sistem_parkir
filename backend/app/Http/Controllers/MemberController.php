<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\TiketParkir;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberController extends Controller
{
    private const TARIF_MAKSIMAL = 150000;

    public function index()
    {
        try {
            $members = Member::latest()->get();

            return response()->json([
                "status" => true,
                "data"   => $members
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"  => false,
                "message" => "Gagal mengambil data member: " . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama_member'     => 'required|string|max:255',
                'nama_perusahaan' => 'required|string|max:255',
            ]);

            $harga_member = self::TARIF_MAKSIMAL;
            $uang_bayar = (float) ($request->uang_bayar ?? $request->jumlah_bayar ?? 0);
            $uang_bayar = max(0, $uang_bayar);

            $kembalian = max(0, $uang_bayar - $harga_member);
            $status = $uang_bayar >= $harga_member ? "lunas" : "belum lunas";

            $tanggalMulai  = Carbon::now();
            $berlakuSampai = $status === 'lunas' ? $tanggalMulai->copy()->addMonth() : null;

            $kodeMember = $this->generateKodeMember();

            $member = Member::create([
                'kode_member'     => $kodeMember,
                'token'           => (string) Str::uuid(),
                'nama_member'     => $request->nama_member,
                'nama_perusahaan' => $request->nama_perusahaan,
                'total_harga'     => $harga_member,
                'jumlah_bayar'    => $uang_bayar,
                'kembalian'       => $kembalian,
                'status'          => $status,
                'tanggal_bayar'   => $status === 'lunas' ? $tanggalMulai : null,
                'tanggal_mulai'   => $status === 'lunas' ? $tanggalMulai : null,
                'tanggal_expired' => $berlakuSampai
            ]);

            $user = auth()->user();

            Transaksi::create([
                'kode_tiket'    => $kodeMember,
                'kategori'      => 'member',
                'no_plat'       => 'MEMBER-REGISTRATION',
                'total_bayar'   => (float) $harga_member,
                'uang_bayar'    => (float) $uang_bayar,
                'kembalian'     => (float) $kembalian,
                'status'        => $status,
                'tanggal_bayar' => Carbon::now(),
                'user_id'       => $user?->id,
                'petugas'       => $user?->name ?? 'Petugas',
            ]);

            return response()->json([
                "status"  => true,
                "message" => "Member berhasil dibuat",
                "data"    => $member
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"  => false,
                "message" => "Gagal menyimpan member: " . $e->getMessage()
            ], 500);
        }
    }

    private function generateKodeMember()
    {
        do {
            $kode = 'MBR-' . strtoupper(Str::random(6));
        } while (Member::where('kode_member', $kode)->exists());

        return $kode;
    }

    public function show($id)
    {
        try {
            $member = Member::find($id);

            if (!$member) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Member tidak ditemukan'
                ], 404);
            }

            $svg = QrCode::format('svg')->size(200)->generate($member->kode_member);
            $qr = base64_encode($svg);

            return response()->json([
                'status' => true,
                'data'   => [
                    'id'              => $member->id,
                    'kode_member'     => $member->kode_member,
                    'nama_member'     => $member->nama_member,
                    'nama_perusahaan' => $member->nama_perusahaan,
                    'total_harga'     => $member->total_harga ?? self::TARIF_MAKSIMAL,
                    'jumlah_bayar'    => $member->jumlah_bayar ?? 0,
                    'status'          => $member->status,
                    'tanggal_mulai'   => $member->tanggal_mulai,
                    'tanggal_bayar'   => $member->tanggal_bayar,
                    'tanggal_expired' => $member->tanggal_expired,
                    'qr'              => "data:image/svg+xml;base64," . $qr
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bayarMember(Request $request, $id)
    {
        try {
            $member = Member::find($id);

            if (!$member) {
                return response()->json([
                    'status' => false,
                    'message' => 'Member tidak ditemukan'
                ], 404);
            }

            $member->status = 'lunas';
            $member->jumlah_bayar = $member->total_harga ?? self::TARIF_MAKSIMAL;
            $member->tanggal_mulai = Carbon::now();
            $member->tanggal_expired = Carbon::now()->addMonth();
            $member->save();

            return response()->json([
                'status' => true,
                'message' => 'Pembayaran member berhasil dikonfirmasi dan masa aktif diperpanjang!',
                'data' => $member
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memproses pembayaran member: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $member = Member::findOrFail($id);

            $request->validate([
                'nama_member'     => 'required|string|max:255',
                'nama_perusahaan' => 'required|string|max:255',
            ]);

            $member->nama_member = trim($request->nama_member);
            $member->nama_perusahaan = trim($request->nama_perusahaan);

            if ($member->status !== 'lunas') {
                $member->total_harga = self::TARIF_MAKSIMAL;

                if ($request->has('jumlah_bayar')) {
                    $bayar = (float) $request->jumlah_bayar;
                    $member->jumlah_bayar = min(self::TARIF_MAKSIMAL, max(0, $bayar));
                }

                if ($member->jumlah_bayar >= self::TARIF_MAKSIMAL) {
                    $member->status = 'lunas';
                    $member->tanggal_mulai = Carbon::now();
                    $member->tanggal_expired = Carbon::now()->addMonth();
                } else {
                    $member->status = 'belum lunas';
                }
            }

            $member->save();

            return response()->json([
                'status'  => true,
                'message' => 'Data member berhasil diperbarui',
                'data'    => $member
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePembayaran(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    public function destroy($id)
    {
        try {
            $member = Member::find($id);

            if (!$member) {
                return response()->json([
                    "status"  => false,
                    "message" => "Member tidak ditemukan"
                ], 404);
            }

            $member->delete();

            return response()->json([
                "status"  => true,
                "message" => "Member berhasil dihapus"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status"  => false,
                "message" => "Gagal menghapus member: " . $e->getMessage()
            ], 500);
        }
    }

    public function check(Request $request)
    {
        try {
            $request->validate([
                'kode_member' => 'nullable|string',
                'token'       => 'nullable|string'
            ]);

            $query = Member::query();
            if ($request->filled('kode_member')) {
                $query->where('kode_member', $request->kode_member);
            } elseif ($request->filled('token')) {
                $query->where('token', $request->token);
            } else {
                return response()->json([
                    "status"  => false,
                    "message" => "Kode member atau token wajib diisi"
                ], 422);
            }

            $member = $query->first();

            if (!$member) {
                return response()->json([
                    "status"  => false,
                    "message" => "Kartu Member Tidak Terdaftar!"
                ], 404);
            }

            if (!empty($member->tanggal_expired) && Carbon::parse($member->tanggal_expired)->endOfDay()->isPast()) {
                return response()->json([
                    "status"  => false,
                    "message" => "Member sudah expired"
                ], 400);
            }

            if ($member->status !== "lunas") {
                return response()->json([
                    "status"  => false,
                    "message" => "Member belum melakukan pembayaran"
                ], 400);
            }

            return response()->json([
                "status"  => true,
                "message" => "Member valid",
                "data"    => $member
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status"  => false,
                "message" => "Gagal cek member: " . $e->getMessage()
            ], 500);
        }
    }
}