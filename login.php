<?php
require_once('conn.php');
require_once('auth.php');

if ($currentUser) {
    header("Location: index.php");
    exit;
}

$savedLogin = $_COOKIE['cookie_login'] ?? '';
$savedPassword = $_COOKIE['cookie_password'] ?? '';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    if (empty($login) || empty($password)) {
        $error = "Wszystkie pola są wymagane.";
    } else {
        $hashedPassword = hash('sha256', $password . PASSWORD_SALT);

        $stmt = $conn->prepare("SELECT id, username, email, role FROM users 
        WHERE (username = ? OR email = ?) AND password = ?");
        $stmt->bind_param("sss", $login, $login, $hashedPassword);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $userId = $user['id'];

            $sessionToken = bin2hex(random_bytes(16));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $sessionToken, $expiresAt);
            $stmt->execute();

            setcookie('session_token', $sessionToken, time() + 3600, "/");

            if ($rememberMe) {
                setcookie('cookie_login', $login, time() + (30 * 24 * 3600), "/");
                setcookie('cookie_password', $password, time() + (30 * 24 * 3600), "/");
            } else {
                setcookie('cookie_login', '', time() - 3600, "/");
                setcookie('cookie_password', '', time() - 3600, "/");
            }

            header("Location: index.php");
            exit;
        } else {
            $error = "Nieprawidłowa nazwa użytkownika/email lub hasło.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Sign In</title>
    <link rel="stylesheet" href="style/styleLogin.css">
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="icon" type="image/png" href="img/others/logo_steam_icon.png">

</head>
<body>
    <?php include('header.php'); ?>
    <div id="container">
        <div>
            <h1>Sign In</h1>
            <br>
            <br>
            <div id="content">
                <form class="form-login" method="post">
                    <label>SIGN IN WITH ACCOUNT NAME OR EMAIL</label>
                    <input type="text" name="login" value="<?php echo htmlspecialchars($savedLogin); ?>">
                    <label>PASSWORD</label>
                    <input type="password" name="password" value="<?php echo htmlspecialchars($savedPassword); ?>">
                    
                    <div id="remember">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <label for="remember_me">REMEMBER ME</label>
                    </div>
                    
                    <input id="button" type="submit" value="Sign In">

                    <?php if ($error): ?>
                        <p style="color: red; text-align: center; margin-top: 15px;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </form>
                <span class="leftBox">
                <h2>Don't have an account yet?</h2><br><h2>Create it now!</h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="60%" height="60%" fill="currentColor" class="bi bi-person-fill-add" viewBox="0 0 16 16">
                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0m-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                    <path d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4"/>
                    </svg>
                <a href="register.php" class="btnHREF">Register</a>
            </span>
        </div>
    </div>
</div>

    <?php include('footer.php'); ?>
    <script src="js/authForms.js" defer></script> 
</body>
</html>