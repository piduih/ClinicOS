# ClinicOS Product & Technical Roadmap

## Product direction

**Target:** Single-clinic first, then multi-clinic SaaS  
**Initial clinic type:** General Practice (GP)  
**Scope:** Full clinic operations, clinical records, billing, pharmacy, reporting and SaaS administration.

The product must deliver value to one GP clinic before SaaS commercialization. However, tenant boundaries must be designed before adding more clinic workflows. Do not treat the first deployment as a single-tenant schema that will later be retrofitted.

## Current baseline

Repository baseline: v0.6.0 development foundation.

Existing areas documented in the repository:
- Authentication and session protections
- Administrator, doctor and receptionist roles
- Patient directory and profile management
- Appointment scheduling and status management
- User administration
- Audit log foundation

Known gaps stated by the project:
- No completed live PHP/MySQL integration test
- Login throttling and password reset remain outstanding
- Appointment duration and overlap handling remain outstanding
- Backup/restore and deployment hardening remain outstanding

Do not use with real patient data until security, privacy, clinical workflow and operational testing have been completed.

## Release roadmap

| Release | Workstream | Scope | Exit criteria |
|---|---|---|---|
| v0.7.x | Architecture and tenant foundation | Clinics, clinic memberships, active clinic context, tenant-scoped access, migrations | Automated tests prove one clinic cannot read or modify another clinic's data |
| v0.8.x | Clinic operations | Check-in, queue, queue status, doctor assignment, waiting-room workflow | Reception-to-consultation workflow passes end-to-end tests |
| v0.9.x | Consultation | Consultation session, vital signs, clinical notes, diagnosis and treatment records | Clinical records are permission-controlled and linked to the correct patient and clinic |
| v1.0.x | Clinical management | Medical history, allergies, diagnosis catalogue, prescriptions, printable clinical documents | Clinical workflow and audit requirements pass review |
| v1.1.x | Billing and payments | Service catalogue, invoices, payments, partial payments, void/refund controls, collection report | Financial totals reconcile and financial actions are auditable |
| v1.2.x | Pharmacy | Medicine catalogue, suppliers, stock movements, batches, expiry, dispensing | Stock ledger reconciles with dispensing and adjustments |
| v1.3.x | Inventory and purchasing | Purchase orders, goods receiving, stock alerts, supplier balances | Inventory workflows pass reconciliation tests |
| v1.4.x | Reporting | Operational, patient, doctor, revenue, expense and stock reports | Reports reconcile against source transactions |
| v1.5.x | Production readiness | Backup/restore, monitoring, security hardening, retention controls, privacy review | Restore drill and production-readiness checklist pass |
| v2.0.x | SaaS expansion | Clinic onboarding, plans, subscription lifecycle, usage limits, platform administration, payment gateway | Tenant isolation, subscription and billing tests pass |

Versions are planning targets, not promises of delivery dates.

## Development sequence

1. Inspect and document the existing codebase and schema.
2. Agree the tenant model and migration strategy.
3. Add clinic and membership schema using versioned migrations.
4. Implement active clinic context and server-side tenant authorization.
5. Migrate patient, appointment and audit data safely.
6. Add automated tenant-isolation tests.
7. Only then start queue and consultation workflows.

## GitHub workflow

- Keep `main` deployable and protected by review.
- Use one short-lived `feature/*` branch per issue.
- Open a pull request for each feature or cohesive change.
- Include migration, authorization and tests in the same change when relevant.
- Never commit credentials, secrets or real patient information.
- Do not merge a schema change without an upgrade path for existing installations.

## Definition of Done

A feature is complete only when:
- Acceptance criteria are met.
- Server-side authorization is enforced.
- Tenant scoping is applied to reads and writes.
- Input validation and error handling are present.
- Database migrations are reversible or have a documented recovery plan.
- Relevant automated tests pass.
- Documentation and audit events are updated where needed.
- No real patient data or secrets are present in fixtures, logs or commits.
