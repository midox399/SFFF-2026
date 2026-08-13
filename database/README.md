# Database — what to import and why

There are **two** SQL files in this project. Only one of them should actually
be imported right now.

## `database/schema.sql` — import this one

3 tables: `admins`, `passport_reservations`, `applications`. This is the
entire backend for the current scope: storing passport reservation
submissions and Espace Professionnels applications, plus a private admin
login to view/manage them.

## `sfff2026_database.sql` (repo root) — do NOT import this yet

This is a separate, much larger schema (23 tables + 3 views) that models the
*entire* festival as dynamic content — pavilions, nights, zones, sponsors,
documents, a full order system with RFID/payment tracking, etc. It was
written as a forward-looking "production" schema, but it doesn't match the
current architecture decision:

- All festival content (pavilions, nights, zones, senses, Future Food Lab,
  documents, map data...) is intentionally **hardcoded in `index.html`** and
  stays that way. There is no CMS and none is planned right now.
- Its `passport_orders` table foreign-keys into `festivals`, `ticket_types`,
  and `users` — none of which exist in the minimal backend, and creating
  them would mean building the CMS this project is explicitly avoiding.
- Its `pro_applications` table uses a profile `ENUM('Investisseur',
  'Exposant','Sponsor','Ambassade','Distributeur','Presse')` that doesn't
  match the real frontend tabs (`Investisseur, Sponsor, Exposant / Kiosque,
  Ambassade, Média & Presse, Visiteur` — see `index.html`'s `pro.tabs`
  arrays). It would need to be rebuilt anyway.
- It also carries payment/RFID/stamp-tracking fields that are explicitly out
  of scope right now (no payment gateway is implemented).

**Nothing in `sfff2026_database.sql` was deleted or modified.** It's left in
place in case the project later grows into the full dynamic system it
describes — at that point it would need a proper review/rebuild against
whatever the frontend actually looks like then, not a blind import.

## Setup (phpMyAdmin)

1. In hPanel / phpMyAdmin, create a new database (e.g. `sfff2026`) if one
   doesn't already exist, and a MySQL user with full privileges on it. Note
   the database name, username, password, and host (usually `localhost` on
   shared hosting).
2. Open phpMyAdmin, select that database, go to the **Import** tab, choose
   `database/schema.sql`, click **Go**. This creates the 3 tables.
3. Copy `config/database.example.php` to `config/database.php` and fill in
   the real `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` values from
   step 1. **Never commit `config/database.php`** — it's already listed in
   `.gitignore`.
4. Create the first admin account by running, from a terminal with PHP CLI
   access on the server (or locally if you have PHP installed):
   ```
   php scripts/create_admin.php you@example.com "a-strong-password"
   ```
   This hashes the password with `password_hash()` and inserts the row —
   nothing is ever stored in plaintext.
5. Visit `/admin/login.php` on your domain and log in with that email/password.
6. The public site doesn't need anything else — the passport modal and the
   Espace Professionnels form on the homepage now POST to
   `/api/passport-reservation.php` and `/api/application.php` respectively.

## Running locally

If you want to test locally before deploying, PHP's built-in server works
fine for this (no need for a full Apache/nginx setup):
```
php -S localhost:8000
```
then open `http://localhost:8000/index.html`. You'll still need a real
MySQL/MariaDB server reachable from `config/database.php` (e.g. a local
XAMPP/Laragon MySQL instance, or point it at a remote dev database).
