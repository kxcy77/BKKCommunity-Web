#!/usr/bin/env bash
set -euo pipefail

base_url="${BKK_BASE_URL:-http://127.0.0.1:8080}/api/v1"
database_name="${BKK_TEST_DB_NAME:-bkk_community}"
database_user="${BKK_TEST_DB_USER:-root}"
stamp="$(date +%s)"
test_email="android.api.${stamp}@example.test"
event_title="Android API Test ${stamp}"
fcm_token="integration-fcm-token-${stamp}"
reset_secret="${BKK_TEST_RESET_CODE_SECRET:?Set BKK_TEST_RESET_CODE_SECRET to the same value used by the test server}"

mysql_test() {
  mysql -N -B -u "$database_user" -D "$database_name" -e "$1"
}

json_value() {
  local expression="$1"
  php -r '$json=json_decode(stream_get_contents(STDIN), true); $value='"$expression"'; if ($value === null) exit(1); echo is_bool($value) ? ($value ? "true" : "false") : $value;'
}

cleanup() {
  mysql_test "DELETE FROM events WHERE title='${event_title}'; DELETE FROM contact_messages WHERE email='${test_email}'; DELETE FROM devices WHERE fcm_token='${fcm_token}'; DELETE FROM users WHERE email='${test_email}'; DELETE FROM api_rate_limits WHERE scope IN ('register','login-ip','login-account','forgot-password-ip','forgot-password-account','reset-password-ip','reset-password-account','contact-ip','contact-account');" >/dev/null 2>&1 || true
}
trap cleanup EXIT

category_id="$(mysql_test 'SELECT id FROM event_categories ORDER BY id LIMIT 1;')"
mysql_test "INSERT INTO events(category_id,title,description,start_at,end_at,location,directions,status) VALUES (${category_id},'${event_title}','Integration event',DATE_ADD(UTC_TIMESTAMP(),INTERVAL 48 HOUR),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 49 HOUR),'BKK Hall','Main entrance','published');" >/dev/null
event_id="$(mysql_test "SELECT id FROM events WHERE title='${event_title}';")"

curl -fsS "${base_url}/health" | php -r '$json=json_decode(stream_get_contents(STDIN), true); if (($json["data"]["status"] ?? null) !== "ok") exit(1);'
curl -fsS "${base_url}/ready" | php -r '$json=json_decode(stream_get_contents(STDIN), true); if (($json["data"]["status"] ?? null) !== "ready") exit(1);'
echo 'PASS health and database readiness contracts'

register="$(curl -fsS -H 'Content-Type: application/json' -d "{\"full_name\":\"Android Test Member\",\"email\":\"${test_email}\",\"phone\":\"0715550101\",\"password\":\"StrongPass26\"}" "${base_url}/auth/register")"
token="$(printf '%s' "$register" | json_value '$json["data"]["token"] ?? null')"
[[ "${#token}" -eq 64 ]]
echo 'PASS registration issued a bearer token'

me="$(curl -fsS -H "Authorization: Bearer ${token}" "${base_url}/me")"
[[ "$(printf '%s' "$me" | json_value '$json["data"]["email"] ?? null')" == "$test_email" ]]
curl -fsS -H 'Content-Type: application/json' -H "Authorization: Bearer ${token}" -X PATCH \
  -d "{\"full_name\":\"Updated Android Member\",\"email\":\"${test_email}\",\"phone\":\"0725550102\"}" "${base_url}/me" >/dev/null
[[ "$(mysql_test "SELECT CONCAT(full_name,':',phone) FROM users WHERE email='${test_email}';")" == 'Updated Android Member:0725550102' ]]
echo 'PASS authenticated profile read and update persisted'

user_id="$(mysql_test "SELECT id FROM users WHERE email='${test_email}';")"
locked_code="654321"
locked_hash="$(php -r 'echo hash_hmac("sha256", $argv[1].":".$argv[2].":".$argv[3], $argv[4]);' "$user_id" "$test_email" "$locked_code" "$reset_secret")"
mysql_test "INSERT INTO password_reset_tokens(user_id,token_hash,failed_attempts,expires_at) VALUES (${user_id},'${locked_hash}',0,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE));" >/dev/null
for attempt in 1 2 3 4 5; do
  status="$(curl -sS -o /tmp/bkk-api-wrong-reset.json -w '%{http_code}' -H 'Content-Type: application/json' -d "{\"email\":\"${test_email}\",\"token\":\"000000\",\"password\":\"NewStrongPass27\"}" "${base_url}/auth/reset-password")"
  [[ "$status" == '422' ]]
done
[[ "$(mysql_test "SELECT CONCAT(failed_attempts,':',used_at IS NOT NULL) FROM password_reset_tokens WHERE user_id=${user_id} ORDER BY id DESC LIMIT 1;")" == '5:1' ]]
echo 'PASS fifth incorrect reset-code attempt invalidated the code'

reset_code="123456"
reset_hash="$(php -r 'echo hash_hmac("sha256", $argv[1].":".$argv[2].":".$argv[3], $argv[4]);' "$user_id" "$test_email" "$reset_code" "$reset_secret")"
mysql_test "INSERT INTO password_reset_tokens(user_id,token_hash,failed_attempts,expires_at) VALUES (${user_id},'${reset_hash}',0,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE));" >/dev/null
curl -fsS -H 'Content-Type: application/json' -d "{\"email\":\"${test_email}\",\"token\":\"${reset_code}\",\"password\":\"NewStrongPass27\"}" "${base_url}/auth/reset-password" >/dev/null
status="$(curl -sS -o /tmp/bkk-api-reset-revoked.json -w '%{http_code}' -H "Authorization: Bearer ${token}" "${base_url}/me")"
[[ "$status" == '401' ]]
login="$(curl -fsS -H 'Content-Type: application/json' -d "{\"email\":\"${test_email}\",\"password\":\"NewStrongPass27\"}" "${base_url}/auth/login")"
token="$(printf '%s' "$login" | json_value '$json["data"]["token"] ?? null')"
[[ "${#token}" -eq 64 ]]
echo 'PASS email-bound 6-digit password reset revoked old sessions and issued a new login token'

events="$(curl -fsS -H "Authorization: Bearer ${token}" "${base_url}/events")"
printf '%s' "$events" | php -r '$json=json_decode(stream_get_contents(STDIN), true); if (!is_array($json["data"] ?? null)) exit(1);'
detail="$(curl -fsS -H "Authorization: Bearer ${token}" "${base_url}/events/${event_id}")"
[[ "$(printf '%s' "$detail" | json_value '$json["data"]["id"] ?? null')" == "$event_id" ]]
echo 'PASS event listing returned the Android JSON contract'

for attempt in 1 2; do
  curl -fsS -H 'Content-Type: application/json' -H "Authorization: Bearer ${token}" -X PUT -d '{"status":"attending"}' "${base_url}/events/${event_id}/attendance" >/dev/null
done
[[ "$(mysql_test "SELECT CONCAT(COUNT(*),':',MAX(status)) FROM attendance WHERE user_id=(SELECT id FROM users WHERE email='${test_email}') AND event_id=${event_id};")" == '1:attending' ]]
echo 'PASS RSVP persisted once and duplicate attendance was prevented'

history="$(curl -fsS -H "Authorization: Bearer ${token}" "${base_url}/me/attendance")"
[[ "$(printf '%s' "$history" | json_value '$json["data"][0]["is_attending"] ?? null')" == 'true' ]]
echo 'PASS attendance history returned confirmed events'

curl -fsS -H 'Content-Type: application/json' -H "Authorization: Bearer ${token}" -d "{\"name\":\"Android Test Member\",\"email\":\"${test_email}\",\"message\":\"This integration message must persist.\"}" "${base_url}/contact" >/dev/null
[[ "$(mysql_test "SELECT COUNT(*) FROM contact_messages WHERE email='${test_email}';")" == '1' ]]
echo 'PASS contact message persisted before success was returned'

curl -fsS -H 'Content-Type: application/json' -H "Authorization: Bearer ${token}" -X PATCH -d '{"notifications_enabled":true,"event_reminders_enabled":false,"discount_alerts_enabled":true}' "${base_url}/me/notification-preferences" >/dev/null
[[ "$(mysql_test "SELECT CONCAT(notifications_enabled,':',event_reminders_enabled,':',discount_alerts_enabled) FROM users WHERE email='${test_email}';")" == '1:0:1' ]]
echo 'PASS notification preferences persisted'

curl -fsS -H 'Content-Type: application/json' -H "Authorization: Bearer ${token}" -X PUT -d "{\"fcm_token\":\"${fcm_token}\",\"notifications_enabled\":true}" "${base_url}/devices/fcm-token" >/dev/null
[[ "$(mysql_test "SELECT COUNT(*) FROM devices WHERE fcm_token='${fcm_token}';")" == '1' ]]
echo 'PASS FCM device token persisted uniquely'

discounts="$(curl -fsS "${base_url}/discounts")"
discount_id="$(printf '%s' "$discounts" | json_value '$json["data"][0]["id"] ?? null')"
curl -fsS "${base_url}/discounts/${discount_id}" | php -r '$json=json_decode(stream_get_contents(STDIN), true); if (!isset($json["data"]["claim_instructions"])) exit(1);'
curl -fsS "${base_url}/local-services" | php -r '$json=json_decode(stream_get_contents(STDIN), true); if (!is_array($json["data"] ?? null)) exit(1);'
echo 'PASS discount details and local-service contracts returned valid data'

curl -fsS -H "Authorization: Bearer ${token}" -X DELETE "${base_url}/auth/session" >/dev/null
status="$(curl -sS -o /tmp/bkk-api-expired.json -w '%{http_code}' -H "Authorization: Bearer ${token}" "${base_url}/me")"
[[ "$status" == '401' ]]
echo 'PASS logout revoked the bearer token'

login="$(curl -fsS -H 'Content-Type: application/json' -d "{\"email\":\"${test_email}\",\"password\":\"NewStrongPass27\"}" "${base_url}/auth/login")"
token="$(printf '%s' "$login" | json_value '$json["data"]["token"] ?? null')"
curl -fsS -H "Authorization: Bearer ${token}" -X DELETE "${base_url}/me" >/dev/null
[[ "$(mysql_test "SELECT COUNT(*) FROM users WHERE email='${test_email}';")" == '0' ]]
[[ "$(mysql_test "SELECT COUNT(*) FROM contact_messages WHERE email='${test_email}' AND user_id IS NULL;")" == '1' ]]
echo 'PASS account deletion removed member data and retained the contact audit record safely'

rate_email="rate-limit.${stamp}@example.test"
for attempt in 1 2 3 4 5 6 7 8 9 10; do
  status="$(curl -sS -o /tmp/bkk-api-rate-limit.json -w '%{http_code}' -H 'Content-Type: application/json' -d "{\"email\":\"${rate_email}\",\"password\":\"WrongPassword27\"}" "${base_url}/auth/login")"
  [[ "$status" == '401' ]]
done
status="$(curl -sS -D /tmp/bkk-api-rate-limit-headers.txt -o /tmp/bkk-api-rate-limit.json -w '%{http_code}' -H 'Content-Type: application/json' -d "{\"email\":\"${rate_email}\",\"password\":\"WrongPassword27\"}" "${base_url}/auth/login")"
[[ "$status" == '429' ]]
grep -qi '^Retry-After:' /tmp/bkk-api-rate-limit-headers.txt
[[ "$(json_value '$json["error"]["code"] ?? null' < /tmp/bkk-api-rate-limit.json)" == 'rate_limited' ]]
echo 'PASS repeated account login attempts were throttled with Retry-After'
