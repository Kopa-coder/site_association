<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

// Vérifier l'authentification
requireLogin();

// Récupérer les statistiques
$stats = [];
$stats['total_dons'] = $pdo->query("SELECT COUNT(*) FROM dons")->fetchColumn();
$stats['montant_dons'] = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM dons WHERE statut = 'valide'")->fetchColumn();
$stats['total_livres'] = $pdo->query("SELECT COUNT(*) FROM livres")->fetchColumn();
$stats['total_commandes'] = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$stats['dons_attente'] = $pdo->query("SELECT COUNT(*) FROM dons WHERE statut = 'en_attente'")->fetchColumn();

// Récupérer les derniers dons
$derniers_dons = $pdo->query("
    SELECT * FROM dons 
    ORDER BY date_don DESC 
    LIMIT 5
")->fetchAll();

// Récupérer les dernières commandes
$dernieres_commandes = $pdo->query("
    SELECT * FROM commandes 
    ORDER BY date_commande DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Cris des Orphelins</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
            color: #f39c12;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #34495e;
            border-left: 4px solid #f39c12;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-bar h1 {
            color: #2c3e50;
            font-size: 1.8rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 10px;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .content-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .content-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #ecf0f1;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-attente, .status-en-attente {
            background: #fff3cd;
            color: #856404;
        }

        .status-valide, .status-confirmee {
            background: #d4edda;
            color: #155724;
        }

        .status-refuse {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
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
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="gestion_livres.php">📚 Gestion Livres</a></li>
            <li><a href="gestion_dons.php">💝 Gestion Dons</a></li>
            <li><a href="gestion_commandes.php">📦 Commandes</a></li>
            <li><a href="gestion_contenu.php">✏️ Contenu Site</a></li>
            <li><a href="gestion_slogans.php">💬 Slogans</a></li>
            <li><a href="gestion_temoignages.php">⭐ Témoignages</a></li>
            <li><a href="gestion_statistiques.php">📈 Statistiques</a></li>
            <li><a href="parametres.php">⚙️ Paramètres</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
            <li><a href="gestion_orphelins.php"> orphelins</a></li>
            <li><a href="gestion_partenaires.php"> partenaires</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>📊 Tableau de Bord</h1>
            <div class="user-info">
                <a href="../index.php" class="btn btn-secondary" target="_blank">🌐 Voir le site</a>
                <a href="logout.php" class="btn btn-primary">🚪 Déconnexion</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>💝 Total Dons</h3>
                <div class="value"><?= number_format($stats['montant_dons'], 0, ',', ' ') ?> FCFA</div>
                <span class="badge badge-success"><?= $stats['total_dons'] ?> dons reçus</span>
            </div>

            <div class="stat-card">
                <h3>📚 Livres en Stock</h3>
                <div class="value"><?= $stats['total_livres'] ?></div>
                <a href="gestion_livres.php" class="badge badge-success">Gérer les livres</a>
            </div>

            <div class="stat-card">
                <h3>📦 Commandes</h3>
                <div class="value"><?= $stats['total_commandes'] ?></div>
                <a href="gestion_commandes.php" class="badge badge-success">Voir les commandes</a>
            </div>

            <div class="stat-card">
                <h3>⏳ Dons en attente</h3>
                <div class="value"><?= $stats['dons_attente'] ?></div>
                <span class="badge badge-warning">À valider</span>
            </div>
        </div>

        <div class="content-section">
            <h2>💝 Derniers Dons</h2>
            <?php if (count($derniers_dons) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Donateur</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($derniers_dons as $don): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></td>
                        <td><?= htmlspecialchars($don['prenom'] . ' ' . $don['nom']) ?></td>
                        <td><?= number_format($don['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td><?= strtoupper($don['mode_paiement']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $don['statut'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $don['statut'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="gestion_dons.php?id=<?= $don['id'] ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">Voir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucun don enregistré pour le moment.</p>
            <?php endif; ?>
            <div style="margin-top: 15px; text-align: right;">
                <a href="gestion_dons.php" class="btn btn-primary">Voir tous les dons →</a>
            </div>
        </div>

        <div class="content-section">
            <h2>📦 Dernières Commandes</h2>
            <?php if (count($dernieres_commandes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dernieres_commandes as $cmd): ?>
                    <tr>
                        <td><?= htmlspecialchars($cmd['reference_commande']) ?></td>
                        <td><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></td>
                        <td><?= number_format($cmd['montant_total'], 0, ',', ' ') ?> FCFA</td>
                        <td>
                            <span class="status-badge status-<?= str_replace('_', '-', $cmd['statut']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $cmd['statut'])) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucune commande enregistrée pour le moment.</p>
            <?php endif; ?>
            <div style="margin-top: 15px; text-align: right;">
                <a href="gestion_commandes.php" class="btn btn-primary">Voir toutes les commandes →</a>
            </div>
        </div>
    </div>
</body>
</html>