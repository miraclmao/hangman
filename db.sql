CREATE DATABASE IF NOT EXISTS galgje
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE galgje;

CREATE TABLE IF NOT EXISTS woorden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    woord VARCHAR(255) NOT NULL UNIQUE
);

INSERT INTO woorden (woord) VALUES
('computer'),
('programmeren'),
('school'),
('database'),
('php'),
('keyboard'),
('monitor'),
('internet'),
('developer'),
('software'),
('hardware'),
('javascript'),
('python'),
('network'),
('website'),
('gaming'),
('elephant'),
('mountain'),
('holiday'),
('football'),
('backpack'),
('sunshine'),
('airplane'),
('hospital'),
('telephone'),
('language'),
('notebook'),
('building'),
('calendar'),
('adventure'),
('umbrella'),
('watermelon'),
('chocolate'),
('sandwich'),
('bicycle'),
('dinosaur'),
('firefighter'),
('astronaut'),
('penguin'),
('kangaroo'),
('restaurant'),
('microphone'),
('newspaper'),
('scientist'),
('education');
