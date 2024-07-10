<?php
session_start();
include('../SQL/connect.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is a patient
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'patient') {
    header('Location: ../login/login.php');
    exit();
}

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_appointments.php');
    exit();
}

$appointmentId = $_POST['appointment_id'];

// Fetch the appointment details
$stmt = mysqli_prepare($connect, "SELECT doctor_id, patient_id FROM appointments WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $appointmentId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $doctorId, $patientId);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Function to add a notification
function addNotification($connect, $userId, $notificationMessage) {
    $stmt = $connect->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $userId, $notificationMessage);
        if (!$stmt->execute()) {
            echo "Error inserting notification: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $connect->error;
    }
}

// Update the appointment status to 'Requested Cancel'
$stmt = mysqli_prepare($connect, "UPDATE appointments SET status = 'Requested Cancel' WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $appointmentId);
if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    // Send notifications
    $userId = $_SESSION['userId'];
    addNotification($connect, $userId, "You have requested to cancel appointment ID $appointmentId.");

    // Fetch the user ID of the doctor
    $stmt = mysqli_prepare($connect, "SELECT user_id FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $doctorId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $doctorUserId);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Send notification to the doctor
    addNotification($connect, $doctorUserId, "A cancellation request has been made for appointment ID $appointmentId.");

    $_SESSION['message'] = 'Cancellation request submitted successfully.';
} else {
    $_SESSION['error'] = 'Error submitting cancellation request: ' . mysqli_stmt_error($stmt);
}
// mysqli_stmt_close($stmt);

header('Location: my_appointments.php');
exit();
?>
