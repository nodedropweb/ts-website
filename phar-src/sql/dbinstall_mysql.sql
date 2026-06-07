SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `DBPREFIXconfig`;
CREATE TABLE `DBPREFIXconfig` (
  `identifier` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STRING' COMMENT 'STRING, INT, FLOAT, BOOL, JSON',
  `value` text COLLATE utf8mb4_unicode_ci,
  `user_editable` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `DBPREFIXconfig` (`identifier`, `type`, `value`, `user_editable`) VALUES
('cache_servericons', 'INT', '600', 1),
('onlinerecord_value', 'INT', '0', 0),
('onlinerecord_date', 'INT', '0', 0),
('usingcloudflare', 'BOOL', 'false', 1),
('loginpokeclient', 'BOOL', 'true', 1),
('cache_logincode', 'INT', '120', 1),
('cache_adminstatus', 'INT', '60', 1),
('cache_languages', 'INT', '300', 1),
('adminstatus_groups', 'JSON', '[]', 1),
('adminstatus_mode', 'INT', '2', 1),
('adminstatus_enabled', 'BOOL', 'true', 1),
('adminstatus_hideoffline', 'BOOL', 'false', 1),
('adminstatus_ignoredusers', 'JSON', '[]', 1),
('assignerconfig', 'JSON', '[]', 1),
('query_nickname', 'STRING', 'TS-website', 1),
('cache_serverinfo', 'INT', '10', 1),
('cache_banlist', 'INT', '60', 1),
('cache_clientlist', 'INT', '15', 1),
('cache_channelist', 'INT', '60', 1),
('cache_servergroups', 'INT', '60', 1),
('cache_channelgroups', 'INT', '60', 1),
('adminstatus_offlinehiddenbydefault', 'BOOL', 'false', 1),
('imprint_enabled', 'BOOL', 'false', 1),
('imprint_url', 'STRING', 'imprint.php', 1),
('assigner_cooldown_seconds', 'INT', '0', 1),
('assigner_required_sgids', 'JSON', '[]', 1),
('viewer_hidden_channel_ids', 'JSON', '[]', 1),
('query_hostname', 'STRING', '127.0.0.1', 1),
('query_port', 'INT', '10011', 1),
('query_username', 'STRING', '', 0),
('query_password', 'STRING', '', 0),
('query_displayip', 'STRING', '', 1),
('tsserver_port', 'INT', '9987', 1),
('baseurl', 'STRING', 'http://localhost', 1),
('website_title', 'STRING', 'TS-Website', 1),
('nav_brand', 'STRING', 'TS-Website', 1),
('faq_contact_url', 'STRING', '#', 1),
('timezone', 'STRING', 'UTC', 1),
('admin_cldbids', 'JSON', '[]', 0);

DROP TABLE IF EXISTS `DBPREFIXfaq`;
CREATE TABLE `DBPREFIXfaq` (
  `faqid` int(11) NOT NULL,
  `langid` int(11) NOT NULL DEFAULT '1',
  `question` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastmodify` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `DBPREFIXfaq` (`faqid`, `langid`, `question`, `answer`, `lastmodify`) VALUES
(1, 1, 'Why does this fork of ts-website exist?', 'The original project by Wruczek was excellent but lacked a proper admin panel and modern PHP support. This fork by <b>nodedropweb</b> adds a full web-based administration interface, modernizes the code for PHP 8.4+, and ensures GDPR compliance by hosting all assets locally.', '2024-06-07 10:00:00'),
(2, 1, 'What are the main new features in this version?', 'This version introduces a <b>complete Admin Panel</b> for managing News, FAQ, Rules, and settings. It also includes an enhanced CLI tool (<code>tsw.phar</code>) for easier maintenance, a modernized dark-themed administration UI, and a patched TeamSpeak 3 PHP framework for improved stability and performance.', '2024-06-07 10:00:00'),
(3, 1, 'Is this version compatible with the original ts-website?', 'Yes! This is a drop-in extension. All existing features remain functional, but you now have much more control through the new administration interface without needing to edit the database manually.', '2024-06-07 10:00:00');

DROP TABLE IF EXISTS `DBPREFIXnews`;
CREATE TABLE `DBPREFIXnews` (
  `newsid` int(11) NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `langid` int(11) NOT NULL DEFAULT '1',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited` timestamp NULL DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `DBPREFIXnews` (`newsid`, `title`, `langid`, `added`, `edited`, `content`) VALUES
(1, 'Welcome to ts-website 3.0!', 1, '2024-06-07 10:00:00', NULL, 'We are excited to present this modernized version of the popular TeamSpeak community website. This release brings a long-awaited <b>Admin Panel</b>, improved stability for modern PHP versions, and enhanced CLI tools for server operators. Check out the FAQ and Rules pages to see how easy it is to manage your community!');


ALTER TABLE `DBPREFIXconfig`
  ADD UNIQUE KEY `param` (`identifier`);

ALTER TABLE `DBPREFIXfaq`
  ADD PRIMARY KEY (`faqid`);

ALTER TABLE `DBPREFIXnews`
  ADD PRIMARY KEY (`newsid`);


ALTER TABLE `DBPREFIXfaq`
  MODIFY `faqid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `DBPREFIXnews`
  MODIFY `newsid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;
