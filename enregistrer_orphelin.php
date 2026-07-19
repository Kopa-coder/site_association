<?php
session_start();
require_once 'config/database.php';

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $lieu_naissance = trim($_POST['lieu_naissance'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    
    $situation_parents = $_POST['situation_parents'] ?? '';
    $date_orphelinat = $_POST['date_orphelinat'] ?? '';
    $raison_orphelinat = trim($_POST['raison_orphelinat'] ?? '');
    
    $nom_tuteur = trim($_POST['nom_tuteur'] ?? '');
    $prenom_tuteur = trim($_POST['prenom_tuteur'] ?? '');
    $lien_avec_enfant = trim($_POST['lien_avec_enfant'] ?? '');
    $telephone_tuteur = trim($_POST['telephone_tuteur'] ?? '');
    $email_tuteur = trim($_POST['email_tuteur'] ?? '');
    $adresse_tuteur = trim($_POST['adresse_tuteur'] ?? '');
    $ville_tuteur = trim($_POST['ville_tuteur'] ?? '');
    
    $niveau_scolaire = trim($_POST['niveau_scolaire'] ?? '');
    $etablissement_scolaire = trim($_POST['etablissement_scolaire'] ?? '');
    $besoins_urgents = trim($_POST['besoins_urgents'] ?? '');
    $problemes_sante = trim($_POST['problemes_sante'] ?? '');
    $observations = trim($_POST['observations'] ?? '');
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($sexe) || 
        empty($situation_parents) || empty($nom_tuteur) || empty($prenom_tuteur) || 
        empty($lien_avec_enfant) || empty($telephone_tuteur) || empty($adresse_tuteur) || empty($ville_tuteur)) {
        $error = "Veuillez remplir tous les champs obligatoires";
    } else {
        try {
            // Gestion de la photo
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                $upload_dir = 'uploads/orphelins/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = uniqid('orphelin_') . '.' . $extension;
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
            }
            
            // Insertion dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO orphelins (
                    nom, prenom, date_naissance, lieu_naissance, sexe, photo,
                    situation_parents, date_orphelinat, raison_orphelinat,
                    nom_tuteur, prenom_tuteur, lien_avec_enfant, telephone_tuteur, 
                    email_tuteur, adresse_tuteur, ville_tuteur,
                    niveau_scolaire, etablissement_scolaire, besoins_urgents, 
                    problemes_sante, observations, statut
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
            ");
            
            $stmt->execute([
                $nom, $prenom, $date_naissance, $lieu_naissance, $sexe, $photo,
                $situation_parents, $date_orphelinat, $raison_orphelinat,
                $nom_tuteur, $prenom_tuteur, $lien_avec_enfant, $telephone_tuteur,
                $email_tuteur, $adresse_tuteur, $ville_tuteur,
                $niveau_scolaire, $etablissement_scolaire, $besoins_urgents,
                $problemes_sante, $observations
            ]);
            
            $success = true;
            $message = "Enregistrement réussi ! Nous examinerons votre demande dans les plus brefs délais.";
            
        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enregistrer un Orphelin - Cris des Orphelins</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
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

        .success-message {
            text-align: center;
            padding: 3rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
        }

        .section-title {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
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
            border-color: #e74c3c;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }

        .info-box h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .info-box p {
            color: #555;
            line-height: 1.6;
        }

        .radio-group {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
        }

        .radio-group input[type="radio"] {
            width: auto;
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

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Enregistrer un Orphelin</h1>
            <p>Aidez-nous à identifier et soutenir les enfants dans le besoin</p>
        </div>

        <?php if ($success): ?>
            <div class="success-message">
                <div class="success-icon">✓</div>
                <div class="alert alert-success">
                    <h2>Enregistrement réussi !</h2>
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                    <a href="enregistrer_orphelin.php" class="btn btn-primary" style="width: auto;">
                        Enregistrer un autre orphelin
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="info-box">
                <h3>ℹ️ Informations importantes</h3>
                <p>Ce formulaire permet d'enregistrer un orphelin dans notre base de données pour lui apporter le soutien nécessaire. Toutes les informations fournies seront traitées de manière confidentielle.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <!-- INFORMATIONS DE L'ENFANT -->
                <h2 class="section-title">👤 Informations de l'enfant</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nom <span class="required">*</span></label>
                        <input type="text" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date de naissance <span class="required">*</span></label>
                        <input type="date" name="date_naissance" required value="<?= htmlspecialchars($_POST['date_naissance'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" value="<?= htmlspecialchars($_POST['lieu_naissance'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Sexe <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="sexe" value="M" required <?= (($_POST['sexe'] ?? '') === 'M') ? 'checked' : '' ?>>
                            Masculin
                        </label>
                        <label>
                            <input type="radio" name="sexe" value="F" required <?= (($_POST['sexe'] ?? '') === 'F') ? 'checked' : '' ?>>
                            Féminin
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Photo de l'enfant (optionnel)</label>
                    <input type="file" name="photo" accept="image/*">
                    <small style="color: #7f8c8d; display: block; margin-top: 0.5rem;">Formats acceptés: JPG, PNG. Max 5MB</small>
                </div>

                <!-- SITUATION FAMILIALE -->
                <h2 class="section-title">👨‍👩‍👧 Situation familiale</h2>

                <div class="form-group">
                    <label>Situation des parents <span class="required">*</span></label>
                    <select name="situation_parents" required>
                        <option value="">Sélectionnez...</option>
                        <option value="pere_decede" <?= (($_POST['situation_parents'] ?? '') === 'pere_decede') ? 'selected' : '' ?>>Père décédé</option>
                        <option value="mere_decede" <?= (($_POST['situation_parents'] ?? '') === 'mere_decede') ? 'selected' : '' ?>>Mère décédée</option>
                        <option value="les_deux_decedes" <?= (($_POST['situation_parents'] ?? '') === 'les_deux_decedes') ? 'selected' : '' ?>>Les deux parents décédés</option>
                        <option value="abandonnes" <?= (($_POST['situation_parents'] ?? '') === 'abandonnes') ? 'selected' : '' ?>>Enfant abandonné</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date de l'orphelinat</label>
                        <input type="date" name="date_orphelinat" value="<?= htmlspecialchars($_POST['date_orphelinat'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Raison/Circonstances de l'orphelinat</label>
                    <textarea name="raison_orphelinat" rows="3"><?= htmlspecialchars($_POST['raison_orphelinat'] ?? '') ?></textarea>
                </div>

                <!-- INFORMATIONS DU TUTEUR -->
                <h2 class="section-title">👤 Informations du tuteur / Personne qui enregistre</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nom <span class="required">*</span></label>
                        <input type="text" name="nom_tuteur" required value="<?= htmlspecialchars($_POST['nom_tuteur'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom_tuteur" required value="<?= htmlspecialchars($_POST['prenom_tuteur'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Lien avec l'enfant <span class="required">*</span></label>
                        <input type="text" name="lien_avec_enfant" required 
                               placeholder="Ex: Grand-mère, Oncle, Voisin, Travailleur social..."
                               value="<?= htmlspecialchars($_POST['lien_avec_enfant'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Téléphone <span class="required">*</span></label>
                        <input type="tel" name="telephone_tuteur" required 
                               placeholder="+237 XXX XXX XXX"
                               value="<?= htmlspecialchars($_POST['telephone_tuteur'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email_tuteur" value="<?= htmlspecialchars($_POST['email_tuteur'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Adresse complète <span class="required">*</span></label>
                    <input type="text" name="adresse_tuteur" required value="<?= htmlspecialchars($_POST['adresse_tuteur'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Ville <span class="required">*</span></label>
                    <input type="text" name="ville_tuteur" required value="<?= htmlspecialchars($_POST['ville_tuteur'] ?? '') ?>">
                </div>

                <!-- INFORMATIONS COMPLÉMENTAIRES -->
                <h2 class="section-title">📚 Informations complémentaires</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Niveau scolaire actuel</label>
                        <input type="text" name="niveau_scolaire" 
                               placeholder="Ex: CP, CE1, 6ème..."
                               value="<?= htmlspecialchars($_POST['niveau_scolaire'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Établissement scolaire</label>
                        <input type="text" name="etablissement_scolaire" value="<?= htmlspecialchars($_POST['etablissement_scolaire'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Besoins urgents</label>
                    <textarea name="besoins_urgents" rows="3" 
                              placeholder="Ex: Nourriture, vêtements, frais scolaires, soins médicaux..."><?= htmlspecialchars($_POST['besoins_urgents'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Problèmes de santé connus</label>
                    <textarea name="problemes_sante" rows="3"><?= htmlspecialchars($_POST['problemes_sante'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Observations / Informations supplémentaires</label>
                    <textarea name="observations" rows="4"><?= htmlspecialchars($_POST['observations'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    📝 Enregistrer l'orphelin
                </button>
            </form>

            <div class="back-link">
                <a href="index.php">← Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>