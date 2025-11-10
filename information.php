<?php
    require_once 'conn.php'; 
    require_once 'auth.php'; 

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
    <title>Information</title>
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
    
    <div id="csg_container">
        <div id="csg_inner">
            <div class="csg_item">
                <div class="csg_content">
                    <h1>Dołącz do społeczności</h1>
                    <p>Poznaj nowych ludzi, dołącz do grup, stwórz klany, czatuj w grze i wiele więcej! Dzięki 100 milionom potencjalnych znajomych (lub wrogów) zabawa nigdy się nie kończy.</p>
                    <a href="community.php">Odwiedź społeczność</a>
                </div>
                <div class="csg_images">
                    <img src="img\others\infoImages\community_pt1.png" class="csg_img">
                    <img src="img\others\infoImages\community_pt2.png" class="csg_img">
                    <img src="img\others\infoImages\community_pt3.png" class="csg_img">
                </div>
            </div>
            <div class="csg_item">
                <div class="csg_images">
                    <img src="img\others\infoImages\hardware_pt1.png" class="csg_img">
                    <img src="img\others\infoImages\hardware_pt2.png" class="csg_img">
                </div>
                <div class="csg_content">
                    <h1>Skorzystaj z Gier Steam</h1>
                    <p>Odkryj bibliotekę tysięcy tytułów, od hitów AAA po niezależne perełki. Dzięki zapisom w chmurze Steam Cloud i automatycznym aktualizacjom, Twoje gry są zawsze gotowe do uruchomienia na dowolnym komputerze.</p>
                    <a href="store.php">Przeglądaj gry</a>
                </div>
            </div>
            <div class="csg_item">
                <div class="csg_content">
                    <h1>Rozbudowany support</h1>
                    <p>Napotkałeś problem z grą lub masz kłopot z kontem? Nasza Pomoc techniczna Steam jest dostępna 24/7, by Ci asystować. Przejrzyj naszą bazę wiedzy lub złóż zlecenie, aby szybko wrócić do gry!</p>
                    <a href="helpdesk.php">Skorzystaj z pomocy</a>
                </div>
                <div class="csg_images">
                    <img src="img\others\infoImages\steamworks_pt1.png" class="csg_img">
                    <img src="img\others\infoImages\steamworks_pt2.png" class="csg_img">
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            const options = {
                root: null, 
                rootMargin: '0px',
                threshold: 0.1 
            };

            const callback = (entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            };

            const observer = new IntersectionObserver(callback, options);

            const itemsToAnimate = document.querySelectorAll('.csg_item');
 
            itemsToAnimate.forEach(item => {
                observer.observe(item);
            });
        });
    </script>
</body>
</html>