<?php
/**
 * Doctor Pending Appointment Requests
 * Review, Confirm, or Reject Patient Bookings
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

// Handle Confirm or Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        $apptId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

        if ($apptId) {
            $stmtV = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ? AND status = 'Pending'");
            $stmtV->execute([$apptId, $doctorId]);
            $targetAppt = $stmtV->fetch();

            if ($targetAppt) {
                if ($action === 'confirm') {
                    // Double-booking check: ensure doctor does not already have another confirmed appointment on this date and time
                    $slotCheck = validate_slot_availability($doctorId, $targetAppt['appointment_date'], $targetAppt['appointment_time'], $apptId);
                    if (!$slotCheck['available']) {
                        set_flash('danger', 'Slot Conflict: ' . $slotCheck['reason']);
                    } else {
                        $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Confirmed', updated_at = NOW() WHERE id = ?");
                        $stmtUp->execute([$apptId]);
                        set_flash('success', 'Appointment #' . $apptId . ' has been Confirmed.');
                    }
                } elseif ($action === 'reject') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('warning', 'Appointment #' . $apptId . ' has been Rejected and marked as Cancelled.');
                }
            } else {
                set_flash('danger', 'Appointment not found or not in pending status.');
            }
        }
    }
    header('Location: ' . BASE_URL . '/doctor/pending-requests.php');
    exit;
}

// Fetch all Pending Requests for this Doctor
$stmtPending = $pdo->prepare("
    SELECT a.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.status = 'Pending'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmtPending->execute([$doctorId]);
$pendingRequests = $stmtPending->fetchAll();

$page_title = 'Pending Appointment Requests';
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
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill mb-1">
                            Action Required
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Pending Appointment Requests</h3>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        Total Requests: <strong><?php echo count($pendingRequests); ?></strong>
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
                                    <th>Requested Date &amp; Time</th>
                                    <th>Reason for Consultation</th>
                                    <th>Requested On</th>
                                    <th class="text-end">Review Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingRequests)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-check2-all text-success" style="font-size: 3rem;"></i>
                                            <h5 class="fw-bold text-dark mt-3">All Caught Up!</h5>
                                            <p class="text-muted small mb-0">There are no pending appointment requests waiting for your review.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingRequests as $req): ?>
                                        <tr>
                                            <td class="text-muted fw-semibold">#<?php echo $req['id']; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($req['patient_name']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo e($req['patient_phone']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?php echo e($req['patient_email']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo format_date($req['appointment_date']); ?></div>
                                                <div class="small text-primary fw-medium"><i class="bi bi-clock me-1"></i><?php echo format_time($req['appointment_time']); ?></div>
                                            </td>
                                            <td>
                                                <span class="small text-secondary d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo e($req['reason']); ?>">
                                                    <?php echo e($req['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="small text-muted"><?php echo format_date($req['created_at']); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <!-- Confirm Button -->
                                                    <form action="<?php echo BASE_URL; ?>/doctor/pending-requests.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="confirm">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $req['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-xs">
                                                            <i class="bi bi-check-lg me-1"></i> Confirm
                                                        </button>
                                                    </form>

                                                    <!-- Reject Button -->
                                                    <form action="<?php echo BASE_URL; ?>/doctor/pending-requests.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $req['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 confirm-action" data-confirm="Are you sure you want to reject this appointment request?">
                                                            <i class="bi bi-x-lg me-1"></i> Reject
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
