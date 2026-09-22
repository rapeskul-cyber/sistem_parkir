<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Menampilkan daftar semua petugas beserta no telepon
    public function index()
    {
        try {
            $users = User::latest()->get(); 
            return response()->json([
                'status' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'ERROR DI DATABASE/KODE: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // Menyimpan akun petugas baru dengan validasi no telepon unik
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'       => 'required|string|max:255',
                'email'      => 'required|string|email|max:255|unique:users,email',
                'no_telepon' => 'required|string|min:10|max:15|unique:users,no_telepon',
                'password'   => 'required|string|min:6',
                'role'       => 'nullable|in:petugas,admin,super_admin,superadmin,super admin',
            ], [
                'name.required'       => 'Nama lengkap petugas wajib diisi.',
                'email.required'      => 'Email petugas wajib diisi.',
                'email.email'         => 'Format email tidak valid.',
                'email.unique'        => 'Email sudah terdaftar untuk petugas lain.',
                'no_telepon.required' => 'Nomor telepon/WhatsApp wajib diisi.',
                'no_telepon.min'      => 'Nomor telepon minimal 10 digit.',
                'no_telepon.max'      => 'Nomor telepon maksimal 15 digit.',
                'no_telepon.unique'   => 'Nomor telepon sudah digunakan oleh petugas lain.',
                'password.required'   => 'Password wajib diisi.',
                'password.min'        => 'Password minimal 6 karakter.',
                'role.in'             => 'Role hanya boleh petugas, admin, super_admin, atau super admin.'
            ]);

            $cleanPhone = preg_replace('/\D/', '', $request->no_telepon ?? $request->no_hp);
            $role = $this->normalizeRole($request->role ?? 'petugas');

            $user = User::create([
                'name'       => trim($request->name),
                'email'      => trim($request->email),
                'no_telepon' => $cleanPhone,
                'password'   => Hash::make($request->password),
                'role'       => $role,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Akun petugas berhasil ditambahkan!',
                'data'    => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menyimpan akun: ' . $e->getMessage()
            ], 500);
        }
    }

    // Mengupdate data profil petugas (nama, email, no_telepon)
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Petugas tidak ditemukan'
                ], 404);
            }

            $request->validate([
                'name'       => 'required|string|max:255',
                'email'      => 'required|string|email|max:255|unique:users,email,' . $id,
                'no_telepon' => 'required|string|min:10|max:15|unique:users,no_telepon,' . $id,
            ], [
                'name.required'       => 'Nama lengkap wajib diisi.',
                'email.required'      => 'Email wajib diisi.',
                'email.email'         => 'Format email tidak valid.',
                'email.unique'        => 'Email sudah digunakan oleh akun lain.',
                'no_telepon.required' => 'Nomor telepon wajib diisi.',
                'no_telepon.min'      => 'Nomor telepon minimal 10 digit.',
                'no_telepon.max'      => 'Nomor telepon maksimal 15 digit.',
                'no_telepon.unique'   => 'Nomor telepon sudah digunakan oleh petugas lain.',
            ]);

            $cleanPhone = preg_replace('/\D/', '', $request->no_telepon ?? $request->no_hp);

            $user->update([
                'name'       => trim($request->name),
                'email'      => trim($request->email),
                'no_telepon' => $cleanPhone,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Data petugas berhasil diperbarui!',
                'data'    => $user
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui data petugas: ' . $e->getMessage()
            ], 500);
        }
    }

    // Reset password akun petugas oleh Admin
    public function updatePassword(Request $request, $id)
    {
        try {
            $request->validate([
                'password' => 'required|min:6'
            ], [
                'password.required' => 'Password baru wajib diisi.',
                'password.min'      => 'Password baru minimal 6 karakter.'
            ]);

            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status'  => false, 
                    'message' => 'Petugas tidak ditemukan'
                ], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status'  => true, 
                'message' => 'Password petugas berhasil direset!'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => 'Gagal mereset password: ' . $e->getMessage()
            ], 500);
        }
    }

    // Menghapus akun petugas
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akun petugas tidak ditemukan'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Akun petugas berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghapus petugas: ' . $e->getMessage()
            ], 500);
        }
    }

    private function normalizeRole(?string $role): string
    {
        $normalized = strtolower(trim((string) ($role ?? 'petugas')));
        $normalized = str_replace([' ', '-', '/'], '_', $normalized);

        return match ($normalized) {
            'superadmin', 'super_admin' => 'super_admin',
            default => $normalized,
        };
    }
}