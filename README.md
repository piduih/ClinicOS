# ClinicOS

ClinicOS is a web-based clinic management system.

## Version
0.6.0 - audit and security foundation

## Technology
- PHP 8.3+
- MySQL 8+
- Bootstrap 5.3
- Vanilla JavaScript (ES6+)

## Features in the current foundation
- Login and logout with session ID regeneration and CSRF protection
- Server-side role checks for administrator, doctor and receptionist
- CLI-only first administrator setup
- Dashboard with database-backed summary counts
- Patient directory, search, registration, profile and editing
- Appointment listing, date/status filters, scheduling and status updates
- Administrator-only user management: create, edit, activate and deactivate accounts
- Audit trail for authentication and user administration
- Administrator-only, read-only audit log viewer with date/event filters and pagination
- Relational database schema

## Roles
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
3. Copy `config/database.example.php` to `config/database.php`.
4. Set the database host, name, username and password in `config/database.php`.
5. Run `php scripts/create-admin.php` from the project root to create the first administrator.
6. Point the web server document root to `public/`.
7. Open the application and sign in.

Do not commit real credentials or production secrets.

## Current status
This repository contains a development foundation. Login throttling, self-service password reset, appointment duration and overlap handling, reminders, calendar integration, backup/restore and deployment hardening remain to be implemented and tested. No live PHP/MySQL integration test has been performed. Do not use with real patient data.

## Documentation
- `docs/SECURITY.md`
- `docs/PATIENTS.md`
- `docs/APPOINTMENTS.md`
- `docs/USERS.md`
- `docs/AUDIT-LOG.md`

## License
To be decided.
