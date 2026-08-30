#!/usr/bin/env sh
set -eu

base_url="${1:-http://127.0.0.1:8080}"
login_body="${TMPDIR:-/tmp}/pulse-login-$$.html"
trap 'rm -f "$login_body"' EXIT

attempt=1
while [ "$attempt" -le 20 ]; do
    health="$(curl --connect-timeout 2 --max-time 3 --fail --silent "$base_url/health.php" 2>/dev/null || true)"
    login_headers="$(curl --connect-timeout 2 --max-time 3 --silent --dump-header - --output "$login_body" "$base_url/login.php" 2>/dev/null || true)"
    data_status="$(curl --connect-timeout 2 --max-time 3 --silent --output /dev/null --write-out '%{http_code}' "$base_url/data/pulse.db" 2>/dev/null || true)"
    unauth_status="$(curl --connect-timeout 2 --max-time 3 --silent --output /dev/null --write-out '%{http_code}' -X POST "$base_url/api/resonate.php" 2>/dev/null || true)"
    logout_get_status="$(curl --connect-timeout 2 --max-time 3 --silent --output /dev/null --write-out '%{http_code}' "$base_url/logout.php" 2>/dev/null || true)"
    if printf '%s\n' "$health" | grep -q '"status":"ok"' \
       && grep -q '<title>Pulse' "$login_body" 2>/dev/null \
       && ! printf '%s\n' "$login_headers" | grep -qi '^X-Powered-By:' \
       && printf '%s\n' "$login_headers" | grep -qi '^X-Content-Type-Options: nosniff' \
       && printf '%s\n' "$login_headers" | grep -qi '^X-Frame-Options: DENY' \
       && printf '%s\n' "$login_headers" | grep -qi '^Set-Cookie:.*HttpOnly' \
       && printf '%s\n' "$login_headers" | grep -qi '^Set-Cookie:.*SameSite=Lax' \
       && [ "$data_status" = '403' ] \
       && [ "$unauth_status" = '401' ] \
       && [ "$logout_get_status" = '405' ]; then
        echo "PASS: $base_url responds and SQLite is reachable"
        exit 0
    fi
    attempt=$((attempt + 1))
    sleep 2
done

echo "FAIL: $base_url did not become ready after 20 attempts" >&2
exit 1
