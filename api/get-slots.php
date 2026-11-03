<?php
/**
 * AJAX API: Get Available Slots for a Doctor on a Given Date
 * Doctor Appointment System
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$doctor_id = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT);
$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$doctor_id || !$date) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing doctor_id or date parameters.',
        'slots'   => []
    ]);
    exit;
}

// Optional exclude_appointment_id (for editing or rescheduling)
$exclude_id = filter_input(INPUT_GET, 'exclude_id', FILTER_VALIDATE_INT) ?: null;

$result = get_doctor_available_slots($doctor_id, $date, $exclude_id);
echo json_encode($result);
exit;
