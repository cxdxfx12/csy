param()

$baseUrl = "https://www.hbdxm.com/index.php/api"

# Step 1: 注册测试用户
$testPhone = "13800138000"
$testPassword = "test123456"
Write-Host "=== Step 1: Register ==="

$registerBody = @{ phone = $testPhone; password = $testPassword } | ConvertTo-Json
try {
    $rr = Invoke-WebRequest -Uri "$baseUrl/register" -Method POST -Body $registerBody -ContentType "application/json" -ErrorAction Stop
    Write-Host "Register: $($rr.Content)"
} catch {
    Write-Host "Register error (maybe exists): $($_.Exception.Message)"
}

# Step 2: 登录
Write-Host "`n=== Step 2: Login ==="
$loginBody = @{ phone = $testPhone; password = $testPassword } | ConvertTo-Json
$token = $null
try {
    $lr = Invoke-WebRequest -Uri "$baseUrl/login" -Method POST -Body $loginBody -ContentType "application/json" -ErrorAction Stop
    Write-Host "Login: $($lr.Content)"
    $ld = $lr.Content | ConvertFrom-Json
    if ($ld.code -eq 0) {
        $token = $ld.data.token
        Write-Host "Token obtained (first 50 chars): $($token.Substring(0, [Math]::Min(50, $token.Length)))"
    }
} catch {
    Write-Host "Login failed: $($_.Exception.Message)"
}

# Step 3: claim API overall test
Write-Host "`n=== Step 3: claimProperty POST test ==="
$cbody = '{"phone":"19171045360"}'
try {
    $cr = Invoke-WebRequest -Uri "$baseUrl/claimProperty" -Method POST -Body $cbody -ContentType "application/json" -ErrorAction Stop
    Write-Host "Status: $($cr.StatusCode)"
    Write-Host "Content-Type: $($cr.Headers['Content-Type'])"
    Write-Host "Body: $($cr.Content)"
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        Write-Host "HTTP Status: $($_.Exception.Response.StatusCode.value__)"
        try {
            $sr = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $errBody = $sr.ReadToEnd()
            $sr.Close()
            Write-Host "Error body (first 300 chars): $($errBody.Substring(0, [Math]::Min(300, $errBody.Length)))"
        } catch {
            Write-Host "Could not read error body"
        }
    }
}

# Step 4: 有token的claim
if ($token) {
    Write-Host "`n=== Step 4: claimProperty with token ==="
    $headers = @{ Authorization = "Bearer $token" }
    try {
        $cr2 = Invoke-WebRequest -Uri "$baseUrl/claimProperty" -Method POST -Body $cbody -ContentType "application/json" -Headers $headers -ErrorAction Stop
        Write-Host "Status: $($cr2.StatusCode)"
        Write-Host "Body: $($cr2.Content)"
    } catch {
        Write-Host "Error: $($_.Exception.Message)"
        if ($_.Exception.Response) {
            Write-Host "HTTP Status: $($_.Exception.Response.StatusCode.value__)"
            try {
                $sr2 = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $errBody2 = $sr2.ReadToEnd()
                $sr2.Close()
                Write-Host "Error body: $($errBody2)"
            } catch {}
        }
    }
}

Write-Host "`n=== Test Complete ==="
