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
    $orphelin_id = intval($_POST['orphelin_id'] ?? 0);
    
    if ($action === 'changer_statut') {
        $nouveau_statut = $_POST['nouveau_statut'] ?? '';
        try {
            $stmt = $pdo->prepare("UPDATE orphelins SET statut = ? WHERE id = ?");
            $stmt->execute([$nouveau_statut, $orphelin_id]);
            
            if (function_exists('logAdminAction')) {
                logAdminAction($pdo, 'Changement statut orphelin', 'orphelins', $orphelin_id, $nouveau_statut);
            }
            
            $message = "Statut modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors du changement de statut";
        }
    }
    
    elseif ($action === 'supprimer') {
        try {
            $stmt = $pdo->prepare("DELETE FROM orphelins WHERE id = ?");
            $stmt->execute([$orphelin_id]);
            
            if (function_exists('logAdminAction')) {
                logAdminAction($pdo, 'Suppression orphelin', 'orphelins', $orphelin_id);
            }
            
            $message = "Orphelin supprimé de la base de données";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Déterminer quel onglet afficher
$onglet = $_GET['onglet'] ?? 'en_attente';

// Récupérer les orphelins par statut
$orphelins_attente = $pdo->query("SELECT * FROM orphelins WHERE statut = 'en_attente' ORDER BY date_enregistrement DESC")->fetchAll();
$orphelins_verification = $pdo->query("SELECT * FROM orphelins WHERE statut = 'en_cours_verification' ORDER BY date_enregistrement DESC")->fetchAll();
$orphelins_acceptes = $pdo->query("SELECT * FROM orphelins WHERE statut = 'accepte' ORDER BY date_enregistrement DESC")->fetchAll();
$orphelins_rejetes = $pdo->query("SELECT * FROM orphelins WHERE statut = 'rejete' ORDER BY date_enregistrement DESC")->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'en_cours_verification' THEN 1 ELSE 0 END) as en_verification,
        SUM(CASE WHEN statut = 'accepte' THEN 1 ELSE 0 END) as acceptes,
        SUM(CASE WHEN statut = 'rejete' THEN 1 ELSE 0 END) as rejetes
    FROM orphelins
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Orphelins - Admin</title>
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
        
        .stats-bar {
            background: white; padding: 20px; border-radius: 10px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex; gap: 20px; justify-content: space-around; flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center; flex: 1; min-width: 150px;
            padding: 15px; border-radius: 8px;
        }
        
        .stat-item h3 { font-size: 2rem; margin-bottom: 5px; }
        .stat-item p { color: #7f8c8d; font-size: 0.9rem; }
        
        .stat-total { background: #ecf0f1; }
        .stat-total h3 { color: #2c3e50; }
        .stat-attente { background: #fff3cd; }
        .stat-attente h3 { color: #856404; }
        .stat-verification { background: #d1ecf1; }
        .stat-verification h3 { color: #0c5460; }
        .stat-acceptes { background: #d4edda; }
        .stat-acceptes h3 { color: #155724; }
        .stat-rejetes { background: #f8d7da; }
        .stat-rejetes h3 { color: #721c24; }
        
        .tabs {
            background: white; padding: 0; border-radius: 10px 10px 0 0;
            margin-bottom: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex; overflow-x: auto;
        }
        
        .tab {
            flex: 1; padding: 15px 20px; text-align: center;
            border: none; background: transparent; cursor: pointer;
            font-size: 1rem; font-weight: 600; color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s; text-decoration: none;
            display: block; white-space: nowrap;
        }
        
        .tab:hover { background: #f8f9fa; }
        .tab.active { color: #e74c3c; border-bottom-color: #e74c3c; background: #fff; }
        
        .content-section {
            background: white; padding: 25px; border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .orphelin-card {
            background: #f8f9fa; padding: 20px; margin-bottom: 15px;
            border-radius: 10px; border-left: 5px solid #e74c3c;
        }
        
        .orphelin-header {
            display: flex; justify-content: space-between; align-items: start;
            margin-bottom: 15px;
        }
        
        .orphelin-photo {
            width: 80px; height: 80px; border-radius: 50%;
            object-fit: cover; margin-right: 15px;
        }
        
        .orphelin-info {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex; flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem; color: #7f8c8d; margin-bottom: 3px;
        }
        
        .info-value {
            font-weight: 600; color: #2c3e50;
        }
        
        .btn {
            padding: 8px 16px; border: none; border-radius: 5px;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
            text-decoration: none; display: inline-block; font-size: 0.9rem;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .actions {
            display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;
        }
        
        .empty-state {
            text-align: center; padding: 40px; color: #7f8c8d;
        }
        
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background: white; margin: 2% auto; padding: 30px;
            border-radius: 10px; max-width: 800px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px; font-weight: bold; cursor: pointer; color: #7f8c8d;
        }
        
        .close:hover { color: #2c3e50; }
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
            <li><a href="gestion_orphelins.php" class="active">👶 Orphelins</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>👶 Gestion des Orphelins</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-bar">
            <div class="stat-item stat-total">
                <h3><?= $stats['total'] ?></h3>
                <p>Total orphelins</p>
            </div>
            <div class="stat-item stat-attente">
                <h3><?= $stats['en_attente'] ?></h3>
                <p>En attente</p>
            </div>
            <div class="stat-item stat-verification">
                <h3><?= $stats['en_verification'] ?></h3>
                <p>En vérification</p>
            </div>
            <div class="stat-item stat-acceptes">
                <h3><?= $stats['acceptes'] ?></h3>
                <p>Acceptés</p>
            </div>
            <div class="stat-item stat-rejetes">
                <h3><?= $stats['rejetes'] ?></h3>
                <p>Rejetés</p>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <a href="?onglet=en_attente" class="tab <?= $onglet === 'en_attente' ? 'active' : '' ?>">
                ⏳ En attente (<?= count($orphelins_attente) ?>)
            </a>
            <a href="?onglet=verification" class="tab <?= $onglet === 'verification' ? 'active' : '' ?>">
                🔍 En vérification (<?= count($orphelins_verification) ?>)
            </a>
            <a href="?onglet=acceptes" class="tab <?= $onglet === 'acceptes' ? 'active' : '' ?>">
                ✅ Acceptés (<?= count($orphelins_acceptes) ?>)
            </a>
            <a href="?onglet=rejetes" class="tab <?= $onglet === 'rejetes' ? 'active' : '' ?>">
                ❌ Rejetés (<?= count($orphelins_rejetes) ?>)
            </a>
        </div>

        <div class="content-section">
            <?php 
            // Déterminer quelle liste afficher
            if ($onglet === 'en_attente') {
                $orphelins_a_afficher = $orphelins_attente;
                $titre = "Orphelins en attente de traitement";
            } elseif ($onglet === 'verification') {
                $orphelins_a_afficher = $orphelins_verification;
                $titre = "Orphelins en cours de vérification";
            } elseif ($onglet === 'acceptes') {
                $orphelins_a_afficher = $orphelins_acceptes;
                $titre = "Orphelins acceptés dans le programme";
            } else {
                $orphelins_a_afficher = $orphelins_rejetes;
                $titre = "Demandes rejetées";
            }
            ?>
            
            <h2 style="margin-bottom: 20px;"><?= $titre ?> (<?= count($orphelins_a_afficher) ?>)</h2>
            
            <?php if (count($orphelins_a_afficher) > 0): ?>
                <?php foreach ($orphelins_a_afficher as $orphelin): ?>
                    <div class="orphelin-card">
                        <div class="orphelin-header">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <?php if (!empty($orphelin['photo'])): ?>
                                    <img src="../uploads/orphelins/<?= htmlspecialchars($orphelin['photo']) ?>" 
                                         alt="Photo" class="orphelin-photo">
                                <?php endif; ?>
                                <div>
                                    <h3 style="color: #2c3e50; margin-bottom: 5px;">
                                        <?= htmlspecialchars($orphelin['prenom'] . ' ' . $orphelin['nom']) ?>
                                    </h3>
                                    <p style="color: #7f8c8d; font-size: 0.9rem;">
                                        Enregistré le <?= date('d/m/Y', strtotime($orphelin['date_enregistrement'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="orphelin-info">
                            <div class="info-item">
                                <span class="info-label">Date de naissance</span>
                                <span class="info-value">
                                    <?= date('d/m/Y', strtotime($orphelin['date_naissance'])) ?>
                                    (<?= date_diff(date_create($orphelin['date_naissance']), date_create('today'))->y ?> ans)
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Sexe</span>
                                <span class="info-value"><?= $orphelin['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Situation</span>
                                <span class="info-value">
                                    <?php
                                    $situations = [
                                        'pere_decede' => 'Père décédé',
                                        'mere_decede' => 'Mère décédée',
                                        'les_deux_decedes' => 'Orphelin total',
                                        'abandonnes' => 'Abandonné'
                                    ];
                                    echo $situations[$orphelin['situation_parents']] ?? $orphelin['situation_parents'];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Tuteur</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($orphelin['prenom_tuteur'] . ' ' . $orphelin['nom_tuteur']) ?>
                                    <br><small>(<?= htmlspecialchars($orphelin['lien_avec_enfant']) ?>)</small>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Contact tuteur</span>
                                <span class="info-value">
                                    📞 <?= htmlspecialchars($orphelin['telephone_tuteur']) ?>
                                    <?php if (!empty($orphelin['email_tuteur'])): ?>
                                        <br>📧 <?= htmlspecialchars($orphelin['email_tuteur']) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Localisation</span>
                                <span class="info-value"><?= htmlspecialchars($orphelin['ville_tuteur']) ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($orphelin['besoins_urgents'])): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                                <strong>Besoins urgents:</strong>
                                <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($orphelin['besoins_urgents'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="actions">
                            <button onclick="viewDetails(<?= $orphelin['id'] ?>)" class="btn btn-primary">
                                👁️ Voir détails
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="changer_statut">
                                <input type="hidden" name="orphelin_id" value="<?= $orphelin['id'] ?>">
                                
                                <?php if ($orphelin['statut'] === 'en_attente'): ?>
                                    <button type="submit" name="nouveau_statut" value="en_cours_verification" 
                                            class="btn btn-warning">🔍 Mettre en vérification</button>
                                <?php endif; ?>
                                
                                <?php if ($orphelin['statut'] === 'en_cours_verification'): ?>
                                    <button type="submit" name="nouveau_statut" value="accepte" 
                                            class="btn btn-success">✅ Accepter</button>
                                    <button type="submit" name="nouveau_statut" value="rejete" 
                                            class="btn btn-danger">❌ Rejeter</button>
                                <?php endif; ?>
                                
                                <?php if ($orphelin['statut'] === 'rejete'): ?>
                                    <button type="submit" name="nouveau_statut" value="en_attente" 
                                            class="btn btn-warning">↩️ Remettre en attente</button>
                                <?php endif; ?>
                            </form>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Supprimer cet enregistrement ?')">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="orphelin_id" value="<?= $orphelin['id'] ?>">
                                <button type="submit" class="btn btn-secondary">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 Aucun orphelin dans cette catégorie</h3>
                    <p>Les enregistrements apparaîtront ici</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Détails -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Détails complets de l'orphelin</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="detailsContent"></div>
        </div>
    </div>

    <script>
        function viewDetails(id) {
            // Pour l'instant, on peut juste afficher un message
            // Plus tard, on peut charger les détails via AJAX
            alert('Fonctionnalité en développement. ID: ' + id);
        }
        
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>