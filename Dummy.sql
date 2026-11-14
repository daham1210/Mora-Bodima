CREATE DATABASE IF NOT EXISTS morabodima;
USE morabodima;

CREATE USER IF NOT EXISTS 'superuser'@'localhost' IDENTIFIED BY '1234';
GRANT ALL PRIVILEGES ON morabodima.* TO 'superuser'@'localhost';
FLUSH PRIVILEGES;

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS house_images;
DROP TABLE IF EXISTS boarding_houses;
DROP TABLE IF EXISTS users;

-- 1. Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    role ENUM('student', 'owner', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Boarding Houses Table
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

-- ============================================================================
-- 5. SEED USERS (25 Users: 2 Admins, 7 Owners, 16 Students)
-- ============================================================================
INSERT INTO users (user_id, name, email, pass, role, created_at) VALUES
(1,  'Sunil Perera',          'owner@boarding.lk',        '1234',     'owner',   '2026-08-01 09:00:00'),
(2,  'Kasun Silva',           'student@uni.lk',           '1234',     'student', '2026-08-02 10:15:00'),
(3,  'Admin User',            'admin@uni.lk',             'admin123', 'admin',   '2026-08-01 08:00:00'),
(4,  'Admin',                 'Superuser@gmail.com',      '1234',     'admin',   '2026-08-01 08:05:00'),
(5,  'Nimal Jayawardena',     'nimal.j@boarding.lk',      '1234',     'owner',   '2026-08-05 11:20:00'),
(6,  'kamani Fernando',       'kamani.f@boarding.lk',     '1234',     'owner',   '2026-08-06 14:30:00'),
(7,  'Rohan Wijesinghe',      'rohan.w@boarding.lk',      '1234',     'owner',   '2026-08-08 09:45:00'),
(8,  'Champa Gunaratne',      'champa.g@boarding.lk',     '1234',     'owner',   '2026-08-10 16:10:00'),
(9,  'Lalith Dissanayake',    'lalith.d@boarding.lk',     '1234',     'owner',   '2026-08-12 12:00:00'),
(10, 'Priyantha Rodrigo',     'priyantha.r@boarding.lk',  '1234',     'owner',   '2026-08-14 15:50:00'),
(11, 'Tharindu Bandara',      'tharindu.22@uom.lk',       '1234',     'student', '2026-09-01 08:30:00'),
(12, 'Nethmi Senanayake',     'nethmi.23@uom.lk',         '1234',     'student', '2026-09-01 09:10:00'),
(13, 'Kaveesha Rajapaksa',    'kaveesha.24@uom.lk',       '1234',     'student', '2026-09-02 10:00:00'),
(14, 'Dilshan Pathirana',     'dilshan.23@uom.lk',        '1234',     'student', '2026-09-03 11:45:00'),
(15, 'Sachini Ekanayake',     'sachini.24@uom.lk',        '1234',     'student', '2026-09-04 13:15:00'),
(16, 'Chamod Samarasinghe',   'chamod.22@uom.lk',         '1234',     'student', '2026-09-05 14:20:00'),
(17, 'Hiruni Karunaratne',    'hiruni.25@uom.lk',         '1234',     'student', '2026-09-06 16:05:00'),
(18, 'Pasindu Liyanage',      'pasindu.23@uom.lk',        '1234',     'student', '2026-09-07 17:30:00'),
(19, 'Amaya Wickramasinghe',  'amaya.24@uom.lk',          '1234',     'student', '2026-09-08 18:10:00'),
(20, 'Hasitha Gunawardena',   'hasitha.22@uom.lk',        '1234',     'student', '2026-09-09 19:00:00'),
(21, 'Dinithi Amarasinghe',   'dinithi.25@uom.lk',        '1234',     'student', '2026-09-10 08:40:00'),
(22, 'Supun Herath',          'supun.23@uom.lk',          '1234',     'student', '2026-09-11 10:25:00'),
(23, 'Sewmini Nanayakkara',   'sewmini.24@uom.lk',        '1234',     'student', '2026-09-12 12:15:00'),
(24, 'Malith Weerasinghe',    'malith.25@uom.lk',         '1234',     'student', '2026-09-13 15:00:00'),
(25, 'Oshadi Ranasinghe',     'oshadi.23@uom.lk',         '1234',     'student', '2026-09-14 17:45:00');

-- ============================================================================
-- 6. SEED BOARDING HOUSES (18 Listings around University of Moratuwa)
-- ============================================================================
INSERT INTO boarding_houses (house_id, owner_id, title, address, google_maps_link, distance_km, monthly_rent, gender_type, description, amenities, avg_rating, total_reviews, status, created_at) VALUES
(1, 1, 'Green View Student Villa', '124 Katubedda Road, Moratuwa', 'https://www.google.com/maps?q=6.7968,79.9018', 0.45, 15000.00, 'male',
'Quiet single and twin-sharing rooms just a 5-minute walk from the University of Moratuwa main gate. Dedicated study desks, ergonomic chairs, and high-speed fiber internet included in the rent.',
'WiFi,Attached Bathroom,Furnished,CCTV', 0, 0, 'approved', '2026-08-15 10:00:00'),

(2, 1, 'Lakefront Ladies Hostel', '45 Molpe Road, Katubedda', 'https://www.google.com/maps?q=6.7925,79.9032', 0.35, 14500.00, 'female',
'Safe and peaceful boarding house for female undergraduates overlooking Bolgoda breeze. Features 24/7 CCTV surveillance, biometric gate lock, shared pantry with refrigerator, and filtered drinking water.',
'WiFi,Attached Bathroom,Furnished,Kitchen,CCTV,Laundry', 0, 0, 'approved', '2026-08-16 11:30:00'),

(3, 5, 'Mora Gate Annex', '18 Bandaranayake Mawatha, Katubedda', 'https://www.google.com/maps?q=6.7955,79.9005', 0.15, 18500.00, 'male',
'Right opposite the back gate! Ideal for Engineering and IT students who stay late at labs. Individual electricity sub-meters, tiled floors, and private balcony space.',
'WiFi,Attached Bathroom,Parking,Furnished,CCTV', 0, 0, 'approved', '2026-08-18 09:20:00'),

(4, 6, 'Serene Girls Residence', '72/A Molpe Mawatha, Moratuwa', 'https://www.google.com/maps?q=6.7910,79.9040', 0.60, 13000.00, 'female',
'Home-away-from-home atmosphere managed by a retired teacher. Clean twin rooms, mosquito-netted windows, spacious common dining area, and washing machine access.',
'WiFi,Furnished,Kitchen,Laundry,CCTV', 0, 0, 'approved', '2026-08-20 14:00:00'),

(5, 7, 'Tech Hub Executive Rooms', '210 Galle Road, Katubedda Junction', 'https://www.google.com/maps?q=6.7982,79.8985', 0.85, 26000.00, 'mixed',
'Modern air-conditioned studio rooms near Katubedda Junction with supermarkets, pharmacies, and bus stops right downstairs. Perfect for final-year students working on projects.',
'WiFi,Attached Bathroom,AC,Parking,Furnished,Hot Water,CCTV,Laundry', 0, 0, 'approved', '2026-08-22 16:45:00'),

(6, 8, 'Palm Grove Bodima', '33 Temple Road, Rawathawatta, Moratuwa', 'https://www.google.com/maps?q=6.7850,79.8895', 1.60, 10500.00, 'male',
'Budget-friendly shared rooms surrounded by shady coconut trees. Calm study environment, bicycle parking, and optional home-cooked rice and curry meals.',
'WiFi,Parking,Furnished', 0, 0, 'approved', '2026-08-25 10:10:00'),

(7, 9, 'Bolgoda Breeze Villa', '89 Dampe Road, Madapatha, Piliyandala', 'https://www.google.com/maps?q=6.7890,79.9150', 2.40, 12000.00, 'mixed',
'Spacious two-storey house with large garden and covered parking for motorbikes. Direct bus route to the university gate every 10 minutes.',
'WiFi,Attached Bathroom,Parking,Furnished,Kitchen', 0, 0, 'approved', '2026-08-27 12:30:00'),

(8, 10, 'Lotus Lane Ladies Villa', '14/2 De Soysa Mawatha, Katubedda', 'https://www.google.com/maps?q=6.7960,79.8998', 0.40, 16500.00, 'female',
'Newly renovated upstairs annex exclusively for female students. Includes solar hot water, pantry with induction cooker, and high-speed SLT Fiber connection.',
'WiFi,Attached Bathroom,Furnished,Kitchen,Hot Water,CCTV,Laundry', 0, 0, 'approved', '2026-08-29 15:00:00'),

(9, 5, 'Scholars Nest Katubedda', '56 Campus Road, Rattanapitiya_Moratuwa', 'https://www.google.com/maps?q=6.7948,79.9022', 0.25, 17000.00, 'male',
'Designed specifically for university students with backup power for routers during power cuts, large whiteboards in the common study room, and individual lockers.',
'WiFi,Attached Bathroom,Furnished,CCTV,Laundry', 0, 0, 'approved', '2026-09-01 09:00:00'),

(10, 6, 'Orchid Court Student Living', '102 Piliyandala Road, Suwarapola', 'https://www.google.com/maps?q=6.7995,79.9120', 1.30, 14000.00, 'female',
'Very clean and airy rooms with private balconies. Strictly no outsiders allowed, ensuring complete privacy and safety for girls.',
'WiFi,Attached Bathroom,Furnished,Kitchen,CCTV', 0, 0, 'approved', '2026-09-03 11:15:00'),

(11, 7, 'Blue Horizon Studio', '405 Angulana Station Road, Moratuwa', 'https://www.google.com/maps?q=6.8030,79.8870', 2.10, 22000.00, 'mixed',
'Private studio units with AC, hot water showers, and a kitchenette. 5 minutes to Angulana railway station and 10 minutes by bike to UoM.',
'WiFi,Attached Bathroom,AC,Parking,Furnished,Kitchen,Hot Water,CCTV', 0, 0, 'approved', '2026-09-05 13:40:00'),

(12, 8, 'Kurusa Junction Boys Hostel', '19 Kurusa Junction, Katubedda', 'https://www.google.com/maps?q=6.7972,79.9045', 0.55, 9500.00, 'male',
'Most affordable boarding near campus for 1st and 2nd year boys. Walking distance to cheap night kades, print shops, and the main gate.',
'WiFi,Furnished,Parking', 0, 0, 'approved', '2026-09-07 16:20:00'),

(13, 9, 'Royal Palms Luxury Annex', '64 Koralawella Road, Moratuwa', 'https://www.google.com/maps?q=6.7720,79.8860', 2.80, 28500.00, 'mixed',
'Fully furnished luxury apartment floor suitable for 3–4 students sharing. Air-conditioned bedrooms, automatic washing machine, microwave, and garage parking.',
'WiFi,Attached Bathroom,AC,Parking,Furnished,Kitchen,Hot Water,CCTV,Laundry', 0, 0, 'approved', '2026-09-09 10:50:00'),

(14, 10, 'Sarasavi Uyana Boarding', '27 Sarasavi Mawatha, Katubedda', 'https://www.google.com/maps?q=6.7940,79.9012', 0.20, 16000.00, 'male',
'Just 200 meters from the Architecture & IT faculties. Spacious drafting/study tables, bright LED lighting, and no water pressure issues.',
'WiFi,Attached Bathroom,Furnished,CCTV,Laundry', 0, 0, 'approved', '2026-09-11 14:10:00'),

(15, 1, 'Ivy Garden Girls Annex', '81 Molpe Road, Katubedda', 'https://www.google.com/maps?q=6.7918,79.9048', 0.50, 15500.00, 'female',
' Surrounded by a landscaped garden with a peaceful outdoor study gazebo. Separate entrance, friendly landlady, and zero noise pollution.',
'WiFi,Attached Bathroom,Furnished,Kitchen,Hot Water,CCTV', 0, 0, 'approved', '2026-09-13 17:00:00'),

(16, 5, 'Sunrise Rooftop Rooms', '142 Piliyandala Road, Katubedda', 'https://www.google.com/maps?q=6.7988,79.9080', 0.95, 13500.00, 'male',
'Breezy 3rd-floor rooftop rooms with a great view and plenty of natural light. Includes laundry area and covered bike shed.',
'WiFi,Attached Bathroom,Parking,Furnished,Laundry', 0, 0, 'pending', '2026-10-01 09:30:00'),

(17, 6, 'Meadow View Ladies Stay', '52 Rawathawatta Road, Moratuwa', 'https://www.google.com/maps?q=6.7840,79.8910', 1.40, 14800.00, 'female',
'Brand-new annex awaiting final inspection. Single and double rooms with attached tiled bathrooms and pantry.',
'WiFi,Attached Bathroom,Furnished,Kitchen,CCTV', 0, 0, 'pending', '2026-10-02 11:00:00'),

(18, 8, 'Old Station Road Rooms', '9 Station Road, Moratuwa', 'https://www.google.com/maps?q=6.7780,79.8820', 3.10, 8000.00, 'male',
'Basic room near railway line.',
'Furnished', 0, 0, 'rejected', '2026-10-03 15:20:00');

-- ============================================================================
-- 7. SEED HOUSE IMAGES (46 Working High-Res Room & Exterior Photos)
-- ============================================================================
INSERT INTO house_images (house_id, image_path) VALUES
-- House 1
(1, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1000&q=80'),
(1, 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1000&q=80'),
(1, 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1000&q=80'),
-- House 2
(2, 'https://images.unsplash.com/photo-1540518614846-7ede433c457?auto=format&fit=crop&w=1000&q=80'),
(2, 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1000&q=80'),
(2, 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1000&q=80'),
-- House 3
(3, 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1000&q=80'),
(3, 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1000&q=80'),
(3, 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?auto=format&fit=crop&w=1000&q=80'),
-- House 4
(4, 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=1000&q=80'),
(4, 'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?auto=format&fit=crop&w=1000&q=80'),
-- House 5
(5, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1000&q=80'),
(5, 'https://images.unsplash.com/photo-1560448075-bb485b067938?auto=format&fit=crop&w=1000&q=80'),
(5, 'https://images.unsplash.com/photo-1584622781564-1d987f7333c1?auto=format&fit=crop&w=1000&q=80'),
-- House 6
(6, 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=1000&q=80'),
(6, 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1000&q=80'),
-- House 7
(7, 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1000&q=80'),
(7, 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1000&q=80'),
(7, 'https://images.unsplash.com/photo-1560185007-cde436f6a4d0?auto=format&fit=crop&w=1000&q=80'),
-- House 8
(8, 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1000&q=80'),
(8, 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=1000&q=80'),
(8, 'https://images.unsplash.com/photo-1620626011761-996317b8d101?auto=format&fit=crop&w=1000&q=80'),
-- House 9
(9, 'https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1000&q=80'),
(9, 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1000&q=80'),
(9, 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1000&q=80'),
-- House 10
(10, 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1000&q=80'),
(10, 'https://images.unsplash.com/photo-1560185127-6ed189bf02f4?auto=format&fit=crop&w=1000&q=80'),
-- House 11
(11, 'https://images.unsplash.com/photo-1536376072261-38c75010e6c9?auto=format&fit=crop&w=1000&q=80'),
(11, 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1000&q=80'),
(11, 'https://images.unsplash.com/photo-1585412727339-54e4bae3bbf9?auto=format&fit=crop&w=1000&q=80'),
-- House 12
(12, 'https://images.unsplash.com/photo-1595526051245-4506e0005bd0?auto=format&fit=crop&w=1000&q=80'),
(12, 'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?auto=format&fit=crop&w=1000&q=80'),
-- House 13
(13, 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1000&q=80'),
(13, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1000&q=80'),
(13, 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1000&q=80'),
-- House 14
(14, 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1000&q=80'),
(14, 'https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=1000&q=80'),
(14, 'https://images.unsplash.com/photo-1507652313519-d4e9174996dd?auto=format&fit=crop&w=1000&q=80'),
-- House 15
(15, 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1000&q=80'),
(15, 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=1000&q=80'),
(15, 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop&w=1000&q=80'),
-- House 16 (Pending)
(16, 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=1000&q=80'),
(16, 'https://images.unsplash.com/photo-1560448204-603b3fc33ddc?auto=format&fit=crop&w=1000&q=80'),
-- House 17 (Pending)
(17, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1000&q=80'),
(17, 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=1000&q=80'),
-- House 18 (Rejected)
(18, 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1000&q=80');

-- ============================================================================
-- 8. SEED REVIEWS (52 Authentic Student Reviews across Houses 1–15)
-- ============================================================================
INSERT INTO reviews (house_id, user_id, overall_rating, comment, created_at) VALUES
-- House 1: Green View Student Villa
(1, 2,  5, 'Very calm environment for studying and fast SLT Fiber Wi-Fi! Sunil uncle is super helpful whenever something needs fixing.', '2026-09-15 18:20:00'),
(1, 11, 4, 'Close to the main gate and reasonable monthly rent. Gets a bit warm in the afternoon, but overall a great place.', '2026-09-18 20:10:00'),
(1, 14, 5, 'Stayed here for two semesters. Water pressure is great and the attached bathroom is clean.', '2026-09-25 14:05:00'),
(1, 16, 4, 'Solid study tables and plenty of plug points for laptops and monitors.', '2026-10-02 21:40:00'),

-- House 2: Lakefront Ladies Hostel
(2, 12, 5, 'Highly secure place with clean facilities and CCTV at the gate. Walking to lectures takes less than 5 minutes.', '2026-09-16 19:00:00'),
(2, 13, 5, 'The Bolgoda breeze in the evening is lovely. Kitchen is well maintained and everyone keeps the fridge organized.', '2026-09-22 17:30:00'),
(2, 15, 4, 'Very safe for girls, even when returning after 7 PM kuppi sessions. Washing machine saves so much time.', '2026-10-01 20:15:00'),
(2, 17, 5, 'Best ladies boarding on Molpe Road! Zero mosquito issues thanks to the window nets.', '2026-10-08 16:45:00'),

-- House 3: Mora Gate Annex
(3, 18, 5, 'Literally 2 minutes to the back gate. You can wake up at 7:50 AM and still make an 8:00 AM lecture!', '2026-09-19 08:55:00'),
(3, 20, 5, 'Separate electricity meters per room so no arguments over bills. Bike parking is safe under a roof.', '2026-09-27 22:10:00'),
(3, 22, 4, 'A little pricier than inner-lane boardings, but the convenience and attached bath are 100% worth it.', '2026-10-04 19:30:00'),
(3, 24, 5, 'Fast internet and super quiet after 10 PM. Great for group project coding nights.', '2026-10-12 23:05:00'),

-- House 4: Serene Girls Residence
(4, 19, 4, 'Kamani aunty treats everyone like family. Rooms are neat and the rent is very fair.', '2026-09-20 15:40:00'),
(4, 21, 5, 'Super peaceful lane with lots of trees. Kitchen has gas and basic utensils ready to use.', '2026-09-29 18:25:00'),
(4, 23, 4, 'Shared bathroom is cleaned regularly. Good lighting and ventilation in all rooms.', '2026-10-06 12:10:00'),

-- House 5: Tech Hub Executive Rooms
(5, 11, 5, 'AC and hot water work flawlessly. Having Keells and Cargills right downstairs at Katubedda junction is unbeatable.', '2026-09-21 21:00:00'),
(5, 13, 5, 'Stayed here during our final year project. High-speed fiber never dropped once during online demos.', '2026-09-30 16:50:00'),
(5, 16, 4, 'Galle Road traffic noise is barely noticeable with the double-glazed windows closed. Very modern room.', '2026-10-09 20:35:00'),
(5, 25, 5, 'Worth every rupee if you want privacy, AC, and hotel-style bathrooms.', '2026-10-15 11:20:00'),

-- House 6: Palm Grove Bodima
(6, 14, 4, 'Great value for Rs. 10,500. Takes about 15 minutes by bus or 5 minutes by bike to campus.', '2026-09-23 19:15:00'),
(6, 18, 3, 'Rooms are basic and bathrooms are shared, but the auntys home-cooked dinner is delicious and cheap.', '2026-10-01 21:50:00'),
(6, 22, 4, 'Shady garden keeps the rooms cool even without AC. Friendly batchmates staying in the other rooms.', '2026-10-11 17:05:00'),

-- House 7: Bolgoda Breeze Villa
(7, 2,  4, 'Huge garden and plenty of space to park motorbikes. Easy 255 bus access right in front of the lane.', '2026-09-24 14:30:00'),
(7, 19, 5, 'Very spacious rooms compared to cramped Katubedda annexes. Quiet neighborhood near the lake.', '2026-10-03 18:00:00'),
(7, 20, 4, 'Kitchen is fully equipped and the owner never disturbs tenants.', '2026-10-14 20:45:00'),

-- House 8: Lotus Lane Ladies Villa
(8, 12, 5, 'Brand-new tiles and fittings! Solar hot water is amazing on rainy mornings.', '2026-09-26 09:30:00'),
(8, 15, 5, 'Induction cooker in the pantry is super convenient for making quick noodles or tea while studying.', '2026-10-05 22:00:00'),
(8, 21, 5, 'Only 400m to campus and the lane is well-lit at night. Highly recommended for girls.', '2026-10-16 19:20:00'),
(8, 25, 4, 'Clean, modern, and very responsive owner.', '2026-10-20 15:10:00'),

-- House 9: Scholars Nest Katubedda
(9, 11, 5, 'The UPS backup on the Wi-Fi router is a lifesaver during CEB power cuts! Never missed a deadline.', '2026-09-28 23:15:00'),
(9, 14, 5, 'Having a whiteboard in the common area helped our group practice presentations and solve math problems.', '2026-10-07 21:30:00'),
(9, 24, 4, 'Very close to Sysco Labs and the university gate. Good ventilation.', '2026-10-18 18:40:00'),

-- House 10: Orchid Court Student Living
(10, 13, 4, 'Lovely private balcony to dry clothes and get fresh air. Bus stop is just 100m away.', '2026-09-29 16:15:00'),
(10, 17, 5, 'Strict security and very clean premises. Ideal for students who prefer a quiet study routine.', '2026-10-10 19:50:00'),
(10, 23, 4, 'Good water supply and clean pantry area.', '2026-10-21 13:25:00'),

-- House 11: Blue Horizon Studio
(11, 16, 5, 'Awesome studio layout with AC and kitchenette. Great if you have a bike to commute to UoM.', '2026-09-30 20:05:00'),
(11, 20, 4, 'Very close to Angulana station and beach. Quiet and private.', '2026-10-12 17:55:00'),
(11, 22, 5, 'Hot water shower and fast internet. Feels like a mini apartment.', '2026-10-22 21:10:00'),

-- House 12: Kurusa Junction Boys Hostel
(12, 2,  3, 'Rooms are small, but at Rs. 9,500 you cannot complain. Right next to all the night kades at Kurusa Junction.', '2026-10-01 22:30:00'),
(12, 18, 4, 'Best budget spot for boys. 5-minute walk to campus and print shops.', '2026-10-13 19:00:00'),
(12, 24, 3, 'Can get a bit noisy when everyone is watching cricket, but great hostel vibe.', '2026-10-23 23:20:00'),

-- House 13: Royal Palms Luxury Annex
(13, 11, 5, 'Four of us shared this floor and split the rent—came to around 7k each for luxury AC rooms and a full kitchen!', '2026-10-02 18:10:00'),
(13, 15, 5, 'Automatic washing machine, microwave, and big living room. Best place if you are sharing with friends.', '2026-10-14 20:00:00'),
(13, 19, 5, 'Spotless interior and secure garage parking.', '2026-10-24 16:30:00'),

-- House 14: Sarasavi Uyana Boarding
(14, 14, 5, '200 meters from the IT faculty! Huge desks that easily fit a 27-inch monitor and laptop.', '2026-10-04 21:45:00'),
(14, 16, 5, 'Bright LED lights, zero water cuts, and super fast Wi-Fi.', '2026-10-15 22:15:00'),
(14, 20, 4, 'Very convenient location and friendly owner.', '2026-10-25 19:05:00'),
(14, 22, 5, 'Saved me hours of commuting every week. Highly recommended.', '2026-10-28 20:50:00'),

-- House 15: Ivy Garden Girls Annex
(15, 12, 5, 'The outdoor garden gazebo is my favorite place to read in the morning. Super calm and green.', '2026-10-05 10:20:00'),
(15, 17, 5, 'Separate entrance gives total independence, and the landlady is very kind.', '2026-10-17 18:35:00'),
(15, 21, 4, 'Clean attached bathrooms with hot water and good Wi-Fi coverage.', '2026-10-26 17:15:00'),
(15, 25, 5, 'Safe, quiet, and just a short walk down Molpe Road to the campus gate.', '2026-10-29 21:00:00');

-- ============================================================================
-- 9. SYNC avg_rating AND total_reviews AUTOMATICALLY FROM REVIEWS TABLE
-- ============================================================================
UPDATE boarding_houses b
LEFT JOIN (
    SELECT house_id, ROUND(AVG(overall_rating), 2) AS calc_avg, COUNT(*) AS calc_total
    FROM reviews
    GROUP BY house_id
) r ON b.house_id = r.house_id
SET b.avg_rating    = COALESCE(r.calc_avg, 0.00),
    b.total_reviews = COALESCE(r.calc_total, 0);