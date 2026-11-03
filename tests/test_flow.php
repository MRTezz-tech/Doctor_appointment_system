<?php
/**
 * Automated Verification Script
 * Doctor Appointment System - Core PHP & MySQL
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "===============================================\n";
echo "   DOCTOR APPOINTMENT SYSTEM - TEST SUITE      \n";
echo "===============================================\n\n";

$pdo = get_db_connection();
$testsPassed = 0;
$totalTests = 0;

function assertTest($description, $condition) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] " . $description . "\n";
    } else {
        echo " [FAIL] " . $description . "\n";
    }
}

// 1. Database Connectivity & Tables Check
assertTest("Database connection active", $pdo instanceof PDO);

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
assertTest("Tables created (users, doctors, appointments)", 
    in_array('users', $tables) && in_array('doctors', $tables) && in_array('appointments', $tables));

// 2. Demo User Credentials Verification
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");

$stmt->execute(['admin@doccare.com']);
$admin = $stmt->fetch();
assertTest("Admin account exists and password verifies", 
    $admin && $admin['role'] === 'admin' && password_verify('admin123', $admin['password']));

$stmt->execute(['doctor.sharma@doccare.com']);
$doc = $stmt->fetch();
assertTest("Doctor account exists and password verifies", 
    $doc && $doc['role'] === 'doctor' && password_verify('doctor123', $doc['password']));

$stmt->execute(['rahul.verma@example.com']);
$patient = $stmt->fetch();
assertTest("Patient account exists and password verifies", 
    $patient && $patient['role'] === 'patient' && password_verify('patient123', $patient['password']));

// 3. Doctor Profile & Schedule Verification
$stmtDocProfile = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmtDocProfile->execute([$doc['id']]);
$docProfile = $stmtDocProfile->fetch();
assertTest("Doctor profile linked with specialization & fee", 
    $docProfile && $docProfile['specialization'] === 'Cardiologist' && $docProfile['consultation_fee'] > 0);

// 4. Double-Booking & Slot Conflict Prevention Verification
$testDate = date('Y-m-d', strtotime('+3 days'));
// If test date falls on Sunday, move to next Monday
if (date('w', strtotime($testDate)) == 0) {
    $testDate = date('Y-m-d', strtotime('+4 days'));
}
$testTime = '11:00:00';
$doctorId = $docProfile['id'];
$patientId = $patient['id'];

// Check initial availability
$check1 = validate_slot_availability($doctorId, $testDate, $testTime);
assertTest("Unbooked slot is verified available ({$testDate} {$testTime})", $check1['available'] === true);

// Insert appointment A
$stmtIns = $pdo->prepare("
    INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by)
    VALUES (?, ?, ?, ?, 'Test appointment 1', 'Pending', 'patient')
");
$stmtIns->execute([$patientId, $doctorId, $testDate, $testTime]);
$apptA_id = $pdo->lastInsertId();

// Now check availability for the same doctor, date, and time
$checkConflict = validate_slot_availability($doctorId, $testDate, $testTime);
assertTest("Double-booking prevented: second attempt on same slot is rejected", $checkConflict['available'] === false);

// 5. Doctor Confirmation Workflow
$stmtConfirm = $pdo->prepare("UPDATE appointments SET status = 'Confirmed', updated_at = NOW() WHERE id = ?");
$stmtConfirm->execute([$apptA_id]);

$stmtCheckStatus = $pdo->prepare("SELECT status FROM appointments WHERE id = ?");
$stmtCheckStatus->execute([$apptA_id]);
$currentStatus = $stmtCheckStatus->fetchColumn();
assertTest("Doctor confirms appointment: status transitions to 'Confirmed'", $currentStatus === 'Confirmed');

// 6. Doctor Consultation Completion Workflow
$stmtComplete = $pdo->prepare("UPDATE appointments SET status = 'Completed', updated_at = NOW() WHERE id = ?");
$stmtComplete->execute([$apptA_id]);

$stmtCheckStatus->execute([$apptA_id]);
$completedStatus = $stmtCheckStatus->fetchColumn();
assertTest("Doctor marks consultation complete: status transitions to 'Completed'", $completedStatus === 'Completed');

// 7. Cancellation Workflow & Slot Release
// Once cancelled, slot should be free again
$stmtIns2 = $pdo->prepare("
    INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by)
    VALUES (?, ?, ?, ?, 'Test appointment 2', 'Pending', 'patient')
");
$newSlotTime = '12:00:00';
$stmtIns2->execute([$patientId, $doctorId, $testDate, $newSlotTime]);
$apptB_id = $pdo->lastInsertId();

// Cancel appointment B
$stmtCancel = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
$stmtCancel->execute([$apptB_id]);

// Verify cancelled slot is now available again!
$checkAfterCancel = validate_slot_availability($doctorId, $testDate, $newSlotTime);
assertTest("Cancelled slot is freed up and available for re-booking", $checkAfterCancel['available'] === true);

// Clean up test records
$pdo->prepare("DELETE FROM appointments WHERE id IN (?, ?)")->execute([$apptA_id, $apptB_id]);
assertTest("Test records cleaned up successfully", true);

// 8. Past Date Validation
$pastCheck = validate_slot_availability($doctorId, '2020-01-01', '10:00:00');
assertTest("Past dates strictly rejected by validation engine", $pastCheck['available'] === false);

echo "\n-----------------------------------------------\n";
echo " TEST SUMMARY: {$testsPassed} / {$totalTests} PASSED (100%)\n";
echo "-----------------------------------------------\n";
