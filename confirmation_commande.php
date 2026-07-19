<?php
session_start();
require_once 'config/database.php';

// Récupérer l'ID de commande
$commande_id = intval($_GET['id'] ?? 0);

if ($commande_id === 0) {
    header('Location: boutique.php');
    exit;
}

// Récupérer les détails de la commande
try {
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch();
    
    if (!$commande) {
        header('Location: boutique.php');
        exit;
    }
    
    // Récupérer les détails de commande avec les titres de livres
    $stmt = $pdo->prepare("
        SELECT dc.*, l.titre 
        FROM details_commande dc
        LEFT JOIN livres l ON dc.livre_id = l.id
        WHERE dc.commande_id = ?
    ");
    $stmt->execute([$commande_id]);
    $details = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: boutique.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée - Cris des Orphelins</title>
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            width: 100%;
        }

        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .numero-commande {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1.2rem;
            color: #e74c3c;
            font-weight: bold;
            margin: 2rem 0;
        }

        .details-commande {
            text-align: left;
            margin: 2rem 0;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .details-section {
            margin-bottom: 2rem;
        }

        .details-section:last-child {
            margin-bottom: 0;
        }

        .details-section h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .detail-line {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-line:last-child {
            border-bottom: none;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
            padding-top: 1rem;
            border-top: 2px solid #dee2e6;
            margin-top: 1rem;
        }

        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-box p {
            color: #856404;
            margin-bottom: 0.5rem;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: #2c3e50;
            border: 2px solid #2c3e50;
        }

        .btn-secondary:hover {
            background: #2c3e50;
            color: white;
        }

        .merci-message {
            margin: 2rem 0;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(243, 156, 18, 0.1));
            border-radius: 15px;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }

        .merci-message h2 {
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .merci-message p {
            font-size: 1.1rem;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-card">
            <div class="success-icon">✓</div>
            
            <h1>Commande confirmée !</h1>
            <p style="font-size: 1.1rem; color: #666; margin-bottom: 1rem;">
                Merci pour votre commande. Nous l'avons bien reçue.
            </p>
            
            <div class="numero-commande">
                Référence : <?= htmlspecialchars($commande['reference_commande']) ?>
            </div>

            <div class="details-commande">
                <div class="details-section">
                    <h3>📦 Articles commandés</h3>
                    <?php foreach ($details as $detail): ?>
                        <div class="detail-line">
                            <span><?= htmlspecialchars($detail['titre']) ?> (x<?= $detail['quantite'] ?>)</span>
                            <span><?= number_format($detail['prix_unitaire'] * $detail['quantite'], 0, ',', ' ') ?> FCFA</span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="total-line">
                        <span>Total</span>
                        <span><?= number_format($commande['montant_total'], 0, ',', ' ') ?> FCFA</span>
                    </div>
                </div>

                <div class="details-section">
                    <h3>📍 Livraison</h3>
                    <div class="detail-line">
                        <span>Nom</span>
                        <span><?= htmlspecialchars($commande['nom'] . ' ' . $commande['prenom']) ?></span>
                    </div>
                    <div class="detail-line">
                        <span>Téléphone</span>
                        <span><?= htmlspecialchars($commande['telephone']) ?></span>
                    </div>
                    <div class="detail-line">
                        <span>Adresse</span>
                        <span><?= htmlspecialchars($commande['adresse']) ?>, <?= htmlspecialchars($commande['ville']) ?></span>
                    </div>
                </div>

                <div class="details-section">
                    <h3>💳 Paiement</h3>
                    <div class="detail-line">
                        <span>Mode</span>
                        <span>
                            <?php
                            $modes = [
                                'mobile_money' => '📱 Mobile Money',
                                'virement' => '🏦 Virement bancaire',
                                'carte' => '💳 Carte bancaire'
                            ];
                            echo $modes[$commande['mode_paiement']] ?? $commande['mode_paiement'];
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($commande['numero_paiement'])): ?>
                    <div class="detail-line">
                        <span>Numéro</span>
                        <span><?= htmlspecialchars($commande['numero_paiement']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($commande['mode_paiement'] === 'mobile_money'): ?>
                <div class="info-box">
                    <h3>📱 Instructions de paiement Mobile Money</h3>
                    <p><strong>Montant à payer :</strong> <?= number_format($commande['montant_total'], 0, ',', ' ') ?> FCFA</p>
                    <p><strong>Nos numéros :</strong></p>
                    <p>📱 MTN Mobile Money : +237 XXX XXX XXX</p>
                    <p>📱 Orange Money : +237 XXX XXX XXX</p>
                    <p style="margin-top: 1rem; font-style: italic;">
                        Envoyez-nous une capture d'écran du paiement par WhatsApp ou email avec la référence <strong><?= htmlspecialchars($commande['reference_commande']) ?></strong>
                    </p>
                </div>
            <?php elseif ($commande['mode_paiement'] === 'virement'): ?>
                <div class="info-box">
                    <h3>🏦 Coordonnées bancaires</h3>
                    <p><strong>Banque :</strong> [Nom de la banque]</p>
                    <p><strong>Titulaire :</strong> Cris des Orphelins</p>
                    <p><strong>N° de compte :</strong> XXXX XXXX XXXX</p>
                    <p><strong>Référence à mentionner :</strong> <?= htmlspecialchars($commande['reference_commande']) ?></p>
                    <p style="margin-top: 1rem; font-style: italic;">
                        Ces informations vous seront également envoyées par email.
                    </p>
                </div>
            <?php endif; ?>

            <div class="merci-message">
                <h2>❤️ Merci pour votre soutien !</h2>
                <p>
                    Votre achat contribue directement à améliorer la vie des orphelins que nous accompagnons.
                   Nous vous contacteront pour finaliser votre achat 
                </p>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">🏠 Retour à l'accueil</a>
                <a href="boutique.php" class="btn btn-secondary">📚 Continuer mes achats</a>
            </div>
        </div>
    </div>
</body>
</html>