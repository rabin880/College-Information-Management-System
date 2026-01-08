-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2025 at 01:49 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cims`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminlog`
--

CREATE TABLE `adminlog` (
  `aid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adminlog`
--

INSERT INTO `adminlog` (`aid`, `username`, `password`, `name`) VALUES
(1, 'admin', 'admin123', 'Sudarshan');

-- --------------------------------------------------------

--
-- Table structure for table `basicinfo`
--

CREATE TABLE `basicinfo` (
  `bid` int(10) NOT NULL,
  `name` varchar(200) NOT NULL,
  `logo` varchar(200) NOT NULL,
  `aboutus` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `basicinfo`
--

INSERT INTO `basicinfo` (`bid`, `name`, `logo`, `aboutus`) VALUES
(3, 'Aadikavi Bhanubhakta Campus New', 'logo.png', 'New College  P1');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `batch_id` int(10) NOT NULL,
  `batch_year` year(4) NOT NULL,
  `faculty_id` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`batch_id`, `batch_year`, `faculty_id`) VALUES
(20, '2023', 9),
(21, '2024', 9),
(24, '2024', 10),
(26, '2023', 11),
(27, '2024', 11),
(29, '2023', 12),
(30, '2024', 12);

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `cid` int(10) NOT NULL,
  `classname` varchar(200) NOT NULL,
  `faculty` varchar(200) NOT NULL,
  `batch` int(10) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `rank` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`cid`, `classname`, `faculty`, `batch`, `status`, `rank`) VALUES
(53, 'BICTE 1st Sem', '9', 20, 2, 1),
(54, 'BICTE 2nd Sem', '9', 20, 1, 2),
(55, 'BICTE 3rd Sem', '9', 20, 0, 3),
(56, 'BICTE 1st Sem', '9', 21, 1, 1),
(57, 'csit 2024 1st sem', '10', 24, 1, 1),
(58, 'BBA 1st year', '11', 26, 1, 1),
(59, 'BBA 2nd year', '11', 26, 0, 2),
(60, 'BBA 1st year', '11', 27, 1, 1),
(61, 'Bed Eng 1 year', '12', 29, 1, 1),
(62, 'Bed Eng 2 year', '12', 29, 0, 2),
(63, 'Bed Eng 1 year', '12', 30, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `poster` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `description`, `date`, `time`, `location`, `poster`) VALUES
(0, 'abc', 'df', '2024-12-28', '10:26:00', 'Pokhara', 'uploads/school.jpg'),
(0, 'abc', 'df', '2024-12-28', '10:26:00', 'Pokhara', 'uploads/school.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `exam_id` int(11) NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `exam_date` date NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `batch` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam`
--

INSERT INTO `exam` (`exam_id`, `exam_name`, `exam_date`, `faculty_id`, `class_id`, `duration`, `remarks`, `batch`) VALUES
(20, 'Midterm', '2025-03-31', 9, 53, 3, 'Good Luck', 20),
(21, 'Final ', '2025-03-31', 9, 53, 3, 'Good Luck', 20),
(22, 'Midterm', '2025-03-31', 9, 54, 3, 'Good Luck', 20);

-- --------------------------------------------------------

--
-- Table structure for table `exam_subjects`
--

CREATE TABLE `exam_subjects` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_subjects`
--

INSERT INTO `exam_subjects` (`id`, `exam_id`, `subject_id`) VALUES
(52, 20, 102),
(53, 20, 103),
(54, 20, 104),
(55, 20, 105),
(56, 20, 106),
(57, 21, 102),
(58, 21, 103),
(59, 21, 104),
(60, 21, 105),
(61, 21, 106),
(62, 22, 107),
(63, 22, 108),
(64, 22, 109),
(65, 22, 110),
(66, 22, 111);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `fcid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Year','Semester') NOT NULL,
  `total_periods` int(11) NOT NULL,
  `status` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`fcid`, `name`, `type`, `total_periods`, `status`) VALUES
(9, 'BICTE', 'Semester', 0, 1),
(10, 'CSIT', 'Semester', 0, 1),
(11, 'BBA', 'Year', 0, 1),
(12, 'B.Ed English', 'Year', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `image`, `created_at`) VALUES
(6, 'jkgjkzhgc jzx', '1742915849_download (5).jfif', '2025-03-25 15:17:29'),
(7, 'jkgjkzhgc jzx', '1742915849_download (4).jfif', '2025-03-25 15:17:29'),
(8, 'jkgjkzhgc jzx', '1742915849_download (3).jfif', '2025-03-25 15:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `mark_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `mark` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`mark_id`, `exam_id`, `student_id`, `subject_id`, `mark`) VALUES
(388, 20, 73, 102, 44.00),
(389, 20, 73, 103, 44.00),
(390, 20, 73, 104, 45.00),
(391, 20, 73, 105, 30.00),
(392, 20, 73, 106, 34.00),
(393, 20, 74, 102, 44.00),
(394, 20, 74, 103, 45.00),
(395, 20, 74, 104, 46.00),
(396, 20, 74, 105, 31.00),
(397, 20, 74, 106, 36.00),
(398, 20, 75, 102, 44.00),
(399, 20, 75, 103, 46.00),
(400, 20, 75, 104, 47.00),
(401, 20, 75, 105, 32.00),
(402, 20, 75, 106, 35.00),
(403, 20, 76, 102, 44.00),
(404, 20, 76, 103, 45.00),
(405, 20, 76, 104, 48.00),
(406, 20, 76, 105, 33.00),
(407, 20, 76, 106, 36.00),
(408, 20, 77, 102, 44.00),
(409, 20, 77, 103, 65.00),
(410, 20, 77, 104, 33.00),
(411, 20, 77, 105, 34.00),
(412, 20, 77, 106, 37.00),
(413, 20, 78, 102, 44.00),
(414, 20, 78, 103, 55.00),
(415, 20, 78, 104, 34.00),
(416, 20, 78, 105, 35.00),
(417, 20, 78, 106, 38.00),
(418, 20, 79, 102, 44.00),
(419, 20, 79, 103, 56.00),
(420, 20, 79, 104, 35.00),
(421, 20, 79, 105, 36.00),
(422, 20, 79, 106, 39.00),
(423, 20, 80, 102, 44.00),
(424, 20, 80, 103, 67.00),
(425, 20, 80, 104, 36.00),
(426, 20, 80, 105, 37.00),
(427, 20, 80, 106, 40.00),
(428, 20, 81, 102, 44.00),
(429, 20, 81, 103, 78.00),
(430, 20, 81, 104, 37.00),
(431, 20, 81, 105, 38.00),
(432, 20, 81, 106, 41.00),
(433, 20, 82, 102, 44.00),
(434, 20, 82, 103, 76.00),
(435, 20, 82, 104, 38.00),
(436, 20, 82, 105, 39.00),
(437, 20, 82, 106, 42.00),
(438, 20, 83, 102, 44.00),
(439, 20, 83, 103, 76.00),
(440, 20, 83, 104, 39.00),
(441, 20, 83, 105, 33.00),
(442, 20, 83, 106, 43.00),
(443, 20, 84, 102, 44.00),
(444, 20, 84, 103, 75.00),
(445, 20, 84, 104, 40.00),
(446, 20, 84, 105, 34.00),
(447, 20, 84, 106, 44.00),
(448, 20, 85, 102, 44.00),
(449, 20, 85, 103, 73.00),
(450, 20, 85, 104, 41.00),
(451, 20, 85, 105, 40.00),
(452, 20, 85, 106, 45.00),
(453, 20, 86, 102, 44.00),
(454, 20, 86, 103, 45.00),
(455, 20, 86, 104, 42.00),
(456, 20, 86, 105, 24.00),
(457, 20, 86, 106, 46.00),
(458, 20, 87, 102, 44.00),
(459, 20, 87, 103, 56.00),
(460, 20, 87, 104, 43.00),
(461, 20, 87, 105, 34.00),
(462, 20, 87, 106, 47.00),
(463, 20, 88, 102, 44.00),
(464, 20, 88, 103, 67.00),
(465, 20, 88, 104, 44.00),
(466, 20, 88, 105, 36.00),
(467, 20, 88, 106, 48.00),
(468, 20, 89, 102, 44.00),
(469, 20, 89, 103, 57.00),
(470, 20, 89, 104, 45.00),
(471, 20, 89, 105, 40.00),
(472, 20, 89, 106, 47.00),
(473, 20, 90, 102, 44.00),
(474, 20, 90, 103, 66.00),
(475, 20, 90, 104, 46.00),
(476, 20, 90, 105, 34.00),
(477, 20, 90, 106, 45.00),
(478, 21, 73, 102, 40.00),
(479, 21, 73, 103, 43.00),
(480, 21, 73, 104, 45.00),
(481, 21, 73, 105, 34.00),
(482, 21, 73, 106, 44.00),
(483, 21, 74, 102, 41.00),
(484, 21, 74, 103, 54.00),
(485, 21, 74, 104, 43.00),
(486, 21, 74, 105, 40.00),
(487, 21, 74, 106, 44.00),
(488, 21, 75, 102, 42.00),
(489, 21, 75, 103, 34.00),
(490, 21, 75, 104, 50.00),
(491, 21, 75, 105, 34.00),
(492, 21, 75, 106, 55.00),
(493, 21, 76, 102, 43.00),
(494, 21, 76, 103, 34.00),
(495, 21, 76, 104, 32.00),
(496, 21, 76, 105, 33.00),
(497, 21, 76, 106, 55.00),
(498, 21, 77, 102, 44.00),
(499, 21, 77, 103, 54.00),
(500, 21, 77, 104, 34.00),
(501, 21, 77, 105, 33.00),
(502, 21, 77, 106, 55.00),
(503, 21, 78, 102, 44.00),
(504, 21, 78, 103, 56.00),
(505, 21, 78, 104, 35.00),
(506, 21, 78, 105, 33.00),
(507, 21, 78, 106, 55.00),
(508, 21, 79, 102, 44.00),
(509, 21, 79, 103, 56.00),
(510, 21, 79, 104, 36.00),
(511, 21, 79, 105, 33.00),
(512, 21, 79, 106, 55.00),
(513, 21, 80, 102, 44.00),
(514, 21, 80, 103, 45.00),
(515, 21, 80, 104, 37.00),
(516, 21, 80, 105, 33.00),
(517, 21, 80, 106, 55.00),
(518, 21, 81, 102, 44.00),
(519, 21, 81, 103, 45.00),
(520, 21, 81, 104, 38.00),
(521, 21, 81, 105, 33.00),
(522, 21, 81, 106, 55.00),
(523, 21, 82, 102, 44.00),
(524, 21, 82, 103, 65.00),
(525, 21, 82, 104, 39.00),
(526, 21, 82, 105, 33.00),
(527, 21, 82, 106, 55.00),
(528, 21, 83, 102, 44.00),
(529, 21, 83, 103, 65.00),
(530, 21, 83, 104, 40.00),
(531, 21, 83, 105, 33.00),
(532, 21, 83, 106, 55.00),
(533, 21, 84, 102, 44.00),
(534, 21, 84, 103, 67.00),
(535, 21, 84, 104, 44.00),
(536, 21, 84, 105, 33.00),
(537, 21, 84, 106, 55.00),
(538, 21, 85, 102, 44.00),
(539, 21, 85, 103, 76.00),
(540, 21, 85, 104, 43.00),
(541, 21, 85, 105, 33.00),
(542, 21, 85, 106, 55.00),
(543, 21, 86, 102, 44.00),
(544, 21, 86, 103, 54.00),
(545, 21, 86, 104, 34.00),
(546, 21, 86, 105, 33.00),
(547, 21, 86, 106, 55.00),
(548, 21, 87, 102, 44.00),
(549, 21, 87, 103, 78.00),
(550, 21, 87, 104, 45.00),
(551, 21, 87, 105, 33.00),
(552, 21, 87, 106, 55.00),
(553, 21, 88, 102, 44.00),
(554, 21, 88, 103, 45.00),
(555, 21, 88, 104, 43.00),
(556, 21, 88, 105, 33.00),
(557, 21, 88, 106, 55.00),
(558, 21, 89, 102, 44.00),
(559, 21, 89, 103, 56.00),
(560, 21, 89, 104, 45.00),
(561, 21, 89, 105, 33.00),
(562, 21, 89, 106, 55.00),
(563, 21, 90, 102, 44.00),
(564, 21, 90, 103, 45.00),
(565, 21, 90, 104, 31.00),
(566, 21, 90, 105, 33.00),
(567, 21, 90, 106, 55.00),
(568, 22, 73, 107, 44.00),
(569, 22, 73, 108, 33.00),
(570, 22, 73, 109, 55.00),
(571, 22, 73, 110, 30.00),
(572, 22, 73, 111, 44.00),
(573, 22, 74, 107, 44.00),
(574, 22, 74, 108, 33.00),
(575, 22, 74, 109, 56.00),
(576, 22, 74, 110, 31.00),
(577, 22, 74, 111, 45.00),
(578, 22, 75, 107, 44.00),
(579, 22, 75, 108, 33.00),
(580, 22, 75, 109, 57.00),
(581, 22, 75, 110, 33.00),
(582, 22, 75, 111, 46.00),
(583, 22, 76, 107, 44.00),
(584, 22, 76, 108, 33.00),
(585, 22, 76, 109, 43.00),
(586, 22, 76, 110, 34.00),
(587, 22, 76, 111, 46.00),
(588, 22, 77, 107, 44.00),
(589, 22, 77, 108, 33.00),
(590, 22, 77, 109, 44.00),
(591, 22, 77, 110, 35.00),
(592, 22, 77, 111, 45.00),
(593, 22, 78, 107, 44.00),
(594, 22, 78, 108, 33.00),
(595, 22, 78, 109, 45.00),
(596, 22, 78, 110, 36.00),
(597, 22, 78, 111, 45.00),
(598, 22, 79, 107, 44.00),
(599, 22, 79, 108, 33.00),
(600, 22, 79, 109, 56.00),
(601, 22, 79, 110, 37.00),
(602, 22, 79, 111, 45.00),
(603, 22, 80, 107, 44.00),
(604, 22, 80, 108, 33.00),
(605, 22, 80, 109, 47.00),
(606, 22, 80, 110, 38.00),
(607, 22, 80, 111, 44.00),
(608, 22, 81, 107, 44.00),
(609, 22, 81, 108, 33.00),
(610, 22, 81, 109, 48.00),
(611, 22, 81, 110, 39.00),
(612, 22, 81, 111, 44.00),
(613, 22, 82, 107, 44.00),
(614, 22, 82, 108, 33.00),
(615, 22, 82, 109, 49.00),
(616, 22, 82, 110, 33.00),
(617, 22, 82, 111, 44.00),
(618, 22, 83, 107, 44.00),
(619, 22, 83, 108, 33.00),
(620, 22, 83, 109, 50.00),
(621, 22, 83, 110, 34.00),
(622, 22, 83, 111, 44.00),
(623, 22, 84, 107, 44.00),
(624, 22, 84, 108, 33.00),
(625, 22, 84, 109, 51.00),
(626, 22, 84, 110, 22.00),
(627, 22, 84, 111, 44.00),
(628, 22, 85, 107, 44.00),
(629, 22, 85, 108, 23.00),
(630, 22, 85, 109, 52.00),
(631, 22, 85, 110, 23.00),
(632, 22, 85, 111, 44.00),
(633, 22, 86, 107, 44.00),
(634, 22, 86, 108, 24.00),
(635, 22, 86, 109, 44.00),
(636, 22, 86, 110, 24.00),
(637, 22, 86, 111, 44.00),
(638, 22, 87, 107, 44.00),
(639, 22, 87, 108, 25.00),
(640, 22, 87, 109, 44.00),
(641, 22, 87, 110, 25.00),
(642, 22, 87, 111, 44.00),
(643, 22, 88, 107, 44.00),
(644, 22, 88, 108, 26.00),
(645, 22, 88, 109, 45.00),
(646, 22, 88, 110, 26.00),
(647, 22, 88, 111, 44.00),
(648, 22, 89, 107, 44.00),
(649, 22, 89, 108, 27.00),
(650, 22, 89, 109, 55.00),
(651, 22, 89, 110, 27.00),
(652, 22, 89, 111, 44.00),
(653, 22, 90, 107, 44.00),
(654, 22, 90, 108, 28.00),
(655, 22, 90, 109, 35.00),
(656, 22, 90, 110, 28.00),
(657, 22, 90, 111, 44.00);

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `notice_date` date NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `description`, `notice_date`, `attachment`, `created_at`) VALUES
(3, 'hello world1', 'hello world1', '2025-03-25', '../noticepic/download.jfif', '2025-03-25 13:46:48'),
(4, 'hello world2', 'hello world1', '2025-03-26', '../noticepic/download (1).jfif', '2025-03-25 13:47:10'),
(5, 'hello world3', 'hello world3', '2025-03-26', '../noticepic/download (2).jfif', '2025-03-25 13:47:34');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `rid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`rid`, `name`) VALUES
(1, 'Admin'),
(2, 'Faculty Head'),
(3, 'Teacher'),
(4, 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `stafflog`
--

CREATE TABLE `stafflog` (
  `stid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stafflog`
--

INSERT INTO `stafflog` (`stid`, `username`, `password`, `name`, `role`, `faculty_id`) VALUES
(35, 'rambhakta23', 'bhaktaram@321@', 'Ram Bhakta Neupane', 2, 9),
(36, 'bhamdarikrishna', 'bhandari@321', 'Ram Krishna Bhandari', 3, 9),
(37, 'krishnashrestha', 'krishna@123', 'Krishna Shrestha', 3, 9),
(38, 'santoshpaudel', 'santosh@123', 'Santosh Paudel', 3, 9),
(39, 'purnapoudel', 'purna@321', 'Purna Poudel', 3, 9),
(40, 'rupakshrestha', 'rupa@456', 'Rupa Shrestha', 3, 9),
(41, 'nirmalpoudel', 'nirmal@789', 'Nirmal Poudel', 3, 9),
(42, 'binodbista', 'binod@123', 'Binod Bista', 3, 11),
(43, 'jagatbhattarai', 'jagat@321', 'Jagat Bhattarai', 3, 11),
(44, 'bishalchhetri', 'bishal@789', 'Bishal Chhetri', 3, 11),
(45, 'rameshkhanal', 'ramesh@987', 'Ramesh Khanal', 3, 11),
(46, 'sumanadikari', 'suman@456', 'Suman Adikari', 3, 11),
(47, 'pravinbhandari', 'pravin@123', 'Pravin Bhandari', 3, 12),
(48, 'manojpoudel', 'manoj@456', 'Manoj Poudel', 3, 12),
(49, 'gokulbhattarai', 'gokul@789', 'Gokul Bhattarai', 3, 12),
(50, 'ramprasad', 'ram@321', 'Ram Prasad', 3, 12),
(51, 'surajshrestha', 'suraj@654', 'Suraj Shrestha', 3, 12);

-- --------------------------------------------------------

--
-- Table structure for table `studentlog`
--

CREATE TABLE `studentlog` (
  `sid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `phoneno` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `parentsname` varchar(255) DEFAULT NULL,
  `parentscontact` varchar(15) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `batch` int(10) NOT NULL,
  `faculty` int(11) NOT NULL,
  `classid` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentlog`
--

INSERT INTO `studentlog` (`sid`, `username`, `password`, `name`, `email`, `dob`, `phoneno`, `address`, `gender`, `parentsname`, `parentscontact`, `photo`, `batch`, `faculty`, `classid`) VALUES
(73, 'rambhandari', 'ram@123#', 'Ram Bhandari', 'bhandariram14@gmail.com', '2000-12-12', '9875465464', 'Damauli', 'Male', 'Sunita Bhandari', '9856465464', 'download.jfif', 20, 9, '53,54'),
(74, 'sujantmg', 'sujan@123', 'Sujan Tamang', 'sujantmg@gmail.com', '2001-05-15', '9812345678', 'Kathmandu', 'Male', 'Mina Tamang', '9807654321', NULL, 20, 9, '53,54'),
(75, 'sitalg', 'sital@321', 'Sital Gurung', 'sitalgurung@gmail.com', '2000-08-22', '9823456789', 'Pokhara', 'Female', 'Krishna Gurung', '9812348765', NULL, 20, 9, '53,54'),
(76, 'anilm', 'anil@321', 'Anil Magar', 'anilmagar@gmail.com', '1999-12-30', '9801234567', 'Chitwan', 'Male', 'Saraswati Magar', '9845123789', NULL, 20, 9, '53,54'),
(77, 'rameshr', 'ramesh@123', 'Ramesh Rai', 'rameshrai@gmail.com', '2002-02-18', '9845123456', 'Ilam', 'Male', 'Ganga Rai', '9856231478', NULL, 20, 9, '53,54'),
(78, 'manishp', 'manish@456', 'Manish Poudel', 'manishpoudel@gmail.com', '1998-07-11', '9812456789', 'Butwal', 'Male', 'Keshav Poudel', '9803124567', NULL, 20, 9, '53,54'),
(79, 'pujans', 'pujan@789', 'Pujan Shahi', 'pujanshahi@gmail.com', '2003-09-25', '9807654321', 'Dhangadhi', 'Male', 'Rita Shahi', '9812678345', NULL, 20, 9, '53,54'),
(80, 'saraswtk', 'saraswati@654', 'Saraswati Khatri', 'saraswati.khatri@gmail.com', '2001-03-09', '9821783456', 'Lalitpur', 'Female', 'Bishnu Khatri', '9845126789', NULL, 20, 9, '53,54'),
(81, 'bishnum', 'bishnu@987', 'Bishnu Magar', 'bishnumagar@gmail.com', '2000-11-02', '9819234567', 'Gorkha', 'Male', 'Hari Magar', '9807456321', NULL, 20, 9, '53,54'),
(82, 'kumarbk', 'kumar@963', 'Kumar BK', 'kumarbk@gmail.com', '1999-06-14', '9804567123', 'Dharan', 'Male', 'Rupa BK', '9813457689', NULL, 20, 9, '53,54'),
(83, 'sujitd', 'sujit@852', 'Sujit Dhakal', 'sujitdhakal@gmail.com', '2002-04-29', '9812349876', 'Bhaktapur', 'Male', 'Purna Dhakal', '9845671234', NULL, 20, 9, '53,54'),
(84, 'aashar', 'aashar@123', 'Aashar Regmi', 'aasharregmi@gmail.com', '1998-03-10', '9847854123', 'Nuwakot', 'Male', 'Sabina Regmi', '9823456789', NULL, 20, 9, '53,54'),
(85, 'roshanp', 'roshan@789', 'Roshan Pandey', 'roshanpandey@gmail.com', '1999-09-19', '9802345678', 'Makwanpur', 'Male', 'Ram Pandey', '9805672345', NULL, 20, 9, '53,54'),
(86, 'deekshaa', 'deeksha@321', 'Deeksha Aryal', 'deekshaaryal@gmail.com', '2003-07-08', '9812347689', 'Tanahun', 'Female', 'Sunita Aryal', '9856473821', NULL, 20, 9, '53,54'),
(87, 'bijaygrg', 'bijay@147', 'Bijay Gurung', 'bijaygurung@gmail.com', '2001-11-21', '9807654789', 'Gorkha', 'Male', 'Sita Gurung', '9845123567', NULL, 20, 9, '53,54'),
(88, 'rameshs', 'ramesh@456', 'Ramesh Shrestha', 'rameshs@gmail.com', '2000-06-28', '9846789234', 'Bara', 'Male', 'Sarita Shrestha', '9801234789', NULL, 20, 9, '53,54'),
(89, 'sandeepb', 'sandeep@741', 'Sandeep Bista', 'sandeepbista@gmail.com', '1998-05-05', '9815672348', 'Surkhet', 'Male', 'Kiran Bista', '9821236789', NULL, 20, 9, '53,54'),
(90, 'priyakh', 'priya@963', 'Priya Khatri', 'priyakhatri@gmail.com', '2003-01-18', '9847896543', 'Kavre', 'Female', 'Mohan Khatri', '9806783456', NULL, 20, 9, '53,54'),
(91, 'krishnathapa', 'krishna@123#', 'Krishna Thapa', 'krishnathapa89@gmail.com', '1999-02-22', '9872312312', 'Damauli,Tanahun', 'Male', 'Susmita Thapa', '9845454545', 'download (2).jfif', 21, 9, '56'),
(92, 'rajanp', 'rajan@123', 'Rajan Paudel', 'rajanpaudel@gmail.com', '2002-07-15', '9813456789', 'Kathmandu', 'Male', 'Rita Paudel', '9801234567', NULL, 21, 9, '56'),
(93, 'saraf', 'sara@321', 'Sara Fernandes', 'saraf@gmail.com', '2001-03-10', '9827654321', 'Pokhara', 'Female', 'Suresh Fernandes', '9812348765', NULL, 21, 9, '56'),
(94, 'prakashr', 'prakash@987', 'Prakash Rathi', 'prakashrathi@gmail.com', '1999-11-18', '9805678901', 'Chitwan', 'Male', 'Bishnu Rathi', '9843123456', NULL, 21, 9, '56'),
(95, 'swetaj', 'sweta@852', 'Sweta Joshi', 'swetajoshi@gmail.com', '2003-02-07', '9845761234', 'Ilam', 'Female', 'Mohan Joshi', '9801237890', NULL, 21, 9, '56'),
(96, 'dilips', 'dilip@456', 'Dilip Sharma', 'dilipsharma@gmail.com', '2000-12-19', '9812347654', 'Butwal', 'Male', 'Sita Sharma', '9807654321', NULL, 21, 9, '56'),
(97, 'neelams', 'neelam@741', 'Neelam Shrestha', 'neelamshrestha@gmail.com', '2002-01-25', '9803456789', 'Dhangadhi', 'Female', 'Gopal Shrestha', '9812345678', NULL, 21, 9, '56'),
(98, 'arunk', 'arun@159', 'Arun Koirala', 'arunkoirala@gmail.com', '1999-04-12', '9843456789', 'Lalitpur', 'Male', 'Suman Koirala', '9801234567', NULL, 21, 9, '56'),
(99, 'priyad', 'priya@321', 'Priya Dangi', 'priyadangi@gmail.com', '2001-06-18', '9817654321', 'Gorkha', 'Female', 'Bina Dangi', '9812345678', NULL, 21, 9, '56'),
(100, 'sagarb', 'sagar@654', 'Sagar Bhattarai', 'sagarbhattarai@gmail.com', '2003-08-02', '9802345678', 'Bhojpur', 'Male', 'Hari Bhattarai', '9845671234', NULL, 21, 9, '56'),
(101, 'shristi', 'shris@963', 'Shristi Gautam', 'shristigautam@gmail.com', '2002-11-30', '9805678901', 'Bhaktapur', 'Female', 'Shiva Gautam', '9812347890', NULL, 21, 9, '56'),
(102, 'bishalk', 'bishal@852', 'Bishal Limbu', 'bishallimbu@gmail.com', '1998-12-05', '9843456789', 'Surkhet', 'Male', 'Nirajan Limbu', '9801234567', NULL, 21, 9, '56'),
(103, 'rupal', 'rupa@963', 'Rupa Adhikari', 'rupaadhikari@gmail.com', '2001-10-22', '9812345678', 'Makwanpur', 'Female', 'Hari Adhikari', '9805671234', NULL, 21, 9, '56'),
(104, 'sumanp', 'suman@741', 'Suman Poudel', 'sumanpoudel@gmail.com', '2000-04-14', '9807654321', 'Dharan', 'Male', 'Krishna Poudel', '9841236789', NULL, 21, 9, '56'),
(105, 'nirmalk', 'nirmal@963', 'Nirmal Karki', 'nirmalkarki@gmail.com', '2002-09-13', '9801234567', 'Tanahun', 'Male', 'Sita Karki', '9847654321', NULL, 21, 9, '56'),
(106, 'krishan', 'krishan@789', 'Krishan Thapa', 'krishanthapa@gmail.com', '1999-06-23', '9813456789', 'Sunsari', 'Male', 'Bishnu Thapa', '9802345678', NULL, 21, 9, '56'),
(107, 'madhurip', 'madhuri@456', 'Madhuri Bhandari', 'madhuribhandari@gmail.com', '2003-03-14', '9812345678', 'Kavre', 'Female', 'Manoj Bhandari', '9803456789', NULL, 21, 9, '56'),
(108, 'sugamstha', 'sugam@345', 'Sugam Shrestha', 'sthasugam19@gmail.com', '2002-12-12', '9856423132', 'Pasale', 'Male', 'Manu Maya', '9856532312', 'download (3).jfif', 26, 11, '58'),
(109, 'gautamr', 'gautam@123', 'Gautam Rai', 'gautamrai@gmail.com', '2003-05-10', '9812345678', 'Kathmandu', 'Male', 'Sita Rai', '9801234567', NULL, 26, 11, '58'),
(110, 'riyak', 'riya@321', 'Riya KC', 'riyakc@gmail.com', '2001-07-18', '9823456789', 'Pokhara', 'Female', 'Krishna KC', '9812345678', NULL, 26, 11, '58'),
(111, 'manojr', 'manoj@987', 'Manoj Rathi', 'manojrathi@gmail.com', '2000-10-22', '9801234567', 'Chitwan', 'Male', 'Purna Rathi', '9847654321', NULL, 26, 11, '58'),
(112, 'prabish', 'prabish@852', 'Prabish Bhatt', 'prabishbhatt@gmail.com', '2003-12-01', '9847651234', 'Ilam', 'Male', 'Sita Bhatt', '9812345678', NULL, 26, 11, '58'),
(113, 'ramyad', 'ramya@741', 'Ramya Yadav', 'ramyadav@gmail.com', '1999-08-15', '9812345678', 'Butwal', 'Female', 'Krishna Yadav', '9807654321', NULL, 26, 11, '58'),
(114, 'nepalk', 'nepal@159', 'Nepal Gurung', 'nepalgurung@gmail.com', '2002-09-02', '9801234567', 'Dhangadhi', 'Male', 'Maya Gurung', '9812345678', NULL, 26, 11, '58'),
(115, 'ashwinb', 'ashwin@369', 'Ashwin Bhandari', 'ashwinbhandari@gmail.com', '2000-01-25', '9847654321', 'Lalitpur', 'Male', 'Bina Bhandari', '9801234567', NULL, 26, 11, '58'),
(116, 'rupaa', 'rupa@258', 'Rupa Acharya', 'rupaacharya@gmail.com', '2003-11-11', '9812348765', 'Gorkha', 'Female', 'Krishna Acharya', '9807654321', NULL, 26, 11, '58'),
(117, 'prabin', 'prabin@987', 'Prabin Sharma', 'prabinsharma@gmail.com', '1999-06-18', '9805678901', 'Dharan', 'Male', 'Sita Sharma', '9847654321', NULL, 26, 11, '58'),
(118, 'shivag', 'shiva@321', 'Shiva Gautam', 'shivagautam@gmail.com', '2001-12-14', '9812345678', 'Tanahun', 'Male', 'Radha Gautam', '9801234567', NULL, 26, 11, '58'),
(119, 'monikag', 'monika@852', 'Monika Thapa', 'monikathapa@gmail.com', '2000-11-21', '9807654321', 'Sunsari', 'Female', 'Mohan Thapa', '9845678901', NULL, 26, 11, '58'),
(120, 'shristig', 'shristi@963', 'Shristi Acharya', 'shristiacharya@gmail.com', '2003-02-13', '9812345678', 'Kavre', 'Female', 'Maya Acharya', '9803456789', NULL, 26, 11, '58'),
(121, 'sarojg', 'saroj@123', 'Saroj Bista', 'sarojbista@gmail.com', '2001-04-03', '9812345678', 'Makwanpur', 'Male', 'Rita Bista', '9845671234', NULL, 26, 11, '58'),
(122, 'subhamg', 'subham@456', 'Subham Karki', 'subhamkarki@gmail.com', '1999-09-30', '9803456789', 'Bhaktapur', 'Male', 'Sita Karki', '9807654321', NULL, 26, 11, '58'),
(123, 'priti', 'priti@159', 'Priti Rathi', 'pritirathi@gmail.com', '2002-06-27', '9812345678', 'Surkhet', 'Female', 'Nirajan Rathi', '9803456789', NULL, 26, 11, '58'),
(124, 'manualemaya', 'manu@ale', 'Manu Maya Ale', 'manu48ale@gmail.com', '2003-12-12', '9856875132', 'Rishing', 'Female', 'Aruna Ale', '9845621231', 'download (4).jfif', 27, 11, '60'),
(125, 'sumanr', 'suman@123', 'Suman Rai', 'sumanrai@gmail.com', '2003-04-05', '9812345678', 'Kathmandu', 'Male', 'Mina Rai', '9801234567', NULL, 27, 11, '60'),
(126, 'tanishg', 'tanish@321', 'Tanish Gurung', 'tanishgurung@gmail.com', '2001-12-14', '9823456789', 'Pokhara', 'Male', 'Krishna Gurung', '9812345678', NULL, 27, 11, '60'),
(127, 'siddharg', 'siddhar@987', 'Siddhartha Koirala', 'siddharthakoirala@gmail.com', '2000-02-25', '9801234567', 'Chitwan', 'Male', 'Laxmi Koirala', '9847654321', NULL, 27, 11, '60'),
(128, 'prakarsh', 'prakarsh@852', 'Prakarsh Yadav', 'prakarshyadav@gmail.com', '2003-11-19', '9847651234', 'Ilam', 'Male', 'Sita Yadav', '9812345678', NULL, 27, 11, '60'),
(129, 'krishnar', 'krishna@741', 'Krishna Adhikari', 'krishnaadhikari@gmail.com', '1999-07-10', '9812345678', 'Butwal', 'Male', 'Rita Adhikari', '9807654321', NULL, 27, 11, '60'),
(130, 'nishag', 'nisha@159', 'Nisha Gurung', 'nishagurung@gmail.com', '2002-05-05', '9801234567', 'Dhangadhi', 'Female', 'Maya Gurung', '9812345678', NULL, 27, 11, '60'),
(131, 'ashokb', 'ashok@369', 'Ashok Bahadur', 'ashokbahadur@gmail.com', '2000-11-30', '9847654321', 'Lalitpur', 'Male', 'Bina Bahadur', '9801234567', NULL, 27, 11, '60'),
(132, 'bishwajit', 'bishwajit@258', 'Bishwajit Magar', 'bishwajitmagar@gmail.com', '2003-01-02', '9812348765', 'Gorkha', 'Male', 'Sita Magar', '9807654321', NULL, 27, 11, '60'),
(133, 'dhanrajg', 'dhanraj@987', 'Dhanraj Karki', 'dhanrajkarki@gmail.com', '2001-09-18', '9805678901', 'Dharan', 'Male', 'Krishna Karki', '9847654321', NULL, 27, 11, '60'),
(134, 'yogeshk', 'yogesh@321', 'Yogesh Thapa', 'yogeshthapa@gmail.com', '2002-07-23', '9812345678', 'Tanahun', 'Male', 'Maya Thapa', '9801234567', NULL, 27, 11, '60'),
(135, 'preetip', 'preeti@852', 'Preeti Shrestha', 'preetishrestha@gmail.com', '2000-03-15', '9845671234', 'Sunsari', 'Female', 'Bina Shrestha', '9807654321', NULL, 27, 11, '60'),
(136, 'shivansh', 'shivansh@963', 'Shivansh Gaire', 'shivanshgaire@gmail.com', '2003-12-01', '9812345678', 'Kavre', 'Male', 'Radha Gaire', '9803456789', NULL, 27, 11, '60'),
(137, 'sanjayg', 'sanjay@123', 'Sanjay Pokhrel', 'sanjaypokhrel@gmail.com', '1999-09-10', '9812345678', 'Makwanpur', 'Male', 'Laxmi Pokhrel', '9847654321', NULL, 27, 11, '60'),
(138, 'krishnaa', 'krishna@321', 'Krishna Aryal', 'krishnaaryal@gmail.com', '2001-06-22', '9812345678', 'Bhaktapur', 'Male', 'Maya Aryal', '9807654321', NULL, 27, 11, '60'),
(139, 'prakash12', 'prakash#21', 'Prakash Thapa', 'prakash23@gmail.com', '2000-11-11', '9812313213', 'Ghansikuwa', 'Male', 'Purnima Thapa', '9856896464', 'download (5).jfif', 29, 12, '61'),
(140, 'rajeshr', 'rajesh@123', 'Rajesh Kumar', 'rajeshkumar@gmail.com', '2003-02-10', '9812345678', 'Kathmandu', 'Male', 'Mina Kumar', '9801234567', NULL, 29, 12, '61'),
(141, 'sushantg', 'sushant@321', 'Sushant Gurung', 'sushantgurung@gmail.com', '2000-05-11', '9823456789', 'Pokhara', 'Male', 'Krishna Gurung', '9812345678', NULL, 29, 12, '61'),
(142, 'alokp', 'alok@987', 'Alok Pradhan', 'alokpradhan@gmail.com', '1999-03-15', '9801234567', 'Chitwan', 'Male', 'Saraswati Pradhan', '9847654321', NULL, 29, 12, '61'),
(143, 'manojr', 'manoj@852', 'Manoj Rai', 'manojrai@gmail.com', '2002-11-22', '9847651234', 'Ilam', 'Male', 'Ganga Rai', '9812345678', NULL, 29, 12, '61'),
(144, 'aayushp', 'aayush@741', 'Aayush Pandey', 'aayushpandey@gmail.com', '2000-09-30', '9812345678', 'Butwal', 'Male', 'Keshav Pandey', '9807654321', NULL, 29, 12, '61'),
(145, 'kripap', 'kripa@159', 'Kripa Sharma', 'kripasharma@gmail.com', '2002-07-08', '9801234567', 'Dhangadhi', 'Female', 'Rita Sharma', '9812345678', NULL, 29, 12, '61'),
(146, 'arunb', 'arun@369', 'Arun Bhattarai', 'arunbhattarai@gmail.com', '2001-12-05', '9847654321', 'Lalitpur', 'Male', 'Sita Bhattarai', '9801234567', NULL, 29, 12, '61'),
(147, 'bindum', 'bindu@258', 'Bindu Shrestha', 'bindushrestha@gmail.com', '2003-04-15', '9812348765', 'Gorkha', 'Female', 'Hari Shrestha', '9847654321', NULL, 29, 12, '61'),
(148, 'jeetk', 'jeet@987', 'Jeet Karki', 'jeetkarki@gmail.com', '2000-01-09', '9805678901', 'Dharan', 'Male', 'Rupa Karki', '9812345678', NULL, 29, 12, '61'),
(149, 'bipins', 'bipin@321', 'Bipin Dhakal', 'bipindhakal@gmail.com', '2002-02-18', '9812345678', 'Tanahun', 'Male', 'Maya Dhakal', '9801234567', NULL, 29, 12, '61'),
(150, 'nepalr', 'nepal@852', 'Nepal Rajbhandari', 'nepalrajbhandari@gmail.com', '2003-10-10', '9845671234', 'Sunsari', 'Male', 'Sunita Rajbhandari', '9807654321', NULL, 29, 12, '61'),
(151, 'subheshp', 'subhesh@963', 'Subhesh Tamang', 'subhestamang@gmail.com', '1999-05-15', '9847654321', 'Kavre', 'Male', 'Radha Tamang', '9803456789', NULL, 29, 12, '61'),
(152, 'priyanka', 'priyanka@123', 'Priyanka Shrestha', 'priyankashrestha@gmail.com', '2001-06-01', '9812345678', 'Makwanpur', 'Female', 'Sarita Shrestha', '9812345678', NULL, 29, 12, '61'),
(153, 'bishwajitp', 'bishwajit@321', 'Bishwajit Pokhrel', 'bishwajitpokhrel@gmail.com', '2000-12-20', '9807654321', 'Bhaktapur', 'Male', 'Durga Pokhrel', '9847654321', NULL, 29, 12, '61'),
(154, 'himalkc', 'kchimal23@32', 'Himal KC', 'himalkc@gmail.com', '1999-04-05', '9823345646', 'Farakchaur', 'Male', 'Riya KC', '9872323534', 'download.jfif', 30, 12, '63'),
(155, 'sushilr', 'sushil@123', 'Sushil Rana', 'sushilrana@gmail.com', '2001-04-11', '9812345678', 'Kathmandu', 'Male', 'Mina Rana', '9801234567', NULL, 30, 12, '63'),
(156, 'sanjayb', 'sanjay@321', 'Sanjay Bhandari', 'sanjaybhandari@gmail.com', '2000-10-30', '9823456789', 'Pokhara', 'Male', 'Krishna Bhandari', '9812345678', NULL, 30, 12, '63'),
(157, 'dineshp', 'dinesh@987', 'Dinesh Paudel', 'dineshpaudel@gmail.com', '1999-07-19', '9801234567', 'Chitwan', 'Male', 'Saraswati Paudel', '9847654321', NULL, 30, 12, '63'),
(158, 'alokbh', 'alok@852', 'Alok Bhattarai', 'alokbhattarai@gmail.com', '2002-03-15', '9847651234', 'Ilam', 'Male', 'Ganga Bhattarai', '9812345678', NULL, 30, 12, '63'),
(159, 'subashp', 'subash@741', 'Subash Poudel', 'subashpoudel@gmail.com', '2000-06-08', '9812345678', 'Butwal', 'Male', 'Keshav Poudel', '9807654321', NULL, 30, 12, '63'),
(160, 'sonamr', 'sonam@159', 'Sonam Rai', 'sonamrai@gmail.com', '2003-05-10', '9801234567', 'Dhangadhi', 'Female', 'Rita Rai', '9812345678', NULL, 30, 12, '63'),
(161, 'binodk', 'binod@369', 'Binod Koirala', 'binodkoirala@gmail.com', '2001-08-22', '9847654321', 'Lalitpur', 'Male', 'Sita Koirala', '9801234567', NULL, 30, 12, '63'),
(162, 'pashupatir', 'pashupati@258', 'Pashupati Shrestha', 'pashupatishrestha@gmail.com', '2003-02-17', '9812348765', 'Gorkha', 'Male', 'Hari Shrestha', '9847654321', NULL, 30, 12, '63'),
(163, 'sanjayp', 'sanjay@987', 'Sanjay Pokhrel', 'sanjaypokhrel@gmail.com', '2000-11-28', '9805678901', 'Dharan', 'Male', 'Rupa Pokhrel', '9812345678', NULL, 30, 12, '63'),
(164, 'santoshk', 'santosh@852', 'Santosh Khadka', 'santoshkhadka@gmail.com', '2002-05-20', '9812345678', 'Tanahun', 'Male', 'Maya Khadka', '9801234567', NULL, 30, 12, '63'),
(165, 'roshanb', 'roshan@963', 'Roshan Bhattarai', 'roshanbhattarai@gmail.com', '2001-09-13', '9847654321', 'Sunsari', 'Male', 'Durga Bhattarai', '9803456789', NULL, 30, 12, '63'),
(166, 'pramodp', 'pramod@159', 'Pramod Poudel', 'pramodpoudel@gmail.com', '2003-01-05', '9807654321', 'Kavre', 'Male', 'Sunita Poudel', '9812345678', NULL, 30, 12, '63'),
(167, 'krishnap', 'krishna@258', 'Krishna Bista', 'krishnabista@gmail.com', '1999-02-27', '9803456789', 'Makwanpur', 'Male', 'Sarita Bista', '9807654321', NULL, 30, 12, '63'),
(168, 'nirajb', 'niraj@741', 'Niraj Bhattarai', 'nirajbhattarai@gmail.com', '2002-12-18', '9812345678', 'Bhaktapur', 'Male', 'Durga Bhattarai', '9801234567', NULL, 30, 12, '63');

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subid` int(10) NOT NULL,
  `subname` varchar(200) NOT NULL,
  `subcode` varchar(200) NOT NULL,
  `faculty` int(100) NOT NULL,
  `fullmarks` int(100) NOT NULL,
  `passmarks` int(100) NOT NULL,
  `crhr` int(10) NOT NULL,
  `rank` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subid`, `subname`, `subcode`, `faculty`, `fullmarks`, `passmarks`, `crhr`, `rank`) VALUES
(102, 'English ', 'Eng 1', 9, 80, 40, 3, 1),
(103, 'Maths', 'maths 1', 9, 80, 40, 4, 1),
(104, 'Education', ' Edu 1', 9, 50, 25, 3, 1),
(105, 'Introduction to Technology', ' Tech 1', 9, 40, 20, 3, 1),
(106, 'Nepali', 'Nep 1', 9, 60, 30, 4, 1),
(107, 'Nepali', 'Nep 2', 9, 60, 30, 3, 2),
(108, 'Programming Concept with C', 'programming 1', 9, 40, 20, 3, 2),
(109, 'Developmental Psychology', 'edu 2', 9, 60, 30, 4, 2),
(110, 'Digital Logic', 'digital 2', 9, 40, 20, 3, 2),
(111, 'English', 'eng 2', 9, 60, 30, 4, 2),
(112, '21st Century Life Skill', 'ict 3', 9, 60, 30, 4, 3),
(113, 'DSA', 'dsa 3', 9, 40, 20, 3, 3),
(114, 'Web Technology', 'web 3', 9, 40, 20, 3, 3),
(115, 'DBMS', 'dbms 3', 9, 40, 20, 3, 3),
(116, 'Probability and Statics', 'maths 3', 9, 60, 30, 4, 3),
(117, 'English - I', 'ENG 201', 11, 60, 30, 4, 1),
(118, 'Micro Economics for Business', 'ECO 203', 11, 60, 30, 4, 1),
(119, 'Foundation of Business Management', 'MGT 231', 11, 60, 30, 3, 1),
(120, 'Business Mathematics - I', 'MTH 201', 11, 60, 30, 4, 1),
(121, 'IT and Applications', 'IT 231', 11, 60, 30, 3, 1),
(122, 'English – II', 'ENG 202', 11, 60, 30, 3, 2),
(123, 'Financial Accounting', 'ACC 201', 11, 60, 30, 3, 2),
(124, 'Macro Economics for Business', 'ECO 204', 11, 60, 30, 3, 2),
(125, 'Seminar on Contemporary Issues of Macro Economics', 'ECO 205', 11, 60, 30, 3, 2),
(126, 'Business Mathematics - II', 'MTH 202', 11, 60, 30, 4, 2),
(127, 'Cost and Management Accounting', 'ACC 202', 11, 60, 30, 3, 3),
(128, 'Business Law', 'MGT 204', 11, 60, 30, 4, 3),
(129, 'Business Environment in Nepal', 'MGT 206', 11, 60, 30, 4, 3),
(130, 'Fundamentals of Marketing', 'MKT 201', 11, 60, 30, 3, 3),
(131, 'Basic Psychology', 'PSY 201', 11, 60, 30, 4, 3),
(132, 'General English', '411', 12, 60, 30, 3, 1),
(133, 'Compulsory Nepali', '401', 12, 60, 30, 4, 1),
(134, 'Philosophical & Sociological Foundation of Education', 'Ed. 412', 12, 60, 30, 3, 1),
(135, 'Found.of Language& Linguistics (TH)', 'Eng. Ed. 416', 12, 60, 30, 1, 1),
(136, 'Reading, Writing & Critical Thinking', 'Eng. Ed. 417', 12, 60, 30, 3, 1),
(137, 'Educational Psychology', 'Ed. 421', 12, 60, 30, 3, 2),
(138, 'Expending Horizons In English', 'Eng. Ed. 422', 12, 60, 30, 2, 2),
(139, 'English For Communication [TH]', 'Eng. Ed. 423', 12, 80, 40, 3, 2),
(140, 'Basics of Academic Writing', 'Eng. Ed. 424', 12, 60, 30, 3, 2),
(141, 'Population  Situtation of Nepal', 'Pop. Ed. 428', 12, 60, 30, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `subject_teacher`
--

CREATE TABLE `subject_teacher` (
  `id` int(11) NOT NULL,
  `subject_id` int(10) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adminlog`
--
ALTER TABLE `adminlog`
  ADD PRIMARY KEY (`aid`);

--
-- Indexes for table `basicinfo`
--
ALTER TABLE `basicinfo`
  ADD PRIMARY KEY (`bid`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`fcid`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`mark_id`),
  ADD UNIQUE KEY `unique_mark_entry` (`exam_id`,`student_id`,`subject_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`rid`);

--
-- Indexes for table `stafflog`
--
ALTER TABLE `stafflog`
  ADD PRIMARY KEY (`stid`),
  ADD KEY `role` (`role`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `studentlog`
--
ALTER TABLE `studentlog`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `faculty` (`faculty`),
  ADD KEY `batch` (`batch`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subid`);

--
-- Indexes for table `subject_teacher`
--
ALTER TABLE `subject_teacher`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_subject_class` (`subject_id`,`class_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adminlog`
--
ALTER TABLE `adminlog`
  MODIFY `aid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `basicinfo`
--
ALTER TABLE `basicinfo`
  MODIFY `bid` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `batch_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `cid` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `fcid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=658;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `rid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stafflog`
--
ALTER TABLE `stafflog`
  MODIFY `stid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `studentlog`
--
ALTER TABLE `studentlog`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subid` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `subject_teacher`
--
ALTER TABLE `subject_teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batches`
--
ALTER TABLE `batches`
  ADD CONSTRAINT `batches_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`fcid`);

--
-- Constraints for table `exam`
--
ALTER TABLE `exam`
  ADD CONSTRAINT `exam_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`fcid`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `class` (`cid`) ON DELETE CASCADE;

--
-- Constraints for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD CONSTRAINT `exam_subjects_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subid`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`),
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `studentlog` (`sid`),
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subid`);

--
-- Constraints for table `stafflog`
--
ALTER TABLE `stafflog`
  ADD CONSTRAINT `stafflog_ibfk_1` FOREIGN KEY (`role`) REFERENCES `role` (`rid`),
  ADD CONSTRAINT `stafflog_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`fcid`);

--
-- Constraints for table `subject_teacher`
--
ALTER TABLE `subject_teacher`
  ADD CONSTRAINT `subject_teacher_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subid`),
  ADD CONSTRAINT `subject_teacher_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `stafflog` (`stid`),
  ADD CONSTRAINT `subject_teacher_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`fcid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
