<?php
// Demo data used by the platform.

$students = [
    [
        'name' => 'Janot NKENG',
        'email' => 'nkengjanot@gmail.com',
        'program' => 'Gestion',
        'level' => 'L2',
        'status' => 'Pending',
        'joined' => '2026-03-17',
        'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop',
    ],
    [
        'name' => 'Alex TAMO',
        'email' => 'tamoalex@gmail.com',
        'program' => 'Médecine',
        'level' => 'L3',
        'status' => 'Active',
        'joined' => '2026-03-17',
        'avatar' => 'https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=80&h=80&fit=crop',
    ],
    [
        'name' => 'Johan Manuel',
        'email' => 'kanojohan@gmail.com',
        'program' => 'Informatique',
        'level' => 'L3',
        'status' => 'Active',
        'joined' => '2026-03-16',
        'avatar' => 'https://images.unsplash.com/photo-1552058544-f2b08422138a?w=80&h=80&fit=crop',
    ],
    [
        'name' => 'Junior TATOU',
        'email' => 'junior@gmail.com',
        'program' => 'Informatique',
        'level' => 'L2',
        'status' => 'Active',
        'joined' => '2026-03-16',
        'avatar' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?w=80&h=80&fit=crop',
    ],
];

$programs = [
    [
        'name' => 'Filière IA',
        'description' => 'Programme destiné aux étudiants prêts à travailler avec l\'IA.',
        'duration' => 5,
        'code' => 'IA',
    ],
    [
        'name' => 'Filière Gestion',
        'description' => 'Parcours complet pour les futurs managers.',
        'duration' => 6,
        'code' => 'MG',
    ],
    [
        'name' => 'Filière Informatique',
        'description' => 'Devenez développeur, administrateur système ou data engineer.',
        'duration' => 6,
        'code' => 'INFO',
    ],
];

$registrations = [
    [
        'student' => 'Janot NKENG',
        'program' => 'Gestion',
        'date' => '2026-03-17',
        'status' => 'pending',
        'amount' => 150000,
        'code' => 'REG-6oMj',
    ],
    [
        'student' => 'Alex TAMO',
        'program' => 'Médecine',
        'date' => '2026-03-17',
        'status' => 'paid',
        'amount' => 150000,
        'code' => 'REG-9ccn',
    ],
    [
        'student' => 'Johan Manuel',
        'program' => 'Informatique',
        'date' => '2026-03-16',
        'status' => 'paid',
        'amount' => 150000,
        'code' => 'REG-IG5x',
    ],
];
