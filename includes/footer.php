<?php
/**
 * Master Footer Template
 * Doctor Appointment System
 */
?>
<footer class="footer bg-white border-top mt-auto py-5 text-secondary">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Brand & Mission -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <span class="brand-icon me-2">
                        <i class="bi bi-hospital-fill"></i>
                    </span>
                    <span class="brand-text fs-4">
                        <span class="brand-primary">Doc</span><span class="brand-secondary">Care</span>
                    </span>
                </div>
                <p class="small text-muted pe-lg-4">
                    Providing top-tier digital healthcare booking solutions. Connecting verified clinical specialists with patients seamlessly, ensuring timely consultations with zero waiting hassle.
                </p>
                <div class="d-flex gap-3 text-primary fs-5">
                    <a href="#" class="text-primary hover-scale"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-primary hover-scale"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-primary hover-scale"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="text-primary hover-scale"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-dark fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?php echo BASE_URL; ?>/index.php" class="text-decoration-none text-muted hover-link">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/doctors.php" class="text-decoration-none text-muted hover-link">Find Doctors</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/about.php" class="text-decoration-none text-muted hover-link">About Clinic</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/contact.php" class="text-decoration-none text-muted hover-link">Contact Us</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/login.php" class="text-decoration-none text-muted hover-link">Portal Login</a></li>
                </ul>
            </div>

            <!-- Medical Specializations -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="text-dark fw-bold mb-3">Departments</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?php echo BASE_URL; ?>/doctors.php?specialization=Cardiologist" class="text-decoration-none text-muted hover-link"><i class="bi bi-heart me-1 text-danger"></i> Cardiology</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/doctors.php?specialization=Dermatologist" class="text-decoration-none text-muted hover-link"><i class="bi bi-shield me-1 text-warning"></i> Dermatology</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/doctors.php?specialization=Orthopedic+Surgeon" class="text-decoration-none text-muted hover-link"><i class="bi bi-activity me-1 text-info"></i> Orthopedics</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/doctors.php?specialization=Pediatrician" class="text-decoration-none text-muted hover-link"><i class="bi bi-emoji-smile me-1 text-success"></i> Pediatrics</a></li>
                </ul>
            </div>

            <!-- Clinic Contact & Timings -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-dark fw-bold mb-3">Clinic Information</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li class="d-flex align-items-start">
                        <i class="bi bi-geo-alt-fill text-primary me-2 mt-1"></i>
                        <span>Medical Enclave, Healthcare Blvd, Central City</span>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="bi bi-telephone-fill text-primary me-2"></i>
                        <span>+91 98765 43210 / 022-2800-CARE</span>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="bi bi-envelope-fill text-primary me-2"></i>
                        <span>appointments@doccare.com</span>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="bi bi-clock-fill text-primary me-2"></i>
                        <span>Mon - Sat: 08:00 AM - 08:00 PM</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-top pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
            <div>
                &copy; <?php echo date('Y'); ?> <strong>DocCare Clinic</strong>. All rights reserved. Built for MCA Academic Project.
            </div>
            <div>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-shield-lock-fill text-success me-1"></i> Secure PHP &amp; MySQL
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.3 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Application Scripts -->
<script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
