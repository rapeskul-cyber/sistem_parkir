<?php

namespace Tests\Feature;

use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MemberController;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MemberLaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_registration_creates_report_entry(): void
    {
        $controller = new MemberController();

        $response = $controller->store(new Request([
            'nama_member' => 'Budi Santoso',
            'nama_perusahaan' => 'PT Maju Jaya',
            'uang_bayar' => 500000,
        ]));

        $this->assertSame(201, $response->getStatusCode());

        $member = Member::query()->first();
        $this->assertNotNull($member);
        $this->assertDatabaseHas('transaksis', [
            'kode_tiket' => $member->kode_member,
            'no_plat' => 'MEMBER-REGISTRATION',
            'total_bayar' => '150000.00',
            'uang_bayar' => '500000.00',
            'kembalian' => '350000.00',
        ]);

        $laporanResponse = (new LaporanController())->member(new Request());
        $payload = json_decode($laporanResponse->getContent(), true);

        $this->assertTrue($payload['status']);
        $this->assertNotEmpty($payload['data']);
        $this->assertSame($member->nama_member, $payload['data'][0]['nama_member']);
        $this->assertSame(150000.0, (float) $payload['data'][0]['total_harga']);
        $this->assertSame(500000.0, (float) $payload['data'][0]['jumlah_bayar']);
        $this->assertSame(350000.0, (float) $payload['data'][0]['kembalian']);
    }
}
