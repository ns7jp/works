param(
    [string]$OutputDirectory = 'backups'
)

$ErrorActionPreference = 'Stop'
New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$target = Join-Path $OutputDirectory "pulse-$timestamp.db"

docker compose exec -T pulse rm -f /tmp/pulse-backup.db
if ($LASTEXITCODE -ne 0) { throw 'Failed to prepare temporary backup path.' }
docker compose exec -T pulse php -r '$p=new PDO("sqlite:/var/www/html/data/pulse.db"); $p->exec("VACUUM INTO " . $p->quote("/tmp/pulse-backup.db"));'
if ($LASTEXITCODE -ne 0) { throw 'SQLite backup failed.' }
docker compose cp pulse:/tmp/pulse-backup.db $target
if ($LASTEXITCODE -ne 0) { throw 'Failed to copy backup from the container.' }
docker compose exec -T pulse rm -f /tmp/pulse-backup.db
if ($LASTEXITCODE -ne 0) { throw 'Failed to remove temporary backup.' }

$hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $target).Hash.ToLowerInvariant()
$manifestTarget = $target.Replace('\', '/')
Set-Content -LiteralPath "$target.sha256" -Encoding ASCII -Value "$hash  $manifestTarget"
Write-Host "Backup created: $target"
