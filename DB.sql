CREATE DATABASE IF NOT EXISTS morabodima;
USE morabodima;

CREATE USER IF NOT EXISTS 'superuser'@'localhost' IDENTIFIED BY '1234';
GRANT ALL PRIVILEGES ON morabodima.* TO 'superuser'@'localhost';
FLUSH PRIVILEGES;

-- Drop existing tables in reverse dependency order so the new structure applies cleanly
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS house_images;
DROP TABLE IF EXISTS boarding_houses;
DROP TABLE IF EXISTS users;

-- 1. Added user_id INT AUTO_INCREMENT PRIMARY KEY so foreign keys can reference it
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    role ENUM('student', 'owner', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Foreign key (owner_id) now properly matches users(user_id)
CREATE TABLE boarding_houses (
    house_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    google_maps_link TEXT,
    distance_km DECIMAL(5,2) DEFAULT 0.00,
    monthly_rent DECIMAL(10,2) NOT NULL,
    gender_type ENUM('male', 'female', 'mixed') DEFAULT 'mixed',
    description TEXT,
    amenities VARCHAR(255),
    avg_rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. House Images Table
CREATE TABLE house_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    image_path LONGTEXT NOT NULL,
    FOREIGN KEY (house_id) REFERENCES boarding_houses(house_id) ON DELETE CASCADE
);

-- 4. Reviews Table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    user_id INT NOT NULL,
    overall_rating INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES boarding_houses(house_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Fixed column names (name, email, pass, role) to match CREATE TABLE users
INSERT INTO users (name, email, pass, role) VALUES
('Sunil Perera', 'owner@boarding.lk', 'hashed_pw_1', 'owner'),
('Kasun Silva', 'student@uni.lk', 'hashed_pw_2', 'student'),
('Admin User', 'admin@uni.lk', 'admin123', 'admin'),
('Admin', 'Superuser@gmail.com', '1234', 'admin');

-- 6. Sample Boarding Houses
INSERT INTO boarding_houses (owner_id, title, address, google_maps_link, distance_km, monthly_rent, gender_type, description, amenities, avg_rating, total_reviews, status) VALUES
(1, 'Green View Student Villa', '124 Katubedda Road, Moratuwa', 'https://maps.google.com', 0.50, 15000.00, 'male', 'Quiet single and shared rooms near the main gate.', 'Wi-Fi,Attached Bath,Study Desk,CCTV Security', 4.5, 2, 'approved'),
(1, 'Lakefront Ladies Hostel', '45 Molpe Road, Katubedda', 'https://maps.google.com', 0.35, 13500.00, 'female', 'Safe boarding house for female students with 24/7 security.', 'Wi-Fi,Cooking Allowed,Filtered Water,CCTV Security', 5.0, 1, 'approved');

-- 7. Sample Reviews
INSERT INTO reviews (house_id, user_id, overall_rating, comment) VALUES
(1, 2, 5, 'Very calm environment for studying and fast Wi-Fi!'),
(1, 3, 4, 'Close to campus and reasonable monthly rent.'),
(2, 2, 5, 'Highly secure place with clean facilities.');