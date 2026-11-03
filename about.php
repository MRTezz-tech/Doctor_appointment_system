<?php
/**
 * About Clinic & System Page
 * Doctor Appointment System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'About DocCare Medical Clinic';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-5 bg-white">
        <div class="card-body p-4 p-md-5 text-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                About Our Healthcare Center
            </span>
            <h1 class="fw-bold mb-3">Compassionate Care Driven by Modern Technology</h1>
            <p class="lead text-secondary max-w-700 mx-auto mb-0">
                DocCare is a premier multi-specialty clinical facility committed to providing accessible, reliable, and patient-centered medical consultations.
            </p>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                <div class="stat-icon primary mb-3">
                    <i class="bi bi-bullseye"></i>
                </div>
                <h4 class="fw-bold text-dark mb-3">Our Clinical Mission</h4>
                <p class="text-secondary leading-relaxed mb-0">
                    To eliminate physical waiting queues and scheduling uncertainties by connecting patients with board-certified physicians through our smart appointment booking engine. Every patient receives attentive, dignified, and timely medical care.
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                <div class="stat-icon success mb-3">
                    <i class="bi bi-eye-fill"></i>
                </div>
                <h4 class="fw-bold text-dark mb-3">Our Vision</h4>
                <p class="text-secondary leading-relaxed mb-0">
                    To establish a digital healthcare benchmark where patient engagement, doctor practice management, and clinic reception operate as a synchronized, transparent ecosystem with zero scheduling conflicts.
                </p>
            </div>
        </div>
    </div>

    <!-- System Highlights (College Project Specs) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark">System Architecture Highlights</h4>
            <p class="text-muted small">Key capabilities of the DocCare Doctor Appointment System</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3 col-6 text-center">
                <div class="p-3 bg-light rounded-4">
                    <i class="bi bi-shield-lock-fill text-primary fs-2 mb-2"></i>
                    <h6 class="fw-bold mb-1">Role-Based Access</h6>
                    <span class="small text-muted">Admin, Doctor &amp; Patient separation</span>
                </div>
            </div>
            <div class="col-md-3 col-6 text-center">
                <div class="p-3 bg-light rounded-4">
                    <i class="bi bi-calendar2-check-fill text-success fs-2 mb-2"></i>
                    <h6 class="fw-bold mb-1">Zero Double-Booking</h6>
                    <span class="small text-muted">Real-time atomic slot validation</span>
                </div>
            </div>
            <div class="col-md-3 col-6 text-center">
                <div class="p-3 bg-light rounded-4">
                    <i class="bi bi-file-earmark-lock-fill text-warning fs-2 mb-2"></i>
                    <h6 class="fw-bold mb-1">CSRF &amp; XSS Secure</h6>
                    <span class="small text-muted">Prepared PDO statements &amp; tokens</span>
                </div>
            </div>
            <div class="col-md-3 col-6 text-center">
                <div class="p-3 bg-light rounded-4">
                    <i class="bi bi-phone-fill text-info fs-2 mb-2"></i>
                    <h6 class="fw-bold mb-1">Responsive UI</h6>
                    <span class="small text-muted">Fluid on mobile, tablet &amp; desktop</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
