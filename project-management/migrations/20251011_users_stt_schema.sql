-- users table dùng stt thay cho id
CREATE TABLE IF NOT EXISTS `users` (
  `stt` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100),
  `email` VARCHAR(100),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `permissions` TEXT
);

CREATE TABLE IF NOT EXISTS `role_user` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES users(stt) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `status` VARCHAR(50),
  `start_date` DATE,
  `end_date` DATE,
  `budget` DECIMAL(18,2),
  `user_id` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME,
  FOREIGN KEY (`user_id`) REFERENCES users(stt) ON DELETE SET NULL
);
