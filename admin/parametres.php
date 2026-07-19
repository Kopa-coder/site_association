<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'changer_mot_de_passe') {
        $ancien_mdp = $_POST['ancien_mdp'];
        $nouveau_mdp = $_POST['nouveau_mdp'];
        $confirmer_mdp = $_POST['confirmer_mdp'];
        
        if ($nouveau_mdp !== $confirmer_mdp) {
            $error = "Les mots de passe ne correspondent pas";
        } elseif (strlen($nouveau_mdp) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères";
        } else {
            try {
                // Vérifier l'ancien mot de passe
                $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
                $stmt->execute([getAdminId()]);
                $admin = $stmt->fetch();
                
                if (password_verify($ancien_mdp, $admin['password'])) {
                    $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $stmt->execute([$nouveau_hash, getAdminId()]);
                    logAdminAction($pdo, 'Changement mot de passe', 'admins', getAdminId());
                    $message = "Mot de passe modifié avec succès !";
                } else {
                    $error = "Ancien mot de passe incorrect";
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de la modification";
            }
        }
    }
    
    elseif ($action === 'modifier_profil') {
        $nom = securiser($_POST['nom']);
        $prenom = securiser($_POST['prenom']);
        $email = securiser($_POST['email']);
        
        try {
            $stmt = $pdo->prepare("UPDATE admins SET nom = ?, prenom = ?, email = ? WHERE id = ?");
            $stmt->execute([$nom, $prenom, $email, getAdminId()]);
            
            // Mettre à jour la session
            $_SESSION['admin_nom'] = $nom;
            $_SESSION['admin_prenom'] = $prenom;
            $_SESSION['admin_email'] = $email;
            
            logAdminAction($pdo, 'Modification profil', 'admins', getAdminId());
            $message = "Profil modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    }
}

// Récupérer les infos de l'admin
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([getAdminId()]);
$admin = $stmt->fetch();

// Récupérer les dernières actions
$logs = $pdo->prepare("SELECT * FROM logs_admin WHERE admin_id = ? ORDER BY date_action DESC LIMIT 20");
$logs->execute([getAdminId()]);
$activites = $logs->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 250px;
            background: #2c3e50; color: white; padding: 20px 0; overflow-y: auto;
        }
        
        .sidebar-header { padding: 0 20px 20px; border-bottom: 1px solid #34495e; margin-bottom: 20px; }
        .sidebar-header h2 { font-size: 1.2rem; color: #f39c12; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin: 5px 0; }
        .sidebar-menu a {
            display: block; padding: 12px 20px; color: white;
            text-decoration: none; transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #34495e; border-left: 4px solid #f39c12;
        }
        
        .main-content { margin-left: 250px; padding: 20px; }
        
        .top-bar {
            background: white; padding: 20px; border-radius: 10px;
            margin-bottom: 20px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .top-bar h1 { color: #2c3e50; font-size: 1.8rem; }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 5px;
            cursor: pointer; text-decoration: none; display: inline-block;
            font-weight: 600; transition: all 0.3s;
        }
        
        .btn-primary { background: #e74c3c; color: white; }
        .btn-primary:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-success { background: #27ae60; color: white; }
        
        .alert {
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        
        .card h3 { color: #2c3e50; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #ecf0f1; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 10px; border: 2px solid #ddd;
            border-radius: 5px; font-size: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .badge {
            padding: 5px 10px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-primary { background: #cce5ff; color: #004085; }
        
        .activity-item {
            padding: 15px;
            border-left: 3px solid #e74c3c;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .activity-item .time {
            font-size: 0.85rem;
            color: #666;
        }
        
        .activity-item .action {
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>⚙️ Administration</h2>
            <p><?= htmlspecialchars(getAdminName()) ?></p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="gestion_livres.php">📚 Gestion Livres</a></li>
            <li><a href="gestion_dons.php">💝 Gestion Dons</a></li>
            <li><a href="gestion_commandes.php">📦 Commandes</a></li>
            <li><a href="gestion_contenu.php">✏️ Contenu Site</a></li>
            <li><a href="gestion_slogans.php">💬 Slogans</a></li>
            <li><a href="gestion_temoignages.php">⭐ Témoignages</a></li>
            <li><a href="gestion_statistiques.php">📈 Statistiques</a></li>
            <li><a href="parametres.php" class="active">⚙️ Paramètres</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>⚙️ Paramètres</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>👤 Informations du Compte</h3>
            <div class="info-grid">
                <div class="info-label">Nom d'utilisateur:</div>
                <div class="info-value"><strong><?= htmlspecialchars($admin['username']) ?></strong></div>
                
                <div class="info-label">Rôle:</div>
                <div class="info-value">
                    <span class="badge badge-primary"><?= htmlspecialchars($admin['role']) ?></span>
                </div>
                
                <div class="info-label">Statut:</div>
                <div class="info-value">
                    <span class="badge badge-success"><?= ucfirst($admin['statut']) ?></span>
                </div>
                
                <div class="info-label">Compte créé le:</div>
                <div class="info-value"><?= date('d/m/Y à H:i', strtotime($admin['date_creation'])) ?></div>
                
                <div class="info-label">Dernière connexion:</div>
                <div class="info-value">
                    <?= $admin['derniere_connexion'] ? date('d/m/Y à H:i', strtotime($admin['derniere_connexion'])) : 'Jamais' ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>✏️ Modifier mon Profil</h3>
            <form method="POST">
                <input type="hidden" name="action" value="modifier_profil">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($admin['nom']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($admin['prenom']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
            </form>
        </div>

        <div class="card">
            <h3>🔒 Changer mon Mot de Passe</h3>
            <form method="POST">
                <input type="hidden" name="action" value="changer_mot_de_passe">
                <div class="form-group">
                    <label>Ancien mot de passe</label>
                    <input type="password" name="ancien_mdp" required>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe (min. 6 caractères)</label>
                    <input type="password" name="nouveau_mdp" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirmer_mdp" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">🔐 Changer le mot de passe</button>
            </form>
        </div>

        <div class="card">
            <h3>📜 Historique d'Activité</h3>
            <?php if (count($activites) > 0): ?>
                <?php foreach ($activites as $act): ?>
                <div class="activity-item">
                    <div class="action"><?= htmlspecialchars($act['action']) ?></div>
                    <?php if ($act['details']): ?>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                            <?= htmlspecialchars($act['details']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="time">
                        🕐 <?= date('d/m/Y à H:i:s', strtotime($act['date_action'])) ?>
                        <?php if ($act['ip_adresse']): ?>
                            • IP: <?= htmlspecialchars($act['ip_adresse']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucune activité enregistrée</p>
            <?php endif; ?>
        </div>

        <div class="card" style="background: #fff3cd; border-left: 4px solid #856404;">
            <h3 style="color: #856404;">⚠️ Zone Dangereuse</h3>
            <p style="margin-bottom: 15px; color: #856404;">
                Actions irréversibles qui affectent votre compte ou le système.
            </p>
            <a href="logout.php" class="btn btn-danger">🚪 Se déconnecter</a>
        </div>
    </div>
</body>
</html>