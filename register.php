<?php
session_start();

require_once('conn.php');
require_once('auth.php');

if ($currentUser) {
    header("Location: index.php");
    exit;
}

$error = null;
if (isset($_SESSION['register_error'])) {
    $error = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['register_error'] = "Wszystkie pola są wymagane.";
        header("Location: register.php");
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Niepoprawny format email.";
        header("Location: register.php");
        exit;
    } elseif ($password !== $confirmPassword) {
        $_SESSION['register_error'] = "Hasła nie są identyczne.";
        header("Location: register.php");
        exit;
    } else {
        $query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $_SESSION['register_error'] = "Nazwa użytkownika lub email już istnieje.";
            header("Location: register.php");
            exit;
        } else {
            $hashedPassword = hash('sha266', $password . PASSWORD_SALT);

            $query = "INSERT INTO users (username, email, password) 
                      VALUES ('$username', '$email', '$hashedPassword')";
            if ($conn->query($query)) {
                $userId = $conn->insert_id;
                $sessionToken = bin2hex(random_bytes(16));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                $query = "INSERT INTO sessions (user_id, session_token, expires_at) 
                          VALUES ('$userId', '$sessionToken', '$expiresAt')";
                $conn->query($query);
                setcookie('session_token', $sessionToken, time() + 3600, "/");
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['register_error'] = "Błąd bazy danych: " . $conn->error;
                header("Location: register.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="style/styleLogin.css">
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include('header.php'); ?>
    <div id="container">
        <div>
            <h1>Create Account</h1>
            <br>
            <br>
            <div id="content">
                <form class="form-login" method="post">
                    <label>ACCOUNT NAME</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">

                    <label>EMAIL ADDRESS</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                    <label>PASSWORD</label>
                    <input type="password" name="password">

                    <label>CONFIRM PASSWORD</label>
                    <input type="password" name="confirm_password"><br>
                    
                    <input id="button" type="submit" value="Create Account">

                    <?php if ($error): ?>
                        <p style="color: red; text-align: center; margin-top: 15px;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </form>
                <span class="leftBox">
                    <h2>Already have an account?</h2><br><h2>Sign in here!</h2>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-person-fill-check" viewBox="0 0 16 16">
                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                    <path d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4"/>
                    </svg>
                    <a href="login.php" class="btnHREF">Sign In</a>
                </span>
            </div>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

</body>
</html>