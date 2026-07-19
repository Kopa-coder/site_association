<?php
require_once 'config/database.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = securiser($_POST['nom'] ?? '');
    $prenom = securiser($_POST['prenom'] ?? '');
    $email = securiser($_POST['email'] ?? '');
    $telephone = securiser($_POST['telephone'] ?? '');
    $type_don = securiser($_POST['type_don'] ?? '');
    $montant = floatval($_POST['montant'] ?? 0);
    $description_don_physique = securiser($_POST['description_don_physique'] ?? '');
    $mode_paiement = securiser($_POST['mode_paiement'] ?? '');
    $numero_paiement = securiser($_POST['numero_paiement'] ?? '');
    $message = securiser($_POST['message'] ?? '');
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone)) {
        $error = "Veuillez remplir tous les champs obligatoires";
    } elseif (empty($type_don)) {
        $error = "Veuillez sélectionner le type de don";
    } elseif ($type_don === 'monetaire') {
        // Validation pour don monétaire
        if ($montant < 500) {
            $error = "Le montant minimum est de 500 FCFA";
        } elseif (!in_array($mode_paiement, ['momo', 'om'])) {
            $error = "Mode de paiement invalide";
        } elseif (empty($numero_paiement)) {
            $error = "Veuillez entrer votre numéro de paiement";
        }
    } elseif ($type_don === 'physique') {
        // Validation pour don physique
        if (empty($description_don_physique)) {
            $error = "Veuillez décrire votre don physique";
        }
    } else {
        $error = "Type de don invalide";
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO dons (nom, prenom, email, telephone, type_don, montant, description_don_physique, mode_paiement, numero_paiement, message, reference_transaction) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $reference = genererReference('DON');
            
            // Pour les dons physiques, on met le montant à 0 et mode_paiement à NULL
            if ($type_don === 'physique') {
                $montant = 0;
                $mode_paiement = 'physique';
                $numero_paiement = '';
            }
            
            $stmt->execute([
                $nom, 
                $prenom, 
                $email, 
                $telephone, 
                $type_don, 
                $montant, 
                $description_don_physique, 
                $mode_paiement, 
                $numero_paiement, 
                $message, 
                $reference
            ]);
            
            $success = true;
        } catch (PDOException $e) {
            $error = "Une erreur s'est produite. Veuillez réessayer.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faire un Don - Cris des Orphelins</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #e74c3c;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
        }

        .success-message h3 {
            margin-bottom: 10px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #dc3545;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .donation-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .donation-type {
            position: relative;
        }

        .donation-type input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .donation-type label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
            border: 3px solid #ddd;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .donation-type label .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .donation-type label .title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .donation-type label .description {
            font-size: 0.85rem;
            color: #666;
        }

        .donation-type input[type="radio"]:checked + label {
            border-color: #e74c3c;
            background: #ffe8e5;
            color: #e74c3c;
        }

        .donation-type input[type="radio"]:checked + label .description {
            color: #c0392b;
        }

        .donation-type label:hover {
            border-color: #e74c3c;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-option {
            position: relative;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 3px solid #ddd;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .payment-option input[type="radio"]:checked + label {
            border-color: #e74c3c;
            background: #ffe8e5;
            color: #e74c3c;
        }

        .payment-option label:hover {
            border-color: #e74c3c;
        }

        .conditional-section {
            display: none;
            animation: fadeIn 0.3s;
        }

        .conditional-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }

        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #17a2b8;
        }

        .highlight-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #ffc107;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .donation-types {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💝 Faire un Don</h1>
            <p>Votre générosité change des vies</p>
        </div>

        <?php if ($success): ?>
            <div class="success-message">
                <h3>✅ Merci pour votre générosité !</h3>
                <?php if ($type_don === 'monetaire'): ?>
                    <p>Votre don monétaire a été enregistré avec succès. Nous vous contacterons pour finaliser le paiement.</p>
                <?php else: ?>
                    <p>Votre don a été enregistré avec succès. Nous vous contacterons pour organiser la collecte ou la livraison.</p>
                <?php endif; ?>
                <p><strong>Référence:</strong> <?php echo htmlspecialchars($reference); ?></p>
                <p style="margin-top: 15px;">
                    <a href="index.php" style="color: #155724; font-weight: bold;">← Retour à l'accueil</a>
                </p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-message">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="donationForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom <span class="required">*</span></label>
                        <input type="text" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Téléphone <span class="required">*</span></label>
                        <input type="tel" name="telephone" required placeholder="Ex: 6XXXXXXXX" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Type de don <span class="required">*</span></label>
                    <div class="donation-types">
                        <div class="donation-type">
                            <input type="radio" id="monetaire" name="type_don" value="monetaire" required <?php echo (isset($_POST['type_don']) && $_POST['type_don'] === 'monetaire') ? 'checked' : ''; ?>>
                            <label for="monetaire">
                                <div class="icon">💰</div>
                                <div class="title">Don financiere</div>
                                <div class="description">Argent via Mobile Money,Orange Money ou Virement bancaire</div>
                            </label>
                        </div>
                        <div class="donation-type">
                            <input type="radio" id="physique" name="type_don" value="physique" required <?php echo (isset($_POST['type_don']) && $_POST['type_don'] === 'physique') ? 'checked' : ''; ?>>
                            <label for="physique">
                                <div class="icon">🎁</div>
                                <div class="title">Don en nature</div>
                                <div class="description">Ordinateur, machine a coudre, etc.</div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section Don Monétaire -->
                <div id="monetaire-section" class="conditional-section">
                    <div class="info-box">
                        ℹ️ <strong>Important:</strong> Après soumission, un de nos conseillers vous contactera pour finaliser le paiement via Mobile Money,Orange Money ou virement bancaire.
                    </div>

                    <div class="form-group">
                        <label>Montant du don (FCFA) <span class="required">*</span></label>
                        <input type="number" name="montant" id="montant" min="500" step="100" placeholder="Entrez le montant de votre don (minimum 500 FCFA)" value="<?php echo htmlspecialchars($_POST['montant'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Mode de paiement <span class="required">*</span></label>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" id="momo" name="mode_paiement" value="momo" <?php echo (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] === 'momo') ? 'checked' : ''; ?>>
                                <label for="momo">📱 Mobile Money (MTN)</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" id="om" name="mode_paiement" value="om" <?php echo (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] === 'om') ? 'checked' : ''; ?>>
                                <label for="om">🟠 Orange Money</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" id="bank" name="mode_paiement" value="bank" <?php echo (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] === 'bank') ? 'checked' : ''; ?>>
                                <label for="bank">📱Virement bancaire</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Votre numéro de téléphone Mobile Money / Orange Money <span class="required">*</span></label>
                        <input type="tel" name="numero_paiement" placeholder="Ex: 6XXXXXXXX" value="<?php echo htmlspecialchars($_POST['numero_paiement'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Section Don Physique -->
                <div id="physique-section" class="conditional-section">
                    <div class="highlight-box">
                        🎁 <strong>Don en nature:</strong> Décrivez ce que vous souhaitez donner (laptop, machiche a coudre, meubles, matériel scolaire, jouets, etc.). Notre équipe vous contactera pour organiser la collecte.
                    </div>

                    <div class="form-group">
                        <label>Description détaillée du don <span class="required">*</span></label>
                        <textarea name="description_don_physique" rows="6" placeholder="Décrivez votre don en détail : type d'objets, quantité, état, valeur estimée si possible...&#10;&#10;Exemples :&#10;- 500 laptop, 100 machine a coudre&#10;- batiments pour logement des orphelins, ,- 100 ordinateur portable en bon état&#10; etc.)"><?php echo htmlspecialchars($_POST['description_don_physique'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label>Message (optionnel)</label>
                    <textarea name="message" rows="4" placeholder="Votre message de soutien..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">💝 Confirmer mon don</button>
            </form>

            <div class="back-link">
                <a href="index.php">← Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const form = document.getElementById('donationForm');
        const monetaireRadio = document.getElementById('monetaire');
        const physiqueRadio = document.getElementById('physique');
        const monetaireSection = document.getElementById('monetaire-section');
        const physiqueSection = document.getElementById('physique-section');

        function updateFormSections() {
            if (monetaireRadio.checked) {
                monetaireSection.classList.add('active');
                physiqueSection.classList.remove('active');
                
                // Rendre les champs monétaires obligatoires
                document.getElementById('montant').required = true;
                document.querySelectorAll('input[name="mode_paiement"]').forEach(input => {
                    input.required = true;
                });
                document.querySelector('input[name="numero_paiement"]').required = true;
                document.querySelector('textarea[name="description_don_physique"]').required = false;
            } else if (physiqueRadio.checked) {
                physiqueSection.classList.add('active');
                monetaireSection.classList.remove('active');
                
                // Rendre les champs physiques obligatoires
                document.querySelector('textarea[name="description_don_physique"]').required = true;
                document.getElementById('montant').required = false;
                document.querySelectorAll('input[name="mode_paiement"]').forEach(input => {
                    input.required = false;
                });
                document.querySelector('input[name="numero_paiement"]').required = false;
            }
        }

        monetaireRadio.addEventListener('change', updateFormSections);
        physiqueRadio.addEventListener('change', updateFormSections);

        // Initialiser au chargement de la page
        if (monetaireRadio.checked || physiqueRadio.checked) {
            updateFormSections();
        }
    </script>
</body>
</html>