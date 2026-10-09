# Installation GreenHumble sur OVH Pro — sans SSH

## Périmètre strict

- Application : `https://doo.climatlocal.com`
- Racine cible : le dossier OVH `doo` affecté à ce sous-domaine.
- Base cible : la base MySQL dédiée à GreenHumble.
- Site principal : `www.climatlocal.com`, ses fichiers, sa racine et sa base sont hors périmètre. Ne pas téléverser ce projet dans sa racine et ne pas modifier ses fichiers ou sa base.

## Avant de transférer

1. Dans OVHcloud > Hébergements, vérifier que `doo.climatlocal.com` pointe bien vers le dossier `doo`, et que `www.climatlocal.com` pointe vers un dossier différent.
2. Vérifier que le certificat SSL de `doo.climatlocal.com` est actif.
3. Dans phpMyAdmin, confirmer le nom exact de la base GreenHumble et faire un export SQL de sauvegarde. Ne jamais sélectionner la base du site principal.
4. Dans FileZilla, ouvrir uniquement le dossier `doo`. Télécharger une copie locale de son contenu actuel avant toute modification.
5. Décompresser la version contrôlée de la branche `feat/tpe-commercial-engine-v0-1` en local. Téléverser le contenu du projet dans le dossier `doo` (pas un dossier parent, pas la racine du site principal). Conserver le fichier `.htaccess` du projet dans `doo`.
6. Vérifier dans le manager OVH que le sous-domaine utilise PHP 8.2. L'extension `pdo_mysql` doit être disponible.

## Assistant d'installation

1. Ouvrir uniquement `https://doo.climatlocal.com/api/install/`.
2. Saisir l'hôte MySQL, le nom de la base dédiée, l'utilisateur et son mot de passe depuis OVH. Ne pas copier ces secrets dans un ticket, un dépôt Git ou un message.
3. Choisir un identifiant administrateur unique et un mot de passe robuste d'au moins 12 caractères. Conserver le mot de passe dans un gestionnaire sécurisé.
4. Cliquer sur « Vérifier et installer ». L'assistant teste la connexion, crée les tables manquantes et ajoute des colonnes. Le schéma livré est additif : il ne contient pas de suppression de tables ou de données.
5. À la fin, l'assistant doit rediriger vers `https://doo.climatlocal.com/api/login.php?installed=1`. Si une erreur survient, ne pas relancer en boucle et ne pas supprimer de tables : vérifier d'abord les permissions et la configuration avec une sauvegarde à portée de main.

## Vérifications après installation

- [ ] `https://doo.climatlocal.com/api/install/` est verrouillé après l'installation.
- [ ] `https://doo.climatlocal.com/api/login.php` accepte l'accès administrateur créé et redirige vers le tableau de bord admin.
- [ ] Un accès collaborateur créé depuis l'administration peut se connecter et arrive dans le cockpit collaborateur, sans accès admin.
- [ ] `https://doo.climatlocal.com/api/index.php` s'affiche en HTTPS.
- [ ] Un scan de test complet produit des résultats marqués comme incomplets s'il manque des données nécessaires ; aucun score ou constat juridique ne doit être inventé.
- [ ] Après un scan valide, l'audit apparaît dans « Derniers audits » du cockpit.
- [ ] Une demande de diagnostic envoyée volontairement apparaît dans « Demandes de diagnostic public ».
- [ ] La case de prospection marketing est décochée par défaut et le contact est enregistré même sans consentement marketing.
- [ ] `https://www.climatlocal.com` fonctionne comme avant ; aucun fichier ni réglage de ce site n'a été modifié.

## En cas de problème

- Ne pas exposer ni envoyer le mot de passe MySQL ou le mot de passe administrateur.
- Ne pas désactiver la protection HTTPS.
- Ne pas modifier la racine ou la base de `www.climatlocal.com`.
- Si l'installation échoue après la création des tables, le schéma est conçu pour être relançable et non destructif, mais vérifier l'état de la base avant toute nouvelle tentative.
- Une installation réussie ne remplace pas un test réel de connexion, de scan, de sauvegarde et de visibilité dans le cockpit.

## URLs GreenHumble

- Scan public : `https://doo.climatlocal.com/api/index.php`
- Connexion : `https://doo.climatlocal.com/api/login.php`
- Administration : `https://doo.climatlocal.com/api/admin/index.php`
- Cockpit collaborateur : `https://doo.climatlocal.com/api/collaborateur/index.php`
