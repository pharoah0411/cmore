-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2026 at 08:24 AM
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
-- Database: `cmore`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `APPOINTMENT_ID` int(11) NOT NULL,
  `PATIENT_ID` int(11) NOT NULL,
  `STAFF_ID` int(11) NOT NULL,
  `APPOINTMENT_DATETIME` datetime NOT NULL,
  `STATUS` varchar(50) DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES
(1, 1, 2, '2026-04-22 10:30:00', 'Completed'),
(2, 2, 2, '2026-04-22 11:30:00', 'Completed'),
(3, 3, 1, '2026-04-25 14:00:00', 'Completed'),
(6, 15, 1, '2026-05-19 16:30:00', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `LOG_ID` int(11) NOT NULL,
  `USER_ID` int(11) NOT NULL,
  `ACTION` varchar(255) NOT NULL,
  `TABLE_NAME` varchar(100) DEFAULT NULL,
  `RECORD_ID` int(11) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES
(1, 4, 'User logged in successfully', NULL, NULL, '2026-05-28 13:04:11'),
(2, 3, 'User changed password and logged in', NULL, NULL, '2026-05-28 13:06:53'),
(3, 1, 'User changed password and logged in', NULL, NULL, '2026-05-28 13:10:40'),
(4, 1, 'Created new clinical exam record', 'eye_examination', 3, '2026-05-28 14:27:41'),
(5, 1, 'Updated clinical exam record', 'eye_examination', 3, '2026-05-28 14:58:30'),
(6, 1, 'Updated clinical exam record', 'eye_examination', 3, '2026-05-28 14:58:49'),
(7, 1, 'Updated clinical exam record', 'eye_examination', 3, '2026-05-28 14:59:12'),
(8, 4, 'User changed password and logged in', NULL, NULL, '2026-05-28 15:07:17'),
(9, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:17:51'),
(10, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:17:54'),
(11, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:17:56'),
(12, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:18:02'),
(13, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:18:04'),
(14, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:18:46'),
(15, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:18:48'),
(16, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:19:41'),
(17, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:19:42'),
(18, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:19:44'),
(19, 4, 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 15:20:31'),
(20, 4, 'Attempted unauthorized access to Add Clinical Exam', NULL, NULL, '2026-05-28 15:22:29'),
(21, 4, 'Attempted unauthorized access to Add Clinical Exam', NULL, NULL, '2026-05-28 15:22:37'),
(22, 4, 'User logged in successfully', NULL, NULL, '2026-06-06 09:55:34'),
(23, 4, 'User logged in successfully', NULL, NULL, '2026-06-07 03:11:39'),
(24, 4, 'User changed password and logged in', NULL, NULL, '2026-06-09 06:22:12');

-- --------------------------------------------------------

--
-- Table structure for table `eye_examination`
--

CREATE TABLE `eye_examination` (
  `EXAM_ID` int(11) NOT NULL,
  `PATIENT_ID` int(11) NOT NULL,
  `OPTOMETRIST_ID` int(11) NOT NULL,
  `EXAM_DATE` date NOT NULL,
  `VISUAL_ACUITY_RESULTS` text DEFAULT NULL,
  `PRESCRIPTION_RESULT` text DEFAULT NULL,
  `RE_SPH` varchar(10) DEFAULT NULL,
  `RE_CYL` varchar(10) DEFAULT NULL,
  `RE_AXIS` varchar(10) DEFAULT NULL,
  `RE_ADD` varchar(10) DEFAULT NULL,
  `LE_SPH` varchar(10) DEFAULT NULL,
  `LE_CYL` varchar(10) DEFAULT NULL,
  `LE_AXIS` varchar(10) DEFAULT NULL,
  `LE_ADD` varchar(10) DEFAULT NULL,
  `PD` varchar(10) DEFAULT NULL,
  `CLINICAL_NOTES` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eye_examination`
--

INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES
(1, 1, 1, '2026-01-15', '6/12 OD, 6/9 OS', 'Myopia with Astigmatism', '-2.25', '-0.50', '180', '+1.50', '-2.00', '-0.75', '175', '+1.50', '64mm', 'Patient complains of night driving glare.'),
(2, 2, 1, '2026-02-10', '6/6 OU', 'Presbyopia', '+0.00', 'DS', NULL, '+2.00', '+0.00', 'DS', NULL, '+2.00', '62mm', 'Prescribed computer lenses with blue light filter.'),
(3, 14, 1, '2026-05-28', '', '', '-4.00', '-1.50', '60', '0', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PATIENT_ID` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `IC_NUMBER` varchar(20) NOT NULL,
  `PHONE_NUMBER` varchar(20) DEFAULT NULL,
  `ADDRESS` text DEFAULT NULL,
  `REGISTRATION_DATE` date NOT NULL,
  `CONNECTION_RELATIONSHIP` varchar(100) DEFAULT NULL,
  `FOLLOW_UP_INTERVAL` varchar(50) DEFAULT NULL,
  `COMPLAINTS` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES
(1, 'John Doe', '850101-14-5567', '012-3456789', '123 Jalan Ampang, KL', '2026-01-15', 'Self', '6 Months', 'Blurry vision at distance, frequent headaches'),
(2, 'Jane Smith', '920512-10-6642', '017-9876543', '45 Crystal Heights, PJ', '2026-02-10', 'Family (Wife of John)', '1 Year', 'Dry eyes and irritation after using computer'),
(3, 'Robert Lim', '781120-01-5231', '019-2233445', '8-2-1 Kondominium Ria, Puchong', '2026-03-05', 'Referral', '3 Months', 'Sudden flashes of light in left eye'),
(4, 'fere demie', '', '01943434343', NULL, '2026-05-18', NULL, NULL, NULL),
(14, 'hhrror', '04440404050', '0195121339', 'To be updated', '2026-05-18', 'None', 'Not Set', 'None'),
(15, 'farerere', 'NO-IC-14754', '0195123399', 'Walk-in / Unrecorded', '2026-05-18', 'None', 'Not Set', 'None');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `PRODUCT_ID` int(11) NOT NULL,
  `CATEGORY` varchar(50) DEFAULT NULL,
  `BRAND_NAME` varchar(100) DEFAULT NULL,
  `STOCK_QUANTITY` int(11) DEFAULT 0,
  `UNIT_PRICE` decimal(10,2) NOT NULL,
  `MINIMUM_PRICE` decimal(10,2) NOT NULL DEFAULT 0.00,
  `SUPPLIER_ID` int(11) DEFAULT NULL,
  `EXPIRY_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`) VALUES
(1, 'Frame', 'Ray-Ban Wayfarer', 14, 450.00, 0.00, 2, NULL),
(2, 'Frame', 'Oakley Holbrook', 6, 520.00, 0.00, 2, NULL),
(3, 'Contact Lens', 'Acuvue Moist (30pk)', 48, 110.00, 0.00, 1, '2026-07-01'),
(4, 'Solution', 'Opti-Free PureMoist', 29, 45.00, 0.00, 1, NULL),
(5, 'Frames', 'Ray-Ban Aviator Classic', 15, 650.00, 500.00, NULL, NULL),
(6, 'Contact Lenses', 'Acuvue Oasys Bi-weekly (Box of 6)', 45, 120.00, 100.00, NULL, '2026-07-01'),
(7, 'Solutions', 'Alcon Opti-Free PureMoist 300ml', 25, 35.00, 28.00, NULL, NULL),
(8, 'Frames', 'Oakley Holbrook Matte Black', 12, 750.00, 680.00, NULL, NULL),
(9, 'Contact Lenses', 'Alcon Dailies Total1 (Box of 30)', 29, 180.00, 150.00, NULL, '2026-06-15'),
(10, 'Frames', 'Prada PR 17WS Rectangular', 8, 1250.00, 1000.00, NULL, NULL),
(11, 'Contact Lenses', 'Bausch + Lomb Biotrue ONEday', 3, 145.00, 130.00, NULL, '2024-12-01'),
(12, 'Frames', 'Tom Ford Blue Block Square', 1, 1800.00, 1500.00, NULL, NULL),
(13, 'Solutions', 'Renu Advanced Formula 355ml', 4, 28.00, 22.00, NULL, NULL),
(14, 'Accessories', 'Premium Microfiber Cleaning Cloth', 2, 8.00, 5.00, NULL, NULL),
(15, 'Accessories', 'Zeiss Anti-Fog Wipes (Box of 30)', 4, 35.00, 25.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `SALE_ID` int(11) NOT NULL,
  `PATIENT_ID` int(11) NOT NULL,
  `STAFF_ID` int(11) NOT NULL,
  `SALE_DATE` datetime DEFAULT current_timestamp(),
  `TOTAL_AMOUNT` decimal(10,2) NOT NULL,
  `PAID_AMOUNT` decimal(10,2) DEFAULT 0.00,
  `PAYMENT_METHOD` enum('Cash','Card','E-wallet','Online Banking') NOT NULL,
  `PAYMENT_STATUS` enum('Pending','Partial','Completed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES
(1, 1, 2, '2026-01-15 11:15:00', 450.00, 450.00, 'Card', 'Completed'),
(2, 2, 2, '2026-02-10 12:45:00', 155.00, 50.00, 'E-wallet', 'Partial'),
(3, 1, 1, '2026-04-29 18:36:38', 2499.99, 2500.00, 'Card', 'Completed'),
(4, 1, 1, '2026-04-30 09:35:54', 630.00, 60.00, 'Card', 'Completed'),
(5, 4, 1, '2026-05-17 18:26:38', 520.00, 400.00, 'Card', 'Partial'),
(6, 4, 1, '2026-05-17 18:46:45', 45.00, 45.00, 'Cash', 'Completed'),
(7, 15, 1, '2026-05-17 19:10:29', 110.00, 110.00, 'Card', 'Completed'),
(8, 14, 1, '2026-05-28 12:34:06', 180.00, 150.00, 'Cash', 'Partial');

-- --------------------------------------------------------

--
-- Table structure for table `sales_item`
--

CREATE TABLE `sales_item` (
  `SALES_ITEM_ID` int(11) NOT NULL,
  `SALE_ID` int(11) NOT NULL,
  `PRODUCT_ID` int(11) NOT NULL,
  `QUANTITY` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_item`
--

INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES
(1, 1, 1, 1),
(2, 2, 3, 1),
(3, 2, 4, 1),
(4, 3, 2, 1),
(5, 3, 2, 1),
(6, 3, 1, 1),
(7, 4, 3, 1),
(8, 4, 2, 1),
(9, 5, 2, 1),
(10, 6, 4, 1),
(11, 7, 3, 1),
(12, 8, 9, 1);

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `SUPPLIER_ID` int(11) NOT NULL,
  `COMPANY_NAME` varchar(255) NOT NULL,
  `CONTACT_PERSON` varchar(100) DEFAULT NULL,
  `PHONE_NUMBER` varchar(20) DEFAULT NULL,
  `EMAIL` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`SUPPLIER_ID`, `COMPANY_NAME`, `CONTACT_PERSON`, `PHONE_NUMBER`, `EMAIL`) VALUES
(1, 'Visionary Wholesale', 'Mr. Wong', '03-55667788', 'sales@visionary.com.my'),
(2, 'Luxottica Group', 'Alice Green', '03-88990011', 'orders@luxottica.com');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `USER_ID` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `ROLE` varchar(50) NOT NULL,
  `FIRST_LOGIN_OTP` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`USER_ID`, `NAME`, `EMAIL`, `PASSWORD`, `ROLE`, `FIRST_LOGIN_OTP`) VALUES
(1, 'Dr. Sarah Chen', 'yinghuai180704@gmail.com', '041104', 'Optometrist', 0),
(2, 'Michael Tan', 'michael@opticore.com', 'hashed_pw_456', 'Staff', 1),
(3, 'Admin User', 'b032420034@student.utem.edu.my', '041104', 'Admin', 0),
(4, 'Farah', 'damia.f0411@gmail.com', '041104', 'staff', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`APPOINTMENT_ID`),
  ADD KEY `fk_appt_patient` (`PATIENT_ID`),
  ADD KEY `fk_appt_staff` (`STAFF_ID`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`LOG_ID`);

--
-- Indexes for table `eye_examination`
--
ALTER TABLE `eye_examination`
  ADD PRIMARY KEY (`EXAM_ID`),
  ADD KEY `fk_exam_patient` (`PATIENT_ID`),
  ADD KEY `fk_exam_optom` (`OPTOMETRIST_ID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PATIENT_ID`),
  ADD UNIQUE KEY `IC_NUMBER` (`IC_NUMBER`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`PRODUCT_ID`),
  ADD KEY `fk_product_supplier` (`SUPPLIER_ID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`SALE_ID`),
  ADD KEY `fk_sales_patient` (`PATIENT_ID`),
  ADD KEY `fk_sales_staff` (`STAFF_ID`);

--
-- Indexes for table `sales_item`
--
ALTER TABLE `sales_item`
  ADD PRIMARY KEY (`SALES_ITEM_ID`),
  ADD KEY `fk_item_sale` (`SALE_ID`),
  ADD KEY `fk_item_prod` (`PRODUCT_ID`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`SUPPLIER_ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`USER_ID`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `APPOINTMENT_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `LOG_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `eye_examination`
--
ALTER TABLE `eye_examination`
  MODIFY `EXAM_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PATIENT_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `PRODUCT_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `SALE_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sales_item`
--
ALTER TABLE `sales_item`
  MODIFY `SALES_ITEM_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `SUPPLIER_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `USER_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `fk_appt_patient` FOREIGN KEY (`PATIENT_ID`) REFERENCES `patient` (`PATIENT_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appt_staff` FOREIGN KEY (`STAFF_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE;

--
-- Constraints for table `eye_examination`
--
ALTER TABLE `eye_examination`
  ADD CONSTRAINT `fk_exam_optom` FOREIGN KEY (`OPTOMETRIST_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exam_patient` FOREIGN KEY (`PATIENT_ID`) REFERENCES `patient` (`PATIENT_ID`) ON DELETE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`SUPPLIER_ID`) REFERENCES `supplier` (`SUPPLIER_ID`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_patient` FOREIGN KEY (`PATIENT_ID`) REFERENCES `patient` (`PATIENT_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sales_staff` FOREIGN KEY (`STAFF_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE;

--
-- Constraints for table `sales_item`
--
ALTER TABLE `sales_item`
  ADD CONSTRAINT `fk_item_prod` FOREIGN KEY (`PRODUCT_ID`) REFERENCES `product` (`PRODUCT_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_sale` FOREIGN KEY (`SALE_ID`) REFERENCES `sales` (`SALE_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
