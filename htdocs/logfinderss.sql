-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 10 Eyl 2025, 23:46:50
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `logfinderss`
--

-- --------------------------------------------------------

--
-- Görünüm yapısı durumu `daily_search_counts`
-- (Asıl görünüm için aşağıya bakın)
--
CREATE TABLE `daily_search_counts` (
`user_id` int(11)
,`username` varchar(50)
,`search_date` date
,`search_count` bigint(21)
,`last_search` timestamp
);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `search_logs`
--

CREATE TABLE `search_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `search_term` varchar(500) NOT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `search_date` date NOT NULL,
  `search_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `search_logs`
--

INSERT INTO `search_logs` (`id`, `user_id`, `username`, `search_term`, `results_count`, `ip_address`, `user_agent`, `search_date`, `search_time`, `created_at`) VALUES
(1, 1, 'demo', 'fuhrer', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11', '00:28:30', '2025-09-10 21:28:30');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `created_at`, `updated_at`) VALUES
(1, 'daily_search_limit', '5', 'Kullanıcıların günlük arama limiti', '2025-09-10 21:01:02', '2025-09-10 21:28:53'),
(2, 'enable_search_logging', '1', 'Arama loglaması aktif/pasif (1=aktif, 0=pasif)', '2025-09-10 21:01:02', '2025-09-10 21:01:02'),
(3, 'max_results_per_search', '50', 'Her aramada maksimum sonuç sayısı', '2025-09-10 21:01:02', '2025-09-10 21:01:02');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `is_admin`, `created_at`, `updated_at`) VALUES
(1, 'demo', '$2y$10$BMWdblQxU8T7rlo764SmcOo/4xo6yRf7ENaQRtU1lRvC9v/ysNLVm', 'demo@logfinder.com', 1, '2025-09-10 20:32:35', '2025-09-10 20:32:35'),
(2, 'testuser', '$2y$10$BMWdblQxU8T7rlo764SmcOo/4xo6yRf7ENaQRtU1lRvC9v/ysNLVm', 'test@logfinder.com', 1, '2025-09-10 20:32:35', '2025-09-10 20:35:57'),
(4, 'test', '$2y$10$c1iFDpSQtdYU9NqM5RkwSuBY.5ZLd55l0ddiPQkkkvrhR2yqKq05.', '', 1, '2025-09-10 21:29:35', '2025-09-10 21:29:35');

-- --------------------------------------------------------

--
-- Görünüm yapısı `daily_search_counts`
--
DROP TABLE IF EXISTS `daily_search_counts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `daily_search_counts`  AS SELECT `search_logs`.`user_id` AS `user_id`, `search_logs`.`username` AS `username`, `search_logs`.`search_date` AS `search_date`, count(0) AS `search_count`, max(`search_logs`.`created_at`) AS `last_search` FROM `search_logs` GROUP BY `search_logs`.`user_id`, `search_logs`.`username`, `search_logs`.`search_date` ;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `search_logs`
--
ALTER TABLE `search_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `search_date` (`search_date`),
  ADD KEY `username` (`username`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `is_admin` (`is_admin`),
  ADD KEY `created_at` (`created_at`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `search_logs`
--
ALTER TABLE `search_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `search_logs`
--
ALTER TABLE `search_logs`
  ADD CONSTRAINT `search_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
