# ClinicOS

ClinicOS is a web-based clinic management system.

## Version
0.2.0 - authentication foundation

## Technology
- PHP 8.3+
- MySQL 8+
- Bootstrap 5.3
- Vanilla JavaScript (ES6+)

## Initial scope
- Login/logout with session regeneration and CSRF protection\n- Server-side role guard for administrator, doctor and receptionist\n- CLI-only first administrator setup
- Dashboard foundation
- Patient records
- Relational database schema

## Planned roles
- Administrator
- Doctor
- Receptionist

Role permissions must be enforced on the server, not only hidden in the interface.

## Requirements
- PHP 8.3 or later
- MySQL 8.0 or later
- PDO MySQL extension
- Web server with PHP support (Apache or Nginx)

## Setup
1. Create a MySQL database.
2. Import `database/schema.sql`.
3. Copy `config/database.example.php` to `config/database.php`.\n4. Create the first administrator from the project root: `php scripts/create-admin.php`.\n5. Open the application through the `public/` document root and sign in.
4. Set the database host, name, username and password in `config/database.php`.
5. Point the web server document root to `public/`.

Do not commit real credentials or production secrets.

## Current status
This repository contains the initial database and authentication foundation. Patient and appointment CRUD, user administration, audit logging, login throttling, backup/restore and deployment hardening remain to be implemented and tested. It is not production-ready.

## License
To be decided.
