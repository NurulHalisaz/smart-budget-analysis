-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versi server:                 8.0.30 - MySQL Community Server - GPL
-- OS Server:                    Win64
-- HeidiSQL Versi:               12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Membuang struktur basisdata untuk smart_budget_analysis
CREATE DATABASE IF NOT EXISTS `smart_budget_analysis` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `smart_budget_analysis`;

-- membuang struktur untuk table smart_budget_analysis.budgets
CREATE TABLE IF NOT EXISTS `budgets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `month` varchar(7) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_month_budget` (`user_id`,`month`),
  CONSTRAINT `fk_user_budget` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Membuang data untuk tabel smart_budget_analysis.budgets: ~2 rows (lebih kurang)
INSERT INTO `budgets` (`id`, `user_id`, `month`, `amount`, `created_at`) VALUES
	(1, 1, '2026-07', 2500000.00, '2026-07-01 12:35:14'),
	(2, 1, '2026-06', 2000000.00, '2026-07-01 23:28:22');

-- membuang struktur untuk table smart_budget_analysis.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('pemasukan','pengeluaran') NOT NULL DEFAULT 'pengeluaran',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_category` (`user_id`,`name`),
  CONSTRAINT `fk_user_category` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Membuang data untuk tabel smart_budget_analysis.categories: ~4 rows (lebih kurang)
INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `created_at`) VALUES
	(1, 1, 'makanan', 'pengeluaran', '2026-07-01 12:37:09'),
	(2, 1, 'gaji', 'pemasukan', '2026-07-01 12:37:21'),
	(3, 1, 'hiburan dan hobi', 'pengeluaran', '2026-07-01 12:37:38'),
	(4, 1, 'listrik', 'pengeluaran', '2026-07-01 23:29:35');

-- membuang struktur untuk table smart_budget_analysis.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_transaction` (`user_id`),
  KEY `fk_category_transaction` (`category_id`),
  CONSTRAINT `fk_category_transaction` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_transaction` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Membuang data untuk tabel smart_budget_analysis.transactions: ~9 rows (lebih kurang)
INSERT INTO `transactions` (`id`, `user_id`, `category_id`, `title`, `amount`, `transaction_date`, `notes`, `created_at`) VALUES
	(1, 1, 3, 'beli game', 100000.00, '2026-07-01', '', '2026-07-01 12:39:04'),
	(2, 1, 2, 'gaji', 3000000.00, '2026-07-01', '', '2026-07-01 12:57:13'),
	(3, 1, 1, 'beli makanan', 20000.00, '2026-07-01', '', '2026-07-01 12:58:10'),
	(4, 1, 2, 'gaji', 3500000.00, '2026-06-01', '', '2026-07-01 23:29:01'),
	(5, 1, 4, 'listrik', 300000.00, '2026-06-02', '', '2026-07-01 23:30:00'),
	(6, 1, 3, 'skincare', 200000.00, '2026-06-03', '', '2026-07-01 23:30:24'),
	(7, 1, 1, 'makanan', 30000.00, '2026-06-04', '', '2026-07-01 23:31:07'),
	(8, 1, 3, 'liburan', 1000000.00, '2026-06-10', '', '2026-07-01 23:35:23'),
	(9, 1, 3, 'konser', 300000.00, '2026-06-20', '', '2026-07-01 23:38:10');

-- membuang struktur untuk table smart_budget_analysis.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Membuang data untuk tabel smart_budget_analysis.users: ~1 rows (lebih kurang)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `remember_token`, `created_at`) VALUES
	(1, 'nurul halisa z', 'nurulhalisa.z04@gmail.com', '$2y$10$sSvN4hetAXMVkPLodI6INOy0nGxWL2phr.Q83DAp8ir8205XKYDJ6', NULL, '2026-07-01 12:32:16');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
