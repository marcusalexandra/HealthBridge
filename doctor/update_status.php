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

// Retrieve the doctor ID based on the logged-in user's ID
$doctorIdQuery = mysqli_prepare($connect, "SELECT id FROM doctors WHERE user_id = ?");
mysqli_stmt_bind_param($doctorIdQuery, "i", $_SESSION['userId']);
mysqli_stmt_execute($doctorIdQuery);
mysqli_stmt_bind_result($doctorIdQuery, $doctorId);
mysqli_stmt_fetch($doctorIdQuery);
mysqli_stmt_close($doctorIdQuery);

if (isset($_GET['id']) && isset($_GET['status'])) {
    $appointmentId = $_GET['id'];
    $newStatus = $_GET['status'];

    // Ensure the status is one of the acceptable values
    if (!in_array($newStatus, ['Done', 'Cancelled', 'Requested Cancel'])) {
        $_SESSION['error'] = 'Invalid status update.';
        header("Location: doctor_appointments.php");
        exit();
    }

    // Update the appointment status in the database
    $stmt = mysqli_prepare($connect, "UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $newStatus, $appointmentId, $doctorId);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Fetch the patient ID from the appointment
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

        // Add notifications based on the new status
        switch ($newStatus) {
            case 'Done':
                addNotification($connect, $_SESSION['userId'], "You have marked appointment ID $appointmentId as done.");
                addNotification($connect, $userId, "Your appointment ID $appointmentId has been marked as done by the doctor.");
                break;
            case 'Cancelled':
                addNotification($connect, $_SESSION['userId'], "You have cancelled appointment ID $appointmentId.");
                addNotification($connect, $userId, "Your appointment ID $appointmentId has been cancelled by the doctor.");
                break;
            case 'Requested Cancel':
                addNotification($connect, $_SESSION['userId'], "You have requested to cancel appointment ID $appointmentId.");
                addNotification($connect, $userId, "The doctor has requested to cancel your appointment ID $appointmentId.");
                break;
        }

        $_SESSION['message'] = "Appointment status updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating appointment status.";
    }
}

header("Location: doctor_appointments.php");
exit();
?>
