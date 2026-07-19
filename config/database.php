<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'cris_orphelins');
define('DB_USER', 'root'); // Changez selon votre configuration
define('DB_PASS', ''); // Changez selon votre configuration
define('DB_CHARSET', 'utf8mb4');

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour sécuriser les entrées
function securiser($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fonction pour générer une référence unique
function genererReference($prefix = 'REF') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Variable globale pour PDO
$GLOBALS['pdo'] = $pdo;