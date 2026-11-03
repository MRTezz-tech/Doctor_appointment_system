<?php
/**
 * Doctor Confirmed Appointments Schedule
 * View Patient Details & Mark Appointments Completed
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Doctor Role
check_auth(['doctor']);

$user = get_logged_in_user();
$pdo = get_db_connection();

$stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
$stmtDoc->execute([$user['id']]);
$doctorId = $stmtDoc->fetchColumn();

if (!$doctorId) {
    die("Doctor account profile not found.");
}

// Handle Status Updates (Mark Completed / Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        $apptId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

        if ($apptId) {
            $stmtV = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ?");
            $stmtV->execute([$apptId, $doctorId]);
            $appt = $stmtV->fetch();

            if ($appt) {
                if ($action === 'complete') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Completed', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('success', 'Appointment #' . $apptId . ' has been marked as Completed.');
                } elseif ($action === 'cancel') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('warning', 'Appointment #' . $apptId . ' has been Cancelled.');
                }
            }
        }
    }
    header('Location: ' . BASE_URL . '/doctor/appointments.php');
    exit;
}

// Fetch Confirmed Upcoming / Active Appointments
$stmtConfirmed = $pdo->prepare("
    SELECT a.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.status = 'Confirmed'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmtConfirmed->execute([$doctorId]);
$confirmedAppointments = $stmtConfirmed->fetchAll();

$page_title = 'Confirmed Appointment Schedule';
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
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Confirmed Consultations
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Upcoming Patient Schedule</h3>
                    </div>
                    <span class="badge bg-light text-primary border px-3 py-2 fw-semibold">
                        Confirmed: <?php echo count($confirmedAppointments); ?>
                    </span>
                </div>

                <div class="card-body p-4">
                    <?php echo display_flash(); ?>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Details</th>
                                    <th>Appointment Slot</th>
                                    <th>Clinical Reason</th>
                                    <th>Booked By</th>
                                    <th class="text-end">Consultation Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($confirmedAppointments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar2-check text-primary fs-2 d-block mb-2"></i>
                                            No confirmed upcoming appointments at this moment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($confirmedAppointments as $appt): ?>
                                        <tr>
                                            <td class="text-muted fw-semibold">#<?php echo $appt['id']; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($appt['patient_name']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo e($appt['patient_phone']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?php echo e($appt['patient_email']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo format_date($appt['appointment_date']); ?></div>
                                                <div class="small text-primary fw-medium"><i class="bi bi-clock me-1"></i><?php echo format_time($appt['appointment_time']); ?></div>
                                            </td>
                                            <td>
                                                <span class="small text-secondary d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo e($appt['reason']); ?>">
                                                    <?php echo e($appt['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo ($appt['booked_by'] === 'admin') ? 'Receptionist' : 'Patient'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <!-- Mark Completed Button -->
                                                    <form action="<?php echo BASE_URL; ?>/doctor/appointments.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="complete">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-xs" title="Mark Consultation Completed">
                                                            <i class="bi bi-check2-all me-1"></i> Mark Done
                                                        </button>
                                                    </form>

                                                    <!-- Cancel Button -->
                                                    <form action="<?php echo BASE_URL; ?>/doctor/appointments.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 confirm-action" data-confirm="Are you sure you want to cancel this confirmed appointment?">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
