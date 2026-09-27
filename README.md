# Barangay Mintal Information System

A PHP and MySQL web application for managing barangay records and providing residents with an online information and request portal. The project includes an administrator dashboard and a separate resident-facing site.

> **Project naming note:** Some pages in the source currently display “Barangay San Jose.” Update those labels if the intended deployment is for Barangay Mintal.

## Features

- Administrator login and dashboard summaries
- Resident and household record management
- Barangay official directory
- Certificate requests and certificate record management
- Blotter record management
- Announcements and reports
- Resident registration, login, profile, and request pages
- Archive and status controls in selected record modules

## Technology

- PHP
- MySQL or MariaDB
- PDO with the MySQL driver
- HTML, CSS, and JavaScript
- Apache through XAMPP, or PHP's built-in development server

## Requirements

- PHP 8 or later with `pdo_mysql` enabled
- MySQL or MariaDB
- Apache (for example, XAMPP) or the PHP CLI server
- A database schema containing the tables used by the application

## Database setup

The application reads these optional environment variables in `BarangaySystem/app/config/database.php`:

| Variable | Default |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `barangay_system_new` |
| `DB_USER` | `root` |
| `DB_PASS` | empty |

Create the database in MySQL or phpMyAdmin. For the defaults, the command is:

```sql
CREATE DATABASE barangay_system_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Important:** This repository does not contain a `.sql` database export. The PHP code creates or updates the `site_users`, `admin_users`, and `officials` tables, and the announcements page creates its own table. The main record pages also expect tables such as `residents`, `households`, `certificates`, and `blotter_records`; obtain and import the matching schema or database backup from the project team before using those modules. A newly created empty database alone is not enough to run the complete system.

For a local machine, the defaults work with a standard XAMPP MySQL installation if its `root` account has no password. Otherwise configure the `DB_*` environment variables for your local database. Do not commit real database passwords to GitHub.

## Run locally with XAMPP

1. Install and start Apache and MySQL in XAMPP.
2. Copy the repository's `BarangaySystem` folder into XAMPP's `htdocs` directory. Keep the folder name `BarangaySystem`, because the current PHP pages use that URL prefix.
3. Create the database and import the required SQL schema or backup as described above.
4. Open the administrator login at [`http://localhost/BarangaySystem/public/adminlogin.php`](http://localhost/BarangaySystem/public/adminlogin.php).
5. Open the resident registration page at [`http://localhost/BarangaySystem/public/user/auth/signup.php`](http://localhost/BarangaySystem/public/user/auth/signup.php).

The source seeds a demo administrator account when the `admin_users` table is first initialized: username `admin`, password `admin123`. Use it only for local evaluation and change or remove it before making the application accessible to others.

## Run with PHP's built-in server

From the directory containing the `BarangaySystem` folder, run:

```bash
php -S 127.0.0.1:8000
```

Then visit:

- Admin: <http://127.0.0.1:8000/BarangaySystem/public/adminlogin.php>
- Resident registration: <http://127.0.0.1:8000/BarangaySystem/public/user/auth/signup.php>

The built-in server is for local development only. PHP and the required MySQL schema still need to be configured.

## Project structure

```text
BarangaySystem/
├── app/
│   ├── config/       # Database connection and shared configuration
│   ├── helpers/      # Shared helper functions
│   └── middleware/   # Administrator authentication checks
├── assets/           # CSS, JavaScript, and images
├── models/           # Data access models
├── public/           # Login pages, dashboard, modules, and resident site
└── views/            # Shared page layouts
```

## Notes

- The application is a PHP/MySQL project; `npm install` and `npm run dev` do not apply.
- The current pages use `/BarangaySystem/...` paths, so keep that folder name and URL prefix unless you also update the links throughout the PHP files.
- Use sample data while demonstrating the project. Do not put real resident personal information in a public repository or an unsecured demo deployment.
