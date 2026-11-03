<?php
/**
 * Admin / Receptionist Walk-in Appointment Booking
 * Book on behalf of existing patients or register a walk-in patient instantly
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$pdo = get_db_connection();

// Pre-selected patient if linked from patients.php
$selected_patient_id = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT) ?: 0;
$selected_doctor_id  = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT) ?: 0;

// Fetch all registered patients
$patients = $pdo->query("SELECT id, name, email, phone FROM users WHERE role = 'patient' ORDER BY name ASC")->fetchAll();

// Fetch active doctors
$doctors = $pdo->query("
    SELECT d.*, u.name as doctor_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'active' 
    ORDER BY u.name ASC
")->fetchAll();

$error = '';
$prefill_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $patient_mode = $_POST['patient_mode'] ?? 'existing'; // 'existing' or 'new'
        $patient_id   = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
        $doctor_id    = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
        $date         = trim($_POST['appointment_date'] ?? '');
        $time         = trim($_POST['appointment_time'] ?? '');
        $reason       = trim($_POST['reason'] ?? '');
        $initialStatus= in_array($_POST['status'] ?? '', ['Confirmed', 'Pending']) ? $_POST['status'] : 'Confirmed';

        $prefill_date = $date;
        $selected_doctor_id = $doctor_id;

        // Handle new patient registration if in 'new' mode
        if ($patient_mode === 'new') {
            $new_name  = trim($_POST['new_patient_name'] ?? '');
            $new_email = trim($_POST['new_patient_email'] ?? '');
            $new_phone = trim($_POST['new_patient_phone'] ?? '');

            if (empty($new_name) || empty($new_phone)) {
                $error = 'Please enter both the patient name and phone number.';
            } else {
                // If email not provided, generate a placeholder clinic email
                if (empty($new_email)) {
                    $new_email = 'walkin_' . time() . '_' . rand(100, 999) . '@doccare.local';
                }

                // Check if email already exists
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtCheck->execute([$new_email]);
                $existing = $stmtCheck->fetch();

                if ($existing) {
                    $patient_id = $existing['id'];
                } else {
                    $defaultPassword = password_hash('walkin123', PASSWORD_DEFAULT);
                    $stmtNewP = $pdo->prepare("
                        INSERT INTO users (name, email, phone, password, role, created_at)
                        VALUES (?, ?, ?, ?, 'patient', NOW())
                    ");
                    $stmtNewP->execute([$new_name, $new_email, $new_phone, $defaultPassword]);
                    $patient_id = $pdo->lastInsertId();
                }
            }
        }

        if (empty($error)) {
            if (!$patient_id || !$doctor_id || empty($date) || empty($time) || empty($reason)) {
                $error = 'Please fill out all booking fields, select a patient, and pick an available time slot.';
            } elseif (!is_valid_future_date($date)) {
                $error = 'Cannot book an appointment for past dates.';
            } else {
                // Server-side double-booking check
                $slotCheck = validate_slot_availability($doctor_id, $date, $time);

                if (!$slotCheck['available']) {
                    $error = 'Slot Conflict: ' . $slotCheck['reason'];
                } else {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO appointments (
                            patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, 'admin', NOW())
                    ");

                    try {
                        $stmtInsert->execute([$patient_id, $doctor_id, $date, $time, $reason, $initialStatus]);
                        set_flash('success', "Appointment scheduled successfully with status: {$initialStatus}.");
                        header('Location: ' . BASE_URL . '/admin/appointments.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Failed to create appointment: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$page_title = 'Reception Walk-in Appointment Booking';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Form Area -->
        <div class="col-lg-9 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Reception Desk Booking
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Walk-in Appointment Booking</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/appointments.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> View Appointments
                    </a>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/admin/book-appointment.php" method="POST" id="adminBookingForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <!-- Patient Selection Mode -->
                        <h5 class="fw-bold text-dark mb-3">1. Patient Identification</h5>
                        <div class="d-flex gap-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="patient_mode" id="mode_existing" value="existing" checked onchange="togglePatientMode()">
                                <label class="form-check-label fw-semibold text-dark small" for="mode_existing">
                                    Existing Registered Patient
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="patient_mode" id="mode_new" value="new" onchange="togglePatientMode()">
                                <label class="form-check-label fw-semibold text-dark small" for="mode_new">
                                    New Walk-in Patient (Quick Registration)
                                </label>
                            </div>
                        </div>

                        <!-- Existing Patient Dropdown -->
                        <div id="existingPatientBox" class="mb-4">
                            <label for="patient_id" class="form-label small fw-semibold text-dark">Select Registered Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" id="patient_id" class="form-select bg-light">
                                <option value="">-- Choose Existing Patient --</option>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($selected_patient_id == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($p['name']); ?> (Phone: <?php echo e($p['phone']); ?>, Email: <?php echo e($p['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- New Walk-in Patient Fields (Hidden by default) -->
                        <div id="newPatientBox" class="mb-4 p-3 bg-light rounded-3 border d-none">
                            <h6 class="fw-bold text-primary mb-2">New Patient Contact Details</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-dark">Patient Name <span class="text-danger">*</span></label>
                                    <input type="text" name="new_patient_name" id="new_patient_name" class="form-control" placeholder="Full Name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-dark">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" name="new_patient_phone" id="new_patient_phone" class="form-control" placeholder="10-digit mobile" pattern="[0-9]{10}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-dark">Email (Optional)</label>
                                    <input type="email" name="new_patient_email" id="new_patient_email" class="form-control" placeholder="patient@example.com">
                                </div>
                            </div>
                        </div>

                        <!-- Doctor & Schedule Selection -->
                        <h5 class="fw-bold text-dark mb-3 pt-3 border-top">2. Doctor &amp; Appointment Slot</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="doctor_id" class="form-label small fw-semibold text-dark">Consulting Doctor <span class="text-danger">*</span></label>
                                <select name="doctor_id" id="doctor_id" class="form-select bg-light" required>
                                    <option value="">-- Choose Medical Specialist --</option>
                                    <?php foreach ($doctors as $d): ?>
                                        <option value="<?php echo $d['id']; ?>" <?php echo ($selected_doctor_id == $d['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($d['doctor_name']); ?> - <?php echo e($d['specialization']); ?> (Fee: ₹<?php echo number_format($d['consultation_fee'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="appointment_date" class="form-label small fw-semibold text-dark">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control bg-light future-date-only" 
                                       value="<?php echo e($prefill_date); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label for="status" class="form-label small fw-semibold text-dark">Initial Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select bg-light">
                                    <option value="Confirmed" selected>Confirmed (Walk-in)</option>
                                    <option value="Pending">Pending Review</option>
                                </select>
                            </div>
                        </div>

                        <!-- Time Slot Container -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Available Time Slots <span class="text-danger">*</span></label>
                            <div id="slotsMessage" class="mb-2"></div>
                            <div id="slotsContainer" class="p-3 bg-light rounded-3 border">
                                <div class="text-muted small text-center py-2">
                                    Select a doctor and date above to load available time slots.
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label small fw-semibold text-dark">Reason for Consultation <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" rows="2" class="form-control bg-light" placeholder="Chief complaints or consultation notes..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="<?php echo BASE_URL; ?>/admin/appointments.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" id="submitAppointmentBtn" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm" disabled>
                                <i class="bi bi-calendar-check me-2"></i> Book Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePatientMode() {
    const isNew = document.getElementById('mode_new').checked;
    const existingBox = document.getElementById('existingPatientBox');
    const newBox = document.getElementById('newPatientBox');
    const patientSelect = document.getElementById('patient_id');
    const newNameInput = document.getElementById('new_patient_name');
    const newPhoneInput = document.getElementById('new_patient_phone');

    if (isNew) {
        existingBox.classList.add('d-none');
        newBox.classList.remove('d-none');
        patientSelect.removeAttribute('required');
        newNameInput.setAttribute('required', 'required');
        newPhoneInput.setAttribute('required', 'required');
    } else {
        existingBox.classList.remove('d-none');
        newBox.classList.add('d-none');
        patientSelect.setAttribute('required', 'required');
        newNameInput.removeAttribute('required');
        newPhoneInput.removeAttribute('required');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
