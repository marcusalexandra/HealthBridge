<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acasă</title>
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
        position: relative;
        z-index: 10;
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
    .logo-container {
        padding: 20px 0;
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
    .carousel-item img {
        width: 100%;
        height: 600px;
        object-fit: cover;
    }
    .carousel-control-prev-icon, .carousel-control-next-icon {
        display: none;
    }
    .service-section, .testimonial-section, .team-section {
        padding: 60px 0;
        text-align: center;
    }
    .service-section h2, .testimonial-section h2, .team-section h2 {
        margin-bottom: 40px;
    }
    .testimonial-section .carousel-item blockquote {
        background-color: #f8f9fa;
        border-left: 5px solid #007bff;
        padding: 20px;
        margin: 20px;
        border-radius: 5px;
    }
    .testimonial-section .carousel-item blockquote p {
        font-style: italic;
        color: #333;
    }
    .testimonial-section .carousel-item blockquote footer {
        font-size: 0.9em;
        color: #555;
    }
    .team-section img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .team-section .col-md-3 {
        margin-bottom: 30px;
    }
    .team-section h5 {
        margin-top: 10px;
        margin-bottom: 5px;
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <div class="logo-container">
            <a href="index.php">
                <img class="logo" src="uploads/Logo.png" alt="logo" style="width: 120px; height: 75px;">
            </a>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto" style="padding: 10px;">
                <li class="nav-item"><a class="nav-link" href="index.php">Acasă</a></li>
                <li class="nav-item"><a class="nav-link" href="login/login.php">Conectare</a></li>
                <li class="nav-item"><a class="nav-link" href="#footer">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="login/login.php">Medici</a></li>
            </ul>
        </div>
    </div>
</nav>

<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="uploads/carousel1.jpg" class="d-block w-100" alt="...">
        </div>
        <div class="carousel-item">
            <img src="uploads/carousel2.jpg" class="d-block w-100" alt="...">
        </div>
        <div class="carousel-item">
            <img src="uploads/carousel3.jpg" class="d-block w-100" alt="...">
        </div>
    </div>
</div>

<section class="service-section">
    <div class="container">
        <h2>Serviciile Noastre</h2>
        <div class="row">
            <div class="col-md-4">
                <i class="fas fa-stethoscope fa-3x"></i>
                <h4>Consult Medical</h4>
                <p>Consultatii medicale de specialitate realizate de cei mai buni medici din domeniu.</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-heartbeat fa-3x"></i>
                <h4>Diagnostic Avansat</h4>
                <p>Tehnologie de ultima generatie pentru diagnosticarea corecta si rapida a afectiunilor.</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-pills fa-3x"></i>
                <h4>Tratamente Personalizate</h4>
                <p>Tratamente adaptate nevoilor fiecarui pacient pentru a obtine cele mai bune rezultate.</p>
            </div>
        </div>
    </div>
</section>

<section class="testimonial-section bg-light">
    <div class="container">
        <h2>Testimoniale</h2>
        <div id="testimonialCarousel" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <blockquote class="blockquote">
                        <p class="mb-0">O echipă extraordinară, mereu atentă la nevoile pacienților. Recomand cu încredere!</p>
                        <footer class="blockquote-footer">Ana Popescu</footer>
                    </blockquote>
                </div>
                <div class="carousel-item">
                    <blockquote class="blockquote">
                        <p class="mb-0">Servicii de înaltă calitate și personal profesionist. Mă simt în siguranță la fiecare vizită.</p>
                        <footer class="blockquote-footer">Mihai Ionescu</footer>
                    </blockquote>
                </div>
                <div class="carousel-item">
                    <blockquote class="blockquote">
                        <p class="mb-0">Clinica oferă cele mai bune servicii medicale din oraș. Sunt foarte mulțumit de fiecare dată.</p>
                        <footer class="blockquote-footer">Ioana Gheorghe</footer>
                    </blockquote>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="team-section">
    <div class="container">
        <h2>Echipa Noastră</h2>
        <div class="row">
            <div class="col-md-3">
                <img src="uploads/doctor1.jpg" class="rounded-circle" alt="Doctor 1">
                <h5>Dr. Andrei Ionescu</h5>
                <p>Cardiolog</p>
            </div>
            <div class="col-md-3">
                <img src="uploads/doctor2.jpg" class="rounded-circle" alt="Doctor 2">
                <h5>Dr. Elena Popa</h5>
                <p>Dermatolog</p>
            </div>
            <div class="col-md-3">
                <img src="uploads/doctor3.jpg" class="rounded-circle" alt="Doctor 3">
                <h5>Dr. Mihai Dumitrescu</h5>
                <p>Neurolog</p>
            </div>
            <div class="col-md-3">
                <img src="uploads/doctor4.jpg" class="rounded-circle" alt="Doctor 4">
                <h5>Dr. Ioana Radu</h5>
                <p>Oftalmolog</p>
            </div>
        </div>
    </div>
</section>

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
            <div class="col-md-6 col-lg-4" style="left: 240px;">
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
