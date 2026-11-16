# Patient module

## Included
- Patient directory with search by name, medical record number or phone.
- New patient registration.
- Patient profile view.
- Patient details editing.
- Generated medical record number in the format `CL-YYYYMMDD-XXXXXX`.
- Prepared statements for database operations and HTML output escaping.
- CSRF verification for form submissions.

## Access
Any authenticated role can currently view and maintain patient demographic records. This is a temporary baseline policy. Before real deployment, define separate permissions for reception, clinical staff and administrators.

## Data handling
The current notes field is labelled for administrative notes only. Do not store diagnoses, prescriptions, examination findings or other sensitive clinical records here. Clinical records require separate authorization, audit trails, retention policies and appropriate security controls.

## Current limitations
- Directory displays up to 100 most recent records; search can find other records.
- No patient deletion or archival action is provided.
- No duplicate-patient detection workflow.
- No audit log yet.
- No automated tests or live database integration test has been run.
- This module is a development foundation, not ready for real patient data.
