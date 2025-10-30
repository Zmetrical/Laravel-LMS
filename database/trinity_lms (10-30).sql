-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 10:39 AM
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
('KElsQVGSrVVf96fp2h14R8iHRVOwrOJeda0FW8u0', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUkFZYWpTT1ZHNFpvMVJub3JsMmhHcjdoUXhvc3ZoakRhNmpJMlQ4aCI7czoyMjoiUEhQREVCVUdCQVJfU1RBQ0tfREFUQSI7YTowOnt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9lbnJvbGxtZW50X21hbmFnZW1lbnQvZW5yb2xsX2NsYXNzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1761817109);

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
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_number`, `student_password`, `email`, `first_name`, `middle_name`, `last_name`, `gender`, `profile_image`, `section_id`, `enrollment_date`, `created_at`, `updated_at`) VALUES
(21, '2024-0001', '$2y$12$pF9aAOTvujmIol6K6i/2xeqpt8ea7X8rXOAE1YWrJG5mjRymla8T.', 'john.reel@example.com', 'Johnnyere', 'Michael', 'Reelsr', 'Male', NULL, 1, '2024-08-15', '2025-10-21 00:56:21', '2025-10-21 23:47:10'),
(22, '2024-0002', '$2y$12$87rudi6DhgzVQovq4A6yQeZFebLmdgeZBZsB1Jn781R068u.kzG3G', 'jane.smith@example.com', 'Jane', 'Marie', 'Smith', 'Female', NULL, 1, '2024-08-15', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(23, '2024-0003', '$2y$12$S4pTqTk8N/z/CHVqjK0gNuQGT.q2GDidRpnDOJbRI/Gq2qI9TED4K', 'robert.johnson@example.com', 'Robert', 'James', 'Johnson', 'Male', NULL, 2, '2024-08-16', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(24, '2024-0004', '$2y$12$NMbSIEqGsXQ.D5QqAX92LeDjduzfjP0kd0J198x.R7AefIDSaPpMe', 'emily.williams@example.com', 'Emily', 'Rose', 'Williams', 'Female', NULL, 2, '2024-08-16', '2025-10-21 00:56:22', '2025-10-21 00:56:22'),
(25, '2024-0005', '$2y$12$MwZjPLzV9wGA1OUHI/XXLOFV1nv2B9mBMSCsmcL5l2/lfIgafm3rS', 'michael.brown@example.com', 'Michael', 'David', 'Brown', 'Male', NULL, 3, '2024-08-17', '2025-10-21 00:56:22', '2025-10-21 00:56:22');

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
(5, 'qwe', 'qwe', 'qwe', 'Male', 'qwe@gmail.com', 'wqe', 'qwe qwe', '$2y$12$7w8dRQSVMpb4gSJczJczs.iPNHUiKmbH28ZcALziFjg1GPmYQcKq.', '', 1, '2025-10-23 16:49:49', '2025-10-23 16:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_matrix`
--

CREATE TABLE `teacher_class_matrix` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `sections`
--
ALTER TABLE `sections`
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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teacher_class_matrix`
--
ALTER TABLE `teacher_class_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
