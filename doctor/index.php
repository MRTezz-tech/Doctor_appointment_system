<?php
/**
 * Doctor Dashboard Overview
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Doctor Role
check_auth(['doctor']);

$user = get_logged_in_user();
$pdo = get_db_connection();

// Ensure doctor profile exists
$stmtDoc = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
$stmtDoc->execute([$user['id']]);
$doctor = $stmtDoc->fetch();

if (!$doctor) {
    die("Doctor profile configuration missing. Please contact clinic administrator.");
}

$doctorId = $doctor['id'];

// Handle Quick Actions (Confirm, Reject, Mark Completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        $apptId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

        if ($apptId) {
            // Verify appointment belongs to this doctor
            $stmtV = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ?");
            $stmtV->execute([$apptId, $doctorId]);
            $targetAppt = $stmtV->fetch();

            if ($targetAppt) {
                if ($action === 'confirm') {
                    // Check if slot has a conflict with another confirmed appointment
                    $slotCheck = validate_slot_availability($doctorId, $targetAppt['appointment_date'], $targetAppt['appointment_time'], $apptId);
                    if (!$slotCheck['available']) {
                        set_flash('danger', 'Cannot confirm: ' . $slotCheck['reason']);
                    } else {
                        $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Confirmed', updated_at = NOW() WHERE id = ?");
                        $stmtUp->execute([$apptId]);
                        set_flash('success', 'Appointment #' . $apptId . ' has been Confirmed.');
                    }
                } elseif ($action === 'reject') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('warning', 'Appointment #' . $apptId . ' has been Rejected / Cancelled.');
                } elseif ($action === 'complete') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Completed', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('success', 'Appointment #' . $apptId . ' marked as Completed.');
                }
            }
        }
    }
    header('Location: ' . BASE_URL . '/doctor/index.php');
    exit;
}

// 1. Fetch Doctor Stats
$stmtStats = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN appointment_date = CURDATE() AND status IN ('Confirmed', 'Pending') THEN 1 ELSE 0 END) as today_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'Confirmed' AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_count,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count
    FROM appointments 
    WHERE doctor_id = ?
");
$stmtStats->execute([$doctorId]);
$stats = $stmtStats->fetch() ?: ['today_count' => 0, 'pending_count' => 0, 'upcoming_count' => 0, 'completed_count' => 0];

// 2. Fetch Today's Appointments
$stmtToday = $pdo->prepare("
    SELECT a.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");
$stmtToday->execute([$doctorId]);
$todayAppointments = $stmtToday->fetchAll();

$page_title = 'Doctor Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area -->
        <div class="col-lg-9 col-md-8">
            <!-- Welcome Header -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Practitioner Portal
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Welcome, <?php echo e($user['name']); ?></h3>
                        <p class="text-muted small mb-0"><?php echo e($doctor['specialization']); ?> &bull; <?php echo e($doctor['qualification']); ?></p>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/doctor/pending-requests.php" class="btn btn-warning text-dark rounded-pill px-3 position-relative">
                            <i class="bi bi-clock-history me-1"></i> Pending Requests
                            <?php if ($stats['pending_count'] > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?php echo $stats['pending_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php echo display_flash(); ?>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Today's Visits</span>
                            <div class="stat-icon primary"><i class="bi bi-calendar2-day"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-primary"><?php echo (int)$stats['today_count']; ?></h3>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Pending Requests</span>
                            <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-warning"><?php echo (int)$stats['pending_count']; ?></h3>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Upcoming Confirmed</span>
                            <div class="stat-icon success"><i class="bi bi-calendar-check"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-success"><?php echo (int)$stats['upcoming_count']; ?></h3>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Consulted</span>
                            <div class="stat-icon info"><i class="bi bi-check2-all"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-info"><?php echo (int)$stats['completed_count']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments Schedule Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Today's Patient Schedule</h5>
                        <span class="text-muted small">Date: <?php echo date('d M Y (l)'); ?></span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/doctor/appointments.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        View Confirmed Schedule <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Reason for Visit</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar2-check fs-2 d-block mb-2 text-secondary"></i>
                                        No appointments scheduled for today. Enjoy your day!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($todayAppointments as $appt): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border fs-6 px-2 py-1">
                                                <i class="bi bi-clock me-1 text-primary"></i><?php echo format_time($appt['appointment_time']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo e($appt['patient_name']); ?></div>
                                            <div class="small text-muted">Booking #<?php echo $appt['id']; ?></div>
                                        </td>
                                        <td>
                                            <div class="small text-dark"><i class="bi bi-telephone me-1 text-muted"></i><?php echo e($appt['patient_phone']); ?></div>
                                            <div class="small text-muted"><i class="bi bi-envelope me-1 text-muted"></i><?php echo e($appt['patient_email']); ?></div>
                                        </td>
                                        <td>
                                            <span class="small text-secondary d-inline-block text-truncate" style="max-width: 180px;" title="<?php echo e($appt['reason']); ?>">
                                                <?php echo e($appt['reason']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo status_badge($appt['status']); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($appt['status'] === 'Pending'): ?>
                                                <form action="<?php echo BASE_URL; ?>/doctor/index.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-2" title="Confirm Appointment">
                                                        <i class="bi bi-check-lg"></i> Confirm
                                                    </button>
                                                </form>
                                                <form action="<?php echo BASE_URL; ?>/doctor/index.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 confirm-action" data-confirm="Reject this appointment request?" title="Reject">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($appt['status'] === 'Confirmed'): ?>
                                                <form action="<?php echo BASE_URL; ?>/doctor/index.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="complete">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3" title="Mark as Completed">
                                                        <i class="bi bi-check2-all me-1"></i> Mark Done
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
