<?php
session_start();
include('../SQL/connect.php');

if (isset($_POST['signup'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = $_POST['phone_number'];
    $ssn = $_POST['ssn'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        echo "Passwords do not match.";
        exit();
    }

    // Check if email already exists
    $emailCheckQuery = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($connect, $emailCheckQuery);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $emailCheckResult = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($emailCheckResult) > 0) {
        echo "Email already exists.";
        mysqli_stmt_close($stmt);
        exit();
    }
    mysqli_stmt_close($stmt); // Close the statement after use

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into users table
    $insertUserQuery = "INSERT INTO users (firstname, lastname, email, ssn, password, phone_number) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connect, $insertUserQuery);
    mysqli_stmt_bind_param($stmt, "ssssss", $firstname, $lastname, $email, $ssn, $passwordHash, $phone_number);

    if (mysqli_stmt_execute($stmt)) {
        $last_user_id = mysqli_insert_id($connect);
        mysqli_stmt_close($stmt); // Close the statement after use

        if ($role === 'doctor') {
            $stmt = mysqli_prepare($connect, "INSERT INTO doctors (user_id, specialization, approved, gender) VALUES (?, NULL, 0, ?)");
            mysqli_stmt_bind_param($stmt, "is", $last_user_id, $gender);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Profilul de doctor a fost creat cu succes.";
            } else {
                echo "Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt); // Close the statement after use
        } else if ($role === 'patient') {
            $stmt = mysqli_prepare($connect, "INSERT INTO patients (user_id, height, weight, date_of_birth, medical_history, gender) VALUES (?, NULL, NULL, ?, NULL, ?)");
            mysqli_stmt_bind_param($stmt, "iss", $last_user_id, $date_of_birth, $gender);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Profilul de pacient a fost creat cu succes.";
            } else {
                echo "Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt); // Close the statement after use
        }
    } else {
        echo "Error: " . mysqli_error($connect);
    }
    mysqli_close($connect); // Close the connection
    header("Location: signup.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înregistrare</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script>
        window.onload = function() {
            <?php if(isset($_SESSION['message'])): ?>
                alert("<?php echo $_SESSION['message']; unset($_SESSION['message']); ?>");
            <?php endif; ?>
        };
    </script>
</head>
<body style="background-color: rgb(249, 249, 249); font-family: 'Poppins', sans-serif;">
<div class="container">
    <div class="signup-form mt-5">
        <h2>Formular Înregistrare</h2>
        <form method="post" action="signup.php">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <input type="text" class="form-control" name="firstname" placeholder="Nume" required>
                </div>
                <div class="form-group col-md-6">
                    <input type="text" class="form-control" name="lastname" placeholder="Prenume" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="form-group col-md-6">
                    <input type="text" class="form-control" name="phone_number" placeholder="Număr de telefon">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <input type="password" class="form-control" name="password" placeholder="Parolă" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W]).{8,}" title="Must contain at least one number, one uppercase and lowercase letter, one special character, and at least 8 or more characters" required>
                </div>
                <div class="form-group col-md-6">
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirmă Parola" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <input type="date" class="form-control" name="date_of_birth" placeholder="Data de naștere" required>
                </div>
                <div class="form-group col-md-6">
                    <input type="text" class="form-control" name="ssn" placeholder="CNP" pattern="[0-9]{13}" required>
                </div>
            </div>
            <div class="form-group" style="margin-top:5px;">
                <label>Sex:</label><br>
                <label class="radio-inline"><input type="radio" name="gender" value="M" required> Masculin</label>
                <label class="radio-inline"><input type="radio" name="gender" value="F" required> Feminin</label>
            </div>
            <div class="form-group form-inline">
                <label for="role" class="mr-2">Tipul de utilizator:</label>
                <select class="form-control" name="role" required style="width:280px;">
                    <option value="patient">Pacient</option>
                    <option value="doctor">Doctor</option>
                </select>
            </div>
            <div class="d-flex justify-content-center" style="margin-top:25px;">
                <button type="submit" name="signup" class="btn btn-primary" style="width: 320px;">Înregistrare</button>
            </div>
        </form>
        <p style="text-align:center; margin-top:15px;">Aveți deja un cont creat? <a href="login.php">Conectați-vă</a></p>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>

