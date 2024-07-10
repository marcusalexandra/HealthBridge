<?php
session_start();
include('../SQL/connect.php');

if (isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($connect, "SELECT id, password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId, $hashedPassword);
    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['userId'] = $userId;

            if ($userId == 1) {
                header('Location: ../admin/admin_profile.php');
                exit();
            }

            // Check if the user is a doctor and approved
            $stmtDoctor = mysqli_prepare($connect, "SELECT approved FROM doctors WHERE user_id = ?");
            mysqli_stmt_bind_param($stmtDoctor, "i", $userId);
            mysqli_stmt_execute($stmtDoctor);
            mysqli_stmt_bind_result($stmtDoctor, $approved);
            if (mysqli_stmt_fetch($stmtDoctor)) {
                mysqli_stmt_close($stmtDoctor);

                if ($approved) {
                    $_SESSION['userRole'] = 'doctor';
                    header('Location: ../doctor/doctor_profile.php');
                    exit();
                } else {
                    $_SESSION['accountApprovalNeeded'] = true;
                    header('Location: login.php');
                    exit();
                }
            } else {
                // Check if the user is a patient
                $stmtPatient = mysqli_prepare($connect, "SELECT id FROM patients WHERE user_id = ?");
                mysqli_stmt_bind_param($stmtPatient, "i", $userId);
                mysqli_stmt_execute($stmtPatient);
                if (mysqli_stmt_fetch($stmtPatient)) {
                    $_SESSION['userRole'] = 'patient';
                    header('Location: ../patient/patient_profile.php');
                    exit();
                } else {
                    echo "Account does not have a recognized role.";
                    exit();
                }
                mysqli_stmt_close($stmtPatient);
            }
        } else {
            echo "Invalid email or password.";
            exit();
        }
    } else {
        mysqli_stmt_close($stmt);
        echo "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectare</title>

        <!-- Link pentru stilurile CSS -->
        <link rel="stylesheet" href="../CSS/style.css">

        <!-- Link pentru Bootstrap -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">

        <!-- Link pentru serviciul Google Fonts -->
        <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>

        <!-- Link către Font Awesome, o bibliotecă de icoane vectoriale --> 
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <script>
        // JavaScript alert based on PHP session variable
        <?php if (isset($_SESSION['accountApprovalNeeded']) && $_SESSION['accountApprovalNeeded']): ?>
        alert("Contul tau trebuie sa primeasca aprobare.");
        <?php
            unset($_SESSION['accountApprovalNeeded']); // Clear the session variable after displaying alert
            endif;
        ?>
    </script>
</head>
<body style="background-color: rgb(249, 249, 249); font-family: 'Poppins', sans-serif;">
<div class="container">
    <div class="login-form">
        <h2>Conectare</h2>
        <form action="login.php" method="post">
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <div class="d-flex justify-content-center" style="margin-top:25px;">
                <button type="submit" name="login" class="btn btn-primary btn-block" style="width: 300px;">Conectare</button>
            </div>
        </form>
        <p style="text-align:center; margin-top:15px;">Încă nu ai un cont?<a href= 'signup.php'> Creează un cont</a></p>
    </div>
</div>
</body>
</html>
