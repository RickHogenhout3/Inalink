DROP DATABASE IF EXISTS `inalink`;

CREATE DATABASE IF NOT EXISTS `inalink`;

USE `inalink`;

CREATE TABLE IF NOT EXISTS `user` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `unique_id` INT NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `avatar` VARCHAR(100) NOT NULL,
    `status` VARCHAR(255) DEFAULT 'Inactive',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username_unique` (`username`),
    UNIQUE KEY `email_unique` (`email`),
    INDEX (`unique_id`)
);


CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `from_user_id` INT,
    `to_user_id` INT,
    `message` TEXT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`from_user_id`) REFERENCES `user`(`unique_id`),
    FOREIGN KEY (`to_user_id`) REFERENCES `user`(`unique_id`)
);


-- Fixed Inalink chatbot account. The application also auto-creates/repairs this
-- row from config.php so existing databases do not need to be recreated.
INSERT INTO `user` (`unique_id`, `username`, `password`, `email`, `avatar`, `status`)
VALUES (
    50000001,
    'Mark Evans',
    '$2y$10$3L3Zb5jD93UpZx4f3mJ4R.OJ8lZrH8GmMlGLSoFCFCMnEjSC9hE9e',
    'mark.evans.bot@inalink.local',
    'avatars/Endou_Mamoru_avatar.png',
    'active now'
)
ON DUPLICATE KEY UPDATE
    `username` = VALUES(`username`),
    `avatar` = VALUES(`avatar`),
    `status` = 'active now';
