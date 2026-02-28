<#
run_order_flow.ps1
PowerShell automation for smoke-testing the order workflow:
- Login as three users (karyawan, supplier, admin)
- Create an order (karyawan)
- Supplier claims the order
- Admin cancels the order
- Supplier reopens the approval
- Supplier accepts the order

Edit the credentials and `$SupplierRecordId` below to match your environment.
#>

param()

$BaseUrl = 'http://localhost/pesanan_kantin'

# --- Configure these accounts ---
$Karyawan = @{ email = 'karyawan@example.com'; password = 'KARYAWAN_PASS' }
$Supplier = @{ email = 'supplier@example.com'; password = 'SUPPLIER_PASS' }
$Admin = @{ email = 'admin@example.com'; password = 'ADMIN_PASS' }

# supplier record id from suppliers table (used when claiming)
$SupplierRecordId = 2

function Get-Csrf {
    param($BaseUrl, [System.Net.WebRequestSession]$Session)
    $r = Invoke-WebRequest -Uri "$BaseUrl/login.php" -WebSession $Session -UseBasicParsing -ErrorAction Stop
    $m = [regex]::Match($r.Content, 'name="_csrf" value="([^"]+)"')
    return $m.Success ? $m.Groups[1].Value : $null
}

function Post-Api {
    param($Url, $Body, $Session)
    try {
        $r = Invoke-WebRequest -Uri $Url -Method Post -Body $Body -WebSession $Session -UseBasicParsing -ContentType 'application/x-www-form-urlencoded' -ErrorAction Stop
        return $r.Content | ConvertFrom-Json
    } catch {
        Write-Host "Request failed: $Url`n$_" -ForegroundColor Red
        return $null
    }
}

Write-Host "Starting order flow smoke test against $BaseUrl" -ForegroundColor Cyan

# Karyawan: create session, login, create order
$sessK = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$csrf = Get-Csrf -BaseUrl $BaseUrl -Session $sessK
if (-not $csrf) { Write-Host 'Failed to get CSRF for karyawan' -ForegroundColor Red; exit 1 }
$res = Post-Api "$BaseUrl/api/auth.php" @{ _csrf=$csrf; email=$Karyawan.email; password=$Karyawan.password } $sessK
if (-not $res -or -not $res.ok) { Write-Host 'Karyawan login failed' -ForegroundColor Red; $res; exit 1 }
Write-Host 'Karyawan logged in' -ForegroundColor Green

$createBody = @{ _csrf=$csrf; order_date = (Get-Date -Format yyyy-MM-dd); menu_id=''; item='PS Test Item'; quantity=1; total_price=10000 }
$cres = Post-Api "$BaseUrl/api/create_order.php" $createBody $sessK
if (-not $cres -or -not $cres.ok) { Write-Host 'Create order failed' -ForegroundColor Red; $cres; exit 1 }
$orderId = $cres.id
Write-Host "Order created: ID=$orderId" -ForegroundColor Green

# Supplier: claim
$sessS = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$csrfS = Get-Csrf -BaseUrl $BaseUrl -Session $sessS
$resS = Post-Api "$BaseUrl/api/auth.php" @{ _csrf=$csrfS; email=$Supplier.email; password=$Supplier.password } $sessS
if (-not $resS -or -not $resS.ok) { Write-Host 'Supplier login failed' -ForegroundColor Red; $resS; exit 1 }
Write-Host 'Supplier logged in' -ForegroundColor Green

$claimBody = @{ _csrf=$csrfS; order_id=$orderId; supplier_id=$SupplierRecordId }
$claimRes = Post-Api "$BaseUrl/api/claim_order.php" $claimBody $sessS
Write-Host "Claim response: $(ConvertTo-Json $claimRes -Depth 3)"
if (-not $claimRes -or -not $claimRes.ok) { Write-Host 'Claim failed' -ForegroundColor Red; exit 1 }

# Admin: cancel
$sessA = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$csrfA = Get-Csrf -BaseUrl $BaseUrl -Session $sessA
$resA = Post-Api "$BaseUrl/api/auth.php" @{ _csrf=$csrfA; email=$Admin.email; password=$Admin.password } $sessA
if (-not $resA -or -not $resA.ok) { Write-Host 'Admin login failed' -ForegroundColor Red; $resA; exit 1 }
Write-Host 'Admin logged in' -ForegroundColor Green

$cancelRes = Post-Api "$BaseUrl/api/cancel_order.php" @{ _csrf=$csrfA; order_id=$orderId } $sessA
Write-Host "Cancel response: $(ConvertTo-Json $cancelRes -Depth 3)"
if (-not $cancelRes -or -not $cancelRes.ok) { Write-Host 'Cancel failed' -ForegroundColor Red; exit 1 }

# Supplier: reopen
$reopenRes = Post-Api "$BaseUrl/api/reopen_order.php" @{ _csrf=$csrfS; order_id=$orderId } $sessS
Write-Host "Reopen response: $(ConvertTo-Json $reopenRes -Depth 3)"
if (-not $reopenRes -or -not $reopenRes.ok) { Write-Host 'Reopen failed' -ForegroundColor Red; exit 1 }

# Supplier: accept
$acceptRes = Post-Api "$BaseUrl/api/accept_order.php" @{ _csrf=$csrfS; order_id=$orderId } $sessS
Write-Host "Accept response: $(ConvertTo-Json $acceptRes -Depth 3)"
if (-not $acceptRes -or -not $acceptRes.ok) { Write-Host 'Accept failed' -ForegroundColor Red; exit 1 }

Write-Host 'Smoke test completed successfully.' -ForegroundColor Green

# end
