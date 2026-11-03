<?php
/**
 * Doctor Profile, Schedule & Practice Settings
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Doctor Role
check_auth(['doctor']);

$user = get_logged_in_user();
$pdo = get_db_connection();

$stmtDoc = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
$stmtDoc->execute([$user['id']]);
$doctor = $stmtDoc->fetch();

if (!$doctor) {
    die("Doctor profile configuration missing.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_schedule') {
            $fee          = filter_input(INPUT_POST, 'consultation_fee', FILTER_VALIDATE_FLOAT);
            $start_time   = trim($_POST['start_time'] ?? '');
            $end_time     = trim($_POST['end_time'] ?? '');
            $duration     = filter_input(INPUT_POST, 'slot_duration', FILTER_VALIDATE_INT);
            $status       = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
            $bio          = trim($_POST['bio'] ?? '');
            $selectedDays = $_POST['days'] ?? [];

            if ($fee === false || $fee < 0) {
                $error = 'Please enter a valid consultation fee.';
            } elseif (empty($start_time) || empty($end_time) || $start_time >= $end_time) {
                $error = 'Start time must be strictly before end time.';
            } elseif (empty($selectedDays)) {
                $error = 'Please select at least one available working day.';
            } else {
                $daysString = implode(',', $selectedDays);

                $stmtUp = $pdo->prepare("
                    UPDATE doctors 
                    SET consultation_fee = ?, available_days = ?, start_time = ?, end_time = ?, slot_duration = ?, status = ?, bio = ?
                    WHERE id = ?
                ");
                $stmtUp->execute([$fee, $daysString, $start_time, $end_time, $duration, $status, $bio, $doctor['id']]);

                set_flash('success', 'Practice schedule and settings updated successfully.');
                header('Location: ' . BASE_URL . '/doctor/profile.php');
                exit;
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                $error = 'Please enter both your current and new password.';
            } elseif (strlen($newPass) < 6) {
                $error = 'New password must be at least 6 characters.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'Password confirmation does not match.';
            } else {
                $stmtP = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmtP->execute([$user['id']]);
                $dbPass = $stmtP->fetchColumn();

                if ($dbPass && password_verify($currentPass, $dbPass)) {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmtUpdatePass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmtUpdatePass->execute([$newHash, $user['id']]);

                    set_flash('success', 'Password updated successfully!');
                    header('Location: ' . BASE_URL . '/doctor/profile.php');
                    exit;
                } else {
                    $error = 'Current password is incorrect.';
                }
            }
        }
    }
}

// Re-fetch doctor profile details
$stmtDoc->execute([$user['id']]);
$doctor = $stmtDoc->fetch();

$currentDays = array_map('trim', explode(',', $doctor['available_days']));
$allWeekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$page_title = 'Practice & Schedule Settings';
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
                <div class="card-header bg-white border-bottom p-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-1">
                        Consultation Configuration
                    </span>
                    <h3 class="fw-bold text-dark mb-0">Practice &amp; Schedule Settings</h3>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php echo display_flash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Schedule Configuration Form -->
                    <form action="<?php echo BASE_URL; ?>/doctor/profile.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update_schedule">

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Practitioner Name</label>
                                <input type="text" class="form-control bg-light" value="<?php echo e($user['name']); ?>" disabled readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Specialization &amp; Degrees</label>
                                <input type="text" class="form-control bg-light" value="<?php echo e($doctor['specialization'] . ' (' . $doctor['qualification'] . ')'); ?>" disabled readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Consultation Fee (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="10" name="consultation_fee" class="form-control bg-light" value="<?php echo e($doctor['consultation_fee']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Daily Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control bg-light" value="<?php echo e(substr($doctor['start_time'], 0, 5)); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Daily End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control bg-light" value="<?php echo e(substr($doctor['end_time'], 0, 5)); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Slot Duration <span class="text-danger">*</span></label>
                                <select name="slot_duration" class="form-select bg-light">
                                    <option value="15" <?php echo ($doctor['slot_duration'] == 15) ? 'selected' : ''; ?>>15 Minutes per Slot</option>
                                    <option value="20" <?php echo ($doctor['slot_duration'] == 20) ? 'selected' : ''; ?>>20 Minutes per Slot</option>
                                    <option value="30" <?php echo ($doctor['slot_duration'] == 30) ? 'selected' : ''; ?>>30 Minutes per Slot (Standard)</option>
                                    <option value="45" <?php echo ($doctor['slot_duration'] == 45) ? 'selected' : ''; ?>>45 Minutes per Slot</option>
                                    <option value="60" <?php echo ($doctor['slot_duration'] == 60) ? 'selected' : ''; ?>>60 Minutes per Slot</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Practice Status</label>
                                <select name="status" class="form-select bg-light">
                                    <option value="active" <?php echo ($doctor['status'] === 'active') ? 'selected' : ''; ?>>Active (Accepting Appointments)</option>
                                    <option value="inactive" <?php echo ($doctor['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive (Temporarily on Leave)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Available Working Days Checkboxes -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark d-block">Available Consultation Days <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded-3 border">
                                <?php foreach ($allWeekdays as $day): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="days[]" value="<?php echo $day; ?>" id="day_<?php echo $day; ?>"
                                               <?php echo in_array($day, $currentDays, true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small fw-semibold text-dark" for="day_<?php echo $day; ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bio / Description -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Clinical Bio &amp; Practice Highlights</label>
                            <textarea name="bio" rows="3" class="form-control bg-light" placeholder="Describe your clinical specialization, degrees, and hospital background..."><?php echo e($doctor['bio']); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Save Practice Settings
                        </button>
                    </form>

                    <!-- Change Password Form -->
                    <h5 class="fw-bold text-dark mb-3 pt-5 border-top">Change Account Password</h5>
                    <form action="<?php echo BASE_URL; ?>/doctor/profile.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Current Password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control bg-light" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">New Password <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control bg-light" minlength="6" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control bg-light" minlength="6" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-key me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
