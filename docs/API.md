# BKK Community Android API

The Android client uses versioned JSON routes below `/api/v1`. Every successful response has the form `{"data": ...}`. Errors use `{"error":{"code":"...","message":"..."}}` and an appropriate non-2xx HTTP status.

## Authentication

Registration and login return a 64-character bearer token. Only a SHA-256 hash of that token is stored in `auth_sessions`; sessions expire after 30 days and are revoked on logout or password reset.

Send authenticated requests with:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

## Routes

| Method | Route | Authentication |
|---|---|---|
| POST | `/auth/register` | Guest |
| POST | `/auth/login` | Guest |
| POST | `/auth/forgot-password` | Guest |
| POST | `/auth/reset-password` | Guest |
| DELETE | `/auth/session` | Member |
| GET, PATCH, DELETE | `/me` | Member |
| PATCH | `/me/notification-preferences` | Member |
| GET | `/me/attendance` | Member |
| GET | `/events` and `/events/{id}` | Guest; attendance state is added for a member |
| PUT | `/events/{id}/attendance` | Member |
| GET | `/discounts` and `/discounts/{id}` | Guest |
| GET | `/local-services` | Guest |
| POST | `/contact` | Guest or member |
| PUT | `/devices/fcm-token` | Member |

Event, discount, and service listing routes accept the documented category or type query filters. Attendance uses a unique `(user_id, event_id)` database key, so repeating a confirmation updates one record instead of creating duplicates.

## Persistence rule

The browser website retains a clearly labelled session-only demonstration mode for design review. The Android API does not. API writes require a MySQL connection; otherwise the server returns HTTP 503 with `database_unavailable`. A success response therefore means the database operation completed.

## Local development

Start MySQL, provide `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` outside Git, then run:

```bash
php -S 127.0.0.1:8080 -t public public/router.php
BKK_BASE_URL=http://127.0.0.1:8080 ./tests/api-integration.sh
```

The Android emulator reaches the host server through `http://10.0.2.2:8080/api/v1/`. Production must use the configured HTTPS Railway endpoint.

Verified production base URL: `https://bkk-community-platform-production.up.railway.app/api/v1/`.
