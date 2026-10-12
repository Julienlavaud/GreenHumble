# Priorité du moteur commercial IA — GreenHumble / TPE

## But
Recommander une prestation adaptée à un besoin constaté, sans transformer une hypothèse IA en accusation ni contacter automatiquement un prospect.

## Logique de recommandation
- **Revue des allégations environnementales** : seulement si une allégation publique précise et sa source sont disponibles.
- **Pré-diagnostic carbone et environnement** : si un besoin carbone est explicitement exprimé ou si des données carbone existent.
- **Structuration RSE pragmatique** : si des exigences clients/RSE ou une démarche à structurer sont documentées.
- **Diagnostic environnemental initial** : point d'entrée lorsque le besoin est général, la maturité inconnue ou les preuves insuffisantes.

Plusieurs prestations peuvent être proposées si les signaux le justifient. Le score est un indicateur de priorité, pas une probabilité de conversion.

## Entrée du moteur
- `company_name`, `website`, `company_size`
- `evidence` : dictionnaire d'identifiants vers des sources réellement collectées
- `signals` : objets `{type, verified, evidence_id}`

Types de signaux : `environmental_claim`, `carbon_request`, `carbon_data`, `rse_request`, `customer_requirements`, `general_environmental_need`.

Un signal ne compte que si `verified=true`, si son `evidence_id` existe et si la preuve correspondante est présente. Une supposition du modèle ne constitue pas une preuve.

## Sortie
Statut de qualification, recommandations triées, scores indicatifs, raisons, prochaine étape. Le brouillon d'e-mail et les prix restent vides dans ce prototype ; l'envoi automatique est désactivé.

## Parcours commercial cible
1. Ajouter une entreprise et son site.
2. Collecter les informations publiques utiles en conservant URL et extrait.
3. Distinguer faits observés, interprétations et éléments à vérifier.
4. Faire valider les preuves et les signaux.
5. Recommander une prestation et préparer un dossier commercial.
6. Générer un message personnalisé, non accusatoire, puis le faire valider avant envoi.
7. Suivre réponse, rendez-vous, proposition, mission gagnée/perdue et motif.

## Indicateurs
Prospects examinés, preuves suffisantes, recommandations modifiées par un humain, messages validés/envoyés, réponses, rendez-vous, propositions, missions gagnées et chiffre d'affaires.

## Garde-fous
- Pas d'accusation de greenwashing sans revue contradictoire.
- Ne pas inventer prix, certifications, qualifications, résultats ou références clients.
- Tarifs configurables, provenant du catalogue approuvé.
- Prospection conforme aux règles applicables et aux demandes d'opposition.
- Validation humaine avant contact et avant toute conclusion sensible.
- Prototype non branché à la base ni à l'interface ; tests et intégration nécessaires avant production.
