<?php
session_start();
include('../SQL/connect.php');

// Ensure the user is logged in and is a doctor
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'doctor') {
    header('Location: ../login/login.php');
    exit();
}

$userId = $_SESSION['userId'];
$message = '';

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

$doctorId = null;
$stmt = mysqli_prepare($connect, "SELECT id FROM doctors WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $doctorId);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch existing doctor data and profile picture
$stmt = mysqli_prepare($connect, "SELECT specialization, approved, profile_picture FROM doctors JOIN users ON doctors.user_id = users.id WHERE doctors.id = ?");
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $specialization, $approved, $profile_picture);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Default image if no profile picture is available
if (empty($profile_picture)) {
    $profile_picture = '../uploads/profile.png';
}

// Initialize array for storing schedule
$schedule = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days as $day) {
    $stmt = mysqli_prepare($connect, "SELECT start_time, end_time FROM doctor_schedule WHERE doctor_id = ? AND day = ?");
    mysqli_stmt_bind_param($stmt, "is", $doctorId, $day);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $start_time, $end_time);
    if (mysqli_stmt_fetch($stmt)) {
        $schedule[$day] = ['start_time' => $start_time, 'end_time' => $end_time];
    } else {
        $schedule[$day] = ['start_time' => '', 'end_time' => ''];
    }
    mysqli_stmt_close($stmt);
}

if (isset($_POST['update'])) {
    $specialization = $_POST['specialization'];

    // Profile picture upload handling
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $fileName = time() . basename($_FILES['profile_picture']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
            $profile_picture = $targetPath; // Update profile picture path
            // Update the profile picture in the database
            $stmt = mysqli_prepare($connect, "UPDATE users SET profile_picture = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $profile_picture, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = "Profile updated successfully.";
            addNotification($connect, $userId, "Your profile has been updated.");
        }
    }

    // Update doctor specialization
    $stmt = mysqli_prepare($connect, "UPDATE doctors SET specialization = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $specialization, $doctorId);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Profile updated successfully.";
            addNotification($connect, $userId, "Your profile has been updated.");
            // Add notification for admin (assuming admin has user_id = 1)
            addNotification($connect, 1, "Doctor with ID $doctorId has updated their specialization.");
        } else {
            $message = "Error updating profile: " . mysqli_error($connect);
        }
        mysqli_stmt_close($stmt);
    }

    // Update or insert schedule
    foreach ($days as $day) {
        $start_time = $_POST[$day . '_start'] ?? '';
        $end_time = $_POST[$day . '_end'] ?? '';

        if (!empty($start_time) && !empty($end_time)) {
            // Check if a schedule already exists for this day
            $stmt = mysqli_prepare($connect, "SELECT id FROM doctor_schedule WHERE doctor_id = ? AND day = ?");
            mysqli_stmt_bind_param($stmt, "is", $doctorId, $day);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $schedule_id);
            if (mysqli_stmt_fetch($stmt)) {
                // Update existing schedule
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($connect, "UPDATE doctor_schedule SET start_time = ?, end_time = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssi", $start_time, $end_time, $schedule_id);
            } else {
                // Insert new schedule
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($connect, "INSERT INTO doctor_schedule (doctor_id, day, start_time, end_time) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isss", $doctorId, $day, $start_time, $end_time);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // If no start and end time provided, delete the schedule if it exists
            $stmt = mysqli_prepare($connect, "DELETE FROM doctor_schedule WHERE doctor_id = ? AND day = ?");
            mysqli_stmt_bind_param($stmt, "is", $doctorId, $day);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle account deletion request
if (isset($_POST['request_deletion'])) {
    $reason = $_POST['deletion_reason'] ?? '';  // Null coalescing operator for optional reason

    // Insert deletion request
    $stmt = mysqli_prepare($connect, "INSERT INTO deletion_requests (doctor_id, reason) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $doctorId, $reason);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Deletion request submitted successfully.";
        addNotification($connect, $userId, "Your account deletion request has been submitted.");
        // Add notification for admin (assuming admin has user_id = 1)
        addNotification($connect, 1, "Doctor with ID $doctorId has requested account deletion.");
    } else {
        $message = "Error submitting deletion request: " . mysqli_error($connect);
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Doctor</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .time-input {
            display: flex;
            align-items: center;
        }
        .time-input label {
            width: 90px; /* Adjust width as needed */
            margin-right: 10px;
        }
        .time-input input {
            width: calc(50% - 60px); /* Adjust based on label width to fill form-group */
        }
        .form-group {
            margin-bottom: 10px;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        }
        .navbar-nav .nav-link {
            color: #343a40 !important;
            margin-left: 10px;
            margin-right: 10px;
        }
        .navbar-toggler {
            border: none;
        }
        .navbar-toggler .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(0, 0, 0, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
        }
        .icon {
            margin-right: 5px;
        }
        .notification-icon {
            position: relative;
        }
        .notification-count {
            position: absolute;
            top: -5px;
            right: 0px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 3px 9px;
            font-size: 12px;
        }
        @media (max-width: 900px) {
            .notification-count {
                top: -15px;
                right: -20px;
            }
        }
        .logo-container {
            padding: 20px 0;
        }
        footer {
            margin-top: 130px; /* Adaugă marginea superioară */
            background-color: #343a40;
            color: #fff;
        }
        footer .container {
            max-width: 1200px; /* Poți ajusta această valoare după cum este necesar */
        }
        footer .row {
            display: flex;
            flex-wrap: wrap;
            gap:0.5px;/* Adaugă spațiere de 5px între coloane */
            margin: 0 -2.5px; /* Ajustează marginile pentru a echilibra padding-ul coloanelor */
        }
        footer .col-md-4, footer .col-lg-2, footer .col-xl-2,
        footer .col-lg-3, footer .col-xl-3, footer .col-lg-4, footer .col-xl-4 {
            margin-bottom: 20px; /* Adaugă spațiere între rânduri pe dispozitivele mai mici */
            padding: 0 2px; /* Ajustează padding-ul pentru a echilibra marginile */
            text-align: justify;
        }
        footer p, footer a {
            font-size: 14px; /* Ajustează dimensiunea fontului pentru lizibilitate */
            color: #ccc;
        }
        footer a:hover {
            color: #fff;
        }
        footer h5 {
            font-size: 18px; /* Ajustează dimensiunea fontului pentru titluri */
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
        }
        .form-control {
            height: auto !important;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-primary:hover {
            background-color: #004494;
            border-color: #004494;
        }

    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
        <div class="container">
        <div class="logo-container">
                <a href="index.php">
                    <img class="logo" src="../uploads/Logo.png" alt="logo" style="width: 120px; height:75px;">
                </a>
        </div>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto" style="padding:10px;">
                <?php
                if (!isset($_SESSION['userId'])) {
                    header('Location: ../login/login.php');
                    exit();
                }

                $userId = $_SESSION['userId'];
                $userRole = $_SESSION['userRole'];

                if ($userId === 1) {
                    echo '<li class="nav-item"><a class="nav-link" href="../admin/admin_profile.php">Profile</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="../admin/manage_appointments.php">Manage Appointments</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="../admin/manage_users.php">Manage Users</a></li>';
                } elseif ($userRole === 'doctor') {
                    echo '<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user-circle-o" aria-hidden="true"></i> Profil</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="nav-link" href="doctor_profile.php">Vezi Profil</a>
                    <a class="dropdown-item nav-link" href="../login/logout.php">Deconectare</a>
                    </div>
                    </li>';
                    echo '<li class="nav-item"><a class="nav-link" href="doctor_appointments.php">Programări pacienți</a></li>';
                } elseif ($userRole === 'patient') {
                    echo '<li class="nav-item"><a class="nav-link" href="../patient/patient_profile.php">Profile</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="../patient/all_doctors.php">Doctors</a></li>';
                }
                echo '<li class="nav-item notification-icon"><a class="nav-link" href="../notifications.php"><i class="fa fa-bell" aria-hidden="true"></i><span class="notification-count">3</span></a></li>';
                ?>
            </ul>
            </div>
        </div>
</nav>
<div class="container" style="margin: 50px auto; padding: 30px;">
<?php if ($message) echo "<p>$message</p>"; ?>
    
    <div>
        <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Doctor's Profile Picture" style="width: 147px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 15px;">
        <p><strong>Specializare:</strong> <?= htmlspecialchars($specialization) ?></p>
        <p><strong>Status:</strong> <?= $approved ? 'Approved' : 'Pending Approval'; ?></p>
        <h4 class= "mt-5 mb-3">Program:</h4>
        <?php foreach ($days as $day): ?>
            <p style="border-bottom: 2px solid #dee2e6;"><?= $day ?>: <?= empty($schedule[$day]['start_time']) ? 'Closed' : htmlspecialchars($schedule[$day]['start_time']) . ' - ' . htmlspecialchars($schedule[$day]['end_time']) ?></p>
        <?php endforeach; ?>
    </div>

    <form method="post" action="doctor_profile.php" enctype="multipart/form-data">
        <div class="form-group mt-5 mb-3">
            <label for="specialization">Specializare:</label>
            <input type="text" class="form-control" name="specialization" value="<?= htmlspecialchars($specialization) ?>" required>
        </div>
        <?php foreach ($days as $day): ?>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?= $day ?>:</label>
            <div class="col-sm-5 time-input">
                <label>Începe:</label>
                <input type="time" class="form-control" name="<?= $day ?>_start" value="<?= $schedule[$day]['start_time'] != '00:00:00' ? htmlspecialchars($schedule[$day]['start_time']) : '' ?>">
            </div>
            <div class="col-sm-5 time-input">
                <label>Sfârșit:</label>
                <input type="time" class="form-control" name="<?= $day ?>_end" value="<?= $schedule[$day]['end_time'] != '00:00:00' ? htmlspecialchars($schedule[$day]['end_time']) : '' ?>">
            </div>
            <?php if ($schedule[$day]['start_time'] == '00:00:00' && $schedule[$day]['end_time'] == '00:00:00'): ?>
            <div class="col-sm-12"><p class="text-muted">Închis</p></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="form-group">
            <label for="profile_picture">Poză Profil:</label>
            <input type="file" class="form-control" name="profile_picture" id="profile_picture">
        </div>

        <div class="form-group">
            <label>Status:</label>
            <input type="text" class="form-control" value="<?php echo $approved ? 'Approved' : 'Pending Approval'; ?>" disabled>
        </div>
        <button type="submit" name="update" class="btn btn-primary mb-4">Actualizați</button>
    </form>

    <form method="post" action="doctor_profile.php">
        <h3>Cerere de ștergere a contului</h3>
        <div class="form-group">
            <label for="deletion_reason">Motivul ștergerii (opțional):</label>
            <textarea class="form-control" name="deletion_reason"></textarea>
        </div>
        <button type="submit" name="request_deletion" class="btn btn-danger">Șterge contul</button>
    </form>
</div>
    
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <footer class="bg-dark text-light pt-5 pb-4">
        <div class="container text-center text-md-left">
            <div class="row justify-content-center text-center text-md-left">
                <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-4">
                    <h5 class="text-uppercase mb-4 font-weight-bold">HealthBridge</h5>
                    <p>Oferim cele mai bune servicii medicale pentru tine și familia ta. Încredere și profesionalism la cele mai înalte standarde.</p>
                </div>

                <div class="col-md-4 col-lg-2 col-xl-2 mx-auto mt-4">
                    <h5 class="text-uppercase mb-4 font-weight-bold">Linkuri utile</h5>
                    <p>
                        <a href="about.php" class="text-light" style="text-decoration: none;">Despre Noi</a>
                    </p>
                    <p>
                        <a href="services.php" class="text-light" style="text-decoration: none;">Servicii</a>
                    </p>
                    <p>
                        <a href="appointments.php" class="text-light" style="text-decoration: none;">Programări</a>
                    </p>
                    <p>
                        <a href="contact.php" class="text-light" style="text-decoration: none;">Contact</a>
                    </p>
                </div>

                <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-4">
                    <h5 class="text-uppercase mb-4 font-weight-bold">Contact</h5>
                    <p>
                        <i class="fas fa-home mr-3"></i> Strada Macilor, Nr. 12, Oradea
                    </p>
                    <p>
                        <i class="fas fa-envelope mr-3"></i> contact@healthbridge.ro
                    </p>
                    <p>
                        <i class="fas fa-phone mr-3"></i> +40 123 456 789
                    </p>
                    <p>
                        <i class="fas fa-print mr-3"></i> +40 123 456 780
                    </p>
                </div>
            </div>

            <hr class="mb-4" style="background-color: #ccc;">

            <div class="row align-items-center">
                <div class="col-md-6 col-lg-6">
                    <p class="text-center text-md-left">© 2024 Clinica Medicală Privată. Toate drepturile rezervate.</p>
                </div>
                <div class="col-md-6 col-lg-4" style="left:240px;">
                    <a href="#" class="text-light" style="text-decoration: none;">
                        <i class="fab fa-facebook-f fa-lg mr-4"></i>
                    </a>
                    <a href="#" class="text-light" style="text-decoration: none;">
                        <i class="fab fa-twitter fa-lg mr-4"></i>
                    </a>
                    <a href="#" class="text-light" style="text-decoration: none;">
                        <i class="fab fa-instagram fa-lg mr-4"></i>
                    </a>
                    <a href="#" class="text-light" style="text-decoration: none;">
                        <i class="fab fa-linkedin fa-lg mr-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
