#!/usr/bin/env bash
set -euo pipefail

base_url="${BKK_BASE_URL:-http://127.0.0.1:8090}"
database_name="${BKK_TEST_DB_NAME:-bkk_community}"
database_user="${BKK_TEST_DB_USER:-root}"
stamp="$(date +%s)"
test_email="codex.integration.${stamp}@example.test"
test_name="Integration Member ${stamp}"
test_message="Integration contact message ${stamp} for persistence verification."
cookie_jar="$(mktemp /tmp/bkk-db-integration.XXXXXX)"

mysql_test() {
  mysql -N -B -u "$database_user" -D "$database_name" -e "$1"
}

csrf_from() {
  sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' | head -1
}

cleanup() {
  mysql_test "DELETE FROM contact_messages WHERE email='${test_email}'; DELETE FROM users WHERE email='${test_email}';" >/dev/null 2>&1 || true
  rm -f "$cookie_jar"
}
trap cleanup EXIT

register_html="$(curl -fsS -c "$cookie_jar" "${base_url}/register.php")"
csrf="$(printf '%s' "$register_html" | csrf_from)"
[[ -n "$csrf" ]]
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=register' \
  --data-urlencode "full_name=${test_name}" \
  --data-urlencode "email=${test_email}" \
  --data-urlencode 'phone=071 555 0101' \
  --data-urlencode 'password=StrongPass26' \
  --data-urlencode 'password_confirmation=StrongPass26' \
  --data-urlencode 'privacy_consent=1' \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT COUNT(*) FROM users WHERE email='${test_email}' AND role='member';")" == '1' ]]
echo 'PASS registration persisted'

user_id="$(mysql_test "SELECT id FROM users WHERE email='${test_email}';")"
printf -v reset_token '%064x' "$stamp"
token_hash="$(printf '%s' "$reset_token" | shasum -a 256 | awk '{print $1}')"
mysql_test "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (${user_id}, '${token_hash}', DATE_ADD(NOW(), INTERVAL 1 HOUR));" >/dev/null
reset_html="$(curl -fsS -b "$cookie_jar" -c "$cookie_jar" "${base_url}/new-password.php?token=${reset_token}")"
csrf="$(printf '%s' "$reset_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=complete_reset' \
  --data-urlencode "token=${reset_token}" \
  --data-urlencode 'password=NewStrongPass26' \
  --data-urlencode 'password_confirmation=NewStrongPass26' \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT COUNT(*) FROM password_reset_tokens WHERE user_id=${user_id} AND used_at IS NOT NULL;")" == '1' ]]
echo 'PASS password reset token consumed'

login_html="$(curl -fsS -b "$cookie_jar" -c "$cookie_jar" "${base_url}/login.php")"
csrf="$(printf '%s' "$login_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=login' \
  --data-urlencode "email=${test_email}" \
  --data-urlencode 'password=NewStrongPass26' \
  "${base_url}/actions.php"
profile_html="$(curl -fsS -b "$cookie_jar" "${base_url}/profile.php")"
printf '%s' "$profile_html" | grep -q "$test_name"
echo 'PASS login and protected profile'

events_html="$(curl -fsS -b "$cookie_jar" "${base_url}/events.php")"
event_id="$(printf '%s' "$events_html" | sed -n 's/.*name="event_id" value="\([0-9][0-9]*\)".*/\1/p' | head -1)"
csrf="$(printf '%s' "$events_html" | csrf_from)"
[[ -n "$event_id" ]]
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=toggle_rsvp' \
  --data-urlencode "event_id=${event_id}" \
  --data-urlencode 'return_to=events.php' \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT a.status FROM attendance a JOIN users u ON u.id=a.user_id WHERE u.email='${test_email}' AND a.event_id=${event_id};")" == 'attending' ]]
echo 'PASS RSVP persisted'

curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" --data-urlencode 'action=toggle_rsvp' --data-urlencode "event_id=${event_id}" --data-urlencode 'return_to=events.php' "${base_url}/actions.php"
[[ "$(mysql_test "SELECT CONCAT(COUNT(*), ':', MAX(a.status)) FROM attendance a JOIN users u ON u.id=a.user_id WHERE u.email='${test_email}' AND a.event_id=${event_id};")" == '1:cancelled' ]]
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" --data-urlencode 'action=toggle_rsvp' --data-urlencode "event_id=${event_id}" --data-urlencode 'return_to=events.php' "${base_url}/actions.php"
[[ "$(mysql_test "SELECT CONCAT(COUNT(*), ':', MAX(a.status)) FROM attendance a JOIN users u ON u.id=a.user_id WHERE u.email='${test_email}' AND a.event_id=${event_id};")" == '1:attending' ]]
echo 'PASS duplicate RSVP prevented and cancellation reversible'

curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=update_preferences' \
  --data-urlencode 'event_reminders=1' \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT CONCAT(event_reminders_enabled, ':', discount_alerts_enabled) FROM users WHERE email='${test_email}';")" == '1:0' ]]
echo 'PASS notification preferences persisted'

curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=update_profile' \
  --data-urlencode 'full_name=Updated Integration Member' \
  --data-urlencode 'phone=072 555 0102' \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT CONCAT(full_name, ':', phone) FROM users WHERE email='${test_email}';")" == 'Updated Integration Member:072 555 0102' ]]
echo 'PASS profile update persisted'

contact_html="$(curl -fsS -b "$cookie_jar" "${base_url}/contact.php")"
csrf="$(printf '%s' "$contact_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=contact' \
  --data-urlencode 'name=Updated Integration Member' \
  --data-urlencode "email=${test_email}" \
  --data-urlencode 'phone=072 555 0102' \
  --data-urlencode 'subject=Website support' \
  --data-urlencode "message=${test_message}" \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT CONCAT(subject, ':', phone) FROM contact_messages WHERE email='${test_email}' ORDER BY id DESC LIMIT 1;")" == 'Website support:072 555 0102' ]]
echo 'PASS contact message persisted'

profile_html="$(curl -fsS -b "$cookie_jar" "${base_url}/profile.php")"
csrf="$(printf '%s' "$profile_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=delete_account' \
  --data-urlencode "confirm_email=${test_email}" \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT COUNT(*) FROM users WHERE email='${test_email}';")" == '0' ]]
[[ "$(mysql_test "SELECT COUNT(*) FROM contact_messages WHERE email='${test_email}' AND user_id IS NULL;")" == '1' ]]
echo 'PASS account deletion and relational cleanup'
echo 'PASS persistent member journey complete'
