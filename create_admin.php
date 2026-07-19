<?php
/**
 * Script de création du compte administrateur
 * À exécuter une seule fois pour créer le compte admin
 * Ensuite SUPPRIMER ce fichier pour des raisons de sécurité
 */

require_once 'config/database.php';

echo "<h2>Création du compte administrateur</h2>";

// Vérifier si un admin existe déjà
$check = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

if ($check > 0) {
    echo "<p style='color: orange;'>⚠️ Un administrateur existe déjà dans la base de données.</p>";
    echo "<p>Pour réinitialiser, cliquez ci-dessous :</p>";
    
    if (isset($_GET['reset'])) {
        $pdo->exec("TRUNCATE TABLE admins");
        echo "<p style='color: green;'>✅ Table admins réinitialisée.</p>";
        header("Refresh: 2; url=create_admin.php");
        exit;
    }
    
    echo "<a href='?reset=1' style='padding: 10px 20px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px;'>Réinitialiser et créer un nouvel admin</a>";
    echo "<br><br>";
}

// Formulaire de création
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($nom) || empty($prenom)) {
        echo "<p style='color: red;'>❌ Tous les champs sont obligatoires</p>";
    } elseif (strlen($password) < 6) {
        echo "<p style='color: red;'>❌ Le mot de passe doit contenir au moins 6 caractères</p>";
    } else {
        try {
            // Hash du mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertion
            $stmt = $pdo->prepare("
                INSERT INTO admins (username, email, password, nom, prenom, role, statut) 
                VALUES (?, ?, ?, ?, ?, 'super_admin', 'actif')
            ");
            
            $stmt->execute([$username, $email, $hashedPassword, $nom, $prenom]);
            
            echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h3>✅ Compte administrateur créé avec succès !</h3>";
            echo "<p><strong>Nom d'utilisateur:</strong> " . htmlspecialchars($username) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
            echo "<p><strong>Mot de passe:</strong> (celui que vous avez entré)</p>";
            echo "<br>";
            echo "<p><a href='admin/login.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>→ Se connecter maintenant</a></p>";
            echo "</div>";
            
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
            echo "<strong>⚠️ IMPORTANT : Supprimez ce fichier create_admin.php pour des raisons de sécurité !</strong>";
            echo "</div>";
            
            exit;
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erreur lors de la création : " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte administrateur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #e74c3c;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #c0392b;
        }
        .info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Créer un Compte Administrateur</h2>
        
        <div class="info">
            <strong>ℹ️ Information:</strong> Ce formulaire permet de créer le compte administrateur principal. 
            Après création, supprimez ce fichier pour la sécurité de votre site.
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required placeholder="Ex: admin">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="admin@example.com">
            </div>

            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" required placeholder="Votre nom">
            </div>

            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" required placeholder="Votre prénom">
            </div>

            <div class="form-group">
                <label>Mot de passe (minimum 6 caractères)</label>
                <input type="password" name="password" required placeholder="••••••">
            </div>

            <button type="submit">Créer le compte</button>
        </form>
    </div>
</body>
</html>