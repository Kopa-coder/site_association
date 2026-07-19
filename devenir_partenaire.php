<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devenir Partenaire - Cris des Orphelins</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .partner-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            display: block;
        }

        .partner-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
        }

        .card-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 2rem;
        }

        .partner-card.donateur .card-icon {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .partner-card.demandeur .card-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .partner-card h2 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .partner-card p {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .features-list {
            list-style: none;
            margin-bottom: 2rem;
        }

        .features-list li {
            padding: 0.8rem 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .features-list li:last-child {
            border-bottom: none;
        }

        .features-list li::before {
            content: "✓";
            color: #27ae60;
            font-weight: bold;
            font-size: 1.3rem;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 1.2rem;
            background: #e74c3c;
            color: white;
            text-align: center;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn.btn-donateur {
            background: #27ae60;
        }

        .btn.btn-donateur:hover {
            background: #229954;
        }

        .btn.btn-demandeur {
            background: #3498db;
        }

        .btn.btn-demandeur:hover {
            background: #2980b9;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤝 Devenir Partenaire</h1>
            <p>Choisissez le type de partenariat qui vous correspond</p>
        </div>

        <div class="cards-container">
            <!-- DONATEUR -->
            <div class="partner-card donateur">
                <div class="card-icon">💝</div>
                <h2>Devenir Donateur</h2>
                <p>Vous souhaitez soutenir notre association et contribuer à améliorer la vie des orphelins</p>
                
                <ul class="features-list">
                    <li>Dons financiers ponctuels ou réguliers</li>
                    <li>Dons matériels (vêtements, fournitures, etc.)</li>
                    <li>Partage de compétences et expertise</li>
                    <li>Mise à disposition d'infrastructures</li>
                    <li>Parrainage de projets spécifiques</li>
                </ul>

                <a href="formulaire_partenaire.php?type=donateur" class="btn btn-donateur">
                    Je deviens donateur →
                </a>
            </div>

            <!-- DEMANDEUR -->
            <div class="partner-card demandeur">
                <div class="card-icon">🙏</div>
                <h2>Devenir Demandeur</h2>
                <p>Vous avez besoin d'aide pour un orphelin ou un groupe d'orphelins</p>
                
                <ul class="features-list">
                    <li>Aide financière pour les besoins urgents</li>
                    <li>Matériel scolaire et éducatif</li>
                    <li>Soins médicaux et suivi santé</li>
                    <li>Formation professionnelle</li>
                    <li>Soutien pour infrastructure d'accueil</li>
                </ul>

                <a href="formulaire_partenaire.php?type=demandeur" class="btn btn-demandeur">
                    Je fais une demande →
                </a>
            </div>
        </div>

        <div class="back-link">
            <a href="index.php">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>