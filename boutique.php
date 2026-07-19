<?php
session_start();
require_once 'config/database.php';

// Récupérer tous les livres disponibles
try {
    $livres = $pdo->query("SELECT * FROM livres WHERE statut = 'disponible' AND stock > 0 ORDER BY date_ajout DESC")->fetchAll();
} catch (PDOException $e) {
    $livres = [];
}

// Gestion du panier
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$message = '';
if (isset($_GET['action']) && $_GET['action'] === 'ajout_panier' && isset($_GET['livre_id'])) {
    $livre_id = intval($_GET['livre_id']);
    
    // Vérifier si le livre existe et est disponible
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = ? AND statut = 'disponible' AND stock > 0");
    $stmt->execute([$livre_id]);
    $livre = $stmt->fetch();
    
    if ($livre) {
        // Ajouter au panier
        if (isset($_SESSION['panier'][$livre_id])) {
            $_SESSION['panier'][$livre_id]['quantite']++;
        } else {
            $_SESSION['panier'][$livre_id] = [
                'titre' => $livre['titre'],
                'prix' => $livre['prix'],
                'quantite' => 1,
                'image' => $livre['image']
            ];
        }
        $message = "✅ Livre ajouté au panier !";
    }
}

// Calcul du total du panier
$total_panier = 0;
$nombre_articles = 0;
foreach ($_SESSION['panier'] as $item) {
    $total_panier += $item['prix'] * $item['quantite'];
    $nombre_articles += $item['quantite'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique - Cris des Orphelins</title>
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

        .btn-back:hover {
            background: #34495e;
        }

        .panier-widget {
            position: relative;
            background: #e74c3c;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .panier-badge {
            background: white;
            color: #e74c3c;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .hero-boutique {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(243, 156, 18, 0.9));
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .hero-boutique h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: bold;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .livres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .livre-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .livre-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .livre-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }

        .livre-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .livre-titre {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .livre-auteur {
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .livre-description {
            color: #555;
            margin-bottom: 1rem;
            line-height: 1.6;
            flex-grow: 1;
        }

        .livre-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .livre-prix {
            font-size: 1.5rem;
            color: #e74c3c;
            font-weight: bold;
        }

        .livre-stock {
            font-size: 0.9rem;
            color: #27ae60;
            font-weight: 600;
        }

        .btn-acheter {
            background: #e74c3c;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
            text-align: center;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-acheter:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state h2 {
            color: #7f8c8d;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .livres-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-boutique h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">
                <span>📚 Boutique - Cris des Orphelins</span>
            </a>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="index.php" class="btn-back">← Retour à l'accueil</a>
                <a href="panier.php" class="panier-widget">
                    🛒 Panier
                    <?php if ($nombre_articles > 0): ?>
                        <span class="panier-badge"><?= $nombre_articles ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>
    </header>

    <section class="hero-boutique">
        <h1>📚 Notre Boutique Solidaire</h1>
        <p>Chaque achat soutient nos actions auprès des orphelins</p>
    </section>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (count($livres) > 0): ?>
            <div class="livres-grid">
                <?php foreach ($livres as $livre): ?>
                    <div class="livre-card">
                        <?php if (!empty($livre['image'])): ?>
                            <img src="uploads/livres/<?= htmlspecialchars($livre['image']) ?>" 
                                 alt="<?= htmlspecialchars($livre['titre']) ?>" 
                                 class="livre-image">
                        <?php else: ?>
                            <img src="page.jpeg" alt="<?= htmlspecialchars($livre['titre']) ?>" class="livre-image">
                        <?php endif; ?>
                        
                        <div class="livre-content">
                            <h3 class="livre-titre"><?= htmlspecialchars($livre['titre']) ?></h3>
                            <p class="livre-auteur">Par <?= htmlspecialchars($livre['auteur']) ?></p>
                            
                            <?php if (!empty($livre['description'])): ?>
                                <p class="livre-description">
                                    <?= htmlspecialchars(substr($livre['description'], 0, 150)) ?>
                                    <?= strlen($livre['description']) > 150 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="livre-footer">
                                <div>
                                    <div class="livre-prix"><?= number_format($livre['prix'], 0, ',', ' ') ?> FCFA</div>
                                    <div class="livre-stock">✓ <?= $livre['stock'] ?> en stock</div>
                                </div>
                            </div>
                            
                            <a href="?action=ajout_panier&livre_id=<?= $livre['id'] ?>" class="btn-acheter">
                                🛒 Ajouter au panier
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>📚 Aucun livre disponible pour le moment</h2>
                <p>Revenez bientôt pour découvrir nos nouveautés !</p>
                <a href="index.php" class="btn-acheter" style="max-width: 300px; margin: 2rem auto;">
                    Retour à l'accueil
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>