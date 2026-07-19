<?php
session_start();
require_once 'config/database.php';

$type = $_GET['type'] ?? '';
if (!in_array($type, ['donateur', 'demandeur'])) {
    header('Location: devenir_partenaire.php');
    exit;
}

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Données du partenaire
    $type_entite = $_POST['type_entite'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $pays = trim($_POST['pays'] ?? 'Cameroun');
    $site_web = trim($_POST['site_web'] ?? '');
    $message_txt = trim($_POST['message'] ?? '');
    
    // Validation basique
    if (empty($nom) || empty($email) || empty($telephone) || empty($adresse) || empty($ville)) {
        $error = "Veuillez remplir tous les champs obligatoires";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO partenaires (
                    type_partenaire, type_entite, nom, prenom, nom_entreprise,
                    email, telephone, adresse, ville, pays, site_web, message
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $type, $type_entite, $nom, $prenom, $nom_entreprise,
                $email, $telephone, $adresse, $ville, $pays, $site_web, $message_txt
            ]);
            
            $success = true;
            $message = "Votre demande de partenariat a été envoyée avec succès ! Nous vous contacterons bientôt.";
            
        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

$titre = $type === 'donateur' ? 'Devenir Partenaire Donateur' : 'Devenir Partenaire Demandeur';
$icon = $type === 'donateur' ? '💝' : '🙏';
$color = $type === 'donateur' ? '#27ae60' : '#3498db';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre ?> - Cris des Orphelins</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 3px solid <?= $color ?>;
        }

        .header-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .section-title {
            background: <?= $color ?>;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin: 2rem 0 1.5rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: <?= $color ?>;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            cursor: pointer;
        }

        .radio-group input[type="radio"] {
            width: auto;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: <?= $color ?>;
            color: white;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }

        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }

        .info-box p {
            margin: 0.5rem 0;
            color: #2c3e50;
        }

        @media (max-width: 768px) {
            .container { padding: 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function toggleFields() {
            const typeEntite = document.querySelector('input[name="type_entite"]:checked')?.value;
            const prenomField = document.getElementById('prenom-field');
            const entrepriseField = document.getElementById('entreprise-field');
            
            if (typeEntite === 'particulier') {
                prenomField.style.display = 'block';
                entrepriseField.style.display = 'none';
            } else {
                prenomField.style.display = 'none';
                entrepriseField.style.display = 'block';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon"><?= $icon ?></div>
            <h1><?= $titre ?></h1>
            <p>Remplissez vos informations de contact</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <h2>✓ Demande envoyée !</h2>
                <p><?= htmlspecialchars($message) ?></p>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="devenir_partenaire.php" class="btn btn-primary" style="max-width: 300px; display: inline-block;">
                    Nouvelle demande
                </a>
                <a href="index.php" class="btn btn-secondary" style="max-width: 300px; display: inline-block; margin-left: 1rem;">
                    Retour à l'accueil
                </a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="info-box">
                <p><strong>ℹ️ Information :</strong></p>
                <p>Ce formulaire permet de vous enregistrer comme partenaire. Après validation de votre demande, nous vous contacterons pour discuter des détails de votre contribution ou de vos besoins spécifiques.</p>
            </div>

            <form method="POST">
                <!-- INFORMATIONS DU PARTENAIRE -->
                <h2 class="section-title">👤 Vos informations</h2>

                <div class="form-group">
                    <label>Type de partenaire <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="type_entite" value="particulier" 
                                   onchange="toggleFields()" required checked>
                            Particulier
                        </label>
                        <label>
                            <input type="radio" name="type_entite" value="entreprise" 
                                   onchange="toggleFields()" required>
                            Entreprise
                        </label>
                        <label>
                            <input type="radio" name="type_entite" value="organisation" 
                                   onchange="toggleFields()" required>
                            Organisation/Association
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nom <span class="required">*</span></label>
                        <input type="text" name="nom" required>
                    </div>
                    
                    <div class="form-group" id="prenom-field">
                        <label>Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom">
                    </div>

                    <div class="form-group" id="entreprise-field" style="display: none;">
                        <label>Nom de l'entreprise/organisation</label>
                        <input type="text" name="nom_entreprise">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Téléphone <span class="required">*</span></label>
                        <input type="tel" name="telephone" required placeholder="+237 XXX XXX XXX">
                    </div>
                </div>

                <div class="form-group">
                    <label>Adresse complète <span class="required">*</span></label>
                    <input type="text" name="adresse" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ville <span class="required">*</span></label>
                        <input type="text" name="ville" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Pays</label>
                        <input type="text" name="pays" value="Cameroun">
                    </div>
                </div>

                <div class="form-group">
                    <label>Site web (optionnel)</label>
                    <input type="url" name="site_web" placeholder="https://...">
                </div>

                <!-- MESSAGE -->
                <h2 class="section-title">💬 Message</h2>
                <div class="form-group">
                    <label>Message complémentaire</label>
                    <textarea name="message" rows="4" 
                              placeholder="Parlez-nous brièvement de votre motivation à devenir partenaire..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $type === 'donateur' ? '💝 Envoyer ma demande de partenariat' : '🙏 Envoyer ma demande de partenariat' ?>
                </button>
            </form>

            <div class="back-link">
                <a href="devenir_partenaire.php">← Changer de type de partenariat</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>