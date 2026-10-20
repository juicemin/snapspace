-- phpMyAdmin SQL Dump
-- version 5.0.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2026 at 07:40 PM
-- Server version: 10.4.14-MariaDB
-- PHP Version: 7.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `IBM4202`
--
CREATE DATABASE IF NOT EXISTS `IBM4202` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `IBM4202`;

-- --------------------------------------------------------

--
-- Table structure for table `album`
--

CREATE TABLE `album` (
  `idalbum` int(11) NOT NULL,
  `title` varchar(45) DEFAULT NULL,
  `imageurl` varchar(100) DEFAULT NULL,
  `idcreator` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `creator`
--

CREATE TABLE `creator` (
  `idcreator` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `imageurl` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `photo`
--

CREATE TABLE `photo` (
  `idphoto` int(11) NOT NULL,
  `title` varchar(45) DEFAULT NULL,
  `imageurl` varchar(100) DEFAULT NULL,
  `comment` varchar(140) DEFAULT NULL,
  `idcreator` int(11) DEFAULT NULL,
  `idalbum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `album`
--
ALTER TABLE `album`
  ADD PRIMARY KEY (`idalbum`),
  ADD KEY `album_creator_idx` (`idcreator`);

--
-- Indexes for table `creator`
--
ALTER TABLE `creator`
  ADD PRIMARY KEY (`idcreator`);

--
-- Indexes for table `photo`
--
ALTER TABLE `photo`
  ADD PRIMARY KEY (`idphoto`),
  ADD KEY `photo_album_idx` (`idalbum`),
  ADD KEY `photo_creator_idx` (`idcreator`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `album`
--
ALTER TABLE `album`
  MODIFY `idalbum` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `creator`
--
ALTER TABLE `creator`
  MODIFY `idcreator` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `photo`
--
ALTER TABLE `photo`
  MODIFY `idphoto` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `album`
--
ALTER TABLE `album`
  ADD CONSTRAINT `album_creator` FOREIGN KEY (`idcreator`) REFERENCES `creator` (`idcreator`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `photo`
--
ALTER TABLE `photo`
  ADD CONSTRAINT `image_album` FOREIGN KEY (`idalbum`) REFERENCES `album` (`idalbum`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `photo_creator` FOREIGN KEY (`idcreator`) REFERENCES `creator` (`idcreator`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `administrator` (
    `idadministrator` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    PRIMARY KEY (`idadministrator`),
    UNIQUE KEY `administrator_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `creator`
  (`idcreator`, `name`, `website`, `imageurl`)
VALUES
  (
    1,
    'Birdbird Tan',
    'https://example.com/birdbirdtan',
    'assets/images/image-placeholder.png'
  ),
  (
    2,
    'Jamis Tang',
    'https://example.com/jamis',
    'assets/images/image-placeholder.png'
  ),
  (
    3,
    'HeeHee Wong',
    'https://example.com/heeheewong',
    'assets/images/image-placeholder.png'
  ),
  (
    4,
    'Batiq Yaw',
    'https://example.com/batiqyaw',
    'assets/images/image-placeholder.png'
  );

  INSERT INTO `album`
  (`idalbum`, `title`, `imageurl`, `idcreator`)
VALUES
  (
    1,
    'Urban Stories',
    'assets/images/image-placeholder.png',
    1
  ),
  (
    2,
    'Quiet Landscapes',
    'assets/images/image-placeholder.png',
    1
  ),
  (
    3,
    'Night Lights',
    'assets/images/image-placeholder.png',
    2
  ),
  (
    4,
    'Natural Forms',
    'assets/images/image-placeholder.png',
    2
  ),
  (
    5,
    'Daily Moments',
    'assets/images/image-placeholder.png',
    3
  ),
  (
    6,
    'Portrait Studies',
    'assets/images/image-placeholder.png',
    3
  ),
  (
    7,
    'Abstract Visions',
    'assets/images/image-placeholder.png',
    4
  ),
  (
    8,
    'Colorful Streets',
    'assets/images/image-placeholder.png',
    4
  );

  INSERT INTO `photo`
  (
    `idphoto`,
    `title`,
    `imageurl`,
    `comment`,
    `idcreator`,
    `idalbum`
  )
VALUES
  (
    1,
    'City Crossing',
    'assets/images/image-placeholder.png',
    'Pedestrians crossing a busy city street.',
    1,
    1
  ),
  (
    2,
    'Quiet Alley',
    'assets/images/image-placeholder.png',
    'A quiet alley surrounded by city buildings.',
    1,
    1
  ),
  (
    3,
    'Distant Hills',
    'assets/images/image-placeholder.png',
    'Hills viewed from across an open landscape.',
    1,
    2
  ),
  (
    4,
    'Morning Lake',
    'assets/images/image-placeholder.png',
    'A calm lake photographed in the morning.',
    1,
    2
  ),
  (
    5,
    'Neon Street',
    'assets/images/image-placeholder.png',
    'Colourful city lights after sunset.',
    2,
    3
  ),
  (
    6,
    'Night Traffic',
    'assets/images/image-placeholder.png',
    'Vehicle lights moving through the city.',
    2,
    3
  ),
  (
    7,
    'Leaf Patterns',
    'assets/images/image-placeholder.png',
    'Natural patterns found on green leaves.',
    2,
    4
  ),
  (
    8,
    'Stone Texture',
    'assets/images/image-placeholder.png',
    'A close view of natural stone textures.',
    2,
    4
  ),
  (
    9,
    'Morning Routine',
    'assets/images/image-placeholder.png',
    'A simple moment from everyday life.',
    3,
    5
  ),
  (
    10,
    'Coffee Break',
    'assets/images/image-placeholder.png',
    'A quiet break during the afternoon.',
    3,
    5
  ),
  (
    11,
    'Studio Portrait',
    'assets/images/image-placeholder.png',
    'A portrait photographed using studio lighting.',
    3,
    6
  ),
  (
    12,
    'Window Portrait',
    'assets/images/image-placeholder.png',
    'A portrait illuminated by natural window light.',
    3,
    6
  ),
  (
    13,
    'Abstract Geometry',
    'assets/images/image-placeholder.png',
    'Geometric shapes arranged in an abstract composition.',
    4,
   7
  ),
  (
    14,
    'Shadow Forms',
    'assets/images/image-placeholder.png',
    'Shadows creating abstract shapes and patterns.',
    4,
    7
  ),
  (
    15,
    'Market Colours',
    'assets/images/image-placeholder.png',
    'Bright colours found along a busy market street.',
    4,
    8
  ),
  (
    16,
    'Painted Walls',
    'assets/images/image-placeholder.png',
    'Colourful artwork displayed on urban walls.',
    4,
    8
  );

/* Development administrator account
-Username: admin
-The password is communicated privately to group members.*/

INSERT INTO `administrator` (`username`, `password_hash`)
VALUES (
    'admin',
    '$2y$10$4vh0t9jBiRWpYXsf6LnFguOLnm1RFiqVBfznZhTxgMKmz72qvPZ12'
);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
