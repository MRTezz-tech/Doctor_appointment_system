# DocCare - Doctor Appointment System (PHP & MySQL)

A modern, full-stack **Doctor Appointment Management System** developed using **Core PHP**, **MySQL**, **Bootstrap 5**, and **JavaScript** for college project presentation (MCA / BCA / B.Tech). The application features role-based access control, real-time dynamic time-slot scheduling, server-side atomic double-booking prevention, and an intuitive medical blue-and-white responsive UI.

---

## 1. Technology Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5.3.3, Vanilla JavaScript, Bootstrap Icons
- **Backend:** Core PHP (PHP 7.4 - PHP 8.5+ compatible)
- **Database:** MySQL / MariaDB (Database: `doctor_appointment_system`)
- **Architecture:** Procedural & Object-Oriented Hybrid with PDO Prepared Statements
- **Server:** Apache via XAMPP or PHP Built-in Development Server

---

## 2. User Roles & Capabilities

### 🩺 Patient
- Self-registration with secure password hashing (`password_hash` / `password_verify`).
- Log in and manage profile contact information.
- Browse certified medical specialists filtered by specialization and search keywords.
- View detailed doctor profiles, qualification degrees, working hours, and consultation fees.
- Interactive appointment booking with **real-time time slot availability** loaded via AJAX.
- View appointment status: `Pending`, `Confirmed`, `Completed`, or `Cancelled`.
- Cancel pending requests before doctor review.
- Access complete consultation history.

### 👨‍⚕️ Doctor
- Dedicated practitioner dashboard with today's schedule and summary statistics.
- Review pending appointment requests with patient contact information and symptoms.
- Confirm or reject pending booking requests (double-booking safety enforced on confirmation).
- View upcoming confirmed appointments and relevant patient details.
- Mark completed consultations after patient visit.
- View comprehensive patient consultation history.
- Practice schedule settings: Configure consultation fees, available working days, daily start & end times, slot durations (15 to 60 mins), and bio.

### 🏥 Admin / Receptionist
- Central hospital control center with live statistics:
  - Total doctors and active faculty count
  - Total registered patients
  - Today's scheduled visits
  - Pending review requests
  - Confirmed active appointments
- **Doctor Management:** Add new doctor accounts, edit profiles, toggle active/inactive status.
- **Patient Directory:** View patient accounts, contact info, and booking counts.
- **Master Appointment Manager:** Search & filter all clinic appointments by doctor, patient, date, and status. Confirm, cancel, mark completed, or delete records.
- **Walk-in Reception Booking:** Book appointments on behalf of existing patients OR register new walk-in patients on the spot with instant confirmation.

---

## 3. Double-Booking Prevention & Real-Time Slot Engine

1. **Schedule Checking:** The doctor's weekly working days (`Monday,Tuesday,...`), start time, end time, and slot duration (e.g. 30 mins) determine the eligible consultation slots.
2. **Dynamic Slot Filtering:** When a patient or receptionist selects a doctor and date:
   - Past dates are strictly blocked.
   - If the date is today, time slots that have already passed in local time are excluded.
   - Any slot with an active appointment (`Pending` or `Confirmed`) is marked as booked and disabled.
3. **Server-Side Validation:** When the form is submitted, the server re-validates slot availability using PDO prepared queries. If another patient reserved the same slot milliseconds earlier, the system rejects the request with a clear conflict message and prevents duplicate bookings.
4. **Slot Re-use on Cancellation:** If an appointment is cancelled, the slot is instantly freed and becomes available for new patient bookings.

---

## 4. Database Architecture

**Database Name:** `doctor_appointment_system`

### `users` Table
Stores authentication accounts for Patients, Doctors, and Administrators.
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique User ID |
| `name` | VARCHAR(100) | NOT NULL | Full Name |
| `email` | VARCHAR(100) | NOT NULL, UNIQUE | User Login Email |
| `phone` | VARCHAR(20) | NOT NULL | Mobile Phone Number |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt Hashed Password |
| `role` | ENUM | 'patient', 'doctor', 'admin' | User Access Role |
| `created_at`| TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration Timestamp |

### `doctors` Table
Stores clinical profiles, pricing, and scheduling rules.
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Doctor Record ID |
| `user_id` | INT | NOT NULL, UNIQUE, FK -> users(id) | Associated User Account |
| `specialization`| VARCHAR(100)| NOT NULL | e.g. Cardiologist, Dermatologist |
| `qualification` | VARCHAR(100)| NOT NULL | e.g. MBBS, MD, DM |
| `consultation_fee`| DECIMAL(10,2)| NOT NULL DEFAULT 500.00 | Consultation Fee in INR (₹) |
| `available_days`| VARCHAR(255)| NOT NULL | e.g. Monday,Tuesday,Wednesday... |
| `start_time` | TIME | NOT NULL DEFAULT '09:00:00' | Daily Start Time |
| `end_time` | TIME | NOT NULL DEFAULT '17:00:00' | Daily End Time |
| `slot_duration` | INT | NOT NULL DEFAULT 30 | Interval in minutes |
| `status` | ENUM | 'active', 'inactive' | Availability Status |
| `bio` | TEXT | NULL | Clinical Background Summary |

### `appointments` Table
Stores patient consultation bookings and lifecycle status.
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Appointment ID |
| `patient_id` | INT | NOT NULL, FK -> users(id) | Patient User ID |
| `doctor_id` | INT | NOT NULL, FK -> doctors(id) | Doctor Record ID |
| `appointment_date`| DATE | NOT NULL | Scheduled Visit Date |
| `appointment_time`| TIME | NOT NULL | Scheduled Slot Time |
| `reason` | TEXT | NOT NULL | Chief Complaints / Symptoms |
| `status` | ENUM | 'Pending', 'Confirmed', 'Cancelled', 'Completed' | Current Workflow Status |
| `booked_by` | ENUM | 'patient', 'admin' | Booking Originator |
| `created_at`| TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Created Timestamp |
| `updated_at`| TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last Status Update |

---

## 5. Working Demo Accounts

For demonstration and evaluation, the database comes preloaded with active demo accounts. In addition, the **Login Page (`login.php`) includes 1-click Quick Demo Fill buttons** to auto-populate credentials without manual typing:

| Role | Account Name | Login Email | Password | Details |
|---|---|---|---|---|
| **Admin / Receptionist** | Admin Receptionist | `admin@doccare.com` | `admin123` | Full clinic management & walk-in booking |
| **Doctor (Cardiology)** | Dr. Rajesh Sharma | `doctor.sharma@doccare.com` | `doctor123` | MBBS, MD, DM (Cardiologist) |
| **Doctor (Dermatology)** | Dr. Ananya Patel | `doctor.patel@doccare.com` | `doctor123` | MBBS, DVD, MD (Dermatologist) |
| **Doctor (Orthopedics)** | Dr. Vikram Malhotra | `doctor.malhotra@doccare.com` | `doctor123` | MBBS, MS (Orthopedic Surgeon) |
| **Doctor (Pediatrics)** | Dr. Sneha Kulkarni | `doctor.kulkarni@doccare.com` | `doctor123` | MBBS, DCH, MD (Pediatrician) |
| **Patient 1** | Rahul Verma | `rahul.verma@example.com` | `patient123` | Existing patient with appointment history |
| **Patient 2** | Priya Singh | `priya.singh@example.com` | `patient123` | Registered patient with upcoming slots |

---

## 6. XAMPP Local Setup Instructions

Follow these steps to run the application on a local machine using XAMPP:

### Step 1: Install & Start XAMPP
1. Download and install [XAMPP](https://www.apachefriends.org/) (PHP 7.4 or PHP 8.x).
2. Open the **XAMPP Control Panel**.
3. Click **Start** for both **Apache** and **MySQL**.

### Step 2: Copy Project to XAMPP Web Root
Copy the project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\doctor_appointment_system
```

### Step 3: Create & Import the Database
1. Open your browser and go to phpMyAdmin:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **New** in the left sidebar and enter the database name:
   ```
   doctor_appointment_system
   ```
   Select collation `utf8mb4_unicode_ci` and click **Create**.
3. Click on the created database `doctor_appointment_system` in the left sidebar.
4. Click on the **Import** tab at the top.
5. Choose file `database/schema.sql` and click **Import** at the bottom.
6. Once completed, click **Import** again, choose `database/sample_data.sql`, and click **Import**.

### Step 4: Verify Database Credentials
Open `config/database.php` and verify your MySQL settings (defaults for XAMPP require no changes):
```php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'doctor_appointment_system');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 5: Open the Application
Navigate in your web browser to:
```
http://localhost/doctor_appointment_system/
```
Or if running via PHP CLI built-in server:
```powershell
php -S localhost:8000
```
Then visit `http://localhost:8000/`.

---

## 7. Project Directory Structure

```
doctor_appointment_system/
├── admin/                           # Administrator & Receptionist Module
│   ├── add-doctor.php               # Register new doctor account & clinical profile
│   ├── appointments.php             # Master appointment management with search/filter
│   ├── book-appointment.php        # Walk-in booking for existing or new patients
│   ├── doctors.php                  # Doctor directory with status toggle
│   ├── edit-doctor.php              # Modify doctor profile & schedule
│   ├── index.php                    # Admin dashboard statistics & overview
│   └── patients.php                 # Registered patients directory & appointment count
├── api/
│   └── get-slots.php                # AJAX endpoint for real-time available time slots
├── assets/
│   ├── css/
│   │   └── style.css                # Medical blue-and-white custom design system
│   └── js/
│       └── script.js                # AJAX slot loader, demo fillers, client validations
├── config/
│   └── database.php                 # PDO database connection & dynamic BASE_URL
├── database/
│   ├── sample_data.sql              # Realistic demo doctors, patients, and bookings
│   └── schema.sql                   # MySQL database schema definition with constraints
├── doctor/                          # Doctor Practice Portal
│   ├── appointments.php             # Confirmed upcoming appointments & mark complete
│   ├── history.php                  # Filterable consultation archive
│   ├── index.php                    # Doctor dashboard overview & today's schedule
│   ├── pending-requests.php         # Pending bookings review (Confirm / Reject)
│   └── profile.php                  # Practice schedule, fee & timing settings
├── includes/
│   ├── auth.php                     # Role-based access control & CSRF token generator
│   ├── footer.php                   # Master footer with branding & JS bundle
│   ├── functions.php                # Sanitization, slot calculation & conflict check
│   ├── header.php                   # Master header with Google Fonts & Bootstrap 5
│   ├── navbar.php                   # Top navigation with role-aware account dropdown
│   └── sidebar.php                  # Dashboard sidebar with pending notification badge
├── patient/                         # Patient Consultation Portal
│   ├── book-appointment.php        # Booking interface with dynamic time slot grid
│   ├── index.php                    # Patient dashboard with booking statistics
│   ├── my-appointments.php         # Appointment status tracking & cancellation
│   └── profile.php                  # Personal details & password management
├── tests/
│   └── test_flow.php                # Automated 13-point test suite for verification
├── about.php                        # Clinic mission & system architecture overview
├── contact.php                      # Clinic reception details & inquiry form
├── doctor-profile.php               # Detailed doctor profile & direct booking link
├── doctors.php                      # Browse doctors with specialization filtering
├── index.php                        # Public landing page with featured specialists
├── login.php                        # Unified login with 1-click viva demo fillers
├── logout.php                       # Secure session termination & cookie destruction
├── register.php                     # Patient self-registration with validation
└── README.md                        # Documentation & setup guide
```

---

## 8. Summary of Automated Testing Results

All core application workflows were verified using the included automated test runner (`tests/test_flow.php`):

| Test Item | Verification Scope | Status |
|---|---|---|
| **1. Database Connectivity** | Verified PDO connection to MySQL database | **PASS** |
| **2. Table Schema Integrity** | Verified `users`, `doctors`, `appointments` tables | **PASS** |
| **3. Admin Credentials** | Verified bcrypt hash for `admin@doccare.com` | **PASS** |
| **4. Doctor Credentials** | Verified bcrypt hash for `doctor.sharma@doccare.com` | **PASS** |
| **5. Patient Credentials** | Verified bcrypt hash for `rahul.verma@example.com` | **PASS** |
| **6. Doctor Profile Linkage** | Verified 1-to-1 doctor profile with fee and schedule | **PASS** |
| **7. Slot Generation** | Verified unbooked slot available on valid working day | **PASS** |
| **8. Double-Booking Prevention** | Attempted duplicate booking on same slot; second attempt rejected | **PASS** |
| **9. Doctor Confirmation** | Doctor reviewed and confirmed pending request | **PASS** |
| **10. Consultation Completion** | Doctor marked appointment as Completed | **PASS** |
| **11. Cancellation & Slot Release** | Cancelled appointment released slot for re-booking | **PASS** |
| **12. Past Date Rejection** | Validation engine strictly rejected past dates | **PASS** |
| **13. Data Cleanup** | Transient test records safely removed | **PASS** |

**Result: 13 / 13 Tests Passed (100%)**
