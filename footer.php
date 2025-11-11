<?php
$current_year = date('Y');
?>

<footer>
    <div class="footer-container">

        <div class="footer-column">
            <h3>O Projekcie</h3>
            <p>
                Projekt semestralny został stworzony w celu praktycznego zastosowania i demonstracji umiejętności z zakresu tworzenia aplikacji internetowych. Aplikacja odwzorowuje kluczowe elementy interfejsu i logiki znanej platformy cyfrowej dystrybucji gier.
            </p>
        </div>

        <div class="footer-column">
            <h3>Autor i Kontakt</h3>
            <ul>
                <li><strong>Imię i Nazwisko:</strong> Kacper Balcerek</li>
                <li><strong>Email:</strong> <a href="mailto:kacperbalcerek2006@gmail.com">kacperbalcerek2006@gmail.com</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Kod i Technologie</h3>
            <p>Zapraszam do przejrzenia kodu źródłowego projektu na moim GitHubie.</p>
            <ul>
                <li>
                    <a href="https://github.com/Kacper-Bal/ProjektSemestralny" target="_blank" rel="noopener noreferrer">
                        Repozytorium GitHub
                        <svg viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>
                    </a>
                </li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo $current_year; ?> Kacper Balcerek. Wszelkie prawa zastrzeżone. Projekt realizowany w celach edukacyjnych.</p>
    </div>
</footer>
    <script src="js/common.js" defer></script> 
</body>
