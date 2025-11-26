-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2025 at 08:59 AM
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
(1, 'CLASS-001', 'Effective Communication', 30, 50, 20, '2025-10-29 18:35:42', '2025-11-20 19:29:02'),
(2, 'CLASS-002', 'General Mathematics', 30, 50, 20, '2025-10-29 18:36:52', '2025-11-20 19:29:15'),
(3, 'CLASS-003', 'General Science', 30, 50, 20, '2025-10-29 18:37:03', '2025-11-20 19:29:24'),
(4, 'CLASS-004', 'Life and Career Skills', 30, 50, 20, '2025-10-29 22:08:12', '2025-11-20 19:29:31'),
(5, 'CLASS-005', 'Mabisang Komunikasyon', 30, 50, 20, '2025-10-29 22:09:23', '2025-11-20 19:29:39'),
(6, 'CLASS-006', 'Pag-aaral ng Kasaysayan at Lipunang Pilipino', 30, 50, 20, '2025-11-11 04:51:34', '2025-11-20 19:29:46'),
(9, 'CLASS-007', 'Applied Economics', 40, 30, 30, '2025-11-20 19:28:40', '2025-11-20 19:28:40');

-- --------------------------------------------------------

--
-- Table structure for table `gradebook_columns`
--

CREATE TABLE `gradebook_columns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_code` varchar(100) NOT NULL,
  `component_type` enum('WW','PT','QA') NOT NULL,
  `column_name` varchar(50) NOT NULL,
  `max_points` decimal(8,2) NOT NULL,
  `order_number` int(11) DEFAULT 0,
  `source_type` enum('manual','online','imported') DEFAULT 'manual',
  `quiz_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gradebook_columns`
--

INSERT INTO `gradebook_columns` (`id`, `class_code`, `component_type`, `column_name`, `max_points`, `order_number`, `source_type`, `quiz_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CLASS-001', 'WW', 'WW1', 20.00, 1, 'manual', NULL, 1, '2025-11-20 03:07:35', '2025-11-20 03:07:35'),
(2, 'CLASS-001', 'WW', 'WW2', 10.00, 2, 'online', 1, 1, '2025-11-20 03:15:51', '2025-11-20 04:00:46'),
(3, 'CLASS-001', 'WW', 'WW3', 2.00, 3, 'online', 2, 1, '2025-11-20 03:45:48', '2025-11-20 03:59:01'),
(4, 'CLASS-001', 'WW', 'WW4', 1.00, 4, 'online', 3, 1, '2025-11-20 04:00:13', '2025-11-20 04:00:17'),
(5, 'CLASS-001', 'WW', 'WW5', 10.00, 5, 'manual', NULL, 1, '2025-11-20 04:01:46', '2025-11-20 04:01:46'),
(6, 'CLASS-001', 'PT', 'PT1', 10.00, 1, 'manual', NULL, 1, '2025-11-20 04:02:44', '2025-11-20 04:02:44'),
(7, 'CLASS-001', 'WW', 'WW6', 10.00, 6, 'manual', NULL, 1, '2025-11-20 04:06:10', '2025-11-20 04:06:10'),
(8, 'CLASS-001', 'WW', 'WW7', 10.00, 7, 'manual', NULL, 1, '2025-11-20 04:06:10', '2025-11-20 04:06:10'),
(9, 'CLASS-001', 'WW', 'WW8', 10.00, 8, 'manual', NULL, 1, '2025-11-20 04:06:21', '2025-11-20 04:06:21'),
(10, 'CLASS-001', 'WW', 'WW9', 10.00, 9, 'manual', NULL, 1, '2025-11-20 04:06:22', '2025-11-20 04:06:22'),
(11, 'CLASS-001', 'WW', 'WW10', 10.00, 10, 'manual', NULL, 1, '2025-11-20 04:06:24', '2025-11-20 04:06:24'),
(31, 'CLASS-001', 'PT', 'PT2', 10.00, 2, 'manual', NULL, 1, '2025-11-20 06:32:18', '2025-11-20 06:32:18'),
(32, 'CLASS-001', 'QA', 'QA1', 50.00, 1, 'manual', NULL, 1, '2025-11-20 18:28:07', '2025-11-20 18:28:07'),
(33, 'CLASS-001', 'QA', 'QA2', 50.00, 2, 'manual', NULL, 1, '2025-11-20 18:28:10', '2025-11-20 18:28:10');

-- --------------------------------------------------------

--
-- Table structure for table `gradebook_scores`
--

CREATE TABLE `gradebook_scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `column_id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(255) NOT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `source` enum('manual','online','imported') DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gradebook_scores`
--

INSERT INTO `gradebook_scores` (`id`, `column_id`, `student_number`, `score`, `remarks`, `source`, `created_at`, `updated_at`) VALUES
(8, 3, '202500005', 10.00, NULL, 'online', NULL, '2025-11-20 03:59:02'),
(9, 4, '202500005', 10.00, NULL, 'online', NULL, '2025-11-20 04:00:17'),
(12, 32, '202500005', 35.00, NULL, 'manual', NULL, '2025-11-20 18:28:42'),
(13, 33, '202500005', 40.00, NULL, 'manual', NULL, '2025-11-20 18:28:42'),
(14, 1, '202500005', 15.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(15, 5, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(16, 7, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(17, 8, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(18, 9, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(19, 10, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(20, 11, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:21'),
(21, 6, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:46'),
(22, 31, '202500005', 8.00, NULL, 'manual', NULL, '2025-11-20 18:29:46');

-- --------------------------------------------------------

--
-- Table structure for table `grades_final`
--

CREATE TABLE `grades_final` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(100) NOT NULL,
  `class_code` varchar(100) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `ww_score` decimal(5,2) DEFAULT NULL,
  `ww_percentage` int(11) NOT NULL,
  `pt_score` decimal(5,2) DEFAULT NULL,
  `pt_percentage` int(11) NOT NULL,
  `qa_score` decimal(5,2) DEFAULT NULL,
  `qa_percentage` int(11) NOT NULL,
  `final_grade` decimal(5,2) NOT NULL,
  `remarks` enum('PASSED','FAILED','INC','DRP','W') NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `computed_by` int(11) DEFAULT NULL,
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_components`
--

CREATE TABLE `grade_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(100) NOT NULL,
  `class_code` varchar(100) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `component_type` enum('WW','PT','QA') NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, 'Lecture 3 - PPT File', NULL, 'file', 'lectures/1764039630_Lecture3.pptx', 1, 1, '2025-11-09 07:16:50', '2025-11-25 03:00:30'),
(2, 1, 'Lecture File', NULL, 'file', 'lectures/pxexRsiallOchyskpxEkf1oXdAXQhfqHo0aIUB0a', 0, 0, '2025-11-09 07:18:51', '2025-11-10 01:13:35'),
(3, 1, 'Lecture 1 - PDF File', NULL, 'pdf', 'lectures/1764037315_Lecture1.pdf', 0, 1, '2025-11-09 07:20:13', '2025-11-25 02:21:54'),
(4, 1, 'Lecture 2 - DOCX File', NULL, 'pdf', 'lectures/1764037333_Lecture1.docx', 0, 1, '2025-11-09 07:27:28', '2025-11-25 02:22:13'),
(5, 1, 'Lecture 4 - Text Content', 'Text Content', 'text', NULL, 0, 1, '2025-11-25 02:26:47', '2025-11-25 02:27:16');

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
(1, 1, 'Lesson 1', 'Examples of Available Lectures and Quizzes', 1, 1, '2025-11-09 05:52:48', '2025-11-25 02:34:42'),
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
(1, 1, 'Quiz 1 - Multiple Choice', NULL, 60, 75.00, 1, 1, 1, 1, '2025-11-10 01:49:22', '2025-11-22 03:23:28'),
(2, 1, 'Quiz 2 - True/False', NULL, 60, 75.00, 1, 1, 1, 1, '2025-11-10 06:51:21', '2025-11-22 03:25:01'),
(3, 1, 'Quiz 3 - Essay', NULL, 60, 75.00, 1, 1, 1, 1, '2025-11-11 22:07:12', '2025-11-22 03:25:53'),
(4, 1, 'Quiz 4 - Multiple Answer', NULL, 60, 75.00, 1, 1, 0, 1, '2025-11-22 13:44:49', '2025-11-22 13:44:49'),
(5, 1, 'Quiz 5 - Short Answer', NULL, 60, 75.00, 1, 1, 0, 1, '2025-11-22 14:00:35', '2025-11-22 14:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','multiple_answer','true_false','short_answer','essay') DEFAULT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exact_match` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`, `order_number`, `created_at`, `updated_at`, `exact_match`) VALUES
(23, 1, '1', 'multiple_choice', 1.00, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(24, 1, '2', 'multiple_choice', 1.00, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(25, 1, '3', 'multiple_choice', 1.00, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(26, 1, '4', 'multiple_choice', 1.00, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(27, 1, '5', 'multiple_choice', 1.00, 5, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(28, 1, '6', 'multiple_choice', 1.00, 6, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(29, 1, '7', 'multiple_choice', 1.00, 7, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(30, 1, '8', 'multiple_choice', 1.00, 8, '2025-11-22 03:23:28', '2025-11-22 03:23:28', 1),
(31, 2, 'True', 'true_false', 1.00, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(32, 2, 'False', 'true_false', 1.00, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(33, 2, 'True', 'true_false', 1.00, 3, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(34, 2, 'False', 'true_false', 1.00, 4, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(35, 2, 'True', 'true_false', 1.00, 5, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(36, 2, 'False', 'true_false', 1.00, 6, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(37, 2, 'True', 'true_false', 1.00, 7, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(38, 2, 'False', 'true_false', 1.00, 8, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(39, 2, 'True', 'true_false', 1.00, 9, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(40, 2, 'False', 'true_false', 1.00, 10, '2025-11-22 03:25:01', '2025-11-22 03:25:01', 1),
(43, 3, 'Write an Essay', 'essay', 5.00, 1, '2025-11-22 03:25:53', '2025-11-22 03:25:53', 1),
(44, 3, 'Write another Essay', 'essay', 5.00, 2, '2025-11-22 03:25:53', '2025-11-22 03:25:53', 1),
(45, 4, 'a', 'multiple_answer', 1.00, 1, '2025-11-22 13:44:49', '2025-11-22 13:44:49', 1),
(49, 5, 'A', 'short_answer', 1.00, 1, '2025-11-22 14:02:47', '2025-11-22 14:02:47', 1),
(50, 5, 'C', 'short_answer', 1.00, 2, '2025-11-22 14:02:47', '2025-11-22 14:02:47', 0);

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
(83, 23, '1', 1, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(84, 23, '2', 0, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(85, 23, '3', 0, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(86, 23, '4', 0, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(87, 24, '1', 0, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(88, 24, '2', 1, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(89, 24, '3', 0, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(90, 24, '4', 0, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(91, 25, '1', 0, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(92, 25, '2', 0, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(93, 25, '3', 1, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(94, 25, '4', 0, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(95, 26, '1', 0, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(96, 26, '2', 0, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(97, 26, '3', 0, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(98, 26, '4', 1, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(99, 27, '5', 1, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(100, 27, '6', 0, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(101, 27, '7', 0, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(102, 27, '8', 0, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(103, 28, '5', 0, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(104, 28, '6', 1, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(105, 28, '7', 0, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(106, 28, '8', 0, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(107, 29, '5', 0, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(108, 29, '6', 0, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(109, 29, '7', 1, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(110, 29, '8', 0, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(111, 30, '5', 0, 1, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(112, 30, '6', 0, 2, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(113, 30, '7', 0, 3, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(114, 30, '8', 1, 4, '2025-11-22 03:23:28', '2025-11-22 03:23:28'),
(115, 31, 'True', 1, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(116, 31, 'False', 0, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(117, 32, 'True', 0, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(118, 32, 'False', 1, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(119, 33, 'True', 1, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(120, 33, 'False', 0, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(121, 34, 'True', 0, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(122, 34, 'False', 1, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(123, 35, 'True', 1, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(124, 35, 'False', 0, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(125, 36, 'True', 0, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(126, 36, 'False', 1, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(127, 37, 'True', 1, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(128, 37, 'False', 0, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(129, 38, 'True', 0, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(130, 38, 'False', 1, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(131, 39, 'True', 1, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(132, 39, 'False', 0, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(133, 40, 'True', 0, 1, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(134, 40, 'False', 1, 2, '2025-11-22 03:25:01', '2025-11-22 03:25:01'),
(135, 45, 'a', 1, 1, '2025-11-22 13:44:49', '2025-11-22 13:44:49'),
(136, 45, 'aa', 1, 2, '2025-11-22 13:44:49', '2025-11-22 13:44:49'),
(137, 45, 'aaa', 1, 3, '2025-11-22 13:44:49', '2025-11-22 13:44:49'),
(138, 45, 'b', 0, 4, '2025-11-22 13:44:49', '2025-11-22 13:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_short_answers`
--

CREATE TABLE `quiz_short_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `answer_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_short_answers`
--

INSERT INTO `quiz_short_answers` (`id`, `question_id`, `answer_text`, `created_at`, `updated_at`) VALUES
(4, 49, 'A', '2025-11-22 14:02:47', '2025-11-22 14:02:47'),
(5, 50, 'C', '2025-11-22 14:02:47', '2025-11-22 14:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `year_start` year(4) NOT NULL,
  `year_end` year(4) NOT NULL,
  `code` varchar(20) NOT NULL,
  `status` enum('active','completed','upcoming') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `year_start`, `year_end`, `code`, `status`, `created_at`, `updated_at`) VALUES
(1, '2024', '2025', '2024-2025', 'active', '2025-11-17 00:23:31', '2025-11-19 07:36:51'),
(2, '2025', '2026', '2025-2026', 'upcoming', '2025-11-17 01:03:15', '2025-11-17 01:03:15'),
(3, '2026', '2027', '2026-2027', 'upcoming', '2025-11-19 01:09:53', '2025-11-19 01:09:53');

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
  `semester_id` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `code`, `name`, `strand_id`, `level_id`, `semester_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ICT-11A', 'Sagittarius', 1, 1, NULL, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:28'),
(2, 'ICT-11B', 'Capricorn', 1, 1, NULL, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:33'),
(3, 'ABM-11A', 'Virgo', 2, 1, NULL, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:35'),
(4, 'ABM-12A', 'Aries', 2, 2, NULL, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:39'),
(5, 'HUMMS-12A', 'Gemini', 3, 2, NULL, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:42'),
(6, 'HUMMS-12B', 'Libra', 3, 2, NULL, 1, '2025-10-19 09:17:04', '2025-10-30 05:45:44'),
(7, 'HUMMS-11A', 'Pisces', 3, 1, NULL, 1, '2025-10-19 09:36:06', '2025-10-30 05:45:46'),
(8, 'HUMMS-11B', 'Aquarius', 3, 1, NULL, 1, '2025-10-19 09:36:06', '2025-10-30 05:45:48'),
(9, 'GAS-11A', 'Ophiuchus', 4, 1, NULL, 1, '2025-10-19 09:36:32', '2025-10-30 05:45:50'),
(10, 'GAS-11B', 'Leo', 4, 1, NULL, 1, '2025-10-19 09:36:32', '2025-10-30 05:45:52'),
(11, 'HE-11A', 'Taurus', 5, 1, NULL, 1, '2025-10-19 09:37:56', '2025-10-30 05:45:54'),
(12, 'HE-11B', 'Cancer', 5, 1, NULL, 1, '2025-10-19 09:37:56', '2025-10-30 05:45:55'),
(13, 'HE-11C', 'Scorpio', 5, 1, NULL, 1, '2025-10-19 09:37:56', '2025-10-30 05:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `section_class_matrix`
--

CREATE TABLE `section_class_matrix` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section_class_matrix`
--

INSERT INTO `section_class_matrix` (`id`, `section_id`, `class_id`, `semester_id`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(2, 1, 3, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(3, 1, 4, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(4, 5, 3, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(5, 5, 1, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(6, 5, 5, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(7, 4, 3, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(8, 4, 4, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(9, 4, 2, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(10, 4, 1, 1, '2025-11-20 01:12:14', '2025-11-20 01:12:14'),
(11, 1, 1, 1, '2025-11-19 17:12:26', '2025-11-19 17:12:26'),
(12, 2, 1, 1, '2025-11-19 17:13:58', '2025-11-19 17:13:58'),
(13, 12, 9, 1, '2025-11-21 14:46:48', '2025-11-21 14:46:48'),
(14, 12, 1, 1, '2025-11-21 14:46:48', '2025-11-21 14:46:48'),
(15, 12, 4, 1, '2025-11-21 14:46:48', '2025-11-21 14:46:48'),
(16, 12, 5, 1, '2025-11-21 14:46:48', '2025-11-21 14:46:48'),
(18, 13, 1, 1, '2025-11-21 15:05:14', '2025-11-21 15:05:14');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','upcoming') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `school_year_id`, `name`, `code`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1st Semester', 'SEM1', '2025-11-01', '2025-11-30', 'active', '2025-11-17 00:51:42', '2025-11-18 23:36:51'),
(2, 1, '2nd Semester', 'SEM2', '2025-12-01', '2025-12-31', 'upcoming', '2025-11-17 01:02:50', '2025-11-17 01:02:50');

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
('OiE5K21PeC6YAtkff9JQRcRTMlvHFPVTFU1co2SV', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiaFQ2RThxTWZZVmhxT0tMR3JzSGVrenhzY2FiOXpjZzNkV01uVUp0dyI7czoyMjoiUEhQREVCVUdCQVJfU1RBQ0tfREFUQSI7YTowOnt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC90ZWFjaGVyL2NsYXNzLzEvbGVzc29ucyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTQ6ImxvZ2luX3RlYWNoZXJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo2O3M6NTQ6ImxvZ2luX3N0dWRlbnRfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7czo5OiIyMDI1MDAwMDIiO3M6MjQ6InN0dWRlbnRfY2xhc3Nlc18zMV9zZW1fMSI7YTo0OntpOjA7Tzo4OiJzdGRDbGFzcyI6Mzp7czoyOiJpZCI7aToxO3M6MTA6ImNsYXNzX2NvZGUiO3M6OToiQ0xBU1MtMDAxIjtzOjEwOiJjbGFzc19uYW1lIjtzOjIzOiJFZmZlY3RpdmUgQ29tbXVuaWNhdGlvbiI7fWk6MTtPOjg6InN0ZENsYXNzIjozOntzOjI6ImlkIjtpOjI7czoxMDoiY2xhc3NfY29kZSI7czo5OiJDTEFTUy0wMDIiO3M6MTA6ImNsYXNzX25hbWUiO3M6MTk6IkdlbmVyYWwgTWF0aGVtYXRpY3MiO31pOjI7Tzo4OiJzdGRDbGFzcyI6Mzp7czoyOiJpZCI7aTozO3M6MTA6ImNsYXNzX2NvZGUiO3M6OToiQ0xBU1MtMDAzIjtzOjEwOiJjbGFzc19uYW1lIjtzOjE1OiJHZW5lcmFsIFNjaWVuY2UiO31pOjM7Tzo4OiJzdGRDbGFzcyI6Mzp7czoyOiJpZCI7aTo0O3M6MTA6ImNsYXNzX2NvZGUiO3M6OToiQ0xBU1MtMDA0IjtzOjEwOiJjbGFzc19uYW1lIjtzOjIyOiJMaWZlIGFuZCBDYXJlZXIgU2tpbGxzIjt9fX0=', 1764136411);

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
(1, 'ICT', 'Information and Communication Technology', 1, '2025-10-19 09:10:46', '2025-11-11 13:46:39'),
(2, 'ABM', 'Accountancy, Business and Management', 1, '2025-10-19 09:10:46', '2025-11-11 13:46:44'),
(3, 'HUMSS', 'Humanities and Social Sciences', 1, '2025-10-19 09:10:46', '2025-11-11 13:46:48'),
(4, 'GAS', 'General Academic Strand', 1, '2025-10-19 09:10:46', '2025-11-11 13:46:51'),
(5, 'HE', ' Home Economics', 1, '2025-10-19 09:37:05', '2025-11-11 13:46:55');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `student_password` varchar(100) NOT NULL,
  `rememberToken` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `current_semester_id` int(11) DEFAULT NULL,
  `student_type` enum('regular','irregular') NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_number`, `student_password`, `rememberToken`, `email`, `first_name`, `middle_name`, `last_name`, `gender`, `profile_image`, `section_id`, `current_semester_id`, `student_type`, `enrollment_date`, `created_at`, `updated_at`) VALUES
(26, '202500001', '$2y$12$YZcRrDI0odM4BFNHplwX8u93AFhJcRiBtGNd8UHMpzSFOLkLzdzrS', '', 'student@gmail.com', 'Amanda', 'V', 'Aquino', 'Female', NULL, 3, NULL, 'regular', NULL, '2025-11-07 04:10:02', '2025-11-07 04:10:02'),
(31, '202500002', '$2y$12$4b.5ExODaUTuKEShaztEdO5ziM6Ef3tvKEhWvhypMKKJ4dCWMyoiK', '', '', 'Aaliyah	', 'A', 'Santos', 'Female', NULL, 1, NULL, 'regular', NULL, '2025-11-06 20:49:13', '2025-11-06 20:49:13'),
(32, '202500003', '$2y$12$c5afSaPZQ74OX06ZqQYRUeHkhw4ZiGvpsF95XmrJQPt1EnHg44H2C', '', NULL, 'Aileens', 'A', 'Reyes', 'Male', NULL, 4, NULL, 'regular', NULL, '2025-11-08 06:03:01', '2025-11-20 23:10:57'),
(33, '202500004', '$2y$12$oBU92qi4HBPD8wN1a9sDjeF/vXj0e3773lPWE6n8K/E55lCTKdIla', '', '', 'Alfred', 'J', 'Lopez', 'Male', NULL, 4, NULL, 'regular', NULL, '2025-11-08 06:03:01', '2025-11-08 06:03:01'),
(371, '202500005', '$2y$12$Pt4gtibzOED8ZG6KH8EIZOgg4vUI7pe6vcotec7ze0fttLD9h.5/a', NULL, NULL, 'Beatrices', 'B', 'Agustin', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-21 04:55:30'),
(372, '202500006', '$2y$12$3ajBk8D1GYT7wlzEqFy3j.aNG08Q7HmdvSzpvzRddpXYf5M/p.V9u', NULL, '', 'Denzel Given Jay', 'B', 'Amoguis', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(373, '202500007', '$2y$12$zv.UNIyn0D9bR0qUDMdn..86K09CkiwWmNDiJvjWXi1PAjmOnEyem', NULL, '', 'Erish Kate', 'A', 'Areola', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(374, '202500008', '$2y$12$RYcF/jpVXg7NJiGNrWc/T.hPPiTPQRqRR4McPzGPw7W53nmOCV7OC', NULL, '', 'Daisy Rey Mae', 'R', 'Bariring', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(375, '202500009', '$2y$12$y.1aFcQKiaMaLAuIpB0jMeRhVjBxb2FN8e9ePzzy.XNeR50pSuJCy', NULL, '', 'Krizzie', 'R', 'Basilan', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(376, '202500010', '$2y$12$IZHaJvrko3FRNEJmYzwjc.rJRcbCaRGCugpjSGmIseumraksyo/IW', NULL, '', 'Danica', 'A', 'Bautista', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(377, '202500011', '$2y$12$ZpKFkMDk2kZ1jLuzRs.x7.FvSBY4KhSZdt9Fkw2b7TMVD2O4Zxu9y', NULL, '', 'Kyla Mae', 'T', 'Borjal', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(378, '202500012', '$2y$12$BU0X0KCCHDWhfavYSKzZde1RRnNkGs.ScGfTHU5nXr91/qqrTXzrG', NULL, '', 'Hazel Anne', 'C', 'Bragado', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(379, '202500013', '$2y$12$SXAX58nnB5zSYB2JD0MLPO8GIRQ2dUP.J5K5CV2cwOGy0g6ogQa7y', NULL, '', 'Angel', 'C', 'Catabay', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(380, '202500014', '$2y$12$enDk9.9kgGTxF1FB248yi.6Yy9RQ1sbPvsrYO/YAC3dTrFFaPtgXG', NULL, '', 'Sandie May', 'C', 'Condes', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(381, '202500015', '$2y$12$DEQJWm/uhFNS10P0AwcEj.JoBvGtmW8r9C9ebl8mg6eqkkkl5pYoi', NULL, '', 'Denise', 'E', 'Cristobal', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(382, '202500016', '$2y$12$c1muUpe1jenCch7.UNYaUezT3NqY0uKWJe6Ij/QHbAmSN9halClgy', NULL, '', 'Francine', 'S', 'David', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(383, '202500017', '$2y$12$oDih1YvD1.lvHPUSoqWs6OWF/JKbOidpawX/6K7wZzRoN7/GQFrw.', NULL, '', 'Ariel', 'B', 'David Jr.', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(384, '202500018', '$2y$12$ZQ/y2whT6dy7UhvutMwnq.TcgQ.GqzrK27wWSPEjguBQaWnFOo4MS', NULL, '', 'Lorein Mae', 'T', 'Dela Cruz', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(385, '202500019', '$2y$12$Hg/KQHgonvBksO5RIMLTyOgDlzgKTTRyntFEa8lSyScLBUCrwII1i', NULL, '', 'Cheanbie', 'S', 'Dela Torre', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(386, '202500020', '$2y$12$hJZ1MIrb.6ISuTqWDUihR.od4vPCZe/NUzDiE4b1Wl6.i2pxn3Gm2', NULL, '', 'Loren Angela', 'O', 'Fabio', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(387, '202500021', '$2y$12$3qwvtwQYCwrQiSpxgIrjzetXPealUY38SkDLfwPsl5N3bZ9OXIAjm', NULL, '', 'Jiann Paulo', 'D', 'Grajo', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(388, '202500022', '$2y$12$dn/ydzIANF7BF1G2NST2PusTHL9zM7mEA/4MtnzeX/T5xZYuc7QRO', NULL, '', 'Aeron Paul', 'F', 'Gumafelix', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(389, '202500023', '$2y$12$TEhTjpE21GK/vpaMcprKl.kLj7iQ/WL1A5zlMOP11ChdzHEpuTB/i', NULL, '', 'John Bryan', 'B', 'Herrera', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(390, '202500024', '$2y$12$a1KI8cPYFLl5ug1TYPxVpOJfp8YT8HWCD81IbpFrAJ4YCaGteNIvG', NULL, '', 'Jhannasheen Luijay', 'L', 'Huerto', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(391, '202500025', '$2y$12$yvUj9IhkJxyHdyM.q.Xiou8xNIXlej6lxW1J.vMbFjIFr9OofhGz2', NULL, '', 'Adriana', 'G', 'Jamandron', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(392, '202500026', '$2y$12$rAhChGGHVuTc0cplRSwyWOH.tTZtreOltJcW5i/RlxQZNouvl4xfu', NULL, '', 'Madelyn', 'M', 'Lagman', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(393, '202500027', '$2y$12$AZswU/gyCDxzOHtf98Io0.k2GfUz/a5Fo9EAVrvtCm9m0rKVAIRF2', NULL, '', 'Nicole', 'G', 'Lagpao', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(394, '202500028', '$2y$12$Q4NpKFgix6aKESXZtOy/i.cTP7CXpzkd0l6HIn9awWmlgPvW67qX2', NULL, '', 'Nicole', 'R', 'Lara', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(395, '202500029', '$2y$12$wnJ17vNFlNvmO6o4DMn8cu3BI0NVWgYMeRFPRs4t8eeu.TKJ6uSpe', NULL, '', 'Rich Ann Alexa', 'Q', 'Lee', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:27', '2025-11-20 21:07:27'),
(396, '202500030', '$2y$12$esci2Ly068pv8XiOHzZpCOqePk0NShyT9Z.bf30DMc/AWUMcOE1zK', NULL, '', 'Princes Liane', 'T', 'Manalaysay', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(397, '202500031', '$2y$12$w2.oC6HSe9yOaBAEHvacbuk1S3.729A9Fu2LPNe5b//dz2w0EMLc.', NULL, '', 'Melissa', 'Q', 'Mateo', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(398, '202500032', '$2y$12$zZpvjarxoilRStxO0Tun1.uPNwRsXtNlXC0rKeHvkWQ7KPE.4zcaq', NULL, '', 'Trisha Mae', 'M', 'Molino', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(399, '202500033', '$2y$12$4h8/NV96emyRsm8H5u6X8.R7mMCIiN8pIiorX7GNBQWPGlbx.quOK', NULL, '', 'Rachelle Mae', 'C', 'Navora', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(400, '202500034', '$2y$12$9To/EhgjOwX7mIIsJYUqoOOtbDHiiq79ISmkyegaKnHiZ7SfIWwuO', NULL, '', 'Shirley Mae', 'B', 'Nedia', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(401, '202500035', '$2y$12$ktULyMLP9iJ1TNlZUR.WruqMstgmPXAvOQI1vy.v0w7BiPVEHQrfS', NULL, '', 'Kent Joncel', 'T', 'Obligacion', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(402, '202500036', '$2y$12$DuvD7tPkm7y.sx36gPSDZuamvwsjLVThiUWl96RQD09Wp8TEy89zW', NULL, '', 'Nylenedyl', 'DJ', 'Pacle', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(403, '202500037', '$2y$12$ZN/qvwv.JyBS32zVl3ox7.gLA9qcgFyl4x/wyv6dqqSiF59ZGi6k.', NULL, '', 'Ashley', 'P', 'Pelayo', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(404, '202500038', '$2y$12$b.SY2QvGk/POLo/m.Q7wmOKjts5xjbitSKHtUy/PBCJqPCoYxwSka', NULL, '', 'Erwin', 'C', 'Penales', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(405, '202500039', '$2y$12$tliEHJaLxV9LSqdG0mOgm.QjVMqbEZsfYO9FcT29yiH8MZfKC.3cK', NULL, '', 'Gillane Ashley', 'A', 'Salvador', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(406, '202500040', '$2y$12$dmGngKzaGdAoPPqcritPX.gdczV6JZh.M5offYah.nMcDk7aUCnJO', NULL, '', 'Clent', 'G', 'Tuando', 'Male', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33'),
(407, '202500041', '$2y$12$.fh3.hb3t2HLStftqQgAXuhbAN5GkTey8NTkgbCtJCjs6AO5HmxvG', NULL, '', 'Jaine Rose', 'C', 'Verunque', 'Female', NULL, 4, NULL, 'regular', '2025-11-21', '2025-11-20 21:07:33', '2025-11-20 21:07:33');

-- --------------------------------------------------------

--
-- Table structure for table `student_class_matrix`
--

CREATE TABLE `student_class_matrix` (
  `id` int(11) NOT NULL,
  `student_number` varchar(100) NOT NULL,
  `class_code` varchar(100) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `enrollment_status` enum('enrolled','dropped','completed') DEFAULT 'enrolled',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_class_matrix`
--

INSERT INTO `student_class_matrix` (`id`, `student_number`, `class_code`, `semester_id`, `enrollment_status`, `updated_at`) VALUES
(5, '202500002', 'CLASS-004', 1, 'enrolled', '2025-11-21 03:56:05');

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
(4, '202500005', 'Ar3@2025'),
(341, '202500005', 'mTaJ37p8jS'),
(342, '202500006', '0JOjSFrzw7'),
(343, '202500007', 'UorIQsQvgW'),
(344, '202500008', 'r2w20YwS9n'),
(345, '202500009', 'xGcPnvuLxQ'),
(346, '202500010', 'RsCl2wvuOh'),
(347, '202500011', 'SlHeo0qrcE'),
(348, '202500012', '2sO27AJNed'),
(349, '202500013', 'D8MzEnLXxW'),
(350, '202500014', 'XL5gHVYiEx'),
(351, '202500015', 'ajCGUrQzCQ'),
(352, '202500016', 'rD9D6Mb63c'),
(353, '202500017', 'oGdfAIWl38'),
(354, '202500018', 'wSIbz7Oqse'),
(355, '202500019', 'GENqha3uc0'),
(356, '202500020', 'PEaem6B5xM'),
(357, '202500021', 'yZ3pNyTNkq'),
(358, '202500022', 'YNKGPZRbY2'),
(359, '202500023', 'QnKhzcnqzG'),
(360, '202500024', 'TXQXLQsRYS'),
(361, '202500025', '21W3s2QhPW'),
(362, '202500026', 'L5EQYCC2U5'),
(363, '202500027', 'KetmfWLEyF'),
(364, '202500028', 'iLnozd9CAx'),
(365, '202500029', 'wt6lcZXUzU'),
(366, '202500030', 'sGwoRHSCgE'),
(367, '202500031', 'Ie9acEhqk2'),
(368, '202500032', '20gC7JOl7T'),
(369, '202500033', 'Y7NJG0NwsP'),
(370, '202500034', 'O77dfGIByG'),
(371, '202500035', 'ZE1xi7pQoi'),
(372, '202500036', 'iEA3A8YPzh'),
(373, '202500037', 'Mqu67nzxG2'),
(374, '202500038', 'p74h4yHliH'),
(375, '202500039', 'T8U8SuUDNo'),
(376, '202500040', '6garlUMdK1'),
(377, '202500041', 'upsoxbNoF6');

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
(11, 9, 23, 83, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(12, 9, 24, 88, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(13, 9, 25, 93, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(14, 9, 26, 98, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(15, 9, 27, 99, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(16, 9, 28, 104, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(17, 9, 29, 109, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(18, 9, 30, 114, NULL, 1, 1.00, '2025-11-22 03:40:19', '2025-11-22 03:40:19'),
(19, 27, 45, 135, NULL, 1, 1.00, '2025-11-22 13:58:55', '2025-11-22 13:58:55'),
(20, 27, 45, 136, NULL, 1, 1.00, '2025-11-22 13:58:55', '2025-11-22 13:58:55'),
(21, 27, 45, 137, NULL, 1, 1.00, '2025-11-22 13:58:55', '2025-11-22 13:58:55'),
(24, 29, 49, NULL, 'a', 1, 1.00, '2025-11-22 14:03:01', '2025-11-22 14:03:01'),
(25, 29, 50, NULL, 'c', 1, 1.00, '2025-11-22 14:03:01', '2025-11-22 14:03:01'),
(26, 30, 43, NULL, 'asd', NULL, 0.00, '2025-11-22 14:07:35', '2025-11-22 14:07:35'),
(27, 30, 44, NULL, 'asdasda', NULL, 0.00, '2025-11-22 14:07:35', '2025-11-22 14:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `student_quiz_attempts`
--

CREATE TABLE `student_quiz_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(255) NOT NULL,
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
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

INSERT INTO `student_quiz_attempts` (`id`, `student_number`, `quiz_id`, `semester_id`, `attempt_number`, `score`, `total_points`, `started_at`, `submitted_at`, `status`, `created_at`, `updated_at`) VALUES
(9, '202500002', 1, NULL, 1, 8.00, 8.00, '2025-11-22 03:38:39', '2025-11-22 03:40:19', 'graded', '2025-11-22 03:38:39', '2025-11-22 03:40:19'),
(26, '202500002', 2, NULL, 1, NULL, 10.00, '2025-11-22 12:53:32', NULL, 'in_progress', '2025-11-22 12:53:32', '2025-11-22 13:06:34'),
(27, '202500002', 4, NULL, 1, 1.00, 1.00, '2025-11-22 13:44:58', '2025-11-22 13:58:55', 'graded', '2025-11-22 13:44:58', '2025-11-22 13:58:55'),
(29, '202500002', 5, NULL, 1, 2.00, 2.00, '2025-11-22 14:02:53', '2025-11-22 14:03:01', 'graded', '2025-11-22 14:02:53', '2025-11-22 14:03:01'),
(30, '202500002', 3, NULL, 1, 0.00, 10.00, '2025-11-22 14:07:28', '2025-11-22 14:07:35', 'submitted', '2025-11-22 14:07:28', '2025-11-22 14:07:35');

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
(5, 'Quea', 'ue', 'Teacher', 'Male', 'qwe@gmail.com', 'wqe', 'Quea Teacher', '$2y$12$7w8dRQSVMpb4gSJczJczs.iPNHUiKmbH28ZcALziFjg1GPmYQcKq.', '', 1, '2025-10-23 16:49:49', '2025-11-10 23:42:58'),
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
(2, 6, 1),
(3, 6, 2),
(4, 6, 3);

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
-- Indexes for table `gradebook_columns`
--
ALTER TABLE `gradebook_columns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`,`component_type`,`column_name`);

--
-- Indexes for table `gradebook_scores`
--
ALTER TABLE `gradebook_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `column_id` (`column_id`,`student_number`);

--
-- Indexes for table `grades_final`
--
ALTER TABLE `grades_final`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_final_grade` (`student_number`,`class_code`,`semester_id`);

--
-- Indexes for table `grade_components`
--
ALTER TABLE `grade_components`
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
-- Indexes for table `quiz_short_answers`
--
ALTER TABLE `quiz_short_answers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `section_class_matrix`
--
ALTER TABLE `section_class_matrix`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section_class_semester` (`section_id`,`class_id`,`semester_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_year_id` (`school_year_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_class_semester` (`student_number`,`class_code`,`semester_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gradebook_columns`
--
ALTER TABLE `gradebook_columns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `gradebook_scores`
--
ALTER TABLE `gradebook_scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `grades_final`
--
ALTER TABLE `grades_final`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lectures`
--
ALTER TABLE `lectures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `quiz_question_options`
--
ALTER TABLE `quiz_question_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `quiz_short_answers`
--
ALTER TABLE `quiz_short_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `section_class_matrix`
--
ALTER TABLE `section_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=408;

--
-- AUTO_INCREMENT for table `student_class_matrix`
--
ALTER TABLE `student_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_password_matrix`
--
ALTER TABLE `student_password_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=378;

--
-- AUTO_INCREMENT for table `student_quiz_answers`
--
ALTER TABLE `student_quiz_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `student_quiz_attempts`
--
ALTER TABLE `student_quiz_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_class_matrix`
--
ALTER TABLE `teacher_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`);

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
