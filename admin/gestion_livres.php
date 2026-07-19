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
        $titre = securiser($_POST['titre']);
        $auteur = securiser($_POST['auteur']);
        $description = securiser($_POST['description']);
        $prix = floatval($_POST['prix']);
        $stock = intval($_POST['stock']);
        $categorie = securiser($_POST['categorie']);
        $langue = securiser($_POST['langue']);
        
        // Gestion de l'image
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/livres/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid('livre_') . '.' . $extension;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO livres (titre, auteur, description, prix, stock, categorie, langue, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$titre, $auteur, $description, $prix, $stock, $categorie, $langue, $image]);
            logAdminAction($pdo, 'Ajout livre', 'livres', $pdo->lastInsertId(), $titre);
            $message = "Livre ajouté avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
    
    elseif ($action === 'modifier') {
        $id = intval($_POST['id']);
        $titre = securiser($_POST['titre']);
        $auteur = securiser($_POST['auteur']);
        $description = securiser($_POST['description']);
        $prix = floatval($_POST['prix']);
        $stock = intval($_POST['stock']);
        $categorie = securiser($_POST['categorie']);
        $statut = securiser($_POST['statut']);
        
        try {
            $stmt = $pdo->prepare("
                UPDATE livres 
                SET titre=?, auteur=?, description=?, prix=?, stock=?, categorie=?, statut=? 
                WHERE id=?
            ");
            $stmt->execute([$titre, $auteur, $description, $prix, $stock, $categorie, $statut, $id]);
            logAdminAction($pdo, 'Modification livre', 'livres', $id, $titre);
            $message = "Livre modifié avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    }
    
    elseif ($action === 'supprimer') {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM livres WHERE id = ?");
            $stmt->execute([$id]);
            logAdminAction($pdo, 'Suppression livre', 'livres', $id);
            $message = "Livre supprimé avec succès !";
        } catch (PDOException $e) {
            $error = "Impossible de supprimer ce livre (commandes existantes)";
        }
    }
}

// Récupérer tous les livres
$livres = $pdo->query("SELECT * FROM livres ORDER BY date_ajout DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres - Admin</title>
    <link rel="stylesheet" href="assets/admin-style.css">
    <style>
        .livre-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 150px 1fr auto;
            gap: 20px;
            align-items: center;
        }
        
        .livre-image {
            width: 150px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .livre-info h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .livre-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>📚 Gestion des Livres</h1>
            <button onclick="openModal('addModal')" class="btn btn-primary">➕ Ajouter un livre</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="content-section">
            <?php foreach ($livres as $livre): ?>
            <div class="livre-card">
                <img src="../uploads/livres/<?= htmlspecialchars($livre['image'] ?: 'default.jpg') ?>" 
                     alt="<?= htmlspecialchars($livre['titre']) ?>" 
                     class="livre-image">
                
                <div class="livre-info">
                    <h3><?= htmlspecialchars($livre['titre']) ?></h3>
                    <p><strong>Auteur:</strong> <?= htmlspecialchars($livre['auteur']) ?></p>
                    <p><strong>Prix:</strong> <?= number_format($livre['prix'], 0, ',', ' ') ?> FCFA</p>
                    <p><strong>Stock:</strong> <?= $livre['stock'] ?> exemplaires</p>
                    <p><strong>Statut:</strong> 
                        <span class="status-badge status-<?= $livre['statut'] ?>">
                            <?= ucfirst($livre['statut']) ?>
                        </span>
                    </p>
                </div>
                
                <div class="livre-actions">
                    <button onclick="editLivre(<?= htmlspecialchars(json_encode($livre)) ?>)" 
                            class="btn btn-primary">✏️ Modifier</button>
                    <form method="POST" onsubmit="return confirm('Supprimer ce livre ?')" style="margin: 0;">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $livre['id'] ?>">
                        <button type="submit" class="btn btn-secondary">🗑️ Supprimer</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>➕ Ajouter un Livre</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ajouter">
                
                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" required>
                </div>
                
                <div class="form-group">
                    <label>Auteur *</label>
                    <input type="text" name="auteur" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Prix (FCFA) *</label>
                        <input type="number" name="prix" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock *</label>
                        <input type="number" name="stock" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Catégorie</label>
                        <input type="text" name="categorie" value="Roman humanitaire">
                    </div>
                    
                    <div class="form-group">
                        <label>Langue</label>
                        <select name="langue">
                            <option value="Français">Français</option>
                            <option value="Anglais">Anglais</option>
                        </select>
                    </div>
                </div>
                
              
                
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Modal Modification -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>✏️ Modifier le Livre</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" id="edit_titre" required>
                </div>
                
                <div class="form-group">
                    <label>Auteur *</label>
                    <input type="text" name="auteur" id="edit_auteur" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Prix (FCFA) *</label>
                        <input type="number" name="prix" id="edit_prix" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock *</label>
                        <input type="number" name="stock" id="edit_stock" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Catégorie</label>
                    <input type="text" name="categorie" id="edit_categorie">
                </div>
                
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut" id="edit_statut">
                        <option value="disponible">Disponible</option>
                        <option value="rupture">Rupture de stock</option>
                        <option value="epuise">Épuisé</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        function editLivre(livre) {
            document.getElementById('edit_id').value = livre.id;
            document.getElementById('edit_titre').value = livre.titre;
            document.getElementById('edit_auteur').value = livre.auteur;
            document.getElementById('edit_description').value = livre.description || '';
            document.getElementById('edit_prix').value = livre.prix;
            document.getElementById('edit_stock').value = livre.stock;
            document.getElementById('edit_categorie').value = livre.categorie || '';
            document.getElementById('edit_statut').value = livre.statut;
            openModal('editModal');
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>