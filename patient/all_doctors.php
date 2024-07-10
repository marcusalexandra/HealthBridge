<?php
session_start();
include('../SQL/connect.php');

// Enable MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Base query to fetch doctors and their average ratings
    $query = "
        SELECT users.id, users.firstname, users.lastname, doctors.specialization, doctors.id,
               COALESCE(AVG(reviews.stars), 0) AS average_rating
        FROM users
        JOIN doctors ON users.id = doctors.user_id
        LEFT JOIN reviews ON doctors.id = reviews.doctor_id
        WHERE doctors.approved = 1
    ";

    // Apply filters if provided in the URL
    $params = [];
    $types = '';
    $specializationFilter = $_GET['specialization'] ?? '';
    $ratingFilter = $_GET['rating'] ?? '';
    $nameFilter = $_GET['name'] ?? '';

    if (!empty($specializationFilter)) {
        $query .= " AND doctors.specialization = ?";
        $types .= 's';
        $params[] = $specializationFilter;
    }

    if (!empty($nameFilter)) {
        $query .= " AND (users.firstname LIKE ? OR users.lastname LIKE ?)";
        $types .= 'ss';
        $nameParam = '%' . $nameFilter . '%';
        $params[] = $nameParam;
        $params[] = $nameParam;
    }

    $query .= " GROUP BY doctors.id, users.firstname, users.lastname, doctors.specialization, users.id";

    if (!empty($ratingFilter)) {
        $query .= " HAVING average_rating >= ?";
        $types .= 'd';
        $params[] = (double)$ratingFilter;
    }

    $stmt = mysqli_prepare($connect, $query);

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId, $firstName, $lastName, $specialization, $doctorId, $averageRating);
    $doctors = [];
    while (mysqli_stmt_fetch($stmt)) {
        $doctors[] = [
            'userId' => $userId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'specialization' => $specialization,
            'doctorId' => $doctorId,
            'averageRating' => $averageRating
        ];
    }
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $e) {
    // If an SQL error occurs, display it and exit the script
    exit('SQL Error: ' . $e->getMessage());
} catch (Exception $e) {
    // Handle other exceptions
    exit('Error: ' . $e->getMessage());
}

function displayStars($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating - $fullStars >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    return str_repeat('&#9733;', $fullStars) . str_repeat('&#9734;', $halfStar) . str_repeat('&#9734;', $emptyStars);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medici</title>
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
        .filter-form {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        }
        .filter-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .doctor-card {
            background-color: #fff;
            box-shadow: 0 6px 9px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            margin-bottom: 20px;
            transition: transform 0.3s ease-in-out;
        }
        .doctor-details {
            padding: 20px;
        }
        .doctor-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .specialization {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .average-rating {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .btn-view-profile {
            width: 120px;
            display: block;
            margin-top: -70px;
        }
        .card:hover {
            transform: translateY(-5px);
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
        <h2 class="mt-5 mb-4">Doctorii clinicii noastre</h2>
        <!-- Filter Form -->
        <div class="filter-form mb-5 mt-5">
            <form method="GET" action="all_doctors.php" class="form-inline">
                <div class="form-group mr-3">
                    <label for="name" class="mr-2">Nume:</label>
                    <input type="text" name="name" id="name" class="form-control" style="width:150px;" value="<?= htmlspecialchars($nameFilter) ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="specialization" class="mr-2">Specializare:</label>
                    <select name="specialization" id="specialization" class="form-control" style="width:150px;">
                        <option value="">Toate</option>
                        <!-- Fetch specialization options dynamically from your database -->
                        <?php
                        try {
                            $stmt = mysqli_prepare($connect, "SELECT DISTINCT specialization FROM doctors WHERE approved = 1");
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_bind_result($stmt, $specialization);
                            while (mysqli_stmt_fetch($stmt)) {
                                echo '<option value="' . htmlspecialchars($specialization) . '">' . htmlspecialchars($specialization) . '</option>';
                            }
                            mysqli_stmt_close($stmt);
                        } catch (mysqli_sql_exception $e) {
                            exit('SQL Error: ' . $e->getMessage());
                        } catch (Exception $e) {
                            exit('Error: ' . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group mr-3">
                    <label for="rating" class="mr-2">Rating:</label>
                    <select name="rating" id="rating" class="form-control" style="width:150px;">
                        <option value="">Toate</option>
                        <option value="5" <?= $ratingFilter == 5 ? 'selected' : '' ?>>5</option>
                        <option value="4" <?= $ratingFilter == 4 ? 'selected' : '' ?>>4</option>
                        <option value="3" <?= $ratingFilter == 3 ? 'selected' : '' ?>>3</option>
                        <option value="2" <?= $ratingFilter == 2 ? 'selected' : '' ?>>2</option>
                        <option value="1" <?= $ratingFilter == 1 ? 'selected' : '' ?>>1</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Aplică filtrele</button>
            </form>
        </div>

        <!-- Doctor Cards -->
        <div class="row">
        <?php
        if (empty($doctors)):
        ?>
        <div class="col-md-12">
            <p class="text-center">Niciun doctor aprobat nu a fost găsit.</p>
        </div>
        <?php else: ?>
            <?php foreach ($doctors as $doctor): ?>
            <div class="col-md-12 mb-3">
                <div class="doctor-card">
                    <div class="doctor-details p-4">
                        <h5 class="doctor-name"><?= htmlspecialchars($doctor['firstName']) . ' ' . htmlspecialchars($doctor['lastName']) ?></h5>
                        <p class="specialization">Specializare: <?= htmlspecialchars($doctor['specialization']) ?></p>
                        <p class="average-rating">Rating: <?= displayStars($doctor['averageRating']) ?> (<?= number_format($doctor['averageRating'], 1) ?>)</p>
                        <a href="pdoctor_profile.php?doctorId=<?= $doctor['doctorId'] ?>" class="btn btn-primary btn-view-profile float-right">Vezi Profilul</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
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