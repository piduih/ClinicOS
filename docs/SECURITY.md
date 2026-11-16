# Security notes

## Authentication foundation
- Passwords are stored using PHP `password_hash()` and checked using `password_verify()`.
- Session IDs are regenerated after successful login.
- Session cookies use HttpOnly and SameSite=Lax; Secure is enabled when HTTPS is detected.
- Login errors do not disclose whether an email address exists.
- State-changing logout requests require a CSRF token.
- Role checks are performed server-side through `Auth::requireRole()`.

## Create the first administrator
1. Configure `config/database.php`.
2. Import `database/schema.sql`.
3. Run from the project root: `php scripts/create-admin.php`.
4. Use a unique password of at least 12 characters.

The script is CLI-only. Do not expose setup scripts through the web server.

## Before production
- Enforce HTTPS at the web server or reverse proxy.
- Add login throttling and account lockout policy.
- Add audit logs for access to and changes in patient records.
- Define data retention, backup, restore and breach-response procedures.
- Restrict database privileges to the minimum required.
- Keep secrets outside the public document root.
- Configure a restrictive Content Security Policy compatible with deployed assets.
- Review Malaysian healthcare, personal data and professional obligations before processing real patient data.

This initial implementation is a development foundation, not a compliance certification or production-ready medical records platform.
