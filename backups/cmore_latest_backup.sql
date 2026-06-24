-- C-More Clinical Suite Database Backup (Auto-Triggered)
-- Generated: 2026-06-24 21:10:02
-- Triggered By: Admin User
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS=0;


-- Table structure for table `appointment`
DROP TABLE IF EXISTS `appointment`;
CREATE TABLE `appointment` (
  `APPOINTMENT_ID` int(11) NOT NULL AUTO_INCREMENT,
  `PATIENT_ID` int(11) NOT NULL,
  `STAFF_ID` int(11) NOT NULL,
  `APPOINTMENT_DATETIME` datetime NOT NULL,
  `STATUS` varchar(50) DEFAULT 'Scheduled',
  PRIMARY KEY (`APPOINTMENT_ID`),
  KEY `fk_appt_patient` (`PATIENT_ID`),
  KEY `fk_appt_staff` (`STAFF_ID`),
  CONSTRAINT `fk_appt_patient` FOREIGN KEY (`PATIENT_ID`) REFERENCES `patient` (`PATIENT_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_staff` FOREIGN KEY (`STAFF_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `appointment`
INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES ('3', '3', '1', '2026-04-25 14:00:00', 'Completed');
INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES ('6', '15', '1', '2026-05-19 16:30:00', 'Cancelled');
INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES ('7', '14', '1', '2026-06-27 12:00:00', 'Pending');
INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES ('8', '14', '1', '2026-06-23 11:00:00', 'Pending');
INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES ('9', '14', '1', '2026-06-18 18:00:00', 'Pending');
INSERT INTO `appointment` (`APPOINTMENT_ID`, `PATIENT_ID`, `STAFF_ID`, `APPOINTMENT_DATETIME`, `STATUS`) VALUES ('12', '14', '1', '2026-06-23 16:50:00', 'Pending');

-- Table structure for table `audit_log`
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `LOG_ID` int(11) NOT NULL AUTO_INCREMENT,
  `USER_ID` int(11) NOT NULL,
  `ACTION` varchar(255) NOT NULL,
  `TABLE_NAME` varchar(100) DEFAULT NULL,
  `RECORD_ID` int(11) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`LOG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `audit_log`
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('1', '4', 'User logged in successfully', NULL, NULL, '2026-05-28 21:04:11');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('2', '3', 'User changed password and logged in', NULL, NULL, '2026-05-28 21:06:53');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('3', '1', 'User changed password and logged in', NULL, NULL, '2026-05-28 21:10:40');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('4', '1', 'Created new clinical exam record', 'eye_examination', '3', '2026-05-28 22:27:41');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('5', '1', 'Updated clinical exam record', 'eye_examination', '3', '2026-05-28 22:58:30');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('6', '1', 'Updated clinical exam record', 'eye_examination', '3', '2026-05-28 22:58:49');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('7', '1', 'Updated clinical exam record', 'eye_examination', '3', '2026-05-28 22:59:12');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('8', '4', 'User changed password and logged in', NULL, NULL, '2026-05-28 23:07:17');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('9', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:17:51');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('10', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:17:54');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('11', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:17:56');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('12', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:18:02');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('13', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:18:04');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('14', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:18:46');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('15', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:18:48');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('16', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:19:41');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('17', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:19:42');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('18', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:19:44');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('19', '4', 'Attempted unauthorized access to Clinical Exams', NULL, NULL, '2026-05-28 23:20:31');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('20', '4', 'Attempted unauthorized access to Add Clinical Exam', NULL, NULL, '2026-05-28 23:22:29');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('21', '4', 'Attempted unauthorized access to Add Clinical Exam', NULL, NULL, '2026-05-28 23:22:37');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('22', '4', 'User logged in successfully', NULL, NULL, '2026-06-06 17:55:34');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('23', '4', 'User logged in successfully', NULL, NULL, '2026-06-07 11:11:39');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('24', '4', 'User changed password and logged in', NULL, NULL, '2026-06-09 14:22:12');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('25', '4', 'User logged in successfully', NULL, NULL, '2026-06-11 22:09:32');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('26', '4', 'Attempted unauthorized access to Add Clinical Exam', NULL, NULL, '2026-06-12 00:49:17');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('27', '1', 'User logged in successfully', NULL, NULL, '2026-06-12 00:49:46');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('28', '1', 'Updated clinical exam record', 'eye_examination', '3', '2026-06-12 00:59:23');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('29', '1', 'Created new clinical exam record', 'eye_examination', '4', '2026-06-12 01:22:11');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('30', '1', 'Created new clinical exam record', 'eye_examination', '5', '2026-06-12 01:23:19');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('31', '4', 'User logged in successfully', NULL, NULL, '2026-06-18 16:07:05');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('32', '4', 'User logged in successfully', NULL, NULL, '2026-06-18 16:14:03');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('33', '1', 'User logged in successfully', NULL, NULL, '2026-06-18 16:23:37');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('34', '3', 'User logged in successfully', NULL, NULL, '2026-06-19 00:41:51');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('35', '4', 'User logged in successfully', NULL, NULL, '2026-06-21 15:12:09');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('36', '4', 'User logged in successfully', NULL, NULL, '2026-06-22 19:35:16');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('37', '4', 'Added new walk-in patient: aisyah', 'patient', '16', '2026-06-22 22:24:22');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('38', '4', 'Booked new appointment', 'appointment', '10', '2026-06-22 22:24:22');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('39', '4', 'Added new walk-in patient: siti', 'patient', '17', '2026-06-22 22:31:06');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('40', '4', 'Booked new appointment', 'appointment', '11', '2026-06-22 22:31:06');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('41', '4', 'Updated appointment details', 'appointment', '9', '2026-06-22 22:32:54');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('42', '3', 'User logged in successfully', NULL, NULL, '2026-06-22 22:33:32');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('43', '1', 'User logged in successfully', NULL, NULL, '2026-06-22 22:33:42');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('44', '3', 'User logged in successfully', NULL, NULL, '2026-06-22 23:17:06');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('45', '3', 'Added new staff member: hor ying huai (b032420042@student.utem.edu.my)', 'user', '5', '2026-06-22 23:22:34');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('46', '5', 'User changed password and logged in', NULL, NULL, '2026-06-22 23:23:22');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('47', '3', 'User logged in successfully', NULL, NULL, '2026-06-22 23:23:49');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('48', '4', 'User logged in successfully', NULL, NULL, '2026-06-22 23:24:03');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('49', '4', 'Attempted unauthorized access to Add Clinical Exam', NULL, NULL, '2026-06-22 23:25:41');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('50', '3', 'User logged in successfully', NULL, NULL, '2026-06-22 23:26:18');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('51', '1', 'User logged in successfully', NULL, NULL, '2026-06-22 23:26:26');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('52', '1', 'Created new clinical exam record', 'eye_examination', '6', '2026-06-22 23:33:46');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('53', '1', 'Added payment of RM5.00 to sale #TXN-8', 'SALES', '8', '2026-06-22 23:46:14');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('54', '1', 'Deleted sale transaction #TXN-2', 'SALES', '2', '2026-06-22 23:47:09');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('55', '1', 'Deleted sale transaction #TXN-10', 'SALES', '10', '2026-06-22 23:53:30');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('56', '1', 'Booked new appointment', 'appointment', '12', '2026-06-23 00:50:21');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('57', '1', 'Updated appointment details', 'appointment', '12', '2026-06-23 00:50:34');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('58', '1', 'Updated appointment details', 'appointment', '12', '2026-06-23 00:50:40');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('59', '1', 'Updated appointment details', 'appointment', '8', '2026-06-23 00:51:14');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('60', '1', 'Created new clinical exam record', 'eye_examination', '7', '2026-06-23 01:01:34');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('61', '1', 'Edited sale transaction #TXN-13 (Changed paid amount to RM0.00)', 'SALES', '13', '2026-06-23 01:03:58');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('62', '1', 'Deleted sale transaction #TXN-3', 'SALES', '3', '2026-06-23 01:04:40');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('63', '1', 'Deleted sale transaction #TXN-1', 'SALES', '1', '2026-06-23 01:04:48');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('64', '1', 'Edited sale transaction #TXN-13 (Changed paid amount to RM0.00)', 'SALES', '13', '2026-06-23 01:16:12');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('65', '1', 'Edited sale transaction #TXN-13 (Changed paid amount to RM0.00)', 'SALES', '13', '2026-06-23 01:16:16');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('66', '4', 'User logged in successfully', NULL, NULL, '2026-06-24 19:22:11');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('67', '4', 'Edited sale transaction #TXN-13 (Changed paid amount to RM0.00)', 'SALES', '13', '2026-06-24 19:23:23');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('68', '4', 'Edited sale transaction #TXN-14 (Changed paid amount to RM120.00)', 'SALES', '14', '2026-06-24 19:25:11');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('69', '3', 'User logged in successfully', NULL, NULL, '2026-06-24 20:29:39');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('70', '4', 'User changed password and logged in', NULL, NULL, '2026-06-24 23:47:08');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('71', '3', 'User changed password and logged in', NULL, NULL, '2026-06-24 23:47:48');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('72', '3', 'User logged in successfully', NULL, NULL, '2026-06-24 23:48:02');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('73', '1', 'User changed password and logged in', NULL, NULL, '2026-06-24 23:48:52');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('74', '5', 'User changed password and logged in', NULL, NULL, '2026-06-24 23:49:35');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('75', '5', 'Generated a manual database backup', NULL, NULL, '2026-06-25 01:39:40');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('76', '5', 'Downloaded the latest database backup file', NULL, NULL, '2026-06-25 01:39:45');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('77', '5', 'Performed a Smart Merge Restore from backup', NULL, NULL, '2026-06-25 01:42:43');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('78', '5', 'Generated a manual database backup', NULL, NULL, '2026-06-25 01:43:26');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('79', '5', 'Downloaded the latest database backup file', NULL, NULL, '2026-06-25 01:43:29');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('80', '5', 'Automatic backup completed upon staff logout', NULL, NULL, '2026-06-25 01:43:55');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('81', '3', 'User logged in successfully', NULL, NULL, '2026-06-25 01:44:04');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('82', '3', 'Generated a manual database backup', NULL, NULL, '2026-06-25 01:52:25');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('83', '3', 'Automatic backup completed upon staff logout', NULL, NULL, '2026-06-25 01:52:38');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('84', '3', 'User logged in successfully', NULL, NULL, '2026-06-25 02:19:51');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('85', '4', 'User logged in successfully', NULL, NULL, '2026-06-25 02:48:34');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('86', '4', 'Performed a Smart Merge Restore from backup', NULL, NULL, '2026-06-25 03:06:45');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('87', '4', 'Automatic backup completed upon staff logout', NULL, NULL, '2026-06-25 03:06:50');
INSERT INTO `audit_log` (`LOG_ID`, `USER_ID`, `ACTION`, `TABLE_NAME`, `RECORD_ID`, `CREATED_AT`) VALUES ('88', '3', 'User logged in successfully', NULL, NULL, '2026-06-25 03:07:07');

-- Table structure for table `eye_examination`
DROP TABLE IF EXISTS `eye_examination`;
CREATE TABLE `eye_examination` (
  `EXAM_ID` int(11) NOT NULL AUTO_INCREMENT,
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
  `CLINICAL_NOTES` text DEFAULT NULL,
  PRIMARY KEY (`EXAM_ID`),
  KEY `fk_exam_patient` (`PATIENT_ID`),
  KEY `fk_exam_optom` (`OPTOMETRIST_ID`),
  CONSTRAINT `fk_exam_optom` FOREIGN KEY (`OPTOMETRIST_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_exam_patient` FOREIGN KEY (`PATIENT_ID`) REFERENCES `patient` (`PATIENT_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `eye_examination`
INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES ('1', '1', '1', '2026-01-15', '6/12 OD, 6/9 OS', 'Myopia with Astigmatism', '-2.25', '-0.50', '180', '+1.50', '-2.00', '-0.75', '175', '+1.50', '64mm', 'Patient complains of night driving glare.');
INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES ('2', '2', '1', '2026-02-10', '6/6 OU', 'Presbyopia', '+0.00', 'DS', NULL, '+2.00', '+0.00', 'DS', NULL, '+2.00', '62mm', 'Prescribed computer lenses with blue light filter.');
INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES ('3', '14', '1', '2026-05-28', '', '', '-4.00', '-1.50', '60', '0', '', '', '', '', '', '');
INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES ('5', '2', '1', '2026-06-11', '', '', '', '', '', '', '', '', '', '', '', '');
INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES ('6', '18', '1', '2026-06-22', '', '', '-6.00', '', '', '', '-9.00', '', '', '', '', '');
INSERT INTO `eye_examination` (`EXAM_ID`, `PATIENT_ID`, `OPTOMETRIST_ID`, `EXAM_DATE`, `VISUAL_ACUITY_RESULTS`, `PRESCRIPTION_RESULT`, `RE_SPH`, `RE_CYL`, `RE_AXIS`, `RE_ADD`, `LE_SPH`, `LE_CYL`, `LE_AXIS`, `LE_ADD`, `PD`, `CLINICAL_NOTES`) VALUES ('7', '18', '1', '2026-06-22', '', '', '', '', '', '', '', '', '', '', '', '');

-- Table structure for table `patient`
DROP TABLE IF EXISTS `patient`;
CREATE TABLE `patient` (
  `PATIENT_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(100) NOT NULL,
  `IC_NUMBER` varchar(20) DEFAULT NULL,
  `PHONE_NUMBER` varchar(20) DEFAULT NULL,
  `ADDRESS` text DEFAULT NULL,
  `REGISTRATION_DATE` date NOT NULL,
  `CONNECTION_RELATIONSHIP` varchar(100) DEFAULT NULL,
  `FOLLOW_UP_INTERVAL` varchar(50) DEFAULT NULL,
  `COMPLAINTS` text DEFAULT NULL,
  PRIMARY KEY (`PATIENT_ID`),
  UNIQUE KEY `IC_NUMBER` (`IC_NUMBER`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `patient`
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('1', 'John Doe', '850101-14-5567', '012-3456789', '123 Jalan Ampang, KL', '2026-01-15', 'Self', '6 Months', 'Blurry vision at distance, frequent headaches');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('2', 'Jane Smith', '920512-10-6642', '017-9876543', '45 Crystal Heights, PJ', '2026-02-10', 'Family (Wife of John)', '1 Year', 'Dry eyes and irritation after using computer');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('3', 'Robert Lim', '781120-01-5231', '019-2233445', '8-2-1 Kondominium Ria, Puchong', '2026-03-05', 'Referral', '3 Months', 'Sudden flashes of light in left eye');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('14', 'hhrror', '040718020390', '0195121339', 'To be updated', '2026-05-18', 'None', '6 Months', 'None');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('15', 'farerere', '050506868968', '01947474779', 'Walk-in / Unrecorded', '2026-05-18', 'None', '3 Months', 'None');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('18', 'aisyah', '041104020405', '012-3456789', '', '2026-06-22', 'friend', '', '');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('19', 'Walk in', 'NO-IC-58750', '0000000000', 'Walk-in / Unrecorded', '2026-06-22', 'None', 'Not Set', 'None');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('20', 'Hor Ying Huai', '041104020390', '055-5555555', '23, Jalan Sri klebang 27,', '2026-06-23', '', '3 Months', '');
INSERT INTO `patient` (`PATIENT_ID`, `NAME`, `IC_NUMBER`, `PHONE_NUMBER`, `ADDRESS`, `REGISTRATION_DATE`, `CONNECTION_RELATIONSHIP`, `FOLLOW_UP_INTERVAL`, `COMPLAINTS`) VALUES ('21', 'Nur Viviana Sia', '770529045294', '019-5597448', '', '2026-06-24', 'Family', '6 Months', 'slight headache');

-- Table structure for table `product`
DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `PRODUCT_ID` int(11) NOT NULL AUTO_INCREMENT,
  `CATEGORY` varchar(50) DEFAULT NULL,
  `BRAND_NAME` varchar(100) DEFAULT NULL,
  `STOCK_QUANTITY` int(11) DEFAULT 0,
  `UNIT_PRICE` decimal(10,2) NOT NULL,
  `MINIMUM_PRICE` decimal(10,2) NOT NULL DEFAULT 0.00,
  `SUPPLIER_ID` int(11) DEFAULT NULL,
  `EXPIRY_DATE` date DEFAULT NULL,
  `PRODUCT_IMAGE` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`PRODUCT_ID`),
  KEY `fk_product_supplier` (`SUPPLIER_ID`),
  CONSTRAINT `fk_product_supplier` FOREIGN KEY (`SUPPLIER_ID`) REFERENCES `supplier` (`SUPPLIER_ID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `product`
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('1', 'Frame', 'Ray-Ban Wayfarer', '14', '450.00', '0.00', '2', NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('2', 'Frame', 'Oakley Holbrook', '4', '520.00', '0.00', '2', NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('3', 'Frames', 'Acuvue Moist (30pk)', '42', '110.00', '90.00', '1', '2026-07-01', 'uploads/products/prod_6a3c0261ac314.jpg');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('4', 'Solution', 'Opti-Free PureMoist', '29', '45.00', '0.00', '1', NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('5', 'Frames', 'Ray-Ban Aviator Classic', '15', '650.00', '500.00', NULL, NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('6', 'Contact Lenses', 'Acuvue Oasys Bi-weekly (Box of 6)', '42', '120.00', '100.00', NULL, '2026-07-01', 'uploads/products/prod_6a3c02a62100d.png');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('7', 'Frames', 'Alcon Opti-Free PureMoist 300ml', '25', '35.00', '28.00', NULL, NULL, 'uploads/products/prod_6a3c0457af4c2.jpg');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('8', 'Frames', 'Oakley Holbrook Matte Black', '12', '750.00', '680.00', NULL, NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('9', 'Contact Lenses', 'Alcon Dailies Total1 (Box of 30)', '197', '180.00', '150.00', NULL, '2026-06-15', 'uploads/products/prod_6a3c049be0d05.jpg');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('10', 'Frames', 'Prada PR 17WS Rectangular', '8', '1250.00', '1000.00', NULL, NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('11', 'Contact Lenses', 'Bausch + Lomb Biotrue ONEday', '3', '145.00', '130.00', NULL, '2024-12-01', 'uploads/products/prod_6a3c039863619.png');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('12', 'Frames', 'Tom Ford Blue Block Square', '100', '1800.00', '1500.00', NULL, NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('13', 'Contact Lenses', 'Renu Advanced Formula 355ml', '4', '28.00', '22.00', NULL, NULL, 'uploads/products/prod_6a3c03e6ea1b7.jpg');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('14', 'Accessories', 'Premium Microfiber Cleaning Cloth', '2', '8.00', '5.00', NULL, NULL, NULL);
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('15', 'Accessories', 'Zeiss Anti-Fog Wipes (Box of 30)', '4', '35.00', '25.00', NULL, NULL, 'uploads/products/prod_6a3bca10eb7a8.jpg');
INSERT INTO `product` (`PRODUCT_ID`, `CATEGORY`, `BRAND_NAME`, `STOCK_QUANTITY`, `UNIT_PRICE`, `MINIMUM_PRICE`, `SUPPLIER_ID`, `EXPIRY_DATE`, `PRODUCT_IMAGE`) VALUES ('16', 'Frame', 'Stepper Frame', '20', '200.00', '100.00', '3', NULL, NULL);

-- Table structure for table `product_category`
DROP TABLE IF EXISTS `product_category`;
CREATE TABLE `product_category` (
  `CATEGORY_ID` int(11) NOT NULL AUTO_INCREMENT,
  `CATEGORY_NAME` varchar(100) NOT NULL,
  PRIMARY KEY (`CATEGORY_ID`),
  UNIQUE KEY `CATEGORY_NAME` (`CATEGORY_NAME`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `product_category`
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('7', 'Accessories');
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('2', 'Contact Lens');
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('5', 'Contact Lenses');
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('1', 'Frame');
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('4', 'Frames');
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('3', 'Solution');
INSERT INTO `product_category` (`CATEGORY_ID`, `CATEGORY_NAME`) VALUES ('6', 'Solutions');

-- Table structure for table `sales`
DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `SALE_ID` int(11) NOT NULL AUTO_INCREMENT,
  `PATIENT_ID` int(11) NOT NULL,
  `STAFF_ID` int(11) NOT NULL,
  `SALE_DATE` datetime DEFAULT current_timestamp(),
  `TOTAL_AMOUNT` decimal(10,2) NOT NULL,
  `PAID_AMOUNT` decimal(10,2) DEFAULT 0.00,
  `PAYMENT_METHOD` enum('Cash','Card','E-wallet','Online Banking') NOT NULL,
  `PAYMENT_STATUS` enum('Pending','Partial','Completed') DEFAULT 'Pending',
  PRIMARY KEY (`SALE_ID`),
  KEY `fk_sales_patient` (`PATIENT_ID`),
  KEY `fk_sales_staff` (`STAFF_ID`),
  CONSTRAINT `fk_sales_patient` FOREIGN KEY (`PATIENT_ID`) REFERENCES `patient` (`PATIENT_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_staff` FOREIGN KEY (`STAFF_ID`) REFERENCES `user` (`USER_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `sales`
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('4', '1', '1', '2026-04-30 09:35:54', '630.00', '60.00', 'Card', 'Completed');
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('7', '15', '1', '2026-05-17 19:10:29', '110.00', '110.00', 'Card', 'Completed');
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('8', '14', '1', '2026-05-28 12:34:06', '180.00', '175.00', 'Cash', 'Partial');
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('11', '19', '1', '2026-06-22 17:53:48', '120.00', '120.00', 'Cash', 'Completed');
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('12', '19', '1', '2026-06-22 18:07:29', '400.00', '200.00', 'Card', 'Partial');
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('13', '20', '1', '2026-06-22 19:03:20', '0.00', '0.00', 'Cash', 'Completed');
INSERT INTO `sales` (`SALE_ID`, `PATIENT_ID`, `STAFF_ID`, `SALE_DATE`, `TOTAL_AMOUNT`, `PAID_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES ('14', '18', '4', '2026-06-24 13:24:11', '120.00', '120.00', 'Cash', 'Completed');

-- Table structure for table `sales_item`
DROP TABLE IF EXISTS `sales_item`;
CREATE TABLE `sales_item` (
  `SALES_ITEM_ID` int(11) NOT NULL AUTO_INCREMENT,
  `SALE_ID` int(11) NOT NULL,
  `PRODUCT_ID` int(11) NOT NULL,
  `QUANTITY` int(11) NOT NULL,
  PRIMARY KEY (`SALES_ITEM_ID`),
  KEY `fk_item_sale` (`SALE_ID`),
  KEY `fk_item_prod` (`PRODUCT_ID`),
  CONSTRAINT `fk_item_prod` FOREIGN KEY (`PRODUCT_ID`) REFERENCES `product` (`PRODUCT_ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_sale` FOREIGN KEY (`SALE_ID`) REFERENCES `sales` (`SALE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `sales_item`
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('7', '4', '3', '1');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('8', '4', '2', '1');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('11', '7', '3', '1');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('12', '8', '9', '1');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('16', '11', '6', '1');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('17', '12', '16', '2');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('18', '13', '9', '3');
INSERT INTO `sales_item` (`SALES_ITEM_ID`, `SALE_ID`, `PRODUCT_ID`, `QUANTITY`) VALUES ('19', '14', '6', '1');

-- Table structure for table `supplier`
DROP TABLE IF EXISTS `supplier`;
CREATE TABLE `supplier` (
  `SUPPLIER_ID` int(11) NOT NULL AUTO_INCREMENT,
  `COMPANY_NAME` varchar(255) NOT NULL,
  `CONTACT_PERSON` varchar(100) DEFAULT NULL,
  `PHONE_NUMBER` varchar(20) DEFAULT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`SUPPLIER_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `supplier`
INSERT INTO `supplier` (`SUPPLIER_ID`, `COMPANY_NAME`, `CONTACT_PERSON`, `PHONE_NUMBER`, `EMAIL`) VALUES ('1', 'Visionary Wholesale', 'Mr. Wong', '03-55667788', 'sales@visionary.com.my');
INSERT INTO `supplier` (`SUPPLIER_ID`, `COMPANY_NAME`, `CONTACT_PERSON`, `PHONE_NUMBER`, `EMAIL`) VALUES ('2', 'Luxottica Group', 'Alice Green', '03-88990011', 'orders@luxottica.com');
INSERT INTO `supplier` (`SUPPLIER_ID`, `COMPANY_NAME`, `CONTACT_PERSON`, `PHONE_NUMBER`, `EMAIL`) VALUES ('3', 'Hor Frame SDN BHD', 'Hor Bing Chang', '0176578978', '');
INSERT INTO `supplier` (`SUPPLIER_ID`, `COMPANY_NAME`, `CONTACT_PERSON`, `PHONE_NUMBER`, `EMAIL`) VALUES ('4', 'Nizan Sdn Bhd', 'Mohamad Nizan', '0196630607', '');

-- Table structure for table `user`
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `USER_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `ROLE` varchar(50) NOT NULL,
  `FIRST_LOGIN_OTP` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`USER_ID`),
  UNIQUE KEY `EMAIL` (`EMAIL`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `user`
INSERT INTO `user` (`USER_ID`, `NAME`, `EMAIL`, `PASSWORD`, `ROLE`, `FIRST_LOGIN_OTP`) VALUES ('1', 'Dr. Sarah Chen', 'yinghuai180704@gmail.com', '$2y$10$VYF2oZNPkPrc6kHRB/UiLuPJEZQYxSQ/wZB8sbco2Js.Ej6CWs8NC', 'Optometrist', '0');
INSERT INTO `user` (`USER_ID`, `NAME`, `EMAIL`, `PASSWORD`, `ROLE`, `FIRST_LOGIN_OTP`) VALUES ('3', 'Admin User', 'b032420034@student.utem.edu.my', '$2y$10$BiKE4htrB6HpPF7B3j0Nh.3PzzYSEEP8QJylsYp3ScAHLK5X04O4m', 'Admin', '0');
INSERT INTO `user` (`USER_ID`, `NAME`, `EMAIL`, `PASSWORD`, `ROLE`, `FIRST_LOGIN_OTP`) VALUES ('4', 'Farah', 'damia.f0411@gmail.com', '$2y$10$ydtqSrCEy/r.HqMtW0.WY.N5ugEbGtlb.6v1ecBcnf0dSj25fjLA.', 'staff', '0');
INSERT INTO `user` (`USER_ID`, `NAME`, `EMAIL`, `PASSWORD`, `ROLE`, `FIRST_LOGIN_OTP`) VALUES ('5', 'hor ying huai', 'b032420042@student.utem.edu.my', '$2y$10$uwKbamgEpmgrf8pyGa.YGusUHyTybHEjMws1nABHtd9W1nuSUHuam', 'Staff', '0');

SET FOREIGN_KEY_CHECKS=1;
