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
    
    if ($action === 'modifier') {
        $id = intval($_POST['id']);
        $valeur = securiser($_POST['valeur']);
        $description = securiser($_POST['description']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("UPDATE statistiques SET valeur = ?, description = ?, ordre = ? WHERE id = ?");
            $stmt->execute([$valeur, $description, $ordre, $id]);
            logAdminAction($pdo, 'Modification statistique', 'statistiques', $id);
            $message = "Statistique modifiée avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    }
    
    elseif ($action === 'ajouter') {
      //  $cle = securiser($_POST['cle']);
        $valeur = securiser($_POST['valeur']);
        $description = securiser($_POST['description']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO statistiques (valeur, description, ordre )VALUES (?, ?, ?)");
            $stmt->execute([ $valeur, $description, $ordre]);
            logAdminAction($pdo, 'Ajout statistique', 'statistiques', $pdo->lastInsertId());
            $message = "Statistique ajoutée avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout (la clé existe peut-être déjà)";
        }
    }
    
    elseif ($action === 'supprimer') {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM statistiques WHERE id = ?");
            $stmt->execute([$id]);
            logAdminAction($pdo, 'Suppression statistique', 'statistiques', $id);
            $message = "Statistique supprimée !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Récupérer les statistiques
$statistiques = $pdo->query("SELECT * FROM statistiques ORDER BY ordre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Statistiques - Admin</title>
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
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: #f8f9fa; padding: 20px; border-radius: 10px;
            border-left: 5px solid #e74c3c;
        }
        
        .stat-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .stat-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .actions { display: flex; gap: 10px; }
        
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
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
            <li><a href="gestion_statistiques.php" class="active">📈 Statistiques</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>📈 Gestion des Statistiques</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-box">
            ℹ️ <strong>Info:</strong> Ces statistiques sont affichées sur la page d'accueil du site. 
            Modifiez les valeurs pour les mettre à jour en temps réel.
        </div>

        <div class="card">
            <h3>➕ Ajouter une Statistique</h3>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                  
                    <div class="form-group">
                        <label>Valeur affichée</label>
                        <input type="text" name="valeur" required placeholder="Ex: 500+">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" required placeholder="Ex: Projets réalisés">
                </div>
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" name="ordre" value="<?= count($statistiques) + 1 ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>

        <div class="card">
            <h3>📊 Statistiques Actuelles</h3>
            <div class="stat-grid">
                <?php foreach ($statistiques as $stat): ?>
                <div class="stat-card">
                    <div class="stat-header">
                        <strong>Ordre: <?= $stat['ordre'] ?></strong>
                    </div>
                    
                    <div class="stat-value"><?= htmlspecialchars($stat['valeur']) ?></div>
                    <div class="stat-description"><?= htmlspecialchars($stat['description']) ?></div>
                    
                    <div class="actions">
                        <button onclick="editStat(<?= htmlspecialchars(json_encode($stat)) ?>)" 
                                class="btn btn-success" style="padding: 8px 15px; font-size: 0.9rem;">
                            ✏️ Modifier
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette statistique ?')">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= $stat['id'] ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;">
                                🗑️ Supprimer
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 10px; max-width: 600px;">
            <span onclick="closeEdit()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2>✏️ Modifier la Statistique</h2>
            <form method="POST">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Clé: <code id="edit_cle_display"></code></label>
                </div>
                <div class="form-group">
                    <label>Valeur affichée</label>
                    <input type="text" name="valeur" id="edit_valeur" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" id="edit_description" required>
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
        function editStat(stat) {
            document.getElementById('edit_id').value = stat.id;
         //   document.getElementById('edit_cle_display').textContent = stat.cle;
            document.getElementById('edit_valeur').value = stat.valeur;
            document.getElementById('edit_description').value = stat.description;
            document.getElementById('edit_ordre').value = stat.ordre;
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