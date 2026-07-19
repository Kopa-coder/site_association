<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

// Logger la déconnexion avant de détruire la session
if (isLoggedIn()) {
    logAdminAction($pdo, 'Déconnexion', 'admins', getAdminId());
}

// Effectuer la déconnexion (détruit la session)
logout();

// Redirection vers le site de l'association
header('Location: http://localhost/site_association'); // Remplacez par l'URL correcte de votre site
exit();