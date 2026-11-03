<?php
/**
 * Master Appointment Management (Admin / Receptionist)
 * Search, Filter, Confirm, Cancel, or Complete any Appointment
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$pdo = get_db_connection();

// Handle Status Updates or Deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        $apptId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

        if ($apptId) {
            $stmtV = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmtV->execute([$apptId]);
            $targetAppt = $stmtV->fetch();

            if ($targetAppt) {
                if ($action === 'confirm') {
                    // Check slot availability before confirming
                    $slotCheck = validate_slot_availability($targetAppt['doctor_id'], $targetAppt['appointment_date'], $targetAppt['appointment_time'], $apptId);
                    if (!$slotCheck['available']) {
                        set_flash('danger', 'Cannot confirm: ' . $slotCheck['reason']);
                    } else {
                        $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Confirmed', updated_at = NOW() WHERE id = ?");
                        $stmtUp->execute([$apptId]);
                        set_flash('success', "Appointment #{$apptId} successfully Confirmed.");
                    }
                } elseif ($action === 'cancel') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('warning', "Appointment #{$apptId} Cancelled.");
                } elseif ($action === 'complete') {
                    $stmtUp = $pdo->prepare("UPDATE appointments SET status = 'Completed', updated_at = NOW() WHERE id = ?");
                    $stmtUp->execute([$apptId]);
                    set_flash('success', "Appointment #{$apptId} marked as Completed.");
                } elseif ($action === 'delete') {
                    $stmtDel = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                    $stmtDel->execute([$apptId]);
                    set_flash('info', "Appointment record #{$apptId} has been deleted.");
                }
            }
        }
    }
    // Retain query parameters on redirect
    $queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ' . BASE_URL . '/admin/appointments.php' . $queryString);
    exit;
}

// Filters
$docFilter    = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT);
$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');
$dateFilter   = trim($_GET['date'] ?? '');

$sql = "
    SELECT a.*, d.specialization, d.consultation_fee,
           u_doc.name as doctor_name,
           u_pat.name as patient_name, u_pat.phone as patient_phone, u_pat.email as patient_email
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u_doc ON d.user_id = u_doc.id
    JOIN users u_pat ON a.patient_id = u_pat.id
    WHERE 1=1
";
$params = [];

if ($docFilter) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $docFilter;
}

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}

if (!empty($search)) {
    $sql .= " AND (u_pat.name LIKE ? OR u_pat.phone LIKE ? OR u_pat.email LIKE ? OR a.reason LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Fetch active doctors for filter select
$doctorsList = $pdo->query("SELECT d.id, u.name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.name ASC")->fetchAll();

$page_title = 'Manage All Appointments';
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
                            Clinical Operations
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Master Appointments Log</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/book-appointment.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-ticket-perforated me-2"></i> Book Walk-in Appointment
                    </a>
                </div>

                <div class="card-body p-4">
                    <?php echo display_flash(); ?>

                    <!-- Filter & Search Toolbar -->
                    <form action="<?php echo BASE_URL; ?>/admin/appointments.php" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Patient name, phone, symptom..." value="<?php echo e($search); ?>">
                        </div>

                        <div class="col-md-3">
                            <select name="doctor_id" class="form-select form-select-sm">
                                <option value="">All Doctors</option>
                                <?php foreach ($doctorsList as $doc): ?>
                                    <option value="<?php echo $doc['id']; ?>" <?php echo ($docFilter == $doc['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($doc['name'] . ' (' . $doc['specialization'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo ($statusFilter === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Completed" <?php echo ($statusFilter === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo ($statusFilter === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="date" name="date" class="form-control form-control-sm" value="<?php echo e($dateFilter); ?>">
                        </div>

                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <?php if ($docFilter || !empty($statusFilter) || !empty($dateFilter) || !empty($search)): ?>
                                <a href="<?php echo BASE_URL; ?>/admin/appointments.php" class="btn btn-outline-secondary btn-sm rounded-pill" title="Reset Filters">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Appointments Table -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Origin</th>
                                    <th class="text-end">Manage Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                            No appointment records found matching your filters.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($appointments as $appt): ?>
                                        <tr>
                                            <td class="text-muted fw-semibold">#<?php echo $appt['id']; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($appt['patient_name']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo e($appt['patient_phone']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo e($appt['doctor_name']); ?></div>
                                                <div class="small text-muted"><?php echo e($appt['specialization']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo format_date($appt['appointment_date']); ?></div>
                                                <div class="small text-primary"><i class="bi bi-clock me-1"></i><?php echo format_time($appt['appointment_time']); ?></div>
                                            </td>
                                            <td>
                                                <?php echo status_badge($appt['status']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-muted border">
                                                    <?php echo ($appt['booked_by'] === 'admin') ? 'Reception' : 'Patient'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-light btn-sm border rounded-pill dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                                                        Action
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                        <?php if ($appt['status'] !== 'Confirmed'): ?>
                                                            <li>
                                                                <form action="<?php echo BASE_URL; ?>/admin/appointments.php<?php echo $queryString ?? ''; ?>" method="POST">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                    <input type="hidden" name="action" value="confirm">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                                    <button type="submit" class="dropdown-item py-2 text-primary">
                                                                        <i class="bi bi-check-circle me-2 text-primary"></i> Mark Confirmed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>

                                                        <?php if ($appt['status'] !== 'Completed'): ?>
                                                            <li>
                                                                <form action="<?php echo BASE_URL; ?>/admin/appointments.php<?php echo $queryString ?? ''; ?>" method="POST">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                    <input type="hidden" name="action" value="complete">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                                    <button type="submit" class="dropdown-item py-2 text-success">
                                                                        <i class="bi bi-check2-all me-2 text-success"></i> Mark Completed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>

                                                        <?php if ($appt['status'] !== 'Cancelled'): ?>
                                                            <li>
                                                                <form action="<?php echo BASE_URL; ?>/admin/appointments.php<?php echo $queryString ?? ''; ?>" method="POST">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                    <input type="hidden" name="action" value="cancel">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                                    <button type="submit" class="dropdown-item py-2 text-warning confirm-action" data-confirm="Cancel this appointment?">
                                                                        <i class="bi bi-x-circle me-2 text-warning"></i> Mark Cancelled
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>

                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li>
                                                            <form action="<?php echo BASE_URL; ?>/admin/appointments.php<?php echo $queryString ?? ''; ?>" method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                                                <button type="submit" class="dropdown-item py-2 text-danger confirm-action" data-confirm="Permanently delete this appointment record?">
                                                                    <i class="bi bi-trash3 me-2 text-danger"></i> Delete Record
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
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
