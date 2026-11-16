# Appointment module

## Included
- Appointment listing with date and status filters.
- Create and edit appointments.
- Select an existing patient.
- Assign an active user with the doctor role, or leave doctor unassigned.
- Update appointment status: scheduled, checked_in, completed, cancelled, no_show.
- CSRF verification and prepared SQL statements.
- Duplicate start-time check for the same doctor (or unassigned slot).

## Important scheduling limitation
The database currently stores a start date/time only. No appointment duration, clinic opening hours, doctor working schedule, holidays or room/resource allocation have been defined. The current duplicate check only blocks an identical start time for the same doctor; it does not detect overlapping appointment durations.

## Access policy
All authenticated roles can currently view, create and edit appointment records. Status changes are also available to authenticated users. A finer-grained policy should be agreed before production use.

## Not production-ready
No reminders, calendar integration, queue management, audit log, automated tests or concurrency-safe slot reservation have been implemented. Do not use this module for live patient operations until it has been tested and reviewed.
