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
    
    if ($action === 'modifier_section') {
        $section_id = intval($_POST['section_id']);
        $titre = securiser($_POST['titre']);
        $contenu = securiser($_POST['contenu']);
        
        try {
            $stmt = $pdo->prepare("UPDATE sections_site SET titre = ?, contenu = ? WHERE id = ?");
            $stmt->execute([$titre, $contenu, $section_id]);
            logAdminAction($pdo, 'Modification section', 'sections_site', $section_id);
            $message = "Section modifiée avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    }
    
    elseif ($action === 'ajouter_section') {
        $section_key = securiser($_POST['section_key']);
        $titre = securiser($_POST['titre']);
        $contenu = securiser($_POST['contenu']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO sections_site (section_key, titre, contenu, ordre) VALUES (?, ?, ?, ?)");
            $stmt->execute([$section_key, $titre, $contenu, $ordre]);
            logAdminAction($pdo, 'Ajout section', 'sections_site', $pdo->lastInsertId());
            $message = "Section ajoutée avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout";
        }
    }
    
    elseif ($action === 'toggle_visible') {
        $section_id = intval($_POST['section_id']);
        try {
            $stmt = $pdo->prepare("UPDATE sections_site SET visible = NOT visible WHERE id = ?");
            $stmt->execute([$section_id]);
            logAdminAction($pdo, 'Toggle visibilité section', 'sections_site', $section_id);
            $message = "Visibilité modifiée !";
        } catch (PDOException $e) {
            $error = "Erreur";
        }
    }
}

// Récupérer les sections
$sections = $pdo->query("SELECT * FROM sections_site ORDER BY ordre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion du Contenu - Admin</title>
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
        
        .section-card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        
        .section-card h3 { color: #2c3e50; margin-bottom: 15px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 10px; border: 2px solid #ddd;
            border-radius: 5px; font-size: 1rem;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        
        .badge {
            padding: 5px 10px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
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
            <li><a href="gestion_contenu.php" class="active">✏️ Contenu Site</a></li>
            <li><a href="gestion_slogans.php">💬 Slogans</a></li>
            <li><a href="gestion_temoignages.php">⭐ Témoignages</a></li>
            <li><a href="gestion_statistiques.php">📈 Statistiques</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>✏️ Gestion du Contenu</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="section-card">
            <h3>➕ Ajouter une Section</h3>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_section">
                <div class="form-group">
                    <label>Clé de la section (ex: hero, mission, contact)</label>
                    <input type="text" name="section_key" required>
                </div>
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="titre" required>
                </div>
                <div class="form-group">
                    <label>Contenu</label>
                    <textarea name="contenu" required></textarea>
                </div>
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" name="ordre" value="0" required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>

        <?php foreach ($sections as $section): ?>
        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3><?= htmlspecialchars($section['titre']) ?></h3>
                <div>
                    <span class="badge <?= $section['visible'] ? 'badge-success' : 'badge-danger' ?>">
                        <?= $section['visible'] ? 'Visible' : 'Masqué' ?>
                    </span>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle_visible">
                        <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                        <button type="submit" class="btn btn-secondary" style="padding: 5px 10px;">
                            <?= $section['visible'] ? '👁️ Masquer' : '👁️ Afficher' ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="modifier_section">
                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                
                <div class="form-group">
                    <label>Clé: <code><?= htmlspecialchars($section['section_key']) ?></code></label>
                </div>
                
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="titre" value="<?= htmlspecialchars($section['titre']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Contenu</label>
                    <textarea name="contenu" required><?= htmlspecialchars($section['contenu']) ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>