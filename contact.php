<?php
/**
 * Contact Us Page
 * Doctor Appointment System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Contact Clinic & Reception';

$message_sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please refresh the page.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $msg     = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($msg)) {
            $error = 'Please fill out all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } else {
            // For project demonstration: confirmation flash message
            set_flash('success', "Thank you, {$name}. Your message regarding '{$subject}' has been received. Our clinic reception will contact you shortly.");
            header('Location: ' . BASE_URL . '/contact.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4 mb-5 bg-white">
        <div class="card-body p-4 p-md-5 text-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                Get In Touch
            </span>
            <h1 class="fw-bold mb-2">Contact DocCare Medical Reception</h1>
            <p class="text-secondary max-w-700 mx-auto mb-0">
                Have questions regarding consultations, doctor availability, or emergency inquiries? Our medical reception desk is available 6 days a week.
            </p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Contact Information Cards -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-4">Clinic Reception Details</h5>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="stat-icon primary flex-shrink-0">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Our Location</h6>
                        <p class="text-muted small mb-0">Plot 42, Medical Enclave, Healthcare Boulevard, Central City - 400001</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="stat-icon success flex-shrink-0">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Telephone &amp; Helpline</h6>
                        <p class="text-muted small mb-0">+91 98765 43210 (Reception)</p>
                        <p class="text-muted small mb-0">022-2800-CARE (Toll Free Desk)</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="stat-icon info flex-shrink-0">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Email Inquiries</h6>
                        <p class="text-muted small mb-0">appointments@doccare.com</p>
                        <p class="text-muted small mb-0">reception@doccare.com</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="stat-icon warning flex-shrink-0">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">OPD Hours</h6>
                        <p class="text-muted small mb-0">Monday – Saturday: 08:00 AM – 08:00 PM</p>
                        <p class="text-muted small mb-0 text-danger fw-semibold">Sunday: Emergency On-Call Only</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <h4 class="fw-bold text-dark mb-2">Send an Inquiry</h4>
                <p class="text-muted small mb-4">Our clinical staff responds to patient inquiries within 2-4 business hours.</p>

                <?php echo display_flash(); ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>/contact.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Your Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control bg-light" placeholder="Rahul Verma" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control bg-light" placeholder="rahul@example.com" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Phone Number</label>
                            <input type="tel" name="phone" class="form-control bg-light" placeholder="9876543210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control bg-light" placeholder="Inquiry about doctor availability" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">Message Details <span class="text-danger">*</span></label>
                        <textarea name="message" rows="4" class="form-control bg-light" placeholder="Describe your question or consultation requirement..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-send me-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
