<?php
session_start();
include('../SQL/connect.php');


if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'doctor') {
    header('Location: ../login/login.php');
    exit();
}

$appointmentId = isset($_GET['appointmentId']) ? intval($_GET['appointmentId']) : 0;
$patientDetails = [];
$diagnosisDetails = [];
$treatmentDetails = [];
$patientId = null;

if ($appointmentId) {
    $stmt = mysqli_prepare($connect, "SELECT p.id as patient_id, d.id as diagnosis_id, d.description, d.diagnosis_name, t.id as treatment_id, t.treatment_description
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        LEFT JOIN diagnosis d ON a.id = d.appointment_id
        LEFT JOIN treatment t ON d.id = t.diagnosis_id
        WHERE a.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appointmentId);
    mysqli_stmt_execute($stmt);
    if ($stmt) {
        mysqli_stmt_bind_result($stmt, $patientId, $diagnosisId, $description, $diagnosisName, $treatmentId, $treatmentDescription);
        if (mysqli_stmt_fetch($stmt)) {
            $patientDetails = ['id' => $patientId];
            $diagnosisDetails = ['id' => $diagnosisId, 'name' => $diagnosisName, 'description' => $description];
            $treatmentDetails = ['id' => $treatmentId, 'description' => $treatmentDescription];
        } else {
            echo "No data fetched. Check your query and data consistency.";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Failed to prepare statement. Error: " . mysqli_error($connect);
    }
} else {
    echo "No appointment ID provided.";
}
?>
<!DOCTYPE html>
<html>
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
    <h2 class="mt-5 mb-5">Adaugă/Actualizează Diagnosticul și Tratamentul</h2>
    <form action="submit_diagnosis_treatment.php" method="post">
        <input type="hidden" name="patientId" value="<?= $patientDetails['id'] ?? '' ?>">
        <input type="hidden" name="diagnosisId" value="<?= $diagnosisDetails['id'] ?? '' ?>">
        <input type="hidden" name="treatmentId" value="<?= $treatmentDetails['id'] ?? '' ?>">
        <input type="hidden" name="appointmentId" value="<?= $appointmentId ?? '' ?>">
        <div class="form-group">
            <label for="diagnosisName">Numele Diagnosticului:</label>
            <input type="text" class="form-control" id="diagnosisName" name="diagnosisName" value="<?= $diagnosisDetails['name'] ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Descrierea Diagnosticului:</label>
            <textarea class="form-control" id="description" name="description"><?= $diagnosisDetails['description'] ?? '' ?></textarea>
        </div>
        <div class="form-group">
            <label for="treatmentDescription">Descrierea Tratamentului:</label>
            <textarea class="form-control" id="treatmentDescription" name="treatmentDescription"><?= $treatmentDetails['description'] ?? '' ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <!-- Button to view patient's medical history -->
        <a href="patients_data.php?patientId=<?= htmlspecialchars($patientId) ?>" class="btn btn-info">Vezi Istoric Medical Pacient</a>
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
