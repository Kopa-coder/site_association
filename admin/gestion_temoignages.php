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
        $texte = securiser($_POST['contenu']);
        $auteur = securiser($_POST['auteur']);
        $role_description = securiser($_POST['fonction']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO temoignages (auteur, fonction, contenu, ordre) VALUES (?,?,?,?)");
            $stmt->execute([$auteur, $role_description, $texte, $ordre]);
            logAdminAction($pdo, 'Ajout témoignage', 'temoignages', $pdo->lastInsertId());
            $message = "Témoignage ajouté avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout";
        }
    }
    
    elseif ($action === 'modifier') {
        $id = intval($_POST['id']);
        $texte = securiser($_POST['contenu']);
        $auteur = securiser($_POST['auteur']);
        $role_description = securiser($_POST['fonction']);
        $ordre = intval($_POST['ordre']);
        
        try {
            $stmt = $pdo->prepare("UPDATE temoignages SET contenu = ?, auteur = ?, fonction = ?, ordre = ? WHERE id = ?");
            $stmt->execute([$texte, $auteur, $role_description, $ordre, $id]);
            logAdminAction($pdo, 'Modification témoignage', 'temoignages', $id);
            $message = "Témoignage modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    }
    
    elseif ($action === 'toggle_actif') {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("UPDATE temoignages SET actif = NOT actif WHERE id = ?");
            $stmt->execute([$id]);
            logAdminAction($pdo, 'Toggle actif témoignage', 'temoignages', $id);
            $message = "Statut modifié !";
        } catch (PDOException $e) {
            $error = "Erreur";
        }
    }
    
    elseif ($action === 'supprimer') {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM temoignages WHERE id = ?");
            $stmt->execute([$id]);
            logAdminAction($pdo, 'Suppression témoignage', 'temoignages', $id);
            $message = "Témoignage supprimé !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Récupérer les témoignages
$temoignages = $pdo->query("SELECT * FROM temoignages ORDER BY ordre ASC, date_ajout DESC")->fetchAll();

// Séparer les témoignages actifs et en attente
$temoignages_attente = array_filter($temoignages, function($t) { return !$t['actif']; });
$temoignages_actifs = array_filter($temoignages, function($t) { return $t['actif']; });
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Témoignages - Admin</title>
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
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        
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
        .form-group textarea { min-height: 100px; resize: vertical; }
        
        .testimonial-card {
            background: #f8f9fa; padding: 20px; border-radius: 10px;
            margin-bottom: 15px; border-left: 5px solid #e74c3c;
        }
        
        .testimonial-card.inactive { opacity: 0.6; border-left-color: #95a5a6; }
        
        .testimonial-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 15px;
        }
        
        .badge {
            padding: 5px 10px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .testimonial-text {
            font-style: italic;
            color: #555;
            line-height: 1.8;
            margin-bottom: 10px;
        }
        
        .testimonial-author {
            font-weight: bold;
            color: #e74c3c;
        }

        .empty-state {
            text-align: center;
            color: #999;
            padding: 30px;
            font-style: italic;
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
            <li><a href="gestion_temoignages.php" class="active">⭐ Témoignages</a></li>
            <li><a href="gestion_statistiques.php">📈 Statistiques</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>⭐ Gestion des Témoignages</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>➕ Ajouter un Témoignage</h3>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-group">
                    <label>Texte du témoignage</label>
                    <textarea name="contenu" required placeholder="Le témoignage..."></textarea>
                </div>
                <div class="form-group">
                    <label>Nom de l'auteur</label>
                    <input type="text" name="auteur" required placeholder="Ex: Sarah, 24 ans">
                </div>
                <div class="form-group">
                    <label>Rôle / Description</label>
                    <input type="text" name="fonction" placeholder="Ex: ancienne bénéficiaire">
                </div>
                <div class="form-group">
                    <label>Ordre d'affichage</label>
                    <input type="number" name="ordre" value="<?= count($temoignages) + 1 ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>

        <div class="card">
            <h3>⏳ Témoignages en Attente de Validation (<?= count($temoignages_attente) ?>)</h3>
            
            <?php if (count($temoignages_attente) > 0): ?>
                <?php foreach ($temoignages_attente as $temoignage): ?>
                <div class="testimonial-card" style="border-left-color: #ffc107; background: #fffbf0;">
                    <div class="testimonial-header">
                        <div>
                            <span class="badge badge-warning">⏳ En attente</span>
                            <strong style="margin-left: 10px;">Ordre: <?= $temoignage['ordre'] ?></strong>
                            <small style="color: #666; margin-left: 10px;">
                                Ajouté le <?= date('d/m/Y à H:i', strtotime($temoignage['date_ajout'])) ?>
                            </small>
                        </div>
                        <div class="actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_actif">
                                <input type="hidden" name="id" value="<?= $temoignage['id'] ?>">
                                <button type="submit" class="btn btn-success" style="padding: 5px 15px; font-size: 0.85rem;">
                                    ✅ Approuver
                                </button>
                            </form>
                            <button onclick="editTestimonial(<?= htmlspecialchars(json_encode($temoignage)) ?>)" 
                                    class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.85rem;">
                                ✏️ Modifier
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Rejeter ce témoignage ?')">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $temoignage['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">
                                    ❌ Rejeter
                                </button>
                            </form>
                        </div>
                    </div>
                    <p class="testimonial-text">"<?= nl2br(htmlspecialchars($temoignage['contenu'])) ?>"</p>
                    <p class="testimonial-author">
                        - <?= htmlspecialchars($temoignage['auteur']) ?>
                        <?php if ($temoignage['fonction']): ?>
                            , <?= htmlspecialchars($temoignage['fonction']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">
                    ✅ Aucun témoignage en attente de validation
                </p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>✅ Témoignages Approuvés (<?= count($temoignages_actifs) ?>)</h3>
            
            <?php if (count($temoignages_actifs) > 0): ?>
                <?php foreach ($temoignages_actifs as $temoignage): ?>
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div>
                            <span class="badge badge-success">✅ Actif</span>
                            <strong style="margin-left: 10px;">Ordre: <?= $temoignage['ordre'] ?></strong>
                        </div>
                        <div class="actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_actif">
                                <input type="hidden" name="id" value="<?= $temoignage['id'] ?>">
                                <button type="submit" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.85rem;">
                                    👁️ Désactiver
                                </button>
                            </form>
                            <button onclick="editTestimonial(<?= htmlspecialchars(json_encode($temoignage)) ?>)" 
                                    class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;">
                                ✏️ Modifier
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce témoignage ?')">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $temoignage['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">
                                    🗑️ Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <p class="testimonial-text">"<?= nl2br(htmlspecialchars($temoignage['contenu'])) ?>"</p>
                    <p class="testimonial-author">
                        - <?= htmlspecialchars($temoignage['auteur']) ?>
                        <?php if ($temoignage['fonction']): ?>
                            , <?= htmlspecialchars($temoignage['fonction']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">
                    Aucun témoignage approuvé pour le moment
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 10px; max-width: 600px;">
            <span onclick="closeEdit()" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2>✏️ Modifier le Témoignage</h2>
            <form method="POST">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Texte du témoignage</label>
                    <textarea name="contenu" id="edit_texte" required></textarea>
                </div>
                <div class="form-group">
                    <label>Nom de l'auteur</label>
                    <input type="text" name="auteur" id="edit_auteur" required>
                </div>
                <div class="form-group">
                    <label>Rôle / Description</label>
                    <input type="text" name="fonction" id="edit_role">
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
        function editTestimonial(temoignage) {
            document.getElementById('edit_id').value = temoignage.id;
            document.getElementById('edit_texte').value = temoignage.contenu;
            document.getElementById('edit_auteur').value = temoignage.auteur;
            document.getElementById('edit_role').value = temoignage.fonction || '';
            document.getElementById('edit_ordre').value = temoignage.ordre;
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