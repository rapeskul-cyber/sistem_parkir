<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

$existing = User::where('email', 'superadmin@parkir.test')->first();
if (!$existing) {
    $existing = User::create([
        'name'       => 'Super Admin Parkir',
        'email'      => 'superadmin@parkir.test',
        'no_telepon' => '081200001111',
        'password'   => Hash::make('password123'),
        'role'       => 'super_admin',
    ]);
    echo "CREATED super_admin: {$existing->id} - {$existing->email}\n";
} else {
    echo "EXISTS super_admin: {$existing->id} - {$existing->email}\n";
}

$petugas = User::where('email', 'petugas@parkir.test')->first();
if (!$petugas) {
    $petugas = User::create([
        'name'       => 'Petugas Gerbang',
        'email'      => 'petugas@parkir.test',
        'no_telepon' => '081200002222',
        'password'   => Hash::make('password123'),
        'role'       => 'petugas',
    ]);
    echo "CREATED petugas: {$petugas->id} - {$petugas->email}\n";
} else {
    echo "EXISTS petugas: {$petugas->id} - {$petugas->email}\n";
}

$member = Member::create([
    'kode_member'     => 'MBR-QA' . strtoupper(bin2hex(random_bytes(3))),
    'token'           => (string) \Illuminate\Support\Str::uuid(),
    'nama_member'     => 'Member QA Test',
    'nama_perusahaan' => 'PT. QA Testing',
    'total_harga'     => 150000,
    'jumlah_bayar'    => 150000,
    'status'          => 'lunas',
    'tanggal_mulai'   => now(),
    'tanggal_expired' => now()->addMonth(),
]);

echo "CREATED member: {$member->id} - {$member->kode_member}\n";
echo "TOTAL users: " . User::count() . " | members: " . Member::count() . "\n";
