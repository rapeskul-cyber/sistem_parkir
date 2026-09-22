<?php
// Script tes sementara: simulasi time travel untuk member
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Member;

$mode = $argv[1] ?? 'expire';
$m = Member::first();

if (!$m) {
    echo "Tidak ada member\n";
    exit;
}

if ($mode === 'expire') {
    $m->tanggal_expired = now()->subDays(2);
    $m->save();
    echo "EXPIRED: {$m->kode_member} -> {$m->tanggal_expired}\n";
} elseif ($mode === 'reset') {
    $m->tanggal_expired = now()->addMonth();
    $m->status = 'lunas';
    $m->save();
    echo "RESET: {$m->kode_member} -> {$m->tanggal_expired} (status: {$m->status})\n";
} elseif ($mode === 'belum-lunas') {
    $m->status = 'belum lunas';
    $m->save();
    echo "SET BELUM LUNAS: {$m->kode_member} (status: {$m->status})\n";
} elseif ($mode === 'show') {
    echo "MEMBER: {$m->kode_member} | status: {$m->status} | expired: {$m->tanggal_expired}\n";
}