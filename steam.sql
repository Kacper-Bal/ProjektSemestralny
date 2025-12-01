-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 08:46 PM
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

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `game_id`) VALUES
(19, 1, 24);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `developers`
--

CREATE TABLE `developers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_color` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `developers`
--

INSERT INTO `developers` (`id`, `name`, `logo_color`) VALUES
(3, 'CD Projekt Red', '#170f0f'),
(4, 'FromSoftware Inc', '#0c0c0c'),
(5, 'Santa Monica', '#2c1314'),
(6, 'Electronics Arts', '#7c2945'),
(7, 'Rockstar Games', '#e6a019'),
(8, 'VOID Interactive', '#141E2A'),
(9, 'Starbreeze Studio', '#956ac4'),
(10, 'Arrowhead Game Studios', '#c3c3c3'),
(11, 'Team17 Digital', '#624674'),
(12, 'Battlestate Games', '#282828'),
(13, 'Sandfall Interactive', '#141E2A'),
(14, 'SMG Fiction', '#e08857'),
(17, 'Traveller\'s Tales', '#535021'),
(18, 'Supergiant Games', '#270703'),
(19, 'Team Cherry', '#da3c53'),
(20, 'Quantic Dream', '#243b46'),
(21, 'Clever Endeavour Studio', '#54718d'),
(22, 'Behaviour Interactive', '#4c4f55');

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
(4, 'Dark Souls 2', 'DARK SOULS™ II, stworzone przez FROM SOFTWARE, to długo oczekiwana kontynuacja wielkiego hitu z roku 2011, Dark Souls™. Ta wyjątkowa, klasyczna fabularna gra akcji zachwyciła wielu graczy na całym świecie dzięki niesamowitym wyzwaniom i radości wynikającej z ich pokonywania.', 3, 4, 149.99, '2014-04-25'),
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
(20, 'Clair Obscur: Expedition 33', 'Poprowadź Ekspedycję 33, by pokonać Malarkę i uniemożliwić jej dalsze kreślenie śmierci. Odkryj cudowny świat zainspirowany Francją okresu belle époque i staw czoła wyjątkowym wrogom w tej turowej grze RPG z mechanikami w czasie rzeczywistym.', 12, 13, 179.00, '2025-04-24'),
(21, 'LEGO® Party!', 'Rywalizuj ze znajomymi w zwariowanych strefach wyzwań i 60 przezabawnych minigrach ze swoich ulubionych zestawów LEGO, takich jak Pirates, Space, NINJAGO® i nie tylko. Graj po swojemu! Dołącz do znajomych bez względu na platformę, z której korzystają – lokalnie lub online!', 13, 14, 149.99, '2025-09-30'),
(22, 'LEGO® Star Wars™ - The Complete Saga', 'Kick Some Brick in I through VI! Play through all six Star Wars movies in one videogame! Adding new characters, new levels, new features and for the first time ever, the chance to build and battle your way through a fun Star Wars galaxy on your PC!', 13, 17, 91.99, '2009-11-13'),
(23, 'LEGO ® Gwiezdne Wojny™: Saga Skywalkerów', 'Przeżyj przygody ze wszystkich dziewięciu filmów z sagi w nowej, innej niż wszystkie grze wideo. Ponad 300 grywalnych postaci, 100 pojazdów i 23 planety do odkrycia sprawiają, że odległa galaktyka nigdy dotąd nie zapewniała tyle zabawy! *W zestawie grywalna postać klasycznego Obi-Wana Kenobiego.', 14, 17, 229.00, '2022-04-05'),
(24, 'Hades', 'Defy the god of the dead as you hack and slash out of the Underworld in this rogue-like dungeon crawler from the creators of Bastion, Transistor, and Pyre.', 15, 18, 114.99, '2020-09-17'),
(25, 'Hades 2', 'Przedrzyj się przez Podziemia (i nie tylko), korzystając z mrocznej magii, i staw czoła Tytanowi Czasu w czarującej kontynuacji obsypanego nagrodami dungeon crawlera typu roguelike.', 15, 18, 117.00, '2025-09-25'),
(26, 'Hollow Knight', 'Forge your own path in Hollow Knight! An epic action adventure through a vast ruined kingdom of insects and heroes. Explore twisting caverns, battle tainted creatures and befriend bizarre bugs, all in a classic, hand-drawn 2D style.', 16, 19, 67.99, '2017-07-24'),
(27, 'Hollow Knight: Silksong', 'Discover a vast, haunted kingdom in Hollow Knight: Silksong! Explore, fight and survive as you ascend to the peak of a land ruled by silk and song.', 16, 19, 74.99, '2025-12-04'),
(28, 'Detroit: Become Human', 'Detroit: Become Human składa los ludzkości i androidów w Twoje ręce, przenosząc Cię do bliskiej przyszłości, kiedy to maszyny staną się bardziej inteligentne od ludzi. Każdy Twój wybór wpłynie na wynik gry, oferującej przy okazji jedną z najbardziej misternie złożonych historii.', 17, 20, 36.99, '2020-06-18'),
(29, 'Ultimate Chicken Horse', 'Ultimate Chicken Horse to platformówka, w której tworzysz kolejne poziomy trudności, knujesz i zastawiasz pułapki na współgraczy. Tylko musisz uważać, żeby nie wpaść we własne sidła.', 18, 21, 17.49, '2016-03-04'),
(30, 'Battlefield 6', 'Oto prawdziwe oblicze wojny totalnej. W wojnie czołgów, odrzutowców i nieprzebranych arsenałów twoja drużyna jest najgroźniejszą bronią.', 6, 6, 299.00, '2025-10-10'),
(31, 'Dead by Daylight', 'Trapped forever in a realm of eldritch evil where even death is not an escape, four determined Survivors face a bloodthirsty Killer in a vicious game of nerve and wits. Pick a side and step into a world of tension and terror with horror gaming\'s best asymmetrical multiplayer.', 19, 22, 71.99, '2016-06-14');

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
(99, 3, 1),
(100, 3, 2),
(98, 3, 3),
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
(48, 20, 3),
(61, 21, 1),
(62, 21, 2),
(60, 21, 3),
(59, 21, 4),
(65, 22, 1),
(66, 22, 2),
(64, 22, 3),
(63, 22, 4),
(69, 23, 1),
(70, 23, 2),
(68, 23, 3),
(67, 23, 4),
(73, 24, 1),
(74, 24, 2),
(72, 24, 3),
(71, 24, 4),
(76, 25, 1),
(75, 25, 4),
(79, 26, 1),
(80, 26, 2),
(78, 26, 3),
(77, 26, 4),
(83, 27, 1),
(84, 27, 2),
(82, 27, 3),
(81, 27, 4),
(86, 28, 1),
(85, 28, 3),
(89, 29, 1),
(90, 29, 2),
(88, 29, 3),
(87, 29, 4),
(92, 30, 1),
(93, 30, 2),
(91, 30, 3),
(96, 31, 1),
(97, 31, 2),
(95, 31, 3),
(94, 31, 4);

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
(121, 3, 1),
(122, 3, 2),
(123, 3, 3),
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
(75, 20, 6),
(88, 21, 6),
(87, 21, 7),
(89, 21, 8),
(90, 21, 10),
(92, 22, 6),
(91, 22, 7),
(93, 22, 10),
(95, 23, 6),
(94, 23, 7),
(96, 23, 10),
(97, 24, 1),
(98, 24, 2),
(100, 24, 3),
(99, 24, 6),
(101, 25, 1),
(104, 25, 3),
(103, 25, 6),
(102, 25, 7),
(105, 26, 1),
(107, 26, 3),
(106, 26, 6),
(108, 27, 1),
(109, 27, 2),
(111, 27, 3),
(110, 27, 6),
(113, 28, 3),
(112, 28, 7),
(115, 29, 6),
(114, 29, 7),
(116, 29, 8),
(117, 29, 10),
(118, 30, 8),
(119, 30, 9),
(120, 31, 8);

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
-- Struktura tabeli dla tabeli `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `discount_percent` tinyint(3) UNSIGNED NOT NULL CHECK (`discount_percent` between 1 and 100),
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `name`, `discount_percent`, `start_date`, `end_date`) VALUES
(3, 'Wyprzedaż zimowa', 30, '2025-11-28 16:21:00', '2025-12-07 16:21:00'),
(4, 'Wyprzedaz bo tak', 40, '2025-11-21 20:13:00', '2025-12-07 20:13:00');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `promotion_games`
--

CREATE TABLE `promotion_games` (
  `id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotion_games`
--

INSERT INTO `promotion_games` (`id`, `promotion_id`, `game_id`) VALUES
(31, 3, 30),
(32, 3, 10),
(33, 3, 4),
(34, 3, 3),
(35, 3, 2),
(36, 3, 20),
(37, 3, 28),
(38, 3, 31),
(40, 3, 9),
(41, 3, 19),
(42, 3, 13),
(43, 3, 24),
(44, 3, 26),
(45, 3, 21),
(47, 3, 15),
(48, 4, 2),
(49, 4, 4),
(50, 4, 3),
(51, 4, 28),
(52, 4, 31),
(53, 4, 5),
(54, 4, 24),
(55, 4, 13),
(56, 4, 17),
(57, 4, 18),
(58, 4, 25);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_color` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publishers`
--

INSERT INTO `publishers` (`id`, `name`, `logo_color`) VALUES
(1, 'CD Projekt Red', '#170f0f'),
(2, 'FromSoftware Inc', '#0c0c0c'),
(3, 'Bandai Namco Entertainment', '#271416'),
(4, 'Activision', '#1e1b1a'),
(5, 'PlayStation Studios', '#444444'),
(6, 'Electronic Arts', '#7c2945'),
(7, 'Rockstar Games', '#e6a019'),
(8, 'VOID Interactive', '#141E2A'),
(9, 'Starbreeze Studio', '#956ac4'),
(10, 'Team17 Digital', '#624674'),
(11, 'Battlestate Games', '#282828'),
(12, 'Kepler Interactive', '#9b99e7'),
(13, 'Fictions', '#101010'),
(14, 'Disney Interactive', '#161eb4'),
(15, 'Supergiant Games', '#270703'),
(16, 'Team Cherry', '#da3c53'),
(17, 'Quantic Dream', '#243b46'),
(18, 'Clever Endeavour Studio', '#54718d'),
(19, 'Behaviour Interactive', '#4c4f55');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `votes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `game_id`, `rating`, `comment`, `created_at`, `votes`) VALUES
(2, 1, 3, 5, 'chuj i \r\nnie chuj', '2025-10-14 06:04:54', 3),
(4, 2, 3, 4, 'ale dluga nazwa', '2025-10-14 16:17:53', 2),
(5, 1, 3, 5, '1', '2025-10-20 17:18:17', 0),
(6, 1, 3, 4, '2', '2025-10-20 17:18:21', 0),
(7, 1, 3, 4, '3', '2025-10-20 17:18:24', 0),
(8, 1, 3, 3, 'Jifheiorghueguh ioheruigh erhguiehrgi heirug eiurgergeug ueruf ehrerverviwvef iubwiuf wuefiu gweuif iuwefui wfu wiuefguwgefuigweufgiwegf uwfui heirufheuirhfiwuehrgiuwugiiu giuwer giuweergiu ug u ergueirug ue ruerg heior gehg eh guerhg iuerrgiu eirug huegherguiriu giure giuwrug iu hgiuerguiriugeruhguere rgioerg irg Jifheiorghueguh ioheruigh erhguiehrgi heirug eiurgergeug ueruf ehrerverviwvef iubwiuf wuefiu gweuif iuwefui wfu wiuefguwgefuigweufgiwegf uwfui heirufheuirhfiwuehrgiuwugiiu giuwer giuweergiu ug u ergueirug ue ruerg heior gehg eh guerhg iuerrgiu eirug huegherguiriu giure giuwrug iu hgiuerguiriugeruhguere rgioerg irg\r\nJifheiorghueguh ioheruigh erhguiehrgi heirug eiurgergeug ueruf ehrerverviwvef iubwiuf wuefiu gweuif iuwefui wfu wiuefguwgefuigweufgiwegf uwfui heirufheuirhfiwuehrgiuwugiiu giuwer giuweergiu ug u ergueirug ue ruerg heior gehg eh guerhg iuerrgiu eirug huegherguiriu giure giuwrug iu hgiuerguiriugeruhguere rgioerg irg', '2025-10-20 17:18:27', 0),
(9, 1, 3, 5, '5', '2025-10-20 17:18:31', -1),
(10, 1, 3, 3, '6', '2025-10-20 17:18:34', 0),
(11, 1, 3, 1, '7', '2025-10-20 17:18:38', -1),
(12, 1, 3, 5, '8', '2025-10-20 17:18:41', -1),
(13, 1, 3, 3, '8', '2025-10-20 17:18:48', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `review_votes`
--

CREATE TABLE `review_votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `vote_type` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_votes`
--

INSERT INTO `review_votes` (`id`, `user_id`, `review_id`, `vote_type`) VALUES
(1, 2, 4, 1),
(2, 2, 2, 1),
(3, 1, 4, 1),
(4, 1, 2, 1),
(5, 1, 9, -1),
(6, 1, 12, -1),
(7, 1, 11, -1);

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
(5, 1, '77f59d74854b07d6776bcd73de787444', '2025-09-23 07:54:38', '2025-09-23 10:54:38'),
(9, 1, 'd070a7d8c21def8dae7975c3d45d55fd', '2025-10-07 06:27:57', '2025-10-07 09:27:57'),
(10, 1, '412ace821d562d2319934ce767965754', '2025-10-14 05:37:24', '2025-10-14 08:37:24'),
(11, 1, '4fe343578b0b63ad6082c3658257fb72', '2025-10-14 06:37:32', '2025-10-14 09:37:32'),
(12, 1, '91d33eddaf51e54dfbc1c96e4d5a1186', '2025-10-14 08:25:46', '2025-10-14 11:25:46'),
(15, 1, '1ebdf5fec3873395f99d816c84b5be9c', '2025-10-14 17:27:57', '2025-10-14 20:27:57'),
(16, 1, 'f5b8449aff38fa8912d4c25abdc8dd08', '2025-10-14 17:53:06', '2025-10-14 20:53:06'),
(17, 1, '998e08339a06209fac4ac78c02e34c6f', '2025-10-20 15:52:30', '2025-10-20 18:52:30'),
(18, 1, 'ece71700e0c574490751d0467c9987ac', '2025-10-20 16:57:39', '2025-10-20 19:57:39'),
(20, 1, 'f409a774b43a1e1b0c4d4e1706bc3082', '2025-10-27 16:10:10', '2025-10-27 18:10:10'),
(21, 1, 'c743bc272b0fcbc20d20709a0c7625ac', '2025-10-27 17:25:39', '2025-10-27 19:25:39'),
(22, 1, 'ce19cb60c9b429f53fb93c667cf96e5e', '2025-11-10 14:33:58', '2025-11-10 16:33:58'),
(24, 1, '52ec4a1b3cb4251a924e224089741788', '2025-11-10 16:52:19', '2025-11-10 18:52:19'),
(25, 1, '765a928569e18eee988335e0db662ae7', '2025-11-10 18:25:34', '2025-11-10 20:25:34'),
(26, 1, 'd744dfce5e5fbd5e117e0b642a0d6efd', '2025-11-11 16:42:15', '2025-11-11 18:42:15'),
(28, 1, '5af34de19228a234a6c1ff75eba6d3d0', '2025-11-22 11:34:19', '2025-11-22 13:34:19'),
(29, 1, '47fcfc8f5223f46ced8fe485f2d96b6f', '2025-11-22 14:36:41', '2025-11-22 16:36:41'),
(31, 1, '7b5109991f3c4bada23979cdd89b4acb', '2025-11-23 19:06:39', '2025-11-23 21:06:39'),
(32, 1, '2b9533af2b62054ca3be8536917c0f40', '2025-11-24 16:49:19', '2025-11-24 18:49:19'),
(33, 1, '687afae61cf7bcc86b8d8ceaed49bd3f', '2025-11-29 15:12:11', '2025-11-29 17:12:11'),
(37, 1, '38fd88bbb84f8b896c51ff1e696d9644', '2025-11-29 17:17:10', '2025-11-29 19:17:10'),
(38, 1, '5887a22e40ce408c3b60aad74879db68', '2025-11-29 18:33:40', '2025-11-29 20:33:40'),
(39, 1, 'f2e638b62c9c30dc92f8606bb0900e0b', '2025-11-29 19:35:49', '2025-11-29 21:35:49'),
(40, 1, '7cf32b0b610eea0d66093902a9494184', '2025-11-30 19:12:42', '2025-11-30 21:12:42'),
(41, 1, '719e9f9b7b333ba1315b0c585d0531cf', '2025-12-01 13:43:18', '2025-12-01 15:43:18');

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
-- Struktura tabeli dla tabeli `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `transaction_date`, `total_amount`) VALUES
(1, 1, '2025-11-22 11:45:15', 259.98),
(2, 1, '2025-11-22 14:36:53', 309.98),
(3, 1, '2025-11-22 14:39:48', 199.99),
(4, 1, '2025-11-22 14:45:40', 169.00),
(5, 1, '2025-11-23 19:08:31', 250.99);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `discount_percent_applied` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `game_id`, `purchase_price`, `discount_percent_applied`) VALUES
(1, 1, 3, 149.99, 0),
(2, 1, 18, 109.99, 0),
(3, 2, 2, 159.99, 20),
(4, 2, 4, 149.99, 0),
(5, 3, 5, 199.99, 0),
(6, 4, 17, 169.00, 0),
(7, 5, 20, 179.00, 0),
(8, 5, 31, 71.99, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` tinyint(1) NOT NULL DEFAULT 0,
  `avatar_filename` varchar(255) DEFAULT 'default_avatar.png',
  `profile_color` varchar(7) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password`, `role`, `avatar_filename`, `profile_color`, `balance`) VALUES
(1, 'jann@example.com', 'janntest', '543360c1ecea52aa7620ccbb32357b72634348350be51c8577da1cf3f7175342', 1, 'user_1_1764437154.png', '#6f5844', 2070.06),
(2, 'aledlugiuzytkownikkkk@example.com', 'aledlugiuzytkownikkkkkkk', '543360c1ecea52aa7620ccbb32357b72634348350be51c8577da1cf3f7175342', 0, 'default_avatar.png', NULL, 0.00);

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
-- Dumping data for table `user_games`
--

INSERT INTO `user_games` (`id`, `user_id`, `game_id`) VALUES
(7, 1, 2),
(1, 1, 3),
(8, 1, 4),
(9, 1, 5),
(2, 1, 10),
(3, 1, 11),
(10, 1, 17),
(6, 1, 18),
(11, 1, 20),
(4, 1, 21),
(12, 1, 31);

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
-- Indeksy dla tabeli `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `promotion_games`
--
ALTER TABLE `promotion_games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `game_id` (`game_id`);

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
-- Indeksy dla tabeli `review_votes`
--
ALTER TABLE `review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_review_vote` (`user_id`,`review_id`),
  ADD KEY `review_id` (`review_id`);

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
-- Indeksy dla tabeli `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indeksy dla tabeli `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_game_id` (`game_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `developers`
--
ALTER TABLE `developers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `game_platforms`
--
ALTER TABLE `game_platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `game_tags`
--
ALTER TABLE `game_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `platforms`
--
ALTER TABLE `platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `promotion_games`
--
ALTER TABLE `promotion_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `review_votes`
--
ALTER TABLE `review_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_games`
--
ALTER TABLE `user_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Constraints for table `promotion_games`
--
ALTER TABLE `promotion_games`
  ADD CONSTRAINT `promotion_games_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_games_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD CONSTRAINT `review_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_votes_ibfk_2` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

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
