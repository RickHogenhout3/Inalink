DROP DATABASE IF EXISTS `inalink`;

CREATE DATABASE IF NOT EXISTS `inalink`;

USE `inalink`;

CREATE TABLE IF NOT EXISTS `user` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `unique_id` INT NOT NULL,
    `firstname` VARCHAR(50) NOT NULL,
    `lastname` VARCHAR(50) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `avatar` VARCHAR(100) NOT NULL,
    `status` VARCHAR(255),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username_unique` (`username`),
    UNIQUE KEY `email_unique` (`email`),
    INDEX (`unique_id`) -- Ensure an index on `unique_id`
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

