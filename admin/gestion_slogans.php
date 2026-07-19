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
    
    if ($action === 'ajouter') {
        $texte = securiser($_POST['texte']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO slogans (texte, ordre) VALUES (?, ?)");
            $stmt->execute([$texte, $ordre]);
            logAdminAction($pdo, 'Ajout slogan', 'slogans', $pdo->lastInsertId());
            $message = "Slogan ajouté avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout";
        }
    }
    
    elseif ($action === 'modifier') {
        $id = intval($_POST['id']);
        $texte = securiser($_POST['texte']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("UPDATE slogans SET texte = ?, ordre = ? WHERE id = ?");
            $stmt->execute([$texte, $ordre, $id]);
            logAdminAction($pdo, 'Modification slogan', 'slogans', $id);
            $message = "Slogan modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    }
    
    elseif ($action === 'toggle_actif') {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("UPDATE slogans SET actif = NOT actif WHERE id = ?");
            $stmt->execute([$id]);
            logAdminAction($pdo, 'Toggle actif slogan', 'slogans', $id);
            $message = "Statut modifié !";
        } catch (PDOException $e) {
            $error = "Erreur";
        }
    }
    
    elseif ($action === 'supprimer') {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM slogans WHERE id = ?");
            $stmt->execute([$id]);
            logAdminAction($pdo, 'Suppression slogan', 'slogans', $id);
            $message = "Slogan supprimé !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Récupérer les slogans
$slogans = $pdo->query("SELECT * FROM slogans ORDER BY ordre ASC, date_ajout DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Slogans - Admin</title>
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
        .btn-danger { background: #e74c3c; color: white; }
        
        .alert {
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        
        .card h3 { color: #2c3e50; margin-bottom: 15px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 10px; border: 2px solid #ddd;
            border-radius: 5px; font-size: 1rem;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        
        .slogan-item {
            background: #f8f9fa; padding: 20px; border-radius: 10px;
            margin-bottom: 15px; border-left: 4px solid #e74c3c;
        }
        
        .slogan-item.inactive { opacity: 0.6; border-left-color: #95a5a6; }
        
        .slogan-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 15px;
        }
        
        .badge {
            padding: 5px 10px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        .actions { display: flex; gap: 10px; }
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
            <li><a href="gestion_slogans.php" class="active">💬 Slogans</a></li>
            <li><a href="gestion_temoignages.php">⭐ Témoignages</a></li>
            <li><a href="gestion_statistiques.php">📈 Statistiques</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>💬 Gestion des Slogans</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>➕ Ajouter un Slogan</h3>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-group">
                    <label>Texte du slogan</label>
                    <textarea name="texte" required placeholder="Entrez votre slogan..."></textarea>
                </div>
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" name="ordre" value="<?= count($slogans) + 1 ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>

        <div class="card">
            <h3>📝 Liste des Slogans (<?= count($slogans) ?>)</h3>
            
            <?php foreach ($slogans as $slogan): ?>
            <div class="slogan-item <?= !$slogan['actif'] ? 'inactive' : '' ?>">
                <div class="slogan-header">
                    <div>
                        <span class="badge <?= $slogan['actif'] ? 'badge-success' : 'badge-danger' ?>">
                            <?= $slogan['actif'] ? 'Actif' : 'Inactif' ?>
                        </span>
                        <strong>Ordre: <?= $slogan['ordre'] ?></strong>
                    </div>
                    <div class="actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_actif">
                            <input type="hidden" name="id" value="<?= $slogan['id'] ?>">
                            <button type="submit" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.85rem;">
                                <?= $slogan['actif'] ? '👁️ Désactiver' : '👁️ Activer' ?>
                            </button>
                        </form>
                        <button onclick="editSlogan(<?= htmlspecialchars(json_encode($slogan)) ?>)" 
                                class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;">
                            ✏️ Modifier
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce slogan ?')">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= $slogan['id'] ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">
                                🗑️ Supprimer
                            </button>
                        </form>
                    </div>
                </div>
                <p style="font-style: italic; color: #555;"><?= htmlspecialchars($slogan['texte']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 10px; max-width: 600px;">
            <span onclick="closeEdit()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2>✏️ Modifier le Slogan</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Texte du slogan</label>
                    <textarea name="texte" id="edit_texte" required></textarea>
                </div>
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" name="ordre" id="edit_ordre" required>
                </div>
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
        function editSlogan(slogan) {
            document.getElementById('edit_id').value = slogan.id;
            document.getElementById('edit_texte').value = slogan.texte;
            document.getElementById('edit_ordre').value = slogan.ordre;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEdit() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>