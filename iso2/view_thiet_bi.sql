-- phpMyAdmin SQL Dump
-- version 4.4.15
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 22, 2025 at 03:45 PM
-- Server version: 5.6.49
-- PHP Version: 5.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `diavatly_ltd`
--

-- --------------------------------------------------------

--
-- Structure for view `view_thiet_bi`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`diavatly`@`localhost` SQL SECURITY DEFINER VIEW `view_thiet_bi` AS select concat(`diavatly_db`.`thietbi_iso`.`mavt`,'-',`diavatly_db`.`thietbi_iso`.`somay`) AS `thiet_bi_id`,`diavatly_db`.`thietbi_iso`.`mamay` AS `mamay`,convert(cast(`diavatly_db`.`thietbi_iso`.`tenvt` as char charset binary) using utf8) AS `ten_vat_tu`,`diavatly_db`.`thietbi_iso`.`madv` AS `madv` from `diavatly_db`.`thietbi_iso`;

--
-- VIEW  `view_thiet_bi`
-- Data: None
--


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
