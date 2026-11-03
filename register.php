<?php
/**
 * Patient Self-Registration Page
 * Doctor Appointment System
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect_by_role(get_user_role());
}

$page_title = 'Patient Registration';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = 'All fields marked with an asterisk are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm) {
            $error = 'Password confirmation does not match.';
        } else {
            $pdo = get_db_connection();

            // Check if email already registered
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $error = 'An account with this email address already exists. Please log in.';
            } else {
                // Insert new patient user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password, role, created_at)
                    VALUES (?, ?, ?, ?, 'patient', NOW())
                ");

                try {
                    $stmtInsert->execute([$name, $email, $phone, $hashedPassword]);
                    $newUserId = $pdo->lastInsertId();

                    // Automatic login upon successful registration
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = $newUserId;
                    $_SESSION['user_name']  = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_phone'] = $phone;
                    $_SESSION['user_role']  = 'patient';

                    set_flash('success', 'Registration successful! Welcome to DocCare Clinic. You can now book your first consultation.');
                    header('Location: ' . BASE_URL . '/patient/book-appointment.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Failed to register account: ' . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 my-md-3">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4 border-0">
                    <div class="brand-icon mx-auto mb-2 bg-white text-primary">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Create Patient Account</h4>
                    <p class="small text-white-50 mb-0">Join DocCare to book doctor consultations in minutes</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form id="registerForm" action="<?php echo BASE_URL; ?>/register.php" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold text-dark small">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control bg-light border-start-0" id="name" name="name" placeholder="e.g. Rahul Sharma" value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold text-dark small">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control bg-light border-start-0" id="email" name="email" placeholder="patient@example.com" value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold text-dark small">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control bg-light border-start-0" id="phone" name="phone" placeholder="9876543210" pattern="[0-9]{10}" title="Please enter a 10-digit mobile number" value="<?php echo isset($_POST['phone']) ? e($_POST['phone']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold text-dark small">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control bg-light border-start-0" id="password" name="password" placeholder="At least 6 characters" minlength="6" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold text-dark small">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" class="form-control bg-light border-start-0" id="confirm_password" name="confirm_password" placeholder="Re-type password" minlength="6" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                            <label class="form-check-label small text-muted" for="termsCheck">
                                I agree to the <a href="#" class="text-primary text-decoration-none">Privacy Policy</a> and <a href="#" class="text-primary text-decoration-none">Terms of Clinical Consultation</a>.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-person-check me-2"></i> Complete Registration
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">Already registered? 
                            <a href="<?php echo BASE_URL; ?>/login.php" class="text-primary fw-semibold text-decoration-none">Log In to Your Account</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
