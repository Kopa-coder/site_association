<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

// Vérifier si admin est connecté
$isAdmin = isLoggedIn();

// Récupérer les données dynamiques depuis la base de données
try {
    // Slogans actifs
    $slogans = $pdo->query("SELECT * FROM slogans WHERE actif = 1 ORDER BY ordre ASC")->fetchAll();
    
    // Statistiques
    $statistiques = $pdo->query("SELECT * FROM statistiques ORDER BY ordre ASC")->fetchAll();
    
    // Témoignages actifs
    $temoignages = $pdo->query("SELECT * FROM temoignages WHERE actif = 1 ORDER BY ordre ASC LIMIT 3")->fetchAll();
    
    // Missions
    $missions = $pdo->query("SELECT * FROM missions WHERE actif = 1 ORDER BY ordre ASC LIMIT 6")->fetchAll();
    
    // Fondateurs
    $fondateurs = $pdo->query("SELECT * FROM fondateurs WHERE actif = 1 ORDER BY ordre ASC")->fetchAll();
    
    // Livres pour la boutique
    $livres_vedette = $pdo->query("SELECT * FROM livres WHERE statut = 'disponible' ORDER BY date_ajout DESC LIMIT 1")->fetch();
    
    // Informations de contact
    $contact_info = $pdo->query("SELECT * FROM contact_info LIMIT 1")->fetch();
    
} catch (PDOException $e) {
    // En cas d'erreur, utiliser des tableaux vides
    $slogans = [];
    $statistiques = [];
    $temoignages = [];
    $missions = [];
    $fondateurs = [];
    $livres_vedette = null;
    $contact_info = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cris des Orphelins - Association Humanitaire</title>
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
        }

        .logo-icon {
            width: 100px;
            height: 100px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #e74c3c;
        }

        .btn-donate {
            background: #e74c3c;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-donate:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-admin {
            background: #2c3e50;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            margin-right: 1rem;
        }

        .btn-admin:hover {
            background: #34495e;
            transform: translateY(-2px);
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #e74c3c;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .quick-action-btn:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.3);
        }

        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 999;
            text-decoration: none;
        }

        .fab:hover {
            background: #c0392b;
            transform: scale(1.1);
        }

        .section-nav {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(255,255,255,0.2);
        }

        .section-nav-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .section-nav-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            border: 2px solid white;
            transition: all 0.3s;
            font-weight: 600;
        }

        .section-nav-btn:hover {
            background: white;
            color: #e74c3c;
        }

        .novel-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .novel-btn {
            padding: 1rem 2rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .novel-btn:hover {
            background: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .novel-btn.secondary {
            background: transparent;
            border: 2px solid #e74c3c;
            color: #e74c3c;
        }

        .novel-btn.secondary:hover {
            background: #e74c3c;
            color: white;
        }

        .hero {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.9), rgba(243, 156, 18, 0.9));
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInUp 1s;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 1s 0.2s backwards;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s 0.4s backwards;
        }

        .btn-primary, .btn-secondary {
            padding: 1rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: white;
            color: #e74c3c;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255,255,255,0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #e74c3c;
        }

        .slogans {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            padding: 4rem 2rem;
            color: white;
        }

        .slogans h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .slogans-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .slogan-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .slogan-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .slogan-card p {
            font-size: 1.1rem;
            line-height: 1.8;
            font-style: italic;
            margin: 0;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .stats {
            background: #2c3e50;
            color: white;
            padding: 3rem 2rem;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 3rem;
            color: #f39c12;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            font-size: 1.1rem;
        }

        .mission {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .mission h2 {
            text-align: center;
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 3rem;
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .mission-card {
            background: #fff;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .mission-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .mission-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        .mission-card h3 {
            color: #e74c3c;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .impact {
            background: linear-gradient(135deg, #f39c12, #e74c3c);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .impact h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .impact-content {
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.2rem;
            line-height: 1.8;
        }

        .novel {
            background: #f8f9fa;
            padding: 4rem 2rem;
        }

        .novel-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .novel-image {
            width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }

        .novel-image:hover {
            transform: scale(1.02);
        }

        .novel-content {
            color: #333;
        }

        .novel-content h2 {
            font-size: 2.5rem;
            color: #e74c3c;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .novel-content p {
            font-size: 1.1rem;
            line-height: 1.9;
            margin-bottom: 1.5rem;
            text-align: justify;
        }

        .founders {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .founders h2 {
            text-align: center;
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 2.5rem;
        }

        .founders-intro {
            max-width: 800px;
            margin: 0 auto 2.5rem;
            text-align: center;
            font-size: 1.05rem;
            color: #555;
            line-height: 1.8;
        }

        .founders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
        }

        .founder-card {
            background: #fff;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
        }

        .founder-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .founder-avatar img {
            width: 200%;
            height: 155%;
            border-radius: 50%;
            object-fit: cover;
        }

        .founder-name {
            font-weight: bold;
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .founder-role {
            font-size: 0.95rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .founder-quote {
            font-style: italic;
            color: #555;
            line-height: 1.7;
            font-size: 0.9rem;
        }

        .testimonials {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .testimonials h2 {
            text-align: center;
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 3rem;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            border-left: 5px solid #e74c3c;
        }

        .testimonial-card p {
            font-style: italic;
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        .testimonial-author {
            font-weight: bold;
            color: #e74c3c;
        }

        .cta {
            background: #2c3e50;
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        footer {
            background: #1a252f;
            color: white;
            padding: 3rem 2rem 1rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            color: #f39c12;
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #f39c12;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #333;
            color: #888;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .hero h1 {
                font-size: 2rem;
            }
            .hero p {
                font-size: 1.1rem;
            }
            .novel-container {
                grid-template-columns: 1fr;
            }
            .founders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <div class="logo-icon"><img src="logo.jpeg" alt="Logo"></div>
                <span>Cris des Orphelins</span>
            </div>
            <ul class="nav-links">
                <li><a href="#accueil">🏠 Accueil</a></li>
                <li><a href="#mission">🎯 Mission</a></li>
                <li><a href="#roman">📖 Roman</a></li>
                <li><a href="#fondateurs">👥 Fondateurs</a></li>
                <li><a href="#impact">💪 Impact</a></li>
                <li><a href="#temoignages">💬 Témoignages</a></li>
                <li><a href="boutique.php">📚 Boutique</a></li>
                <li><a href="#contact">📞 Contact</a></li>
                <li><a href="devenir_partenaire.php">🤝 Devenir Partenaire</a></li>
            </ul>
            <div style="display: flex; align-items: center; gap: 1rem;">
              
                <a href="faire_un_don.php" class="btn-donate">💝 Faire un don</a>
            </div>
        </nav>
    </header>

    <section class="hero" id="accueil">
        <h1>Ensemble, donnons une voix aux orphelins</h1>
        <p>Chaque enfant mérite un avenir. Aidez-nous à transformer leurs cris en espoir et leurs rêves en réalité.</p>
        <div class="hero-buttons">
            <a href="faire_un_don.php" class="btn-primary">💝 Soutenir notre cause</a>
            <a href="#mission" class="btn-secondary">🎯 Découvrir nos actions</a>
            <a href="boutique.php" class="btn-primary">📚 Acheter le livre</a>
        </div>
    </section>

    <?php if (count($slogans) > 0): ?>
    <section class="slogans" id="slogans">
        <h2>Nos Slogans</h2>
        <div class="slogans-grid">
            <?php foreach ($slogans as $slogan): ?>
            <div class="slogan-card">
                <p><?php echo nl2br(htmlspecialchars($slogan['texte'])); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="stats">
        <div class="stats-container">
            <?php if (count($statistiques) > 0): ?>
                <?php foreach ($statistiques as $stat): ?>
                <div class="stat-item">
                    <h3><?php echo htmlspecialchars($stat['valeur']); ?></h3>
                    <p><?php echo htmlspecialchars($stat['description']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="stat-item">
                    <h3>5,000+</h3>
                    <p>Enfants aidés</p>
                </div>
                <div class="stat-item">
                    <h3>15</h3>
                    <p>Pays d'intervention</p>
                </div>
                <div class="stat-item">
                    <h3>30</h3>
                    <p>Centres d'accueil</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Assistance disponible</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="quick-actions">
            <a href="#mission" class="quick-action-btn">🎯 Nos Actions</a>
            <a href="boutique.php" class="quick-action-btn">📚 Boutique</a>
            <a href="faire_un_don.php" class="quick-action-btn">💝 Faire un don</a>
            <a href="#contact" class="quick-action-btn">📞 Contact</a>
        </div>
    </section>

    <section class="mission" id="mission">
        <h2>Nos Domaines d'Action</h2>
        <div class="mission-grid">
            <?php if (count($missions) > 0): ?>
                <?php foreach ($missions as $mission): ?>
                <div class="mission-card">
                    <div class="mission-icon"><?php echo htmlspecialchars($mission['icone'] ?? '🎯'); ?></div>
                    <h3><?php echo htmlspecialchars($mission['titre']); ?></h3>
                    <p><?php echo htmlspecialchars($mission['description']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mission-card">
                    <div class="mission-icon">🏠</div>
                    <h3>Logement</h3>
                    <p>Nous offrons des foyers sécurisés et chaleureux où les enfants peuvent grandir dans un environnement aimant et protecteur.</p>
                </div>
                <div class="mission-card">
                    <div class="mission-icon">📚</div>
                    <h3>Éducation</h3>
                    <p>Accès à une éducation de qualité pour tous les orphelins, incluant le matériel scolaire, les uniformes et le suivi pédagogique.</p>
                </div>
                <div class="mission-card">
                    <div class="mission-icon">🏥</div>
                    <h3>Santé</h3>
                    <p>Soins médicaux complets, vaccinations, suivi nutritionnel et accompagnement psychologique.</p>
                </div>
                <div class="mission-card">
                    <div class="mission-icon">💼</div>
                    <h3>Formation Professionnelle</h3>
                    <p>Programmes d'apprentissage pour préparer les jeunes à une vie autonome.</p>
                </div>
                <div class="mission-card">
                    <div class="mission-icon">❤️</div>
                    <h3>Soutien Psychologique</h3>
                    <p>Accompagnement émotionnel pour surmonter les traumatismes.</p>
                </div>
                <div class="mission-card">
                    <div class="mission-icon">🤝</div>
                    <h3>Réinsertion Sociale</h3>
                    <p>Programmes de parrainage et d'intégration sociale.</p>
                </div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 3rem;">
            <div class="quick-actions">
                <a href="faire_un_don.php" class="quick-action-btn">💝 Faire un don</a>
                <a href="boutique.php" class="quick-action-btn">📚 Boutique</a>
                <a href="#impact" class="quick-action-btn">💪 Voir notre impact</a>
            </div>
        </div>
    </section>

    <section class="impact" id="impact">
        <h2>Notre Impact sur le Terrain</h2>
        <div class="impact-content">
            <p>Depuis notre création, Cris des Orphelins s'engage quotidiennement auprès des enfants les plus vulnérables.</p>
            <p><strong>Chaque don compte. Chaque geste fait la différence.</strong></p>
            <p>Ensemble, nous construisons un avenir meilleur.</p>
        </div>
        <div class="section-nav">
            <div class="section-nav-buttons">
                <a href="faire_un_don.php" class="section-nav-btn">💝 Faire un don</a>
                <a href="#mission" class="section-nav-btn">🎯 Nos actions</a>
                <a href="#temoignages" class="section-nav-btn">💬 Témoignages</a>
            </div>
        </div>
    </section>

    <section class="novel" id="roman">
        <div class="novel-container">
            <div class="novel-image-wrapper">
                
                    <img src="page.jpeg" alt="CRIS DES ORPHELINS" class="novel-image">
                
            </div>
            <div class="novel-content">
             
                    <h2>CRIS DES ORPHELINS</h2>
                    <p>Dans un village du NOSO, des enfants innocents grandissent avec pour seule richesse leurs rêves et leurs cicatrices.</p>
                    <p>Mais les CRIS DES ORPHELINS ne sont pas que des plaintes : ce sont des appels à la vie, des éclats de courage.</p>
                    <p><strong>CRIS DES ORPHELINS</strong>, un roman bouleversant sur l'enfance volée et la résilience.</p>
                
                <div class="novel-actions">
                    <a href="boutique.php" class="novel-btn">🛒 Acheter maintenant</a>
                    <a href="#slogans" class="novel-btn secondary">📝 Voir les slogans</a>
                    <a href="#temoignages" class="novel-btn secondary">💬 Lire les témoignages</a>
                </div>
            </div>
        </div>
    </section>

    <?php if (count($fondateurs) > 0): ?>
    <section class="founders" id="fondateurs">
        <h2>À propos des membres fondateurs</h2>
        <p class="founders-intro">
            Derrière <strong>Cris des Orphelins</strong>, il y a des femmes et des hommes engagés qui ont choisi de transformer la douleur en espoir.
            Voici quelques mots de celles et ceux qui portent cette vision au quotidien.
        </p>
        <div class="founders-grid">
        <?php foreach ($fondateurs as $fondateur): ?>
            <div class="founder-card">
                <div class="founder-avatar">
                    <?php if (!empty($fondateur['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($fondateur['photo']); ?>" alt="<?php echo htmlspecialchars($fondateur['nom']); ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr($fondateur['nom'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="founder-name"><?php echo htmlspecialchars($fondateur['nom']); ?></div>
                <div class="founder-role"><?php echo htmlspecialchars($fondateur['role']); ?></div>
                <p class="founder-quote"><?php echo nl2br(htmlspecialchars($fondateur['bio'])); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="testimonials" id="temoignages">
        <h2>Ils Témoignent</h2>
        <div class="testimonial-grid">
            <?php if (count($temoignages) > 0): ?>
                <?php foreach ($temoignages as $temoignage): ?>
                <div class="testimonial-card">
                    <p>"<?php echo nl2br(htmlspecialchars($temoignage['contenu'])); ?>"</p>
                    <p class="testimonial-author">- <?php echo htmlspecialchars($temoignage['auteur']); ?><?php if (!empty($temoignage['fonction'])): ?>, <?php echo htmlspecialchars($temoignage['fonction']); ?><?php endif; ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="testimonial-card">
                    <p>"Grâce à Cris des Orphelins, j'ai pu poursuivre mes études et réaliser mon rêve de devenir enseignante."</p>
                    <p class="testimonial-author">- Sarah, 24 ans</p>
                </div>
                <div class="testimonial-card">
                    <p>"Cette association fait un travail remarquable. Le professionnalisme et l'amour qu'ils portent à chaque enfant sont admirables."</p>
                    <p class="testimonial-author">- Dr. Jean Mbala</p>
                </div>
                <div class="testimonial-card">
                    <p>"Parrainer un enfant avec Cris des Orphelins a changé ma vie autant que la sienne."</p>
                    <p class="testimonial-author">- Marie Dupont</p>
                </div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="faire_un_don.php" class="quick-action-btn" style="background: #e74c3c; color: white;">💝 Faire un don</a>
            <a href="boutique.php" class="quick-action-btn" style="background: #e74c3c; color: white;">📚 Boutique</a>
            <a href="#contact" class="quick-action-btn" style="background: #e74c3c; color: white;">📞 Contact</a>
        </div>
  <!-- AJOUTEZ CETTE SECTION APRÈS LA SECTION TESTIMONIALS (après la ligne ~900) -->

<section class="testimonial-form-section" id="laisser-temoignage">
    <div class="testimonial-form-container">
        <h2>Partagez Votre Témoignage</h2>
        <p class="form-intro">Votre expérience avec Cris des Orphelins peut inspirer d'autres personnes. Partagez votre histoire avec nous !</p>
        
        <?php if (isset($_GET['temoignage_success'])): ?>
            <div class="success-message">
                ✅ <strong>Merci pour votre témoignage !</strong><br>
                Il sera examiné par notre équipe avant publication. Vous recevrez une notification une fois qu'il sera approuvé.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['temoignage_error'])): ?>
            <div class="error-message">
                ❌ <strong>Une erreur est survenue.</strong><br>
                <?php 
                if (isset($_SESSION['temoignage_erreurs'])) {
                    echo implode('<br>', $_SESSION['temoignage_erreurs']);
                    unset($_SESSION['temoignage_erreurs']);
                } else {
                    echo "Veuillez vérifier vos informations et réessayer.";
                }
                ?>
            </div>
        <?php endif; ?>

        <form action="traiter_temoignage.php" method="POST" class="testimonial-form">
            <div class="form-group">
                <label for="auteur">Votre Nom *</label>
                <input type="text" id="auteur" name="auteur" required placeholder="Ex: Marie Dupont">
            </div>

            <div class="form-group">
                <label for="fonction">Votre Fonction/Rôle (optionnel)</label>
                <input type="text" id="fonction" name="fonction" placeholder="Ex: Parrain, Bénévole, Donateur...">
            </div>

            <div class="form-group">
                <label for="contenu">Votre Témoignage *</label>
                <textarea id="contenu" name="contenu" rows="6" required placeholder="Partagez votre expérience avec Cris des Orphelins..."></textarea>
                <small style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">Votre témoignage sera examiné par notre équipe avant publication.</small>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="accepte_publication" required>
                    J'accepte que mon témoignage soit publié sur le site
                </label>
            </div>

            <button type="submit" class="submit-btn">📝 Envoyer mon témoignage</button>
        </form>
    </div>
</section>

<!-- AJOUTEZ CE CSS DANS VOTRE SECTION <style> -->
<style>
.testimonial-form-section {
    background: #f8f9fa;
    padding: 4rem 2rem;
}

.testimonial-form-container {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    padding: 3rem;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.testimonial-form-container h2 {
    text-align: center;
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.form-intro {
    text-align: center;
    color: #555;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #28a745;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #dc3545;
}

.testimonial-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.form-group input,
.form-group textarea {
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #e74c3c;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: normal;
    cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.submit-btn {
    background: linear-gradient(135deg, #e74c3c, #f39c12);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 30px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 1rem;
}

.submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
}

@media (max-width: 768px) {
    .testimonial-form-container {
        padding: 2rem 1.5rem;
    }
    
    .testimonial-form-container h2 {
        font-size: 2rem;
    }
}
</style>
    <section class="cta" id="don">
        <h2>Agissez Maintenant</h2>
        <p>Votre soutien peut transformer la vie d'un enfant. Choisissez comment vous souhaitez nous aider.</p>
        <div class="hero-buttons">
            <a href="faire_un_don.php" class="btn-primary">💝 Faire un don unique</a>
            <a href="faire_un_don.php" class="btn-secondary">🤝 Devenir parrain/marraine</a>
            <a href="boutique.php" class="btn-primary">📚 Acheter le livre</a>
        </div>
    </section>

    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Cris des Orphelins</h3>
                <p>Association humanitaire dédiée à la protection et au développement des enfants orphelins.</p>
            </div>
            <div class="footer-section">
                <h3>Liens Rapides</h3>
                <ul>
                    <li><a href="#accueil">🏠 Accueil</a></li>
                    <li><a href="#mission">🎯 Nos missions</a></li>
                    <li><a href="#roman">📖 Le roman</a></li>
                    <li><a href="#fondateurs">👥 Fondateurs</a></li>
                    <li><a href="#impact">💪 Notre impact</a></li>
                    <li><a href="#temoignages">💬 Témoignages</a></li>
                    <li><a href="boutique.php">📚 Boutique</a></li>
                    <li><a href="faire_un_don.php">💝 Faire un don</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    
                        <li>📧 crisdesorphelins4@gmail.com</li>
                        <li>📞 +237 682 442 426</li>
                        <li>📍 Yaoundé, Cameroun</li>
                    
                </ul>
            </div>
            <div class="footer-section">
                <h3>Suivez-nous</h3>
                <ul>
                        <li><a href="https://www.facebook.com/profile.php?id=61585103147148&mibextid=rS40aB7S9Ucbxw6v">📘 Facebook</a></li>
                    

                        <li><a href="#">🐦 Twitter</a></li>
                    
                        <li><a href="#">📷 Instagram</a></li>
                    
                   
                        <li><a href="#">💼 LinkedIn</a></li>
                    
                </ul>
                <div style="margin-top: 1rem;">
                <?php if ($isAdmin): ?>
                        <a href="admin/dashboard.php" ">Merci pour tout</a>
                    <?php else: ?>
                        <a href="admin/login.php"">Merci pour tout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Cris des Orphelins. Tous droits réservés. | Association reconnue d'utilité publique</p>
        </div>
    </footer>

    <a href="faire_un_don.php" class="fab" title="Faire un don">💝</a>

</body>
</html>