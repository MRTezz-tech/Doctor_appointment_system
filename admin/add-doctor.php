<?php
/**
 * Admin: Add New Doctor Account & Clinical Profile
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$pdo = get_db_connection();
$error = '';

$allWeekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $name           = trim($_POST['name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $password       = $_POST['password'] ?? '';
        $specialization = trim($_POST['specialization'] ?? '');
        $qualification  = trim($_POST['qualification'] ?? '');
        $fee            = filter_input(INPUT_POST, 'consultation_fee', FILTER_VALIDATE_FLOAT);
        $start_time     = trim($_POST['start_time'] ?? '');
        $end_time       = trim($_POST['end_time'] ?? '');
        $duration       = filter_input(INPUT_POST, 'slot_duration', FILTER_VALIDATE_INT) ?: 30;
        $status         = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $bio            = trim($_POST['bio'] ?? '');
        $days           = $_POST['days'] ?? [];

        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($specialization) || empty($qualification)) {
            $error = 'Please fill out all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Temporary password must be at least 6 characters.';
        } elseif ($fee === false || $fee < 0) {
            $error = 'Please enter a valid consultation fee.';
        } elseif (empty($start_time) || empty($end_time) || $start_time >= $end_time) {
            $error = 'Start time must be strictly before end time.';
        } elseif (empty($days)) {
            $error = 'Please select at least one available working day.';
        } else {
            // Check if email already exists
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                $daysString = implode(',', $days);
                $hash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $pdo->beginTransaction();

                    // 1. Insert User
                    $stmtUser = $pdo->prepare("
                        INSERT INTO users (name, email, phone, password, role, created_at)
                        VALUES (?, ?, ?, ?, 'doctor', NOW())
                    ");
                    $stmtUser->execute([$name, $email, $phone, $hash]);
                    $userId = $pdo->lastInsertId();

                    // 2. Insert Doctor Profile
                    $stmtDoc = $pdo->prepare("
                        INSERT INTO doctors (
                            user_id, specialization, qualification, consultation_fee, 
                            available_days, start_time, end_time, slot_duration, status, bio
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtDoc->execute([
                        $userId, $specialization, $qualification, $fee,
                        $daysString, $start_time, $end_time, $duration, $status, $bio
                    ]);

                    $pdo->commit();

                    set_flash('success', "Doctor account for {$name} created successfully!");
                    header('Location: ' . BASE_URL . '/admin/doctors.php');
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Failed to create doctor account: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Add New Doctor Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Form Area -->
        <div class="col-lg-9 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                            Practitioner Registration
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Add New Doctor</h3>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/admin/doctors.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Back to Directory
                    </a>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/admin/add-doctor.php" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <h5 class="fw-bold text-dark mb-3">1. Account Credentials &amp; Contact</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control bg-light" placeholder="Dr. Firstname Lastname" value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control bg-light" placeholder="doctor.name@doccare.com" value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control bg-light" placeholder="9876543210" pattern="[0-9]{10}" value="<?php echo isset($_POST['phone']) ? e($_POST['phone']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Temporary Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control bg-light" placeholder="At least 6 characters" minlength="6" required>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-3 pt-3 border-top">2. Clinical Specialization &amp; Pricing</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Specialization <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" class="form-control bg-light" placeholder="e.g. Cardiologist, Dermatologist, Orthopedic Surgeon" value="<?php echo isset($_POST['specialization']) ? e($_POST['specialization']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Medical Qualifications / Degrees <span class="text-danger">*</span></label>
                                <input type="text" name="qualification" class="form-control bg-light" placeholder="e.g. MBBS, MD, DM (Cardiology)" value="<?php echo isset($_POST['qualification']) ? e($_POST['qualification']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Consultation Fee (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="10" name="consultation_fee" class="form-control bg-light" value="<?php echo isset($_POST['consultation_fee']) ? e($_POST['consultation_fee']) : '500'; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Daily Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control bg-light" value="<?php echo isset($_POST['start_time']) ? e($_POST['start_time']) : '09:00'; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Daily End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control bg-light" value="<?php echo isset($_POST['end_time']) ? e($_POST['end_time']) : '17:00'; ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Slot Duration (Minutes) <span class="text-danger">*</span></label>
                                <select name="slot_duration" class="form-select bg-light">
                                    <option value="15">15 Minutes</option>
                                    <option value="20">20 Minutes</option>
                                    <option value="30" selected>30 Minutes (Standard)</option>
                                    <option value="45">45 Minutes</option>
                                    <option value="60">60 Minutes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Account Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select bg-light">
                                    <option value="active" selected>Active (Visible for Bookings)</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark d-block">Available Consultation Days <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                                <?php foreach ($allWeekdays as $day): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="days[]" value="<?php echo $day; ?>" id="day_<?php echo $day; ?>"
                                               <?php echo in_array($day, ['Monday','Tuesday','Wednesday','Thursday','Friday'], true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small fw-semibold text-dark" for="day_<?php echo $day; ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Clinical Bio &amp; Professional Highlights</label>
                            <textarea name="bio" rows="3" class="form-control bg-light" placeholder="Professional clinical experience, hospital affiliations, patient achievements..."><?php echo isset($_POST['bio']) ? e($_POST['bio']) : ''; ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="<?php echo BASE_URL; ?>/admin/doctors.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-person-check-fill me-2"></i> Register Doctor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
