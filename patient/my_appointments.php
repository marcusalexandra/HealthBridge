<?php
session_start();
include('../SQL/connect.php');

// Ensure the user is logged in and is a patient
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'patient') {
    header('Location: ../login/login.php');
    exit();
}

$userId = $_SESSION['userId'];

// Fetch the patient ID using the user ID from the session
$stmt = mysqli_prepare($connect, "SELECT id FROM patients WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $patientId);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch appointments for the patient
$stmt = mysqli_prepare($connect, "SELECT a.id, a.appointment_date, a.appointment_time, a.status, u.firstname, u.lastname, d.specialization
                                  FROM appointments a
                                  JOIN doctors d ON a.doctor_id = d.id
                                  JOIN users u ON d.user_id = u.id
                                  WHERE a.patient_id = ?");
mysqli_stmt_bind_param($stmt, "i", $patientId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $appointmentId, $appointmentDate, $appointmentTime, $status, $doctorFirstname, $doctorLastname, $specialization);

// Fetch all appointments
$appointments = [];
while (mysqli_stmt_fetch($stmt)) {
    $appointments[] = [
        'id' => $appointmentId,
        'date' => $appointmentDate,
        'time' => $appointmentTime,
        'status' => $status,
        'doctor' => $doctorFirstname . ' ' . $doctorLastname,
        'specialization' => $specialization
    ];
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programări</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .navbar {
            background-color: white;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        }
        .navbar-nav .nav-link {
            color: black !important;
            margin-left: 10px;
            margin-right: 10px;
        }
        .navbar-toggler {
            color: black;
            border: none;
        }
        .navbar-toggler .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(0, 0, 0, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
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
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .table th, .table td:nth-child(1),
        .table td:nth-child(2), .table td:nth-child(3),
        .table td:nth-child(4), .table td:nth-child(5) {
            width: 10%;
        }
        .table th:last-child, .table td:last-child {
            width: 15%;
        }
        .btn-warning {
            color: #fff;
            background-color: #ff6347;
            border-color: #ff6347;
        }
        .btn-warning:hover {
            color: #fff;
            background-color: red;
            border-color: red;
        }
        .btn-warning:focus, .btn-warning.focus {
            box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.5);
        }
        .btn-info {
            color: #fff;
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-info:hover {
            color: #fff;
            background-color: #138496;
            border-color: #117a8b;
        }
        .btn-info:focus, .btn-info.focus {
            box-shadow: 0 0 0 0.2rem rgba(23,162,184,0.5);
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
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
        <div class="container">
        <div class="logo-container">
                <a href="index.php">
                    <img class="logo" src="../uploads/Logo.png" alt="logo" style="width:120px; height:75px;">
                </a>
        </div>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto" style="padding:10px;">
                    <?php
                    if ($userId === 1) {
                        echo '<li class="nav-item"><a class="nav-link" href="../admin/admin_profile.php">Admin Profile</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="../admin/manage_appointments.php">Manage Appointments</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="../admin/manage_users.php">Manage Users</a></li>';
                    } elseif ($_SESSION['userRole'] === 'doctor') {
                        echo '<li class="nav-item"><a class="nav-link" href="../doctor/doctor_profile.php">Doctor Profile</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="../doctor/doctor_appointments.php">Appointments</a></li>';
                    } elseif ($_SESSION['userRole'] === 'patient') {
                        echo '<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user-circle-o" aria-hidden="true"></i> Profil</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="patient_profile.php">Vezi Profil</a>
                    <a class="dropdown-item" href="../login/logout.php">Deconectare</a>
                    </div>
                    </li>';
                        echo '<li class="nav-item"><a class="nav-link" href="my_appointments.php">Programări și Diagnostic</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="../patient/all_doctors.php">Doctori</a></li>';
                    }
                    echo '<li class="nav-item notification-icon"><a class="nav-link" href="../notifications.php"><i class="fa fa-bell" aria-hidden="true"></i><span class="notification-count">3</span></a></li>';
                    ?>
                </ul>
            </div>
        </div>
</nav>
    <div class="container">
        <h2 class="mt-5 mb-5">Programările mele</h2>
        <?php if (empty($appointments)): ?>
            <div class="alert alert-info" role="alert">
                Nu ai încă programări de efectuat.
            </div>
        <?php else: ?>
            <table class="table table-striped" style="box-shadow: 0 6px 9px 6px rgba(0, 0, 0, 0.1);">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Status</th>
                        <th>Doctor</th>
                        <th>Specializare</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= htmlspecialchars($appointment['id']) ?></td>
                            <td><?= htmlspecialchars($appointment['date']) ?></td>
                            <td><?= htmlspecialchars($appointment['time']) ?></td>
                            <td><?= htmlspecialchars($appointment['status']) ?></td>
                            <td><?= htmlspecialchars($appointment['doctor']) ?></td>
                            <td><?= htmlspecialchars($appointment['specialization']) ?></td>
                            <td>
                                <?php if ($appointment['status'] === 'Coming Up'): ?>
                                    <form action="request_cancellation.php" method="post" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['id']) ?>">
                                        <button type="submit" class="btn btn-warning">Anulează</button>
                                    </form>
                                <?php elseif ($appointment['status'] === 'Done'): ?>
                                    <form action="view_diagnosis.php" method="get" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['id']) ?>">
                                        <button type="submit" class="btn btn-info">Vezi diagnostic</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
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
