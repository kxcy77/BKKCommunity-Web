# BKK Community Web App

A responsive PHP web application for BKK Community Group, based on the documented Phase 2 high-fidelity website design and Phase 3 authentication/admin screens.

![BKK Community web app desktop home screen](docs/screenshots/home-desktop.png)

Responsive evidence is also available in [the mobile home capture](docs/screenshots/home-mobile.png) and [the desktop events capture](docs/screenshots/events-desktop.png).

## Included

- Accessible five-item public navigation: Home, Events, Discounts, Local Info and Contact.
- Responsive high-fidelity home page with community highlights and upcoming events.
- Calendar and detailed event views with category filters.
- Member registration, login, password reset, profile, RSVP/attendance history and notification preferences.
- Senior-discount filters with eligibility and claim instructions.
- Local-service directory and contact form.
- Administrator dashboard with event, discount, local-service and contact-inbox management.
- Session-only demonstration mode when MySQL is not configured.
- Shared MySQL schema compatible with the BKK Android/API project, plus sample content for persistent mode.
- CSRF protection, secure session cookies, session-level abuse throttling and strict response security headers.

## Requirements

- PHP 8.2 or newer with PDO MySQL and mbstring.
- MySQL 8 or newer for persistent production data.
- Vendored Bootstrap 5.3.8 plus the BKK design system; no CDN dependency.
- A modern browser. No Node.js runtime is required.

## Run locally

```bash
cd /path/to/BKKCommunity-Web
php -S 127.0.0.1:8080 -t public
```

Open <http://127.0.0.1:8080>. Without a `.env` file containing database credentials, the application clearly identifies itself as a session-only demo.

Demo member: `member@bkk.demo` / `MemberDemo!26`

Demo administrator: `admin@bkk.demo` / `AdminDemo!26`

## Configure MySQL

1. Create a database user with access only to the BKK database.
2. For a new database, run `database/schema.sql`, followed by `database/seed.sql` if sample content is required. For an existing BKK Android/API database, run only the files in `database/migrations/` in numeric order.
3. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`.
4. Create an administrator securely from the command line:

```bash
./bin/create-admin.php
```

The command promotes an existing member or creates a new administrator without placing a password in shell history or Git.

Never commit `.env`, database passwords, SMTP credentials or hosting credentials.

## Verify

```bash
find app public -type f -name '*.php' -print0 | xargs -0 -n1 php -l
./tests/smoke.sh
BKK_BASE_URL=http://127.0.0.1:8080 ./tests/database-integration.sh
BKK_BASE_URL=http://127.0.0.1:8080 ./tests/admin-database-integration.sh
```

The checked-in milestone was also exercised in headless Chrome at 1440px, 390px and 320px. Public-page Axe scans returned zero violations, mobile navigation and mouse-wheel scrolling worked, 320px pages had no horizontal overflow, guest admin access was blocked, and administrator event creation completed successfully. These automated checks do not replace real TalkBack/VoiceOver or elderly-user UAT.

## Production blockers

The core website is database-backed and its member/admin workflows have automated integration coverage. It is not a permanent production deployment until the following external services and operational work are completed:

- Replace all demonstration events, offers and service details with stakeholder-verified information.
- Configure and test transactional email delivery for password-reset messages. Saving contact messages works without email because administrators can read them in the protected inbox.
- Deploy PHP and MySQL to permanent hosting with a domain; a development server or quick tunnel is not an uptime solution.
- Serve only over HTTPS and configure secure headers at the hosting layer.
- Add host-level/IP rate limiting, bot protection, automated encrypted backups and uptime/error monitoring.
- Wire a scheduled delivery provider for actual event-reminder and discount-alert messages; the preference data is already persisted.
- Complete real browser, screen-reader, 200% text-scale and elderly-user acceptance testing.
- Replace sample BKK content only after stakeholders verify the names, times, addresses and offers.
