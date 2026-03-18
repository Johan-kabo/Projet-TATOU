-- Script pour vérifier la structure de la base de données gestion_inscription
USE `gestion_inscription`;

-- Afficher toutes les tables existantes
SHOW TABLES;

-- Afficher la structure des principales tables
DESCRIBE users;
DESCRIBE students;
DESCRIBE programs;
DESCRIBE registrations;

-- Afficher quelques données pour vérifier
SELECT * FROM users LIMIT 5;
SELECT * FROM programs LIMIT 5;
SELECT * FROM students LIMIT 5;
SELECT * FROM registrations LIMIT 5;
