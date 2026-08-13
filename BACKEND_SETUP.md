# Backend Setup — Passport Reservations & Applications

This is the minimal PHP + MySQL backend for two of the site's forms:
"Billetterie" (passport reservations) and "Espace Professionnels"
(applications), plus a payment layer for the reservations. Everything else
on the site remains static/hardcoded — see `database/README.md` for why the
larger `sfff2026_database.sql` schema is *not* used here.

**Payment gateway status: stubbed, not live.** The reservation → checkout →
webhook → "paid" → ticket flow works end-to-end today against a local
simulator (`payment-stub-checkout.php`) so the wiring can be tested before a
real provider is chosen. See "Wiring up a real payment provider" below for
exactly what to change once you have Konnect/Paymee API keys — it's a
contained change (one file, `includes/payment_provider.php`), not a rewrite.

Once a reservation is paid, the customer lands on `ticket.php?ref=<reference>`
— a printable/screenshot-able ticket showing their name, passport type,
amount paid, and a QR code encoding the reference. This page only shows
anything for reservations that are actually `payment_status='paid'` — the
reference itself is a random, unguessable code, so the page is safe to be
reachable without a login. **This QR code is a payment receipt, not an
access-control credential** — staff still hand over the real physical
passport at pickup after confirming payment (by scanning or looking up the
reference in the admin panel).

**Door check-in** (`/admin/checkin.php`) is a separate, admin-only page for
staff working the gate: it opens the device's camera and scans the same QR
code to instantly confirm `payment_status='paid'` and pull up the
passport-holder's name/type. Every scan is logged to `checkin_log`
(append-only, not a single "used" flag) since this is a 15-night festival —
a paid passport is expected to be scanned on multiple different nights, not
blocked after the first entry. Works on a phone browser; falls back to
manual reference entry if the camera is unavailable or permission is denied.

The entire admin area (`/admin/*`) is responsive down to phone width — wide
tables scroll horizontally within their own container instead of breaking
the page layout, and the nav/toolbar/buttons reflow and get larger tap
targets under ~720px.

There is also no email sending yet (see "What's not included" below).

## 1. Create the database (phpMyAdmin)

1. In Hostinger's hPanel, create a MySQL database and a database user with
   full privileges on it (Databases → MySQL Databases). Note down the
   database name, username, password, and host — on most Hostinger shared
   plans the host is `localhost`.
2. Open phpMyAdmin (hPanel → Databases → phpMyAdmin), select the database
   you just created.
3. Go to the **Import** tab, choose the file `database/schema.sql` from this
   project, click **Go**.
4. You should now see 5 tables: `admins`, `passport_reservations`,
   `applications`, `payment_events`, `checkin_log`.
5. **If you already imported `schema.sql` before payments/check-in were
   added**, import the migrations you're missing instead of re-running the
   full schema (they only add new columns/tables, existing data is safe):
   - `database/migrations/001_add_payments.sql` — if you only have 3 tables
   - `database/migrations/002_add_checkin.sql` — if you have 4 tables but no `checkin_log`

## 2. Configure database credentials

1. Copy `config/database.example.php` to `config/database.php`.
2. Edit `config/database.php` and fill in the real `DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASSWORD` from step 1.
3. Also set `PAYMENT_WEBHOOK_SECRET` to a real random value, e.g.:
   ```
   php -r "echo bin2hex(random_bytes(32));"
   ```
   This is required before the payment webhook will accept anything —
   `api/payment-webhook.php` rejects requests that don't send this exact
   value in an `X-Webhook-Secret` header (see "Wiring up a real payment
   provider" below). It's an interim protection; it gets replaced by real
   provider signature verification once a real gateway is wired up.
4. `config/database.php` is listed in `.gitignore` — it will not be
   committed. Never paste real credentials or the real webhook secret into
   `database.example.php` or any other tracked file.

## 3. Create the first admin account

Run this once, from a terminal with PHP CLI access on the server (SSH) — or
locally against a database you can reach, if you're testing before
deploying:

```
php scripts/create_admin.php you@example.com "a-strong-password-here"
```

- The password is hashed with `password_hash()` before it's stored — never
  entered or stored in plaintext anywhere.
- Running the same command again with the same email updates that admin's
  password instead of creating a duplicate — useful if you forget it later.
- There is intentionally no web-based "create admin" page. The only way to
  create one is this script, so a stray public endpoint can't be used to
  create rogue admin accounts.

## 4. Deploy

Upload the whole project (including the new `api/`, `admin/`, `includes/`,
`config/database.php`, `database/`, `scripts/` folders) to your Hostinger
hosting the same way you already deploy `index.html`. No build step is
needed for the PHP files — they run as-is on any standard PHP 8.2+ hosting.

Visit `https://www.globalvillagetunisia.com/admin/login.php` and log in
with the admin account from step 3.

## 5. Verify the public forms work

- Open the site, go to Billetterie, pick a passport, fill the checkout form,
  submit. You should land on the existing "Commande Confirmée !" success
  modal with a real reference like `GV-2026-8F42K` — and the row should
  appear in `/admin/passports.php`.
- Same for the "Espace Professionnels" form → check `/admin/applications.php`.

## Wiring up the real payment provider (Attijari Bank Tunisie)

The chosen provider is **Attijari Bank Tunisie**'s online payment / e-commerce
merchant solution. No merchant account has been opened yet, so right now
`includes/payment_provider.php` is still a stub: `payment_provider_init_checkout()`
sends the browser to a local page (`payment-stub-checkout.php`) that
simulates a successful payment by POSTing a fake webhook, and
`payment_provider_verify_webhook()` checks only an interim shared secret
(`PAYMENT_WEBHOOK_SECRET`), not a real bank signature.

**Before any of this can be wired up**, open an e-commerce/TPE (terminal de
paiement électronique) merchant account with Attijari — talk to their
business/enterprise banking team. Ask them for:
- Site/merchant ID (numéro de site)
- Secret key for MAC/HMAC signature generation and verification
- The hosted payment page URL (sandbox and production)
- Their integration kit/PDF documenting exact POST field names for amount,
  currency, order reference, return URL, notification (IPN) URL, and the
  signature algorithm they use on callbacks

Once you have that kit:

1. Store the site ID + secret key in `config/database.php` or a new
   gitignored `config/payment.php`.
2. In `includes/payment_provider.php`, replace `payment_provider_init_checkout()`
   with the redirect/form-POST to Attijari's hosted payment page per their
   kit — send the server-computed `amount_due` (never trust a client-sent
   amount), a success return URL, and a webhook/notification URL pointing at
   `api/payment-webhook.php`.
3. Replace `payment_provider_verify_webhook()` with Attijari's real MAC/
   signature verification, replacing (not layering onto) the interim
   `PAYMENT_WEBHOOK_SECRET` check. **Do not skip this** — the current
   shared-secret check only stops a random internet visitor from faking a
   payment; it does not verify the request actually came from the bank.
4. Delete `payment-stub-checkout.php` once the real integration is wired up —
   it only exists for local testing before a merchant account exists.
5. `api/payment-webhook.php` and the `payment_events` audit table don't need
   to change — they're already provider-agnostic, they just read whatever
   `payment_provider_verify_webhook()`/the payload give them.

## What's not included (by design, see the architecture brief)

- A *live* payment gateway — see above, the plumbing exists but needs real
  credentials and signature verification before it can be trusted.
- Email confirmations — can be added later (e.g. via PHPMailer + SMTP)
  without changing the database shape.
- RFID/passport-stamp tracking, sponsor/exhibitor/press management, CMS for
  static content — all out of scope for this backend.

## Local testing without deploying

If you want to try it locally first:
```
php -S localhost:8000
```
then open `http://localhost:8000/index.html`. You still need a real
MySQL/MariaDB server reachable from `config/database.php` — e.g. a local
XAMPP/Laragon MySQL instance is the easiest way to get one on Windows.
