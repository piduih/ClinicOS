# ClinicOS

ClinicOS is a web-based clinic management system.

## Version
0.1.0 - foundation

## Technology
- PHP 8.3+
- MySQL 8+
- Bootstrap 5.3
- Vanilla JavaScript (ES6+)

## Initial scope
- Authentication and role-based access foundation
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
3. Copy `config/database.example.php` to `config/database.php`.
4. Set the database host, name, username and password in `config/database.php`.
5. Point the web server document root to `public/`.

Do not commit real credentials or production secrets.

## Current status
This repository contains the initial project foundation and database schema. It is not yet a production-ready clinic system. Authentication workflows, authorization policies, patient CRUD screens, audit logging, backups and deployment hardening remain to be implemented and tested.

## License
To be decided.
