<?php
session_start();
require_once 'config/database.php';

// Vérifier que le panier n'est pas vide
if (!isset($_SESSION['panier']) || count($_SESSION['panier']) === 0) {
    header('Location: boutique.php');
    exit;
}

$message = '';
$error = '';
$commande_id = null;

// Calculer le total
$total = 0;
foreach ($_SESSION['panier'] as $item) {
    $total += $item['prix'] * $item['quantite'];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $mode_paiement = $_POST['mode_paiement'] ?? '';
    $numero_paiement = trim($_POST['numero_paiement'] ?? '');
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($telephone) || empty($adresse) || empty($ville)) {
        $error = "Veuillez remplir tous les champs obligatoires";
    }  else {
        try {
            $pdo->beginTransaction();
            
            // Générer une référence de commande unique
            $reference_commande = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Insérer la commande
            $stmt = $pdo->prepare("
                INSERT INTO commandes (nom, prenom, telephone, adresse, ville, 
                                      montant_total, mode_paiement, numero_paiement, reference_commande, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?,?, ?, 'en_attente')
            ");
            $stmt->execute([
                $nom, $prenom, $telephone, $adresse, 
                $ville, $total, $mode_paiement, $numero_paiement, $reference_commande
            ]);
            
            $commande_id = $pdo->lastInsertId();
            
            // Insérer les détails de commande et mettre à jour le stock
            $stmt_detail = $pdo->prepare("
                INSERT INTO details_commande (commande_id, livre_id, quantite, prix_unitaire) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt_stock = $pdo->prepare("UPDATE livres SET stock = stock - ? WHERE id = ?");
            
            foreach ($_SESSION['panier'] as $livre_id => $item) {
                // Insérer le détail de commande
                $stmt_detail->execute([
                    $commande_id,
                    $livre_id,
                    $item['quantite'],
                    $item['prix']
                ]);
                
                // Déduire du stock
                $stmt_stock->execute([$item['quantite'], $livre_id]);
            }
            
            $pdo->commit();
            
            // Vider le panier
            $_SESSION['panier'] = [];
            
            // Rediriger vers la page de confirmation
            header("Location: confirmation_commande.php?id=$commande_id");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la commande : " . $e->getMessage();
            error_log("Erreur commande : " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser ma commande - Cris des Orphelins</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        header {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .commande-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .formulaire-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #e74c3c;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .resume-commande {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .resume-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .resume-item:last-child {
            border-bottom: none;
        }

        .resume-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            padding-top: 1rem;
            border-top: 2px solid #eee;
            margin-top: 1rem;
        }

        .btn-commander {
            width: 100%;
            background: #e74c3c;
            color: white;
            padding: 1.2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }

        .btn-commander:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .mode-paiement {
            display: grid;
            gap: 1rem;
        }

        .mode-paiement-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .mode-paiement-option:hover {
            border-color: #e74c3c;
            background: #fff5f5;
        }

        .mode-paiement-option input[type="radio"] {
            width: auto;
            margin-right: 1rem;
        }

        .numero-paiement-field {
            display: none;
            margin-top: 1rem;
        }

        .numero-paiement-field.active {
            display: block;
        }

        @media (max-width: 968px) {
            .commande-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="color: #e74c3c;">✓ Finaliser ma commande</h1>
            <a href="panier.php" style="color: #2c3e50; text-decoration: none;">← Retour au panier</a>
        </div>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="commande-container">
                <div>
                    <div class="formulaire-section">
                        <h2 class="section-title">📋 Informations de livraison</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom <span class="required">*</span></label>
                                <input type="text" name="nom" required 
                                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Prénom <span class="required">*</span></label>
                                <input type="text" name="prenom" required 
                                       value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            
                            <div class="form-group">
                                <label>Téléphone <span class="required">*</span></label>
                                <input type="tel" name="telephone" required 
                                       value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                                       placeholder="+237 XXX XXX XXX">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Adresse de livraison <span class="required">*</span></label>
                            <input type="text" name="adresse" required 
                                   value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ville <span class="required">*</span></label>
                                <input type="text" name="ville" required 
                                       value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>">
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="formulaire-section" style="margin-top: 2rem;">
                        <h2 class="section-title">💳 Mode de paiement</h2>
                        
                        <div class="mode-paiement">
                            <label class="mode-paiement-option">
                                <input type="radio" name="mode_paiement" value="mobile_money" required 
                                       onchange="toggleNumeroField('mobile_money')">
                                <div>
                                    <strong>📱 Payment electronique</strong>
                                    <p style="font-size: 0.9rem; color: #666; margin-top: 0.3rem;">
                                        MTN, Orange Money, Express Union
                                    </p>
                                </div>
                            </label>
                            
                            <div id="mobile_money_field" class="numero-paiement-field">
                                <label>Payment electronique</label>
                                <input type="text" name="numero_paiement" 
                                       placeholder="+237 XXX XXX XXX">
                            </div>
                            
                            <label class="mode-paiement-option">
                                <input type="radio" name="mode_paiement" value="livraison" required
                                       onchange="toggleNumeroField('livraison')">
                                
                            </label>
                            
                            <label class="mode-paiement-option">
                                <input type="radio" name="mode_paiement" value="virement" required
                                       onchange="toggleNumeroField('virement')">
                                <div>
                                    <strong>🏦 Virement bancaire</strong>
                                    <p style="font-size: 0.9rem; color: #666; margin-top: 0.3rem;">
                                        Coordonnées envoyées par email
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="resume-commande">
                    <h2 class="section-title">📋 Récapitulatif</h2>
                    
                    <?php foreach ($_SESSION['panier'] as $item): ?>
                        <div class="resume-item">
                            <div>
                                <strong><?= htmlspecialchars($item['titre']) ?></strong>
                                <p style="font-size: 0.9rem; color: #666;">Quantité: <?= $item['quantite'] ?></p>
                            </div>
                            <div style="text-align: right;">
                                <strong><?= number_format($item['prix'] * $item['quantite'], 0, ',', ' ') ?> FCFA</strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="resume-item">
                        <span>Livraison</span>
                        <span style="color: #27ae60; font-weight: bold;">facturer selon la distance</span>
                    </div>
                    
                    <div class="resume-total">
                        <span>Total</span>
                        <span><?= number_format($total, 0, ',', ' ') ?> FCFA</span>
                    </div>
                    
                    <button type="submit" class="btn-commander">
                        ✓ Confirmer la commande
                    </button>
                    
                    <p style="text-align: center; font-size: 0.9rem; color: #666; margin-top: 1rem;">
                        🔒 Paiement sécurisé<br>
                        ❤️ Votre achat soutient nos orphelins
                    </p>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleNumeroField(mode) {
            document.getElementById('mobile_money_field').classList.remove('active');
            
            if (mode === 'mobile_money') {
                document.getElementById('mobile_money_field').classList.add('active');
            }
        }
    </script>
</body>
</html>