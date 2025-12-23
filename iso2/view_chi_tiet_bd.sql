-- phpMyAdmin SQL Dump
-- version 4.4.15
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 22, 2025 at 03:12 PM
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
-- Structure for view `view_chi_tiet_bd`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`diavatly`@`localhost` SQL SECURITY DEFINER VIEW `view_chi_tiet_bd` AS select concat(`diavatly_db`.`hososcbd_iso`.`mavt`,'-',`diavatly_db`.`hososcbd_iso`.`somay`) AS `thiet_bi_id`,`diavatly_db`.`hososcbd_iso`.`ngaykt` AS `ngaykt`,convert(cast(`diavatly_db`.`hososcbd_iso`.`honghoc` as char charset binary) using utf8) AS `honghoc`,convert(cast(`diavatly_db`.`hososcbd_iso`.`khacphuc` as char charset binary) using utf8) AS `khacphuc`,convert(cast(`diavatly_db`.`hososcbd_iso`.`noidung` as char charset binary) using utf8) AS `noidung`,convert(cast(group_concat(`ngthuchien_iso`.`hoten` separator ' ') as char charset binary) using utf8) AS `ketluan` from (`diavatly_db`.`hososcbd_iso` join `ngthuchien_iso` on((`diavatly_db`.`hososcbd_iso`.`hoso` = `ngthuchien_iso`.`mahoso`))) group by `diavatly_db`.`hososcbd_iso`.`hoso` order by `diavatly_db`.`hososcbd_iso`.`ngaykt` desc;

--
-- VIEW  `view_chi_tiet_bd`
-- Data: None
--


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
