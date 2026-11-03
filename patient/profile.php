<?php
/**
 * Patient Profile Settings
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Patient Role
check_auth(['patient']);

$patient = get_logged_in_user();
$pdo = get_db_connection();
$patientId = $patient['id'];

$error = '';
$success = '';

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name  = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (empty($name) || empty($phone)) {
                $error = 'Name and Phone number cannot be empty.';
            } else {
                $stmtUp = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmtUp->execute([$name, $phone, $patientId]);

                $_SESSION['user_name'] = $name;
                $_SESSION['user_phone'] = $phone;
                set_flash('success', 'Profile information updated successfully.');
                header('Location: ' . BASE_URL . '/patient/profile.php');
                exit;
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                $error = 'Please enter both your current and new password.';
            } elseif (strlen($newPass) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'New password and confirmation do not match.';
            } else {
                // Verify current password
                $stmtP = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmtP->execute([$patientId]);
                $dbPass = $stmtP->fetchColumn();

                if ($dbPass && password_verify($currentPass, $dbPass)) {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmtUpdatePass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmtUpdatePass->execute([$newHash, $patientId]);

                    set_flash('success', 'Password updated successfully!');
                    header('Location: ' . BASE_URL . '/patient/profile.php');
                    exit;
                } else {
                    $error = 'Current password is incorrect.';
                }
            }
        }
    }
}

// Fetch current patient details
$stmtFetch = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtFetch->execute([$patientId]);
$userData = $stmtFetch->fetch();

$page_title = 'Patient Profile Settings';
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
                        Account Management
                    </span>
                    <h3 class="fw-bold text-dark mb-0">Profile Settings</h3>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php echo display_flash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Personal Information Form -->
                    <h5 class="fw-bold text-dark mb-3">Personal Details</h5>
                    <form action="<?php echo BASE_URL; ?>/patient/profile.php" method="POST" class="mb-5">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control bg-light" value="<?php echo e($userData['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control bg-light" value="<?php echo e($userData['phone']); ?>" required pattern="[0-9]{10}">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Email Address</label>
                                <input type="email" class="form-control bg-light" value="<?php echo e($userData['email']); ?>" disabled readonly>
                                <div class="form-text small">Email cannot be changed directly for security reasons.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Member Since</label>
                                <input type="text" class="form-control bg-light" value="<?php echo format_date($userData['created_at']); ?>" disabled readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </form>

                    <!-- Change Password Form -->
                    <h5 class="fw-bold text-dark mb-3 pt-4 border-top">Change Account Password</h5>
                    <form action="<?php echo BASE_URL; ?>/patient/profile.php" method="POST">
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
