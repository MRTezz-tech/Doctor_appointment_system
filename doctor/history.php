<?php
/**
 * Doctor Consultation History & Past Records
 * Doctor Appointment System
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

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');
$dateFilter   = trim($_GET['date'] ?? '');

$sql = "
    SELECT a.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
";
$params = [$doctorId];

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.phone LIKE ? OR a.reason LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$historyAppointments = $stmt->fetchAll();

$page_title = 'Doctor Appointment History';
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
                <div class="card-header bg-white border-bottom p-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                        Archived Consultations
                    </span>
                    <h3 class="fw-bold text-dark mb-0">Patient Appointment History</h3>
                </div>

                <div class="card-body p-4">
                    <!-- Filter & Search Bar -->
                    <form action="<?php echo BASE_URL; ?>/doctor/history.php" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Patient name, phone or symptoms..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo ($statusFilter === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Completed" <?php echo ($statusFilter === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo ($statusFilter === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="date" class="form-control form-control-sm" value="<?php echo e($dateFilter); ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <?php if (!empty($search) || !empty($statusFilter) || !empty($dateFilter)): ?>
                                <a href="<?php echo BASE_URL; ?>/doctor/history.php" class="btn btn-outline-secondary btn-sm rounded-pill" title="Reset">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Date &amp; Time</th>
                                    <th>Reason / Symptoms</th>
                                    <th>Booked By</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historyAppointments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                            No appointment records found matching your filters.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($historyAppointments as $appt): ?>
                                        <tr>
                                            <td class="text-muted fw-semibold">#<?php echo $appt['id']; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo e($appt['patient_name']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo e($appt['patient_phone']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo format_date($appt['appointment_date']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-clock me-1"></i><?php echo format_time($appt['appointment_time']); ?></div>
                                            </td>
                                            <td>
                                                <span class="small text-secondary d-inline-block text-truncate" style="max-width: 220px;" title="<?php echo e($appt['reason']); ?>">
                                                    <?php echo e($appt['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo ($appt['booked_by'] === 'admin') ? 'Receptionist' : 'Patient'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo status_badge($appt['status']); ?>
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
