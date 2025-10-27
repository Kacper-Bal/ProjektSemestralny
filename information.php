<?php
    require_once 'conn.php'; 
    require_once 'auth.php'; 

    
$query="SELECT name FROM games order by RAND() LIMIT 12";
$result=$conn->query($query);

$queryUser="SELECT count(*) from users";
$resultUser = $conn->query($queryUser);
$resultUser = $resultUser->fetch_row();

$queryGames="SELECT count(*) from games";
$resultGames = $conn->query($queryGames);
$resultGames = $resultGames -> fetch_row();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Społeczność - Recenzje</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleInfo.css">
</head>
<body>
    <?php include('header.php'); ?>

    <div class="about_container">
        <div class="about_content">
            <img src="img/others/logo_steam.svg" style="width: 80%">
            <p>Steam to najlepsze miejsce do grania omawiania i tworzenia gier<p>
                <span class="numbers">
                    <span class="number">
                        &#x2022; <span class="firstP">UŻYTKOWNICY</span><br>
                        <?php echo $resultUser[0];?>
                    </span>
                    <span class="number">
                        &#x2022; <span class="firstP">GRY</span><br>
                        <?php echo $resultGames[0];?>
                    </span>
                </span>
            <a href="https://github.com/Kacper-Bal/ProjektSemestralny" class="btn">ZAINSTALUJ STEAM</a>
        </div>
        <div class="about_video">
            <video width="100%" height="auto" autoplay="" muted="" loop="" playsinline="">
                <source src="img/others/about_information.mp4" type="video/mp4">
            </video>
            <div id="about_video_gradient"></div>
        </div>
        
    </div>

    <?php include('footer.php'); ?>
</body>
</html>