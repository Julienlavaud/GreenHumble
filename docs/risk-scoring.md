# Méthode de score de risque GH-RISK-1.0

Le score mesure la sévérité moyenne des signaux observés, de 0 (signal faible) à 100 (signal élevé). Il ne mesure pas la probabilité d'une infraction et ne remplace pas une analyse juridique.

## Grille par allégation

| Facteur | Barème | Règle |
| --- | ---: | --- |
| Nature | 8 / 16 / 24 | Signal générique / allégation environnementale / allégation absolue ou de neutralité |
| Précision | 6 / 12 / 20 | Terme court / description plus précise / absolu, garantie ou périmètre total |
| Portée | 8 / 18 | Portée non précisée / gamme, ensemble ou tous les produits |
| Preuve | 0 / 14 | Justificatif substantié / citation du signal trouvée mais justificatif non établi |

Les points par allégation sont la somme des facteurs, plafonnée à 100. Le score final est la moyenne arrondie des points des allégations. La grille et les seuils sont versionnés dans `api/includes/risk_scoring.php`.

## Confiance et limites

La confiance est évaluée séparément (20, 50 ou 80 points de base), selon la présence d'une citation source et l'incertitude du signal; une justification explicite apporte 10 points, plafonnés à 95. La force des preuves indique la part des allégations avec citation retrouvée. Une citation prouve la présence d'une formulation, pas la véracité de son allégation. Une preuve absente diminue la confiance et n'ajoute pas de risque.

Sans allégation exploitable, le score est `null` et le niveau `a_evaluer`; l'absence de signal détecté ne prouve pas l'absence de risque.

## Seuils

- 0–44 : faible
- 45–69 : modéré
- 70–100 : élevé

Les cas étalons dans `tests/fixtures/ai-engine-reference.json` et `tests/risk_scoring_test.php` ancrent le comportement et doivent être revus manuellement lors d'une évolution de la grille.
