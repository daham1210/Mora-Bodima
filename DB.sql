create user if not exists 'superuser'@'localhost' identified by '1234';	
GRANT ALL PRIVILEGES ON morabodima.* TO 'superuser'@'localhost';

Create database if not exists morabodima;
use morabodima;

CREATE TABLE IF NOT EXISTS users (User VARCHAR(50) PRIMARY KEY,name varchar(50),pass VARCHAR(255) NOT NULL,role varchar(20));
INSERT IGNORE INTO users (User,name, pass,role) VALUES ('Superuser','Admin','1234','super');


