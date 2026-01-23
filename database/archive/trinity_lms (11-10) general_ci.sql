-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2025 at 04:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trinity_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `admin_password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_code` varchar(100) NOT NULL,
  `class_name` varchar(250) NOT NULL,
  `ww_perc` int(11) NOT NULL,
  `pt_perc` int(11) NOT NULL,
  `qa_perce` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_code`, `class_name`, `ww_perc`, `pt_perc`, `qa_perce`, `created_at`, `updated_at`) VALUES
(1, 'GEN-MATH11', 'General Mathemathics', 30, 50, 20, '2025-10-29 18:35:42', '2025-10-29 18:35:42'),
(2, 'GEN-MATH12', 'General Mathemathics', 30, 50, 20, '2025-10-29 18:36:52', '2025-10-29 18:36:52'),
(3, 'GEN-MATH13', 'General Mathemathics', 30, 50, 20, '2025-10-29 18:37:03', '2025-10-29 18:37:03'),
(4, 'PHIL-HISTORY11', 'History', 30, 50, 20, '2025-10-29 22:08:12', '2025-10-29 22:08:12'),
(5, 'PHIL-HISTORY12', 'History', 30, 50, 20, '2025-10-29 22:09:23', '2025-10-29 22:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `lectures`
--

CREATE TABLE `lectures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `content_type` enum('text','image','pdf','file') NOT NULL DEFAULT 'text',
  `file_path` varchar(500) DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lectures`
--

INSERT INTO `lectures` (`id`, `lesson_id`, `title`, `content`, `content_type`, `file_path`, `order_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Lecture 1', 'Hey there', 'text', NULL, 1, 1, '2025-11-09 07:16:50', '2025-11-09 07:16:50'),
(2, 1, 'Lecture File', NULL, 'file', 'lectures/pxexRsiallOchyskpxEkf1oXdAXQhfqHo0aIUB0a', 0, 0, '2025-11-09 07:18:51', '2025-11-10 01:13:35'),
(3, 1, 'Lecture Word', NULL, 'pdf', 'lectures/1762762287_LectureFile.pdf', 0, 1, '2025-11-09 07:20:13', '2025-11-10 00:11:26'),
(4, 1, 'asd', NULL, 'pdf', 'lectures/1762702234_LectureFile.docx', 0, 1, '2025-11-09 07:27:28', '2025-11-09 07:30:34');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `class_id`, `title`, `description`, `order_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Lesson 1', 'Description', 1, 1, '2025-11-09 05:52:48', '2025-11-10 01:50:35'),
(2, 1, 'Lesson 2', NULL, 2, 1, '2025-11-09 06:25:08', '2025-11-09 06:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Grade 11', '2025-10-19 09:29:32', '2025-10-19 09:29:32'),
(2, 'Grade 12', '2025-10-19 09:29:32', '2025-10-19 09:29:32');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(15, '2025_10_01_150739_create_students_table', 1),
(16, '2025_10_01_151304_create_users_table', 1),
(17, '2025_10_01_151841_create_teacher_table', 1),
(18, '2025_10_01_154354_create_sessions_table', 1),
(19, '2025_10_01_154615_create_strands_table', 1),
(20, '2025_10_01_154626_create_sections_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL COMMENT 'in minutes',
  `passing_score` decimal(5,2) DEFAULT 60.00,
  `max_attempts` int(11) DEFAULT 1,
  `show_results` tinyint(1) NOT NULL DEFAULT 1,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `lesson_id`, `title`, `description`, `time_limit`, `passing_score`, `max_attempts`, `show_results`, `shuffle_questions`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Chapter 1 Quiz', NULL, 60, 75.00, 1, 1, 0, 1, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(2, 1, 'qwe', NULL, 60, 75.00, 1, 1, 0, 1, '2025-11-10 06:51:21', '2025-11-10 06:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','essay') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`, `order_number`, `created_at`, `updated_at`) VALUES
(1, 1, 'Answer = 1', 'multiple_choice', 1.00, 1, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(2, 1, 'Answer = T', 'true_false', 1.00, 2, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(3, 1, '100 words', 'essay', 10.00, 3, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(4, 2, 'qwe', 'multiple_choice', 1.00, 1, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(5, 2, 'qwe', 'multiple_choice', 1.00, 2, '2025-11-10 06:51:21', '2025-11-10 06:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_question_options`
--

CREATE TABLE `quiz_question_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_question_options`
--

INSERT INTO `quiz_question_options` (`id`, `question_id`, `option_text`, `is_correct`, `order_number`, `created_at`, `updated_at`) VALUES
(1, 1, '1', 0, 1, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(2, 1, '2', 1, 2, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(3, 1, '3', 0, 3, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(4, 1, '4', 0, 4, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(5, 2, 'True', 1, 1, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(6, 2, 'False', 0, 2, '2025-11-10 01:49:22', '2025-11-10 01:49:22'),
(7, 4, 'qwe1', 1, 1, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(8, 4, 'qwe', 0, 2, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(9, 4, 'qwe', 0, 3, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(10, 4, 'qwe', 0, 4, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(11, 5, 'qwe1', 1, 1, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(12, 5, 'qwe', 0, 2, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(13, 5, 'qwe', 0, 3, '2025-11-10 06:51:21', '2025-11-10 06:51:21'),
(14, 5, 'qwe', 0, 4, '2025-11-10 06:51:21', '2025-11-10 06:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `strand_id` bigint(20) UNSIGNED NOT NULL,
  `level_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `code`, `name`, `strand_id`, `level_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ICT-11A', 'Sagittarius', 1, 1, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:28'),
(2, 'ICT-11B', 'Capricorn', 1, 1, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:33'),
(3, 'ABM-11A', 'Virgo', 2, 1, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:35'),
(4, 'ABM-12A', 'Aries', 2, 2, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:39'),
(5, 'HUMMS-12A', 'Gemini', 3, 2, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:42'),
(6, 'HUMMS-12B', 'Libra', 3, 2, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:44'),
(7, 'HUMMS-11A', 'Pisces', 3, 1, 1, '2025-10-19 09:36:06', '2025-10-30 05:45:46'),
(8, 'HUMMS-11B', 'Aquarius', 3, 1, 1, '2025-10-19 09:36:06', '2025-10-30 05:45:48'),
(9, 'GAS-11A', 'Ophiuchus', 4, 1, 1, '2025-10-19 09:36:32', '2025-10-30 05:45:50'),
(10, 'GAS-11B', 'Leo', 4, 1, 1, '2025-10-19 09:36:32', '2025-10-30 05:45:52'),
(11, 'HE-11A', 'Taurus', 5, 1, 1, '2025-10-19 09:37:56', '2025-10-30 05:45:54'),
(12, 'HE-11B', 'Cancer', 5, 1, 1, '2025-10-19 09:37:56', '2025-10-30 05:45:55'),
(13, 'HE-11C', 'Scorpio', 5, 1, 1, '2025-10-19 09:37:56', '2025-10-30 05:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `section_class_matrix`
--

CREATE TABLE `section_class_matrix` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section_class_matrix`
--

INSERT INTO `section_class_matrix` (`id`, `section_id`, `class_id`) VALUES
(1, 1, 2),
(2, 1, 3),
(3, 1, 4),
(4, 5, 3),
(5, 5, 1),
(6, 5, 5),
(7, 4, 3),
(8, 4, 4),
(9, 4, 2),
(10, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('6iVJ5SwnJE5C5iq4woqCcrp74YxDUJiATXXsUJzv', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoid0FOcWdZcDZWUGxZZzMxa3g3SW93NGZTNGVoWm1LanMzeVVVMHk5WSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC90ZWFjaGVyL2NsYXNzLzEvZ3JhZGVzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1NDoibG9naW5fc3R1ZGVudF81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtzOjk6IjIwMjUwMDAwNSI7czoyMjoiUEhQREVCVUdCQVJfU1RBQ0tfREFUQSI7YTowOnt9czo1NDoibG9naW5fdGVhY2hlcl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjY7fQ==', 1762789309);

-- --------------------------------------------------------

--
-- Table structure for table `strands`
--

CREATE TABLE `strands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `strands`
--

INSERT INTO `strands` (`id`, `code`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ICT', '', 1, '2025-10-19 09:10:46', '2025-10-19 09:34:42'),
(2, 'ABM', 'Accountancy, Business and Management', 1, '2025-10-19 09:10:46', '2025-10-19 09:34:45'),
(3, 'HUMSS', 'Humanities and Social Sciences', 1, '2025-10-19 09:10:46', '2025-10-19 09:34:47'),
(4, 'GAS', 'General Academic Strand', 1, '2025-10-19 09:10:46', '2025-10-19 09:34:48'),
(5, 'HE', '', 1, '2025-10-19 09:37:05', '2025-10-19 09:37:16');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(255) NOT NULL,
  `student_password` varchar(100) NOT NULL,
  `rememberToken` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_type` enum('regular','irregular') NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_number`, `student_password`, `rememberToken`, `email`, `first_name`, `middle_name`, `last_name`, `gender`, `profile_image`, `section_id`, `student_type`, `enrollment_date`, `created_at`, `updated_at`) VALUES
(21, '2024-0001', '$2y$12$pF9aAOTvujmIol6K6i/2xeqpt8ea7X8rXOAE1YWrJG5mjRymla8T.', '', 'john.reel@example.com', 'Johnnyere', 'Michael', 'Reelsr', 'Male', NULL, 1, 'irregular', '2024-08-15', '2025-10-21 00:56:21', '2025-10-21 23:47:10'),
(22, '2024-0002', '$2y$12$87rudi6DhgzVQovq4A6yQeZFebLmdgeZBZsB1Jn781R068u.kzG3G', '', 'jane.smith@example.com', 'Jane', 'Marie', 'Smith', 'Female', NULL, 1, 'irregular', '2024-08-15', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(23, '2024-0003', '$2y$12$S4pTqTk8N/z/CHVqjK0gNuQGT.q2GDidRpnDOJbRI/Gq2qI9TED4K', '', 'robert.johnson@example.com', 'Robert', 'James', 'Johnson', 'Male', NULL, 2, 'irregular', '2024-08-16', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(24, '2024-0004', '$2y$12$NMbSIEqGsXQ.D5QqAX92LeDjduzfjP0kd0J198x.R7AefIDSaPpMe', '', 'emily.williams@example.com', 'Emily', 'Rose', 'Williams', 'Female', NULL, 2, 'irregular', '2024-08-16', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(25, '2024-0005', '$2y$12$MwZjPLzV9wGA1OUHI/XXLOFV1nv2B9mBMSCsmcL5l2/lfIgafm3rS', '', 'michael.brown@example.com', 'Michael', 'David', 'Brown', 'Male', NULL, 3, 'regular', '2024-08-17', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(26, '202500001', '$2y$12$YZcRrDI0odM4BFNHplwX8u93AFhJcRiBtGNd8UHMpzSFOLkLzdzrS', '', 'student@gmail.com', 'qwe', 'DR', 'qwe', 'Male', NULL, 3, 'regular', NULL, '2025-11-07 04:10:02', '2025-11-07 04:10:02'),
(31, '202500002', '$2y$12$4b.5ExODaUTuKEShaztEdO5ziM6Ef3tvKEhWvhypMKKJ4dCWMyoiK', '', 'asd@gmail.com', 'asd', 'a', 'asd', 'Male', NULL, 5, 'regular', NULL, '2025-11-06 20:49:13', '2025-11-06 20:49:13'),
(32, '202500003', '$2y$12$c5afSaPZQ74OX06ZqQYRUeHkhw4ZiGvpsF95XmrJQPt1EnHg44H2C', '', 'ar1@gmail.com', 'ar', 'a', '1', 'Male', NULL, 4, 'regular', NULL, '2025-11-08 06:03:01', '2025-11-08 06:03:01'),
(33, '202500004', '$2y$12$oBU92qi4HBPD8wN1a9sDjeF/vXj0e3773lPWE6n8K/E55lCTKdIla', '', 'ar2@gmail.com', 'ar', 'a', '2', 'Male', NULL, 4, 'regular', NULL, '2025-11-08 06:03:01', '2025-11-08 06:03:01'),
(34, '202500005', '$2y$12$KOlMV/xBEMy9erdpdJF0w.b4.uvmjsBsbA/t41J4h7fzrlw8wUcSe', '', 'ar3@gmail.com', 'ar', 'a', '3', 'Male', NULL, 4, 'regular', NULL, '2025-11-08 06:03:01', '2025-11-08 06:03:01');

-- --------------------------------------------------------

--
-- Table structure for table `student_class_matrix`
--

CREATE TABLE `student_class_matrix` (
  `id` int(11) NOT NULL,
  `student_number` varchar(100) NOT NULL,
  `class_code` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_class_matrix`
--

INSERT INTO `student_class_matrix` (`id`, `student_number`, `class_code`) VALUES
(5, '202500002', 'PHIL-HISTORY11');

-- --------------------------------------------------------

--
-- Table structure for table `student_password_matrix`
--

CREATE TABLE `student_password_matrix` (
  `id` int(11) NOT NULL,
  `student_number` varchar(100) NOT NULL,
  `plain_password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_password_matrix`
--

INSERT INTO `student_password_matrix` (`id`, `student_number`, `plain_password`) VALUES
(1, '202500002', 'AsdAsd@2025'),
(2, '202500003', 'Ar1@2025'),
(3, '202500004', 'Ar2@2025'),
(4, '202500005', 'Ar3@2025');

-- --------------------------------------------------------

--
-- Table structure for table `student_quiz_answers`
--

CREATE TABLE `student_quiz_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attempt_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'For multiple choice/true-false',
  `answer_text` text DEFAULT NULL COMMENT 'For essay questions',
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_quiz_answers`
--

INSERT INTO `student_quiz_answers` (`id`, `attempt_id`, `question_id`, `option_id`, `answer_text`, `is_correct`, `points_earned`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 0, 0.00, '2025-11-10 06:16:22', '2025-11-10 06:16:22'),
(2, 1, 2, 6, NULL, 0, 0.00, '2025-11-10 06:16:22', '2025-11-10 06:16:22'),
(3, 2, 4, 7, NULL, 1, 1.00, '2025-11-10 06:53:20', '2025-11-10 06:53:20'),
(4, 2, 5, 11, NULL, 1, 1.00, '2025-11-10 06:53:20', '2025-11-10 06:53:20');

-- --------------------------------------------------------

--
-- Table structure for table `student_quiz_attempts`
--

CREATE TABLE `student_quiz_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(255) NOT NULL,
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) DEFAULT NULL,
  `total_points` decimal(5,2) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_quiz_attempts`
--

INSERT INTO `student_quiz_attempts` (`id`, `student_number`, `quiz_id`, `attempt_number`, `score`, `total_points`, `started_at`, `submitted_at`, `status`, `created_at`, `updated_at`) VALUES
(1, '202500005', 1, 1, 0.00, 12.00, '2025-11-10 06:16:21', '2025-11-10 06:16:21', 'graded', '2025-11-10 06:16:21', '2025-11-10 06:16:22'),
(2, '202500005', 2, 1, 2.00, 2.00, '2025-11-10 06:53:20', '2025-11-10 06:53:20', 'graded', '2025-11-10 06:53:20', '2025-11-10 06:53:20');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `user` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `first_name`, `middle_name`, `last_name`, `gender`, `email`, `phone`, `user`, `password`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(5, 'qwe', 'qwe', 'qwe', 'Male', 'qwe@gmail.com', 'wqe', 'qwe qwe', '$2y$12$7w8dRQSVMpb4gSJczJczs.iPNHUiKmbH28ZcALziFjg1GPmYQcKq.', '', 1, '2025-10-23 16:49:49', '2025-10-23 16:49:49'),
(6, 'John', 'a', 'Teacher', 'Female', 'johnteacher@gmail.com', '000', 'John Teacher', '$2y$12$nx8TBP5ctRGosv8mLZN9Je27/nlZawpRg2iKTPWGaXeQxDUAZGuue', '', 1, '2025-11-09 05:03:40', '2025-11-09 05:03:40');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_matrix`
--

CREATE TABLE `teacher_class_matrix` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_class_matrix`
--

INSERT INTO `teacher_class_matrix` (`id`, `teacher_id`, `class_id`) VALUES
(1, 5, 2),
(2, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_password_matrix`
--

CREATE TABLE `teacher_password_matrix` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `plain_password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_password_matrix`
--

INSERT INTO `teacher_password_matrix` (`id`, `teacher_id`, `plain_password`) VALUES
(1, 6, 'Teacher@2025');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lectures`
--
ALTER TABLE `lectures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_order` (`order_number`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_order` (`order_number`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lesson_id` (`lesson_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_id` (`quiz_id`),
  ADD KEY `idx_order` (`order_number`);

--
-- Indexes for table `quiz_question_options`
--
ALTER TABLE `quiz_question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question_id` (`question_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `section_class_matrix`
--
ALTER TABLE `section_class_matrix`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `strands`
--
ALTER TABLE `strands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_number_unique` (`student_number`);

--
-- Indexes for table `student_class_matrix`
--
ALTER TABLE `student_class_matrix`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_password_matrix`
--
ALTER TABLE `student_password_matrix`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_quiz_answers`
--
ALTER TABLE `student_quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempt_id` (`attempt_id`),
  ADD KEY `idx_question_id` (`question_id`);

--
-- Indexes for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_quiz` (`student_number`,`quiz_id`),
  ADD KEY `idx_quiz_id` (`quiz_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_class_matrix`
--
ALTER TABLE `teacher_class_matrix`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_password_matrix`
--
ALTER TABLE `teacher_password_matrix`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lectures`
--
ALTER TABLE `lectures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_question_options`
--
ALTER TABLE `quiz_question_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `section_class_matrix`
--
ALTER TABLE `section_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `student_class_matrix`
--
ALTER TABLE `student_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_password_matrix`
--
ALTER TABLE `student_password_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_quiz_answers`
--
ALTER TABLE `student_quiz_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_class_matrix`
--
ALTER TABLE `teacher_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_password_matrix`
--
ALTER TABLE `teacher_password_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
