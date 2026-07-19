<?php
session_start();
require_once 'config/database.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupérer et nettoyer les données
    $auteur = trim($_POST['auteur'] ?? '');
    $fonction = trim($_POST['fonction'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $accepte_publication = isset($_POST['accepte_publication']) ? 1 : 0;
    
    // Validation des données
    $erreurs = [];
    
    if (empty($auteur)) {
        $erreurs[] = "Le nom est requis.";
    }
    
    if (empty($contenu)) {
        $erreurs[] = "Le contenu du témoignage est requis.";
    }
    
    if (strlen($contenu) < 20) {
        $erreurs[] = "Le témoignage doit contenir au moins 20 caractères.";
    }
    
    if (!$accepte_publication) {
        $erreurs[] = "Vous devez accepter la publication de votre témoignage.";
    }
    
    // Si pas d'erreurs, enregistrer dans la base de données
    if (empty($erreurs)) {
        try {
            // Récupérer le prochain numéro d'ordre
            $stmt_ordre = $pdo->query("SELECT COALESCE(MAX(ordre), 0) + 1 AS prochain_ordre FROM temoignages");
            $prochain_ordre = $stmt_ordre->fetch()['prochain_ordre'];
            
            // Insérer le témoignage avec actif = 0 (en attente de validation)
            $sql = "INSERT INTO temoignages (auteur, fonction, contenu, ordre, actif, date_ajout) 
                    VALUES (:auteur, :fonction, :contenu, :ordre, 0, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':auteur' => $auteur,
                ':fonction' => $fonction,
                ':contenu' => $contenu,
                ':ordre' => $prochain_ordre
            ]);
            
            // Redirection avec message de succès
            header('Location: index.php?temoignage_success=1#laisser-temoignage');
            exit();
            
        } catch (PDOException $e) {
            // En cas d'erreur de base de données
            error_log("Erreur insertion témoignage: " . $e->getMessage());
            header('Location: index.php?temoignage_error=1#laisser-temoignage');
            exit();
        }
    } else {
        // Redirection avec erreurs
        $_SESSION['temoignage_erreurs'] = $erreurs;
        header('Location: index.php?temoignage_error=1#laisser-temoignage');
        exit();
    }
    
} else {
    // Si accès direct sans POST, rediriger vers l'accueil
    header('Location: index.php');
    exit();
}
?>