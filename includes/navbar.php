<?php
/**
 * Main Top Navigation Bar
 * Responsive with Active Link Highlights & User Dropdown
 */

$current_page = basename($_SERVER['PHP_SELF']);
$currentUser = get_logged_in_user();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top main-navbar shadow-sm">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/index.php">
            <span class="brand-icon me-2">
                <i class="bi bi-hospital-fill"></i>
            </span>
            <span class="brand-text">
                <span class="brand-primary">Doc</span><span class="brand-secondary">Care</span>
                <span class="brand-tagline d-none d-sm-inline">Clinic</span>
            </span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'index.php' && !strpos($_SERVER['PHP_SELF'], 'patient') && !strpos($_SERVER['PHP_SELF'], 'doctor') && !strpos($_SERVER['PHP_SELF'], 'admin')) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'doctors.php' || $current_page === 'doctor-profile.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/doctors.php">
                        <i class="bi bi-person-badge me-1"></i> Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/about.php">
                        <i class="bi bi-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/contact.php">
                        <i class="bi bi-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <!-- Right-side actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($currentUser): ?>
                    <!-- Quick Dashboard Link -->
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-speedometer2 me-1"></i> Admin Panel
                        </a>
                    <?php elseif ($currentUser['role'] === 'doctor'): ?>
                        <a href="<?php echo BASE_URL; ?>/doctor/index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-calendar2-week me-1"></i> Doctor Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/patient/book-appointment.php" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-plus-circle me-1"></i> Book Appointment
                        </a>
                    <?php endif; ?>

                    <!-- User Account Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-pill border dropdown-toggle d-flex align-items-center gap-2 px-3 py-1" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-circle-sm bg-primary text-white">
                                <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline fw-medium text-dark"><?php echo e($currentUser['name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3" aria-labelledby="userMenuBtn">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-dark"><?php echo e($currentUser['name']); ?></div>
                                <div class="small text-muted"><?php echo e($currentUser['email']); ?></div>
                                <span class="badge bg-primary-subtle text-primary mt-1 text-uppercase font-monospace" style="font-size: 0.7rem;">
                                    <?php echo e($currentUser['role']); ?>
                                </span>
                            </li>
                            
                            <?php if ($currentUser['role'] === 'patient'): ?>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/patient/index.php"><i class="bi bi-grid-fill me-2 text-primary"></i> Dashboard</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/patient/my-appointments.php"><i class="bi bi-calendar-event me-2 text-primary"></i> My Appointments</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/patient/profile.php"><i class="bi bi-person me-2 text-primary"></i> Profile</a></li>
                            <?php elseif ($currentUser['role'] === 'doctor'): ?>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/doctor/index.php"><i class="bi bi-grid-fill me-2 text-primary"></i> Doctor Dashboard</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/doctor/pending-requests.php"><i class="bi bi-clock-history me-2 text-warning"></i> Pending Requests</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/doctor/profile.php"><i class="bi bi-sliders me-2 text-primary"></i> Practice Schedule</a></li>
                            <?php elseif ($currentUser['role'] === 'admin'): ?>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/admin/index.php"><i class="bi bi-speedometer2 me-2 text-primary"></i> Admin Overview</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/admin/appointments.php"><i class="bi bi-calendar-check me-2 text-primary"></i> Manage Appointments</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/admin/doctors.php"><i class="bi bi-person-badge me-2 text-primary"></i> Doctors Directory</a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                    </a>
                    <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
