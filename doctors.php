<?php
/**
 * Browse Doctors Directory
 * Filtering by Specialization and Keyword Search
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Find & Browse Doctors';

$search = trim($_GET['search'] ?? '');
$specialization = trim($_GET['specialization'] ?? '');

$pdo = get_db_connection();

// Build query with search & filter
$sql = "
    SELECT d.*, u.name, u.email, u.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'active'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR d.specialization LIKE ? OR d.qualification LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($specialization)) {
    $sql .= " AND d.specialization = ?";
    $params[] = $specialization;
}

$sql .= " ORDER BY u.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get unique specializations for filter dropdown
$specStmt = $pdo->query("SELECT DISTINCT specialization FROM doctors WHERE status = 'active' ORDER BY specialization ASC");
$specializations = $specStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <!-- Header & Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                        Medical Directory
                    </span>
                    <h2 class="fw-bold mb-1">Our Medical Specialists</h2>
                    <p class="text-muted mb-0">Browse our experienced doctor faculty, check schedules, and book your consultation.</p>
                </div>

                <div class="col-lg-6">
                    <form action="<?php echo BASE_URL; ?>/doctors.php" method="GET" class="row g-2">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Doctor name or keyword..." value="<?php echo e($search); ?>">
                            </div>
                        </div>

                        <div class="col-md-5">
                            <select name="specialization" class="form-select bg-light">
                                <option value="">All Specialties</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo e($spec); ?>" <?php echo ($specialization === $spec) ? 'selected' : ''; ?>>
                                        <?php echo e($spec); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <?php if (!empty($search) || !empty($specialization)): ?>
                                <a href="<?php echo BASE_URL; ?>/doctors.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                    <i class="bi bi-x-circle me-1"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                                <i class="bi bi-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctors Cards Grid -->
    <div class="row g-4">
        <?php if (empty($doctors)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
                    <div class="card-body">
                        <i class="bi bi-person-x text-muted" style="font-size: 3.5rem;"></i>
                        <h4 class="fw-bold text-dark mt-3">No Doctors Found</h4>
                        <p class="text-muted max-w-500 mx-auto">We couldn't find any active doctors matching your search criteria. Try resetting filters or searching with another term.</p>
                        <a href="<?php echo BASE_URL; ?>/doctors.php" class="btn btn-primary rounded-pill px-4">
                            View All Doctors
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($doctors as $doc): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 doctor-card p-4 shadow-sm">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="doc-avatar-wrapper flex-shrink-0">
                                <div class="avatar-circle-lg shadow-sm">
                                    <?php echo strtoupper(substr($doc['name'], 4, 1) ?: substr($doc['name'], 0, 1)); ?>
                                </div>
                                <span class="doc-status-dot active"></span>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><?php echo e($doc['name']); ?></h5>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1">
                                    <?php echo e($doc['specialization']); ?>
                                </span>
                                <div class="text-muted small"><?php echo e($doc['qualification']); ?></div>
                            </div>
                        </div>

                        <p class="small text-secondary mb-3 flex-grow-1" style="min-height: 40px;">
                            <?php echo e(mb_strimwidth($doc['bio'] ?? 'Dedicated specialist focused on comprehensive diagnostics and modern patient care.', 0, 110, '...')); ?>
                        </p>

                        <div class="bg-light p-3 rounded-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="bi bi-cash-stack text-success me-1"></i> Consultation Fee:</span>
                                <strong class="text-dark">₹<?php echo number_format($doc['consultation_fee'], 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="bi bi-clock text-primary me-1"></i> Hours:</span>
                                <span class="text-dark"><?php echo format_time($doc['start_time']); ?> - <?php echo format_time($doc['end_time']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="bi bi-calendar3 text-info me-1"></i> Working Days:</span>
                                <span class="text-dark text-truncate ms-2" title="<?php echo e($doc['available_days']); ?>"><?php echo e($doc['available_days']); ?></span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>/doctor-profile.php?id=<?php echo $doc['id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                                <i class="bi bi-person-lines-fill me-1"></i> View Doctor Profile
                            </a>
                            <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php?doctor_id=<?php echo $doc['id']; ?>" class="btn btn-primary btn-sm rounded-pill">
                                <i class="bi bi-calendar-plus me-1"></i> Book Appointment
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
