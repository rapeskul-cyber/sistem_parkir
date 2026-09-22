# QA Test: Alur Lengkap Member (Masuk -> Keluar)
$base = "http://127.0.0.1:8000/api"

function Req {
    param([string]$Name, [string]$Method, [string]$Url, [string]$Body, [string]$Token)
    $h = @{}
    if ($Token) { $h["Authorization"] = "Bearer $Token" }
    try {
        $p = @{ Uri = $Url; Method = $Method; Headers = $h; ContentType = "application/json" }
        if ($Body) { $p["Body"] = $Body }
        $r = Invoke-RestMethod @p
        Write-Host "[$Name] OK" -ForegroundColor Green
        return $r
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        $sr = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $b = $sr.ReadToEnd()
        Write-Host "[$Name] HTTP $code -> $b" -ForegroundColor Red
        return $null
    }
}

$login = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body '{"email":"petugas@parkir.test","password":"password123"}'
$tok = $login.token

Write-Host "`n===== ALUR MEMBER: MASUK GERBANG =====" -ForegroundColor Yellow
$masuk = Req -Name "Gate Masuk Member" -Method POST -Url "$base/gate/masuk-member" -Body '{"kode":"MBR-QADFE73F","kategori":"mobil"}' -Token $tok
if ($masuk) { Write-Host "  -> $($masuk.message)" -ForegroundColor Gray; Write-Host "  -> Kode Sesi: $($masuk.data.kode_tiket)" -ForegroundColor Gray }

Write-Host "`n===== CEK KENDARAAN AKTIF =====" -ForegroundColor Yellow
$aktif = Req -Name "Kendaraan Aktif" -Method GET -Url "$base/parkir/aktif" -Token $tok
if ($aktif) { $aktif.data | Format-Table kode_tiket,tipe,kategori,no_plat,waktu_masuk -AutoSize }

Write-Host "`n===== ALUR MEMBER: KELUAR GERBANG =====" -ForegroundColor Yellow
$scan = Req -Name "Scan Gate Keluar (member)" -Method POST -Url "$base/scan" -Body '{"kode":"MBR-QADFE73F"}' -Token $tok
if ($scan) { $scan | ConvertTo-Json -Depth 4 }

Write-Host "`n===== DUPLIKASI MASUK (harus ditolak) =====" -ForegroundColor Yellow
Req -Name "Masuk 2x" -Method POST -Url "$base/gate/masuk-member" -Body '{"kode":"MBR-QADFE73F"}' -Token $tok