<?php
session_start();
include('../SQL/connect.php');


if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'doctor') {
    header('Location: ../login/login.php');
    exit();
}

// Fetch the doctor's ID from the doctors table
$doctorIdQuery = mysqli_prepare($connect, "SELECT id FROM doctors WHERE user_id = ?");
mysqli_stmt_bind_param($doctorIdQuery, "i", $_SESSION['userId']);
mysqli_stmt_execute($doctorIdQuery);
mysqli_stmt_bind_result($doctorIdQuery, $doctorId);
mysqli_stmt_fetch($doctorIdQuery);
mysqli_stmt_close($doctorIdQuery);

// Fetch appointments linked to this doctor's ID
$stmt = mysqli_prepare($connect, "SELECT a.id, u.firstname, u.lastname, u.ssn, a.appointment_date, a.appointment_time, a.status, a.notes FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users u ON p.user_id = u.id WHERE a.doctor_id = ? AND (a.status = 'Coming Up' OR a.status = 'Requested Cancel') ORDER BY a.appointment_date ASC, a.appointment_time ASC");
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $appointmentId, $firstName, $lastName, $ssn, $appointmentDate, $appointmentTime, $status, $notes);
$appointments = [];

while (mysqli_stmt_fetch($stmt)) {
    $appointments[] = [
        'appointmentId' => $appointmentId,
        'patientName' => $firstName . ' ' . $lastName,
        'ssn' => $ssn,
        'appointmentDate' => $appointmentDate,
        'appointmentTime' => $appointmentTime,
        'status' => $status,
        'notes' => $notes
    ];
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programări pacienți</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
        footer {
            margin-top: 130px;
            background-color: #343a40;
            color: #fff;
        }
        footer .container {
            max-width: 1200px;
        }
        footer .row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5px;
            margin: 0 -2.5px;
        }
        footer .col-md-4, footer .col-lg-2, footer .col-xl-2,
        footer .col-lg-3, footer .col-xl-3, footer .col-lg-4, footer .col-xl-4 {
            margin-bottom: 20px;
            padding: 0 2px;
            text-align: justify;
        }
        footer p, footer a {
            font-size: 14px;
            color: #ccc;
        }
        footer a:hover {
            color: #fff;
        }
        footer h5 {
            font-size: 18px;
        }
        .modal-body {
            text-align: justify;
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
<div class="container">
    <h2 class="mt-5 mb-5">Programările pacienților mei</h2>
    <table class="table table-striped" style="box-shadow: 0 6px 9px 6px rgba(0, 0, 0, 0.1);">
        <thead>
        <tr>
            <th>Nume Pacient</th>
            <th>CNP</th>
            <th>Data</th>
            <th>Ora</th>
            <th>Status</th>
            <th>Motiv programare</th>
            <th>Acțiuni</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $appointment): ?>
            <tr>
                <td><?= htmlspecialchars($appointment['patientName']) ?></td>
                <td>
                    <a href="add_diagnosis_treatment.php?appointmentId=<?= htmlspecialchars($appointment['appointmentId']) ?>"><?= htmlspecialchars($appointment['ssn']) ?></a>
                </td>
                <td><?= htmlspecialchars($appointment['appointmentDate']) ?></td>
                <td><?= htmlspecialchars($appointment['appointmentTime']) ?></td>
                <td><?= htmlspecialchars($appointment['status']) ?></td>
                <!--<td><?= htmlspecialchars($appointment['notes']) ?></td>-->
                <td>
                    <!-- Buton pentru a deschide modalul -->
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalMotiv<?= $appointment['appointmentId'] ?>">
                    Vezi Motiv
                </button>
            </td>
            <td>
                <?php
                $dateDiff = (new DateTime())->diff(new DateTime($appointment['appointmentDate']))->days;
                if ($appointment['status'] == 'Coming Up' && $dateDiff > 2) {
                    echo '<a href="update_status.php?id=' . $appointment['appointmentId'] . '&status=Done" class="btn btn-success mb-1">Realizată</a>';
                    echo ' <a href="update_status.php?id=' . $appointment['appointmentId'] . '&status=Cancelled" class="btn btn-danger mb-1">Șterge</a>';
                } elseif ($dateDiff <= 2) {
                    echo '<a href="update_status.php?id=' . $appointment['appointmentId'] . '&status=Done" class="btn btn-success mb-1">Realizată</a>';
                    echo ' <a href="update_status.php?id=' . $appointment['appointmentId'] . '&status=Cancelled" class="btn btn-danger mb-1">Șterge</a>';
                    
                }
                ?>
                </td>
            </tr>
            <!-- Modal pentru motivul programării -->
        <div class="modal fade" id="modalMotiv<?= $appointment['appointmentId'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalMotivLabel<?= $appointment['appointmentId'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMotivLabel<?= $appointment['appointmentId'] ?>">Motivul Programării</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p><?= htmlspecialchars($appointment['notes']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
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
            </div>
        </div>
    </div>
</footer>



<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>
