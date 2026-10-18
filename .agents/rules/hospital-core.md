# Hospital Management System - Core Rules & Blueprint (قواعد النظام والمعمارية الأساسية)

## 1. System Identity & Environment
- **Framework & Language**: Laravel 12 on PHP 8.2.
- **Frontend Stack**: Vite, Blade, Tailwind CSS, Bootstrap 5, Chart.js v4.
- **Database**: MySQL/MariaDB (`hospital_pho` locally, `remote_hostinger` for cloud sync).
- **Permissions**: `spatie/laravel-permission` (run `php artisan permission:cache-reset` on role changes).
- **Currency**: Iraqi Dinar (IQD) for all financial transactions and ledgers.

## 2. Instant Domain Blueprint (الخريطة السريعة للأنظمة)
Avoid running broad file scans (`grep_search` or `list_dir`). Target these files directly:

- **Cashier & Receipts**: `app/Http/Controllers/CashierController.php`, `app/Models/Payment.php`, `resources/views/cashier/`
- **Hospital Treasury & Analytics**: `app/Http/Controllers/HospitalTreasuryController.php`, `resources/views/treasury/index.blade.php`
- **Outpatient Visits & Doctor Station**: `app/Http/Controllers/DoctorVisitController.php`, `app/Models/DoctorVisit.php`, `resources/views/doctors/visits/`
- **Appointments & Queue**: `app/Http/Controllers/AppointmentController.php`, `app/Models/Appointment.php`, `resources/views/appointments/`
- **Radiology & Ultrasound**: `app/Http/Controllers/RadiologyStaffController.php`, `resources/views/radiology-staff/index.blade.php`
- **Laboratory**: `app/Http/Controllers/LabStaffController.php`, `app/Models/LabRequest.php`, `resources/views/lab/`
- **Patients & Insurance (HI/MOI)**: `app/Http/Controllers/PatientController.php`, `app/Models/Patient.php`, `resources/views/patients/`
- **Emergency & Operations**: `app/Http/Controllers/EmergencyController.php`, `app/Http/Controllers/SurgeryController.php`
- **Inpatient & Incubators**: `app/Http/Controllers/BedReservationController.php`, `app/Http/Controllers/IncubatorReservationController.php`
- **Activity Logs & Live Pulse**: `app/Http/Controllers/UserActivityController.php`, `app/Services/UserActivityLogger.php`
- **Database Sync**: `app/Services/DatabaseSyncService.php`, `php artisan db:pull-hostinger`

## 3. Non-Negotiable Operational Rules (القواعد الصارمة)
1. **Hostinger Multi-Environment URL Rule**: NEVER hardcode root URLs starting with `/` (e.g. `/radiology-staff/...`). ALWAYS use `{{ url('/') }}` or `{{ route(...) }}` in Blade and JS so routes work on Hostinger subpaths and local WAMP without 404 errors.
2. **Git Rule**: Direct push (`git push`) strictly to `main` branch. No side branches.
3. **Doctor Visit Refund Isolation**: Cancelling a doctor visit refunds only the doctor's appointment fee (`payment_type = 'appointment'`). Never overwrite or auto-refund paid lab or radiology requests.
4. **State Tracking**: Consult `hospital-memory.md` for current project state before starting tasks, and update its `Current Project State` section at the end of sessions.
5. **Session Continuity Prompts**: Refer to `SESSION_PROMPTS.md` for standard session start and handoff instructions.
