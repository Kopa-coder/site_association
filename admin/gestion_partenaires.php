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
    $partenaire_id = intval($_POST['partenaire_id'] ?? 0);
    
    if ($action === 'changer_statut') {
        $nouveau_statut = $_POST['nouveau_statut'] ?? '';
        $notes_admin = trim($_POST['notes_admin'] ?? '');
        
        try {
            $stmt = $pdo->prepare("UPDATE partenaires SET statut = ?, notes_admin = ? WHERE id = ?");
            $stmt->execute([$nouveau_statut, $notes_admin, $partenaire_id]);
            
            if (function_exists('logAdminAction')) {
                logAdminAction($pdo, 'Changement statut partenaire', 'partenaires', $partenaire_id, $nouveau_statut);
            }
            
            $message = "Statut modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors du changement de statut";
        }
    }
    
    elseif ($action === 'supprimer') {
        try {
            $stmt = $pdo->prepare("DELETE FROM partenaires WHERE id = ?");
            $stmt->execute([$partenaire_id]);
            
            if (function_exists('logAdminAction')) {
                logAdminAction($pdo, 'Suppression partenaire', 'partenaires', $partenaire_id);
            }
            
            $message = "Partenaire supprimé";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression";
        }
    }
}

// Déterminer quel onglet afficher
$onglet = $_GET['onglet'] ?? 'en_attente';
$type_filter = $_GET['type'] ?? 'tous'; // tous, donateur, demandeur

// Construction de la requête selon les filtres
$where_conditions = [];
$params = [];

// Filtre par statut
if ($onglet === 'en_attente') {
    $where_conditions[] = "statut = 'en_attente'";
} elseif ($onglet === 'en_cours') {
    $where_conditions[] = "statut IN ('en_cours_etude', 'approuve')";
} elseif ($onglet === 'actifs') {
    $where_conditions[] = "statut = 'en_partenariat'";
} elseif ($onglet === 'rejetes') {
    $where_conditions[] = "statut = 'rejete'";
}

// Filtre par type
if ($type_filter !== 'tous') {
    $where_conditions[] = "type_partenaire = ?";
    $params[] = $type_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT * FROM partenaires $where_clause ORDER BY date_soumission DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$partenaires = $stmt->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type_partenaire = 'donateur' THEN 1 ELSE 0 END) as donateurs,
        SUM(CASE WHEN type_partenaire = 'demandeur' THEN 1 ELSE 0 END) as demandeurs,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut IN ('en_cours_etude', 'approuve') THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'en_partenariat' THEN 1 ELSE 0 END) as actifs,
        SUM(CASE WHEN statut = 'rejete' THEN 1 ELSE 0 END) as rejetes
    FROM partenaires
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Partenaires - Admin</title>
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
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-item {
            text-align: center; padding: 15px; border-radius: 8px;
        }
        
        .stat-item h3 { font-size: 2rem; margin-bottom: 5px; }
        .stat-item p { color: #7f8c8d; font-size: 0.9rem; }
        
        .stat-total { background: #ecf0f1; }
        .stat-total h3 { color: #2c3e50; }
        .stat-donateurs { background: #d4edda; }
        .stat-donateurs h3 { color: #155724; }
        .stat-demandeurs { background: #d1ecf1; }
        .stat-demandeurs h3 { color: #0c5460; }
        .stat-attente { background: #fff3cd; }
        .stat-attente h3 { color: #856404; }
        .stat-actifs { background: #d4edda; }
        .stat-actifs h3 { color: #155724; }
        
        .filters {
            background: white; padding: 15px; border-radius: 10px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex; gap: 15px; align-items: center; flex-wrap: wrap;
        }
        
        .filters label { font-weight: 600; color: #2c3e50; }
        .filters select {
            padding: 8px 15px; border: 2px solid #ddd; border-radius: 5px;
            font-size: 1rem; cursor: pointer;
        }
        
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
        
        .partenaire-card {
            background: #f8f9fa; padding: 20px; margin-bottom: 15px;
            border-radius: 10px; border-left: 5px solid #3498db;
        }
        
        .partenaire-card.donateur { border-left-color: #27ae60; }
        .partenaire-card.demandeur { border-left-color: #3498db; }
        
        .partenaire-header {
            display: flex; justify-content: space-between; align-items: start;
            margin-bottom: 15px; flex-wrap: wrap; gap: 15px;
        }
        
        .partenaire-type-badge {
            display: inline-block; padding: 5px 15px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
        }
        
        .badge-donateur { background: #d4edda; color: #155724; }
        .badge-demandeur { background: #d1ecf1; color: #0c5460; }
        
        .partenaire-info {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px; margin-bottom: 15px;
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
            border-radius: 10px; max-width: 600px;
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block; margin-bottom: 0.5rem;
            font-weight: 600; color: #2c3e50;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 0.8rem;
            border: 2px solid #ddd; border-radius: 5px;
        }
        
        .urgence-badge {
            display: inline-block; padding: 3px 10px; border-radius: 15px;
            font-size: 0.8rem; font-weight: 600;
        }
        
        .urgence-faible { background: #d4edda; color: #155724; }
        .urgence-moyenne { background: #fff3cd; color: #856404; }
        .urgence-elevee { background: #f8d7da; color: #721c24; }
        .urgence-critique { background: #dc3545; color: white; }
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
            <li><a href="gestion_orphelins.php">👶 Orphelins</a></li>
            <li><a href="gestion_partenaires.php" class="active">🤝 Partenaires</a></li>
            <li><a href="logout.php">🚪 Déconnexion</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>🤝 Gestion des Partenaires</h1>
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
                <p>Total</p>
            </div>
            <div class="stat-item stat-donateurs">
                <h3><?= $stats['donateurs'] ?></h3>
                <p>Donateurs</p>
            </div>
            <div class="stat-item stat-demandeurs">
                <h3><?= $stats['demandeurs'] ?></h3>
                <p>Demandeurs</p>
            </div>
            <div class="stat-item stat-attente">
                <h3><?= $stats['en_attente'] ?></h3>
                <p>En attente</p>
            </div>
            <div class="stat-item stat-actifs">
                <h3><?= $stats['actifs'] ?></h3>
                <p>Partenariats actifs</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <label>Filtrer par type:</label>
            <select onchange="window.location.href='?onglet=<?= $onglet ?>&type=' + this.value">
                <option value="tous" <?= $type_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                <option value="donateur" <?= $type_filter === 'donateur' ? 'selected' : '' ?>>Donateurs uniquement</option>
                <option value="demandeur" <?= $type_filter === 'demandeur' ? 'selected' : '' ?>>Demandeurs uniquement</option>
            </select>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <a href="?onglet=en_attente&type=<?= $type_filter ?>" class="tab <?= $onglet === 'en_attente' ? 'active' : '' ?>">
                ⏳ En attente (<?= $stats['en_attente'] ?>)
            </a>
            <a href="?onglet=en_cours&type=<?= $type_filter ?>" class="tab <?= $onglet === 'en_cours' ? 'active' : '' ?>">
                🔍 En cours (<?= $stats['en_cours'] ?>)
            </a>
            <a href="?onglet=actifs&type=<?= $type_filter ?>" class="tab <?= $onglet === 'actifs' ? 'active' : '' ?>">
                ✅ Actifs (<?= $stats['actifs'] ?>)
            </a>
            <a href="?onglet=rejetes&type=<?= $type_filter ?>" class="tab <?= $onglet === 'rejetes' ? 'active' : '' ?>">
                ❌ Rejetés (<?= $stats['rejetes'] ?>)
            </a>
        </div>

        <div class="content-section">
            <h2 style="margin-bottom: 20px;">
                <?php
                $titres = [
                    'en_attente' => 'Demandes en attente',
                    'en_cours' => 'En cours d\'étude / Approuvées',
                    'actifs' => 'Partenariats actifs',
                    'rejetes' => 'Demandes rejetées'
                ];
                echo $titres[$onglet] ?? 'Partenaires';
                ?>
                (<?= count($partenaires) ?>)
            </h2>
            
            <?php if (count($partenaires) > 0): ?>
                <?php foreach ($partenaires as $part): ?>
                    <div class="partenaire-card <?= $part['type_partenaire'] ?>">
                        <div class="partenaire-header">
                            <div>
                                <span class="partenaire-type-badge badge-<?= $part['type_partenaire'] ?>">
                                    <?= $part['type_partenaire'] === 'donateur' ? '💝 DONATEUR' : '🙏 DEMANDEUR' ?>
                                </span>
                                <h3 style="margin-top: 10px; color: #2c3e50;">
                                    <?php
                                    if ($part['type_entite'] === 'particulier') {
                                        echo htmlspecialchars($part['prenom'] . ' ' . $part['nom']);
                                    } else {
                                        echo htmlspecialchars($part['nom_entreprise'] ?: $part['nom']);
                                    }
                                    ?>
                                </h3>
                                <p style="color: #7f8c8d; font-size: 0.9rem;">
                                    Soumis le <?= date('d/m/Y à H:i', strtotime($part['date_soumission'])) ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="partenaire-info">
                            <div class="info-item">
                                <span class="info-label">Type d'entité</span>
                                <span class="info-value"><?= ucfirst($part['type_entite']) ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?= htmlspecialchars($part['email']) ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Téléphone</span>
                                <span class="info-value"><?= htmlspecialchars($part['telephone']) ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Localisation</span>
                                <span class="info-value"><?= htmlspecialchars($part['ville']) ?>, <?= htmlspecialchars($part['pays']) ?></span>
                            </div>
                            
                            <?php if ($part['type_partenaire'] === 'donateur'): ?>
                                <div class="info-item">
                                    <span class="info-label">Type de don</span>
                                    <span class="info-value"><?= htmlspecialchars($part['type_don']) ?></span>
                                </div>
                                
                                <?php if ($part['montant_propose']): ?>
                                <div class="info-item">
                                    <span class="info-label">Montant proposé</span>
                                    <span class="info-value" style="color: #27ae60;">
                                        <?= number_format($part['montant_propose'], 0, ',', ' ') ?> FCFA
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($part['domaine_intervention']): ?>
                                <div class="info-item">
                                    <span class="info-label">Domaine</span>
                                    <span class="info-value"><?= htmlspecialchars($part['domaine_intervention']) ?></span>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="info-item">
                                    <span class="info-label">Type de demande</span>
                                    <span class="info-value"><?= htmlspecialchars($part['type_demande']) ?></span>
                                </div>
                                
                                <?php if ($part['montant_demande']): ?>
                                <div class="info-item">
                                    <span class="info-label">Montant demandé</span>
                                    <span class="info-value" style="color: #3498db;">
                                        <?= number_format($part['montant_demande'], 0, ',', ' ') ?> FCFA
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($part['nombre_beneficiaires']): ?>
                                <div class="info-item">
                                    <span class="info-label">Bénéficiaires</span>
                                    <span class="info-value"><?= $part['nombre_beneficiaires'] ?> personne(s)</span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <span class="info-label">Urgence</span>
                                    <span class="urgence-badge urgence-<?= $part['urgence'] ?>">
                                        <?= strtoupper($part['urgence']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($part['type_partenaire'] === 'donateur' && $part['description_don']): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #e8f8f5; border-radius: 5px;">
                                <strong>Description du don:</strong>
                                <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($part['description_don'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($part['type_partenaire'] === 'demandeur' && $part['description_besoin']): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #ebf5fb; border-radius: 5px;">
                                <strong>Description du besoin:</strong>
                                <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($part['description_besoin'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($part['notes_admin']): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                                <strong>📝 Notes admin:</strong>
                                <p style="margin-top: 5px;"><?= nl2br(htmlspecialchars($part['notes_admin'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="actions">
                            <button onclick="changeStatus(<?= $part['id'] ?>, '<?= $part['statut'] ?>')" 
                                    class="btn btn-primary">
                                ✏️ Changer statut
                            </button>
                            
                            <?php if ($part['justificatif']): ?>
                                <a href="../uploads/partenaires/<?= htmlspecialchars($part['justificatif']) ?>" 
                                   target="_blank" class="btn btn-warning">
                                    📄 Voir justificatif
                                </a>
                            <?php endif; ?>
                            
                            <a href="mailto:<?= htmlspecialchars($part['email']) ?>" class="btn btn-success">
                                📧 Contacter
                            </a>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Supprimer ce partenaire ?')">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="partenaire_id" value="<?= $part['id'] ?>">
                                <button type="submit" class="btn btn-secondary">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 Aucun partenaire dans cette catégorie</h3>
                    <p>Les demandes apparaîtront ici</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Changement de statut -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifier le statut</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="changer_statut">
                <input type="hidden" name="partenaire_id" id="status_partenaire_id">
                
                <div class="form-group">
                    <label>Nouveau statut</label>
                    <select name="nouveau_statut" id="status_select" required>
                        <option value="en_attente">⏳ En attente</option>
                        <option value="en_cours_etude">🔍 En cours d'étude</option>
                        <option value="approuve">✅ Approuvé</option>
                        <option value="en_partenariat">🤝 En partenariat actif</option>
                        <option value="rejete">❌ Rejeté</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Notes administratives</label>
                    <textarea name="notes_admin" id="notes_admin" rows="4" 
                              placeholder="Ajoutez des notes internes..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Enregistrer</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function changeStatus(id, currentStatus) {
            document.getElementById('status_partenaire_id').value = id;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('statusModal').style.display =<function_calls>
<invoke name="artifacts">
<parameter name="command">update</parameter>
<parameter name="id">admin_partenaires</parameter>
<parameter name="old_str">        function closeModal() {
document.getElementById('statusModal').style.display =</parameter>
<parameter name="new_str">        function closeModal() {
document.getElementById('statusModal').style.display = 'none';window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>
}