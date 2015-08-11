CREATE TABLE `post_revision` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `title` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `markdown` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(10) unsigned NOT NULL,
  `ip` varchar(39) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `post_revision_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  CONSTRAINT `post_revision_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `post`
CHANGE `timestamp` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `markdown`;

INSERT INTO `post_revision`
(`post_id`, `user_id`, `title`, `content`, `markdown`, `timestamp`, `ip`) SELECT `id`, `user_id`, `title`, `content`, `markdown`, `created_at`, '' FROM `post`;

ALTER TABLE `post`
DROP `title`,
DROP `content`,
DROP `markdown`;
