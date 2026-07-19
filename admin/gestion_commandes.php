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
    $commande_id = intval($_POST['commande_id'] ?? 0);
    
    if ($action === 'changer_statut') {
        $nouveau_statut = securiser($_POST['nouveau_statut']);
        try {
            $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
            $stmt->execute([$nouveau_statut, $commande_id]);
            logAdminAction($pdo, 'Changement statut commande', 'commandes', $commande_id, $nouveau_statut);
            $message = "Statut modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors du changement de statut";
        }
    }
    
    elseif ($action === 'supprimer') {
        try {
            $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
            $stmt->execute([$commande_id]);
            logAdminAction($pdo, 'Suppression commande', 'commandes', $commande_id);
            $message = "Commande supprimée";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Déterminer quel onglet afficher
$onglet = $_GET['onglet'] ?? 'en_attente';

// Récupérer les commandes en attente
$commandes_attente = $pdo->query("
    SELECT c.*, 
           COUNT(dc.id) as nb_livres,
           GROUP_CONCAT(l.titre SEPARATOR ', ') as titres_livres
    FROM commandes c
    LEFT JOIN details_commande dc ON c.id = dc.commande_id
    LEFT JOIN livres l ON dc.livre_id = l.id
    WHERE c.statut = 'en_attente'
    GROUP BY c.id
    ORDER BY c.date_commande DESC
")->fetchAll();

// Récupérer les commandes terminées (confirmées, expédiées, livrées)
$commandes_terminees = $pdo->query("
    SELECT c.*, 
           COUNT(dc.id) as nb_livres,
           GROUP_CONCAT(l.titre SEPARATOR ', ') as titres_livres
    FROM commandes c
    LEFT JOIN details_commande dc ON c.id = dc.commande_id
    LEFT JOIN livres l ON dc.livre_id = l.id
    WHERE c.statut IN ('confirmee', 'expediee', 'livree')
    GROUP BY c.id
    ORDER BY c.date_commande DESC
")->fetchAll();

// Récupérer les commandes annulées
$commandes_annulees = $pdo->query("
    SELECT c.*, 
           COUNT(dc.id) as nb_livres,
           GROUP_CONCAT(l.titre SEPARATOR ', ') as titres_livres
    FROM commandes c
    LEFT JOIN details_commande dc ON c.id = dc.commande_id
    LEFT JOIN livres l ON dc.livre_id = l.id
    WHERE c.statut = 'annulee'
    GROUP BY c.id
    ORDER BY c.date_commande DESC
")->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut IN ('confirmee', 'expediee', 'livree') THEN 1 ELSE 0 END) as terminees,
        SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as annulees
    FROM commandes
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Commandes - Admin</title>
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
            display: flex; gap: 20px; justify-content: space-around;
        }
        
        .stat-item {
            text-align: center; flex: 1;
            padding: 15px; border-radius: 8px;
            transition: all 0.3s;
        }
        
        .stat-item:hover { transform: translateY(-5px); }
        
        .stat-item h3 { font-size: 2rem; margin-bottom: 5px; }
        .stat-item p { color: #7f8c8d; font-size: 0.9rem; }
        
        .stat-total { background: #ecf0f1; }
        .stat-total h3 { color: #2c3e50; }
        .stat-attente { background: #fff3cd; }
        .stat-attente h3 { color: #856404; }
        .stat-terminees { background: #d4edda; }
        .stat-terminees h3 { color: #155724; }
        .stat-annulees { background: #f8d7da; }
        .stat-annulees h3 { color: #721c24; }
        
        .tabs {
            background: white; padding: 0; border-radius: 10px 10px 0 0;
            margin-bottom: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
        }
        
        .tab {
            flex: 1; padding: 15px 20px; text-align: center;
            border: none; background: transparent; cursor: pointer;
            font-size: 1rem; font-weight: 600; color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s; text-decoration: none;
            display: block;
        }
        
        .tab:hover { background: #f8f9fa; }
        .tab.active { color: #e74c3c; border-bottom-color: #e74c3c; background: #fff; }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 5px;
            cursor: pointer; text-decoration: none; display: inline-block;
            font-weight: 600; transition: all 0.3s;
        }
        
        .btn-primary { background: #e74c3c; color: white; }
        .btn-primary:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-small { padding: 5px 10px; font-size: 0.85rem; }
        
        .alert {
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .content-section {
            background: white; padding: 25px; border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table { width: 100%; border-collapse: collapse; }
        table th {
            background: #ecf0f1; padding: 12px; text-align: left;
            font-weight: 600; color: #2c3e50;
        }
        table td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
        
        .status-badge {
            padding: 5px 12px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
        }
        .status-en_attente { background: #fff3cd; color: #856404; }
        .status-confirmee { background: #d4edda; color: #155724; }
        .status-expediee { background: #cce5ff; color: #004085; }
        .status-livree { background: #d1e7dd; color: #0a3622; }
        .status-annulee { background: #f8d7da; color: #721c24; }
        
        select { padding: 8px; border: 2px solid #ddd; border-radius: 5px; }
        
        .empty-state {
            text-align: center; padding: 40px; color: #7f8c8d;
        }
        
        .empty-state h3 { margin-bottom: 10px; }
        
        .client-info { font-size: 0.9rem; }
        .client-info strong { display: block; margin-bottom: 3px; }
        .client-info small { color: #7f8c8d; display: block; }
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
            <li><a href="gestion_commandes.php" class="active">📦 Commandes</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>📦 Gestion des Commandes</h1>
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
                <p>Total commandes</p>
            </div>
            <div class="stat-item stat-attente">
                <h3><?= $stats['en_attente'] ?></h3>
                <p>En attente</p>
            </div>
            <div class="stat-item stat-terminees">
                <h3><?= $stats['terminees'] ?></h3>
                <p>Terminées</p>
            </div>
            <div class="stat-item stat-annulees">
                <h3><?= $stats['annulees'] ?></h3>
                <p>Annulées</p>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <a href="?onglet=en_attente" class="tab <?= $onglet === 'en_attente' ? 'active' : '' ?>">
                ⏳ En attente (<?= count($commandes_attente) ?>)
            </a>
            <a href="?onglet=terminees" class="tab <?= $onglet === 'terminees' ? 'active' : '' ?>">
                ✅ Terminées (<?= count($commandes_terminees) ?>)
            </a>
            <a href="?onglet=annulees" class="tab <?= $onglet === 'annulees' ? 'active' : '' ?>">
                ❌ Annulées (<?= count($commandes_annulees) ?>)
            </a>
        </div>

        <div class="content-section">
            <?php 
            // Déterminer quelle liste afficher
            if ($onglet === 'en_attente') {
                $commandes_a_afficher = $commandes_attente;
                $titre = "Commandes en attente";
            } elseif ($onglet === 'terminees') {
                $commandes_a_afficher = $commandes_terminees;
                $titre = "Commandes terminées";
            } else {
                $commandes_a_afficher = $commandes_annulees;
                $titre = "Commandes annulées";
            }
            ?>
            
            <h2><?= $titre ?> (<?= count($commandes_a_afficher) ?>)</h2>
            
            <?php if (count($commandes_a_afficher) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th>Livres</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes_a_afficher as $cmd): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cmd['reference_commande']) ?></strong></td>
                            <td class="client-info">
                                <strong><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></strong>
                                <small><?= htmlspecialchars($cmd['ville']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($cmd['telephone']) ?></strong>
                                <?php if (!empty($cmd['numero_paiement'])): ?>
                                    <br><small>💳 <?= htmlspecialchars($cmd['numero_paiement']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($cmd['titres_livres'] ?: 'N/A') ?></small><br>
                                <strong><?= $cmd['nb_livres'] ?> livre(s)</strong>
                            </td>
                            <td><strong><?= number_format($cmd['montant_total'], 0, ',', ' ') ?> FCFA</strong></td>
                            <td>
                                <?php
                                $modes = [
                                    'mobile_money' => '📱 Mobile Money',
                                    'livraison' => '🚚 À livraison',
                                    'virement' => '🏦 Virement',
                                    'carte' => '💳 Carte'
                                ];
                                echo $modes[$cmd['mode_paiement']] ?? strtoupper($cmd['mode_paiement']);
                                ?>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?><br>
                                <small><?= date('H:i', strtotime($cmd['date_commande'])) ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $cmd['statut'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $cmd['statut'])) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="changer_statut">
                                    <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                                    <select name="nouveau_statut" onchange="if(confirm('Changer le statut ?')) this.form.submit();">
                                        <option value="">Changer...</option>
                                        <option value="en_attente">⏳ En attente</option>
                                        <option value="confirmee">✅ Confirmée</option>
                                        <option value="expediee">📦 Expédiée</option>
                                        <option value="livree">🎉 Livrée</option>
                                        <option value="annulee">❌ Annulée</option>
                                    </select>
                                </form>
                                <form method="POST" style="display: inline-block; margin-left: 5px;">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-small" 
                                            onclick="return confirm('Supprimer cette commande ?')">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 Aucune commande dans cette catégorie</h3>
                    <p>Les commandes apparaîtront ici</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>