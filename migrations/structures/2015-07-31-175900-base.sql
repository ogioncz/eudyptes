CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(35) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(60) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(35) COLLATE utf8_czech_ci NOT NULL,
  `role` enum('admin','editor','moderator','news','verified','basic','shadow','new') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'new',
  `member` tinyint(1) NOT NULL DEFAULT '0',
  `mailnotify` tinyint(1) NOT NULL DEFAULT '1',
  `skype` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL,
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `profile` text COLLATE utf8_czech_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(39) COLLATE utf8_unicode_ci NOT NULL,
  `board` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reaction` int(11) DEFAULT NULL,
  `sender` int(10) unsigned DEFAULT NULL,
  `recipient` int(10) unsigned DEFAULT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `markdown` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(39) COLLATE utf8_unicode_ci NOT NULL,
  `read` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reaction` (`reaction`),
  KEY `sender` (`sender`),
  KEY `recipient` (`recipient`),
  CONSTRAINT `mail_ibfk_7` FOREIGN KEY (`reaction`) REFERENCES `mail` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `mail_ibfk_8` FOREIGN KEY (`sender`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mail_ibfk_9` FOREIGN KEY (`recipient`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `meeting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `server` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Snow Bank',
  `date` datetime NOT NULL,
  `program` text COLLATE utf8_unicode_ci NOT NULL,
  `markdown` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(39) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `meeting_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `meeting_user` (
  `meeting_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `userid` (`user_id`,`meeting_id`),
  KEY `meeting_id` (`meeting_id`),
  CONSTRAINT `meeting_user_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meeting` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `slug` varchar(250) CHARACTER SET ascii NOT NULL,
  `title` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(250) CHARACTER SET ascii DEFAULT NULL,
  `menu` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`slug`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `page_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `page_revision` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `markdown` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(10) unsigned NOT NULL,
  `ip` varchar(39) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `page_revision_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`),
  CONSTRAINT `page_revision_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `title` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `markdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `likeable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `post_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `stamp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `icon` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `difficulty` int(11) NOT NULL,
  `obtainable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('registration','login','password') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'login',
  `user_id` int(10) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expiration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(39) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `user_stamp` (
  `user_id` int(10) unsigned NOT NULL,
  `stamp_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `stampid` (`stamp_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_stamp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_stamp_ibfk_2` FOREIGN KEY (`stamp_id`) REFERENCES `stamp` (`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
