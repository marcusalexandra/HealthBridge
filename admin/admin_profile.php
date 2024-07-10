<?php
session_start();

include('../SQL/connect.php');
if (!isset($_SESSION['userId']) || $_SESSION['userId'] != 1) {
    header('Location: ../login/login.php'); // Redirect non-admins back to login page
    exit();
}
// Fetch the count of approved doctors
$doctorCountQuery = "SELECT COUNT(*) AS count FROM doctors WHERE approved = 1";
$doctorCountResult = $connect->query($doctorCountQuery);
$doctorCount = $doctorCountResult->fetch_assoc()['count'];

// Fetch the count of all patients
$patientCountQuery = "SELECT COUNT(*) AS count FROM patients";
$patientCountResult = $connect->query($patientCountQuery);
$patientCount = $patientCountResult->fetch_assoc()['count'];

// Fetch the count of all appointments
$appointmentCountQuery = "SELECT COUNT(*) AS count FROM appointments";
$appointmentCountResult = $connect->query($appointmentCountQuery);
$appointmentCount = $appointmentCountResult->fetch_assoc()['count'];

// Fetch the count of unseen notifications
$notificationCountQuery = "SELECT COUNT(*) AS count FROM notifications WHERE is_read = 0 AND user_id = ?";
$stmt = $connect->prepare($notificationCountQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notificationCount = $result->fetch_assoc()['count'];
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar .navbar-brand,
        .navbar .nav-link {
            color: #ffffff;
        }
        .navbar .nav-link {
            margin-right: 15px;
        }
        .navbar .nav-link:hover {
            color: #d4d4d4;
        }
        .sidebar {
            background-color: #343a40;
            color: #ffffff;
            position: fixed;
            top: 56px; 
            left: 0;
            width: 250px;
            height: calc(100% - 56px);
            padding-top: 20px;
        }
        .sidebar a {
            color: #ffffff;
            padding: 15px 20px;
            display: block;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 56px; 
        }
        .info-box {
            background-color: #ffffff;
            box-shadow: 0 6px 9px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .info-box h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .info-box p {
            font-size: 2rem;
            margin: 0;
        }
        .notification-icon {
            position: relative;
            margin-right: 15px;
        }
        .notification-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 12px;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
    <a class="navbar-brand" href="#" style="padding:15px 15px;">HealthBridge</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <?php
                if (!isset($_SESSION['userId'])) {
                    header('Location: ../login/login.php');
                    exit();
                }

                $userId = $_SESSION['userId'];

                if ($userId === 1) {
                    echo '<li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-user-circle-o" aria-hidden="true"></i> Profil</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="admin_profile.php">Vezi Profil</a>
                        <a class="dropdown-item" href="../login/logout.php">Deconectare</a>
                        </div>
                        </li>';
                    echo '<li class="nav-item notification-icon"><a class="nav-link" href="../notifications.php"><i class="fa fa-bell" aria-hidden="true"></i><span class="notification-count">3</span></a></li>';
                } elseif ($userRole === 'doctor') {
                    echo '<li class="nav-item"><a class="nav-link" href="../doctor/doctor_profile.php">Profil</a></li>';
                } elseif ($userRole === 'patient') {
                    echo '<li class="nav-item"><a class="nav-link" href="../patient/patient_profile.php">Profil</a></li>';
                }
                ?>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="nav flex-column">
            <?php
            if ($userId === 1) {
                echo '<li class="nav-item"><a class="nav-link" href="manage_appointments.php"><i class="fas fa-calendar-alt"></i> Gestionați programările</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="manage_doctors.php"><i class="fas fa-user-md"></i> Gestionați medicii</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users"></i> Gestionați aprobările</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="manage_deletion_requests.php"><i class="fas fa-trash"></i> Gestionați cererile de ștergere</a></li>';
            } elseif ($userRole === 'doctor') {
                echo '<li class="nav-item"><a class="nav-link" href="../doctor/doctor_appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a></li>';
            } elseif ($userRole === 'patient') {
                echo '<li class="nav-item"><a class="nav-link" href="../patient/all_doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>';
            }
            ?>
        </ul>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-box">
                        <h2>Doctori aprobați</h2>
                        <p><?php echo $doctorCount; ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <h2>Număr total pacienți</h2>
                        <p><?php echo $patientCount; ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <h2>Număr total programări</h2>
                        <p><?php echo $appointmentCount; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>
