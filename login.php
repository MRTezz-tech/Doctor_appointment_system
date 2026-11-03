<?php
/**
 * User Login Page
 * Supports Patient, Doctor, and Admin / Receptionist Authentication
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect directly to dashboard
if (is_logged_in()) {
    redirect_by_role(get_user_role());
}

$page_title = 'Account Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email and password.';
        } else {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['user_role'] = $user['role'];

                // If user is doctor, link their doctor ID
                if ($user['role'] === 'doctor') {
                    $stmtDoc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                    $stmtDoc->execute([$user['id']]);
                    $docProfile = $stmtDoc->fetch();
                    $_SESSION['doctor_id'] = $docProfile ? $docProfile['id'] : null;
                }

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect_by_role($user['role']);
            } else {
                $error = 'Invalid email address or password. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <!-- Login Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4 border-0">
                    <div class="brand-icon mx-auto mb-2 bg-white text-primary">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-1">DocCare Portal Login</h4>
                    <p class="small text-white-50 mb-0">Sign in to your patient, doctor, or staff account</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php echo display_flash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/login.php" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <div class="mb-3">
                            <label for="login_email" class="form-label fw-semibold text-dark small">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control bg-light border-start-0" id="login_email" name="email" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="login_password" class="form-label fw-semibold text-dark small mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control bg-light border-start-0" id="login_password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">Don't have an account yet? 
                            <a href="<?php echo BASE_URL; ?>/register.php" class="text-primary fw-semibold text-decoration-none">Register as Patient</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Demo Login Helpers (MCA Viva & Demonstration) -->
            <div class="card border-0 shadow-sm rounded-4 mt-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-dark mb-0 small text-uppercase font-monospace">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Demo Login
                        </h6>
                        <span class="badge bg-light text-muted border">1-Click Auto-Fill</span>
                    </div>
                    <p class="small text-muted mb-3" style="font-size: 0.8rem;">
                        Click any role button below to automatically load working demo credentials into the form:
                    </p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm text-start btn-demo-fill d-flex align-items-center justify-content-between"
                                data-email="admin@doccare.com" data-pass="admin123">
                            <span><i class="bi bi-shield-check text-primary me-2"></i><strong>Admin / Receptionist</strong></span>
                            <code class="small text-muted">admin@doccare.com</code>
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm text-start btn-demo-fill d-flex align-items-center justify-content-between"
                                data-email="doctor.sharma@doccare.com" data-pass="doctor123">
                            <span><i class="bi bi-heart-pulse text-danger me-2"></i><strong>Dr. Sharma (Cardiology)</strong></span>
                            <code class="small text-muted">doctor.sharma@doccare.com</code>
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm text-start btn-demo-fill d-flex align-items-center justify-content-between"
                                data-email="doctor.patel@doccare.com" data-pass="doctor123">
                            <span><i class="bi bi-shield-plus text-warning me-2"></i><strong>Dr. Patel (Dermatology)</strong></span>
                            <code class="small text-muted">doctor.patel@doccare.com</code>
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm text-start btn-demo-fill d-flex align-items-center justify-content-between"
                                data-email="rahul.verma@example.com" data-pass="patient123">
                            <span><i class="bi bi-person text-success me-2"></i><strong>Rahul Verma (Patient)</strong></span>
                            <code class="small text-muted">rahul.verma@example.com</code>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
