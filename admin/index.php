<?php
/**
 * Administrator & Receptionist Dashboard
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$admin = get_logged_in_user();
$pdo = get_db_connection();

// 1. Fetch System Overview Statistics
$totalDocs = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$activeDocs = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'active'")->fetchColumn();
$totalPatients = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();

$stmtApptStats = $pdo->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) as today_appointments,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_appointments,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments
    FROM appointments
");
$apptStats = $stmtApptStats->fetch() ?: [
    'total_appointments' => 0,
    'today_appointments' => 0,
    'pending_requests'   => 0,
    'confirmed_appointments' => 0,
    'completed_appointments' => 0
];

// 2. Fetch Recent Appointments Across Clinic
$stmtRecent = $pdo->query("
    SELECT a.*, d.specialization, 
           u_doc.name as doctor_name, 
           u_pat.name as patient_name, u_pat.phone as patient_phone
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u_doc ON d.user_id = u_doc.id
    JOIN users u_pat ON a.patient_id = u_pat.id
    ORDER BY a.created_at DESC
    LIMIT 6
");
$recentAppointments = $stmtRecent->fetchAll();

$page_title = 'Admin Management Dashboard';
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
            <!-- Header Banner -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Administration &amp; Reception Desk
                        </span>
                        <h3 class="fw-bold text-dark mb-1">Hospital Control Center</h3>
                        <p class="text-muted small mb-0">Live statistics and clinical operations control.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo BASE_URL; ?>/admin/book-appointment.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                            <i class="bi bi-ticket-perforated me-1"></i> Walk-in Booking
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/add-doctor.php" class="btn btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-person-plus me-1"></i> Add Doctor
                        </a>
                    </div>
                </div>
            </div>

            <?php echo display_flash(); ?>

            <!-- Stat Cards Row 1 -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Total Doctors</span>
                            <div class="stat-icon primary"><i class="bi bi-person-badge"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $totalDocs; ?></h3>
                        <span class="small text-success"><i class="bi bi-check-circle me-1"></i><?php echo $activeDocs; ?> Active Faculty</span>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Registered Patients</span>
                            <div class="stat-icon success"><i class="bi bi-people"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $totalPatients; ?></h3>
                        <span class="small text-muted">Active patient profiles</span>
                    </div>
                </div>

                <div class="col-md-4 col-sm-12">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Today's Appointments</span>
                            <div class="stat-icon info"><i class="bi bi-calendar-event"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-primary"><?php echo (int)$apptStats['today_appointments']; ?></h3>
                        <span class="small text-muted">Scheduled for <?php echo date('d M'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stat Cards Row 2 (Appointment Status Breakup) -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-sm-6">
                    <div class="stat-card border-warning">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-warning-emphasis small fw-bold">Pending Requests</span>
                            <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-warning"><?php echo (int)$apptStats['pending_requests']; ?></h3>
                        <span class="small text-muted">Awaiting confirmation</span>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6">
                    <div class="stat-card border-primary">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-primary small fw-bold">Confirmed Visits</span>
                            <div class="stat-icon primary"><i class="bi bi-calendar2-check"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-primary"><?php echo (int)$apptStats['confirmed_appointments']; ?></h3>
                        <span class="small text-muted">Active consultations</span>
                    </div>
                </div>

                <div class="col-md-4 col-sm-12">
                    <div class="stat-card border-success">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-success small fw-bold">Completed Visits</span>
                            <div class="stat-icon success"><i class="bi bi-check2-all"></i></div>
                        </div>
                        <h3 class="fw-bold mb-0 text-success"><?php echo (int)$apptStats['completed_appointments']; ?></h3>
                        <span class="small text-muted">Successful consults</span>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments Summary Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Latest Clinic Appointments</h5>
                        <span class="text-muted small">Real-time booking transactions across all doctors</span>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/appointments.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        Manage All Appointments <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No appointments recorded yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAppointments as $appt): ?>
                                    <tr>
                                        <td class="text-muted fw-semibold">#<?php echo $appt['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo e($appt['patient_name']); ?></div>
                                            <div class="small text-muted"><?php echo e($appt['patient_phone']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo e($appt['doctor_name']); ?></div>
                                            <div class="small text-muted"><?php echo e($appt['specialization']); ?></div>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium"><?php echo format_date($appt['appointment_date']); ?></div>
                                            <div class="small text-muted"><?php echo format_time($appt['appointment_time']); ?></div>
                                        </td>
                                        <td>
                                            <?php echo status_badge($appt['status']); ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo BASE_URL; ?>/admin/appointments.php?search=<?php echo urlencode($appt['patient_name']); ?>" class="btn btn-light btn-sm border rounded-pill px-3">
                                                Manage
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
