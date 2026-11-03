<?php
/**
 * Detailed Doctor Profile View
 * Doctor Appointment System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$doctor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$doctor_id) {
    header('Location: ' . BASE_URL . '/doctors.php');
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("
    SELECT d.*, u.name, u.email, u.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.id = ? LIMIT 1
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    $_SESSION['flash_error'] = 'Requested doctor profile not found.';
    header('Location: ' . BASE_URL . '/doctors.php');
    exit;
}

$page_title = $doctor['name'] . ' - Clinical Profile';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/doctors.php" class="text-decoration-none">Doctors</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e($doctor['name']); ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Left: Profile Summary Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white sticky-top" style="top: 90px;">
                <div class="doc-avatar-wrapper mx-auto mb-3">
                    <div class="avatar-circle-lg mx-auto shadow" style="width: 100px; height: 100px; font-size: 2.2rem;">
                        <?php echo strtoupper(substr($doctor['name'], 4, 1) ?: substr($doctor['name'], 0, 1)); ?>
                    </div>
                    <span class="doc-status-dot <?php echo $doctor['status']; ?>" style="width: 18px; height: 18px;"></span>
                </div>

                <h4 class="fw-bold text-dark mb-1"><?php echo e($doctor['name']); ?></h4>
                <div class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1 mb-2">
                    <?php echo e($doctor['specialization']); ?>
                </div>
                <p class="text-muted small mb-3"><?php echo e($doctor['qualification']); ?></p>

                <div class="p-3 bg-light rounded-3 text-start mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small"><i class="bi bi-wallet2 text-success me-1"></i> Consultation Fee:</span>
                        <strong class="text-dark">₹<?php echo number_format($doctor['consultation_fee'], 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small"><i class="bi bi-clock text-primary me-1"></i> Daily Timings:</span>
                        <span class="text-dark small fw-semibold"><?php echo format_time($doctor['start_time']); ?> - <?php echo format_time($doctor['end_time']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small"><i class="bi bi-hourglass text-warning me-1"></i> Slot Length:</span>
                        <span class="text-dark small fw-semibold"><?php echo e($doctor['slot_duration']); ?> Minutes</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary py-2 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-calendar-plus me-1"></i> Book Appointment Now
                    </a>
                    <a href="<?php echo BASE_URL; ?>/doctors.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i> Back to Doctors List
                    </a>
                </div>
            </div>
        </div>

        <!-- Right: Detailed Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                <h4 class="fw-bold text-dark mb-3">About Dr. <?php echo e(explode(' ', $doctor['name'])[1] ?? $doctor['name']); ?></h4>
                <p class="text-secondary leading-relaxed mb-4">
                    <?php echo nl2br(e($doctor['bio'] ?: 'A distinguished practitioner dedicated to evidence-based healthcare delivery, diagnostic excellence, and individualized patient consultation.')); ?>
                </p>

                <h5 class="fw-bold text-dark mb-3 pt-3 border-top">Clinical Availability</h5>
                <p class="text-muted small mb-3">
                    Patients can schedule visits during standard working days. Appointments are regulated by dynamic slot intervals to minimize clinic waiting time.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 bg-light">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-calendar-week me-2"></i>Consultation Days</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <?php 
                                $days = explode(',', $doctor['available_days']);
                                foreach ($days as $day): 
                                    $dayTrim = trim($day);
                                ?>
                                    <span class="badge bg-white text-dark border shadow-xs"><?php echo e($dayTrim); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 bg-light">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-shield-check me-2"></i>Status &amp; Verification</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                                    <i class="bi bi-check2-circle me-1"></i> Certified Specialist
                                </span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1">
                                    In-Person Clinic Visit
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h6 class="fw-bold text-primary mb-1">Ready to consult with <?php echo e($doctor['name']); ?>?</h6>
                        <span class="small text-muted">Select an available date and pick an exclusive time slot without double-booking.</span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary rounded-pill px-4">
                        Book Slot <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
