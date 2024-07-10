<?php
session_start();
include('../SQL/connect.php');

if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'doctor') {
    header('Location: ../login/login.php');
    exit();
}

// Fetch patients' data along with previous diagnostics and treatments
$stmt = mysqli_prepare($connect, "
    SELECT
        u.id AS patient_id,
        u.firstname,
        u.lastname,
        u.email,
        p.height,
        p.weight,
        p.date_of_birth,
        p.medical_history,
        u.profile_picture, /* Include profile picture column */
        d.id AS diagnosis_id,
        d.diagnosis_name,
        d.description AS diagnosis_description,
        t.id AS treatment_id,
        t.treatment_description,
        d.doctor_id AS doctor_id,
        doc.specialization,
        doc.user_id AS doctor_user_id,
        doc_user.firstname AS doctor_firstname,
        doc_user.lastname AS doctor_lastname
    FROM
        users u
    JOIN
        patients p ON u.id = p.user_id
    LEFT JOIN
        diagnosis d ON p.id = d.patient_id
    LEFT JOIN
        treatment t ON d.id = t.diagnosis_id
    LEFT JOIN
        doctors doc ON d.doctor_id = doc.id
    LEFT JOIN
        users doc_user ON doc.user_id = doc_user.id
");

mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $patientId, $firstname, $lastname, $email, $height, $weight, $dateOfBirth, $medicalHistory, $profilePicture, $diagnosisId, $diagnosisName, $diagnosisDescription, $treatmentId, $treatmentDescription, $doctorId, $specialization, $doctorUserId, $doctorFirstname, $doctorLastname);

$patientsData = [];
while (mysqli_stmt_fetch($stmt)) {
    $patientsData[$patientId]['firstname'] = $firstname;
    $patientsData[$patientId]['lastname'] = $lastname;
    $patientsData[$patientId]['email'] = $email;
    $patientsData[$patientId]['height'] = $height;
    $patientsData[$patientId]['weight'] = $weight;
    $patientsData[$patientId]['dateOfBirth'] = $dateOfBirth;
    $patientsData[$patientId]['medicalHistory'] = $medicalHistory;
    $patientsData[$patientId]['profilePicture'] = $profilePicture; // Store profile picture

    // Initialize arrays for diagnostics and treatments if not already set
    if (!isset($patientsData[$patientId]['diagnostics'])) {
        $patientsData[$patientId]['diagnostics'] = [];
    }

    // Add diagnostics and treatments to the corresponding arrays
    if ($diagnosisId) {
        // Initialize treatments array for the current diagnosis
        $treatments = [];

        // Add treatments only if they are not empty
        if ($treatmentId && $treatmentDescription) {
            $treatments[] = [
                'id' => $treatmentId,
                'description' => $treatmentDescription
            ];
        }

        $patientsData[$patientId]['diagnostics'][] = [
            'id' => $diagnosisId,
            'name' => $diagnosisName,
            'description' => $diagnosisDescription,
            'treatments' => $treatments, // Add treatments array
            'doctor' => [
                'id' => $doctorId,
                'firstname' => $doctorFirstname ?? '', // Handle null values
                'lastname' => $doctorLastname ?? '', // Handle null values
                'user_id' => $doctorUserId ?? '', // Handle null values
                'specialization' => $specialization ?? '' // Handle null values
            ]
        ];
    }
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Pacienți</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
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
            white-space: nowrap;
        }
        .table th {
            background-color: #4CAF50;
            color: white;
        }
        .table td {
            width: 14%;
            padding: 10px; /* Adăugat padding pentru a crea spațiul între rânduri */
        }
        .diagnostics,
        .treatments,
        .doctor-info {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 10px;
        }
        .diagnosis-container {
            margin-top: 20px;
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto; /* Aliniază conținutul pe mijloc */
        }
        footer {
            margin-top: 50px; /* Redus pentru spațiu mai mic între conținut și footer */
            background-color: #343a40;
            color: #fff;
        }
        footer .container {
            max-width: 1200px; /* Poți ajusta această valoare după cum este necesar */
        }
        footer .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px; /* Ajustat spațiul între coloane */
            margin: 0 -10px; /* Margin pentru coloanele din footer */
        }
        footer .col-md-4, footer .col-lg-2, footer .col-xl-2,
        footer .col-lg-3, footer .col-xl-3, footer .col-lg-4, footer .col-xl-4 {
            margin-bottom: 20px; /* Adaugă spațiere între rânduri pe dispozitivele mai mici */
            padding: 0 10px; /* Ajustează padding-ul pentru a echilibra marginile */
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
        .profile-pic {
            max-width: 50px;
            max-height: 50px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Navigation bar -->
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

    <div class="main-container mt-5">
        <h2 class="mb-4">Date Pacienți</h2>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Poză Profil</th>
                    <th>Nume</th>
                    <th>Prenume</th>
                    <th>Email</th>
                    <th>Înălțime (cm)</th>
                    <th>Greutate (kg)</th>
                    <th>Data Nașterii</th>
                    <th>Istoric Medical</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patientsData as $patientId => $patient): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($patient['profilePicture'] ?? '') ?>" alt="Profile Picture" class="profile-pic"></td>
                    <td><?= htmlspecialchars($patient['firstname'] ?? '') ?></td>
                    <td><?= htmlspecialchars($patient['lastname'] ?? '') ?></td>
                    <td><?= htmlspecialchars($patient['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($patient['height'] ?? '') ?></td>
                    <td><?= htmlspecialchars($patient['weight'] ?? '') ?></td>
                    <td><?= htmlspecialchars($patient['dateOfBirth'] ?? '') ?></td>
                    <td><?= nl2br(htmlspecialchars($patient['medicalHistory'] ?? '')) ?></td>
                </tr>
                <tr style="height: 20px;"></tr> <!-- Spațiu de 20px între rânduri -->
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Diagnostics and Treatments -->
        <div class="diagnosis-container">
            <?php foreach ($patientsData as $patientId => $patient): ?>
            <h3><?= htmlspecialchars($patient['firstname'] ?? '') ?> <?= htmlspecialchars($patient['lastname'] ?? '') ?></h3>
            <?php foreach ($patient['diagnostics'] as $diagnostic): ?>
                <div class="diagnostics">
                    <strong><?= htmlspecialchars($diagnostic['name'] ?? '') ?></strong>: <?= htmlspecialchars($diagnostic['description'] ?? '') ?><br>
                    <?php foreach ($diagnostic['treatments'] as $treatment): ?>
                        <div class="treatments">
                            Treatment: <?= htmlspecialchars($treatment['description'] ?? '') ?><br>
                        </div>
                    <?php endforeach; ?>
                    <div class="doctor-info">
                        Doctor: <?= htmlspecialchars($diagnostic['doctor']['firstname'] ?? '') . ' ' . htmlspecialchars($diagnostic['doctor']['lastname'] ?? '') ?><br>
                        Specialization: <?= htmlspecialchars($diagnostic['doctor']['specialization'] ?? '') ?><br>
                    </div>
                </div>
                <div style="margin-bottom: 20px;"></div> <!-- Spațiu de 20px între secțiuni -->
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
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
                <div class="col-md-6 col-lg-4">
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

    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
