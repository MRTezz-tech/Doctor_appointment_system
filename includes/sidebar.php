<?php
/**
 * Dashboard Sidebar Component
 * Supports Patient, Doctor, and Admin / Receptionist Portals
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = get_logged_in_user();
if (!$user) return;

$role = $user['role'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Retrieve notification counts if applicable
$pdo = get_db_connection();
$pendingCount = 0;

if ($role === 'doctor') {
    // Count pending appointments for this doctor
    if (!empty($user['doctor_id'])) {
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Pending'");
        $stmtC->execute([$user['doctor_id']]);
        $pendingCount = (int)$stmtC->fetchColumn();
    }
} elseif ($role === 'admin') {
    // Count all pending appointments in the clinic
    $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();
}
?>
<div class="card border-0 shadow-sm rounded-4 mb-4 dashboard-sidebar">
    <!-- User Quick Summary Card -->
    <div class="card-body p-4 text-center border-bottom bg-gradient-light rounded-top-4">
        <div class="avatar-circle-lg mx-auto mb-3 shadow-sm">
            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
        </div>
        <h6 class="fw-bold text-dark mb-1"><?php echo e($user['name']); ?></h6>
        <p class="text-muted small mb-2"><?php echo e($user['email']); ?></p>
        <span class="badge bg-primary text-uppercase px-3 py-1 font-monospace" style="font-size: 0.75rem; letter-spacing: 0.5px;">
            <i class="bi bi-shield-check me-1"></i><?php echo e($role); ?>
        </span>
    </div>

    <!-- Navigation List -->
    <div class="list-group list-group-flush p-2">
        <?php if ($role === 'patient'): ?>
            <a href="<?php echo BASE_URL; ?>/patient/index.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill me-3 fs-5"></i>
                <span class="fw-medium">Dashboard Overview</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'book-appointment.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-plus-fill me-3 fs-5"></i>
                <span class="fw-medium">Book Appointment</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/patient/my-appointments.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'my-appointments.php') ? 'active' : ''; ?>">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar2-check-fill me-3 fs-5"></i>
                    <span class="fw-medium">My Appointments</span>
                </div>
            </a>
            <a href="<?php echo BASE_URL; ?>/doctors.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0">
                <i class="bi bi-search me-3 fs-5"></i>
                <span class="fw-medium">Browse Doctors</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/patient/profile.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-gear me-3 fs-5"></i>
                <span class="fw-medium">Profile Settings</span>
            </a>

        <?php elseif ($role === 'doctor'): ?>
            <a href="<?php echo BASE_URL; ?>/doctor/index.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-3 fs-5"></i>
                <span class="fw-medium">Doctor Dashboard</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/doctor/pending-requests.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'pending-requests.php') ? 'active' : ''; ?>">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock-history me-3 fs-5"></i>
                    <span class="fw-medium">Pending Requests</span>
                </div>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-danger rounded-pill px-2"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo BASE_URL; ?>/doctor/appointments.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'appointments.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill me-3 fs-5"></i>
                <span class="fw-medium">Confirmed Schedule</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/doctor/history.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'history.php') ? 'active' : ''; ?>">
                <i class="bi bi-journal-medical me-3 fs-5"></i>
                <span class="fw-medium">Patient History</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/doctor/profile.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar2-range-fill me-3 fs-5"></i>
                <span class="fw-medium">Schedule & Fee Settings</span>
            </a>

        <?php elseif ($role === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-3 fs-5"></i>
                <span class="fw-medium">Dashboard Overview</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/appointments.php" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'appointments.php') ? 'active' : ''; ?>">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar2-check-fill me-3 fs-5"></i>
                    <span class="fw-medium">All Appointments</span>
                </div>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill px-2"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/book-appointment.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'book-appointment.php') ? 'active' : ''; ?>">
                <i class="bi bi-ticket-perforated-fill me-3 fs-5"></i>
                <span class="fw-medium">Walk-in Booking</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/doctors.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'doctors.php' || $currentPage === 'add-doctor.php' || $currentPage === 'edit-doctor.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-badge-fill me-3 fs-5"></i>
                <span class="fw-medium">Doctor Management</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/patients.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 rounded-3 mb-1 border-0 <?php echo ($currentPage === 'patients.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill me-3 fs-5"></i>
                <span class="fw-medium">Patient Directory</span>
            </a>
        <?php endif; ?>

        <div class="border-top my-2 pt-2">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 rounded-3 border-0 text-danger">
                <i class="bi bi-box-arrow-right me-3 fs-5"></i>
                <span class="fw-medium">Log Out</span>
            </a>
        </div>
    </div>
</div>
