<?php
require_once 'auth.php';

$profileUsername = $_GET['user'] ?? null;
$pageTitle = 'Nie znaleziono użytkownika';
$profileUser = null;

if (!$profileUsername) {
    header('Location: index.php');
    exit;
}

$usernameEsc = $conn->real_escape_string($profileUsername);
$query = "SELECT id, username, avatar_filename, profile_color 
          FROM users 
          WHERE username = '$usernameEsc'";

$result = $conn->query($query);

if ($result && $result->num_rows === 1) {
    $profileUser = $result->fetch_assoc();
    $pageTitle = htmlspecialchars($profileUser['username']);
}

$headerColor = htmlspecialchars($profileUser['profile_color'] ?? '#141E2A');

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleProfile.css">
</head>
<body>
    <?php include('header.php'); ?>

    <?php if ($profileUser): ?>
        <div class="profile-header-banner" style="background-color: <?php echo $headerColor; ?>;">
            
            <div class="profile-header-content">
                
                <img 
                    src="img/avatars/<?php echo htmlspecialchars($profileUser['avatar_filename'] ?? 'default_avatar.png'); ?>" 
                    alt="Awatar <?php echo htmlspecialchars($profileUser['username']); ?>"
                    class="profile-avatar"
                >
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($profileUser['username']); ?></h1>
                </div>

            </div>
        </div>
        <div id="profile-container-main">
            <h2>Aktywność użytkownika</h2>
            <p>(Wkrótce...)</p>
        </div>

    <?php else: ?>
        <div id="profile-container-main">
            <h1>Nie znaleziono użytkownika</h1>
            <p>Użytkownik o nazwie "<?php echo htmlspecialchars($profileUsername); ?>" nie został znaleziony.</p>
        </div>
    <?php endif; ?>

    <?php include('footer.php'); ?>
</body>
</html>