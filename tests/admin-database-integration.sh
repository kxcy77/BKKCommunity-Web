#!/usr/bin/env bash
set -euo pipefail

base_url="${BKK_BASE_URL:-http://127.0.0.1:8090}"
database_name="${BKK_TEST_DB_NAME:-bkk_community}"
database_user="${BKK_TEST_DB_USER:-root}"
stamp="$(date +%s)"
test_email="codex.admin.${stamp}@example.test"
test_name="Integration Admin ${stamp}"
event_title="Integration Event ${stamp}"
discount_store="Integration Store ${stamp}"
service_name="Integration Service ${stamp}"
category="Integration ${stamp}"
cookie_jar="$(mktemp /tmp/bkk-admin-integration.XXXXXX)"

mysql_test() {
  mysql -N -B -u "$database_user" -D "$database_name" -e "$1"
}

csrf_from() {
  sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' | head -1
}

cleanup() {
  mysql_test "DELETE FROM contact_messages WHERE email='${test_email}'; DELETE FROM local_services WHERE name='${service_name}'; DELETE d FROM discounts d JOIN discount_categories c ON c.id=d.category_id WHERE d.store_name='${discount_store}'; DELETE e FROM events e JOIN event_categories c ON c.id=e.category_id WHERE e.title='${event_title}'; DELETE FROM discount_categories WHERE name='${category}'; DELETE FROM event_categories WHERE name='${category}'; DELETE FROM users WHERE email='${test_email}';" >/dev/null 2>&1 || true
  rm -f "$cookie_jar"
}
trap cleanup EXIT

register_html="$(curl -fsS -c "$cookie_jar" "${base_url}/register.php")"
csrf="$(printf '%s' "$register_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=register' \
  --data-urlencode "full_name=${test_name}" \
  --data-urlencode "email=${test_email}" \
  --data-urlencode 'phone=071 555 0201' \
  --data-urlencode 'password=StrongAdmin26' \
  --data-urlencode 'password_confirmation=StrongAdmin26' \
  --data-urlencode 'privacy_consent=1' \
  "${base_url}/actions.php"
mysql_test "UPDATE users SET role='admin' WHERE email='${test_email}';" >/dev/null

login_html="$(curl -fsS -b "$cookie_jar" -c "$cookie_jar" "${base_url}/login.php")"
csrf="$(printf '%s' "$login_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=login' \
  --data-urlencode "email=${test_email}" \
  --data-urlencode 'password=StrongAdmin26' \
  "${base_url}/actions.php"
admin_html="$(curl -fsS -b "$cookie_jar" "${base_url}/admin/index.php")"
printf '%s' "$admin_html" | grep -q 'Dashboard overview'
echo 'PASS database administrator login'

contact_html="$(curl -fsS -b "$cookie_jar" "${base_url}/contact.php")"
csrf="$(printf '%s' "$contact_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=contact' \
  --data-urlencode "name=${test_name}" \
  --data-urlencode "email=${test_email}" \
  --data-urlencode 'phone=071 555 0201' \
  --data-urlencode 'subject=General enquiry' \
  --data-urlencode "message=Admin inbox integration message ${stamp}." \
  "${base_url}/actions.php"
message_id="$(mysql_test "SELECT id FROM contact_messages WHERE email='${test_email}' ORDER BY id DESC LIMIT 1;")"
messages_html="$(curl -fsS -b "$cookie_jar" "${base_url}/admin/messages.php")"
printf '%s' "$messages_html" | grep -q "Admin inbox integration message ${stamp}"
csrf="$(printf '%s' "$messages_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=admin_update_message' \
  --data-urlencode "id=${message_id}" \
  --data-urlencode 'status=resolved' \
  "${base_url}/actions.php"
[[ "$(mysql_test "SELECT status FROM contact_messages WHERE id=${message_id};")" == 'resolved' ]]
echo 'PASS contact inbox and status management'

events_html="$(curl -fsS -b "$cookie_jar" "${base_url}/admin/events.php")"
csrf="$(printf '%s' "$events_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=admin_create_event' \
  --data-urlencode "title=${event_title}" \
  --data-urlencode 'date=2030-08-10' \
  --data-urlencode 'time=10:00' \
  --data-urlencode 'end_time=11:30' \
  --data-urlencode 'location=Integration Hall' \
  --data-urlencode "category=${category}" \
  --data-urlencode 'tone=teal' \
  --data-urlencode 'description=Integration event created by the automated admin test.' \
  "${base_url}/actions.php"
event_id="$(mysql_test "SELECT id FROM events WHERE title='${event_title}' LIMIT 1;")"
[[ -n "$event_id" ]]
echo 'PASS administrator event creation'

discounts_html="$(curl -fsS -b "$cookie_jar" "${base_url}/admin/discounts.php")"
csrf="$(printf '%s' "$discounts_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=admin_create_discount' \
  --data-urlencode "store_name=${discount_store}" \
  --data-urlencode "category=${category}" \
  --data-urlencode 'deal=Integration discount offer' \
  --data-urlencode 'eligibility=BKK members' \
  --data-urlencode 'claim_instructions=Show your membership card.' \
  --data-urlencode 'tone=gold' \
  "${base_url}/actions.php"
discount_id="$(mysql_test "SELECT id FROM discounts WHERE store_name='${discount_store}' LIMIT 1;")"
[[ -n "$discount_id" ]]
echo 'PASS administrator discount creation'

services_html="$(curl -fsS -b "$cookie_jar" "${base_url}/admin/services.php")"
csrf="$(printf '%s' "$services_html" | csrf_from)"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null \
  --data-urlencode "csrf_token=${csrf}" \
  --data-urlencode 'action=admin_create_service' \
  --data-urlencode 'type=support' \
  --data-urlencode "name=${service_name}" \
  --data-urlencode 'address=12 Integration Road' \
  --data-urlencode 'phone=071 555 0202' \
  --data-urlencode 'opening_hours=Weekdays, 08:00-17:00' \
  --data-urlencode 'directions=Ask at the community hall reception.' \
  "${base_url}/actions.php"
service_id="$(mysql_test "SELECT id FROM local_services WHERE name='${service_name}' LIMIT 1;")"
[[ -n "$service_id" ]]
echo 'PASS administrator local-service creation'

curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null --data-urlencode "csrf_token=${csrf}" --data-urlencode 'action=admin_delete_event' --data-urlencode "id=${event_id}" "${base_url}/actions.php"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null --data-urlencode "csrf_token=${csrf}" --data-urlencode 'action=admin_delete_discount' --data-urlencode "id=${discount_id}" "${base_url}/actions.php"
curl -fsS -b "$cookie_jar" -c "$cookie_jar" -o /dev/null --data-urlencode "csrf_token=${csrf}" --data-urlencode 'action=admin_delete_service' --data-urlencode "id=${service_id}" "${base_url}/actions.php"
[[ "$(mysql_test "SELECT (SELECT COUNT(*) FROM events WHERE id=${event_id}) + (SELECT COUNT(*) FROM discounts WHERE id=${discount_id}) + (SELECT COUNT(*) FROM local_services WHERE id=${service_id});")" == '0' ]]
echo 'PASS administrator content deletion'
echo 'PASS persistent administrator journey complete'
