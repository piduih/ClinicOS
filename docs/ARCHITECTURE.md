# ClinicOS Technical Architecture

## 1. Architecture decisions

| Concern | Initial decision |
|---|---|
| Product path | Single GP clinic first, SaaS-ready foundations |
| Backend | PHP 8.3+, modular application structure |
| Database | MySQL 8+, InnoDB, utf8mb4 |
| Data access | PDO with parameterized queries |
| Frontend | Bootstrap 5.3 and vanilla JavaScript |
| Web server | Apache or Nginx on Linux |
| Deployment | Linux VPS with HTTPS, monitored backups |
| API | Introduce versioned HTTP endpoints when a real client/integration requires them |

Keep the current stack. Do not introduce a framework migration as part of the tenant-foundation work unless a separate technical decision demonstrates a concrete need.

## 2. Tenant model

Use a shared-database, shared-schema model with a mandatory tenant boundary for clinic-owned records.

### Core identity model

- `clinics`: one row per clinic/tenant.
- `users`: global login identity.
- `clinic_users`: membership linking a user to a clinic, with a clinic-specific role and active state.
- A user may belong to multiple clinics.
- A user's permissions are evaluated against the selected clinic membership, not only a global role.

The existing global `users.role` field is part of the v0.6 baseline. Migration must preserve existing accounts and map their current role into a membership for the initial clinic. Do not remove or repurpose the column until compatibility and rollback are planned.

### Tenant-owned data

At minimum, the following records must be associated with a clinic:
- Patients and patient identifiers
- Appointments and queue entries
- Consultations, vital signs and clinical records
- Diagnoses, treatment plans and prescriptions
- Invoices, payments, refunds and service catalogue entries
- Medicines, stock batches, stock movements and suppliers
- Clinic-specific settings and operational records
- Audit events generated within a clinic context

Add `clinic_id` to tenant-owned tables and index common tenant access patterns, for example `(clinic_id, created_at)`, `(clinic_id, status)` and tenant-scoped unique keys.

## 3. Authorization and isolation

Every request that accesses clinic data must:
1. Authenticate the user.
2. Resolve the active clinic from a server-validated membership.
3. Verify the membership is active.
4. Authorize the requested action for the user's role/permissions in that clinic.
5. Apply clinic scope to every database read, update and delete.
6. Verify related records belong to the same clinic before linking them.

Never trust a submitted `clinic_id`, patient ID, appointment ID or doctor ID without checking membership and tenant ownership. Hiding links in the UI is not authorization.

Use deny-by-default authorization. Add tests for cross-tenant IDOR attempts, including direct URL access, crafted form submissions, exports and background jobs.

## 4. Data integrity

- Use InnoDB foreign keys for relational integrity.
- Use transactions for multi-table business operations.
- Enforce tenant-scoped uniqueness, such as patient medical record number within a clinic, rather than assuming identifiers are globally unique.
- Keep immutable financial and stock movement records; correct errors through controlled reversal/adjustment entries.
- Store timestamps consistently and define the clinic's display timezone explicitly.
- Avoid hard deletes for clinical and financial records; define retention and correction workflows with appropriate review.

Where MySQL cannot express a cross-table tenant constraint directly, enforce it in the service layer and verify it with integration tests.

## 5. Audit and privacy

Audit events should record the actor, clinic context, event, entity reference and timestamp, with operational metadata only where justified.

Do not place passwords, session tokens, patient names, contact information, clinical notes, diagnoses or submitted form payloads in audit metadata or application logs.

Define access, retention, backup encryption, restore access and incident response before production use. Audit logs in the current foundation are best-effort and are not tamper-proof; strengthen critical audit writes and off-host retention before production.

## 6. Suggested application boundaries

- Auth: authentication, sessions and password lifecycle
- Clinics: clinic profile and tenant context
- Access: memberships, roles and permissions
- Patients: demographic and registration records
- Appointments: scheduling and appointment lifecycle
- Queue: check-in and waiting-room workflow
- Consultation: encounter workflow
- Clinical: history, vitals, diagnoses, notes and prescriptions
- Billing: invoices, payments, refunds and receipts
- Pharmacy: medicines, dispensing and inventory ledger
- Reports: operational and financial reporting
- Audit: security and business audit trail

Keep business rules in application services rather than duplicating them across page handlers.

## 7. Migration strategy from v0.6

Use additive, versioned migrations. Do not replace the live schema in one destructive operation.

1. Back up the database and verify a restore.
2. Create the initial clinic record.
3. Create clinic membership records for existing users, mapping current roles.
4. Add nullable `clinic_id` columns to existing tenant-owned tables.
5. Backfill all existing patients, appointments and clinic-context audit events to the initial clinic.
6. Verify there are no orphaned or unassigned tenant records.
7. Add indexes and tenant-scoped unique constraints after data validation.
8. Deploy code that resolves and enforces active clinic context.
9. Make tenant ownership mandatory after all rows are backfilled.
10. Retain a documented rollback path and test on a copy of production-like data.

Do not run these steps against production until migrations have been tested against a backup copy.

## 8. Testing requirements

- Unit tests for permission decisions and tenant context.
- Integration tests for tenant-scoped CRUD.
- Cross-tenant denial tests for every tenant-owned module.
- Migration tests from the current v0.6 schema.
- Transaction and rollback tests for financial and inventory operations.
- Backup restoration drills.
- Security tests for authentication, session handling, CSRF, IDOR and privilege escalation.

## 9. Deployment boundaries

- Web document root must point to `public/`.
- Keep configuration and secrets outside the public directory.
- Use HTTPS and secure cookie/session settings.
- Use separate least-privilege database credentials for runtime and migrations where practical.
- Restrict database/network access and administrative endpoints.
- Keep backups encrypted and off-host, with monitored restore tests.
- Do not use real patient data until security, privacy, legal and operational reviews are complete.
