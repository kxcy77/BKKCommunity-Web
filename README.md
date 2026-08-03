# BKK Community Web App

A responsive PHP web application for BKK Community Group, based on the documented Phase 2 high-fidelity website design and Phase 3 authentication/admin screens.

![BKK Community web app desktop home screen](docs/screenshots/home-desktop.png)

Responsive evidence is also available in [the mobile home capture](docs/screenshots/home-mobile.png) and [the desktop events capture](docs/screenshots/events-desktop.png).

## Included

- Accessible five-item public navigation: Home, Events, Discounts, Local Info and Contact.
- Responsive high-fidelity home page with community highlights and upcoming events.
- Calendar and detailed event views with category filters.
- Member registration, login, profile, RSVP history and notification preferences.
- Senior-discount filters with eligibility and claim instructions.
- Local-service directory and contact form.
- Administrator dashboard with event and discount management.
- Session-only demonstration mode when MySQL is not configured.
- MySQL 8 schema and sample data for persistent mode.

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
2. Run `database/schema.sql`, followed by `database/seed.sql` if sample content is required.
3. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`.
4. Change or remove every demo account before deployment.

Never commit `.env`, database passwords, SMTP credentials or hosting credentials.

## Verify

```bash
find app public -type f -name '*.php' -print0 | xargs -0 -n1 php -l
./tests/smoke.sh
```

The checked-in milestone was also exercised in headless Chrome at 1440px, 390px and 320px. Public-page Axe scans returned zero violations, mobile navigation and mouse-wheel scrolling worked, 320px pages had no horizontal overflow, guest admin access was blocked, and administrator event creation completed successfully. These automated checks do not replace real TalkBack/VoiceOver or elderly-user UAT.

## Production blockers

This repository is a working application prototype, not a production deployment. Before launch:

- Replace all demonstration events, offers and service details with stakeholder-verified information.
- Configure transactional email for password resets and contact notifications.
- Serve only over HTTPS and configure secure headers at the hosting layer.
- Replace the sample user accounts and passwords.
- Complete real browser, screen-reader, 200% text-scale and elderly-user acceptance testing.
- Add backup, audit, rate-limiting and operational monitoring procedures.
