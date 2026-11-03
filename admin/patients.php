<?php
/**
 * Admin Patient Directory Management
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$pdo = get_db_connection();
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.id, u.name, u.email, u.phone, u.created_at,
           COUNT(a.id) as total_appointments,
           SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments,
           MAX(a.appointment_date) as last_appointment_date
    FROM users u
    LEFT JOIN appointments a ON u.id = a.patient_id
    WHERE u.role = 'patient'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$page_title = 'Patient Directory';
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
                            User Records
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Patient Accounts Directory</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/book-appointment.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-ticket-perforated me-2"></i> New Walk-in Booking
                    </a>
                </div>

                <div class="card-body p-4">
                    <!-- Search Filter Form -->
                    <form action="<?php echo BASE_URL; ?>/admin/patients.php" method="GET" class="row g-2 mb-4">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search by patient name, phone number, or email..." value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="<?php echo BASE_URL; ?>/admin/patients.php" class="btn btn-outline-secondary rounded-pill" title="Reset">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Contact Info</th>
                                    <th>Registered Date</th>
                                    <th>Total Bookings</th>
                                    <th>Completed Visits</th>
                                    <th class="text-end">Reception Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($patients)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                            No patient records found matching your query.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patients as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle-sm bg-success text-white">
                                                        <?php echo strtoupper(substr($p['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo e($p['name']); ?></div>
                                                        <div class="small text-muted">Patient ID #<?php echo $p['id']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small text-dark"><i class="bi bi-telephone me-1 text-muted"></i><?php echo e($p['phone']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-envelope me-1 text-muted"></i><?php echo e($p['email']); ?></div>
                                            </td>
                                            <td>
                                                <span class="small text-dark"><?php echo format_date($p['created_at']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <?php echo (int)$p['total_appointments']; ?> Bookings
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <?php echo (int)$p['completed_appointments']; ?> Completed
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo BASE_URL; ?>/admin/book-appointment.php?patient_id=<?php echo $p['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="Book on behalf of patient">
                                                    <i class="bi bi-calendar-plus me-1"></i> Book Slot
                                                </a>
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
