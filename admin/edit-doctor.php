<?php
/**
 * Admin: Edit Doctor Profile & Practice Schedule
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce Admin Role
check_auth(['admin']);

$docId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$docId) {
    header('Location: ' . BASE_URL . '/admin/doctors.php');
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("
    SELECT d.*, u.name, u.email, u.phone 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.id = ? LIMIT 1
");
$stmt->execute([$docId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    set_flash('danger', 'Doctor profile not found.');
    header('Location: ' . BASE_URL . '/admin/doctors.php');
    exit;
}

$allWeekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $name           = trim($_POST['name'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $new_password   = $_POST['new_password'] ?? '';
        $specialization = trim($_POST['specialization'] ?? '');
        $qualification  = trim($_POST['qualification'] ?? '');
        $fee            = filter_input(INPUT_POST, 'consultation_fee', FILTER_VALIDATE_FLOAT);
        $start_time     = trim($_POST['start_time'] ?? '');
        $end_time       = trim($_POST['end_time'] ?? '');
        $duration       = filter_input(INPUT_POST, 'slot_duration', FILTER_VALIDATE_INT) ?: 30;
        $status         = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $bio            = trim($_POST['bio'] ?? '');
        $days           = $_POST['days'] ?? [];

        if (empty($name) || empty($phone) || empty($specialization) || empty($qualification)) {
            $error = 'Please fill out all required fields.';
        } elseif ($fee === false || $fee < 0) {
            $error = 'Please enter a valid consultation fee.';
        } elseif (empty($start_time) || empty($end_time) || $start_time >= $end_time) {
            $error = 'Start time must be strictly before end time.';
        } elseif (empty($days)) {
            $error = 'Please select at least one available working day.';
        } else {
            $daysString = implode(',', $days);

            try {
                $pdo->beginTransaction();

                // 1. Update User Record
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        throw new Exception('New password must be at least 6 characters.');
                    }
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmtU = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE id = ?");
                    $stmtU->execute([$name, $phone, $hash, $doctor['user_id']]);
                } else {
                    $stmtU = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                    $stmtU->execute([$name, $phone, $doctor['user_id']]);
                }

                // 2. Update Doctor Record
                $stmtD = $pdo->prepare("
                    UPDATE doctors 
                    SET specialization = ?, qualification = ?, consultation_fee = ?,
                        available_days = ?, start_time = ?, end_time = ?, slot_duration = ?,
                        status = ?, bio = ?
                    WHERE id = ?
                ");
                $stmtD->execute([
                    $specialization, $qualification, $fee,
                    $daysString, $start_time, $end_time, $duration,
                    $status, $bio, $docId
                ]);

                $pdo->commit();

                set_flash('success', "Doctor details for {$name} updated successfully!");
                header('Location: ' . BASE_URL . '/admin/doctors.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to update doctor details: ' . $e->getMessage();
            }
        }
    }
}

// Reload current values
$stmt->execute([$docId]);
$doctor = $stmt->fetch();
$currentDays = array_map('trim', explode(',', $doctor['available_days']));

$page_title = 'Edit Doctor Profile';
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
                            Practitioner Settings
                        </span>
                        <h3 class="fw-bold text-dark mb-0">Edit Doctor: <?php echo e($doctor['name']); ?></h3>
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

                    <form action="<?php echo BASE_URL; ?>/admin/edit-doctor.php?id=<?php echo $docId; ?>" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <h5 class="fw-bold text-dark mb-3">1. Account &amp; Contact Information</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control bg-light" value="<?php echo e($doctor['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Email Address</label>
                                <input type="email" class="form-control bg-light" value="<?php echo e($doctor['email']); ?>" disabled readonly>
                                <div class="form-text small">Email cannot be modified directly.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control bg-light" value="<?php echo e($doctor['phone']); ?>" required pattern="[0-9]{10}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Reset Password (Optional)</label>
                                <input type="password" name="new_password" class="form-control bg-light" placeholder="Leave empty to keep existing password">
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-3 pt-3 border-top">2. Clinical Specialization &amp; Pricing</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Specialization <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" class="form-control bg-light" value="<?php echo e($doctor['specialization']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Qualifications / Degrees <span class="text-danger">*</span></label>
                                <input type="text" name="qualification" class="form-control bg-light" value="<?php echo e($doctor['qualification']); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Consultation Fee (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="10" name="consultation_fee" class="form-control bg-light" value="<?php echo e($doctor['consultation_fee']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control bg-light" value="<?php echo e(substr($doctor['start_time'], 0, 5)); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control bg-light" value="<?php echo e(substr($doctor['end_time'], 0, 5)); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Slot Duration (Minutes) <span class="text-danger">*</span></label>
                                <select name="slot_duration" class="form-select bg-light">
                                    <option value="15" <?php echo ($doctor['slot_duration'] == 15) ? 'selected' : ''; ?>>15 Minutes</option>
                                    <option value="20" <?php echo ($doctor['slot_duration'] == 20) ? 'selected' : ''; ?>>20 Minutes</option>
                                    <option value="30" <?php echo ($doctor['slot_duration'] == 30) ? 'selected' : ''; ?>>30 Minutes</option>
                                    <option value="45" <?php echo ($doctor['slot_duration'] == 45) ? 'selected' : ''; ?>>45 Minutes</option>
                                    <option value="60" <?php echo ($doctor['slot_duration'] == 60) ? 'selected' : ''; ?>>60 Minutes</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Account Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select bg-light">
                                    <option value="active" <?php echo ($doctor['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($doctor['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

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

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Clinical Bio &amp; Professional Highlights</label>
                            <textarea name="bio" rows="3" class="form-control bg-light"><?php echo e($doctor['bio']); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="<?php echo BASE_URL; ?>/admin/doctors.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Save Doctor Updates
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
