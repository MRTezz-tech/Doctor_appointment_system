<?php
/**
 * Patient Appointment History & Status Tracking
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

// Handle Cancellation of Pending Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $token = $_POST['csrf_token'] ?? '';
    $apptId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } elseif ($apptId) {
        $stmtCheck = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND status = 'Pending'");
        $stmtCheck->execute([$apptId, $patientId]);
        if ($stmtCheck->fetch()) {
            $stmtCancel = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
            $stmtCancel->execute([$apptId]);
            set_flash('success', 'Your appointment request # ' . $apptId . ' has been cancelled successfully.');
        } else {
            set_flash('danger', 'You can only cancel appointment requests that are currently in "Pending" status.');
        }
    }
    header('Location: ' . BASE_URL . '/patient/my-appointments.php');
    exit;
}

// Filter by Status
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT a.*, d.specialization, d.consultation_fee, u.name as doctor_name, u.phone as doctor_phone
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE a.patient_id = ?
";
$params = [$patientId];

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$page_title = 'My Appointments History';
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
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Consultation Log
                        </span>
                        <h3 class="fw-bold text-dark mb-0">My Appointments</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-calendar-plus me-2"></i> Book New Slot
                    </a>
                </div>

                <div class="card-body p-4">
                    <?php echo display_flash(); ?>

                    <!-- Status Filter Tabs -->
                    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
                        <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php" 
                           class="btn btn-sm rounded-pill <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-light border'; ?>">
                            All Appointments
                        </a>
                        <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php?status=Pending" 
                           class="btn btn-sm rounded-pill <?php echo ($statusFilter === 'Pending') ? 'btn-warning text-dark' : 'btn-light border'; ?>">
                            Pending Requests
                        </a>
                        <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php?status=Confirmed" 
                           class="btn btn-sm rounded-pill <?php echo ($statusFilter === 'Confirmed') ? 'btn-primary' : 'btn-light border'; ?>">
                            Confirmed
                        </a>
                        <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php?status=Completed" 
                           class="btn btn-sm rounded-pill <?php echo ($statusFilter === 'Completed') ? 'btn-success' : 'btn-light border'; ?>">
                            Completed
                        </a>
                        <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php?status=Cancelled" 
                           class="btn btn-sm rounded-pill <?php echo ($statusFilter === 'Cancelled') ? 'btn-danger' : 'btn-light border'; ?>">
                            Cancelled
                        </a>
                    </div>

                    <!-- Appointments Table -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor &amp; Specialty</th>
                                    <th>Consultation Time</th>
                                    <th>Reason for Visit</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                            No appointments found matching this status filter.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($appointments as $appt): ?>
                                        <tr>
                                            <td class="text-muted fw-semibold">#<?php echo $appt['id']; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($appt['doctor_name']); ?></div>
                                                <div class="small text-muted"><?php echo e($appt['specialization']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo format_date($appt['appointment_date']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-clock me-1"></i><?php echo format_time($appt['appointment_time']); ?></div>
                                            </td>
                                            <td>
                                                <span class="small text-secondary d-inline-block text-truncate" style="max-width: 180px;" title="<?php echo e($appt['reason']); ?>">
                                                    <?php echo e($appt['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark">₹<?php echo number_format($appt['consultation_fee'], 2); ?></span>
                                            </td>
                                            <td>
                                                <?php echo status_badge($appt['status']); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($appt['status'] === 'Pending'): ?>
                                                    <form action="<?php echo BASE_URL; ?>/patient/my-appointments.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill confirm-action" data-confirm="Are you sure you want to cancel this pending appointment?">
                                                            <i class="bi bi-x-circle me-1"></i> Cancel Request
                                                        </button>
                                                    </form>
                                                <?php elseif ($appt['status'] === 'Confirmed'): ?>
                                                    <span class="badge bg-light text-primary border"><i class="bi bi-bell me-1"></i>Be on time</span>
                                                <?php elseif ($appt['status'] === 'Completed'): ?>
                                                    <span class="text-success small"><i class="bi bi-check2-circle me-1"></i>Finished</span>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
