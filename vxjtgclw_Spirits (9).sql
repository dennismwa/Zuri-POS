-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 08, 2025 at 01:50 PM
-- Server version: 8.0.42
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vxjtgclw_Spirits`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`vxjtgclw`@`localhost` PROCEDURE `sp_cleanup_old_data` ()   BEGIN
    -- Delete old login attempts (older than 30 days)
    DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Delete old sessions (older than 90 days)
    DELETE FROM sessions WHERE logout_time < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Mark old notifications as read (older than 60 days)
    UPDATE notifications SET is_read = 1 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY) AND is_read = 0;
    
    -- Delete sent emails from queue (older than 7 days)
    DELETE FROM email_queue 
    WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
END$$

CREATE DEFINER=`vxjtgclw`@`localhost` PROCEDURE `sp_get_product_sales_stats` (IN `p_product_id` INT, IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    SELECT 
        p.id,
        p.name,
        COUNT(si.id) as times_sold,
        SUM(si.quantity) as total_quantity,
        SUM(si.subtotal) as total_revenue,
        AVG(si.unit_price) as avg_price,
        MIN(s.sale_date) as first_sale,
        MAX(s.sale_date) as last_sale
    FROM products p
    LEFT JOIN sale_items si ON p.id = si.product_id
    LEFT JOIN sales s ON si.sale_id = s.id
    WHERE p.id = p_product_id
    AND (p_start_date IS NULL OR DATE(s.sale_date) >= p_start_date)
    AND (p_end_date IS NULL OR DATE(s.sale_date) <= p_end_date)
    GROUP BY p.id;
END$$

CREATE DEFINER=`vxjtgclw`@`localhost` PROCEDURE `sp_get_user_performance` (IN `p_user_id` INT, IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    SELECT 
        u.id,
        u.name,
        COUNT(s.id) as total_sales,
        SUM(s.total_amount) as total_revenue,
        AVG(s.total_amount) as avg_sale_value,
        SUM(s.discount_amount) as total_discounts_given,
        MIN(s.sale_date) as first_sale,
        MAX(s.sale_date) as last_sale
    FROM users u
    LEFT JOIN sales s ON u.id = s.user_id
    WHERE u.id = p_user_id
    AND (p_start_date IS NULL OR DATE(s.sale_date) >= p_start_date)
    AND (p_end_date IS NULL OR DATE(s.sale_date) <= p_end_date)
    GROUP BY u.id;
END$$

CREATE DEFINER=`vxjtgclw`@`localhost` PROCEDURE `sp_monthly_report` (IN `report_month` DATE)   BEGIN
    DECLARE month_start DATE;
    DECLARE month_end DATE;
    
    SET month_start = DATE_FORMAT(report_month, '%Y-%m-01');
    SET month_end = LAST_DAY(report_month);
    
    -- Sales Summary
    SELECT 
        'Sales Summary' as report_section,
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_transaction,
        MAX(total_amount) as highest_sale,
        MIN(total_amount) as lowest_sale
    FROM sales
    WHERE sale_date BETWEEN month_start AND month_end;
    
    -- Top Products
    SELECT 
        'Top 10 Products' as report_section,
        p.name,
        SUM(si.quantity) as units_sold,
        SUM(si.subtotal) as revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.sale_date BETWEEN month_start AND month_end
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10;
    
    -- Employee Performance
    SELECT 
        'Employee Performance' as report_section,
        u.name as employee,
        COUNT(s.id) as sales_count,
        SUM(s.total_amount) as total_sales
    FROM users u
    LEFT JOIN sales s ON u.id = s.user_id AND s.sale_date BETWEEN month_start AND month_end
    GROUP BY u.id
    ORDER BY total_sales DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(1, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 08:13:33'),
(2, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 08:18:38'),
(3, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 09:26:20'),
(4, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 09:30:28'),
(5, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 09:40:41'),
(6, 1, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-7F0A8A with total 1800', '41.209.14.78', NULL, NULL, '2025-10-07 10:33:44'),
(7, 1, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-B0487D with total 380', '41.209.14.78', NULL, NULL, '2025-10-07 10:34:35'),
(8, 2, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 10:47:46'),
(9, 2, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-51D8F6 with total 2300', '41.209.14.78', NULL, NULL, '2025-10-07 10:48:05'),
(10, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 11:17:54'),
(11, 1, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-4C7E8D with total 2300', '41.209.14.78', NULL, NULL, '2025-10-07 11:19:32'),
(12, 1, 'ALERT_UPDATED', 'Updated alert level for category ID: 0 to 5', '41.209.14.78', NULL, NULL, '2025-10-07 11:21:28'),
(13, 1, 'SALE_COMPLETED', 'Completed sale ZWS-20251007-7E7391 with total 2300', '41.209.14.78', NULL, NULL, '2025-10-07 11:26:16'),
(14, 1, 'EXPENSE_ADDED', 'Added expense: Transport - KSh 1,000.00', '41.209.14.78', NULL, NULL, '2025-10-07 12:16:40'),
(15, 1, 'Login', 'User logged in successfully', '41.209.14.78', NULL, NULL, '2025-10-07 13:03:26'),
(16, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 14:25:09'),
(17, 1, 'SALE_COMPLETED', 'Sale ZWS-20251007-030C0D completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 14:47:07'),
(18, 1, 'SALE_COMPLETED', 'Sale ZWS-20251007-162357 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 14:47:57'),
(19, 1, 'SALE_COMPLETED', 'Sale ZWS-20251007-69C4A8 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 14:48:19'),
(20, 1, 'SALE_COMPLETED', 'Sale ZWS-20251007-29D876 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 14:59:13'),
(21, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-07 15:31:57'),
(22, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 16:05:15'),
(23, 2, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-07 16:05:33'),
(24, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 06:59:03'),
(25, 1, 'USER_UPDATED', 'Updated user: Seller 1 (ID: 2)', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:00:05'),
(26, 2, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:01:24'),
(27, 2, 'SALE_COMPLETED', 'Sale ZWS-20251008-06DA53 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:19:47'),
(28, 2, 'SALE_COMPLETED', 'Sale ZWS-20251008-90CC03 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:20:35'),
(29, 2, 'LOGOUT', 'User logged out', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:28:54'),
(30, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:28:59'),
(31, 1, 'LOGOUT', 'User logged out', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:35:47'),
(32, 2, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:35:51'),
(33, 2, 'LOGOUT', 'User logged out', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:38:00'),
(34, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:38:04'),
(35, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:44:53'),
(36, 1, 'LOGOUT', 'User logged out', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:52:30'),
(37, 2, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 07:52:34'),
(38, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 08:14:09'),
(39, 1, 'LOGOUT', 'User logged out', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 08:14:18'),
(40, 2, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 08:14:24'),
(41, 1, 'SALE_COMPLETED', 'Sale ZWS-20251008-F48590 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 08:23:27'),
(42, 1, 'LOGOUT', 'User logged out', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 08:30:07'),
(43, 2, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 08:30:11'),
(44, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:54:23'),
(45, 1, 'SALE_COMPLETED', 'Sale ZWS-20251008-BE6D59 completed', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:55:33'),
(46, 1, 'PRODUCT_DELETED', 'Deleted product ID: 90', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:56:16'),
(47, 1, 'PRODUCT_DELETED', 'Deleted product ID: 78', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:56:30'),
(48, 1, 'PRODUCT_DELETED', 'Deleted product ID: 89', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:56:37'),
(49, 1, 'PRODUCT_DELETED', 'Deleted product ID: 64', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:56:49'),
(50, 1, 'STOCK_ADJUSTED', 'Adjusted stock for product ID: 68 - Type: out, Qty: 11', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:57:31'),
(51, 1, 'STOCK_ADJUSTED', 'Adjusted stock for product ID: 41 - Type: out, Qty: 45', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:58:24'),
(52, 1, 'STOCK_ADJUSTED', 'Adjusted stock for product ID: 27 - Type: in, Qty: 16', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', NULL, '2025-10-08 08:58:37'),
(53, 1, 'Login', 'User logged in successfully', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 09:32:26'),
(54, 1, 'BRANCH_CREATED', 'Created branch: Main Branch', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '2025-10-08 10:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `user_id`, `table_name`, `record_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'products', 79, 'UPDATE', '{\"name\": \"Amstel Lager 330ml\", \"status\": \"active\", \"cost_price\": 130.00, \"selling_price\": 190.00, \"stock_quantity\": 158}', '{\"name\": \"Amstel Lager 330ml\", \"status\": \"active\", \"cost_price\": 130.00, \"selling_price\": 190.00, \"stock_quantity\": 157}', NULL, NULL, '2025-10-07 14:47:07'),
(2, 1, 'products', 12, 'UPDATE', '{\"name\": \"Absolute Vodka 750ml\", \"status\": \"active\", \"cost_price\": 1800.00, \"selling_price\": 2300.00, \"stock_quantity\": 43}', '{\"name\": \"Absolute Vodka 750ml\", \"status\": \"active\", \"cost_price\": 1800.00, \"selling_price\": 2300.00, \"stock_quantity\": 42}', NULL, NULL, '2025-10-07 14:47:57'),
(3, 1, 'products', 12, 'UPDATE', '{\"name\": \"Absolute Vodka 750ml\", \"status\": \"active\", \"cost_price\": 1800.00, \"selling_price\": 2300.00, \"stock_quantity\": 42}', '{\"name\": \"Absolute Vodka 750ml\", \"status\": \"active\", \"cost_price\": 1800.00, \"selling_price\": 2300.00, \"stock_quantity\": 41}', NULL, NULL, '2025-10-07 14:48:19'),
(4, 1, 'products', 52, 'UPDATE', '{\"name\": \"Amarula Cream 750ml\", \"status\": \"active\", \"cost_price\": 1400.00, \"selling_price\": 1800.00, \"stock_quantity\": 49}', '{\"name\": \"Amarula Cream 750ml\", \"status\": \"active\", \"cost_price\": 1400.00, \"selling_price\": 1800.00, \"stock_quantity\": 48}', NULL, NULL, '2025-10-07 14:48:19'),
(5, 1, 'products', 52, 'UPDATE', '{\"name\": \"Amarula Cream 750ml\", \"status\": \"active\", \"cost_price\": 1400.00, \"selling_price\": 1800.00, \"stock_quantity\": 48}', '{\"name\": \"Amarula Cream 750ml\", \"status\": \"active\", \"cost_price\": 1400.00, \"selling_price\": 1800.00, \"stock_quantity\": 47}', NULL, NULL, '2025-10-07 14:59:13'),
(6, 1, 'products', 79, 'UPDATE', '{\"name\": \"Amstel Lager 330ml\", \"status\": \"active\", \"cost_price\": 130.00, \"selling_price\": 190.00, \"stock_quantity\": 157}', '{\"name\": \"Amstel Lager 330ml\", \"status\": \"active\", \"cost_price\": 130.00, \"selling_price\": 190.00, \"stock_quantity\": 156}', NULL, NULL, '2025-10-07 14:59:13'),
(7, 1, 'products', 11, 'UPDATE', '{\"name\": \"Smirnoff Red Label 750ml\", \"status\": \"active\", \"cost_price\": 1400.00, \"selling_price\": 1800.00, \"stock_quantity\": 65}', '{\"name\": \"Smirnoff Red Label 750ml\", \"status\": \"active\", \"cost_price\": 1400.00, \"selling_price\": 1800.00, \"stock_quantity\": 63}', NULL, NULL, '2025-10-08 07:19:47'),
(8, 1, 'products', 48, 'UPDATE', '{\"name\": \"Four Cousins Sweet Red 750ml\", \"status\": \"active\", \"cost_price\": 900.00, \"selling_price\": 1200.00, \"stock_quantity\": 80}', '{\"name\": \"Four Cousins Sweet Red 750ml\", \"status\": \"active\", \"cost_price\": 900.00, \"selling_price\": 1200.00, \"stock_quantity\": 79}', NULL, NULL, '2025-10-08 07:20:35'),
(9, 1, 'products', 29, 'UPDATE', '{\"name\": \"Captain Morgan Spiced Rum 750ml\", \"status\": \"active\", \"cost_price\": 1600.00, \"selling_price\": 2000.00, \"stock_quantity\": 50}', '{\"name\": \"Captain Morgan Spiced Rum 750ml\", \"status\": \"active\", \"cost_price\": 1600.00, \"selling_price\": 2000.00, \"stock_quantity\": 48}', NULL, NULL, '2025-10-08 08:23:27'),
(10, 1, 'products', 91, 'UPDATE', '{\"name\": \"Smirnoff Ice Double Black 300ml\", \"status\": \"active\", \"cost_price\": 180.00, \"selling_price\": 250.00, \"stock_quantity\": 150}', '{\"name\": \"Smirnoff Ice Double Black 300ml\", \"status\": \"active\", \"cost_price\": 180.00, \"selling_price\": 250.00, \"stock_quantity\": 149}', NULL, NULL, '2025-10-08 08:55:33'),
(11, 1, 'products', 90, 'UPDATE', '{\"name\": \"Aperol 700ml\", \"status\": \"active\", \"cost_price\": 1800.00, \"selling_price\": 2300.00, \"stock_quantity\": 30}', '{\"name\": \"Aperol 700ml\", \"status\": \"inactive\", \"cost_price\": 1800.00, \"selling_price\": 2300.00, \"stock_quantity\": 30}', NULL, NULL, '2025-10-08 08:56:16'),
(12, 1, 'products', 78, 'UPDATE', '{\"name\": \"Budweiser 330ml\", \"status\": \"active\", \"cost_price\": 150.00, \"selling_price\": 220.00, \"stock_quantity\": 140}', '{\"name\": \"Budweiser 330ml\", \"status\": \"inactive\", \"cost_price\": 150.00, \"selling_price\": 220.00, \"stock_quantity\": 140}', NULL, NULL, '2025-10-08 08:56:30'),
(13, 1, 'products', 89, 'UPDATE', '{\"name\": \"Campari 700ml\", \"status\": \"active\", \"cost_price\": 2100.00, \"selling_price\": 2700.00, \"stock_quantity\": 26}', '{\"name\": \"Campari 700ml\", \"status\": \"inactive\", \"cost_price\": 2100.00, \"selling_price\": 2700.00, \"stock_quantity\": 26}', NULL, NULL, '2025-10-08 08:56:37'),
(14, 1, 'products', 64, 'UPDATE', '{\"name\": \"Casillero del Diablo Sauvignon 750ml\", \"status\": \"active\", \"cost_price\": 1300.00, \"selling_price\": 1700.00, \"stock_quantity\": 42}', '{\"name\": \"Casillero del Diablo Sauvignon 750ml\", \"status\": \"inactive\", \"cost_price\": 1300.00, \"selling_price\": 1700.00, \"stock_quantity\": 42}', NULL, NULL, '2025-10-08 08:56:49'),
(15, 1, 'products', 68, 'UPDATE', '{\"name\": \"Veuve Clicquot Yellow Label 750ml\", \"status\": \"active\", \"cost_price\": 7000.00, \"selling_price\": 9000.00, \"stock_quantity\": 12}', '{\"name\": \"Veuve Clicquot Yellow Label 750ml\", \"status\": \"active\", \"cost_price\": 7000.00, \"selling_price\": 9000.00, \"stock_quantity\": 1}', NULL, NULL, '2025-10-08 08:57:31'),
(16, 1, 'products', 41, 'UPDATE', '{\"name\": \"Klipdrift Brandy 750ml\", \"status\": \"active\", \"cost_price\": 1200.00, \"selling_price\": 1600.00, \"stock_quantity\": 45}', '{\"name\": \"Klipdrift Brandy 750ml\", \"status\": \"active\", \"cost_price\": 1200.00, \"selling_price\": 1600.00, \"stock_quantity\": 0}', NULL, NULL, '2025-10-08 08:58:24'),
(17, 1, 'products', 27, 'UPDATE', '{\"name\": \"Gordons Pink Gin 750ml\", \"status\": \"active\", \"cost_price\": 1500.00, \"selling_price\": 1900.00, \"stock_quantity\": 45}', '{\"name\": \"Gordons Pink Gin 750ml\", \"status\": \"active\", \"cost_price\": 1500.00, \"selling_price\": 1900.00, \"stock_quantity\": 61}', NULL, NULL, '2025-10-08 08:58:37');

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int NOT NULL,
  `backup_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint NOT NULL,
  `backup_type` enum('manual','automatic','scheduled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `created_by` int DEFAULT NULL,
  `status` enum('completed','failed','in_progress') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `address`, `city`, `phone`, `email`, `manager_id`, `status`, `opening_time`, `closing_time`, `latitude`, `longitude`, `tax_number`, `created_at`, `updated_at`) VALUES
(1, 'Main Branch', 'MAIN', 'Head Office', 'Nairobi', '+254700000000', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, '2025-10-08 10:02:46', '2025-10-08 10:02:46'),
(2, 'Main Branch', '001', 'Ruiru', 'Ruiru', '+254758256440', 'mwangidennis546@gmail.com', NULL, 'active', '18:00:00', '23:00:00', NULL, NULL, NULL, '2025-10-08 10:32:57', '2025-10-08 10:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `branch_inventory`
--

CREATE TABLE `branch_inventory` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL,
  `product_id` int NOT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '10',
  `last_restock_date` datetime DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branch_inventory`
--

INSERT INTO `branch_inventory` (`id`, `branch_id`, `product_id`, `stock_quantity`, `reorder_level`, `last_restock_date`, `last_updated`) VALUES
(1, 1, 41, 0, 15, NULL, '2025-10-08 10:02:47'),
(2, 1, 68, 1, 4, NULL, '2025-10-08 10:02:47'),
(3, 1, 28, 12, 5, NULL, '2025-10-08 10:02:47'),
(4, 1, 15, 15, 5, NULL, '2025-10-08 10:02:47'),
(5, 1, 45, 15, 5, NULL, '2025-10-08 10:02:47'),
(6, 1, 67, 15, 5, NULL, '2025-10-08 10:02:47'),
(7, 1, 17, 18, 6, NULL, '2025-10-08 10:02:47'),
(8, 1, 38, 18, 6, NULL, '2025-10-08 10:02:47'),
(9, 1, 47, 18, 6, NULL, '2025-10-08 10:02:47'),
(10, 1, 6, 20, 5, NULL, '2025-10-08 10:02:47'),
(11, 1, 40, 20, 6, NULL, '2025-10-08 10:02:47'),
(12, 1, 13, 20, 8, NULL, '2025-10-08 10:02:47'),
(13, 1, 25, 22, 8, NULL, '2025-10-08 10:02:47'),
(14, 1, 39, 22, 8, NULL, '2025-10-08 10:02:47'),
(15, 1, 5, 25, 8, NULL, '2025-10-08 10:02:47'),
(16, 1, 37, 25, 8, NULL, '2025-10-08 10:02:47'),
(17, 1, 85, 25, 8, NULL, '2025-10-08 10:02:47'),
(18, 1, 89, 26, 8, NULL, '2025-10-08 10:02:47'),
(19, 1, 34, 28, 8, NULL, '2025-10-08 10:02:47'),
(20, 1, 46, 28, 8, NULL, '2025-10-08 10:02:47'),
(21, 1, 88, 28, 8, NULL, '2025-10-08 10:02:47'),
(22, 1, 18, 28, 10, NULL, '2025-10-08 10:02:47'),
(23, 1, 4, 30, 8, NULL, '2025-10-08 10:02:47'),
(24, 1, 23, 30, 10, NULL, '2025-10-08 10:02:47'),
(25, 1, 33, 30, 10, NULL, '2025-10-08 10:02:47'),
(26, 1, 43, 30, 10, NULL, '2025-10-08 10:02:47'),
(27, 1, 69, 30, 10, NULL, '2025-10-08 10:02:47'),
(28, 1, 86, 30, 10, NULL, '2025-10-08 10:02:47'),
(29, 1, 90, 30, 10, NULL, '2025-10-08 10:02:47'),
(30, 1, 36, 32, 10, NULL, '2025-10-08 10:02:47'),
(31, 1, 44, 32, 10, NULL, '2025-10-08 10:02:47'),
(32, 1, 87, 32, 10, NULL, '2025-10-08 10:02:47'),
(33, 1, 3, 35, 10, NULL, '2025-10-08 10:02:47'),
(34, 1, 22, 35, 10, NULL, '2025-10-08 10:02:47'),
(35, 1, 31, 35, 10, NULL, '2025-10-08 10:02:47'),
(36, 1, 70, 35, 10, NULL, '2025-10-08 10:02:47'),
(37, 1, 84, 35, 10, NULL, '2025-10-08 10:02:47'),
(38, 1, 19, 35, 12, NULL, '2025-10-08 10:02:47'),
(39, 1, 9, 38, 10, NULL, '2025-10-08 10:02:47'),
(40, 1, 35, 39, 12, NULL, '2025-10-08 10:02:47'),
(41, 1, 8, 40, 12, NULL, '2025-10-08 10:02:47'),
(42, 1, 16, 40, 12, NULL, '2025-10-08 10:02:47'),
(43, 1, 24, 40, 12, NULL, '2025-10-08 10:02:47'),
(44, 1, 42, 40, 12, NULL, '2025-10-08 10:02:47'),
(45, 1, 66, 40, 12, NULL, '2025-10-08 10:02:47'),
(46, 1, 83, 40, 12, NULL, '2025-10-08 10:02:47'),
(47, 1, 12, 41, 15, NULL, '2025-10-08 10:02:47'),
(48, 1, 64, 42, 12, NULL, '2025-10-08 10:02:47'),
(49, 1, 1, 45, 10, NULL, '2025-10-08 10:02:47'),
(50, 1, 55, 45, 12, NULL, '2025-10-08 10:02:47'),
(51, 1, 30, 45, 15, NULL, '2025-10-08 10:02:47'),
(52, 1, 52, 47, 15, NULL, '2025-10-08 10:02:47'),
(53, 1, 29, 48, 15, NULL, '2025-10-08 10:02:47'),
(54, 1, 56, 48, 15, NULL, '2025-10-08 10:02:47'),
(55, 1, 63, 48, 15, NULL, '2025-10-08 10:02:47'),
(56, 1, 7, 50, 15, NULL, '2025-10-08 10:02:47'),
(57, 1, 14, 50, 15, NULL, '2025-10-08 10:02:47'),
(58, 1, 62, 50, 15, NULL, '2025-10-08 10:02:47'),
(59, 1, 57, 52, 15, NULL, '2025-10-08 10:02:47'),
(60, 1, 10, 55, 15, NULL, '2025-10-08 10:02:47'),
(61, 1, 21, 55, 15, NULL, '2025-10-08 10:02:47'),
(62, 1, 32, 55, 15, NULL, '2025-10-08 10:02:47'),
(63, 1, 54, 55, 15, NULL, '2025-10-08 10:02:47'),
(64, 1, 59, 55, 15, NULL, '2025-10-08 10:02:47'),
(65, 1, 2, 60, 15, NULL, '2025-10-08 10:02:47'),
(66, 1, 26, 60, 18, NULL, '2025-10-08 10:02:47'),
(67, 1, 49, 60, 18, NULL, '2025-10-08 10:02:47'),
(68, 1, 61, 60, 18, NULL, '2025-10-08 10:02:47'),
(69, 1, 27, 61, 12, NULL, '2025-10-08 10:02:47'),
(70, 1, 65, 62, 18, NULL, '2025-10-08 10:02:47'),
(71, 1, 11, 63, 20, NULL, '2025-10-08 10:02:47'),
(72, 1, 53, 65, 18, NULL, '2025-10-08 10:02:47'),
(73, 1, 50, 70, 20, NULL, '2025-10-08 10:02:47'),
(74, 1, 58, 75, 20, NULL, '2025-10-08 10:02:47'),
(75, 1, 48, 79, 20, NULL, '2025-10-08 10:02:47'),
(76, 1, 60, 85, 25, NULL, '2025-10-08 10:02:47'),
(77, 1, 51, 90, 25, NULL, '2025-10-08 10:02:47'),
(78, 1, 95, 100, 30, NULL, '2025-10-08 10:02:47'),
(79, 1, 77, 120, 35, NULL, '2025-10-08 10:02:47'),
(80, 1, 20, 120, 40, NULL, '2025-10-08 10:02:47'),
(81, 1, 94, 140, 40, NULL, '2025-10-08 10:02:47'),
(82, 1, 78, 140, 40, NULL, '2025-10-08 10:02:47'),
(83, 1, 91, 149, 40, NULL, '2025-10-08 10:02:47'),
(84, 1, 75, 150, 40, NULL, '2025-10-08 10:02:47'),
(85, 1, 82, 150, 40, NULL, '2025-10-08 10:02:47'),
(86, 1, 79, 156, 45, NULL, '2025-10-08 10:02:47'),
(87, 1, 93, 160, 45, NULL, '2025-10-08 10:02:47'),
(88, 1, 72, 180, 50, NULL, '2025-10-08 10:02:47'),
(89, 1, 76, 180, 50, NULL, '2025-10-08 10:02:47'),
(90, 1, 92, 180, 50, NULL, '2025-10-08 10:02:47'),
(91, 1, 100, 180, 50, NULL, '2025-10-08 10:02:47'),
(92, 1, 71, 200, 50, NULL, '2025-10-08 10:02:47'),
(93, 1, 81, 200, 50, NULL, '2025-10-08 10:02:47'),
(94, 1, 98, 200, 60, NULL, '2025-10-08 10:02:47'),
(95, 1, 73, 220, 60, NULL, '2025-10-08 10:02:47'),
(96, 1, 74, 250, 70, NULL, '2025-10-08 10:02:47'),
(97, 1, 99, 250, 80, NULL, '2025-10-08 10:02:47'),
(98, 1, 80, 280, 80, NULL, '2025-10-08 10:02:47'),
(99, 1, 97, 280, 100, NULL, '2025-10-08 10:02:47'),
(100, 1, 96, 300, 100, NULL, '2025-10-08 10:02:47'),
(128, 2, 1, 0, 10, NULL, '2025-10-08 10:32:57'),
(129, 2, 2, 0, 15, NULL, '2025-10-08 10:32:57'),
(130, 2, 3, 0, 10, NULL, '2025-10-08 10:32:57'),
(131, 2, 4, 0, 8, NULL, '2025-10-08 10:32:57'),
(132, 2, 5, 0, 8, NULL, '2025-10-08 10:32:57'),
(133, 2, 6, 0, 5, NULL, '2025-10-08 10:32:57'),
(134, 2, 7, 0, 15, NULL, '2025-10-08 10:32:57'),
(135, 2, 8, 0, 12, NULL, '2025-10-08 10:32:57'),
(136, 2, 9, 0, 10, NULL, '2025-10-08 10:32:57'),
(137, 2, 10, 0, 15, NULL, '2025-10-08 10:32:57'),
(138, 2, 11, 0, 20, NULL, '2025-10-08 10:32:57'),
(139, 2, 12, 0, 15, NULL, '2025-10-08 10:32:57'),
(140, 2, 13, 0, 8, NULL, '2025-10-08 10:32:57'),
(141, 2, 14, 0, 15, NULL, '2025-10-08 10:32:57'),
(142, 2, 15, 0, 5, NULL, '2025-10-08 10:32:57'),
(143, 2, 16, 0, 12, NULL, '2025-10-08 10:32:57'),
(144, 2, 17, 0, 6, NULL, '2025-10-08 10:32:57'),
(145, 2, 18, 0, 10, NULL, '2025-10-08 10:32:57'),
(146, 2, 19, 0, 12, NULL, '2025-10-08 10:32:57'),
(147, 2, 20, 0, 40, NULL, '2025-10-08 10:32:57'),
(148, 2, 21, 0, 15, NULL, '2025-10-08 10:32:57'),
(149, 2, 22, 0, 10, NULL, '2025-10-08 10:32:57'),
(150, 2, 23, 0, 10, NULL, '2025-10-08 10:32:57'),
(151, 2, 24, 0, 12, NULL, '2025-10-08 10:32:57'),
(152, 2, 25, 0, 8, NULL, '2025-10-08 10:32:57'),
(153, 2, 26, 0, 18, NULL, '2025-10-08 10:32:57'),
(154, 2, 27, 0, 12, NULL, '2025-10-08 10:32:57'),
(155, 2, 28, 0, 5, NULL, '2025-10-08 10:32:57'),
(156, 2, 29, 0, 15, NULL, '2025-10-08 10:32:57'),
(157, 2, 30, 0, 15, NULL, '2025-10-08 10:32:57'),
(158, 2, 31, 0, 10, NULL, '2025-10-08 10:32:57'),
(159, 2, 32, 0, 15, NULL, '2025-10-08 10:32:57'),
(160, 2, 33, 0, 10, NULL, '2025-10-08 10:32:57'),
(161, 2, 34, 0, 8, NULL, '2025-10-08 10:32:57'),
(162, 2, 35, 0, 12, NULL, '2025-10-08 10:32:57'),
(163, 2, 36, 0, 10, NULL, '2025-10-08 10:32:57'),
(164, 2, 37, 0, 8, NULL, '2025-10-08 10:32:57'),
(165, 2, 38, 0, 6, NULL, '2025-10-08 10:32:57'),
(166, 2, 39, 0, 8, NULL, '2025-10-08 10:32:57'),
(167, 2, 40, 0, 6, NULL, '2025-10-08 10:32:57'),
(168, 2, 41, 0, 15, NULL, '2025-10-08 10:32:57'),
(169, 2, 42, 0, 12, NULL, '2025-10-08 10:32:57'),
(170, 2, 43, 0, 10, NULL, '2025-10-08 10:32:57'),
(171, 2, 44, 0, 10, NULL, '2025-10-08 10:32:57'),
(172, 2, 45, 0, 5, NULL, '2025-10-08 10:32:57'),
(173, 2, 46, 0, 8, NULL, '2025-10-08 10:32:57'),
(174, 2, 47, 0, 6, NULL, '2025-10-08 10:32:57'),
(175, 2, 48, 0, 20, NULL, '2025-10-08 10:32:57'),
(176, 2, 49, 0, 18, NULL, '2025-10-08 10:32:57'),
(177, 2, 50, 0, 20, NULL, '2025-10-08 10:32:57'),
(178, 2, 51, 0, 25, NULL, '2025-10-08 10:32:57'),
(179, 2, 52, 0, 15, NULL, '2025-10-08 10:32:57'),
(180, 2, 53, 0, 18, NULL, '2025-10-08 10:32:57'),
(181, 2, 54, 0, 15, NULL, '2025-10-08 10:32:57'),
(182, 2, 55, 0, 12, NULL, '2025-10-08 10:32:57'),
(183, 2, 56, 0, 15, NULL, '2025-10-08 10:32:57'),
(184, 2, 57, 0, 15, NULL, '2025-10-08 10:32:57'),
(185, 2, 58, 0, 20, NULL, '2025-10-08 10:32:57'),
(186, 2, 59, 0, 15, NULL, '2025-10-08 10:32:57'),
(187, 2, 60, 0, 25, NULL, '2025-10-08 10:32:57'),
(188, 2, 61, 0, 18, NULL, '2025-10-08 10:32:57'),
(189, 2, 62, 0, 15, NULL, '2025-10-08 10:32:57'),
(190, 2, 63, 0, 15, NULL, '2025-10-08 10:32:57'),
(191, 2, 65, 0, 18, NULL, '2025-10-08 10:32:57'),
(192, 2, 66, 0, 12, NULL, '2025-10-08 10:32:57'),
(193, 2, 67, 0, 5, NULL, '2025-10-08 10:32:57'),
(194, 2, 68, 0, 4, NULL, '2025-10-08 10:32:57'),
(195, 2, 69, 0, 10, NULL, '2025-10-08 10:32:57'),
(196, 2, 70, 0, 10, NULL, '2025-10-08 10:32:57'),
(197, 2, 71, 0, 50, NULL, '2025-10-08 10:32:57'),
(198, 2, 72, 0, 50, NULL, '2025-10-08 10:32:57'),
(199, 2, 73, 0, 60, NULL, '2025-10-08 10:32:57'),
(200, 2, 74, 0, 70, NULL, '2025-10-08 10:32:57'),
(201, 2, 75, 0, 40, NULL, '2025-10-08 10:32:57'),
(202, 2, 76, 0, 50, NULL, '2025-10-08 10:32:57'),
(203, 2, 77, 0, 35, NULL, '2025-10-08 10:32:57'),
(204, 2, 79, 0, 45, NULL, '2025-10-08 10:32:57'),
(205, 2, 80, 0, 80, NULL, '2025-10-08 10:32:57'),
(206, 2, 81, 0, 50, NULL, '2025-10-08 10:32:57'),
(207, 2, 82, 0, 40, NULL, '2025-10-08 10:32:57'),
(208, 2, 83, 0, 12, NULL, '2025-10-08 10:32:57'),
(209, 2, 84, 0, 10, NULL, '2025-10-08 10:32:57'),
(210, 2, 85, 0, 8, NULL, '2025-10-08 10:32:57'),
(211, 2, 86, 0, 10, NULL, '2025-10-08 10:32:57'),
(212, 2, 87, 0, 10, NULL, '2025-10-08 10:32:57'),
(213, 2, 88, 0, 8, NULL, '2025-10-08 10:32:57'),
(214, 2, 91, 0, 40, NULL, '2025-10-08 10:32:57'),
(215, 2, 92, 0, 50, NULL, '2025-10-08 10:32:57'),
(216, 2, 93, 0, 45, NULL, '2025-10-08 10:32:57'),
(217, 2, 94, 0, 40, NULL, '2025-10-08 10:32:57'),
(218, 2, 95, 0, 30, NULL, '2025-10-08 10:32:57'),
(219, 2, 96, 0, 100, NULL, '2025-10-08 10:32:57'),
(220, 2, 97, 0, 100, NULL, '2025-10-08 10:32:57'),
(221, 2, 98, 0, 60, NULL, '2025-10-08 10:32:57'),
(222, 2, 99, 0, 80, NULL, '2025-10-08 10:32:57'),
(223, 2, 100, 0, 50, NULL, '2025-10-08 10:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `branch_metrics`
--

CREATE TABLE `branch_metrics` (
  `id` int NOT NULL,
  `branch_id` int NOT NULL,
  `metric_date` date NOT NULL,
  `sales_count` int DEFAULT '0',
  `sales_revenue` decimal(15,2) DEFAULT '0.00',
  `expenses_total` decimal(15,2) DEFAULT '0.00',
  `profit` decimal(15,2) DEFAULT '0.00',
  `customers_served` int DEFAULT '0',
  `avg_transaction` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Whisky', 'Premium Scotch, Bourbon, and Blended Whiskies', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(2, 'Vodka', 'Premium and Standard Vodkas', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(3, 'Gin', 'London Dry, Premium and Flavored Gins', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(4, 'Rum', 'White, Dark, Spiced and Premium Rums', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(5, 'Cognac & Brandy', 'Fine Cognacs and Brandies', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(6, 'Tequila', 'Blanco, Reposado and Añejo Tequilas', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(7, 'Wine - Red', 'Red Wines from Various Regions', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(8, 'Wine - White', 'White and Rosé Wines', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(9, 'Wine - Sparkling', 'Champagne and Sparkling Wines', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(10, 'Beer', 'Local and International Beers', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(11, 'Liqueurs', 'Sweet and Flavored Liqueurs', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(12, 'Ready-to-Drink (RTD)', 'Pre-mixed Cocktails and Coolers', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(13, 'Non-Alcoholic', 'Mixers, Soft Drinks and Water', 'active', '2025-10-07 09:44:45', '2025-10-07 09:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int NOT NULL,
  `customer_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `loyalty_points` int NOT NULL DEFAULT '0',
  `total_purchases` decimal(10,2) NOT NULL DEFAULT '0.00',
  `visit_count` int NOT NULL DEFAULT '0',
  `last_visit` datetime DEFAULT NULL,
  `last_purchase` datetime DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_rules`
--

CREATE TABLE `discount_rules` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percentage','fixed','bulk') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_quantity` int DEFAULT NULL COMMENT 'For bulk discounts',
  `min_amount` decimal(10,2) DEFAULT NULL COMMENT 'Minimum purchase amount',
  `applicable_to` enum('all','category','product') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `applicable_ids` json DEFAULT NULL COMMENT 'Category or product IDs',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int NOT NULL,
  `to_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` int NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `branch_id` int DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `receipt_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `vendor` varchar(255) DEFAULT '',
  `expense_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `branch_id`, `category`, `amount`, `description`, `receipt_number`, `payment_method`, `vendor`, `expense_date`, `created_at`) VALUES
(1, 1, 1, 'Transport', 1000.00, 'delivery', NULL, 'cash', '', '2025-10-07', '2025-10-07 12:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PIN or username',
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `type` enum('info','warning','error','success') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `action_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `data`, `type`, `category`, `is_read`, `action_url`, `created_at`, `read_at`) VALUES
(1, 1, 'Low Stock Alert', 'Product \"Veuve Clicquot Yellow Label 750ml\" is running low. Current stock: 1', NULL, 'warning', 'inventory', 0, '/products.php?id=68', '2025-10-08 08:57:31', NULL),
(2, 1, 'Low Stock Alert', 'Product \"Klipdrift Brandy 750ml\" is running low. Current stock: 0', NULL, 'warning', 'inventory', 0, '/products.php?id=41', '2025-10-08 08:58:24', NULL),
(3, 1, 'Out of Stock Alert', 'Product \"Klipdrift Brandy 750ml\" is now out of stock!', NULL, 'error', 'inventory', 0, '/products.php?id=41', '2025-10-08 08:58:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_quantity` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '10',
  `supplier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bottle',
  `size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `barcode`, `description`, `cost_price`, `selling_price`, `stock_quantity`, `reorder_level`, `supplier`, `unit`, `size`, `sku`, `location`, `expiry_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Johnnie Walker Black Label 750ml', 'JWB750', '12 Year Old Blended Scotch Whisky', 2800.00, 3500.00, 45, 10, 'Diageo Kenya', 'bottle', NULL, 'WHY-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(2, 1, 'Johnnie Walker Red Label 750ml', 'JWR750', 'Classic Blended Scotch Whisky', 1800.00, 2300.00, 60, 15, 'Diageo Kenya', 'bottle', NULL, 'WHY-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(3, 1, 'Jameson Irish Whiskey 750ml', 'JAM750', 'Triple Distilled Irish Whiskey', 2200.00, 2800.00, 35, 10, 'Pernod Ricard', 'bottle', NULL, 'WHY-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(4, 1, 'Jack Daniels Tennessee Whiskey 750ml', 'JD750', 'Old No.7 Tennessee Whiskey', 3000.00, 3800.00, 30, 8, 'Brown-Forman', 'bottle', NULL, 'WHY-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(5, 1, 'Chivas Regal 12 Year 750ml', 'CHV12', 'Premium Blended Scotch Whisky', 3500.00, 4500.00, 25, 8, 'Pernod Ricard', 'bottle', NULL, 'WHY-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(6, 1, 'Glenfiddich 12 Year 750ml', 'GLE12', 'Single Malt Scotch Whisky', 4000.00, 5200.00, 20, 5, 'William Grant', 'bottle', NULL, 'WHY-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(7, 1, 'Teachers Highland Cream 750ml', 'TCH750', 'Blended Scotch Whisky', 1600.00, 2000.00, 50, 15, 'Beam Suntory', 'bottle', NULL, 'WHY-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(8, 1, 'Famous Grouse 750ml', 'FGR750', 'Finest Blended Scotch Whisky', 1700.00, 2200.00, 40, 12, 'Edrington Group', 'bottle', NULL, 'WHY-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(9, 1, 'Ballantines Finest 750ml', 'BAL750', 'Premium Blended Scotch', 1900.00, 2400.00, 38, 10, 'Pernod Ricard', 'bottle', NULL, 'WHY-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(10, 1, 'Grants Triple Wood 750ml', 'GRT750', 'Triple Matured Blended Scotch', 1500.00, 1900.00, 55, 15, 'William Grant', 'bottle', NULL, 'WHY-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(11, 2, 'Smirnoff Red Label 750ml', 'SMR750', 'Triple Distilled Vodka', 1400.00, 1800.00, 63, 20, 'Diageo Kenya', 'bottle', NULL, 'VOD-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 07:19:47'),
(12, 2, 'Absolute Vodka 750ml', 'ABS750', 'Swedish Premium Vodka', 1800.00, 2300.00, 41, 15, 'Pernod Ricard', 'bottle', NULL, 'VOD-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 14:48:19'),
(13, 2, 'Ciroc Vodka 750ml', 'CRC750', 'Ultra Premium French Vodka', 3500.00, 4500.00, 20, 8, 'Diageo Kenya', 'bottle', NULL, 'VOD-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(14, 2, 'Skyy Vodka 750ml', 'SKY750', 'American Premium Vodka', 1500.00, 1900.00, 50, 15, 'Campari Group', 'bottle', NULL, 'VOD-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(15, 2, 'Grey Goose 750ml', 'GGO750', 'French Luxury Vodka', 4000.00, 5200.00, 15, 5, 'Bacardi', 'bottle', NULL, 'VOD-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(16, 2, 'Russian Standard 750ml', 'RST750', 'Original Russian Vodka', 1600.00, 2000.00, 40, 12, 'Russian Standard', 'bottle', NULL, 'VOD-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(17, 2, 'Belvedere Vodka 750ml', 'BEL750', 'Polish Luxury Vodka', 3800.00, 4800.00, 18, 6, 'LVMH', 'bottle', NULL, 'VOD-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(18, 2, 'Ketel One 750ml', 'KET750', 'Dutch Premium Vodka', 2500.00, 3200.00, 28, 10, 'Diageo Kenya', 'bottle', NULL, 'VOD-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(19, 2, 'Stolichnaya 750ml', 'STO750', 'Premium Russian Vodka', 1700.00, 2200.00, 35, 12, 'SPI Group', 'bottle', NULL, 'VOD-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(20, 2, 'Smirnoff Ice 275ml', 'SMI275', 'Vodka Mixed Drink', 180.00, 250.00, 120, 40, 'Diageo Kenya', 'bottle', NULL, 'VOD-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(21, 3, 'Gordons London Dry Gin 750ml', 'GOR750', 'Classic London Dry Gin', 1300.00, 1700.00, 55, 15, 'Diageo Kenya', 'bottle', NULL, 'GIN-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(22, 3, 'Tanqueray London Dry Gin 750ml', 'TAN750', 'Premium London Dry Gin', 2200.00, 2800.00, 35, 10, 'Diageo Kenya', 'bottle', NULL, 'GIN-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(23, 3, 'Bombay Sapphire 750ml', 'BOM750', 'Premium London Dry Gin', 2400.00, 3000.00, 30, 10, 'Bacardi', 'bottle', NULL, 'GIN-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(24, 3, 'Beefeater London Dry Gin 750ml', 'BEE750', 'Classic London Gin', 1800.00, 2300.00, 40, 12, 'Pernod Ricard', 'bottle', NULL, 'GIN-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(25, 3, 'Hendricks Gin 750ml', 'HEN750', 'Premium Scottish Gin', 3200.00, 4000.00, 22, 8, 'William Grant', 'bottle', NULL, 'GIN-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(26, 3, 'Gilbeys Gin 750ml', 'GIL750', 'London Dry Gin', 1100.00, 1400.00, 60, 18, 'Diageo Kenya', 'bottle', NULL, 'GIN-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(27, 3, 'Gordons Pink Gin 750ml', 'GRP750', 'Premium Pink Gin', 1500.00, 1900.00, 61, 12, 'Diageo Kenya', 'bottle', NULL, 'GIN-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 08:58:37'),
(28, 3, 'Monkey 47 Gin 500ml', 'MON500', 'Ultra Premium German Gin', 4500.00, 5800.00, 12, 5, 'Pernod Ricard', 'bottle', NULL, 'GIN-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(29, 4, 'Captain Morgan Spiced Rum 750ml', 'CAP750', 'Original Spiced Rum', 1600.00, 2000.00, 48, 15, 'Diageo Kenya', 'bottle', NULL, 'RUM-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 08:23:27'),
(30, 4, 'Bacardi Superior White 750ml', 'BAC750', 'Premium White Rum', 1700.00, 2200.00, 45, 15, 'Bacardi', 'bottle', NULL, 'RUM-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(31, 4, 'Havana Club 3 Year 750ml', 'HAV3', 'Aged Cuban Rum', 1900.00, 2400.00, 35, 10, 'Pernod Ricard', 'bottle', NULL, 'RUM-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(32, 4, 'Malibu Coconut Rum 750ml', 'MAL750', 'Caribbean Coconut Rum', 1500.00, 1900.00, 55, 15, 'Pernod Ricard', 'bottle', NULL, 'RUM-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(33, 4, 'Appleton Estate 750ml', 'APP750', 'Jamaican Gold Rum', 2000.00, 2600.00, 30, 10, 'Campari Group', 'bottle', NULL, 'RUM-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(34, 4, 'Kraken Black Spiced Rum 750ml', 'KRA750', 'Black Spiced Rum', 2200.00, 2800.00, 28, 8, 'Proximo Spirits', 'bottle', NULL, 'RUM-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(35, 4, 'Bacardi Black 750ml', 'BAB750', 'Premium Dark Rum', 1800.00, 2300.00, 39, 12, 'Bacardi', 'bottle', NULL, 'RUM-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 11:26:16'),
(36, 4, 'Mount Gay Eclipse 750ml', 'MGE750', 'Barbados Golden Rum', 2100.00, 2700.00, 32, 10, 'Remy Cointreau', 'bottle', NULL, 'RUM-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(37, 5, 'Hennessy VS 700ml', 'HEN700', 'Fine Cognac', 4500.00, 5800.00, 25, 8, 'Moet Hennessy', 'bottle', NULL, 'COG-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(38, 5, 'Remy Martin VSOP 700ml', 'REM700', 'Premium Cognac', 5500.00, 7000.00, 18, 6, 'Remy Cointreau', 'bottle', NULL, 'COG-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(39, 5, 'Martell VS 700ml', 'MAR700', 'French Cognac', 4000.00, 5200.00, 22, 8, 'Pernod Ricard', 'bottle', NULL, 'COG-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(40, 5, 'Courvoisier VS 700ml', 'COU700', 'Cognac Fine Champagne', 4200.00, 5500.00, 20, 6, 'Beam Suntory', 'bottle', NULL, 'COG-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(41, 5, 'Klipdrift Brandy 750ml', 'KLP750', 'South African Brandy', 1200.00, 1600.00, 0, 15, 'Distell', 'bottle', NULL, 'COG-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 08:58:24'),
(42, 5, 'Viceroy Brandy 750ml', 'VIC750', 'Premium Brandy', 1400.00, 1800.00, 40, 12, 'Pernod Ricard', 'bottle', NULL, 'COG-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(43, 6, 'Jose Cuervo Especial Gold 750ml', 'JCG750', 'Premium Gold Tequila', 2500.00, 3200.00, 30, 10, 'Proximo Spirits', 'bottle', NULL, 'TEQ-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(44, 6, 'Jose Cuervo Especial Silver 750ml', 'JCS750', 'Silver Tequila', 2400.00, 3000.00, 32, 10, 'Proximo Spirits', 'bottle', NULL, 'TEQ-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(45, 6, 'Patron Silver 750ml', 'PAT750', 'Ultra Premium Tequila', 5000.00, 6500.00, 15, 5, 'Bacardi', 'bottle', NULL, 'TEQ-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(46, 6, 'Olmeca Blanco 750ml', 'OLM750', 'Premium Blanco Tequila', 2200.00, 2800.00, 28, 8, 'Pernod Ricard', 'bottle', NULL, 'TEQ-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(47, 6, 'Don Julio Blanco 750ml', 'DON750', 'Premium Luxury Tequila', 4800.00, 6200.00, 18, 6, 'Diageo Kenya', 'bottle', NULL, 'TEQ-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(48, 7, 'Four Cousins Sweet Red 750ml', 'FC4SR', 'Sweet Red Wine', 900.00, 1200.00, 79, 20, 'Distell', 'bottle', NULL, 'WRD-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 07:20:35'),
(49, 7, 'Nederburg Cabernet Sauvignon 750ml', 'NED750', 'South African Red Wine', 1100.00, 1500.00, 60, 18, 'Distell', 'bottle', NULL, 'WRD-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(50, 7, 'Drostdy Hof Merlot 750ml', 'DRO750', 'Premium Merlot', 850.00, 1100.00, 70, 20, 'Distell', 'bottle', NULL, 'WRD-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(51, 7, 'Robertson Winery Sweet Red 750ml', 'ROB750', 'Natural Sweet Red', 750.00, 1000.00, 90, 25, 'Robertson', 'bottle', NULL, 'WRD-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(52, 7, 'Amarula Cream 750ml', 'AMA750', 'Cream Liqueur', 1400.00, 1800.00, 47, 15, 'Distell', 'bottle', NULL, 'WRD-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 14:59:13'),
(53, 7, 'KWV Red Muscadel 750ml', 'KWV750', 'Fortified Red Wine', 800.00, 1050.00, 65, 18, 'KWV', 'bottle', NULL, 'WRD-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(54, 7, 'Kumala Shiraz Cabernet 750ml', 'KUM750', 'Red Wine Blend', 950.00, 1250.00, 55, 15, 'Accolade Wines', 'bottle', NULL, 'WRD-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(55, 7, 'Casillero del Diablo Merlot 750ml', 'CAS750', 'Chilean Red Wine', 1300.00, 1700.00, 45, 12, 'Concha y Toro', 'bottle', NULL, 'WRD-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(56, 7, 'Lindeman\'s Bin 45 Cabernet 750ml', 'LIN750', 'Australian Red Wine', 1200.00, 1600.00, 48, 15, 'Treasury Wine', 'bottle', NULL, 'WRD-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(57, 7, 'Jacobs Creek Shiraz 750ml', 'JAC750', 'Classic Shiraz', 1100.00, 1450.00, 52, 15, 'Pernod Ricard', 'bottle', NULL, 'WRD-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(58, 8, 'Four Cousins Sweet White 750ml', 'FC4SW', 'Sweet White Wine', 900.00, 1200.00, 75, 20, 'Distell', 'bottle', NULL, 'WWH-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(59, 8, 'Nederburg Sauvignon Blanc 750ml', 'NEDSB', 'Crisp White Wine', 1100.00, 1500.00, 55, 15, 'Distell', 'bottle', NULL, 'WWH-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(60, 8, 'Robertson Winery Sweet White 750ml', 'ROBSW', 'Natural Sweet White', 750.00, 1000.00, 85, 25, 'Robertson', 'bottle', NULL, 'WWH-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(61, 8, 'Drostdy Hof Chardonnay 750ml', 'DROCH', 'Premium Chardonnay', 850.00, 1100.00, 60, 18, 'Distell', 'bottle', NULL, 'WWH-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(62, 8, 'Kumala Chenin Chardonnay 750ml', 'KUMCH', 'White Wine Blend', 950.00, 1250.00, 50, 15, 'Accolade Wines', 'bottle', NULL, 'WWH-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(63, 8, 'Jacobs Creek Chardonnay 750ml', 'JACCH', 'Classic Chardonnay', 1100.00, 1450.00, 48, 15, 'Pernod Ricard', 'bottle', NULL, 'WWH-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(64, 8, 'Casillero del Diablo Sauvignon 750ml', 'CASSB', 'Chilean White Wine', 1300.00, 1700.00, 42, 12, 'Concha y Toro', 'bottle', NULL, 'WWH-007', NULL, NULL, 'inactive', 1, '2025-10-07 09:44:45', '2025-10-08 08:56:49'),
(65, 8, 'KWV White Muscadel 750ml', 'KWVWM', 'Fortified White Wine', 800.00, 1050.00, 62, 18, 'KWV', 'bottle', NULL, 'WWH-008', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(66, 9, 'J.C. Le Roux Le Domaine 750ml', 'JCLD750', 'South African Sparkling', 1200.00, 1600.00, 40, 12, 'Distell', 'bottle', NULL, 'WSP-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(67, 9, 'Moet & Chandon Brut Imperial 750ml', 'MOET750', 'Premium Champagne', 6000.00, 7800.00, 15, 5, 'Moet Hennessy', 'bottle', NULL, 'WSP-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(68, 9, 'Veuve Clicquot Yellow Label 750ml', 'VEUVE750', 'Luxury Champagne', 7000.00, 9000.00, 1, 4, 'Moet Hennessy', 'bottle', NULL, 'WSP-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 08:57:31'),
(69, 9, 'Pongracz Brut Rose 750ml', 'PONG750', 'Sparkling Rose', 1800.00, 2400.00, 30, 10, 'Distell', 'bottle', NULL, 'WSP-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(70, 9, 'Graham Beck Brut 750ml', 'GRAB750', 'Premium MCC', 1600.00, 2100.00, 35, 10, 'Graham Beck', 'bottle', NULL, 'WSP-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(71, 10, 'Tusker Lager 500ml', 'TUS500', 'Kenyan Premium Lager', 120.00, 180.00, 200, 50, 'EABL', 'bottle', NULL, 'BER-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(72, 10, 'Tusker Malt 500ml', 'TUSM500', 'Kenyan Premium Malt', 130.00, 200.00, 180, 50, 'EABL', 'bottle', NULL, 'BER-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(73, 10, 'White Cap Lager 500ml', 'WHC500', 'Kenyan Lager', 110.00, 170.00, 220, 60, 'EABL', 'bottle', NULL, 'BER-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(74, 10, 'Pilsner Lager 500ml', 'PIL500', 'Ice Cold Lager', 100.00, 150.00, 250, 70, 'EABL', 'bottle', NULL, 'BER-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(75, 10, 'Guinness Original 500ml', 'GUI500', 'Irish Stout', 150.00, 220.00, 150, 40, 'EABL', 'bottle', NULL, 'BER-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(76, 10, 'Heineken Lager 330ml', 'HEI330', 'Premium Lager', 140.00, 200.00, 180, 50, 'Heineken Kenya', 'bottle', NULL, 'BER-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(77, 10, 'Corona Extra 355ml', 'COR355', 'Mexican Lager', 160.00, 240.00, 120, 35, 'ABInBev', 'bottle', NULL, 'BER-007', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(78, 10, 'Budweiser 330ml', 'BUD330', 'American Lager', 150.00, 220.00, 140, 40, 'ABInBev', 'bottle', NULL, 'BER-008', NULL, NULL, 'inactive', 1, '2025-10-07 09:44:45', '2025-10-08 08:56:30'),
(79, 10, 'Amstel Lager 330ml', 'AMS330', 'Premium Lager', 130.00, 190.00, 156, 45, 'Heineken Kenya', 'bottle', NULL, 'BER-009', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 14:59:13'),
(80, 10, 'Balozi Lager 500ml', 'BAL500', 'Kenyan Lager', 90.00, 140.00, 280, 80, 'EABL', 'bottle', NULL, 'BER-010', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(81, 10, 'Chrome Vodka Ice 275ml', 'CHR275', 'Vodka Premix', 110.00, 160.00, 200, 50, 'EABL', 'bottle', NULL, 'BER-011', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(82, 10, 'Hunters Dry 330ml', 'HUN330', 'Premium Cider', 140.00, 200.00, 150, 40, 'Distell', 'bottle', NULL, 'BER-012', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(83, 11, 'Baileys Original Irish Cream 750ml', 'BAI750', 'Irish Cream Liqueur', 2200.00, 2800.00, 40, 12, 'Diageo Kenya', 'bottle', NULL, 'LIQ-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(84, 11, 'Jagermeister 700ml', 'JAG700', 'Herbal Liqueur', 2400.00, 3000.00, 35, 10, 'Mast-Jaegermeister', 'bottle', NULL, 'LIQ-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(85, 11, 'Cointreau 700ml', 'COI700', 'Orange Liqueur', 2800.00, 3600.00, 25, 8, 'Remy Cointreau', 'bottle', NULL, 'LIQ-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(86, 11, 'Kahlua Coffee Liqueur 750ml', 'KAH750', 'Coffee Liqueur', 2000.00, 2600.00, 30, 10, 'Pernod Ricard', 'bottle', NULL, 'LIQ-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(87, 11, 'Southern Comfort 750ml', 'SOU750', 'Whiskey Liqueur', 2200.00, 2800.00, 32, 10, 'Sazerac', 'bottle', NULL, 'LIQ-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(88, 11, 'Tia Maria 700ml', 'TIA700', 'Coffee Liqueur', 1900.00, 2400.00, 28, 8, 'Illva Saronno', 'bottle', NULL, 'LIQ-006', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(89, 11, 'Campari 700ml', 'CAM700', 'Italian Aperitif', 2100.00, 2700.00, 26, 8, 'Campari Group', 'bottle', NULL, 'LIQ-007', NULL, NULL, 'inactive', 1, '2025-10-07 09:44:45', '2025-10-08 08:56:37'),
(90, 11, 'Aperol 700ml', 'APE700', 'Italian Aperitif', 1800.00, 2300.00, 30, 10, 'Campari Group', 'bottle', NULL, 'LIQ-008', NULL, NULL, 'inactive', 1, '2025-10-07 09:44:45', '2025-10-08 08:56:16'),
(91, 12, 'Smirnoff Ice Double Black 300ml', 'SIDB300', 'Vodka Premix', 180.00, 250.00, 149, 40, 'Diageo Kenya', 'bottle', NULL, 'RTD-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-08 08:55:33'),
(92, 12, 'Brutal Fruit Ruby 275ml', 'BRU275', 'Sparkling Fruit Drink', 160.00, 220.00, 180, 50, 'Heineken Kenya', 'bottle', NULL, 'RTD-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(93, 12, 'Flying Fish 330ml', 'FLY330', 'Flavored Beer', 150.00, 210.00, 160, 45, 'Distell', 'bottle', NULL, 'RTD-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(94, 12, 'Bacardi Breezer 275ml', 'BAB275', 'Rum Cooler', 170.00, 240.00, 140, 40, 'Bacardi', 'bottle', NULL, 'RTD-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(95, 12, 'Ciroc Spritz 275ml', 'CRS275', 'Vodka Cocktail', 200.00, 280.00, 100, 30, 'Diageo Kenya', 'bottle', NULL, 'RTD-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(96, 13, 'Coca Cola 500ml', 'COK500', 'Carbonated Soft Drink', 35.00, 60.00, 300, 100, 'Coca Cola', 'bottle', NULL, 'NON-001', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(97, 13, 'Sprite 500ml', 'SPR500', 'Lemon Lime Soda', 35.00, 60.00, 280, 100, 'Coca Cola', 'bottle', NULL, 'NON-002', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(98, 13, 'Schweppes Tonic Water 300ml', 'SCH300', 'Premium Tonic Water', 60.00, 100.00, 200, 60, 'Coca Cola', 'bottle', NULL, 'NON-003', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(99, 13, 'Keringet Sparkling Water 500ml', 'KER500', 'Natural Mineral Water', 50.00, 80.00, 250, 80, 'Keringet', 'bottle', NULL, 'NON-004', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45'),
(100, 13, 'Red Bull Energy 250ml', 'RDB250', 'Energy Drink', 150.00, 220.00, 180, 50, 'Red Bull', 'can', NULL, 'NON-005', NULL, NULL, 'active', 1, '2025-10-07 09:44:45', '2025-10-07 09:44:45');

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `trg_low_stock_notification` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock_quantity <= NEW.reorder_level AND OLD.stock_quantity > OLD.reorder_level THEN
        INSERT INTO notifications (user_id, title, message, type, category, action_url)
        SELECT 
            id,
            'Low Stock Alert',
            CONCAT('Product "', NEW.name, '" is running low. Current stock: ', NEW.stock_quantity),
            'warning',
            'inventory',
            CONCAT('/products.php?id=', NEW.id)
        FROM users WHERE role = 'owner' AND status = 'active';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_out_of_stock_notification` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock_quantity = 0 AND OLD.stock_quantity > 0 THEN
        INSERT INTO notifications (user_id, title, message, type, category, action_url)
        SELECT 
            id,
            'Out of Stock Alert',
            CONCAT('Product "', NEW.name, '" is now out of stock!'),
            'error',
            'inventory',
            CONCAT('/products.php?id=', NEW.id)
        FROM users WHERE role = 'owner' AND status = 'active';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_products_audit_update` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 1;
    DECLARE v_ip_address VARCHAR(45) DEFAULT NULL;
    
    -- Try to get user context (will fail gracefully if not set)
    SET v_user_id = IFNULL(@current_user_id, 1);
    SET v_ip_address = @current_ip_address;
    
    INSERT INTO audit_trail (user_id, table_name, record_id, action, old_values, new_values, ip_address)
    VALUES (
        v_user_id,
        'products',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'name', OLD.name,
            'stock_quantity', OLD.stock_quantity,
            'selling_price', OLD.selling_price,
            'cost_price', OLD.cost_price,
            'status', OLD.status
        ),
        JSON_OBJECT(
            'name', NEW.name,
            'stock_quantity', NEW.stock_quantity,
            'selling_price', NEW.selling_price,
            'cost_price', NEW.cost_price,
            'status', NEW.status
        ),
        v_ip_address
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `received_date` date NOT NULL,
  `status` enum('active','expired','depleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discount_type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_products`
--

CREATE TABLE `promotion_products` (
  `id` int NOT NULL,
  `promotion_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int NOT NULL,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int NOT NULL,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int NOT NULL,
  `po_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `received_quantity` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int NOT NULL,
  `sale_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `branch_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('cash','mpesa','mpesa_till','card') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `mpesa_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `change_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sale_date` datetime NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_number`, `user_id`, `branch_id`, `customer_id`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `payment_method`, `mpesa_reference`, `amount_paid`, `change_amount`, `sale_date`, `notes`, `created_at`) VALUES
(1, 'ZWS-20251007-7F0A8A', 1, 1, NULL, 1800.00, 0.00, 0.00, 1800.00, 'cash', NULL, 1800.00, 0.00, '2025-10-07 13:33:43', NULL, '2025-10-07 10:33:43'),
(2, 'ZWS-20251007-B0487D', 1, 1, NULL, 380.00, 0.00, 0.00, 380.00, 'cash', NULL, 380.00, 0.00, '2025-10-07 13:34:35', NULL, '2025-10-07 10:34:35'),
(3, 'ZWS-20251007-51D8F6', 2, 1, NULL, 2300.00, 0.00, 0.00, 2300.00, 'cash', NULL, 2300.00, 0.00, '2025-10-07 13:48:05', NULL, '2025-10-07 10:48:05'),
(4, 'ZWS-20251007-4C7E8D', 1, 1, NULL, 2300.00, 0.00, 0.00, 2300.00, 'cash', NULL, 2300.00, 0.00, '2025-10-07 14:19:32', NULL, '2025-10-07 11:19:32'),
(5, 'ZWS-20251007-7E7391', 1, 1, NULL, 2300.00, 0.00, 0.00, 2300.00, 'cash', NULL, 2300.00, 0.00, '2025-10-07 14:26:15', NULL, '2025-10-07 11:26:16'),
(17, 'ZWS-20251007-030C0D', 1, 1, NULL, 190.00, 0.00, 0.00, 190.00, 'cash', NULL, 190.00, 0.00, '2025-10-07 17:47:06', '', '2025-10-07 14:47:07'),
(18, 'ZWS-20251007-162357', 1, 1, NULL, 2300.00, 0.00, 0.00, 2300.00, 'cash', NULL, 2300.00, 0.00, '2025-10-07 17:47:57', '', '2025-10-07 14:47:57'),
(19, 'ZWS-20251007-69C4A8', 1, 1, NULL, 4100.00, 0.00, 0.00, 4100.00, 'cash', NULL, 4100.00, 0.00, '2025-10-07 17:48:19', '', '2025-10-07 14:48:19'),
(20, 'ZWS-20251007-29D876', 1, 1, NULL, 1990.00, 0.00, 0.00, 1990.00, 'cash', NULL, 2000.00, 10.00, '2025-10-07 17:59:13', '', '2025-10-07 14:59:13'),
(21, 'ZWS-20251008-06DA53', 2, 1, NULL, 3600.00, 0.00, 0.00, 3600.00, 'cash', '', 3600.00, 0.00, '2025-10-08 10:19:47', '', '2025-10-08 07:19:47'),
(22, 'ZWS-20251008-90CC03', 2, 1, NULL, 1200.00, 0.00, 0.00, 1200.00, 'mpesa', '-', 1200.00, 0.00, '2025-10-08 10:20:35', '', '2025-10-08 07:20:35'),
(23, 'ZWS-20251008-F48590', 1, 1, NULL, 4000.00, 0.00, 0.00, 4000.00, 'cash', '', 4000.00, 0.00, '2025-10-08 11:23:27', '', '2025-10-08 08:23:27'),
(24, 'ZWS-20251008-BE6D59', 1, 1, NULL, 250.00, 0.00, 0.00, 250.00, 'cash', '', 250.00, 0.00, '2025-10-08 11:55:33', '', '2025-10-08 08:55:33');

-- --------------------------------------------------------

--
-- Table structure for table `sales_targets`
--

CREATE TABLE `sales_targets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `achieved_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `bonus_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int NOT NULL,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `discount`, `subtotal`, `created_at`) VALUES
(1, 1, 52, 'Amarula Cream 750ml', 1, 1800.00, 0.00, 1800.00, '2025-10-07 10:33:44'),
(2, 2, 79, 'Amstel Lager 330ml', 2, 190.00, 0.00, 380.00, '2025-10-07 10:34:35'),
(3, 3, 12, 'Absolute Vodka 750ml', 1, 2300.00, 0.00, 2300.00, '2025-10-07 10:48:05'),
(4, 4, 12, 'Absolute Vodka 750ml', 1, 2300.00, 0.00, 2300.00, '2025-10-07 11:19:32'),
(5, 5, 35, 'Bacardi Black 750ml', 1, 2300.00, 0.00, 2300.00, '2025-10-07 11:26:16'),
(6, 17, 79, 'Amstel Lager 330ml', 1, 190.00, 0.00, 190.00, '2025-10-07 14:47:07'),
(7, 18, 12, 'Absolute Vodka 750ml', 1, 2300.00, 0.00, 2300.00, '2025-10-07 14:47:57'),
(8, 19, 12, 'Absolute Vodka 750ml', 1, 2300.00, 0.00, 2300.00, '2025-10-07 14:48:19'),
(9, 19, 52, 'Amarula Cream 750ml', 1, 1800.00, 0.00, 1800.00, '2025-10-07 14:48:19'),
(10, 20, 52, 'Amarula Cream 750ml', 1, 1800.00, 0.00, 1800.00, '2025-10-07 14:59:13'),
(11, 20, 79, 'Amstel Lager 330ml', 1, 190.00, 0.00, 190.00, '2025-10-07 14:59:13'),
(12, 21, 11, 'Smirnoff Red Label 750ml', 2, 1800.00, 0.00, 3600.00, '2025-10-08 07:19:47'),
(13, 22, 48, 'Four Cousins Sweet Red 750ml', 1, 1200.00, 0.00, 1200.00, '2025-10-08 07:20:35'),
(14, 23, 29, 'Captain Morgan Spiced Rum 750ml', 2, 2000.00, 0.00, 4000.00, '2025-10-08 08:23:27'),
(15, 24, 91, 'Smirnoff Ice Double Black 300ml', 1, 250.00, 0.00, 250.00, '2025-10-08 08:55:33');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_tasks`
--

CREATE TABLE `scheduled_tasks` (
  `id` int NOT NULL,
  `task_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'backup, report, cleanup, etc',
  `schedule` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Cron expression',
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','running') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `login_time`, `last_activity`, `logout_time`) VALUES
(1, 1, '2a5eb5d986be18c549eb1ac772104cb4c975f23aadf9025c3b900327f729006f', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 08:13:33', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(2, 1, '411b40a164876b00a24627620bb767998d762f8aadd1d85f75a22231218de0b2', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 08:18:38', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(3, 1, '2c3005dd117ae2b02a48e53d76a2da63213f537a81ee2df5ed1d79b84032a6aa', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 09:26:20', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(4, 1, '11f0812ea8686b810b86567b5d8febcb937f9e1ce357e7d32b6f263b068c5d6f', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 09:30:28', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(5, 1, '420ea5ec81aa816d11016bc09804fee2555e0a2dc4af92bda319a4bbcff4aafb', '41.209.14.78', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-07 09:40:41', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(6, 2, 'c9a35961c781054f54aaf3fb0425e7f142c1e2da550815557485061d14965dc4', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 10:47:46', '2025-10-08 07:28:54', '2025-10-08 07:28:54'),
(7, 1, 'a5250a91a9f0eb40fd649bb8b07d1935e9f163a44641cf29d9a5ffd83e8d9f68', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 11:17:54', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(8, 1, 'a4bf690c489d4b299eec2a05960f99bffc73b65b448dc4271d11bc2e8ac27170', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 13:03:26', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(9, 1, '7d96881e2d3721531e69eb36d0b720ebfe605362b4a8280c1a0c171b42b68e86', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 14:25:09', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(10, 1, '96330eb61a828cac3d7663ab173b32433fa7be2d69c38e53f4877fc85fbfa3b6', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 15:31:57', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(11, 1, '8d1cb5f0cd9cc6f8d67df65ca5930b5342ae127ac9d0faafbf4cf0c172ee9da3', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 16:05:15', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(12, 2, '2465f75b2f9d6d8dfb2a55d4ea8edd7969b6356d7cfaf0367cd42701ba623c87', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-07 16:05:33', '2025-10-08 07:28:54', '2025-10-08 07:28:54'),
(13, 1, '3521e4972b0a92c926baaff088d681aa08ea64f7ad6b85195fe69417fd0b8721', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 06:59:03', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(14, 2, 'a557329029a5f3d971ed73765a922decfdec62a7bae9754df75ff4582f4d14bd', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 07:01:24', '2025-10-08 07:28:54', '2025-10-08 07:28:54'),
(15, 1, 'c90142a4cdd2d6130652e0de1999ae54c5b9e0525e16e5dbb7ab704816578ed3', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 07:28:59', '2025-10-08 07:35:47', '2025-10-08 07:35:47'),
(16, 2, '1d212f818728dd77ebf8d80b32f44b7668571498dd472b71f4d29b03cfdd8106', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 07:35:51', '2025-10-08 07:38:00', '2025-10-08 07:38:00'),
(17, 1, 'a923fd3ec5e63d3cd88e23efe7c69b88282d34b856961c23cfc9858537c13eef', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 07:38:04', '2025-10-08 07:52:29', '2025-10-08 07:52:29'),
(18, 1, '51408178f81136d40e4a2a6daae60bb8374138673396f63e1435c4b80249fad6', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 07:44:53', '2025-10-08 07:52:29', '2025-10-08 07:52:29'),
(19, 2, '5bb44e1bd09951ed2e522ff36753224a16fe91317495884d9213df9034b6de08', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 07:52:34', '2025-10-08 07:52:34', NULL),
(20, 1, 'e847b37251dd7ae9c8f1a09a1118b171576c2e32e0dc8a24bca734fbee347a30', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 08:14:09', '2025-10-08 08:14:18', '2025-10-08 08:14:18'),
(21, 2, 'c7354b8e5d288367c9dc9634b4fbc0dd4995c4902f1ff2acda1f1f36a5ada3d4', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 08:14:24', '2025-10-08 08:14:24', NULL),
(22, 2, '98754eae8f3a840e0a17164416905c7f64755032e88c40bce37e568b5260cbc7', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 08:30:11', '2025-10-08 08:30:11', NULL),
(23, 1, 'add061847e3ee0d4bc80aa8afb90adc2ed4f04bec4fdd277c039a6787817d7f7', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-08 08:54:23', '2025-10-08 08:54:23', NULL),
(24, 1, 'e5679e93870661043a61f3c09ca150517db109932820c3b8367817c73800c026', '41.209.14.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-08 09:32:26', '2025-10-08 09:32:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Zuri Wines & Spirits',
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/logo.jpg',
  `primary_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#ea580c',
  `secondary_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#ffffff',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KSh',
  `currency_symbol` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KSh',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `receipt_footer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `receipt_template` text COLLATE utf8mb4_unicode_ci,
  `barcode_scanner_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `low_stock_alert_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `enable_loyalty_points` tinyint(1) NOT NULL DEFAULT '1',
  `points_per_currency` decimal(5,2) NOT NULL DEFAULT '1.00',
  `enable_email_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `smtp_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int DEFAULT NULL,
  `smtp_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enable_sms_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `sms_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `low_stock_threshold` int NOT NULL DEFAULT '10',
  `auto_backup_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `backup_frequency` enum('daily','weekly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'weekly',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `company_name`, `logo_path`, `primary_color`, `secondary_color`, `currency`, `currency_symbol`, `tax_rate`, `receipt_footer`, `receipt_template`, `barcode_scanner_enabled`, `low_stock_alert_enabled`, `enable_loyalty_points`, `points_per_currency`, `enable_email_notifications`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `enable_sms_notifications`, `sms_api_key`, `low_stock_threshold`, `auto_backup_enabled`, `backup_frequency`, `created_at`, `updated_at`) VALUES
(1, 'Zuri Wines & Spirits', '/logo.jpg', '#ea580c', '#ffffff', 'KSh', 'KSh', 0.00, 'Thank you for your business!\nVisit us again!', NULL, 1, 1, 1, 1.00, 0, NULL, NULL, NULL, NULL, 0, NULL, 10, 0, 'weekly', '2025-10-07 07:04:06', '2025-10-07 07:04:06');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `movement_type` enum('in','out','adjustment','sale','return') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `user_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_at`) VALUES
(1, 52, 1, 'sale', 1, 'sale', 1, 'Sale: ZWS-20251007-7F0A8A', '2025-10-07 10:33:44'),
(2, 79, 1, 'sale', 2, 'sale', 2, 'Sale: ZWS-20251007-B0487D', '2025-10-07 10:34:35'),
(3, 12, 2, 'sale', 1, 'sale', 3, 'Sale: ZWS-20251007-51D8F6', '2025-10-07 10:48:05'),
(4, 12, 1, 'sale', 1, 'sale', 4, 'Sale: ZWS-20251007-4C7E8D', '2025-10-07 11:19:32'),
(5, 35, 1, 'sale', 1, 'sale', 5, 'Sale: ZWS-20251007-7E7391', '2025-10-07 11:26:16'),
(6, 79, 1, 'sale', 1, 'sale', 17, 'Sale: ZWS-20251007-030C0D', '2025-10-07 14:47:07'),
(7, 12, 1, 'sale', 1, 'sale', 18, 'Sale: ZWS-20251007-162357', '2025-10-07 14:47:57'),
(8, 12, 1, 'sale', 1, 'sale', 19, 'Sale: ZWS-20251007-69C4A8', '2025-10-07 14:48:19'),
(9, 52, 1, 'sale', 1, 'sale', 19, 'Sale: ZWS-20251007-69C4A8', '2025-10-07 14:48:19'),
(10, 52, 1, 'sale', 1, 'sale', 20, 'Sale: ZWS-20251007-29D876', '2025-10-07 14:59:13'),
(11, 79, 1, 'sale', 1, 'sale', 20, 'Sale: ZWS-20251007-29D876', '2025-10-07 14:59:13'),
(12, 11, 2, 'sale', 2, 'sale', 21, 'Sale: ZWS-20251008-06DA53', '2025-10-08 07:19:47'),
(13, 48, 2, 'sale', 1, 'sale', 22, 'Sale: ZWS-20251008-90CC03', '2025-10-08 07:20:35'),
(14, 29, 1, 'sale', 2, 'sale', 23, 'Sale: ZWS-20251008-F48590', '2025-10-08 08:23:27'),
(15, 91, 1, 'sale', 1, 'sale', 24, 'Sale: ZWS-20251008-BE6D59', '2025-10-08 08:55:33'),
(16, 68, 1, 'out', 11, NULL, NULL, '', '2025-10-08 08:57:31'),
(17, 41, 1, 'out', 45, NULL, NULL, '', '2025-10-08 08:58:24'),
(18, 27, 1, 'in', 16, NULL, NULL, '', '2025-10-08 08:58:37');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` int NOT NULL,
  `transfer_number` varchar(100) NOT NULL,
  `from_branch_id` int NOT NULL,
  `to_branch_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `transfer_date` datetime NOT NULL,
  `initiated_by` int NOT NULL,
  `received_by` int DEFAULT NULL,
  `status` enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `payment_terms` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_purchases` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `payment_terms`, `total_purchases`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Diageo Kenya Ltd', 'John Kamau', 'orders@diageo.co.ke', '+254712345001', NULL, NULL, 0.00, 'active', '2025-10-07 12:46:26', '2025-10-07 12:46:26'),
(2, 'Pernod Ricard Kenya', 'Mary Wanjiru', 'sales@pernod.co.ke', '+254712345002', NULL, NULL, 0.00, 'active', '2025-10-07 12:46:26', '2025-10-07 12:46:26'),
(3, 'Bacardi Limited', 'Peter Ochieng', 'kenya@bacardi.com', '+254712345003', NULL, NULL, 0.00, 'active', '2025-10-07 12:46:26', '2025-10-07 12:46:26'),
(4, 'EABL - East African Breweries', 'Sarah Njeri', 'orders@eabl.co.ke', '+254712345004', NULL, NULL, 0.00, 'active', '2025-10-07 12:46:26', '2025-10-07 12:46:26'),
(5, 'Distell Kenya', 'James Mwangi', 'info@distell.co.ke', '+254712345005', NULL, NULL, 0.00, 'active', '2025-10-07 12:46:26', '2025-10-07 12:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','number','boolean','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'general, email, backup, etc',
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `updated_at`) VALUES
(1, 'max_login_attempts', '5', 'number', 'security', 'Maximum login attempts before lockout', '2025-10-07 13:20:19'),
(2, 'lockout_duration', '900', 'number', 'security', 'Lockout duration in seconds (15 minutes)', '2025-10-07 13:20:19'),
(3, 'session_timeout', '3600', 'number', 'security', 'Session timeout in seconds (1 hour)', '2025-10-07 13:20:19'),
(4, 'enable_2fa', 'false', 'boolean', 'security', 'Enable two-factor authentication', '2025-10-07 13:20:19'),
(5, 'low_stock_email_alert', 'true', 'boolean', 'notifications', 'Send email when stock is low', '2025-10-07 13:20:19'),
(6, 'daily_sales_report', 'false', 'boolean', 'reports', 'Send daily sales report', '2025-10-07 13:20:19'),
(7, 'backup_retention_days', '30', 'number', 'backup', 'Number of days to keep backups', '2025-10-07 13:20:19'),
(8, 'enable_loyalty_program', 'true', 'boolean', 'customer', 'Enable customer loyalty points', '2025-10-07 13:20:19'),
(9, 'loyalty_points_ratio', '100', 'number', 'customer', 'Points earned per currency unit', '2025-10-07 13:20:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pin_code` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('owner','seller') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'seller',
  `branch_id` int DEFAULT NULL,
  `permissions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `pin_code`, `role`, `branch_id`, `permissions`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Owner', NULL, NULL, '1234', 'owner', 1, '[\"all\"]', 'active', '2025-10-07 07:04:05', '2025-10-08 10:02:47'),
(2, 'Seller 1', '', '', '5678', 'seller', 1, '[\"pos\", \"view_products\", \"add_products\", \"view_own_sales\"]', 'active', '2025-10-07 07:04:05', '2025-10-08 10:02:47');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_daily_sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_daily_sales_summary` (
`sale_day` date
,`transaction_count` bigint
,`total_sales` decimal(32,2)
,`total_tax` decimal(32,2)
,`total_discounts` decimal(32,2)
,`avg_transaction_value` decimal(14,6)
,`cash_sales` decimal(32,2)
,`mpesa_sales` decimal(32,2)
,`card_sales` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_inventory_valuation`
-- (See below for the actual view)
--
CREATE TABLE `v_inventory_valuation` (
`category` varchar(255)
,`product_count` bigint
,`total_units` decimal(32,0)
,`cost_value` decimal(42,2)
,`retail_value` decimal(42,2)
,`potential_profit` decimal(43,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_low_stock_products`
-- (See below for the actual view)
--
CREATE TABLE `v_low_stock_products` (
`id` int
,`name` varchar(255)
,`sku` varchar(100)
,`barcode` varchar(100)
,`stock_quantity` int
,`reorder_level` int
,`selling_price` decimal(10,2)
,`category_name` varchar(255)
,`needed_quantity` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_performance`
-- (See below for the actual view)
--
CREATE TABLE `v_product_performance` (
`id` int
,`name` varchar(255)
,`category_id` int
,`category_name` varchar(255)
,`stock_quantity` int
,`selling_price` decimal(10,2)
,`cost_price` decimal(10,2)
,`total_sold` decimal(32,0)
,`total_revenue` decimal(32,2)
,`total_profit` decimal(43,2)
,`times_sold` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sales_analytics`
-- (See below for the actual view)
--
CREATE TABLE `v_sales_analytics` (
`sale_date` date
,`total_sales` bigint
,`unique_customers` bigint
,`revenue` decimal(32,2)
,`avg_sale_value` decimal(14,6)
,`cash_sales` decimal(32,2)
,`mpesa_sales` decimal(32,2)
,`card_sales` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_top_selling_products`
-- (See below for the actual view)
--
CREATE TABLE `v_top_selling_products` (
`id` int
,`name` varchar(255)
,`category_id` int
,`category_name` varchar(255)
,`times_sold` bigint
,`total_quantity_sold` decimal(32,0)
,`total_revenue` decimal(32,2)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_logs_action_date` (`action`,`created_at`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_record` (`table_name`,`record_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `branch_inventory`
--
ALTER TABLE `branch_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_branch_product` (`branch_id`,`product_id`),
  ADD KEY `idx_branch` (`branch_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_low_stock` (`branch_id`,`stock_quantity`,`reorder_level`);

--
-- Indexes for table `branch_metrics`
--
ALTER TABLE `branch_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_branch_date` (`branch_id`,`metric_date`),
  ADD KEY `idx_branch` (`branch_id`),
  ADD KEY `idx_date` (`metric_date`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `phone` (`phone`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `discount_rules`
--
ALTER TABLE `discount_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `start_end_dates` (`start_date`,`end_date`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_branch_expenses` (`branch_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier_time` (`identifier`,`attempt_time`),
  ADD KEY `idx_attempt_time` (`attempt_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_notifications_unread` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `sku` (`sku`),
  ADD KEY `status` (`status`),
  ADD KEY `stock_quantity` (`stock_quantity`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_products_category_status` (`category_id`,`status`),
  ADD KEY `idx_products_stock_status` (`stock_quantity`,`status`),
  ADD KEY `idx_products_supplier` (`supplier`),
  ADD KEY `idx_products_stock_level` (`stock_quantity`,`reorder_level`,`status`),
  ADD KEY `idx_name_status` (`name`,`status`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_number` (`batch_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `dates` (`start_date`,`end_date`),
  ADD KEY `promotion_user_fk` (`created_by`);

--
-- Indexes for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sale_date` (`sale_date`),
  ADD KEY `payment_method` (`payment_method`),
  ADD KEY `idx_sales_date_user` (`sale_date`,`user_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_sales_customer` (`customer_id`,`sale_date`),
  ADD KEY `idx_sales_period` (`sale_date`,`total_amount`),
  ADD KEY `idx_customer_date` (`customer_id`,`sale_date`),
  ADD KEY `idx_branch_sales` (`branch_id`);

--
-- Indexes for table `sales_targets`
--
ALTER TABLE `sales_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `period` (`period_start`,`period_end`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_sale_product` (`sale_id`,`product_id`);

--
-- Indexes for table `scheduled_tasks`
--
ALTER TABLE `scheduled_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_name` (`task_name`),
  ADD KEY `next_run` (`next_run`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movement_type` (`movement_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_stock_movements_product_type` (`product_id`,`movement_type`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_number` (`transfer_number`),
  ADD KEY `idx_from_branch` (`from_branch_id`),
  ADD KEY `idx_to_branch` (`to_branch_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transfer_date` (`transfer_date`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `initiated_by` (`initiated_by`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pin_code` (`pin_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `status` (`status`),
  ADD KEY `role` (`role`),
  ADD KEY `idx_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `branch_inventory`
--
ALTER TABLE `branch_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `branch_metrics`
--
ALTER TABLE `branch_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discount_rules`
--
ALTER TABLE `discount_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_products`
--
ALTER TABLE `promotion_products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sales_targets`
--
ALTER TABLE `sales_targets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `scheduled_tasks`
--
ALTER TABLE `scheduled_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Structure for view `v_daily_sales_summary`
--
DROP TABLE IF EXISTS `v_daily_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vxjtgclw`@`localhost` SQL SECURITY DEFINER VIEW `v_daily_sales_summary`  AS SELECT cast(`sales`.`sale_date` as date) AS `sale_day`, count(0) AS `transaction_count`, sum(`sales`.`total_amount`) AS `total_sales`, sum(`sales`.`tax_amount`) AS `total_tax`, sum(`sales`.`discount_amount`) AS `total_discounts`, avg(`sales`.`total_amount`) AS `avg_transaction_value`, sum((case when (`sales`.`payment_method` = 'cash') then `sales`.`total_amount` else 0 end)) AS `cash_sales`, sum((case when (`sales`.`payment_method` in ('mpesa','mpesa_till')) then `sales`.`total_amount` else 0 end)) AS `mpesa_sales`, sum((case when (`sales`.`payment_method` = 'card') then `sales`.`total_amount` else 0 end)) AS `card_sales` FROM `sales` GROUP BY cast(`sales`.`sale_date` as date) ORDER BY `sale_day` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_inventory_valuation`
--
DROP TABLE IF EXISTS `v_inventory_valuation`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vxjtgclw`@`localhost` SQL SECURITY DEFINER VIEW `v_inventory_valuation`  AS SELECT `c`.`name` AS `category`, count(`p`.`id`) AS `product_count`, sum(`p`.`stock_quantity`) AS `total_units`, sum((`p`.`stock_quantity` * `p`.`cost_price`)) AS `cost_value`, sum((`p`.`stock_quantity` * `p`.`selling_price`)) AS `retail_value`, sum((`p`.`stock_quantity` * (`p`.`selling_price` - `p`.`cost_price`))) AS `potential_profit` FROM (`products` `p` join `categories` `c` on((`p`.`category_id` = `c`.`id`))) WHERE (`p`.`status` = 'active') GROUP BY `c`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_low_stock_products`
--
DROP TABLE IF EXISTS `v_low_stock_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vxjtgclw`@`localhost` SQL SECURITY DEFINER VIEW `v_low_stock_products`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`sku` AS `sku`, `p`.`barcode` AS `barcode`, `p`.`stock_quantity` AS `stock_quantity`, `p`.`reorder_level` AS `reorder_level`, `p`.`selling_price` AS `selling_price`, `c`.`name` AS `category_name`, (`p`.`reorder_level` - `p`.`stock_quantity`) AS `needed_quantity` FROM (`products` `p` left join `categories` `c` on((`p`.`category_id` = `c`.`id`))) WHERE ((`p`.`stock_quantity` <= `p`.`reorder_level`) AND (`p`.`status` = 'active')) ORDER BY `p`.`stock_quantity` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_product_performance`
--
DROP TABLE IF EXISTS `v_product_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vxjtgclw`@`localhost` SQL SECURITY DEFINER VIEW `v_product_performance`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`category_id` AS `category_id`, `c`.`name` AS `category_name`, `p`.`stock_quantity` AS `stock_quantity`, `p`.`selling_price` AS `selling_price`, `p`.`cost_price` AS `cost_price`, coalesce(sum(`si`.`quantity`),0) AS `total_sold`, coalesce(sum(`si`.`subtotal`),0) AS `total_revenue`, coalesce((sum(`si`.`subtotal`) - sum((`si`.`quantity` * `p`.`cost_price`))),0) AS `total_profit`, count(distinct `s`.`id`) AS `times_sold` FROM (((`products` `p` left join `categories` `c` on((`p`.`category_id` = `c`.`id`))) left join `sale_items` `si` on((`p`.`id` = `si`.`product_id`))) left join `sales` `s` on((`si`.`sale_id` = `s`.`id`))) WHERE (`p`.`status` = 'active') GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_sales_analytics`
--
DROP TABLE IF EXISTS `v_sales_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vxjtgclw`@`localhost` SQL SECURITY DEFINER VIEW `v_sales_analytics`  AS SELECT cast(`s`.`sale_date` as date) AS `sale_date`, count(distinct `s`.`id`) AS `total_sales`, count(distinct `s`.`customer_id`) AS `unique_customers`, sum(`s`.`total_amount`) AS `revenue`, avg(`s`.`total_amount`) AS `avg_sale_value`, sum((case when (`s`.`payment_method` = 'cash') then `s`.`total_amount` else 0 end)) AS `cash_sales`, sum((case when (`s`.`payment_method` in ('mpesa','mpesa_till')) then `s`.`total_amount` else 0 end)) AS `mpesa_sales`, sum((case when (`s`.`payment_method` = 'card') then `s`.`total_amount` else 0 end)) AS `card_sales` FROM `sales` AS `s` GROUP BY cast(`s`.`sale_date` as date) ;

-- --------------------------------------------------------

--
-- Structure for view `v_top_selling_products`
--
DROP TABLE IF EXISTS `v_top_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`vxjtgclw`@`localhost` SQL SECURITY DEFINER VIEW `v_top_selling_products`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`category_id` AS `category_id`, `c`.`name` AS `category_name`, count(`si`.`id`) AS `times_sold`, sum(`si`.`quantity`) AS `total_quantity_sold`, sum(`si`.`subtotal`) AS `total_revenue` FROM ((`products` `p` left join `sale_items` `si` on((`p`.`id` = `si`.`product_id`))) left join `categories` `c` on((`p`.`category_id` = `c`.`id`))) WHERE (`p`.`status` = 'active') GROUP BY `p`.`id` ORDER BY `total_quantity_sold` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backup_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `branch_inventory`
--
ALTER TABLE `branch_inventory`
  ADD CONSTRAINT `branch_inventory_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `branch_inventory_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `branch_metrics`
--
ALTER TABLE `branch_metrics`
  ADD CONSTRAINT `branch_metrics_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notification_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `batch_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `batch_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotion_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD CONSTRAINT `pp_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pp_promotion_fk` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `po_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `po_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `poi_po_fk` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poi_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_targets`
--
ALTER TABLE `sales_targets`
  ADD CONSTRAINT `target_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_ibfk_4` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_ibfk_5` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
