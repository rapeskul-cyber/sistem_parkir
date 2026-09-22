<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    public function checkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ], [
                'email.required' => 'Email wajib diisi.',
                'email.email'    => 'Format email tidak valid.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = User::where('email', trim($request->input('email')))->first();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Email tidak terdaftar dalam sistem!'
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Email terdaftar. Silakan masukkan nomor WhatsApp.'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error Server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'      => 'required|email',
                'no_telepon' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $cleanPhone = preg_replace('/\D/', '', $request->input('no_telepon'));

            $format08 = $cleanPhone;
            $format62 = $cleanPhone;

            if (str_starts_with($cleanPhone, '62')) {
                $format08 = '0' . substr($cleanPhone, 2);
            } elseif (str_starts_with($cleanPhone, '0')) {
                $format62 = '62' . substr($cleanPhone, 1);
            }

            $user = User::where('email', trim($request->input('email')))
                ->where(function ($q) use ($format08, $format62) {
                    $q->where('no_telepon', $format08)
                      ->orWhere('no_telepon', $format62);
                })->first();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Nomor WhatsApp tidak cocok dengan akun email tersebut!'
                ], 422);
            }

            $otp = rand(100000, 999999);
            $cacheKey = 'otp_reset_' . $user->id;
            Cache::put($cacheKey, $otp, now()->addMinutes(5));

            $targetWA = $format62;
            $fonnteToken = env('FONNTE_TOKEN');
            $pesanWA = "*PLAZA ANDALAS PARKING SYSTEM*\n\n"
                     . "Halo *" . $user->name . "*,\n"
                     . "Berikut kode verifikasi OTP reset password Anda:\n\n"
                     . "👉 *" . $otp . "* 👈\n\n"
                     . "Berlaku selama *5 menit*. Jangan berikan kode ini kepada siapa pun.";

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $fonnteToken,
                ])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target'      => $targetWA,
                    'message'     => $pesanWA,
                    'countryCode' => '62'
                ]);

            $result = $response->json();

            if (!isset($result['status']) || $result['status'] !== true) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Fonnte error: ' . ($result['reason'] ?? 'Gagal mengirim pesan WA')
                ], 502);
            }

            return response()->json([
                'status'  => true,
                'user_id' => $user->id,
                'message' => 'Kode OTP berhasil dikirim ke WhatsApp!'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memproses OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $userId = (int) $request->input('user_id');
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User tidak ditemukan.'
                ], 404);
            }

            // Generate OTP baru (otomatis menimpa yang lama)
            $otp = rand(100000, 999999);
            $cacheKey = 'otp_reset_' . $user->id;
            Cache::put($cacheKey, $otp, now()->addMinutes(5));

            // Format nomor telepon user ke format 62
            $cleanPhone = preg_replace('/\D/', '', $user->no_telepon);
            $targetWA = $cleanPhone;
            if (str_starts_with($cleanPhone, '0')) {
                $targetWA = '62' . substr($cleanPhone, 1);
            }

            $fonnteToken = env('FONNTE_TOKEN');
            $pesanWA = "*PLAZA ANDALAS PARKING SYSTEM*\n\n"
                     . "Halo *" . $user->name . "*,\n"
                     . "Anda meminta *Kirim Ulang Kode OTP* reset password.\n\n"
                     . "👉 Kode OTP baru Anda: *" . $otp . "* 👈\n\n"
                     . "Berlaku selama *5 menit*. Jangan berikan kode ini kepada siapa pun.";

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $fonnteToken,
                ])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target'      => $targetWA,
                    'message'     => $pesanWA,
                    'countryCode' => '62'
                ]);

            $result = $response->json();

            if (!isset($result['status']) || $result['status'] !== true) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Fonnte error: ' . ($result['reason'] ?? 'Gagal mengirim pesan WA')
                ], 502);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Kode OTP baru berhasil dikirim ke WhatsApp!'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memproses Kirim Ulang OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyAndReset(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id'  => 'required',
                'otp'      => 'required|string',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $userId = (int) $request->input('user_id');
            $cacheKey = 'otp_reset_' . $userId;
            $savedOtp = Cache::get($cacheKey);

            if (!$savedOtp || trim((string)$savedOtp) !== trim((string)$request->input('otp'))) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Kode OTP salah atau sudah kedaluwarsa!'
                ], 422);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User tidak ditemukan.'
                ], 404);
            }

            $user->password = Hash::make($request->input('password'));
            $user->save();

            Cache::forget($cacheKey);

            return response()->json([
                'status'  => true,
                'message' => 'Password berhasil diperbarui! Silakan login kembali.'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengubah password: ' . $e->getMessage()
            ], 500);
        }
    }
}