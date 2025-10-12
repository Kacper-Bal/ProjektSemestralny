-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Wrz 26, 2025 at 07:33 AM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `steam`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `developers`
--

CREATE TABLE `developers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `developers`
--

INSERT INTO `developers` (`id`, `name`) VALUES
(10, 'Arrowhead Game Studios'),
(12, 'Battlestate Games'),
(3, 'CD Projekt Red'),
(6, 'Electronics Arts'),
(4, 'FromSoftware Inc'),
(7, 'Rockstar Games'),
(13, 'Sandfall Interactive'),
(5, 'Santa Monica'),
(9, 'Starbreeze Studio'),
(11, 'Team17 Digital'),
(8, 'VOID Interactive');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `publisher_id` int(11) NOT NULL,
  `developer_id` int(11) NOT NULL,
  `price` decimal(5,2) NOT NULL DEFAULT 0.00,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `name`, `description`, `publisher_id`, `developer_id`, `price`, `date`) VALUES
(1, 'Wiedźmin 3: Dziki Gon', 'Nazywasz się Geralt z Rivii i jesteś wiedźminem w świecie zniszczonym wojną i pełnym krwiożerczych bestii. Twoje zadanie? Musisz odnaleźć Ciri — swoją przybraną córkę obdarzoną mocą zdolną uratować świat lub pogrążyć go w ruinie.', 1, 3, 99.99, '2025-05-18'),
(2, 'Cyberpunk 2077', 'Cyberpunk 2077 to pełna akcji gra role-playing, której akcja toczy się w Night City, megalopolis rządzonym przez obsesyjną pogoń za władzą, sławą i przerabianiem własnego ciała.', 1, 3, 199.99, '2020-12-10'),
(3, 'Dark Souls', 'I wtedy zapłonął ogień. Raz jeszcze przeżyj emocjonujące przygody w gorąco przyjętej w swoim gatunku grze, od której wszystko się zaczęło. Wróć do przepięknego, odświeżonego Lordran – teraz w wysokiej rozdzielczości i 60 FPS.', 3, 4, 149.99, '2011-09-22'),
(4, 'Dark Souls II', 'DARK SOULS™ II, stworzone przez FROM SOFTWARE, to długo oczekiwana kontynuacja wielkiego hitu z roku 2011, Dark Souls™. Ta wyjątkowa, klasyczna fabularna gra akcji zachwyciła wielu graczy na całym świecie dzięki niesamowitym wyzwaniom i radości wynikającej z ich pokonywania.', 3, 4, 149.99, '2014-04-25'),
(5, 'Dark Souls 3', 'DARK SOULS™ III znów przesuwa granice gatunku - oto najnowsza i najbardziej ambitna odsłona docenionej przez krytyków serii. Przygotuj się do walki i przywitaj mrok!', 3, 4, 199.99, '2016-04-11'),
(6, 'Sekiro: Shadows Die Twice', 'W Sekiro™: Shadows Die Twice wcielasz się w rolę \"jednorękiego wilka\" - zhańbionego, okaleczonego wojownika, który cudem uniknął śmierci. Zobowiązany chronić młodego panicza, potomka prastarego rodu, staje się on celem wielu bezlitosnych wrogów, w tym członków klanu Ashina. Gdy panicz zostaje schwytany, nic nie powstrzyma Sekiro™ przed wyruszeniem w niebezpieczną podróż w poszukiwaniu honoru - nawet śmierć.', 4, 4, 249.99, '2019-03-21'),
(7, 'God of War', 'Minęło wiele lat od czasu, gdy Kratos wywarł swą zemstę na bogach Olimpu. Teraz mieszka pośród skandynawskich bóstw i potworów. W tym okrutnym świecie, który nie zna przebaczenia, będzie musiał walczyć o przetrwanie… i przygotować do tej walki swojego syna.', 5, 5, 219.00, '2018-04-20'),
(8, 'God of War Ragnarök', 'W obliczu zbliżającego się ragnaröku Kratos i Atreus wyruszają w podróż w poszukiwaniu odpowiedzi.', 3, 5, 259.00, '2022-11-09'),
(9, 'EA Sports FC 26', 'Klub należy do Ciebie w EA SPORTS FC™ 26. Graj na swój sposób z ulepszoną rozgrywką opartą na opiniach społeczności, menedżerskimi wyzwaniami na żywo, które wprowadzają nowe wątki fabularne do nowego sezonu, a także Archetypami inspirowanymi wielkimi nazwiskami w piłce nożnej.', 6, 6, 299.00, '2025-09-26'),
(10, 'Bloodborne', 'Bloodborne to fabularna gra akcji z elementami charakterystycznymi dla produkcji Hidetaki Miyazakiego. Gra została osadzona w mrocznym świecie fantasy, stylizowanym na okres wiktoriański. Akcja Bloodborne’a toczy się w fikcyjnym mieście Yharnam, trawionym przez niezidentyfikowaną plagę przemieniającą mieszkańców w potwory. Zadaniem głównego bohatera jest stawienie czoła zagrożeniu oraz odkrycie mrocznej tajemnicy miasta.', 5, 4, 149.99, '2015-03-24'),
(11, 'Red Dead Redemption', 'Wyrusz na szlak wraz z byłym bandytą Johnem Marstonem i wytrop ostatnich żyjących członków niesławnego gangu van der Lindego w znakomicie przyjętej grze poprzedzającej Red Dead Redemption 2.', 7, 7, 129.99, '2010-05-18'),
(12, 'Red Dead Redemption 2', 'RDR2, które zdobyło ponad 250 doskonałych ocen i 175 nagród dla gry roku, to spektakularna opowieść o Arthurze Morganie i niesławnym gangu van der Lindego w czasach u zarania współczesności. Zawiera teżakże dostęp do współdzielonego przez graczy i tętniącego życiem świata Red Dead Online', 7, 7, 249.99, '2018-10-26'),
(13, 'Grand Theft Auto V', 'Zagraj w bestsellerowe Grand Theft Auto V i Grand Theft Auto Online — ulepszone z myślą o platformach najnowszej generacji, które oferują olśniewającą grafikę, szybsze wczytywanie, dźwięk przestrzenny, ekskluzywną zawartość dla graczy GTA Online i nie tylko.', 7, 7, 129.99, '2013-09-17'),
(14, 'Ready or Not', 'Ready or Not to dynamiczna, taktyczna strzelanka FPP przedstawiająca operacje współczesnych oddziałów policyjnych SWAT, które muszą neutralizować przestępców i stawiać czoła niebezpiecznym sytuacjom.', 8, 8, 169.99, '2021-12-17'),
(15, 'PAYDAY 2', 'PAYDAY 2 is an action-packed, four-player co-op shooter that once again lets gamers don the masks of the original PAYDAY crew - Dallas, Hoxton, Wolf and Chains - as they descend on Washington DC for an epic crime spree.', 9, 9, 45.99, '2013-08-13'),
(16, 'PAYDAY 3', 'PAYDAY 3 to wyczekiwana kontynuacja jednej z najpopularniejszych kooperacyjnych strzelanek w historii. Od czasu premiery gracze PAYDAY cieszą się idealnie zaplanowanymi i wykonanymi napadami. Właśnie to sprawia, że PAYDAY jest dynamiczną kooperacyjną produkcją FPS, która nie ma sobie równych.', 9, 9, 129.99, '2023-09-21'),
(17, 'Helldivers 2', 'Ostatnia linia galaktycznej ofensywy. Wstąp w szeregi Helldiversów i zmierz się z wrogą galaktyką w walce o wolność w tej szybkiej i zaciekłej trzecioosobowej strzelance.', 5, 10, 169.00, '2023-09-21'),
(18, 'Hell Let Loose', 'Hell Let Loose to ostra strzelanina FPS osadzona w realiach II wojny światowej. W niesamowitych bitwach bierze udział po 100 graczy kierujących piechurami, czołgami i artylerią. Linia frontu wciąż się zmienia, a całość oparta jest na zasobach w sposób znany z gier RTS.', 10, 11, 109.99, '2025-07-27'),
(19, 'Escape from Tarkov', 'Escape from Tarkov to oryginalny FPS typu extraction, gdzie każdy rajd to ryzyko życia. Przygotuj się, wytrwaj i walcz z bezlitosnymi PMC i Scavami, zdobądź przewagę taktyczną i chroń łup — tylko ekstrakcja zdecyduje, czy przeżyjesz, czy stracisz wszystko.', 11, 12, 149.99, '2017-07-27'),
(20, 'Clair Obscur: Expedition 33', 'Poprowadź Ekspedycję 33, by pokonać Malarkę i uniemożliwić jej dalsze kreślenie śmierci. Odkryj cudowny świat zainspirowany Francją okresu belle époque i staw czoła wyjątkowym wrogom w tej turowej grze RPG z mechanikami w czasie rzeczywistym.', 12, 13, 179.00, '2025-04-24');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `game_platforms`
--

CREATE TABLE `game_platforms` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_platforms`
--

INSERT INTO `game_platforms` (`id`, `game_id`, `platform_id`) VALUES
(3, 1, 1),
(4, 1, 2),
(2, 1, 3),
(1, 1, 4),
(7, 2, 1),
(8, 2, 2),
(6, 2, 3),
(5, 2, 4),
(10, 3, 1),
(11, 3, 2),
(9, 3, 3),
(13, 5, 1),
(14, 5, 2),
(12, 5, 3),
(16, 6, 1),
(17, 6, 2),
(15, 6, 3),
(19, 7, 1),
(18, 7, 3),
(21, 8, 1),
(20, 8, 3),
(23, 9, 1),
(24, 9, 2),
(22, 9, 3),
(25, 10, 3),
(27, 11, 1),
(28, 11, 2),
(26, 11, 3),
(30, 12, 1),
(31, 12, 2),
(29, 12, 3),
(33, 13, 1),
(34, 13, 2),
(32, 13, 3),
(35, 14, 1),
(37, 15, 1),
(38, 15, 2),
(36, 15, 3),
(40, 16, 1),
(41, 16, 2),
(39, 16, 3),
(43, 17, 1),
(42, 17, 3),
(45, 18, 1),
(46, 18, 2),
(44, 18, 3),
(47, 19, 1),
(49, 20, 1),
(50, 20, 2),
(48, 20, 3);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `game_tags`
--

CREATE TABLE `game_tags` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_tags`
--

INSERT INTO `game_tags` (`id`, `game_id`, `tag_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 2, 1),
(5, 2, 2),
(6, 2, 3),
(7, 2, 9),
(8, 3, 1),
(9, 3, 2),
(10, 3, 3),
(11, 4, 1),
(12, 4, 2),
(13, 4, 3),
(14, 5, 1),
(15, 5, 2),
(16, 5, 3),
(17, 6, 1),
(18, 6, 2),
(19, 6, 3),
(20, 7, 1),
(21, 7, 2),
(22, 7, 3),
(23, 8, 1),
(24, 8, 2),
(25, 8, 3),
(28, 9, 4),
(26, 9, 7),
(27, 9, 8),
(29, 10, 1),
(30, 10, 2),
(31, 10, 3),
(32, 11, 1),
(33, 11, 2),
(34, 11, 3),
(35, 11, 9),
(36, 12, 1),
(37, 12, 2),
(38, 12, 3),
(39, 12, 9),
(40, 13, 1),
(42, 13, 3),
(44, 13, 4),
(41, 13, 8),
(43, 13, 9),
(45, 14, 1),
(48, 14, 4),
(49, 14, 5),
(46, 14, 8),
(47, 14, 9),
(50, 15, 1),
(53, 15, 4),
(54, 15, 5),
(51, 15, 8),
(52, 15, 9),
(55, 16, 1),
(58, 16, 4),
(59, 16, 5),
(56, 16, 8),
(57, 16, 9),
(60, 17, 1),
(61, 17, 6),
(62, 17, 8),
(63, 17, 9),
(64, 18, 1),
(67, 18, 4),
(65, 18, 8),
(66, 18, 9),
(68, 19, 1),
(72, 19, 4),
(69, 19, 6),
(70, 19, 8),
(71, 19, 9),
(73, 20, 1),
(74, 20, 2),
(76, 20, 3),
(77, 20, 5),
(75, 20, 6);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `platforms`
--

CREATE TABLE `platforms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platforms`
--

INSERT INTO `platforms` (`id`, `name`) VALUES
(4, 'Nintendo'),
(3, 'Playstation'),
(1, 'Windows'),
(2, 'Xbox');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publishers`
--

INSERT INTO `publishers` (`id`, `name`) VALUES
(4, 'Activision'),
(3, 'Bandai Namco Entertainment'),
(11, 'Battlestate Games'),
(1, 'CD Projekt Red'),
(6, 'Electronic Arts'),
(2, 'FromSoftware Inc'),
(12, 'Kepler Interactive'),
(5, 'PlayStation Studios'),
(7, 'Rockstar Games'),
(9, 'Starbreeze Studio'),
(10, 'Team17 Digital'),
(8, 'VOID Interactive');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `session_token`, `created_at`, `expires_at`) VALUES
(3, 1, '631b489f51639f4f439ed976c23badb0', '2025-09-23 05:58:09', '2025-09-23 08:58:09'),
(5, 1, '77f59d74854b07d6776bcd73de787444', '2025-09-23 07:54:38', '2025-09-23 10:54:38');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`) VALUES
(1, 'Action'),
(2, 'Adventure'),
(7, 'Casual'),
(6, 'Indie'),
(8, 'Multiplayer'),
(10, 'Puzzle'),
(3, 'RPG'),
(9, 'Shooter'),
(4, 'Simulation'),
(5, 'Strategy');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password`, `role`) VALUES
(1, 'jann@example.com', 'jann', '543360c1ecea52aa7620ccbb32357b72634348350be51c8577da1cf3f7175342', 1);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user_games`
--

CREATE TABLE `user_games` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cart_unique` (`user_id`,`game_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indeksy dla tabeli `developers`
--
ALTER TABLE `developers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publishers_id` (`publisher_id`),
  ADD KEY `developer_id` (`developer_id`);

--
-- Indeksy dla tabeli `game_platforms`
--
ALTER TABLE `game_platforms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_platform_unique` (`game_id`,`platform_id`),
  ADD KEY `platform_id` (`platform_id`);

--
-- Indeksy dla tabeli `game_tags`
--
ALTER TABLE `game_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_tag_unique` (`game_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indeksy dla tabeli `platforms`
--
ALTER TABLE `platforms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indeksy dla tabeli `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeksy dla tabeli `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeksy dla tabeli `user_games`
--
ALTER TABLE `user_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_game_unique` (`user_id`,`game_id`),
  ADD KEY `game_id` (`game_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `developers`
--
ALTER TABLE `developers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `game_platforms`
--
ALTER TABLE `game_platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `game_tags`
--
ALTER TABLE `game_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `platforms`
--
ALTER TABLE `platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_games`
--
ALTER TABLE `user_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_ibfk_1` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `games_ibfk_2` FOREIGN KEY (`developer_id`) REFERENCES `developers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_platforms`
--
ALTER TABLE `game_platforms`
  ADD CONSTRAINT `game_platforms_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_platforms_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_tags`
--
ALTER TABLE `game_tags`
  ADD CONSTRAINT `game_tags_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_games`
--
ALTER TABLE `user_games`
  ADD CONSTRAINT `user_games_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_games_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
