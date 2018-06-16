<?php
/**
 * EVO CMS
 * Template parser
 *
 * Date: 24.06.2017
 * @deprecated Use EvolutionCMS\Support\TemplateParser
 * @todo could be unnecessary
 *
 */
Class TemplateParser extends EvolutionCMS\Legacy\TemplateParser
{
}

?>

<?php
/*

include_once MODX_BASE_PATH . MGR_DIR . '/media/style/' . $modx->config['manager_theme'] . '/includes/template.parser.class.inc.php';

echo TemplateParser::output(array('action' => 88), $userdata);

*/
?>


<!--

-- --------------------------------------------------------
-- Хост:                         127.0.0.1
-- Версия сервера:               5.6.29 - MySQL Community Server (GPL)
-- Операционная система:         Win64
-- HeidiSQL Версия:              9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Дамп структуры базы данных modxnewhtml
CREATE DATABASE IF NOT EXISTS `modxnewhtml` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `modxnewhtml`;

-- Дамп структуры для таблица modxnewhtml.modx_system_templates
CREATE TABLE IF NOT EXISTS `modx_system_templates` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `help` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `readonly` int(1) NOT NULL DEFAULT '0',
  `elements` text NOT NULL,
  `content` text NOT NULL,
  `category` int(11) NOT NULL,
  `template` int(11) NOT NULL DEFAULT '0',
  `tab` int(11) NOT NULL DEFAULT '0',
  `rank` int(11) NOT NULL DEFAULT '0',
  `action` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- Дамп данных таблицы modxnewhtml.modx_system_templates: ~14 rows (приблизительно)
	/*!40000 ALTER TABLE `modx_system_templates` DISABLE KEYS */;
INSERT INTO `modx_system_templates` (`id`, `name`, `alias`, `type`, `description`, `help`, `value`, `readonly`, `elements`, `content`, `category`, `template`, `tab`, `rank`, `action`) VALUES
(0, 'fullname', 'user_full_name', 'text', '', '', '', 0, '', '', 9, 0, 1, 1, 88),
	(1, 'email', 'user_email', 'text', '', '', '', 0, '', '', 9, 0, 1, 2, 88),
	(2, 'phone', 'user_phone', 'text', '', '', '', 0, '', '', 9, 0, 1, 3, 88),
	(3, 'mobilephone', 'user_mobile', 'text', '', '', '', 0, '', '', 9, 0, 1, 5, 88),
	(4, 'fax', 'user_fax', 'text', '', '', '', 0, '', '', 9, 0, 1, 6, 88),
	(5, 'street', 'user_street', 'text', '', '', '', 0, '', '', 9, 0, 1, 7, 88),
	(6, 'city', 'user_city', 'text', '', '', '', 0, '', '', 9, 0, 1, 8, 88),
	(7, 'state', 'user_state', 'text', '', '', '', 0, '', '', 9, 0, 1, 9, 88),
	(8, 'zip', 'user_zip', 'text', '', '', '', 0, '', '', 9, 0, 1, 10, 88),
	(9, 'country', 'user_country', 'select', '', '', '', 0, '', '', 9, 0, 1, 11, 88),
	(10, 'dob', 'user_dob', 'date', '', '', '', 0, '', '', 9, 0, 1, 12, 88),
	(11, 'gender', 'user_gender', 'select', '', '', '', 0, '||user_male==1||user_female==2||user_other==3', '', 9, 0, 1, 13, 88),
	(12, 'comment', '', 'textarea', '', '', '', 0, '', '', 9, 0, 1, 14, 88),
	(13, 'logincount', 'user_logincount', 'custom', '', '', '', 0, '', '[+value+]', 9, 0, 1, 15, 88);
/*!40000 ALTER TABLE `modx_system_templates` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

-->
