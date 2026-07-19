<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$message = '';
$error = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'supprimer') {
        $livre_id = intval($_POST['livre_id']);
        unset($_SESSION['panier'][$livre_id]);
        $message = "Article retiré du panier";
    }
    
    elseif ($action === 'modifier_quantite') {
        $livre_id = intval($_POST['livre_id']);
        $quantite = intval($_POST['quantite']);
        
        if ($quantite > 0) {
            // Vérifier le stock disponible
            $stmt = $pdo->prepare("SELECT stock FROM livres WHERE id = ?");
            $stmt->execute([$livre_id]);
            $livre = $stmt->fetch();
            
            if ($livre && $quantite <= $livre['stock']) {
                $_SESSION['panier'][$livre_id]['quantite'] = $quantite;
                $message = "Quantité mise à jour";
            } else {
                $error = "Stock insuffisant";
            }
        }
    }
    
    elseif ($action === 'vider') {
        $_SESSION['panier'] = [];
        $message = "Panier vidé";
    }
}

// Calculer le total
$total = 0;
$nombre_articles = 0;
foreach ($_SESSION['panier'] as $item) {
    $total += $item['prix'] * $item['quantite'];
    $nombre_articles += $item['quantite'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - Cris des Orphelins</title>
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
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            text-decoration: none;
        }

        .btn-back {
            background: #2c3e50;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .panier-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .panier-items {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .panier-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .panier-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .item-prix {
            color: #e74c3c;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }

        .quantite-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .quantite-control button {
            background: #e74c3c;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantite-control input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.3rem;
        }

        .btn-supprimer {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-supprimer:hover {
            background: #c82333;
        }

        .panier-resume {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .resume-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .resume-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
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
            padding: 1rem;
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

        .btn-vider {
            width: 100%;
            background: transparent;
            color: #dc3545;
            padding: 0.8rem;
            border: 2px solid #dc3545;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .btn-vider:hover {
            background: #dc3545;
            color: white;
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
        }

        .empty-cart h2 {
            color: #7f8c8d;
            margin-bottom: 1rem;
        }

        @media (max-width: 968px) {
            .panier-container {
                grid-template-columns: 1fr;
            }
            
            .panier-resume {
                position: static;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">
                <span>🛒 Mon Panier</span>
            </a>
            <a href="boutique.php" class="btn-back">← Continuer mes achats</a>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">🛒 Mon Panier (<?= $nombre_articles ?> article<?= $nombre_articles > 1 ? 's' : '' ?>)</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (count($_SESSION['panier']) > 0): ?>
            <div class="panier-container">
                <div class="panier-items">
                    <?php foreach ($_SESSION['panier'] as $livre_id => $item): ?>
                        <div class="panier-item">
                            <img src="uploads/livres/<?= htmlspecialchars($item['image'] ?: 'default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['titre']) ?>" 
                                 class="item-image">
                            
                            <div class="item-info">
                                <h3><?= htmlspecialchars($item['titre']) ?></h3>
                                <div class="item-prix"><?= number_format($item['prix'], 0, ',', ' ') ?> FCFA</div>
                                
                                <form method="POST" class="quantite-control">
                                    <input type="hidden" name="action" value="modifier_quantite">
                                    <input type="hidden" name="livre_id" value="<?= $livre_id ?>">
                                    <button type="submit" name="quantite" value="<?= $item['quantite'] - 1 ?>">-</button>
                                    <input type="number" name="quantite" value="<?= $item['quantite'] ?>" min="1" readonly>
                                    <button type="submit" name="quantite" value="<?= $item['quantite'] + 1 ?>">+</button>
                                </form>
                                
                                <p style="margin-top: 0.5rem; color: #7f8c8d;">
                                    Sous-total: <?= number_format($item['prix'] * $item['quantite'], 0, ',', ' ') ?> FCFA
                                </p>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="livre_id" value="<?= $livre_id ?>">
                                <button type="submit" class="btn-supprimer" onclick="return confirm('Retirer cet article ?')">
                                    🗑️ Retirer
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="panier-resume">
                    <h2 class="resume-title">📋 Résumé</h2>
                    
                    <div class="resume-line">
                        <span>Articles (<?= $nombre_articles ?>)</span>
                        <span><?= number_format($total, 0, ',', ' ') ?> FCFA</span>
                    </div>
                    
                    <div class="resume-line">
                        <span>Livraison</span>
                        <span style="color: #27ae60;">Facturer celon la distance</span>
                    </div>
                    
                    <div class="resume-total">
                        <span>Total</span>
                        <span><?= number_format($total, 0, ',', ' ') ?> FCFA</span>
                    </div>
                    
                    <a href="commander.php">
                        <button class="btn-commander">✓ Passer la commande</button>
                    </a>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="vider">
                        <button type="submit" class="btn-vider" onclick="return confirm('Vider le panier ?')">
                            🗑️ Vider le panier
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>🛒 Votre panier est vide</h2>
                <p style="color: #7f8c8d; margin-bottom: 2rem;">Découvrez nos livres et soutenez notre cause !</p>
                <a href="boutique.php" class="btn-back" style="display: inline-block;">
                    📚 Découvrir la boutique
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>