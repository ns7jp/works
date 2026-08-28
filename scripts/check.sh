#!/usr/bin/env sh
set -eu

echo '[1/4] PHP syntax'
find . -name '*.php' -not -path './data/*' -print0 | xargs -0 -n1 php -l

echo '[2/4] Required PHP extensions'
php -r 'foreach (["pdo", "pdo_sqlite", "mbstring"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "missing: $e\n"); exit(1); }}'

echo '[3/4] Writable data directory'
mkdir -p data
test -w data

echo '[4/4] Tracked sensitive-data guard'
test -z "$(git ls-files -- data backups .env '.env.*')"
echo 'PASS: static checks completed'
