#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "$0")/.." && pwd)"
test_port="${BKK_TEST_PORT:-8091}"
base_url="http://127.0.0.1:${test_port}"
server_log="$(mktemp /tmp/bkk-web-server.XXXXXX)"
cookie_jar="$(mktemp /tmp/bkk-web-cookie.XXXXXX)"

php -S "127.0.0.1:${test_port}" -t "${project_dir}/public" >"${server_log}" 2>&1 &
server_pid=$!
trap 'kill "$server_pid" 2>/dev/null || true' EXIT

for attempt in {1..20}; do
  if curl -fsS "${base_url}/index.php" >/dev/null 2>&1; then
    break
  fi
  sleep 0.2
done

for route in index.php events.php discounts.php info.php contact.php login.php register.php reset-password.php new-password.php; do
  code="$(curl -sS -o /dev/null -w '%{http_code}' "${base_url}/${route}")"
  [[ "$code" == "200" ]] || { echo "FAIL ${route}: HTTP ${code}"; exit 1; }
  echo "PASS ${route}: HTTP ${code}"
done

for route in profile.php admin/index.php; do
  code="$(curl -sS -o /dev/null -w '%{http_code}' "${base_url}/${route}")"
  [[ "$code" == "302" ]] || { echo "FAIL guest protection ${route}: HTTP ${code}"; exit 1; }
  echo "PASS guest protection ${route}: HTTP ${code}"
done

csrf_code="$(curl -sS -o /dev/null -w '%{http_code}' --data 'action=logout&csrf_token=invalid' "${base_url}/actions.php")"
[[ "$csrf_code" == "419" ]] || { echo "FAIL CSRF protection: HTTP ${csrf_code}"; exit 1; }
echo "PASS CSRF protection: HTTP ${csrf_code}"

login_html="$(curl -sS -c "$cookie_jar" "${base_url}/login.php")"
csrf="$(printf '%s' "$login_html" | sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' | head -1)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=login' \
  --data-urlencode 'email=member@bkk.demo' \
  --data-urlencode 'password=MemberDemo!26' \
  "${base_url}/actions.php"

profile_html="$(curl -fsS -b "$cookie_jar" "${base_url}/profile.php")"
printf '%s' "$profile_html" | grep -q 'Thandiwe Nkosi' || { echo 'FAIL member login'; exit 1; }
echo 'PASS member login and protected profile'

admin_cookie="$(mktemp /tmp/bkk-web-admin-cookie.XXXXXX)"
admin_login_html="$(curl -sS -c "$admin_cookie" "${base_url}/login.php")"
admin_csrf="$(printf '%s' "$admin_login_html" | sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' | head -1)"
curl -fsS -b "$admin_cookie" -c "$admin_cookie" -o /dev/null \
  --data-urlencode "csrf_token=${admin_csrf}" \
  --data-urlencode 'action=login' \
  --data-urlencode 'email=admin@bkk.demo' \
  --data-urlencode 'password=AdminDemo!26' \
  "${base_url}/actions.php"
admin_html="$(curl -fsS -b "$admin_cookie" "${base_url}/admin/index.php")"
printf '%s' "$admin_html" | grep -q 'Dashboard overview' || { echo 'FAIL admin login'; exit 1; }
echo 'PASS admin login and protected dashboard'
