# Intégration du moteur commercial — contrat v0.1

## État

Le moteur de qualification (`api/includes/commercial_engine.php`), le préparateur de dossier (`api/includes/commercial_dossier.php`) et l'adaptateur défensif (`api/includes/commercial_adapter.php`) sont des fonctions PHP isolées. Ils ne sont pas encore branchés aux routes d'audit, à la base de données, ni à l'interface.

L'adaptateur bloque la préparation si l'audit n'est pas explicitement complet et si les signaux n'ont pas été revus. Il n'effectue aucun scraping, accès réseau, envoi de message ou enregistrement. Le booléen de revue doit être défini par un processus de validation réel côté application ; il ne doit jamais être considéré comme une autorisation implicite venant de l'IA.

## Compatibilité observée avec `api/audit.php`

La revue du code existant a identifié deux blocages à résoudre avant tout branchement :

1. Le chemin de succès de `validateIAResponse()` normalise `riskScore`, mais ne garantit pas d'ajouter `analysisComplete: true`. L'adaptateur strict peut donc rejeter une réponse que la route considère comme complète. Ne pas affaiblir l'adaptateur : normaliser explicitement l'état de complétude après validation stricte et ajouter des tests.
2. Quand la réponse IA ne fournit pas de kit commercial, `validateIAResponse()` construit actuellement un kit par défaut avec une offre et un prix estimatif. Ce contenu n'est pas une donnée validée du catalogue GreenHumble. Il faut supprimer ce fallback commercial fabriqué ou le remplacer par `null`, et ne présenter que des offres/prix provenant d'un catalogue approuvé.

Autre limite : la route produit des données de secteur, de salariés et de chiffre d'affaires avec des valeurs de repli heuristiques. Elles ne doivent pas être considérées comme des données vérifiées pour la qualification commerciale.

## Parcours cible

1. Recevoir une URL et appliquer les protections SSRF existantes avant toute récupération.
2. Analyser uniquement le contenu effectivement récupéré. En cas d'échec de récupération ou d'analyse IA incomplète, ne pas inventer de signaux.
3. Construire un registre de preuves. Chaque élément doit avoir un identifiant stable, une URL source, un titre ou extrait, et un statut de vérification.
4. Transformer les signaux en entrées du moteur. Un signal n'est admissible que si `verified === true` et que `evidence_id` référence un élément existant.
5. Après revue humaine effective, appeler `gh_prepare_commercial_result($audit, $lead, true)`.
6. Afficher la recommandation, les raisons et les sources dans l'interface. L'utilisateur peut corriger ou rejeter le résultat.
7. Avant tout enregistrement, définir le schéma et la politique de conservation. Avant tout contact, appliquer les règles de prospection et exiger une validation explicite.

## Contrat minimal d'entrée

- `audit.analysisComplete`: doit être strictement `true`.
- `audit.riskScore`: score numérique produit par le parcours d'audit validé.
- `company_name`: nom de l'entreprise si confirmé.
- `website`: URL de l'entreprise, validée par le parcours existant.
- `company_size`: valeur contrôlée (`tpe`, `micro`, `small`, `unknown`).
- `evidence`: tableau indexé par identifiant de preuve; chaque entrée peut inclure `url`, `title`, `excerpt`.
- `signals`: liste d'objets avec `type`, `verified` et `evidence_id`.

Les types de signaux doivent venir d'une liste contrôlée : `environmental_claim`, `rse_request`, `customer_requirements`, `carbon_request`, `carbon_data`, `general_environmental_need`.

## Contrôles bloquants avant intégration

- Corriger et tester la normalisation du statut complet et supprimer les offres/prix commerciaux de repli non validés.
- Ne pas appeler le moteur avec un résultat IA incomplet comme s'il était vérifié.
- Ne pas confondre présence d'une allégation et preuve qu'elle est fausse ou trompeuse.
- Vérifier que les extraits sont fidèles aux sources et limiter leur longueur.
- Ajouter des tests d'intégration couvrant l'échec du scraping, l'absence de clé IA, le JSON malformé, les preuves manquantes et les entreprises sans signal.
- Corriger et tester l'authentification existante avant toute exposition publique. Ne pas contourner les contrôles de connexion actuels.
- Ne pas écrire en base ni envoyer d'e-mail tant que les autorisations, le schéma, la conservation et les règles de prospection ne sont pas définis.
- Ne pas fusionner ou déployer le prototype avant revue du code et validation des tests.

## Prochaine étape technique

Isoler la normalisation de la réponse IA dans une fonction testable, corriger ces deux problèmes avec tests unitaires, puis seulement ensuite préparer un appel optionnel depuis la route d'audit. Garder le moteur commercial hors de la persistance et de l'envoi jusqu'à ce que l'authentification soit revue.
