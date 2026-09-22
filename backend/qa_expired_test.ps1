# QA Test: Skenario Member Expired (Time Travel)
$base = "http://127.0.0.1:8000/api"

function Req {
    param([string]$Name, [string]$Method, [string]$Url, [string]$Body, [string]$Token)
    $h = @{}
    if ($Token) { $h["Authorization"] = "Bearer $Token" }
    try {
        $p = @{ Uri = $Url; Method = $Method; Headers = $h; ContentType = "application/json" }
        if ($Body) { $p["Body"] = $Body }
        $r = Invoke-RestMethod @p
        Write-Host "[$Name]" -NoNewline -ForegroundColor Cyan
        Write-Host " HTTP $($r.status)" -NoNewline -ForegroundColor Green
        Write-Host " -> $($r.message)" -ForegroundColor Gray
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        $sr = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $b = $sr.ReadToEnd()
        Write-Host "[$Name]" -NoNewline -ForegroundColor Cyan
        Write-Host " HTTP $code" -NoNewline -ForegroundColor Yellow
        Write-Host " -> $b" -ForegroundColor Gray
    }
}

$login = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body '{"email":"petugas@parkir.test","password":"password123"}'
$tok = $login.token

Write-Host "`n===== SIMULASI MEMBER EXPIRED =====" -ForegroundColor Yellow
Req -Name "1. member/check (public)" -Method POST -Url "$base/member/check" -Body '{"kode_member":"MBR-QADFE73F"}'
Req -Name "2. gate/masuk-member" -Method POST -Url "$base/gate/masuk-member" -Body '{"kode":"MBR-QADFE73F"}' -Token $tok
Req -Name "3. scan (gate keluar)" -Method POST -Url "$base/scan" -Body '{"kode":"MBR-QADFE73F"}' -Token $tok
Req -Name "4. member/bayar (perpanjang)" -Method POST -Url "$base/member/bayar/1" -Token $tok