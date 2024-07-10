<?php
session_start();
require('../SQL/connect.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['userId']) && isset($_POST['doctorId']) && isset($_POST['stars']) && isset($_POST['comment'])) {
    $doctorId = $_POST['doctorId'];
    $userId = $_SESSION['userId'];
    $stars = $_POST['stars'];
    $comment = $_POST['comment'];

    // Retrieve patient_id using user_id from session
    $patientIdQuery = $connect->prepare("SELECT id FROM patients WHERE user_id = ?");
    $patientIdQuery->bind_param("i", $userId);
    $patientIdQuery->execute();
    $patientIdQuery->bind_result($patientId);
    $patientIdQuery->fetch();
    $patientIdQuery->close();

    if (!$patientId) {
        $_SESSION['error'] = "Patient ID could not be found.";
        header("Location: pdoctor_profile.php?doctorId=$doctorId");
        exit();
    }

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

    // Prepare the insert statement for the review
    $stmt = $connect->prepare("INSERT INTO reviews (doctor_id, patient_id, stars, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $doctorId, $patientId, $stars, $comment);
    if (!$stmt->execute()) {
        // Add notifications for both the doctor and the patient
        $_SESSION['error'] = "Error executing SQL statement: " . $stmt->error;
        addNotification($connect, $userId, "Your review has been submitted.");
        addNotification($connect, $doctorId, "You have received a new review.");
        $_SESSION['message'] = "Review submitted successfully!";
    } else {
        $_SESSION['error'] = "Failed to submit review: " . $stmt->error;
    }
    $stmt->close();
    header("Location: pdoctor_profile.php?doctorId=$doctorId");
    exit();
} else {
    $_SESSION['error'] = "Invalid request";
    header("Location: all_doctors.php");
    exit();
}
?>
