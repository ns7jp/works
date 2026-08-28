param(
    [string]$BaseUrl = 'http://127.0.0.1:8080'
)

$ErrorActionPreference = 'Stop'

function Get-HttpStatus {
    param([string]$Uri, [string]$Method = 'GET')
    try {
        $response = Invoke-WebRequest -Uri $Uri -Method $Method -UseBasicParsing -TimeoutSec 3
        return [int]$response.StatusCode
    } catch {
        if ($_.Exception.Response) {
            return [int]$_.Exception.Response.StatusCode
        }
        throw
    }
}

for ($attempt = 1; $attempt -le 20; $attempt++) {
    try {
        $health = Invoke-RestMethod -Uri "$BaseUrl/health.php" -TimeoutSec 3
        $login = Invoke-WebRequest -Uri "$BaseUrl/login.php" -UseBasicParsing -TimeoutSec 3
        $poweredBy = $login.Headers['X-Powered-By']
        $nosniff = $login.Headers['X-Content-Type-Options']
        $frameOptions = $login.Headers['X-Frame-Options']
        $cookieHeader = $login.Headers['Set-Cookie'] -join ';'
        $dataStatus = Get-HttpStatus -Uri "$BaseUrl/data/pulse.db"
        $unauthStatus = Get-HttpStatus -Uri "$BaseUrl/api/resonate.php" -Method POST
        $logoutGetStatus = Get-HttpStatus -Uri "$BaseUrl/logout.php"
        if (
            $health.status -eq 'ok' -and
            $login.Content -match '<title>Pulse' -and
            -not $poweredBy -and
            $nosniff -eq 'nosniff' -and
            $frameOptions -eq 'DENY' -and
            $cookieHeader -match 'HttpOnly' -and
            $cookieHeader -match 'SameSite=Lax' -and
            $dataStatus -eq 403 -and
            $unauthStatus -eq 401 -and
            $logoutGetStatus -eq 405
        ) {
            Write-Host "PASS: $BaseUrl responds and SQLite is reachable"
            exit 0
        }
    } catch {
        # 起動直後の接続失敗は、上限回数まで待って再試行する。
    }
    Start-Sleep -Seconds 2
}

throw "$BaseUrl did not become ready after 20 attempts."
