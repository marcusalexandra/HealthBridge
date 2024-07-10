<?php
session_start();
include('../SQL/connect.php');
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['userId'])) {
    $_SESSION['error'] = 'You must have an account to schedule an appointment. Please log in.';
    header('Location: ../login/login.php'); // Redirect to login page
    exit();
}

if (!isset($_SESSION['userId']) || !isset($_POST['doctorId']) || !isset($_POST['date']) || !isset($_POST['time'])) {
    // Redirect back to the doctor profile or another appropriate page if the necessary data is missing
    header('Location: all_doctors.php');
    exit();
}

$doctorId = $_POST['doctorId'];
$patientIdQuery = mysqli_prepare($connect, "SELECT id FROM patients WHERE user_id = ?");
mysqli_stmt_bind_param($patientIdQuery, "i", $_SESSION['userId']);
mysqli_stmt_execute($patientIdQuery);
mysqli_stmt_bind_result($patientIdQuery, $patient_id);
mysqli_stmt_fetch($patientIdQuery);
mysqli_stmt_close($patientIdQuery);
$date = $_POST['date'];
$time = $_POST['time'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : ''; // Ensure that notes is set, even if it's an empty string
echo($patient_id);
// Function to add a notification


// Check for existing appointment at the same time for this doctor
$stmt = mysqli_prepare($connect, "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
mysqli_stmt_bind_param($stmt, "iss", $doctorId, $date, $time);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    // There is already an appointment at this time, redirect or inform the user
    mysqli_stmt_close($stmt);
    $_SESSION['error'] = 'This time slot is already booked. Please choose another time.';
    header("Location: pdoctor_profile.php?doctorId=$doctorId");
    exit();
}
mysqli_stmt_close($stmt);

// If no conflict, proceed to create the appointment
$insertStmt = mysqli_prepare($connect, "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, notes) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insertStmt, "iisss", $doctorId, $patient_id, $date, $time, $notes);
$success = mysqli_stmt_execute($insertStmt);
mysqli_stmt_close($insertStmt);



// Redirect back to the doctor profile with a success or error message
header("Location: pdoctor_profile.php?doctorId=$doctorId");
exit();
?>
