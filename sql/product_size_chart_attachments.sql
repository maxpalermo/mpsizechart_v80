-- Host: localhost
-- Creato il: Ott 17, 2024 alle 15:58
-- Versione del server: 10.6.18-MariaDB-0ubuntu0.22.04.1
-- Versione PHP: 8.1.30
SET
    FOREIGN_KEY_CHECKS = 0;

SET
    SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
    time_zone = "+00:00";

--
-- Database: `{PREFIX}dl_16`
--
-- --------------------------------------------------------
--
-- Struttura della tabella `{PREFIX}product_size_chart_attachment`
--
DROP TABLE IF EXISTS `{PREFIX}product_size_chart_attachment`;

CREATE TABLE IF NOT EXISTS `{PREFIX}product_size_chart_attachment` (
    `id_product` int(11) NOT NULL AUTO_INCREMENT,
    `file_name` varchar(64) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `file_type` varchar(32) NOT NULL,
    `file_size` int(10) UNSIGNED NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime DEFAULT NULL,
    PRIMARY KEY (`id_product`),
    KEY `idx_filename` (`file_name`)
) ENGINE = { ENGINE_TYPE } DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

SET
    FOREIGN_KEY_CHECKS = 1;

COMMIT;