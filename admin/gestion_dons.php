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
    $don_id = intval($_POST['don_id'] ?? 0);
    
    if ($action === 'valider') {
        try {
            $stmt = $pdo->prepare("UPDATE dons SET statut = 'valide', date_validation = NOW(), valideur_id = ? WHERE id = ?");
            $stmt->execute([getAdminId(), $don_id]);
            logAdminAction($pdo, 'Validation don', 'dons', $don_id);
            $message = "Don validé avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la validation";
        }
    }
    
    elseif ($action === 'refuser') {
        try {
            $stmt = $pdo->prepare("UPDATE dons SET statut = 'refuse', date_validation = NOW(), valideur_id = ? WHERE id = ?");
            $stmt->execute([getAdminId(), $don_id]);
            logAdminAction($pdo, 'Refus don', 'dons', $don_id);
            $message = "Don refusé";
        } catch (PDOException $e) {
            $error = "Erreur lors du refus";
        }
    }
    
    elseif ($action === 'supprimer') {
        try {
            $stmt = $pdo->prepare("DELETE FROM dons WHERE id = ?");
            $stmt->execute([$don_id]);
            logAdminAction($pdo, 'Suppression don', 'dons', $don_id);
            $message = "Don supprimé";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Filtres
$filtre_statut = $_GET['statut'] ?? '';
$recherche = $_GET['recherche'] ?? '';

// Construction de la requête
$sql = "SELECT * FROM dons WHERE 1=1";
$params = [];

if ($filtre_statut) {
    $sql .= " AND statut = ?";
    $params[] = $filtre_statut;
}

if ($recherche) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$sql .= " ORDER BY date_don DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dons = $stmt->fetchAll();

// Statistiques
$stats_dons = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut='valide' THEN montant ELSE 0 END) as montant_valide,
        SUM(CASE WHEN statut='en_attente' THEN 1 ELSE 0 END) as en_attente
    FROM dons
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Dons - Admin</title>
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
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .alert {
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 10px; }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        
        .filters {
            background: white; padding: 20px; border-radius: 10px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filters form { display: flex; gap: 15px; flex-wrap: wrap; align-items: end; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 2px solid #ddd;
            border-radius: 5px; font-size: 1rem;
        }
        
        .content-section {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;
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
        .status-valide { background: #d4edda; color: #155724; }
        .status-refuse { background: #f8d7da; color: #721c24; }
        
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white; margin: 5% auto; padding: 30px;
            border-radius: 10px; max-width: 600px;
        }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
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
            <li><a href="gestion_dons.php" class="active">💝 Gestion Dons</a></li>
            <li><a href="gestion_commandes.php">📦 Commandes</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>💝 Gestion des Dons</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total des dons</h3>
                <div class="value"><?= $stats_dons['total'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Montant validé</h3>
                <div class="value"><?= number_format($stats_dons['montant_valide'], 0, ',', ' ') ?> FCFA</div>
            </div>
            <div class="stat-card">
                <h3>En attente</h3>
                <div class="value"><?= $stats_dons['en_attente'] ?></div>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label>Rechercher</label>
                    <input type="text" name="recherche" placeholder="Nom, email..." value="<?= htmlspecialchars($recherche) ?>">
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="">Tous</option>
                        <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="valide" <?= $filtre_statut === 'valide' ? 'selected' : '' ?>>Validé</option>
                        <option value="refuse" <?= $filtre_statut === 'refuse' ? 'selected' : '' ?>>Refusé</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="gestion_dons.php" class="btn btn-secondary">Réinitialiser</a>
            </form>
        </div>

        <div class="content-section">
            <h2>Liste des Dons (<?= count($dons) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Donateur</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dons as $don): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></td>
                        <td>
                            <?= htmlspecialchars($don['prenom'] . ' ' . $don['nom']) ?><br>
                            <small><?= htmlspecialchars($don['email']) ?></small>
                        </td>
                        <td><strong><?= number_format($don['montant'], 0, ',', ' ') ?> FCFA</strong></td>
                        <td><?= strtoupper($don['mode_paiement']) ?></td>
                        <td><?= htmlspecialchars($don['numero_paiement']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $don['statut'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $don['statut'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($don['statut'] === 'en_attente'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="valider">
                                    <input type="hidden" name="don_id" value="<?= $don['id'] ?>">
                                    <button type="submit" class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;">✓ Valider</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="refuser">
                                    <input type="hidden" name="don_id" value="<?= $don['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">✗ Refuser</button>
                                </form>
                            <?php endif; ?>
                            <button onclick="voirDetails(<?= htmlspecialchars(json_encode($don)) ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.85rem;">👁 Détails</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Détails du Don</h2>
            <div id="detailsContent"></div>
        </div>
    </div>

    <script>
        function voirDetails(don) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            content.innerHTML = `
                <p><strong>Référence:</strong> ${don.reference_transaction}</p>
                <p><strong>Donateur:</strong> ${don.prenom} ${don.nom}</p>
                <p><strong>Email:</strong> ${don.email}</p>
                <p><strong>Téléphone:</strong> ${don.telephone}</p>
                <p><strong>Montant:</strong> ${don.montant.toLocaleString()} FCFA</p>
                <p><strong>Mode de paiement:</strong> ${don.mode_paiement.toUpperCase()}</p>
                <p><strong>Numéro de paiement:</strong> ${don.numero_paiement}</p>
                <p><strong>Message:</strong> ${don.message || 'Aucun message'}</p>
                <p><strong>Date:</strong> ${new Date(don.date_don).toLocaleString('fr-FR')}</p>
                <p><strong>Statut:</strong> ${don.statut}</p>
            `;
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>