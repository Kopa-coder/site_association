<div class="sidebar">
    <div class="sidebar-header">
        <h2>⚙️ Administration</h2>
        <p><?php echo htmlspecialchars(getAdminName()); ?></p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>📊 Dashboard</a></li>
        <li><a href="gestion_livres.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_livres.php' ? 'class="active"' : ''; ?>>📚 Gestion Livres</a></li>
        <li><a href="gestion_dons.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_dons.php' ? 'class="active"' : ''; ?>>💝 Gestion Dons</a></li>
        <li><a href="gestion_commandes.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_commandes.php' ? 'class="active"' : ''; ?>>📦 Commandes</a></li>
        <li><a href="gestion_contenu.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_contenu.php' ? 'class="active"' : ''; ?>>✏️ Contenu Site</a></li>
        <li><a href="gestion_slogans.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_slogans.php' ? 'class="active"' : ''; ?>>💬 Slogans</a></li>
        <li><a href="gestion_temoignages.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_temoignages.php' ? 'class="active"' : ''; ?>>⭐ Témoignages</a></li>
        <li><a href="gestion_statistiques.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_statistiques.php' ? 'class="active"' : ''; ?>>📈 Statistiques</a></li>
        <li><a href="gestion_fondateurs.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_fondateurs.php' ? 'class="active"' : ''; ?>>👥 Fondateurs</a></li>
        <li><a href="gestion_missions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_missions.php' ? 'class="active"' : ''; ?>>🎯 Missions</a></li>
        <li><a href="gestion_contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_contact.php' ? 'class="active"' : ''; ?>>📞 Contact</a></li>
        <li><a href="parametres.php" <?php echo basename($_SERVER['PHP_SELF']) == 'parametres.php' ? 'class="active"' : ''; ?>>⚙️ Paramètres</a></li>
        <li><a href="logout.php">🚪 Déconnexion</a></li>
    </ul>
</div>