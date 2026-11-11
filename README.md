# Vaccination Management System (PHP + MySQL)

Roles: Parent, Admin, Hospital. Workflow: parent books, admin approves or rejects, hospital marks Vaccinated / Not vaccinated, reports update.

## Setup (XAMPP / WAMP / LAMP)
1. Copy this folder to `htdocs/vms` (or `/var/www/html/vms`). Needs PHP 8.0+ with PDO MySQL.
2. Start Apache and MySQL. In phpMyAdmin choose Import and select `database.sql` (creates `vaccination_db`).
3. Edit `config/config.php` if your MySQL user or password is not `root` with an empty password.
4. Open `http://localhost/vms/`.

Default admin: `admin@vms.local` / `Admin@12345`. Change this password after the first login.
Parents and hospitals register themselves. The admin can also add hospitals, with or without a login.

## Design
Same look as the website preview: teal and coral palette, Bricolage Grotesque and Figtree fonts, cover page with the baby photo and tap-able immunization card, a How it works page, role pages with a Step 1/2/3 header, section tabs, live badges and a Next bar. Light and dark mode, remembered per browser. Styles are in `assets/app.css`, scripts in `assets/app.js`.

## Folders
- `index.php` cover page · `how.php` how it works
- `config/` settings · `includes/` database, auth, CSRF, validation, schedule logic, layout
- `admin/` dashboard, requests, children, vaccines, hospitals, bookings, reports (CSV)
- `parent/` dashboard, children, book, bookings, report, profile
- `hospital/` appointments and profile · `assets/` css and image · `database.sql` schema and seed data

## Tables
users, hospitals (optional user), children (parent), vaccines (age_weeks, available), appointments (status, admin_remark, result), vaccination_records.

## Security
Prepared statements everywhere, `password_hash`, session id regeneration on login, CSRF token on every POST, role check on every page, ownership checks (parents only touch their own children, hospitals only their own appointments), output escaping, login throttle, CSV formula-injection guard.
For production also: serve over HTTPS, set `DEBUG` to false, use a dedicated MySQL user instead of root.
