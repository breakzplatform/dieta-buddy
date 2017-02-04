-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 03-Fev-2017 às 18:11
-- Versão do servidor: 10.1.19-MariaDB
-- PHP Version: 5.6.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fb_bot`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `aux`
--

CREATE TABLE `aux` (
  `id` varchar(255) NOT NULL,
  `name` text NOT NULL,
  `cal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `aux`
--

INSERT INTO `aux` (`id`, `name`, `cal`) VALUES
('1144940105629418', 'enchilada', 532);

-- --------------------------------------------------------

--
-- Estrutura da tabela `day_cal`
--

CREATE TABLE `day_cal` (
  `day` date NOT NULL,
  `id` varchar(255) NOT NULL,
  `consumido` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `day_cal`
--

INSERT INTO `day_cal` (`day`, `id`, `consumido`) VALUES
('2017-02-03', '1144940105629418', 739);

-- --------------------------------------------------------

--
-- Estrutura da tabela `user`
--

CREATE TABLE `user` (
  `id` varchar(255) NOT NULL,
  `sexo` text NOT NULL,
  `idade` int(11) NOT NULL,
  `cal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `user`
--

INSERT INTO `user` (`id`, `sexo`, `idade`, `cal`) VALUES
('1144940105629418', 'Feminino', 0, 3000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aux`
--
ALTER TABLE `aux`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD UNIQUE KEY `id` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
