<?php
/**
 * Patient Dashboard Overview
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Patient Role
check_auth(['patient']);

$patient = get_logged_in_user();
$pdo = get_db_connection();
$patientId = $patient['id'];

// Handle Cancel Pending Request from Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $token = $_POST['csrf_token'] ?? '';
    $apptId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } elseif ($apptId) {
        // Ensure appointment belongs to this patient and is in 'Pending' status
        $stmtCheck = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND status = 'Pending'");
        $stmtCheck->execute([$apptId, $patientId]);
        if ($stmtCheck->fetch()) {
            $stmtCancel = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
            $stmtCancel->execute([$apptId]);
            set_flash('success', 'Your pending appointment request has been cancelled.');
        } else {
            set_flash('danger', 'Only pending requests can be cancelled.');
        }
    }
    header('Location: ' . BASE_URL . '/patient/index.php');
    exit;
}

// 1. Fetch Patient Stats
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'Confirmed' AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_count,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count
    FROM appointments 
    WHERE patient_id = ?
");
$stmtStats->execute([$patientId]);
$stats = $stmtStats->fetch() ?: ['total_count' => 0, 'pending_count' => 0, 'upcoming_count' => 0, 'completed_count' => 0];

// 2. Fetch Recent & Upcoming Appointments
$stmtAppts = $pdo->prepare("
    SELECT a.*, d.specialization, d.consultation_fee, u.name as doctor_name, u.phone as doctor_phone
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 6
");
$stmtAppts->execute([$patientId]);
$recentAppointments = $stmtAppts->fetchAll();

$page_title = 'Patient Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Dashboard Area -->
        <div class="col-lg-9 col-md-8">
            <!-- Welcome Header -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Patient Health Portal
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Welcome back, <?php echo e($patient['name']); ?>!</h3>
                        <p class="text-muted small mb-0">Here is your clinical consultation summary and upcoming schedule.</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-calendar-plus me-2"></i> Book Appointment
                    </a>
                </div>
            </div>

            <?php echo display_flash(); ?>

            <!-- Stat Cards Grid -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Bookings</span>
                            <div class="stat-icon primary"><i class="bi bi-calendar2-range"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo (int)$stats['total_count']; ?></h3>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Upcoming</span>
                            <div class="stat-icon success"><i class="bi bi-calendar-check"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-success"><?php echo (int)$stats['upcoming_count']; ?></h3>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Pending Review</span>
                            <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-warning"><?php echo (int)$stats['pending_count']; ?></h3>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Completed</span>
                            <div class="stat-icon info"><i class="bi bi-check2-all"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-info"><?php echo (int)$stats['completed_count']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Recent &amp; Upcoming Consultations</h5>
                        <span class="text-muted small">Tracking your latest appointment requests</span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Doctor / Specialist</th>
                                <th>Date &amp; Time</th>
                                <th>Reason / Notes</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAppointments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                        You don't have any appointments booked yet.
                                        <div class="mt-3">
                                            <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php" class="btn btn-primary btn-sm rounded-pill px-3">
                                                Book Your First Consultation
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAppointments as $appt): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-circle-sm bg-primary-subtle text-primary">
                                                    <i class="bi bi-heart-pulse"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo e($appt['doctor_name']); ?></div>
                                                    <div class="small text-muted"><?php echo e($appt['specialization']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><?php echo format_date($appt['appointment_date']); ?></div>
                                            <div class="small text-muted"><i class="bi bi-clock me-1"></i><?php echo format_time($appt['appointment_time']); ?></div>
                                        </td>
                                        <td>
                                            <span class="small text-secondary text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo e($appt['reason']); ?>">
                                                <?php echo e($appt['reason']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo status_badge($appt['status']); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($appt['status'] === 'Pending'): ?>
                                                <form action="<?php echo BASE_URL; ?>/patient/index.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 confirm-action" data-confirm="Are you sure you want to cancel this pending appointment request?">
                                                        <i class="bi bi-x me-1"></i> Cancel
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
