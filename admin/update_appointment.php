<?php
session_start();
include('../SQL/connect.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['userId']) || $_SESSION['userId'] != 1) {
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

if (isset($_GET['id']) && isset($_GET['status'])) {
    $appointmentId = $_GET['id'];
    $newStatus = $_GET['status'];

    // Validate the new status
    if (!in_array($newStatus, ['Cancelled', 'Coming Up'])) {
        $_SESSION['error'] = 'Invalid status provided.';
        header('Location: manage_appointments.php');
        exit();
    }

    // Update the appointment status
    $stmt = mysqli_prepare($connect, "UPDATE appointments SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $appointmentId);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Fetch the doctor and patient IDs from the appointment
        $stmt = mysqli_prepare($connect, "SELECT doctor_id, patient_id FROM appointments WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $appointmentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $doctorId, $patientId);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Fetch user IDs associated with the doctor and patient
        $stmt = mysqli_prepare($connect, "SELECT user_id FROM doctors WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $doctorId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $doctorUserId);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($connect, "SELECT user_id FROM patients WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $patientId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $patientUserId);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Add notifications based on the new status
        switch ($newStatus) {
            case 'Cancelled':
                addNotification($connect, $doctorUserId, "The admin has approved the cancellation of appointment ID $appointmentId.");
                addNotification($connect, $patientUserId, "The admin has approved the cancellation of your appointment ID $appointmentId.");
                break;
            case 'Coming Up':
                addNotification($connect, $doctorUserId, "The admin has denied the cancellation request of appointment ID $appointmentId.");
                addNotification($connect, $patientUserId, "The admin has denied the cancellation request of your appointment ID $appointmentId.");
                break;
        }

        $_SESSION['message'] = "Appointment status updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating appointment status: " . mysqli_stmt_error($stmt);
    }

    header('Location: manage_appointments.php');
    exit();
}
?>
