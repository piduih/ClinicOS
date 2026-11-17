# ClinicOS Multi-Tenancy Design

## Decision

ClinicOS will support a single-clinic pilot first while adopting a shared-database, shared-schema tenant model from the beginning.

## Entity relationships

```text
clinics 1---many clinic_users many---1 users

clinics 1---many patients
clinics 1---many appointments
clinics 1---many queue_entries
clinics 1---many consultations
clinics 1---many invoices
clinics 1---many medicines
clinics 1---many audit_logs
```

The diagram is conceptual. Exact foreign keys and module tables will be introduced through reviewed migrations.

## Membership and roles

A user is a global identity. Access to a clinic requires an active row in `clinic_users`. The role belongs to that membership, allowing the same person to be a doctor in one clinic and an administrator in another, subject to policy.

Initial roles: administrator, doctor and receptionist. Permission checks must be server-side and clinic-scoped. Avoid relying on a single global role for authorization.

## Tenant isolation rules

- Resolve active clinic from authenticated server-side membership.
- Never accept a client-supplied clinic ID as proof of access.
- Scope every query and mutation by clinic ID.
- Validate that related patient, appointment, doctor, invoice and stock records belong to the same clinic.
- Apply the same controls to exports, search, reports, background jobs and API endpoints.
- Use tenant-scoped unique identifiers where appropriate.
- Add automated cross-tenant denial tests before onboarding a second clinic.

## Existing data migration

For the current v0.6 database:
1. Back up and restore-test a copy.
2. Create the first clinic.
3. Create a clinic membership for every existing user using their current role.
4. Add clinic ownership columns to tenant-owned tables.
5. Assign existing rows to the first clinic.
6. Validate counts, relationships and unassigned rows.
7. Add tenant-scoped constraints and indexes.
8. Deploy tenant-aware application code.
9. Make clinic ownership mandatory only after validation.

Do not delete or repurpose `users.role` in the same migration that introduces memberships. Use a compatibility period and a separately reviewed cleanup migration.

## Audit considerations

Add clinic context to audit events when the event occurs inside a clinic. Authentication events that occur before clinic selection may legitimately have no clinic context. Do not store patient or clinical content in audit metadata.

## Acceptance criteria

- A user without an active clinic membership cannot access clinic data.
- A member of Clinic A cannot retrieve or change Clinic B records by guessing IDs.
- Cross-clinic relationships are rejected.
- Existing v0.6 users and records are preserved in the first clinic.
- Migration can be rehearsed on a database copy and has a documented recovery plan.
