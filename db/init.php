<?php

require_once __DIR__ . '/connection.php';

/**
 * Initialize the database schema and seed demo data when needed.
 */
function initializeDatabase(): void
{
    $db = getDb();

    // Create tables if they do not exist
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    program TEXT NOT NULL,
    level TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Active',
    joined DATE NOT NULL,
    avatar TEXT NULL
);

CREATE TABLE IF NOT EXISTS programs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT NULL,
    duration INTEGER NULL,
    code TEXT NULL
);

CREATE TABLE IF NOT EXISTS registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    program TEXT NOT NULL,
    date DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    amount INTEGER NOT NULL DEFAULT 0,
    code TEXT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
SQL
    );

    // Seed initial data if empty
    $hasStudents = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
    if ($hasStudents === 0) {
        $stmt = $db->prepare('INSERT INTO students (name, email, program, level, status, joined, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(['Janot NKENG', 'nkengjanot@gmail.com', 'Gestion', 'L2', 'Pending', '2026-03-17', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop']);
        $stmt->execute(['Alex TAMO', 'tamoalex@gmail.com', 'Médecine', 'L3', 'Active', '2026-03-17', 'https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=80&h=80&fit=crop']);
        $stmt->execute(['Johan Manuel', 'kabojohan@gmail.com', 'Informatique', 'L3', 'Active', '2026-03-16', 'https://images.unsplash.com/photo-1552058544-f2b08422138a?w=80&h=80&fit=crop']);
        $stmt->execute(['Junior TATOU', 'junior@gmail.com', 'Informatique', 'L2', 'Active', '2026-03-16', 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?w=80&h=80&fit=crop']);
    }

    $hasPrograms = (int) $db->query('SELECT COUNT(*) FROM programs')->fetchColumn();
    if ($hasPrograms === 0) {
        $stmt = $db->prepare('INSERT INTO programs (name, description, duration, code) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Filière IA', 'Programme destiné aux étudiants prêts à travailler avec l\'IA.', 5, 'IA']);
        $stmt->execute(['Filière Gestion', 'Parcours complet pour les futurs managers.', 6, 'MG']);
        $stmt->execute(['Filière Informatique', 'Devenez développeur, administrateur système ou data engineer.', 6, 'INFO']);
    }

    $hasRegs = (int) $db->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
    if ($hasRegs === 0) {
        $studentId = (int) $db->query("SELECT id FROM students WHERE name = 'Janot NKENG' LIMIT 1")->fetchColumn();
        $stmt = $db->prepare('INSERT INTO registrations (student_id, program, date, status, amount, code) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$studentId, 'Gestion', '2026-03-17', 'pending', 150000, 'REG-6oMj']);

        $studentId = (int) $db->query("SELECT id FROM students WHERE name = 'Alex TAMO' LIMIT 1")->fetchColumn();
        $stmt->execute([$studentId, 'Médecine', '2026-03-17', 'paid', 150000, 'REG-9ccn']);

        $studentId = (int) $db->query("SELECT id FROM students WHERE name = 'Johan Manuel' LIMIT 1")->fetchColumn();
        $stmt->execute([$studentId, 'Informatique', '2026-03-16', 'paid', 150000, 'REG-IG5x']);
    }
}

initializeDatabase();
