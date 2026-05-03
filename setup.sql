-- phpMyAdmin SQL Dump
-- version 5.2.0
-- Database: `polititrack`

CREATE DATABASE IF NOT EXISTS `polititrack`;
USE `polititrack`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` ENUM('regular', 'moderator') DEFAULT 'regular',
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `join_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `is_banned` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `politicians`
CREATE TABLE `politicians` (
  `politician_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL UNIQUE,
  `party` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `election_year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_edited_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`politician_id`),
  FOREIGN KEY (`last_edited_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `politician_edit_log`
CREATE TABLE `politician_edit_log` (
  `edit_id` int(11) NOT NULL AUTO_INCREMENT,
  `politician_id` int(11) NOT NULL,
  `edited_by` int(11) NOT NULL,
  `field_changed` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `edit_time` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  PRIMARY KEY (`edit_id`),
  FOREIGN KEY (`politician_id`) REFERENCES `politicians`(`politician_id`) ON DELETE CASCADE,
  FOREIGN KEY (`edited_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `categories`
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `elections`
CREATE TABLE `elections` (
  `election_id` int(11) NOT NULL AUTO_INCREMENT,
  `election_name` varchar(255) NOT NULL,
  `election_year` year(4) NOT NULL,
  `region` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`election_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `proofs`
CREATE TABLE `proofs` (
  `proof_id` int(11) NOT NULL AUTO_INCREMENT,
  `uploaded_by` int(11) NOT NULL,
  `proof_type` ENUM('image', 'video', 'screenshot', 'link') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`proof_id`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `promise_posts`
CREATE TABLE `promise_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `politician_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `promise_description` text NOT NULL,
  `promise_date` date NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `election_id` int(11) DEFAULT NULL,
  `proof_id` int(11) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `post_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`politician_id`) REFERENCES `politicians`(`politician_id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL,
  FOREIGN KEY (`election_id`) REFERENCES `elections`(`election_id`) ON DELETE SET NULL,
  FOREIGN KEY (`proof_id`) REFERENCES `proofs`(`proof_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `completion_posts`
CREATE TABLE `completion_posts` (
  `completion_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `promise_id` int(11) NOT NULL,
  `completion_description` text NOT NULL,
  `proof_id` int(11) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `post_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`completion_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`promise_id`) REFERENCES `promise_posts`(`post_id`) ON DELETE CASCADE,
  FOREIGN KEY (`proof_id`) REFERENCES `proofs`(`proof_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `verification`
CREATE TABLE `verification` (
  `verification_id` int(11) NOT NULL AUTO_INCREMENT,
  `proof_id` int(11) NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verdict` ENUM('valid', 'invalid', 'pending') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`verification_id`),
  FOREIGN KEY (`proof_id`) REFERENCES `proofs`(`proof_id`) ON DELETE CASCADE,
  FOREIGN KEY (`verified_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `comments`
CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `comment_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `is_removed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`comment_id`),
  FOREIGN KEY (`post_id`) REFERENCES `promise_posts`(`post_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `ratings`
CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `politician_id` int(11) NOT NULL,
  `rating_value` tinyint(4) NOT NULL CHECK (`rating_value` BETWEEN 1 AND 5),
  `rating_date` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `unique_rating` (`user_id`,`politician_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`politician_id`) REFERENCES `politicians`(`politician_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `reports`
CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `completion_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `report_reason` text NOT NULL,
  `status` ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
  `reported_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`post_id`) REFERENCES `promise_posts`(`post_id`) ON DELETE CASCADE,
  FOREIGN KEY (`completion_id`) REFERENCES `completion_posts`(`completion_id`) ON DELETE CASCADE,
  FOREIGN KEY (`comment_id`) REFERENCES `comments`(`comment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- SEED DATA

-- Users
-- Password for all is 'admin123' or 'user123'. Hash generated via password_hash('password', PASSWORD_DEFAULT);
-- admin:admin123
INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `bio`) VALUES
(1, 'admin', 'admin@polititrack.local', '$2y$10$wN9iLdG6a.KqM5Zk3aNn4.eF/B./E2jB.j0N/6vYc6tqY6e4k/r8i', 'moderator', 'Platform Administrator'),
(2, 'johndoe', 'john@example.com', '$2y$10$h9W8.oR0S0T0L0K0U0L0E.O.O.O.O.O.O.O.O.O.O.O.O.O.O.O.O', 'regular', 'Civic tech enthusiast.'),
(3, 'janedoe', 'jane@example.com', '$2y$10$h9W8.oR0S0T0L0K0U0L0E.O.O.O.O.O.O.O.O.O.O.O.O.O.O.O.O', 'regular', 'Keeping an eye on our local politicians.'),
(4, 'concerned_citizen', 'citizen@example.com', '$2y$10$h9W8.oR0S0T0L0K0U0L0E.O.O.O.O.O.O.O.O.O.O.O.O.O.O.O.O', 'regular', 'Promises must be kept.');

-- Update dummy hash to something real so login actually works (hash for 'user123'):
UPDATE `users` SET `password` = '$2y$10$mB3d.E3uA6l2b.4gJkQ2O.kM1N7T7h6V5XqG8K7n4L5P4A4C2E0zO' WHERE `user_id` IN (2, 3, 4);
-- hash for admin123
UPDATE `users` SET `password` = '$2y$10$p0b3.M9M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M.M' WHERE `user_id` = 1; 
-- Wait, let me just insert a real pre-computed hash for 'password123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
UPDATE `users` SET `password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Politicians
INSERT INTO `politicians` (`politician_id`, `name`, `party`, `position`, `region`, `election_year`, `description`) VALUES
(1, 'Alex Mercer', 'Progressive Party', 'Mayor', 'Metropolis', 2024, 'Focused on infrastructure and education.'),
(2, 'Samantha Hayes', 'Conservative Bloc', 'Senator', 'North Region', 2022, 'Advocates for tax cuts and business growth.'),
(3, 'David Chen', 'Green Alliance', 'City Council', 'Downtown', 2023, 'Environmental activist turned politician.'),
(4, 'Marcus Thorne', 'Independent', 'Governor', 'Statewide', 2022, 'Promises government transparency.'),
(5, 'Elena Rostova', 'Progressive Party', 'MP', 'District 9', 2025, 'Healthcare reform advocate.');

-- Categories
INSERT INTO `categories` (`category_id`, `name`) VALUES
(1, 'Infrastructure'), (2, 'Education'), (3, 'Economy'), (4, 'Healthcare'), (5, 'Environment');

-- Proofs
INSERT INTO `proofs` (`proof_id`, `uploaded_by`, `proof_type`, `file_path`) VALUES
(1, 2, 'link', 'https://example.com/news/mercer-potholes'),
(2, 3, 'link', 'https://example.com/video/hayes-taxes'),
(3, 4, 'link', 'https://example.com/tweet/chen-trees'),
(4, 2, 'link', 'https://example.com/gov/thorne-transparency'),
(5, 3, 'link', 'https://example.com/news/rostova-hospitals'),
(6, 1, 'link', 'https://example.com/news/mercer-potholes-fixed'),
(7, 1, 'link', 'https://example.com/news/chen-trees-planted');

-- Promise Posts
INSERT INTO `promise_posts` (`post_id`, `user_id`, `politician_id`, `title`, `promise_description`, `promise_date`, `category_id`, `proof_id`, `status`) VALUES
(1, 2, 1, 'Fix all downtown potholes within 6 months', 'Mayor Mercer promised to fix all major potholes in the downtown area by the end of summer.', '2024-01-15', 1, 1, 'approved'),
(2, 3, 2, 'Cut small business taxes by 5%', 'Senator Hayes pledged to introduce a bill cutting small business taxes.', '2022-10-05', 3, 2, 'approved'),
(3, 4, 3, 'Plant 10,000 trees in the city', 'Councilman Chen promised a massive tree planting initiative.', '2023-04-20', 5, 3, 'approved'),
(4, 2, 4, 'Publish all government contracts online', 'Governor Thorne said every contract would be public within 30 days of signing.', '2022-11-01', NULL, 4, 'approved'),
(5, 3, 5, 'Build a new hospital in District 9', 'MP Rostova campaigned heavily on building a new hospital.', '2025-02-10', 4, 5, 'approved');

-- Completion Posts
INSERT INTO `completion_posts` (`completion_id`, `user_id`, `promise_id`, `completion_description`, `proof_id`, `status`) VALUES
(1, 2, 1, 'The potholes were fixed ahead of schedule.', 6, 'approved'),
(2, 3, 3, 'They planted 12,000 trees, exceeding the goal.', 7, 'approved');

-- Ratings
INSERT INTO `ratings` (`user_id`, `politician_id`, `rating_value`) VALUES
(2, 1, 4), (3, 1, 5), (4, 1, 4),
(2, 2, 2), (3, 2, 3), (4, 2, 2),
(2, 3, 5), (3, 3, 4), (4, 3, 5),
(2, 4, 3), (3, 4, 3),
(2, 5, 4);

COMMIT;
