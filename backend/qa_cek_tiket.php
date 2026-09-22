<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TiketParkir;
use App\Models\Member;

echo "=== TIKET PARKIR ===\n";
foreach (TiketParkir::orderBy('id')->get() as $t) {
    echo $t->id.' | '.$t->kode_tiket.' | '.$t->status.' | '.$t->plat_nomor.' | '.$t->jenis_kendaraan.' | masuk='.$t->waktu_masuk."\n";
}

echo "\n=== MEMBER ===\n";
foreach (Member::orderBy('id')->get() as $m) {
    echo $m->id.' | '.$m->kode_member.' | '.$m->nama_member.' | status='.$m->status.' | bayar='.$m->jumlah_bayar.' | expired='.$m->tanggal_expired."\n";
}

echo "\n=== TRANSAKSI ===\n";
foreach (App\Models\Transaksi::orderBy('id')->get() as $tr) {
    echo $tr->id.' | '.$tr->kode_tiket.' | bayar='.$tr->total_bayar.' | uang='.$tr->uang_bayar.' | kembali='.$tr->kembalian.' | status='.$tr->status."\n";
}

echo "\n=== USERS ===\n";
foreach (App\Models\User::orderBy('id')->get() as $u) {
    echo $u->id.' | '.$u->email.' | role='.$u->role."\n";
}