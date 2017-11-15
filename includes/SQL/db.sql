
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) unsigned NOT NULL,
  `id_owner` int(11) unsigned DEFAULT NULL,
  `owner_type` varchar(256) COLLATE utf8_bin NOT NULL,
  `created_time` timestamp NULL DEFAULT NULL,
  `modified_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(256) COLLATE utf8_bin NOT NULL,
  `login` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `password` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `api_key` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `restricted_to` text COLLATE utf8_bin NOT NULL,
  `state` int(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Comment on the table';

--
-- Contenu de la table `accounts`
--

INSERT INTO `accounts` (`id`, `id_owner`, `owner_type`, `created_time`, `modified_time`, `name`, `login`, `password`, `api_key`, `restricted_to`, `state`) VALUES
(1, 1, 'auth', '2016-07-29 14:29:47', '2016-08-10 08:38:23', 'admin', NULL, NULL, '2E8222563D92217995CB82D98642F', '', 1),
(2, 1, 'auth', '2016-07-29 14:51:28', '2016-08-10 08:38:26', 'user1', NULL, NULL, 'AB12C5EBAD1A92FC3D1F725B83FBF', '', 1);

--
-- Triger `accounts`
--
DELIMITER $$
CREATE TRIGGER `auth_creation_timestamp` BEFORE INSERT ON `accounts`
 FOR EACH ROW SET NEW.`created_time` = NOW()
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `activity`
--

CREATE TABLE IF NOT EXISTS `activity` (
  `id` int(11) NOT NULL,
  `id_auth` int(11) NOT NULL,
  `uri` varchar(256) COLLATE utf8_bin NOT NULL,
  `class` varchar(256) COLLATE utf8_bin NOT NULL,
  `method` varchar(16) COLLATE utf8_bin NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(20) COLLATE utf8_bin NOT NULL,
  `as_ip` varchar(256) COLLATE utf8_bin NOT NULL,
  `city` varchar(256) COLLATE utf8_bin NOT NULL,
  `country` varchar(256) COLLATE utf8_bin NOT NULL,
  `countryCode` varchar(16) COLLATE utf8_bin NOT NULL,
  `isp` varchar(256) COLLATE utf8_bin NOT NULL,
  `lat` float NOT NULL,
  `lon` float NOT NULL,
  `org` varchar(256) COLLATE utf8_bin NOT NULL,
  `region` varchar(256) COLLATE utf8_bin NOT NULL,
  `regionName` varchar(256) COLLATE utf8_bin NOT NULL,
  `timezone` varchar(32) COLLATE utf8_bin NOT NULL,
  `zip` varchar(15) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


--
-- Structure de la table `api_auth_fail`
--

CREATE TABLE IF NOT EXISTS `api_auth_fail` (
  `id` int(11) NOT NULL,
  `ip` varchar(16) COLLATE utf8_bin NOT NULL,
  `auth` varchar(256) COLLATE utf8_bin NOT NULL,
  `abuse` varchar(256) COLLATE utf8_bin NOT NULL,
  `url` varchar(512) COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Structure de la table `authors`
--

CREATE TABLE IF NOT EXISTS `authors` (
  `id` int(11) unsigned NOT NULL,
  `id_owner` int(11) unsigned DEFAULT NULL,
  `owner_type` varchar(256) COLLATE utf8_bin NOT NULL,
  `created_time` timestamp NULL DEFAULT NULL,
  `modified_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `first_name` varchar(256) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Comment on the table';

--
-- Contenu de la table `authors`
--

INSERT INTO `authors` (`id`, `id_owner`, `owner_type`, `created_time`, `modified_time`, `name`, `first_name`) VALUES
(1, 1, 'accounts', '2016-08-10 15:03:48', '2016-08-25 06:57:56', 'Redfields', 'James'),
(2, 1, 'accounts', '2016-08-10 15:35:00', '2016-08-24 16:30:54', 'Coelho', 'Paulo');

--
-- DÃ©clencheurs `authors`
--
DELIMITER $$
CREATE TRIGGER `authors_creation_timestamp` BEFORE INSERT ON `authors`
 FOR EACH ROW SET NEW.`created_time` = NOW()
$$
DELIMITER ;

--
-- Structure de la table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `id` int(11) unsigned NOT NULL,
  `id_owner` int(11) unsigned DEFAULT NULL,
  `owner_type` varchar(256) COLLATE utf8_bin NOT NULL,
  `created_time` timestamp NULL DEFAULT NULL,
  `modified_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `file_name` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `directory` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `mime_type` varchar(32) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Auto created';

--
-- Déclencheurs `images`
--
DELIMITER $$
CREATE TRIGGER `images_creation_timestamp` BEFORE INSERT ON `images`
 FOR EACH ROW SET NEW.`created_time` = NOW()
$$
DELIMITER ;


--
-- Index pour la table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY AUTO_INCREMENT (`id`),
  ADD KEY `api_key` (`api_key`);

--
-- Index pour la table `activity`
--
ALTER TABLE `activity`
  ADD PRIMARY KEY AUTO_INCREMENT (`id`);

--
-- Index pour la table `api_auth_fail`
--
ALTER TABLE `api_auth_fail`
  ADD PRIMARY KEY AUTO_INCREMENT (`id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `time` (`ip`);

--
-- Index pour la table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY AUTO_INCREMENT (`id`);


--
-- Index pour la table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY AUTO_INCREMENT (`id`);