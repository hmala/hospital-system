---
name: hospital-memory
description: Persistent memory, architectural map, and standards guide for the hospital management system. Use to quickly access domain controllers, models, database tables, and current project state without scanning or rescanning files.
---

# Hospital System Memory & Architectural Reference Skill

Use this skill to instantly understand the structure, architecture, and current status of the Hospital Management System without needing to perform broad file scans.

## Core Reference Files
- **Primary Architecture & Status**: `hospital-memory.md` (in workspace root)
- **Agent Operating Rules**: `.agents/rules/hospital-core.md` (automatically injected into prompt)
- **Session History & Decisions**: `AGENTS.md` (recent sessions) & `docs/SESSION_ARCHIVE.md` (historical archive)
- **Code Graph & Relationships**: `graphify-out/wiki/index.md` and `graphify-out/graph.json`

## Domain Quick Index

| Domain | Key Controller | Key Model(s) | Views Directory |
| :--- | :--- | :--- | :--- |
| **Cashier & Payments** | `CashierController.php` | `Payment.php`, `Patient.php` | `resources/views/cashier/` |
| **Treasury & Finance** | `HospitalTreasuryController.php` | `Payment.php`, `DoctorVisit.php` | `resources/views/treasury/` |
| **Outpatient Clinics** | `DoctorVisitController.php`, `DoctorController.php` | `DoctorVisit.php`, `Appointment.php` | `resources/views/doctors/` |
| **Appointments & Queue**| `AppointmentController.php`, `DoctorQueueController.php` | `Appointment.php` | `resources/views/appointments/`, `queue/` |
| **Radiology & Ultrasound**| `RadiologyStaffController.php`, `RadiologyController.php` | `RadiologyRequest.php`, `RadiologyType.php` | `resources/views/radiology-staff/`, `radiology/` |
| **Laboratory** | `LabStaffController.php`, `LabTestController.php` | `LabRequest.php`, `LabTest.php` | `resources/views/lab/`, `lab-tests/` |
| **Emergency Station** | `EmergencyController.php` | `EmergencyPatient.php`, `EmergencyService.php` | `resources/views/emergency/` |
| **Inpatient & Beds** | `BedReservationController.php`, `RoomController.php` | `BedReservation.php`, `Room.php` | `resources/views/bed_reservations/`, `rooms/` |
| **Incubators (الخدج)** | `IncubatorReservationController.php` | `Incubator.php`, `IncubatorReservation.php` | `resources/views/incubators/` |
| **Surgeries & Operating**| `SurgeryController.php`, `SurgeonStationController.php` | `Surgery.php`, `SurgicalOperation.php` | `resources/views/surgeries/`, `surgery-stations/` |
| **Pharmacy & Store** | `InventoryController.php`, `ProductController.php` | `Product.php`, `StockTransfer.php` | `resources/views/inventory/`, `products/` |
| **Activity Logs & Radar**| `UserActivityController.php` | `UserActivityLog.php` | `resources/views/activity-logs/` |
| **Hostinger DB Sync** | `DatabaseSyncController.php` (`db:pull-hostinger`) | `DatabaseSyncService.php` | `resources/views/system/database-sync.blade.php` |

## Non-Negotiable Coding Rules
1. **Never use hardcoded `/` root links in JavaScript/Blade**: Always use `{{ url('/') }}` or `{{ route(...) }}` to ensure compatibility with Hostinger and local subdirectories.
2. **Push directly to `main` branch only**: Never create side branches.
3. **Doctor appointment fee isolation**: Cancelling an appointment refunds the appointment fee only. Never cancel or overwrite paid lab/radiology request payments.
4. **Currency**: All amounts are in Iraqi Dinar (IQD).

## Start & End of Session Routine
- **Start**: Read `hospital-memory.md` line 1-100 to check `Current Project State`.
- **End**: Update `Current Project State` in `hospital-memory.md` and log key changes in `AGENTS.md`.
