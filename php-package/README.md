# 📦 GUIDE DE DÉPLOIEMENT & INSTALLATION - CLIMAT LOCAL
## Compatible : PHP 8.x • MySQL 5.7+ / MariaDB • OVH • IONOS • o2switch • LWS

---

### 1. DÉPLOIEMENT VIA FILEZILLA (UPLOAD FTP)

1. **Connectez-vous à votre espace d'hébergement avec FileZilla** :
   - Hôte : `ftp.votre-domaine.com` (fourni par votre hébergeur OVH, IONOS, o2switch...)
   - Identifiant & Mot de passe FTP
   - Port : 21 (ou 22 pour SFTP)

2. **Accédez au répertoire racine web** :
   - Chez **OVH** : `/www/`
   - Chez **o2switch** / cPanel : `/public_html/`
   - Chez **IONOS** : Répertoire racine assigné à votre nom de domaine
   - Chez **LWS** : `/htdocs/`

3. **Téléversez les fichiers du pack** :
   - Glissez l'ensemble des fichiers (`install.php`, `config.php`, `schema.sql`, `api.php`, `.htaccess`, etc.) ainsi que le build frontend.

---

### 2. LANCEMENT DU MODULE D'INSTALLATION (TYPE DOLIBARR / WORDPRESS)

1. Ouvrez votre navigateur et naviguez sur :
   `https://votre-domaine.com/install.php`

2. **Étape 1 : Prérequis**
   L'assistant vérifie automatiquement votre version de PHP (8.0+), les extensions (`pdo_mysql`, `curl`, `json`, `openssl`, `mbstring`) et les droits d'écriture sur les répertoires.

3. **Étape 2 : Base de Données MySQL**
   Saisissez vos identifiants MySQL fournis par l'hébergeur.
   Cliquez sur **« Tester la connexion MySQL en direct »**.
   L'assistant crée la base et importe automatiquement l'intégralité du schéma `schema.sql` (tables `cl_audits`, `cl_claims`, `cl_settings`, etc.).

4. **Étape 3 : Configuration Gemini AI**
   Renseignez votre clé API Google Gemini (gratuite sur [Google AI Studio](https://aistudio.google.com)).
   Cliquez sur **« Tester la clé Gemini en direct »** pour valider la latence et les quotas.

5. **Étape 4 : Finalisation**
   Le fichier `.env` est automatiquement généré avec des tokens sécurisés.
   Le fichier de verrou `config/install.lock` est posé pour empêcher toute réinstallation non autorisée.

---

### 3. SÉCURITÉ & CONTINUITÉ DE SERVICE (ZÉRO PAGE BLANCHE)

- **Gestionnaire d'erreurs global** : Si une exception survient, une page d'incident stylée est affichée sans jamais laisser de page blanche.
- **Moteur Fallback Gemini** : Si l'API Google Gemini est temporairement injoignable ou en dépassement de quota (429), le moteur local réglementaire prend immédiatement le relais pour délivrer l'audit sans interruption.
- **Sauvegardes** : Rendez-vous sur `https://votre-domaine.com/backup.php` pour générer un dump SQL complet en un clic.
- **Diagnostic** : Consultez `https://votre-domaine.com/diagnostic.php` pour vérifier la santé de vos connexions MySQL et Gemini.
