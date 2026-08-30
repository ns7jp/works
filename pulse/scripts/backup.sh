#!/usr/bin/env sh
set -eu

output_dir="${1:-backups}"
mkdir -p "$output_dir"
timestamp="$(date +%Y%m%d-%H%M%S)"
target="$output_dir/pulse-$timestamp.db"

docker compose exec -T pulse rm -f /tmp/pulse-backup.db
docker compose exec -T pulse php -r '$p=new PDO("sqlite:/var/www/html/data/pulse.db"); $p->exec("VACUUM INTO " . $p->quote("/tmp/pulse-backup.db"));'
docker compose cp pulse:/tmp/pulse-backup.db "$target"
docker compose exec -T pulse rm -f /tmp/pulse-backup.db
sha256sum "$target" > "$target.sha256"
echo "Backup created: $target"
