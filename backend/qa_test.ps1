# ==========================================================
# QA AUTOMATED TEST - E-PARKIR (Laravel API)
# ==========================================================
$base = "http://127.0.0.1:8000/api"
$results = @()

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Method,
        [string]$Url,
        [string]$Body = $null,
        [string]$Token = $null,
        [int]$Expect = 200
    )
    $headers = @{}
    if ($Token) { $headers["Authorization"] = "Bearer $Token" }

    try {
        $params = @{
            Uri         = $Url
            Method      = $Method
            Headers     = $headers
            ContentType = "application/json"
        }
        if ($Body) { $params["Body"] = $Body }

        $resp = Invoke-RestMethod @params
        $detail = if ($resp.message) { $resp.message } else { "berhasil" }
        $script:results += [PSCustomObject]@{ Fitur = $Name; Status = "OK"; Kode = "HTTP"; Detail = $detail }
        return $resp
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $errBody = $reader.ReadToEnd()
        $script:results += [PSCustomObject]@{ Fitur = $Name; Status = "GAGAL"; Kode = "HTTP $code"; Detail = $errBody }
        return $null
    }
}

Write-Host "`n===================== 1. AUTENTIKASI =====================" -ForegroundColor Cyan

$adminLogin = Test-Endpoint -Name "Login Admin" -Method POST -Url "$base/login" -Body '{"email":"superadmin@parkir.test","password":"password123"}'
$adminToken = $adminLogin.token

$petugasLogin = Test-Endpoint -Name "Login Petugas" -Method POST -Url "$base/login" -Body '{"email":"petugas@parkir.test","password":"password123"}'
$petugasToken = $petugasLogin.token

Test-Endpoint -Name "Login Password Salah (harus 401)" -Method POST -Url "$base/login" -Body '{"email":"superadmin@parkir.test","password":"salah123"}'

Write-Host "`n===================== 2. LUPA PASSWORD (MANUAL/CEK EMAIL) =====================" -ForegroundColor Cyan
Test-Endpoint -Name "Lupa Password - Cek Email Valid" -Method POST -Url "$base/forgot-password/check-email" -Body '{"email":"superadmin@parkir.test"}'
Test-Endpoint -Name "Lupa Password - Email Tidak Terdaftar" -Method POST -Url "$base/forgot-password/check-email" -Body '{"email":"ngawur@test.com"}'

Write-Host "`n===================== 3. ROLE USER (PUBLIC) =====================" -ForegroundColor Cyan
$tiket = Test-Endpoint -Name "User: Buat Tiket Non-Member" -Method POST -Url "$base/tiket" -Body '{"kategori":"mobil","plat_nomor":"D9999ZZ"}'
$kodeTiket = $tiket.data.kode_tiket
Test-Endpoint -Name "User: Lihat Tiket by Kode" -Method GET -Url "$base/tiket/$kodeTiket"

$member = (Test-Endpoint -Name "Petugas: List Member" -Method GET -Url "$base/member" -Token $adminToken).data[0]
$kodeMember = $member.kode_member

Test-Endpoint -Name "User: Cek Member (public)" -Method POST -Url "$base/member/check" -Body "{`"kode_member`":`"$kodeMember`"}"

Write-Host "`n===================== 4. GATE MASUK / SCAN =====================" -ForegroundColor Cyan
Test-Endpoint -Name "Gate: Scan Tiket (masuk)" -Method POST -Url "$base/gate/scan" -Token $petugasToken -Body "{`"kode`":`"$kodeTiket`"}"

Write-Host "`n===================== 5. PEMBAYARAN (GATE KELUAR) =====================" -ForegroundColor Cyan
$scan = Test-Endpoint -Name "Bayar: Scan Tiket Non-Member" -Method POST -Url "$base/scan" -Token $petugasToken -Body "{`"kode`":`"$kodeTiket`",`"jenis_kendaraan`":`"mobil`"}"
$total = $scan.data.total_tarif

if ($total) {
    Test-Endpoint -Name "Bayar: Proses Bayar Tunai" -Method POST -Url "$base/payment" -Token $petugasToken -Body "{`"kode_tiket`":`"$kodeTiket`",`"uang_bayar`":50000,`"total_tarif`":$total,`"kategori`":`"mobil`",`"no_plat`":`"D9999ZZ`"}"
    Test-Endpoint -Name "Bayar: Uang Kurang (harus 400)" -Method POST -Url "$base/payment" -Token $petugasToken -Body "{`"kode_tiket`":`"$kodeTiket`",`"uang_bayar`":1000,`"total_tarif`":$total}"
}

Write-Host "`n===================== 6. CRUD MEMBER =====================" -ForegroundColor Cyan
$newMember = Test-Endpoint -Name "Member: Create" -Method POST -Url "$base/member" -Token $petugasToken -Body '{"nama_member":"Budi QA","nama_perusahaan":"PT Budi","jumlah_bayar":150000}'
$mid = $newMember.data.id
Test-Endpoint -Name "Member: Detail" -Method GET -Url "$base/member/$mid" -Token $petugasToken
Test-Endpoint -Name "Member: Update" -Method PUT -Url "$base/member/$mid" -Token $petugasToken -Body '{"nama_member":"Budi QA Update","nama_perusahaan":"PT Budi Jaya"}'
Test-Endpoint -Name "Member: Bayar/Perpanjang" -Method POST -Url "$base/member/bayar/$mid" -Token $petugasToken
Test-Endpoint -Name "Member: Delete" -Method DELETE -Url "$base/member/$mid" -Token $petugasToken

Write-Host "`n===================== 7. LAPORAN =====================" -ForegroundColor Cyan
Test-Endpoint -Name "Laporan: Member" -Method GET -Url "$base/laporan/member" -Token $petugasToken
Test-Endpoint -Name "Laporan: Non-Member" -Method GET -Url "$base/laporan/non-member" -Token $petugasToken
Test-Endpoint -Name "Laporan: Rekap Global" -Method GET -Url "$base/admin/laporan" -Token $adminToken
Test-Endpoint -Name "Dashboard: Stats" -Method GET -Url "$base/dashboard/stats" -Token $adminToken
Test-Endpoint -Name "Parkir: Kendaraan Aktif" -Method GET -Url "$base/parkir/aktif" -Token $petugasToken
Test-Endpoint -Name "Transaksi: List" -Method GET -Url "$base/transaksi" -Token $petugasToken

Write-Host "`n===================== 8. ADMIN - KELOLA PETUGAS =====================" -ForegroundColor Cyan
Test-Endpoint -Name "Admin: List Petugas" -Method GET -Url "$base/admin/petugas" -Token $adminToken
$np = Test-Endpoint -Name "Admin: Create Petugas" -Method POST -Url "$base/admin/petugas" -Token $adminToken -Body '{"name":"Petugas QA","email":"petugas.qa@parkir.test","no_telepon":"081200009999","password":"password123","role":"petugas"}'
$npid = $np.data.id
Test-Endpoint -Name "Admin: Update Petugas" -Method PUT -Url "$base/admin/petugas/$npid" -Token $adminToken -Body '{"name":"Petugas QA Edit","email":"petugas.qa@parkir.test","no_telepon":"081200009999","role":"petugas"}'
Test-Endpoint -Name "Admin: Reset Password Petugas" -Method PUT -Url "$base/admin/petugas/$npid/reset-password" -Token $adminToken -Body '{"password":"password456"}'
Test-Endpoint -Name "Admin: Duplikasi Email (harus gagal)" -Method POST -Url "$base/admin/petugas" -Token $adminToken -Body '{"name":"Dup","email":"petugas.qa@parkir.test","no_telepon":"081200001234","password":"password123","role":"petugas"}'
Test-Endpoint -Name "Admin: Delete Petugas" -Method DELETE -Url "$base/admin/petugas/$npid" -Token $adminToken

Write-Host "`n===================== 9. KEAMANAN (TANPA TOKEN) =====================" -ForegroundColor Cyan
Test-Endpoint -Name "Tanpa Token: List Member (harus 401)" -Method GET -Url "$base/member"
Test-Endpoint -Name "Tanpa Token: Dashboard (harus 401)" -Method GET -Url "$base/dashboard/stats"

Write-Host "`n===================== HASIL =====================" -ForegroundColor Yellow
$results | Format-Table -AutoSize -Wrap
$ok = ($results | Where-Object { $_.Status -eq "OK" }).Count
$fail = ($results | Where-Object { $_.Status -eq "GAGAL" }).Count
Write-Host "`nTOTAL: $($results.Count) | OK: $ok | GAGAL: $fail" -ForegroundColor Green