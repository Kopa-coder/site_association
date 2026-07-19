<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Vérifier si l'utilisateur a un rôle spécifique
function hasRole($role) {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
}

// Obtenir l'ID de l'admin connecté
function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Obtenir le nom complet de l'admin connecté
function getAdminName() {
    return ($_SESSION['admin_prenom'] ?? '') . ' ' . ($_SESSION['admin_nom'] ?? '');
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit();
    }
}

// Rediriger si connecté
function requireLogout() {
    if (isLoggedIn()) {
        header('Location: /admin/dashboard.php');
        exit();
    }
}

// Déconnexion
function logout() {
    session_unset();
    session_destroy();
    header('Location: admin/login.php');
    exit();
}

// Logger une action admin
function logAdminAction($pdo, $action, $table = null, $id_element = null, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_admin (admin_id, action, table_affectee, id_element, details, ip_adresse) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            getAdminId(),
            $action,
            $table,
            $id_element,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (PDOException $e) {
        // Log l'erreur mais ne pas arrêter l'exécution
        error_log("Erreur log admin: " . $e->getMessage());
    }
}