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
    <div class="function_container">
        <div class="function_content">
            <div class="function_text">
                <h1>Funkcje</h1>
                <pre>
Ciągle pracujemy nad nowymi 
aktualizacjami i funkcjonalnościami 
na Steam, takimi jak:
                </pre>
            </div>
            <div class="function_grid">
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-steamchat.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Czat Steam</h2>
                        <p>Rozmawiaj ze znajomymi i grupami za pomocą tekstu lub głosu bez opuszczania Steam. Obsługiwane są wideo, tweety, GIF-y i wiele więcej – korzystaj z nich rozważnie.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-gamehubs.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Centra Gier</h2>
                        <p>Wszystko o twojej grze w jednym miejscu. Dołącz do dyskusji, dodaj nowe treści i bądź pierwszą osobą, która dowie się o nowych aktualizacjach.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-broadcasts.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Transmisje Steam</h2>
                        <p>Transmituj swoją rozgrywkę na żywo za pomocą jednego kliknięcia i podziel się nią ze znajomymi lub resztą społeczności.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-steamworkshop.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Warsztat Steam</h2>
                        <p>Twórz, odkrywaj i pobieraj modyfikacje oraz przedmioty ozdobne stworzone przez graczy w ponad 1000 wspieranych gier.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-steammobile.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Dostępne mobilnie</h2>
                        <p>Używaj Steam w dowolnym miejscu dzięki swojemu urządzeniu z systemem iOS lub Android z aplikacją mobilną Steam.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-earlyaccess.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Wczesny dostęp do gier</h2>
                        <p>Odkrywaj, graj i zaangażuj się w gry podczas ich rozwoju. Bądź pierwszą osobą, która ujrzy nadchodzące zmiany i stań się częścią tego procesu.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-languages.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Wielojęzyczność</h2>
                        <p>Tworzenie globalnej społeczności jest dla nas bardzo ważne, dlatego nasz klient wspiera obecnie 28 języków.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-payment.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Łatwe kupowanie</h2>
                        <p>Nasz sklep wspiera ponad 100 różnych metod płatności i ponad 35 walut, oferując wam dużą elastyczność i komfort kupowania.</p>
                    </div>
                </div>
                <div class="function_item">
                    <div class="function_item_icon">
                        <img src="img\others\functionImages\icon-controllers.svg">
                    </div>
                    <div class="function_item_about">
                        <h2>Obsługa controlerów</h2>
                        <p>Steam zachęca producentów do dodawania obsługi kontrolerów w swoich grach, w tym kontrolerów PlayStation, Xbox i Nintendo.</p>
                    </div>
                </div>
            </div>
            <div class="function_bottom">
                <div class="function_bottom_left">
                    <h1>I wiele więcej...</h1>
                    <p>Zdobywaj osiągnięcia, czytaj recenzje, uzyskuj spersonalizowane rekomendacje i wiele więcej.</p>
                </div>
                <div class="function_bottom_right">
                    <a href="register.php">ZAREJESTRUJ SIE</a>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script src="js/information.js" defer></script>
</body>
</html>