# Audit Log & Security

## Setup
Import the updated `database/schema.sql` into the ClinicOS database. For an existing installation, run only the `CREATE TABLE audit_logs` statement from the schema. The table uses InnoDB and retains an actor reference with `ON DELETE SET NULL`.

## Current coverage
- Successful and failed login attempts
- Logout
- User creation, update, activation and deactivation
- Administrator-only, read-only audit viewer with event/date filters and 50-row pagination

The logger also defines event names for patient and appointment actions; those workflows are being expanded in subsequent iterations.

## Data minimisation
Audit rows contain actor ID, event name, entity type/ID, remote IP, user-agent and timestamp. Passwords, session tokens, patient names, contact details, appointment reasons, notes, diagnoses and submitted form values must never be written to audit metadata.

Failed login entries deliberately do not store the submitted email address. IP address and user-agent are operational metadata and still constitute personal data; define retention and access policies before production use.

## Operational limitations
Audit writes are best-effort in this foundation: an audit database error is sent to the server error log and does not roll back the underlying operation. This is not tamper-proof logging. Production deployment should add restricted database permissions, central/off-host log retention, monitoring, retention policy and transactional audit guarantees for critical writes.

The viewer is restricted to administrators and does not expose edit/delete controls. Do not use this development foundation with real patient data before security, privacy and regulatory review.
