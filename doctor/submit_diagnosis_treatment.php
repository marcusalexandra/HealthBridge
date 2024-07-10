<?php
session_start();
include('../SQL/connect.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is a doctor
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'doctor') {
    header('Location: ../login/login.php');
    exit();
}

// Ensure required data is provided
if (!isset($_POST['diagnosisName'], $_POST['description'], $_POST['treatmentDescription'], $_POST['patientId'])) {
    exit('Required data is missing!');
}

$diagnosisId = $_POST['diagnosisId'] ?? null;
$treatmentId = $_POST['treatmentId'] ?? null;
$patientId = $_POST['patientId'] ?? null;
$appointmentId = $_POST['appointmentId'] ?? null;

// Retrieve the doctor_id from the doctors table based on the logged-in user's ID
$doctorId = null;
$stmt = mysqli_prepare($connect, "SELECT id FROM doctors WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['userId']);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $doctorId);
if (!mysqli_stmt_fetch($stmt)) {
    echo "No doctor found with this user ID.";
    exit;
}
mysqli_stmt_close($stmt);

// Function to add a notification
function addNotification($connect, $userId, $notificationMessage) {
    $stmt = mysqli_prepare($connect, "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $userId, $notificationMessage);
        if (!mysqli_stmt_execute($stmt)) {
            echo "Error inserting notification: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($connect);
    }
}

try {
    // Start transaction
    mysqli_begin_transaction($connect);

    // Update or insert diagnosis
    if ($diagnosisId) {
        $stmt = mysqli_prepare($connect, "UPDATE diagnosis SET diagnosis_name = ?, description = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $_POST['diagnosisName'], $_POST['description'], $diagnosisId);
    } else {
        $stmt = mysqli_prepare($connect, "INSERT INTO diagnosis (patient_id, doctor_id, diagnosis_name, description, diagnosis_date, appointment_id) VALUES (?, ?, ?, ?, CURDATE(), ?)");
        mysqli_stmt_bind_param($stmt, "iissi", $patientId, $doctorId, $_POST['diagnosisName'], $_POST['description'], $appointmentId);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Get the newly inserted or updated diagnosis ID
    $diagnosisId = $diagnosisId ?: mysqli_insert_id($connect);

    // Update or insert treatment
    if ($treatmentId) {
        $stmt = mysqli_prepare($connect, "UPDATE treatment SET treatment_description = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $_POST['treatmentDescription'], $treatmentId);
    } else {
        $stmt = mysqli_prepare($connect, "INSERT INTO treatment (diagnosis_id, treatment_description, treatment_date) VALUES (?, ?, CURDATE())");
        mysqli_stmt_bind_param($stmt, "is", $diagnosisId, $_POST['treatmentDescription']);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Commit transaction
    mysqli_commit($connect);

    // Fetch user ID associated with the patient ID
    $stmt = mysqli_prepare($connect, "SELECT user_id FROM patients WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $patientId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Add notifications for the doctor and the patient
    addNotification($connect, $_SESSION['userId'], "You have updated the diagnosis and treatment for patient ID $patientId.");
    addNotification($connect, $userId, "Your diagnosis and treatment have been updated by the doctor.");

    $_SESSION['message'] = 'Diagnosis and treatment successfully updated.';
    header("Location: doctor_appointments.php");
} catch (Exception $e) {
    mysqli_rollback($connect);
    echo "Error: " . $e->getMessage();
}

exit();
?>
