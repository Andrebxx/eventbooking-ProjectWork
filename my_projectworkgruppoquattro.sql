-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Set 03, 2025 alle 15:16
-- Versione del server: 8.0.36
-- Versione PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_projectworkgruppoquattro`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `carte_credito`
--

CREATE TABLE `carte_credito` (
  `id` int NOT NULL,
  `utente_id` int NOT NULL,
  `numero_mascherato` varchar(25) NOT NULL,
  `numero_hash` varchar(255) NOT NULL,
  `data_creazione` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `scadenza` varchar(5) NOT NULL,
  `CVV` int NOT NULL,
  `Titolare` varchar(100) NOT NULL,
  `Tipo` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `Eventi`
--

CREATE TABLE `Eventi` (
  `id` int NOT NULL,
  `titoloE` varchar(100) NOT NULL,
  `descrizione` varchar(500) NOT NULL,
  `data` date NOT NULL,
  `ora` time NOT NULL,
  `Posti` int NOT NULL,
  `Immagine` varchar(1000) NOT NULL,
  `prezzo` decimal(10,2) DEFAULT '0.00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `Eventi`
--

INSERT INTO `Eventi` (`id`, `titoloE`, `descrizione`, `data`, `ora`, `Posti`, `Immagine`, `prezzo`) VALUES
(7, 'Cinema all\'aperto', 'Proiezione di film cult sotto le stelle.', '2025-07-28', '22:00:00', 56, 'Evento7.jpg', '10.00'),
(2, 'Jazz Night', 'Serata jazz con artisti internazionali.', '2025-07-23', '20:30:00', 0, 'Evento2.jpg', '15.00'),
(3, 'Teatro Classico', 'Spettacolo teatrale classico.', '2025-08-10', '19:00:00', 92, 'Evento3.jpg', '50.00'),
(4, 'Festival Pop', 'Festival di musica pop all\'aperto.', '2025-08-15', '18:00:00', 200, 'Evento4.jpg', '9.00'),
(5, 'Stand Up Comedy', 'Serata di comicità con comici famosi.', '2025-08-20', '21:30:00', 67, 'Evento5.jpg', '9.00'),
(6, 'Opera sotto le stelle', 'Opera all\'aperto in una location suggestiva.', '2025-08-25', '20:00:00', 150, 'Evento6.jpg', '5.00'),
(8, 'Festival Elettronica', 'DJ set e musica elettronica.', '2025-08-30', '23:00:00', 175, 'Evento8.jpg', '70.00'),
(1, 'Concerto Rock', 'Un concerto rock con band locali.', '2025-07-01', '21:00:00', 115, 'Evento1.jpg', '700.00'),
(17, 'Concerto Rock: The Lightning Bolts', 'Una serata esplosiva con la band rock più energica del momento! Preparatevi a un viaggio musicale attraverso i grandi classici del rock e i nuovi successi che stanno conquistando le classifiche mondiali.', '2025-07-25', '21:00:00', 250, 'concerto_rock_lightning.jpg', '35.00'),
(18, 'Serata Jazz al Tramonto', 'Un\'atmosfera intima e sofisticata per gli amanti del jazz. Musica dal vivo con i migliori musicisti della scena locale, cocktail esclusivi e una vista mozzafiato sul tramonto dalla terrazza panoramica.', '2025-07-22', '19:30:00', 71, 'serata_jazz_tramonto.jpg', '28.50'),
(19, 'Festival Elettronico: Neon Nights', 'La notte più elettrizzante dell\'anno! DJ internazionali, luci stroboscopiche, effetti speciali e la migliore musica elettronica. Un\'esperienza sensoriale unica che ti farà ballare fino all\'alba.', '2025-07-29', '22:00:00', 495, 'festival_elettronico_neon.jpg', '45.00'),
(20, 'Concerto Classico: Orchestra Sinfonica', 'Una serata dedicata alla grande musica classica con l\'esibizione dell\'Orchestra Sinfonica Regionale. In programma: Beethoven, Mozart e Vivaldi in un\'interpretazione magistrale che emozionerà tutti i presenti.', '2025-08-01', '20:30:00', 296, 'concerto_classico_orchestra.jpg', '42.00'),
(21, 'Serata Karaoke & Fun', 'Divertimento assicurato per tutta la famiglia! Karaoke con le canzoni più amate, giochi interattivi, premi per i migliori performers e tanto divertimento in compagnia. Adatto a tutte le età.', '2025-08-03', '20:00:00', 141, 'serata_karaoke_fun.jpg', '15.00'),
(22, 'Concerto Pop: Star Voices', 'Le voci più belle della musica pop italiana e internazionale si esibiscono in un concerto imperdibile. Hits del momento, grandi successi del passato e sorprese speciali per una serata indimenticabile.', '2025-08-05', '21:30:00', 397, 'concerto_pop_star.jpg', '38.00'),
(23, 'Notte Latina: Salsa & Bachata', 'Lasciatevi trasportare dai ritmi caldi dell\'America Latina! Musica dal vivo, lezioni di ballo gratuite, cocktail tropicali e un\'atmosfera calorosa che vi farà sentire come se foste nei Caraibi.', '2025-08-07', '21:00:00', 198, 'notte_latina_salsa.jpg', '25.00'),
(24, 'Concerto Indie Rock: Underground Sounds', 'Scoprite i nuovi talenti della scena indie rock! Band emergenti, suoni innovativi e l\'energia pura della musica indipendente in un locale intimo che valorizza la qualità artistica.', '2025-08-09', '20:30:00', 117, 'concerto_indie_underground.jpg', '22.00'),
(25, 'Serata Tribute: Queen Forever', 'Il più grande omaggio ai Queen mai realizzato! Una tribute band straordinaria ripropone dal vivo tutti i capolavori di Freddie Mercury e della leggendaria band britannica. We Will Rock You!', '2025-08-12', '21:00:00', 350, 'tribute_queen_forever.jpg', '40.00'),
(26, 'Festival Acustico: Unplugged Sessions', 'La magia della musica nella sua forma più pura. Artisti di diversi generi musicali si esibiscono in versione acustica, creando un\'atmosfera magica e coinvolgente per veri intenditori.', '2025-08-16', '19:00:00', 180, 'festival_acustico_unplugged.jpg', '30.00');

-- --------------------------------------------------------

--
-- Struttura della tabella `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `attempt_time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `prenotazioni`
--

CREATE TABLE `prenotazioni` (
  `id` int NOT NULL,
  `utente_id` int DEFAULT NULL,
  `evento_id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `posti` int NOT NULL,
  `nominativi` text,
  `pagamento` varchar(50) DEFAULT NULL,
  `data_prenotazione` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `pagamento_info` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(13) DEFAULT NULL,
  `foto_profilo` varchar(500) NOT NULL DEFAULT 'default.jpg',
  `password` varchar(255) NOT NULL,
  `creato_il` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0',
  `email_verified` tinyint(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `carte_credito`
--
ALTER TABLE `carte_credito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utente_id` (`utente_id`);

--
-- Indici per le tabelle `Eventi`
--
ALTER TABLE `Eventi`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evento_id` (`evento_id`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `carte_credito`
--
ALTER TABLE `carte_credito`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `Eventi`
--
ALTER TABLE `Eventi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT per la tabella `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
