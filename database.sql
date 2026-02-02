CREATE DATABASE IF NOT EXISTS movie_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE movie_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Default admin: username = admin, password = admin123
INSERT IGNORE INTO users (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    genre VARCHAR(150) NOT NULL,
    rating DECIMAL(3,1) DEFAULT 5.0,
    description TEXT,
    poster VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data
INSERT IGNORE INTO movies (title, year, genre, rating, description, poster) VALUES
('Inception', 2010, 'Action,Sci-Fi,Thriller', 8.8, 'A skilled thief is offered a chance to erase his criminal history.', 'inception.jpg'),
('The Dark Knight', 2008, 'Action,Crime,Drama', 9.0, 'Batman faces the Joker in a battle for Gotham.', NULL),
('Interstellar', 2014, 'Adventure,Drama,Sci-Fi', 8.7, 'A team explores space to find a new home for humanity.', 'interstellar.jpg');