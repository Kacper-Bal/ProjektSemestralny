<?php
    require_once 'conn.php';
    require_once 'auth.php';

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleHelp.css">
    <link rel="icon" type="image/png" href="img/others/logo_steam_icon.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body>
    <?php include('header.php'); ?>
    <div id="container">
        <div class="content">
            <h1>POMOC TECHNICZNA STEAM</h1>

            <div class="sections">
                <button class="section">CONTACT US</button>
                <div class="panel">
                <p><strong>Email:</strong> <a class="link" href="mailto:kacperbalcerek2006@gmail.com">kacperbalcerek2006@gmail.com</a></p>
                <p><strong>Number:</strong> +48 519 423 893</p>
                <p><strong>Fax:</strong> +48-208-1234567</p>

                </div>

                <button class="section">VIDEO TUTORIAL</button>
                <div class="panel">
                    <video controls>
                        <source src="img\others\indian.mp4" type="video/mp4">
                    </video>
                </div>

                <button class="section">COMMON FIXES</button>
                <div class="panel">
                <p><strong>JUST REFRESH </strong><svg viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>like [#1386]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-219.000000, -760.000000)" fill="white"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M163,610.021159 L163,618.021159 C163,619.126159 163.93975,620.000159 165.1,620.000159 L167.199999,620.000159 L167.199999,608.000159 L165.1,608.000159 C163.93975,608.000159 163,608.916159 163,610.021159 M183.925446,611.355159 L182.100546,617.890159 C181.800246,619.131159 180.639996,620.000159 179.302297,620.000159 L169.299999,620.000159 L169.299999,608.021159 L171.104948,601.826159 C171.318098,600.509159 172.754498,599.625159 174.209798,600.157159 C175.080247,600.476159 175.599997,601.339159 175.599997,602.228159 L175.599997,607.021159 C175.599997,607.573159 176.070397,608.000159 176.649997,608.000159 L181.127196,608.000159 C182.974146,608.000159 184.340196,609.642159 183.925446,611.355159" id="like-[#1386]"> </path> </g> </g> </g> </g></svg></p>

                <img style="width: 200px;" src="img\others\refresh.gif">
                </div>

                
                <button class="section">FAQ</button>
                <div class="panel">
                <p>Pytanie 1 <a class="link" href="">HERE</a></p>
                <p>Pytanie 2 <a class="link" href="">HERE</a></p> 
                <p>Pytanie 3 <a class="link" href="">HERE</a></p> 
                <p>Pytanie 4 <a class="link" href="">HERE</a></p> 
                <p>Pytanie 5 <a class="link" href="">HERE</a></p> 
                <p>Pytanie 6 <a class="link" href="">HERE</a></p> 
                </div>
            </div>
        </div>
    </div>
    <?php include('footer.php'); ?>
    <script src="js/helpdesk.js" defer></script>
</body>
</html>