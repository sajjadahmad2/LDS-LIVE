-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2025 at 08:43 PM
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
-- Database: `admin_panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `destination_location` varchar(255) DEFAULT NULL,
  `destination_webhook` text DEFAULT NULL,
  `consent` text DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `daily_limit` int(11) DEFAULT NULL,
  `monthly_limit` int(11) DEFAULT NULL,
  `weightage` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `total_limit` int(11) NOT NULL DEFAULT 0,
  `cross_link` varchar(255) DEFAULT NULL,
  `npm_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_carrier_types`
--

CREATE TABLE `agent_carrier_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `carrier_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_states`
--

CREATE TABLE `agent_states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `state_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_users`
--

CREATE TABLE `agent_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` varchar(190) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `daily_limit` int(11) NOT NULL DEFAULT 0,
  `monthly_limit` int(11) NOT NULL DEFAULT 0,
  `total_limit` int(11) NOT NULL DEFAULT 0,
  `weightage` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_agents`
--

CREATE TABLE `campaign_agents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `weightage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_carrier_types`
--

CREATE TABLE `campaign_carrier_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `carrier_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_locations`
--

CREATE TABLE `company_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `location_id` varchar(255) NOT NULL,
  `company_id` varchar(255) NOT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `location_email` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `dnd` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `assigned_to` varchar(255) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `location_id` varchar(255) DEFAULT NULL,
  `contact_id` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `trusted_form_ping_url` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `trusted_form_cert_url` varchar(255) DEFAULT NULL,
  `your_gender` varchar(255) DEFAULT NULL,
  `social_security` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `spouses_first_name` varchar(255) DEFAULT NULL,
  `spouses_last_name` varchar(255) DEFAULT NULL,
  `spouse_gende` varchar(255) DEFAULT NULL,
  `spouse_date_of_birth` varchar(255) DEFAULT NULL,
  `do_you_want_to_enroll_spouse_as_well` varchar(255) DEFAULT NULL,
  `spouse_ssn` varchar(255) DEFAULT NULL,
  `tax_dependents_typically_children` varchar(255) DEFAULT NULL,
  `number_of_tax_dependants_typically_children` varchar(255) DEFAULT NULL,
  `wish_to_enroll_your_dependents` varchar(255) DEFAULT NULL,
  `tax_dependants_date_of_births` varchar(255) DEFAULT NULL,
  `disqualify_lead` varchar(255) DEFAULT NULL,
  `company_name_if_self_employed` varchar(255) DEFAULT NULL,
  `projected_annual_income` varchar(255) DEFAULT NULL,
  `employment_status` varchar(255) DEFAULT NULL,
  `application_informatio_my_signature` varchar(255) DEFAULT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `plan_carrier_name` varchar(255) DEFAULT NULL,
  `plan_id` varchar(255) DEFAULT NULL,
  `plan_type` varchar(255) DEFAULT NULL,
  `brochure_url` varchar(255) DEFAULT NULL,
  `my_signature` longtext DEFAULT NULL,
  `benefits_url` varchar(255) DEFAULT NULL,
  `selected_plan_image` varchar(255) DEFAULT NULL,
  `signature` longtext DEFAULT NULL,
  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('Not Sent','Sent') NOT NULL DEFAULT 'Not Sent',
  `contact_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_fields`
--

CREATE TABLE `custom_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cf_id` varchar(255) DEFAULT NULL,
  `cf_name` longtext DEFAULT NULL,
  `cf_key` longtext DEFAULT NULL,
  `dataType` varchar(255) DEFAULT NULL,
  `location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ghl_auths`
--

CREATE TABLE `ghl_auths` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` varchar(190) DEFAULT NULL,
  `user_type` varchar(30) DEFAULT 'Location',
  `expires_in` varchar(6) DEFAULT NULL,
  `company_id` varchar(190) DEFAULT NULL,
  `crm_user_id` varchar(190) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2014_10_12_100000_create_password_resets_table', 1),
(4, '2019_08_19_000000_create_failed_jobs_table', 1),
(5, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(6, '2024_11_05_195103_create_settings_table', 1),
(7, '2024_11_06_173417_create_ghl_auths_table', 1),
(8, '2024_11_08_213806_alter_location_id_to_users', 1),
(9, '2024_11_08_221224_alter_last_name_id_to_users', 1),
(10, '2024_12_11_214806_create_states_table', 1),
(11, '2024_12_11_214848_create_agents_table', 1),
(12, '2024_12_11_214912_create_agent_states_table', 1),
(13, '2024_12_11_214929_create_campaigns_table', 1),
(14, '2024_12_11_214955_create_campaign_agents_table', 1),
(15, '2024_12_16_181411_create_agent_carrier_types_table', 1),
(16, '2024_12_16_181515_create_campaign_carrier_types_table', 1),
(17, '2024_12_18_182300_create_company_locations_table', 1),
(18, '2024_12_18_225231_create_contacts_table', 1),
(19, '2024_12_18_225715_create_track_logs_table', 1),
(20, '2024_12_30_221707_add_status_column_to_agents', 1),
(21, '2024_12_31_162642_add_compaign_agent_weightage_in_compaign_agents_table', 1),
(22, '2025_01_01_211528_create_proccess_contacts_table', 1),
(23, '2025_01_01_211932_create_reserve_contacts_table', 1),
(25, '2025_01_03_223022_create_custom_fields_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proccess_contacts`
--

CREATE TABLE `proccess_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `dnd` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `assigned_to` varchar(255) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `location_id` varchar(255) DEFAULT NULL,
  `contact_id` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `trusted_form_ping_url` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `trusted_form_cert_url` varchar(255) DEFAULT NULL,
  `your_gender` varchar(255) DEFAULT NULL,
  `social_security` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `spouses_first_name` varchar(255) DEFAULT NULL,
  `spouses_last_name` varchar(255) DEFAULT NULL,
  `spouse_gende` varchar(255) DEFAULT NULL,
  `spouse_date_of_birth` varchar(255) DEFAULT NULL,
  `do_you_want_to_enroll_spouse_as_well` varchar(255) DEFAULT NULL,
  `spouse_ssn` varchar(255) DEFAULT NULL,
  `tax_dependents_typically_children` varchar(255) DEFAULT NULL,
  `number_of_tax_dependants_typically_children` varchar(255) DEFAULT NULL,
  `wish_to_enroll_your_dependents` varchar(255) DEFAULT NULL,
  `tax_dependants_date_of_births` varchar(255) DEFAULT NULL,
  `disqualify_lead` varchar(255) DEFAULT NULL,
  `company_name_if_self_employed` varchar(255) DEFAULT NULL,
  `projected_annual_income` varchar(255) DEFAULT NULL,
  `employment_status` varchar(255) DEFAULT NULL,
  `application_informatio_my_signature` varchar(255) DEFAULT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `plan_carrier_name` varchar(255) DEFAULT NULL,
  `plan_id` varchar(255) DEFAULT NULL,
  `plan_type` varchar(255) DEFAULT NULL,
  `brochure_url` varchar(255) DEFAULT NULL,
  `my_signature` longtext DEFAULT NULL,
  `benefits_url` varchar(255) DEFAULT NULL,
  `selected_plan_image` varchar(255) DEFAULT NULL,
  `signature` longtext DEFAULT NULL,
  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('In Compelete','Compelete') NOT NULL DEFAULT 'In Compelete',
  `contact_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reserve_contacts`
--

CREATE TABLE `reserve_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `dnd` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `assigned_to` varchar(255) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `location_id` varchar(255) DEFAULT NULL,
  `contact_id` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `trusted_form_ping_url` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `trusted_form_cert_url` varchar(255) DEFAULT NULL,
  `your_gender` varchar(255) DEFAULT NULL,
  `social_security` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `spouses_first_name` varchar(255) DEFAULT NULL,
  `spouses_last_name` varchar(255) DEFAULT NULL,
  `spouse_gende` varchar(255) DEFAULT NULL,
  `spouse_date_of_birth` varchar(255) DEFAULT NULL,
  `do_you_want_to_enroll_spouse_as_well` varchar(255) DEFAULT NULL,
  `spouse_ssn` varchar(255) DEFAULT NULL,
  `tax_dependents_typically_children` varchar(255) DEFAULT NULL,
  `number_of_tax_dependants_typically_children` varchar(255) DEFAULT NULL,
  `wish_to_enroll_your_dependents` varchar(255) DEFAULT NULL,
  `tax_dependants_date_of_births` varchar(255) DEFAULT NULL,
  `disqualify_lead` varchar(255) DEFAULT NULL,
  `company_name_if_self_employed` varchar(255) DEFAULT NULL,
  `projected_annual_income` varchar(255) DEFAULT NULL,
  `employment_status` varchar(255) DEFAULT NULL,
  `application_informatio_my_signature` varchar(255) DEFAULT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `plan_carrier_name` varchar(255) DEFAULT NULL,
  `plan_id` varchar(255) DEFAULT NULL,
  `plan_type` varchar(255) DEFAULT NULL,
  `brochure_url` varchar(255) DEFAULT NULL,
  `my_signature` longtext DEFAULT NULL,
  `benefits_url` varchar(255) DEFAULT NULL,
  `selected_plan_image` varchar(255) DEFAULT NULL,
  `signature` longtext DEFAULT NULL,
  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('Not Sent') NOT NULL DEFAULT 'Not Sent',
  `contact_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `user_id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 1, 'crm_client_id', '67575f166cdfa8bbbf13da1f-m4rb4bcg', '2025-01-03 16:12:23', '2025-01-03 16:12:23'),
(2, 1, 'crm_client_secret', '3d054c0e-f488-4d96-b9c8-897ecbbbea00', '2025-01-03 16:12:23', '2025-01-03 16:12:23');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `state` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `state`, `user_id`, `location_id`, `created_at`, `updated_at`) VALUES
(1, 'Alabama', NULL, NULL, NULL, NULL),
(2, 'Alaska', NULL, NULL, NULL, NULL),
(3, 'Arizona', NULL, NULL, NULL, NULL),
(4, 'Arkansas', NULL, NULL, NULL, NULL),
(5, 'California', NULL, NULL, NULL, NULL),
(6, 'Colorado', NULL, NULL, NULL, NULL),
(7, 'Connecticut', NULL, NULL, NULL, NULL),
(8, 'Delaware', NULL, NULL, NULL, NULL),
(9, 'Florida', NULL, NULL, NULL, NULL),
(10, 'Georgia', NULL, NULL, NULL, NULL),
(11, 'Hawaii', NULL, NULL, NULL, NULL),
(12, 'Idaho', NULL, NULL, NULL, NULL),
(13, 'Illinois', NULL, NULL, NULL, NULL),
(14, 'Indiana', NULL, NULL, NULL, NULL),
(15, 'Iowa', NULL, NULL, NULL, NULL),
(16, 'Kansas', NULL, NULL, NULL, NULL),
(17, 'Kentucky', NULL, NULL, NULL, NULL),
(18, 'Louisiana', NULL, NULL, NULL, NULL),
(19, 'Maine', NULL, NULL, NULL, NULL),
(20, 'Maryland', NULL, NULL, NULL, NULL),
(21, 'Massachusetts', NULL, NULL, NULL, NULL),
(22, 'Michigan', NULL, NULL, NULL, NULL),
(23, 'Minnesota', NULL, NULL, NULL, NULL),
(24, 'Mississippi', NULL, NULL, NULL, NULL),
(25, 'Missouri', NULL, NULL, NULL, NULL),
(26, 'Montana', NULL, NULL, NULL, NULL),
(27, 'Nebraska', NULL, NULL, NULL, NULL),
(28, 'Nevada', NULL, NULL, NULL, NULL),
(29, 'New Hampshire', NULL, NULL, NULL, NULL),
(30, 'New Jersey', NULL, NULL, NULL, NULL),
(31, 'New Mexico', NULL, NULL, NULL, NULL),
(32, 'New York', NULL, NULL, NULL, NULL),
(33, 'North Carolina', NULL, NULL, NULL, NULL),
(34, 'North Dakota', NULL, NULL, NULL, NULL),
(35, 'Ohio', NULL, NULL, NULL, NULL),
(36, 'Oklahoma', NULL, NULL, NULL, NULL),
(37, 'Oregon', NULL, NULL, NULL, NULL),
(38, 'Pennsylvania', NULL, NULL, NULL, NULL),
(39, 'Rhode Island', NULL, NULL, NULL, NULL),
(40, 'South Carolina', NULL, NULL, NULL, NULL),
(41, 'South Dakota', NULL, NULL, NULL, NULL),
(42, 'Tennessee', NULL, NULL, NULL, NULL),
(43, 'Texas', NULL, NULL, NULL, NULL),
(44, 'Utah', NULL, NULL, NULL, NULL),
(45, 'Vermont', NULL, NULL, NULL, NULL),
(46, 'Virginia', NULL, NULL, NULL, NULL),
(47, 'Washington', NULL, NULL, NULL, NULL),
(48, 'West Virginia', NULL, NULL, NULL, NULL),
(49, 'Wisconsin', NULL, NULL, NULL, NULL),
(50, 'Wyoming', NULL, NULL, NULL, NULL),
(51, 'District of Columbia', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `track_logs`
--

CREATE TABLE `track_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source_location` text DEFAULT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `sent_to` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `role` tinyint(4) NOT NULL DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `ghl_api_key` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location_id` varchar(255) DEFAULT NULL,
  `added_by` varchar(255) NOT NULL DEFAULT '1',
  `image` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1',
  `last_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `role`, `password`, `ghl_api_key`, `company_id`, `agent_id`, `remember_token`, `created_at`, `updated_at`, `location_id`, `added_by`, `image`, `status`, `last_name`) VALUES
(1, 'Super', 'superadmin@gmail.com', NULL, 0, '$2y$12$GLmDm50ZWYAlWdRLQqlbhOADi4pVsnUGRaWL/Is0Sck/YcRge486G', NULL, NULL, NULL, 'l2nMCXV7s8kqREmpZBQLJSlKOsnOUlkPTZbTMAsdC3ogkx0EAL6gmGLyHItm', '2025-01-03 16:08:18', '2025-01-03 16:08:18', NULL, '1', NULL, '1', 'Super');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agents_user_id_foreign` (`user_id`);

--
-- Indexes for table `agent_carrier_types`
--
ALTER TABLE `agent_carrier_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_carrier_types_agent_id_foreign` (`agent_id`);

--
-- Indexes for table `agent_states`
--
ALTER TABLE `agent_states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_states_agent_id_foreign` (`agent_id`),
  ADD KEY `agent_states_state_id_foreign` (`state_id`),
  ADD KEY `agent_states_user_id_foreign` (`user_id`);

--
-- Indexes for table `agent_users`
--
ALTER TABLE `agent_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_users_user_id_foreign` (`user_id`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaigns_user_id_foreign` (`user_id`);

--
-- Indexes for table `campaign_agents`
--
ALTER TABLE `campaign_agents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_agents_agent_id_foreign` (`agent_id`),
  ADD KEY `campaign_agents_campaign_id_foreign` (`campaign_id`),
  ADD KEY `campaign_agents_user_id_foreign` (`user_id`);

--
-- Indexes for table `campaign_carrier_types`
--
ALTER TABLE `campaign_carrier_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_carrier_types_campaign_id_foreign` (`campaign_id`);

--
-- Indexes for table `company_locations`
--
ALTER TABLE `company_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_locations_user_id_foreign` (`user_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contacts_campaign_id_foreign` (`campaign_id`),
  ADD KEY `contacts_agent_id_foreign` (`agent_id`),
  ADD KEY `contacts_user_id_foreign` (`user_id`);

--
-- Indexes for table `custom_fields`
--
ALTER TABLE `custom_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `ghl_auths`
--
ALTER TABLE `ghl_auths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ghl_auths_user_id_foreign` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `proccess_contacts`
--
ALTER TABLE `proccess_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proccess_contacts_campaign_id_foreign` (`campaign_id`),
  ADD KEY `proccess_contacts_agent_id_foreign` (`agent_id`),
  ADD KEY `proccess_contacts_user_id_foreign` (`user_id`);

--
-- Indexes for table `reserve_contacts`
--
ALTER TABLE `reserve_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reserve_contacts_campaign_id_foreign` (`campaign_id`),
  ADD KEY `reserve_contacts_agent_id_foreign` (`agent_id`),
  ADD KEY `reserve_contacts_user_id_foreign` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `settings_user_id_foreign` (`user_id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `states_user_id_foreign` (`user_id`);

--
-- Indexes for table `track_logs`
--
ALTER TABLE `track_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `track_logs_campaign_id_foreign` (`campaign_id`),
  ADD KEY `track_logs_sent_to_foreign` (`sent_to`);

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
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_carrier_types`
--
ALTER TABLE `agent_carrier_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_states`
--
ALTER TABLE `agent_states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_users`
--
ALTER TABLE `agent_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_agents`
--
ALTER TABLE `campaign_agents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_carrier_types`
--
ALTER TABLE `campaign_carrier_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_locations`
--
ALTER TABLE `company_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_fields`
--
ALTER TABLE `custom_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ghl_auths`
--
ALTER TABLE `ghl_auths`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proccess_contacts`
--
ALTER TABLE `proccess_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reserve_contacts`
--
ALTER TABLE `reserve_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `track_logs`
--
ALTER TABLE `track_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `agents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agent_carrier_types`
--
ALTER TABLE `agent_carrier_types`
  ADD CONSTRAINT `agent_carrier_types_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agent_states`
--
ALTER TABLE `agent_states`
  ADD CONSTRAINT `agent_states_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_states_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_states_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_agents`
--
ALTER TABLE `campaign_agents`
  ADD CONSTRAINT `campaign_agents_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_agents_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_agents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_carrier_types`
--
ALTER TABLE `campaign_carrier_types`
  ADD CONSTRAINT `campaign_carrier_types_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `company_locations`
--
ALTER TABLE `company_locations`
  ADD CONSTRAINT `company_locations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ghl_auths`
--
ALTER TABLE `ghl_auths`
  ADD CONSTRAINT `ghl_auths_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proccess_contacts`
--
ALTER TABLE `proccess_contacts`
  ADD CONSTRAINT `proccess_contacts_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proccess_contacts_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proccess_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reserve_contacts`
--
ALTER TABLE `reserve_contacts`
  ADD CONSTRAINT `reserve_contacts_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserve_contacts_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserve_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `track_logs`
--
ALTER TABLE `track_logs`
  ADD CONSTRAINT `track_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `track_logs_sent_to_foreign` FOREIGN KEY (`sent_to`) REFERENCES `agents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
