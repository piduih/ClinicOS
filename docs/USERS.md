# User administration

## Included
- Administrator-only user directory.
- Create accounts with administrator, doctor or receptionist roles.
- Edit name, email, role and active status.
- Set or replace a password without displaying the existing password.
- Passwords are hashed using PHP `password_hash()`.
- CSRF checks on state-changing forms.
- Prevents an administrator from deactivating their own account.
- Prevents deactivation or demotion of the last active administrator.

## Important
User creation and role changes are restricted to active administrators through server-side authorization. This module does not send invitation emails or password-reset links. Passwords are set by an administrator and should be delivered to the account holder through a secure, separate channel, then changed by the user after first login (self-service password change is not yet implemented).

## Not production-ready
No audit log, email verification, login throttling, password reset workflow or automated tests have been implemented. Review authorization policies and test on a non-production database before use.
