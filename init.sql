CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `icon_filename` text COLLATE utf8mb4_unicode_ci,
  `introduction` text COLLATE utf8mb4_unicode_ci,
  `cover_filename` text COLLATE utf8mb4_unicode_ci,
  `birthday` date DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `user_relationships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `follower_user_id` int(10) unsigned NOT NULL,
  `followee_user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `bbs_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `bbs_images` (
  `id` int(11) NOT NULL,
  `image_filename` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);