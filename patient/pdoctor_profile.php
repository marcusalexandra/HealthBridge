<?php
session_start();
include('../SQL/connect.php');

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if (!isset($_GET['doctorId'])) {
    header('Location: all_doctors.php');
    exit();
}

$doctorId = $_GET['doctorId'];

// Fetch doctor details along with profile picture
$stmt = mysqli_prepare($connect, "SELECT users.firstname, users.lastname, doctors.specialization, users.profile_picture FROM users JOIN doctors ON users.id = doctors.user_id WHERE doctors.id = ?");
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $firstName, $lastName, $specialization, $profilePicture);
if (!mysqli_stmt_fetch($stmt)) {
    mysqli_stmt_close($stmt);
    header('Location: all_doctors.php');
    exit();
}
mysqli_stmt_close($stmt);

// Set a default profile picture if none exists
if (empty($profilePicture)) {
    $profilePicture = '../uploads/profile.png'; // Ensure this path is correct
}

// Fetch doctor's schedule
$scheduleQuery = "SELECT day, start_time, end_time FROM doctor_schedule WHERE doctor_id = ?";
$stmt = mysqli_prepare($connect, $scheduleQuery);
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $day, $start_time, $end_time);
$weekly_schedule = [];
while (mysqli_stmt_fetch($stmt)) {
    $weekly_schedule[$day][] = ['start' => $start_time, 'end' => $end_time];
}
mysqli_stmt_close($stmt);

// Fetch booked appointments
$appointmentQuery = "SELECT appointment_date, appointment_time FROM appointments WHERE doctor_id = ?";
$stmt = mysqli_prepare($connect, $appointmentQuery);
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $app_date, $app_time);
$booked_times = [];
while (mysqli_stmt_fetch($stmt)) {
    $booked_times[$app_date][] = $app_time;
}
mysqli_stmt_close($stmt);

// Fetch reviews and calculate the average rating
$reviewStmt = mysqli_prepare($connect, "SELECT stars FROM reviews WHERE doctor_id = ?");
mysqli_stmt_bind_param($reviewStmt, "i", $doctorId);
mysqli_stmt_execute($reviewStmt);
mysqli_stmt_bind_result($reviewStmt, $stars);

$ratings = [];
while (mysqli_stmt_fetch($reviewStmt)) {
    $ratings[] = $stars;
}
mysqli_stmt_close($reviewStmt);

$averageRating = 0;
if (count($ratings) > 0) {
    $averageRating = array_sum($ratings) / count($ratings);
    $averageRating = round($averageRating, 1);
}

function displayStars($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating - $fullStars >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    return str_repeat('&#9733;', $fullStars) . str_repeat('&#9733;', $halfStar) . str_repeat('&#9734;', $emptyStars);
}

// Pagination setup for reviews
$limit = 3; // Number of reviews per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Sorting setup
$sortOrder = $_GET['sort'] ?? 'newest';
$sortColumn = 'reviews.review_date';
$sortDirection = 'DESC';
if ($sortOrder === 'oldest') {
    $sortDirection = 'ASC';
} elseif ($sortOrder === 'highest') {
    $sortColumn = 'reviews.stars';
    $sortDirection = 'DESC';
} elseif ($sortOrder === 'lowest') {
    $sortColumn = 'reviews.stars';
    $sortDirection = 'ASC';
}

// Fetch reviews for pagination and sorting
$reviewStmt = mysqli_prepare($connect, "SELECT reviews.stars, reviews.comment, reviews.review_date, users.firstname, users.profile_picture
                                        FROM reviews
                                        JOIN patients ON reviews.patient_id = patients.id
                                        JOIN users ON patients.user_id = users.id
                                        WHERE reviews.doctor_id = ?
                                        ORDER BY $sortColumn $sortDirection
                                        LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($reviewStmt, "iii", $doctorId, $limit, $offset);
mysqli_stmt_execute($reviewStmt);
mysqli_stmt_bind_result($reviewStmt, $stars, $comment, $review_date, $reviewerFirstName, $reviewerProfilePicture);

$reviews = [];
while (mysqli_stmt_fetch($reviewStmt)) {
    $reviews[] = [
        'stars' => $stars,
        'comment' => $comment,
        'review_date' => $review_date,
        'reviewerFirstName' => $reviewerFirstName,
        'reviewerProfilePicture' => $reviewerProfilePicture ? $reviewerProfilePicture : '../uploads/profile.png'
    ];
}
mysqli_stmt_close($reviewStmt);

// Get the total number of reviews
$totalReviewsStmt = mysqli_prepare($connect, "SELECT COUNT(*) FROM reviews WHERE doctor_id = ?");
mysqli_stmt_bind_param($totalReviewsStmt, "i", $doctorId);
mysqli_stmt_execute($totalReviewsStmt);
mysqli_stmt_bind_result($totalReviewsStmt, $totalReviews);
mysqli_stmt_fetch($totalReviewsStmt);
mysqli_stmt_close($totalReviewsStmt);

$totalPages = ceil($totalReviews / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilul Doctorului</title>
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
        .profile-card {
            margin: 50px auto;
            box-shadow: 0 6px 9px 6px rgba(0, 0, 0, 0.1);
            background-color: white;
            border-radius: 10px;
            padding: 30px;
        }
        .profile-card img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #dee2e6;
        }
        .profile-card h3 {
            margin-top: 10px;
            color: #343a40;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .time-slot {
            margin: 5px;
        }
        .time-slot.active {
            background-color: #007bff;
            color: white;
        }
        .star {
            cursor: pointer;
            font-size: 25px;
            color: grey;
        }
        .star:hover,
        .star.selected {
            color: gold;
        }
        .flat-star {
            font-size: 25px;
            color: gold;
        }
        .review {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .reviewer-name {
            font-weight: bold;
        }
        .average-rating {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .average-rating .star {
            font-size: 20px;
        }
        .average-rating span {
            margin-left: 10px;
            font-size: 18px;
        }
        .review img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
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
        .container {
    width: 100%;
    max-width: 1200px; /* Ajustează dimensiunea maximă a containerului după necesitate */
    margin: 0 auto;
    padding: 20px;
}

.review-form {
    background-color: #ffffff;
    padding: 20px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    margin-bottom: 20px;
}

.review-form h4 {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 15px;
}

.review-form form {
    margin-top: 10px;
}

.review-form label {
    font-weight: bold;
}

.review-form .form-group {
    margin-bottom: 15px;
}

.review-form textarea.form-control {
    height: 100px;
}

.reviews {
    background-color: #ffffff;
    padding: 20px;
    border: 1px solid #ced4da;
    border-radius: 8px;
}

.reviews h4 {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
}

.reviews label {
    font-weight: bold;
    margin-right: 10px;
}

.reviews #sort-reviews {
    width: auto;
    display: inline-block;
    margin-right: 10px;
}

.reviews .btn-primary {
    margin-top: 5px;
}

.review {
    border-bottom: 1px solid #dee2e6;
    padding: 15px 0;
}

.review:last-child {
    border-bottom: none;
}

.review img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
}

.review .reviewer-name {
    font-weight: bold;
    margin-bottom: 5px;
}

.review .review-stars {
    color: #ffc107;
    margin-bottom: 5px;
}

.review p {
    margin-bottom: 5px;
}

.review .review-date {
    font-style: italic;
    font-size: 12px;
    color: #6c757d;
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
                $userId = $_SESSION['userId'];
                $userRole = $_SESSION['userRole'];

                if ($userId === 1) {
                    echo '<li><a href="../admin/admin_profile.php">Profile</a></li>';
                    echo '<li><a href="../admin/manage_appointments.php">Manage Appointments</a></li>';
                    echo '<li><a href="../admin/manage_users.php">Manage Users</a></li>';
                } elseif ($userRole === 'doctor') {
                    echo '<li><a href="../doctor/doctor_profile.php">Profile</a></li>';
                    echo '<li><a href="../doctor/doctor_appointments.php">Appointments</a></li>';
                } elseif ($userRole === 'patient') {
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
    <div class="card profile-card" style="top:25px; bottom:25px;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center"style="border-right: 2px solid #dee2e6;">
                    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile Picture">
                    <h3><?= htmlspecialchars($firstName) . ' ' . htmlspecialchars($lastName) ?></h3>
                    <p class="mb-3 mt-3">Specializare: <?= htmlspecialchars($specialization) ?></p>
                    <div class="average-rating ml-5" style="text-align:center;">
                        <div>
                            <?= displayStars($averageRating); ?>
                        </div>
                    <span><?= $averageRating ?> (<?= count($ratings) ?> review-uri)</span>
                    </div>
                </div>
                <div class="col-md-8">
                    <h4>Stabilește o programare</h4>
                    <!-- Appointment scheduling form -->
                    <form action="schedule_appointment.php" method="post">
                        <input type="hidden" name="doctorId" value="<?= $doctorId ?>">
                        <div class="form-group">
                            <label for="date">Alege o dată pentru programare:</label>
                            <input type="date" class="form-control" id="date" name="date" required onchange="updateAvailableTimes(this.value);">
                        </div>
                        <div class="form-group">
                            <label for="time">Alege o oră pentru programare:</label>
                            <div id="timeSlots" class="d-flex flex-wrap"></div>
                            <input type="hidden" id="time" name="time" required>
                        </div>
                        <div class="form-group">
                            <label for="notes">Motivul pentru cara dorești să faci această programare:</label>
                            <textarea class="form-control" id="notes" name="notes"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Relizează programarea</button>
                    </form>
                </div>
                <div class="col-md-12 mt-3" style="width: 1000px; border-top: 2px solid #dee2e6;">
                <h4 class="mt-4">Comentarii și Review-uri</h4>
                </div>
                <div class="col-md-12 mt-3" style="width: 1000px;">
            <?php if (isset($_SESSION['userId']) && $userRole === 'patient'): ?>
                <div class="review-form">
                    <h4>Lasă un review</h4>
                    <form action="submit_review.php" method="post">
                        <input type="hidden" name="doctorId" value="<?= $doctorId ?>">
                        <div class="form-group">
                            <label>Rating:</label>
                            <div id="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star" data-value="<?= $i ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="stars" name="stars" required>
                        </div>
                        <div class="form-group">
                            <label for="comment">Lasă un comentariu:</label>
                            <textarea class="form-control" id="comment" name="comment" rows="2" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Trimitere Review</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="width: 1000px;">
            <div class="reviews review-container mt-5">
                <h4>Review-uri</h4>
                <label for="sort-reviews">Sortare după:</label>
                <select id="sort-reviews" class="form-control" style="display: inline-block; width: auto;">
                    <option value="newest">Cele mai noi</option>
                    <option value="oldest">Cele mai vechi</option>
                    <option value="highest">Cel mai înalt Rating</option>
                    <option value="lowest">Cel mai scăzut Rating</option>
                </select>
                <button onclick="applySort()" class="btn btn-primary mb-2">Sortează</button>
                <div id="reviews-list">
                    <?php
                    foreach ($reviews as $review) {
                        echo "<div class='review'>";
                        echo "<img src='" . htmlspecialchars($review['reviewerProfilePicture']) . "' alt='Reviewer Picture'>";
                        echo "<p class='reviewer-name'>" . htmlspecialchars($review['reviewerFirstName']) . "</p>";
                        echo "<p class='review-stars'>Rating: " . displayStars($review['stars']) . "</p>";
                        echo "<p>" . htmlspecialchars($review['comment']) . "</p>";
                        echo "<p class='review-date'>Data: " . date("F j, Y, g:i a", strtotime($review['review_date'])) . "</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="<?= ($i == $page) ? 'active' : '' ?>">
                                <a href="?doctorId=<?= $doctorId ?>&page=<?= $i ?>&sort=<?= $sortOrder ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
        </div>
        </div>
    </div>
    </div>
    <script>
        var schedule = <?= json_encode($weekly_schedule); ?>;
        var bookedTimes = <?= json_encode($booked_times); ?>;
        function updateAvailableTimes(selectedDate) {
            var date = new Date(selectedDate);
            var dayOfWeek = date.toLocaleString('en-us', {weekday: 'long'});
            var intervals = schedule[dayOfWeek] || [];
            var timeSlotsContainer = document.getElementById('timeSlots');
            var selectedTimeInput = document.getElementById('time');
            timeSlotsContainer.innerHTML = ''; // Clear previous time slots

            intervals.forEach(function(interval) {
                var times = generateTimeSlots(interval.start, interval.end);
                times.forEach(function(time) {
                    var isBooked = bookedTimes[selectedDate] && bookedTimes[selectedDate].includes(time);
                    var timeSlot = document.createElement('button');
                    timeSlot.className = isBooked ? 'btn btn-secondary disabled time-slot' : 'btn btn-primary time-slot';
                    timeSlot.type = 'button';
                    timeSlot.textContent = time;
                    if (!isBooked) {
                        timeSlot.onclick = function() {
                            document.querySelectorAll('.time-slot').forEach(t => t.classList.remove('active'));
                            timeSlot.classList.add('active');
                            selectedTimeInput.value = time; // Set the selected time
                        };
                    }
                    timeSlotsContainer.appendChild(timeSlot);
                });
            });

            if (timeSlotsContainer.children.length === 0) {
                var opt = document.createElement('div');
                opt.textContent = 'Doctorul nu are program';
                opt.className = 'btn btn-danger disabled';
                timeSlotsContainer.appendChild(opt);
            }
        }

        function generateTimeSlots(start, end) {
            var result = [];
            var current = new Date('1970-01-01T' + start + 'Z');
            var endTime = new Date('1970-01-01T' + end + 'Z');
            while (current < endTime) {
                result.push(current.toISOString().substr(11, 8));
                current.setHours(current.getHours() + 1);
            }
            return result;
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('stars');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-value');
                ratingInput.value = rating; // Set the hidden input's value to the selected rating
                updateStars(rating);
            });

            star.addEventListener('mouseenter', function() {
                highlightStars(this.getAttribute('data-value')); // Highlight stars on hover
            });

            star.addEventListener('mouseleave', function() {
                highlightStars(ratingInput.value); // Reset highlights when not hovering
            });
        });

        function updateStars(rating) {
            highlightStars(rating); // Update visual stars based on the selected rating
        }

        function highlightStars(rating) {
            stars.forEach(star => {
                if (parseInt(star.getAttribute('data-value')) <= parseInt(rating)) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }
    });
    </script>

    

    <script>
    function applySort() {
        const sortValue = document.getElementById('sort-reviews').value;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', sortValue);
        urlParams.set('page', 1); // Reset to the first page on sort
        window.location.search = urlParams.toString();
    }
    </script>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.3.3/dist/umd/popper.min.js"></script>
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
