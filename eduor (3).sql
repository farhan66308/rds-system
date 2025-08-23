-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2025 at 08:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eduor`
--

-- --------------------------------------------------------

--
-- Table structure for table `2fa`
--

CREATE TABLE `2fa` (
  `UserID` varchar(12) DEFAULT NULL,
  `TwoFASecret` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academicinformation`
--

CREATE TABLE `academicinformation` (
  `UserID` varchar(12) NOT NULL,
  `CGPA` int(4) NOT NULL,
  `PassCredits` int(3) NOT NULL,
  `PendingCredits` int(3) NOT NULL,
  `CurrentSemester` varchar(12) DEFAULT NULL,
  `StartSemester` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academicinformation`
--

INSERT INTO `academicinformation` (`UserID`, `CGPA`, `PassCredits`, `PendingCredits`, `CurrentSemester`, `StartSemester`) VALUES
('1222', 4, 91, 39, 'Summer 2025', 'Summer 2022'),
('1234', 3, 82, 48, 'Summer 2025', 'Spring 2022');

-- --------------------------------------------------------

--
-- Table structure for table `advising`
--

CREATE TABLE `advising` (
  `UserID` varchar(12) NOT NULL,
  `Semester` varchar(50) NOT NULL,
  `AdvisingTime` date NOT NULL,
  `Notified` enum('Yes','NO','N/A') NOT NULL,
  `AdvisingTime2` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `annoucement`
--

CREATE TABLE `annoucement` (
  `AnnouceID` varchar(20) NOT NULL,
  `AuthorUserID` varchar(12) DEFAULT NULL,
  `FromCourseID` varchar(12) DEFAULT NULL,
  `Description` varchar(200) DEFAULT NULL,
  `DateUpload` date DEFAULT NULL,
  `Title` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annoucement`
--

INSERT INTO `annoucement` (`AnnouceID`, `AuthorUserID`, `FromCourseID`, `Description`, `DateUpload`, `Title`) VALUES
('6APygVsaCDsz', '2022', 'CSE115', 'Everyone is requesting to show their project tomorrow', '2025-08-23', 'PROJECT'),
('ZMtGEfcKMWbI', '1441', 'CSE115', 'q', '2025-08-09', 'Test');

-- --------------------------------------------------------

--
-- Table structure for table `assesments`
--

CREATE TABLE `assesments` (
  `AssessmentID` varchar(20) NOT NULL,
  `AuthorID` varchar(12) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Type` varchar(20) NOT NULL,
  `PublishDate` date NOT NULL,
  `Deadline` date NOT NULL,
  `Description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` varchar(18) NOT NULL,
  `CourseID` varchar(12) DEFAULT NULL,
  `StudentID` varchar(12) DEFAULT NULL,
  `LectureNumber` int(2) DEFAULT NULL,
  `Status` enum('Present','Absent','Late','Excused') DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Section` int(2) DEFAULT NULL,
  `FacultyID` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`AttendanceID`, `CourseID`, `StudentID`, `LectureNumber`, `Status`, `Date`, `Section`, `FacultyID`) VALUES
('ATDN22082025214650', 'CSE115', '1234', 1, '', '2025-08-22', 1, '2022'),
('ATDN22082025214846', 'CSE115', '1234', 2, '', '2025-08-22', 1, '2022'),
('ATDN22082025214914', 'CSE115', '1234', 3, '', '2025-08-22', 1, '2022');

-- --------------------------------------------------------

--
-- Table structure for table `courseproperty`
--

CREATE TABLE `courseproperty` (
  `StructureID` varchar(12) DEFAULT NULL,
  `CourseID` varchar(12) DEFAULT NULL,
  `Type` varchar(12) DEFAULT NULL,
  `Content` varchar(350) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courseproperty`
--

INSERT INTO `courseproperty` (`StructureID`, `CourseID`, `Type`, `Content`) VALUES
('STR_68a89ecd', NULL, 'Modules', '/resource/68a93ae993ffe.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `CourseID` varchar(12) NOT NULL,
  `CourseName` varchar(50) DEFAULT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `credits` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`CourseID`, `CourseName`, `Description`, `credits`) VALUES
('CSE115', 'Programming Language I', 'This is the first course in the computer science programming and is required for all computer scienc', 3),
('CSE173', 'Discrete Mathematics', 'This course introduces the students to discrete mathematical structures. Topics include sets, relati', 3),
('CSE311', 'Database Systems', 'This course introduces students with database management systems for the first time in their undergr', 3),
('CSE327', 'Software Engineering', 'Follows the software life cycle – from requirement, specification, and design phases through the con', 3),
('CSE373', 'Design and Analysis of Algorithms', 'This course introduces basic methods for the design and analysis of efficient algorithms emphasizing', 3),
('ENG102', 'Introduction to Composition', 'Development of integrated language skills with special focus on the mechanics of the writing process', 3);

-- --------------------------------------------------------

--
-- Table structure for table `coursestructure`
--

CREATE TABLE `coursestructure` (
  `StructureID` varchar(12) NOT NULL,
  `Section` int(2) DEFAULT NULL,
  `CourseID` varchar(12) DEFAULT NULL,
  `Syllabus` int(1) DEFAULT NULL,
  `Assignments` int(1) DEFAULT NULL,
  `Modules` int(1) DEFAULT NULL,
  `Annoucements` int(1) DEFAULT NULL,
  `Files` int(1) DEFAULT NULL,
  `People` int(1) DEFAULT NULL,
  `Grades` int(1) DEFAULT NULL,
  `Discussions` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coursestructure`
--

INSERT INTO `coursestructure` (`StructureID`, `Section`, `CourseID`, `Syllabus`, `Assignments`, `Modules`, `Annoucements`, `Files`, `People`, `Grades`, `Discussions`) VALUES
('STR_68a89ecd', 1, 'CSE115', 1, 1, 1, 0, 0, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `enrolled`
--

CREATE TABLE `enrolled` (
  `CourseID` varchar(12) NOT NULL,
  `UserID` varchar(12) NOT NULL,
  `Role` varchar(20) NOT NULL,
  `Section` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrolled`
--

INSERT INTO `enrolled` (`CourseID`, `UserID`, `Role`, `Section`) VALUES
('CSE115', '1001', 'Student', 1),
('CSE115', '220820253571', 'Student', 1),
('CSE115', '2022', 'Faculty', 1),
('CSE115', '1222', 'Student', 1),
('CSE327', '2023', 'Faculty', 1),
('CSE115', '1234', 'Student', 1);

-- --------------------------------------------------------

--
-- Table structure for table `facultyinfo`
--

CREATE TABLE `facultyinfo` (
  `FacultyCode` varchar(5) NOT NULL,
  `UserID` varchar(12) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Avatar` varchar(200) NOT NULL,
  `Bio` varchar(200) NOT NULL,
  `Department` varchar(50) NOT NULL,
  `Role` varchar(50) NOT NULL,
  `Office` varchar(50) NOT NULL,
  `Website` varchar(50) DEFAULT NULL,
  `EducationInfo` varchar(250) NOT NULL,
  `SkillSet` varchar(350) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facultyinfo`
--

INSERT INTO `facultyinfo` (`FacultyCode`, `UserID`, `Name`, `Avatar`, `Bio`, `Department`, `Role`, `Office`, `Website`, `EducationInfo`, `SkillSet`) VALUES
('IQN', '2023', 'Iqtidar Newaz', 'https://dummyimage.com/150x150/cccccc/000000&text=Avatar', 'MS in Computer Engineering, Florida International University (FIU), USA.\r\n\r\nBS in Computer Science, Islamic University of Technology (IUT), Bangladesh.', 'ECE', 'Faculty', 'SAC1140', NULL, '', ''),
('MLE', '2022', 'Md. Lufte Elahi', 'https://dummyimage.com/150x150/cccccc/000000&text=Avatar', 'ASfas', 'ECE', 'Faculty', 'SAC1111', 'https://ece.northsouth.edu/~lutfe.elahi/', 'MSc in Computer Engineering  University of Texas at Arlington, USA', 'SAFSf');

-- --------------------------------------------------------

--
-- Table structure for table `field`
--

CREATE TABLE `field` (
  `FieldID` varchar(12) NOT NULL,
  `UserID` varchar(12) NOT NULL,
  `FieldName` varchar(100) NOT NULL,
  `FieldSubTitle` varchar(100) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `SetField1` varchar(150) DEFAULT NULL,
  `SetField2` varchar(150) DEFAULT NULL,
  `SetField3` varchar(150) DEFAULT NULL,
  `Description` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `field`
--

INSERT INTO `field` (`FieldID`, `UserID`, `FieldName`, `FieldSubTitle`, `Date`, `SetField1`, `SetField2`, `SetField3`, `Description`) VALUES
('', '2023', 'Research Interests', '', NULL, NULL, NULL, NULL, 'Network Security\r\nWeb Security\r\nBrowser Security\r\nMedical Device Security and Health IoT'),
('230820251751', '2022', 'Assessing climate-induced agricultural vulnerable coastal communities of Bangladesh using machine le', '', NULL, '', '', '', 'TRTASAS');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `UserID` varchar(12) DEFAULT NULL,
  `CourseID` varchar(12) DEFAULT NULL,
  `Grade` varchar(12) DEFAULT NULL,
  `Semester` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`UserID`, `CourseID`, `Grade`, `Semester`) VALUES
('1234', 'CSE115', 'F', 'Spring 2025'),
('1234', 'CSE115', 'F', 'Spring 2025'),
('1234', 'CSE115', 'A-', 'Spring 2025');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` varchar(12) NOT NULL,
  `Info` varchar(250) DEFAULT NULL,
  `Amount` int(10) DEFAULT NULL,
  `UserID` varchar(12) DEFAULT NULL,
  `Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `preadvise`
--

CREATE TABLE `preadvise` (
  `UserID` varchar(12) DEFAULT NULL,
  `CourseID` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `preadvise`
--

INSERT INTO `preadvise` (`UserID`, `CourseID`) VALUES
('1234', 'CSE115'),
('1234', 'CSE173'),
('1234', 'CSE327');

-- --------------------------------------------------------

--
-- Table structure for table `studentinfo`
--

CREATE TABLE `studentinfo` (
  `UserID` varchar(12) DEFAULT NULL,
  `StudentID` varchar(10) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Gender` varchar(7) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `CitizenID` varchar(20) DEFAULT NULL,
  `Passport` varchar(20) DEFAULT NULL,
  `Nationality` varchar(15) NOT NULL,
  `Blood` varchar(5) NOT NULL,
  `Addresss` varchar(150) NOT NULL,
  `EmergencyContact` varchar(12) NOT NULL,
  `Program` varchar(40) NOT NULL,
  `MotherName` varchar(100) DEFAULT NULL,
  `FatherName` varchar(100) DEFAULT NULL,
  `ParentNationality` varchar(100) DEFAULT NULL,
  `MotherOccupation` varchar(50) DEFAULT NULL,
  `FatherOccupation` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentinfo`
--

INSERT INTO `studentinfo` (`UserID`, `StudentID`, `FirstName`, `LastName`, `Gender`, `DateOfBirth`, `CitizenID`, `Passport`, `Nationality`, `Blood`, `Addresss`, `EmergencyContact`, `Program`, `MotherName`, `FatherName`, `ParentNationality`, `MotherOccupation`, `FatherOccupation`) VALUES
('1234', '2212273204', 'Farhan', 'Tahmeed', 'Male', '0000-00-00', '19935791', 'BFG12412', 'Bangladesh', 'A +(v', 'Bashundhara R/A', '01712', 'Computer Science Engineering', 'Khadija Kabir', 'Alamgir Kabir', 'Bangladesh', 'Business', 'Job'),
('1222', '2412512151', 'Pritom', 'Deb', 'Male', '0000-00-00', '1241241251', NULL, 'Bangladesh', 'B(+ve', 'Puran Dhaka, Wari', '1245125151', 'Computer Science Engineering', NULL, NULL, 'Bangladeshi', NULL, NULL),
('220820253571', '21412414', 'Samia', 'Mozumder', 'Female', '2025-08-15', '12512516561', 'VASF12712', 'Bangladesh', 'O+ve', '', '', 'Computer Science', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `TicketID` varchar(40) NOT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `FromUserID` varchar(12) DEFAULT NULL,
  `Status` enum('SOLVED','PENDING') DEFAULT NULL,
  `Feedback` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`TicketID`, `Description`, `FromUserID`, `Status`, `Feedback`) VALUES
('230820250481', 'My advising system is not working', '1234', 'PENDING', NULL),
('230820259374', 'Test', '1234', 'PENDING', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` varchar(12) NOT NULL,
  `Phone` varchar(12) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Password` varchar(50) DEFAULT NULL,
  `UserFlag` int(1) DEFAULT NULL,
  `2fa` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Phone`, `Email`, `Password`, `UserFlag`, `2fa`) VALUES
('1001', '10991', 'ftmed@gmail.com', 'ftmed', 1, 0),
('1011', '01899114', 'adminfarhan@eduor.edu', 'h123', 3, 0),
('1222', '019141211', 'pritom.deb@eduor.edu', 'pritom', 1, 0),
('1234', '1241', 'farhan.tahmeed12@gmail.com', 'h123', 1, 0),
('1441', '0131551551', 'osman.gani@eduor.edu', 'osman', 2, 0),
('2022', '019191241', 'mle@eduor.edu', 'mlesir', 2, 0),
('2023', '0165122412', 'iqn@eduor.edu', 'iqnsir', 2, 0),
('220820253571', '914', 'samia.mozumder@eduor.edu', 'samia12', 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `2fa`
--
ALTER TABLE `2fa`
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `academicinformation`
--
ALTER TABLE `academicinformation`
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `advising`
--
ALTER TABLE `advising`
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `annoucement`
--
ALTER TABLE `annoucement`
  ADD PRIMARY KEY (`AnnouceID`),
  ADD KEY `AuthorUserID` (`AuthorUserID`),
  ADD KEY `FromCourseID` (`FromCourseID`);

--
-- Indexes for table `assesments`
--
ALTER TABLE `assesments`
  ADD PRIMARY KEY (`AssessmentID`),
  ADD KEY `AuthorID` (`AuthorID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD KEY `CourseID` (`CourseID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `fk_attendance_faculty` (`FacultyID`);

--
-- Indexes for table `courseproperty`
--
ALTER TABLE `courseproperty`
  ADD KEY `StructureID` (`StructureID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`CourseID`);

--
-- Indexes for table `coursestructure`
--
ALTER TABLE `coursestructure`
  ADD PRIMARY KEY (`StructureID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `enrolled`
--
ALTER TABLE `enrolled`
  ADD KEY `CourseID` (`CourseID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `facultyinfo`
--
ALTER TABLE `facultyinfo`
  ADD PRIMARY KEY (`FacultyCode`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `field`
--
ALTER TABLE `field`
  ADD PRIMARY KEY (`FieldID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD KEY `UserID` (`UserID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `preadvise`
--
ALTER TABLE `preadvise`
  ADD KEY `UserID` (`UserID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `studentinfo`
--
ALTER TABLE `studentinfo`
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`TicketID`),
  ADD KEY `FromUserID` (`FromUserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `2fa`
--
ALTER TABLE `2fa`
  ADD CONSTRAINT `2fa_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `academicinformation`
--
ALTER TABLE `academicinformation`
  ADD CONSTRAINT `academicinformation_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `advising`
--
ALTER TABLE `advising`
  ADD CONSTRAINT `advising_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `annoucement`
--
ALTER TABLE `annoucement`
  ADD CONSTRAINT `annoucement_ibfk_1` FOREIGN KEY (`AuthorUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `annoucement_ibfk_2` FOREIGN KEY (`FromCourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `assesments`
--
ALTER TABLE `assesments`
  ADD CONSTRAINT `assesments_ibfk_1` FOREIGN KEY (`AuthorID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`StudentID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_faculty` FOREIGN KEY (`FacultyID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courseproperty`
--
ALTER TABLE `courseproperty`
  ADD CONSTRAINT `courseproperty_ibfk_1` FOREIGN KEY (`StructureID`) REFERENCES `coursestructure` (`StructureID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `courseproperty_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `coursestructure` (`StructureID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `coursestructure`
--
ALTER TABLE `coursestructure`
  ADD CONSTRAINT `coursestructure_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `enrolled`
--
ALTER TABLE `enrolled`
  ADD CONSTRAINT `enrolled_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enrolled_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `facultyinfo`
--
ALTER TABLE `facultyinfo`
  ADD CONSTRAINT `facultyinfo_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `field`
--
ALTER TABLE `field`
  ADD CONSTRAINT `field_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `preadvise`
--
ALTER TABLE `preadvise`
  ADD CONSTRAINT `preadvise_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `preadvise_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `courses` (`CourseID`);

--
-- Constraints for table `studentinfo`
--
ALTER TABLE `studentinfo`
  ADD CONSTRAINT `studentinfo_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`FromUserID`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
