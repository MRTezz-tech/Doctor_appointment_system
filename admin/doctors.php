<?php
/**
 * Admin Doctor Directory & Management
 * View, Edit, and Activate/Deactivate Doctor Profiles
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$pdo = get_db_connection();

// Handle Status Toggle (Activate / Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $token = $_POST['csrf_token'] ?? '';
    $docId = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);

    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security validation failed.');
    } elseif ($docId) {
        $stmtS = $pdo->prepare("SELECT status FROM doctors WHERE id = ?");
        $stmtS->execute([$docId]);
        $currentStatus = $stmtS->fetchColumn();

        if ($currentStatus !== false) {
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            $stmtUp = $pdo->prepare("UPDATE doctors SET status = ? WHERE id = ?");
            $stmtUp->execute([$newStatus, $docId]);

            $label = ($newStatus === 'active') ? 'Activated' : 'Deactivated';
            set_flash('success', "Doctor profile status updated to: {$label}.");
        }
    }
    header('Location: ' . BASE_URL . '/admin/doctors.php');
    exit;
}

// Fetch all doctors with user info and appointment count
$stmt = $pdo->query("
    SELECT d.*, u.name, u.email, u.phone,
           COUNT(a.id) as total_appointments
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN appointments a ON d.id = a.doctor_id
    GROUP BY d.id
    ORDER BY u.name ASC
");
$doctors = $stmt->fetchAll();

$page_title = 'Doctor Management';
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
                            Practitioners Management
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Doctor Directory</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/add-doctor.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-person-plus-fill me-2"></i> Add New Doctor
                    </a>
                </div>

                <div class="card-body p-4">
                    <?php echo display_flash(); ?>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Doctor Name</th>
                                    <th>Specialization &amp; Degrees</th>
                                    <th>Fee</th>
                                    <th>Working Schedule</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctors)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            No doctors found. Add a doctor profile to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($doctors as $d): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle-sm bg-primary text-white">
                                                        <?php echo strtoupper(substr($d['name'], 4, 1) ?: substr($d['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo e($d['name']); ?></div>
                                                        <div class="small text-muted"><?php echo e($d['email']); ?> &bull; <?php echo e($d['phone']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-primary"><?php echo e($d['specialization']); ?></div>
                                                <div class="small text-muted"><?php echo e($d['qualification']); ?></div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark">₹<?php echo number_format($d['consultation_fee'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div class="small text-dark fw-medium"><?php echo format_time($d['start_time']); ?> - <?php echo format_time($d['end_time']); ?></div>
                                                <div class="small text-muted text-truncate" style="max-width: 150px;" title="<?php echo e($d['available_days']); ?>">
                                                    <?php echo e($d['available_days']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($d['status'] === 'active'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-check-circle me-1"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                        <i class="bi bi-pause-circle me-1"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="<?php echo BASE_URL; ?>/admin/edit-doctor.php?id=<?php echo $d['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="Edit Doctor Profile">
                                                        <i class="bi bi-pencil me-1"></i> Edit
                                                    </a>

                                                    <!-- Status Toggle Form -->
                                                    <form action="<?php echo BASE_URL; ?>/admin/doctors.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="doctor_id" value="<?php echo $d['id']; ?>">
                                                        <?php if ($d['status'] === 'active'): ?>
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 confirm-action" data-confirm="Deactivate this doctor? They will not appear in booking searches." title="Deactivate">
                                                                <i class="bi bi-slash-circle"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-2" title="Activate">
                                                                <i class="bi bi-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
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
