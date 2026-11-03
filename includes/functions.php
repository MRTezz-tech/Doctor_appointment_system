<?php
/**
 * Utility Functions & Business Logic Helpers
 * Doctor Appointment System
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Escape HTML output for XSS prevention
 * @param mixed $value
 * @return string
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for friendly display
 * @param string|null $date
 * @param string $format
 * @return string
 */
function format_date($date, $format = 'd M Y') {
    if (empty($date)) return 'N/A';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Format time for friendly 12-hour AM/PM display
 * @param string|null $time
 * @return string
 */
function format_time($time) {
    if (empty($time)) return 'N/A';
    $timestamp = strtotime($time);
    return $timestamp ? date('h:i A', $timestamp) : 'N/A';
}

/**
 * Set a session flash message
 * @param string $type success|danger|warning|info
 * @param string $message
 */
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Display and clear session flash message
 * @return string
 */
function display_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $html = '';

    // Check specific flash_error from auth
    if (!empty($_SESSION['flash_error'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>' . e($_SESSION['flash_error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        unset($_SESSION['flash_error']);
    }

    // Check standard flash_message
    if (!empty($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = e($msg['type']);
        $icon = ($type === 'success') ? 'bi-check-circle-fill' : (($type === 'danger') ? 'bi-exclamation-octagon-fill' : 'bi-info-circle-fill');

        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi ' . $icon . ' me-2"></i>' . e($msg['message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        unset($_SESSION['flash_message']);
    }

    return $html;
}

/**
 * Render Status Badge with medical theme colors
 * @param string $status
 * @return string
 */
function status_badge($status) {
    switch ($status) {
        case 'Pending':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
        case 'Confirmed':
            return '<span class="badge bg-primary"><i class="bi bi-calendar-check me-1"></i>Confirmed</span>';
        case 'Completed':
            return '<span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>Completed</span>';
        case 'Cancelled':
            return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">' . e($status) . '</span>';
    }
}

/**
 * Check if a date string is valid and not in the past
 * @param string $date (Y-m-d)
 * @return bool
 */
function is_valid_future_date($date) {
    $currentDate = date('Y-m-d');
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
        return false;
    }
    return $date >= $currentDate;
}

/**
 * Generate available time slots for a doctor on a given date
 * Handles doctor schedule, slot duration, past-time suppression, and existing active bookings
 * 
 * @param int $doctor_id
 * @param string $date Y-m-d
 * @param int|null $exclude_appointment_id (for rescheduling or updating)
 * @return array ['success' => bool, 'message' => string, 'slots' => array]
 */
function get_doctor_available_slots($doctor_id, $date, $exclude_appointment_id = null) {
    $pdo = get_db_connection();

    // 1. Fetch Doctor details
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as doctor_name 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        return ['success' => false, 'message' => 'Doctor not found.', 'slots' => []];
    }

    if ($doctor['status'] !== 'active') {
        return ['success' => false, 'message' => 'This doctor is currently inactive.', 'slots' => []];
    }

    // 2. Date validation (Cannot book past dates)
    $today = date('Y-m-d');
    if ($date < $today) {
        return ['success' => false, 'message' => 'Appointments cannot be booked for past dates.', 'slots' => []];
    }

    // 3. Day of week validation
    $dayOfWeek = date('l', strtotime($date)); // e.g. "Monday"
    $availableDays = array_map('trim', explode(',', $doctor['available_days']));

    $dayMatch = false;
    foreach ($availableDays as $d) {
        if (strcasecmp($d, $dayOfWeek) === 0) {
            $dayMatch = true;
            break;
        }
    }

    if (!$dayMatch) {
        return [
            'success' => false,
            'message' => "Doctor " . $doctor['doctor_name'] . " is not available on " . $dayOfWeek . "s. Available days: " . $doctor['available_days'] . ".",
            'slots' => []
        ];
    }

    // 4. Fetch existing active bookings for this doctor on this date
    $sqlBooked = "
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? 
          AND appointment_date = ? 
          AND status IN ('Pending', 'Confirmed')
    ";
    $params = [$doctor_id, $date];
    if ($exclude_appointment_id) {
        $sqlBooked .= " AND id != ?";
        $params[] = $exclude_appointment_id;
    }
    $stmtBooked = $pdo->prepare($sqlBooked);
    $stmtBooked->execute($params);
    $bookedTimes = $stmtBooked->fetchAll(PDO::FETCH_COLUMN);

    // Normalize booked times to H:i:00 format
    $bookedLookup = [];
    foreach ($bookedTimes as $bt) {
        $bookedLookup[date('H:i:s', strtotime($bt))] = true;
    }

    // 5. Generate slots between start_time and end_time
    $startTime = strtotime($date . ' ' . $doctor['start_time']);
    $endTime = strtotime($date . ' ' . $doctor['end_time']);
    $duration = max(15, (int)$doctor['slot_duration']) * 60; // in seconds

    $currentTime = time();
    $slots = [];

    for ($time = $startTime; $time < $endTime; $time += $duration) {
        $timeStr = date('H:i:s', $time);
        $label = date('h:i A', $time);

        // Check if slot has already passed if date is today
        $isPastTime = false;
        if ($date === $today && $time <= $currentTime) {
            $isPastTime = true;
        }

        $isBooked = isset($bookedLookup[$timeStr]);
        $isAvailable = (!$isBooked && !$isPastTime);

        $slots[] = [
            'time'       => $timeStr,
            'label'      => $label,
            'available'  => $isAvailable,
            'is_booked'  => $isBooked,
            'is_past'    => $isPastTime
        ];
    }

    return [
        'success'        => true,
        'message'        => count($slots) . ' slots generated.',
        'doctor_name'    => $doctor['doctor_name'],
        'specialization' => $doctor['specialization'],
        'consultation_fee'=> $doctor['consultation_fee'],
        'date'           => $date,
        'day'            => $dayOfWeek,
        'slots'          => $slots
    ];
}

/**
 * Server-side double-booking check before inserting or confirming appointment
 * 
 * @param int $doctor_id
 * @param string $date Y-m-d
 * @param string $time H:i:s
 * @param int|null $exclude_appointment_id
 * @return array ['available' => bool, 'reason' => string]
 */
function validate_slot_availability($doctor_id, $date, $time, $exclude_appointment_id = null) {
    // 1. Check Date and Time is not in past
    $today = date('Y-m-d');
    if ($date < $today) {
        return ['available' => false, 'reason' => 'Cannot book an appointment for a past date.'];
    }

    $slotTimestamp = strtotime($date . ' ' . $time);
    if ($date === $today && $slotTimestamp <= time()) {
        return ['available' => false, 'reason' => 'Selected time slot has already passed for today.'];
    }

    $pdo = get_db_connection();

    // 2. Verify Doctor exists and is working on that day
    $stmtDoc = $pdo->prepare("SELECT d.*, u.name as doctor_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmtDoc->execute([$doctor_id]);
    $doctor = $stmtDoc->fetch();

    if (!$doctor || $doctor['status'] !== 'active') {
        return ['available' => false, 'reason' => 'Doctor is not available or inactive.'];
    }

    $dayOfWeek = date('l', strtotime($date));
    $availableDays = array_map('trim', explode(',', $doctor['available_days']));
    $dayMatch = false;
    foreach ($availableDays as $d) {
        if (strcasecmp($d, $dayOfWeek) === 0) {
            $dayMatch = true;
            break;
        }
    }
    if (!$dayMatch) {
        return ['available' => false, 'reason' => "Doctor is not scheduled on {$dayOfWeek}s."];
    }

    // 3. Check for existing active appointment at that exact slot
    $sql = "
        SELECT id, status 
        FROM appointments 
        WHERE doctor_id = ? 
          AND appointment_date = ? 
          AND appointment_time = ? 
          AND status IN ('Pending', 'Confirmed')
    ";
    $params = [$doctor_id, $date, $time];
    if ($exclude_appointment_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_appointment_id;
    }

    $stmtCheck = $pdo->prepare($sql);
    $stmtCheck->execute($params);
    $conflict = $stmtCheck->fetch();

    if ($conflict) {
        return [
            'available' => false,
            'reason'    => "This time slot is already booked ({$conflict['status']}). Please choose another time slot."
        ];
    }

    return ['available' => true, 'reason' => 'Slot is available.'];
}
