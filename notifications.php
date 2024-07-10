<?php
session_start();
include('SQL/connect.php');

// Redirecționează utilizatorul către pagina de login dacă nu este autentificat
if (!isset($_SESSION['userId'])) {
    header('Location: login/login.php');
    exit();
}

$userId = $_SESSION['userId'];
$message = '';
$translations = [
    'Notificare marcată ca citită.' => 'Notificare marcată ca citită.',
    'Eroare la marcarea notificării ca fiind citită:' => 'Eroare la marcarea notificării ca fiind citită:',
];

// Marchează notificarea ca fiind citită
if (isset($_POST['mark_as_read'])) {
    $notificationId = $_POST['notification_id'];
    $stmt = mysqli_prepare($connect, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $notificationId, $userId);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Notificare marcată ca citită.";
    } else {
        $message = "Eroare la marcarea notificării ca fiind citită: " . mysqli_error($connect);
    }
    mysqli_stmt_close($stmt);
}

// Interogare și preluare notificări
$stmt = mysqli_prepare($connect, "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id, $messageText, $is_read, $created_at);
$notifications = [];
while (mysqli_stmt_fetch($stmt)) {
    $notifications[] = ['id' => $id, 'message' => $messageText, 'is_read' => $is_read, 'created_at' => $created_at];
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificări</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
                // Afișează linkurile în funcție de rolul utilizatorului
                echo '<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user-circle-o" aria-hidden="true"></i> Profil</a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="patient/patient_profile.php">Vezi Profil</a>
                    <a class="dropdown-item" href="login/logout.php">Deconectare</a>
                    </div>
                    </li>';
                echo '<li class="nav-item"><a class="nav-link" href="patient/my_appointments.php">Programări și Diagnostic</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="patient/all_doctors.php">Doctori</a></li>';
                echo '<li class="nav-item notification-icon"><a class="nav-link" href="notifications.php"><i class="fa fa-bell" aria-hidden="true"></i><span class="notification-count">3</span></a></li>';
                ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <h2 style="margin-top:50px; margin-bottom:15px;">Notificări</h2>
    <?php if ($message) echo "<p>$message</p>"; ?>
    <table class="table table-bordered" style="box-shadow: 0 6px 9px 6px rgba(0, 0, 0, 0.1);">
        <thead>
            <tr>
                <th>Mesajele</th>
                <th>Create la</th>
                <th>Status</th>
                <th>Acțiune</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $notification): ?>
            <tr>
                <td><?= htmlspecialchars($notification['message']); ?></td>
                <td><?= date_format(date_create($notification['created_at']), 'd.m.Y H:i:s'); ?></td>
                <td><?= $notification['is_read'] ? 'Citită' : 'Necitită'; ?></td>
                <td>
                    <?php if (!$notification['is_read']): ?>
                    <form method="post" action="notifications.php">
                        <input type="hidden" name="notification_id" value="<?= $notification['id']; ?>">
                        <button type="submit" name="mark_as_read" class="btn btn-success">Citit</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>
