<?php
/**
 * Patient Appointment Booking Form
 * Real-Time Slot Engine & Atomic Double-Booking Validation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Patient Role
check_auth(['patient']);

$patient = get_logged_in_user();
$pdo = get_db_connection();
$patientId = $patient['id'];

// Pre-selected doctor ID from query param if available
$selected_doctor_id = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT) ?: 0;

// Fetch all active doctors for dropdown
$stmtDocs = $pdo->query("
    SELECT d.*, u.name as doctor_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'active' 
    ORDER BY u.name ASC
");
$doctors = $stmtDocs->fetchAll();

$error = '';
$prefill_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please refresh and try again.';
    } else {
        $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
        $date      = trim($_POST['appointment_date'] ?? '');
        $time      = trim($_POST['appointment_time'] ?? '');
        $reason    = trim($_POST['reason'] ?? '');

        $selected_doctor_id = $doctor_id;
        $prefill_date = $date;

        // 1. Basic validation
        if (!$doctor_id || empty($date) || empty($time) || empty($reason)) {
            $error = 'Please complete all fields, including choosing an available time slot.';
        } elseif (!is_valid_future_date($date)) {
            $error = 'Appointments cannot be booked for past dates. Please select today or a future date.';
        } else {
            // 2. Server-side atomic validation for double-booking prevention
            $slotCheck = validate_slot_availability($doctor_id, $date, $time);

            if (!$slotCheck['available']) {
                $error = 'Slot Unavailable: ' . $slotCheck['reason'];
            } else {
                // 3. Insert Appointment with Pending status
                $stmtInsert = $pdo->prepare("
                    INSERT INTO appointments (
                        patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'Pending', 'patient', NOW())
                ");

                try {
                    $stmtInsert->execute([$patientId, $doctor_id, $date, $time, $reason]);

                    set_flash('success', 'Your appointment request has been scheduled with "Pending" status. The doctor will review and confirm your booking.');
                    header('Location: ' . BASE_URL . '/patient/my-appointments.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'An error occurred while booking: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Book Consultation';
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
                <div class="card-header bg-white border-bottom p-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                        Online Consultation Scheduling
                    </span>
                    <h3 class="fw-bold text-dark mb-1">Book an Appointment</h3>
                    <p class="text-muted small mb-0">Select your preferred doctor, date, and exclusive available time slot.</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/patient/book-appointment.php" method="POST" id="bookingForm" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <!-- Step 1: Select Doctor -->
                        <div class="mb-4">
                            <label for="doctor_id" class="form-label fw-bold text-dark small">
                                1. Select Medical Specialist <span class="text-danger">*</span>
                            </label>
                            <select name="doctor_id" id="doctor_id" class="form-select bg-light py-2" required>
                                <option value="">-- Choose Doctor --</option>
                                <?php foreach ($doctors as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" 
                                            data-days="<?php echo e($d['available_days']); ?>"
                                            data-fee="<?php echo e($d['consultation_fee']); ?>"
                                            <?php echo ($selected_doctor_id == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($d['doctor_name']); ?> - <?php echo e($d['specialization']); ?> (Fee: ₹<?php echo number_format($d['consultation_fee'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Step 2: Select Date -->
                        <div class="mb-4">
                            <label for="appointment_date" class="form-label fw-bold text-dark small">
                                2. Select Appointment Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="appointment_date" id="appointment_date" class="form-control bg-light py-2 future-date-only" 
                                   value="<?php echo e($prefill_date); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                            <div class="form-text small">Appointments can be booked for today or any upcoming working day.</div>
                        </div>

                        <!-- Step 3: Choose Time Slot (Dynamic AJAX Container) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small">
                                3. Available Time Slots <span class="text-danger">*</span>
                            </label>
                            <div id="slotsMessage" class="mb-2"></div>
                            <div id="slotsContainer" class="p-3 bg-light rounded-3 border">
                                <div class="text-muted small text-center py-3">
                                    <i class="bi bi-calendar2-range fs-3 d-block mb-1 text-secondary"></i>
                                    Please select a doctor and date above to load available time slots.
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Reason for Visit -->
                        <div class="mb-4">
                            <label for="reason" class="form-label fw-bold text-dark small">
                                4. Reason for Consultation / Symptoms <span class="text-danger">*</span>
                            </label>
                            <textarea name="reason" id="reason" rows="3" class="form-control bg-light" 
                                      placeholder="Briefly describe your symptoms, medical concerns, or whether this is a routine follow-up..." required><?php echo isset($_POST['reason']) ? e($_POST['reason']) : ''; ?></textarea>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                            <a href="<?php echo BASE_URL; ?>/patient/index.php" class="btn btn-outline-secondary rounded-pill px-4">
                                Cancel
                            </a>
                            <button type="submit" id="submitAppointmentBtn" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm" disabled>
                                <i class="bi bi-calendar-check me-2"></i> Confirm Booking Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
