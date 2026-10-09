# Intégration du moteur commercial — contrat v0.1

## État

Le moteur de qualification (\`api/includes/commercial_engine.php\`) et le préparateur de dossier (\`api/includes/commercial_dossier.php\`) sont des fonctions PHP isolées. Ils ne sont pas encore branchés aux routes d'audit, à la base de données, ni à l'interface.

## Parcours cible

1. Recevoir une URL et appliquer les protections SSRF existantes avant toute récupération.
2. Analyser uniquement le contenu effectivement récupéré. En cas d'échec de récupération ou d'analyse IA incomplète, ne pas inventer de signaux.
3. Construire un registre de preuves. Chaque élément doit avoir un identifiant stable, une URL source, un titre ou extrait, et un statut de vérification.
4. Transformer les signaux en entrées du moteur. Un signal n'est admissible que si \`verified === true\` et que \`evidence_id\` référence un élément existant.
5. Appeler \`gh_recommend_services($lead)\`.
6. Appeler \`gh_build_sales_dossier($lead, $qualification)\` uniquement pour une qualification exploitable.
7. Afficher la recommandation, les raisons et les sources dans l'interface. L'utilisateur peut corriger ou rejeter le résultat.
8. Avant tout enregistrement, définir le schéma et la politique de conservation. Avant tout contact, appliquer les règles de prospection et exiger une validation explicite.

## Contrat minimal d'entrée

- \`company_name\`: nom de l'entreprise si confirmé.
- \`website\`: URL de l'entreprise, validée par le parcours existant.
- \`company_size\`: valeur contrôlée (`tpe`, `micro`, `small`, `unknown`).
- \`evidence\`: tableau indexé par identifiant de preuve; chaque entrée peut inclure \`url\`, \`title\`, \`excerpt\`.
- \`signals\`: liste d'objets avec \`type\`, \`verified\` et \`evidence_id\`.

Les types de signaux doivent venir d'une liste contrôlée : \`environmental_claim\`, \`rse_request\`, \`customer_requirements\`, \`carbon_request\`, \`carbon_data\`, \`general_environmental_need\`.

## Contrôles bloquants avant intégration

- Ne pas appeler le moteur avec un résultat IA incomplet comme s'il était vérifié.
- Ne pas confondre présence d'une allégation et preuve qu'elle est fausse ou trompeuse.
- Vérifier que les extraits sont fidèles aux sources et limiter leur longueur.
- Ajouter des tests d'intégration couvrant l'échec du scraping, l'absence de clé IA, le JSON malformé, les preuves manquantes et les entreprises sans signal.
- Corriger et tester l'authentification existante avant toute exposition publique. Ne pas contourner les contrôles de connexion actuels.
- Ne pas écrire en base ni envoyer d'e-mail tant que les autorisations, le schéma, la conservation et les règles de prospection ne sont pas définis.
- Ne pas fusionner ou déployer le prototype avant revue du code et validation des tests.

## Prochaine étape technique

Faire une revue ciblée de la route d'audit et du format de sortie actuel, puis proposer un adaptateur isolé et testé. Ne modifier ni les fichiers de connexion, ni la configuration, ni le schéma SQL dans la même étape.
