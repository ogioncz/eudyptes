SET foreign_key_checks = 0;
ALTER TABLE `post` DROP FOREIGN KEY `post_ibfk_2`,
ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
CHANGE `user_id` `user_id` int(10) unsigned NOT NULL AFTER `id`;
SET foreign_key_checks = 1;
