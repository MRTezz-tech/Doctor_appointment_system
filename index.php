<?php
/**
 * Public Home Page
 * Doctor Appointment System - DocCare Clinic
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Modern Healthcare Booking Portal';

// Fetch active doctors for featured section
$pdo = get_db_connection();
$stmtDoctors = $pdo->query("
    SELECT d.*, u.name, u.email, u.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'active' 
    ORDER BY d.id ASC 
    LIMIT 4
");
$featuredDoctors = $stmtDoctors->fetchAll();

// Quick statistics
$docCount = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'active'")->fetchColumn();
$patientCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$apptCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-shield-check me-1"></i> Verified &amp; Certified Medical Specialists
                </span>
                <h1 class="hero-title mb-4">
                    Book Your Doctor Consultation <span class="text-gradient">Quickly &amp; Effortlessly</span>
                </h1>
                <p class="lead text-secondary mb-4 pe-lg-5">
                    Experience hassle-free medical appointments. Browse top specialists, check real-time slot availability, and confirm your visit in under two minutes with zero wait time.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-5">
                    <?php if (is_logged_in() && get_user_role() === 'patient'): ?>
                        <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">
                            <i class="bi bi-calendar-plus me-2"></i> Book Appointment Now
                        </a>
                    <?php elseif (!is_logged_in()): ?>
                        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">
                            <i class="bi bi-calendar-plus me-2"></i> Book Appointment
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/doctors.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4">
                        <i class="bi bi-search me-2"></i> Browse Specialists
                    </a>
                </div>

                <!-- Trust Stats Strip -->
                <div class="row g-3 pt-3 border-top">
                    <div class="col-4">
                        <h4 class="fw-bold text-dark mb-0"><?php echo (int)$docCount; ?></h4>
                        <span class="text-muted small">Specialist Doctors</span>
                    </div>
                    <div class="col-4 border-start ps-3">
                        <h4 class="fw-bold text-dark mb-0"><?php echo (int)$patientCount; ?></h4>
                        <span class="text-muted small">Registered Patients</span>
                    </div>
                    <div class="col-4 border-start ps-3">
                        <h4 class="fw-bold text-dark mb-0">100%</h4>
                        <span class="text-muted small">Real-Time Slots</span>
                    </div>
                </div>
            </div>

            <!-- Hero Graphic Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 bg-white position-relative">
                    <div class="d-flex align-items-center mb-4">
                        <div class="brand-icon me-3">
                            <i class="bi bi-calendar2-heart-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Quick Slot Check</h5>
                            <span class="text-muted small">Instant availability preview</span>
                        </div>
                    </div>

                    <form action="<?php echo BASE_URL; ?>/doctors.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Specialization / Department</label>
                            <select name="specialization" class="form-select bg-light border-0 py-2">
                                <option value="">All Clinical Departments</option>
                                <option value="Cardiologist">Cardiology (Heart Care)</option>
                                <option value="Dermatologist">Dermatology (Skin &amp; Hair)</option>
                                <option value="Orthopedic Surgeon">Orthopedics (Bones &amp; Joints)</option>
                                <option value="Pediatrician">Pediatrics (Child Healthcare)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted">Consultation Date</label>
                            <input type="date" name="date" class="form-control bg-light border-0 py-2 future-date-only" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold">
                            <i class="bi bi-search me-1"></i> Find Available Doctors
                        </button>
                    </form>

                    <div class="mt-3 p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        <div class="small">
                            <strong>Double-Booking Protection:</strong>
                            <div class="text-muted">Smart slot validation guarantees exclusive reserved time with your doctor.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Clinic Key Features Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">Why DocCare Clinic</span>
            <h2 class="fw-bold">Seamless Healthcare Booking Built for You</h2>
            <p class="text-muted">Designed for maximum patient convenience and clinical transparency.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 bg-light p-4 rounded-4 stat-card">
                    <div class="stat-icon primary mb-3">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Verified Specialists</h5>
                    <p class="text-muted small mb-0">
                        Consult with highly qualified, board-certified medical practitioners with verified credentials and clinical experience.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 bg-light p-4 rounded-4 stat-card">
                    <div class="stat-icon success mb-3">
                        <i class="bi bi-calendar2-range-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Real-Time Slot Engine</h5>
                    <p class="text-muted small mb-0">
                        Choose your exact consultation time. Our server dynamically eliminates already reserved slots to prevent double-booking.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 bg-light p-4 rounded-4 stat-card">
                    <div class="stat-icon info mb-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Live Status Tracking</h5>
                    <p class="text-muted small mb-0">
                        Receive instant status updates (Pending, Confirmed, Completed) on your personal dashboard with complete appointment history.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Doctors Grid -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">Clinical Faculty</span>
                <h2 class="fw-bold mb-0">Featured Doctors</h2>
            </div>
            <a href="<?php echo BASE_URL; ?>/doctors.php" class="btn btn-outline-primary rounded-pill mt-3 mt-md-0">
                View All Doctors <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredDoctors as $doc): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 doctor-card p-3 shadow-sm">
                        <div class="text-center pt-3 pb-2">
                            <div class="doc-avatar-wrapper mb-3">
                                <div class="avatar-circle-lg mx-auto shadow-sm">
                                    <?php echo strtoupper(substr($doc['name'], 4, 1) ?: substr($doc['name'], 0, 1)); ?>
                                </div>
                                <span class="doc-status-dot <?php echo $doc['status']; ?>"></span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1"><?php echo e($doc['name']); ?></h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">
                                <?php echo e($doc['specialization']); ?>
                            </span>
                            <div class="text-muted small mb-3"><?php echo e($doc['qualification']); ?></div>
                        </div>

                        <div class="card-body p-0 border-top pt-3 small text-muted">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="bi bi-cash-coin me-1 text-success"></i> Consultation Fee:</span>
                                <strong class="text-dark">₹<?php echo number_format($doc['consultation_fee'], 2); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="bi bi-clock me-1 text-primary"></i> Working Hours:</span>
                                <span class="text-dark"><?php echo format_time($doc['start_time']); ?> - <?php echo format_time($doc['end_time']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span><i class="bi bi-hourglass me-1 text-warning"></i> Slot Duration:</span>
                                <span class="text-dark"><?php echo e($doc['slot_duration']); ?> mins</span>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="<?php echo BASE_URL; ?>/doctor-profile.php?id=<?php echo $doc['id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                                    <i class="bi bi-info-circle me-1"></i> View Profile
                                </a>
                                <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php?doctor_id=<?php echo $doc['id']; ?>" class="btn btn-primary btn-sm rounded-pill">
                                    <i class="bi bi-calendar-plus me-1"></i> Book Slot
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-3">
        <h2 class="fw-bold mb-3">Need Immediate Clinical Assistance?</h2>
        <p class="lead text-white-50 max-w-700 mx-auto mb-4">
            Our medical reception desk is available to assist walk-in appointments and telephone inquiries.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn-light btn-lg rounded-pill px-4 text-primary fw-semibold shadow-sm">
                <i class="bi bi-telephone-outbound me-2"></i> Contact Clinic Reception
            </a>
            <?php if (!is_logged_in()): ?>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-light btn-lg rounded-pill px-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Patient Portal Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
