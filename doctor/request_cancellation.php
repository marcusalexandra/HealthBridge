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

if (isset($_GET['id'])) {
    $appointmentId = $_GET['id'];

    // Fetch patient ID and user ID from the appointment
    $stmt = mysqli_prepare($connect, "SELECT patient_id FROM appointments WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appointmentId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $patientId);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Fetch user ID associated with the patient ID
    $stmt = mysqli_prepare($connect, "SELECT user_id FROM patients WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $patientId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Update the appointment status to 'Requested Cancel'
    $stmt = mysqli_prepare($connect, "UPDATE appointments SET status = 'Requested Cancel' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appointmentId);
    if (mysqli_stmt_execute($stmt)) {
        // Add notifications for both the doctor and the patient
        addNotification($connect, $_SESSION['userId'], "You have requested to cancel appointment ID $appointmentId.");
        addNotification($connect, $userId, "The doctor has requested to cancel your appointment ID $appointmentId.");
    } else {
        echo "Error updating appointment status: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}

header('Location: doctor_appointments.php');
exit();
?>
