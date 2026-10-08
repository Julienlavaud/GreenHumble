/**
 * Référentiel Mots Bannis EmpCO & Directive (UE) 2024/825 des allégations environnementales interdites
 * Sources officielles :
 * - Directive EmpCO / ECGT (UE) 2024/825 du 28 février 2024 modifiant 2005/29/CE et 2011/83/UE (JO L_202400825)
 * - Référentiel EmpCO des allégations environnementales trompeuses et interdites
 * - Décret français n° 2022-539 du 13 avril 2022 (Loi Climat et Résilience, art. L229-68)
 * - Code de la consommation (Art. L121-2 pratiques commerciales trompeuses)
 * - RE2020 & Norme NF EN 15804+A2 (FDES / Base INIES)
 */

import { ClimatLocalServiceKey, LocalCarbonProject } from '../types.js';

export interface BannedWordDef {
  term: string;
  category: 'neutralite' | 'generique' | 'produit_acv' | 'materiau_fdes' | 'label_non_certifie' | 'futur_sans_tiers';
  severity: 'interdit_strict' | 'haut_risque' | 'trompeur_sans_preuve';
  legalRiskSummary: string;
  lawReference: string;
  directive2024Article: string;
  maxSanction: string;
  climatLocalSolution: ClimatLocalServiceKey;
  climatLocalOfferTitle: string;
  climatLocalOfferPitch: string;
}

export const DIRECTIVE_2024_825_POINTS = [
  {
    code: 'Point 4 septies',
    title: 'Interdiction des allégations de neutralité fondées sur la compensation',
    text: 'Formuler une allégation, fondée sur la compensation des émissions de gaz à effet de serre, selon laquelle un produit a un impact neutre, réduit ou positif sur l’environnement.',
    impact: 'Interdit formellement d’affirmer qu’un produit ou service est "neutre en carbone" ou "compensé" grâce à des crédits carbone génériques.',
  },
  {
    code: 'Point 4 quater',
    title: 'Interdiction des allégations environnementales génériques',
    text: 'Formuler une allégation environnementale générique pour laquelle le professionnel n’est pas en mesure de démontrer une performance environnementale reconnue.',
    impact: 'Bannit des termes vagues comme "éco-responsable", "vert", "écologique", "ami du climat", "durable".',
  },
  {
    code: 'Point 4 ter',
    title: 'Interdiction des labels de durabilité auto-proclamés',
    text: 'Afficher un label de durabilité qui n’est pas fondé sur un système de certification officiel ou qui n’a pas été établi par des autorités publiques.',
    impact: 'Sanctionne les faux logos verts, auto-déclarations et chartes privées non auditées par un tiers indépendant.',
  },
  {
    code: 'Point 4 bis',
    title: 'Allégations sur les engagements futurs sans tiers indépendant',
    text: 'Formuler une allégation environnementale sur des performances futures sans engagements clairs, objectifs, publics et vérifiés régulièrement par un expert indépendant.',
    impact: 'Exige une trajectoire SBTi ou SNBC vérifiée par un tiers agréé.',
  },
];

export const EMPCO_BANNED_WORDS: BannedWordDef[] = [
  // 1. Allégations de neutralité carbone fondées sur la compensation (Point 4 septies Directive 2024/825)
  {
    term: 'neutre en carbone',
    category: 'neutralite',
    severity: 'interdit_strict',
    legalRiskSummary: 'Interdit en toutes circonstances dès lors qu’il repose sur la compensation des émissions. La loi impose de mesurer et réduire d’abord, sans s’attribuer une neutralité fictive.',
    lawReference: 'Directive (UE) 2024/825 (Annexe I, point 4 septies) & Décret n° 2022-539 (Art. L229-68 Code Env.)',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 septies (Pratique commerciale trompeuse en toutes circonstances)',
    maxSanction: 'Jusqu’à 100 000 € d’amende DGCCRF et 4% du chiffre d’affaires annuel en sanction européenne.',
    climatLocalSolution: 'credits_carbone_locaux',
    climatLocalOfferTitle: 'Bilan Carbone® + Contribution Carbone Locale certifiée (climatlocal.com)',
    climatLocalOfferPitch: 'Remplacez la fausse "neutralité" par une contribution transparente à des projets de proximité vérifiés (Label Bas-Carbone, haies bocagères, agriculture régénératrice en France) avec visites terrain pour vos salariés et clients.',
  },
  {
    term: 'neutralité carbone',
    category: 'neutralite',
    severity: 'interdit_strict',
    legalRiskSummary: 'Concept réservé à l’équilibre planétaire global. Illégal pour vanter les mérites d’un produit, d’un service ou d’une entreprise isolée.',
    lawReference: 'Directive (UE) 2024/825 & Recommandations ADEME / ARPP',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 septies',
    maxSanction: '100 000 € d’amende et publication obligatoire de rectificatif.',
    climatLocalSolution: 'bilan_carbone',
    climatLocalOfferTitle: 'Bilan Carbone® Scopes 1-2-3 & Trajectoire de Réduction SBTi',
    climatLocalOfferPitch: 'Mesurez scientifiquement votre empreinte avec Climat Local (subventionné à 50% par le Diag Décarbon’Action Bpifrance/ADEME) et engagez un plan de baisse mesurable.',
  },
  {
    term: 'zéro émission',
    category: 'neutralite',
    severity: 'interdit_strict',
    legalRiskSummary: 'Physiquement et scientifiquement impossible pour tout produit ou activité économique sur l’ensemble du cycle de vie.',
    lawReference: 'Directive (UE) 2024/825 & Article L121-2 Code de la consommation',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Jusqu’à 2 ans d’emprisonnement et 300 000 € d’amende pour pratique commerciale trompeuse.',
    climatLocalSolution: 'acv',
    climatLocalOfferTitle: 'Analyse du Cycle de Vie (ACV) normée ISO 14040/44',
    climatLocalOfferPitch: 'Quantifiez objectivement les 16 impacts réels de vos produits pour communiquer sur des réductions prouvées sans mensonge.',
  },
  {
    term: 'compensé carbone',
    category: 'neutralite',
    severity: 'interdit_strict',
    legalRiskSummary: 'L’achat de crédits carbone internationaux ("arbres au bout du monde") ne permet plus de s’affranchir de son impact.',
    lawReference: 'Directive (UE) 2024/825 (Point 4 septies) & Décret 2022-539',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 septies',
    maxSanction: 'Mise en demeure immédiate et pénalités DGCCRF.',
    climatLocalSolution: 'credits_carbone_locaux',
    climatLocalOfferTitle: 'Programme de Financement Écologique Local (climatlocal.com)',
    climatLocalOfferPitch: 'Participez au financement direct d’agriculteurs et projets forestiers locaux certifiés Label Bas-Carbone dans votre territoire, avec un vrai retour d’image RSE.',
  },
  {
    term: 'net zéro',
    category: 'neutralite',
    severity: 'interdit_strict',
    legalRiskSummary: 'Ne peut être employé sans une validation formelle de trajectoire selon le standard Net-Zero du SBTi.',
    lawReference: 'Directive (UE) 2024/825 (Point 4 bis) & ADEME',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 bis (Engagements futurs)',
    maxSanction: 'Condamnation pour allégation d’engagement non vérifiée par un tiers.',
    climatLocalSolution: 'bilan_carbone',
    climatLocalOfferTitle: 'Stratégie Décarbonation & Bilan Carbone® Certifié',
    climatLocalOfferPitch: 'Accompagnement de vos équipes par Climat Local pour fixer des objectifs de baisse validés et auditables.',
  },

  // 2. Allégations génériques floues (Point 4 quater Directive 2024/825)
  {
    term: 'éco-responsable',
    category: 'generique',
    severity: 'haut_risque',
    legalRiskSummary: 'Terme générique flou explicitement visé par la Directive 2024/825. Interdit sans Écolabel officiel de l’UE ou certification publique.',
    lawReference: 'Directive (UE) 2024/825 (Point 4 quater) & Loi Climat et Résilience',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater (Allégations génériques)',
    maxSanction: 'Sanction pécuniaire jusqu’à 4% du CA et obligation de retrait.',
    climatLocalSolution: 'strategie_rse',
    climatLocalOfferTitle: 'Stratégie RSE Structurée & Reporting CSRD (Double Matérialité)',
    climatLocalOfferPitch: 'Passez du slogan "éco-responsable" à une politique RSE robuste, prouvée par des indicateurs ESG conformes aux exigences européennes.',
  },
  {
    term: 'écologique',
    category: 'generique',
    severity: 'haut_risque',
    legalRiskSummary: 'Allégation générale interdite pour un produit standard ne disposant pas d’une performance environnementale d’excellence reconnue.',
    lawReference: 'Directive (UE) 2024/825 (Point 4 quater)',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Amende pour tromperie commerciale.',
    climatLocalSolution: 'strategie_rse',
    climatLocalOfferTitle: 'Accompagnement Labellisation RSE Reconnue (Engagé RSE / Lucie / B Corp)',
    climatLocalOfferPitch: 'Audit et préparation de votre entreprise pour obtenir un label officiel reconnu par les pouvoirs publics.',
  },
  {
    term: 'vert',
    category: 'generique',
    severity: 'haut_risque',
    legalRiskSummary: 'Qualificatif générique proscrit s’il n’est pas assorti de spécifications techniques précises.',
    lawReference: 'Directive (UE) 2024/825 & Code de la consommation',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Injonction sous astreinte de la DGCCRF.',
    climatLocalSolution: 'credits_carbone_locaux',
    climatLocalOfferTitle: 'Mécénat Écologique & Projets de Transition Territoriale (climatlocal.com)',
    climatLocalOfferPitch: 'Investissez dans de vraies actions tangibles sur votre territoire : plantation de haies et soutien aux éleveurs locaux de votre département.',
  },
  {
    term: 'bon pour la planète',
    category: 'generique',
    severity: 'interdit_strict',
    legalRiskSummary: 'Allégation abusivement valorisante interdite. Toute production industrielle consomme des ressources et génère un impact.',
    lawReference: 'Directive (UE) 2024/825 & Recommandation ARPP',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Retrait immédiat ordonné et risque majeur de bad buzz.',
    climatLocalSolution: 'strategie_rse',
    climatLocalOfferTitle: 'Charte de Communication Responsable & Audit Anti-Greenwashing',
    climatLocalOfferPitch: 'Formation de votre direction et de votre agence de communication pour sécuriser tous vos supports de vente.',
  },

  // 3. Allégations Produits non étayées scientifiquement (ACV ISO 14040/44)
  {
    term: 'sans impact',
    category: 'produit_acv',
    severity: 'interdit_strict',
    legalRiskSummary: 'Aucun produit manufacturé n’a un impact nul. Sanctionné systématiquement par la répression des fraudes.',
    lawReference: 'Code de la consommation art. L121-2 & Directive 2024/825',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Sanction pénale pouvant atteindre 80% des dépenses engagées pour la promotion.',
    climatLocalSolution: 'acv',
    climatLocalOfferTitle: 'ACV Produit (Analyse du Cycle de Vie normée ISO 14040/44)',
    climatLocalOfferPitch: 'Modélisation rigoureuse des impacts (climat, eau, ressources) pour valoriser des gains réels et vérifiables.',
  },
  {
    term: '100% naturel',
    category: 'produit_acv',
    severity: 'trompeur_sans_preuve',
    legalRiskSummary: 'Créer une confusion entre origine naturelle et absence d’impact environnemental est qualifié de pratique trompeuse.',
    lawReference: 'Directive (UE) 2024/825 & CNC',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Sanctions administratives pour tromperie sur les qualités du produit.',
    climatLocalSolution: 'acv',
    climatLocalOfferTitle: 'Éco-conception & Bilan Environnemental Sourcing',
    climatLocalOfferPitch: 'Diagnostic complet de la chaîne d’approvisionnement et valorisation des filières locales éco-conçues.',
  },

  // 4. Allégations Bâtiment & Matériaux (Exigeant FDES / EPD - RE2020)
  {
    term: 'matériau bas carbone',
    category: 'materiau_fdes',
    severity: 'trompeur_sans_preuve',
    legalRiskSummary: 'Dans la construction (RE2020), aucune allégation "bas carbone" n’est légale sans FDES vérifiée par un tiers sur la base INIES.',
    lawReference: 'Réglementation RE2020 & Norme NF EN 15804+A2 (Code de la construction)',
    directive2024Article: 'Réglementation Nationale RE2020 & Directive (UE) 2024/825',
    maxSanction: 'Exclusion immédiate des chantiers RE2020, rejet en appel d’offres et sanctions DGCCRF.',
    climatLocalSolution: 'fdes_epd',
    climatLocalOfferTitle: 'Réalisation & Vérification de FDES (Base INIES) & EPD',
    climatLocalOfferPitch: 'Conception de vos FDES conformes NF EN 15804+A2 pour référencer vos matériaux bois, isolants ou menuiseries et remporter les marchés RE2020.',
  },
  {
    term: 'isolation écologique',
    category: 'materiau_fdes',
    severity: 'trompeur_sans_preuve',
    legalRiskSummary: 'Les isolants doivent justifier de FDES collectives ou individuelles vérifiées pour revendiquer un bénéfice environnemental.',
    lawReference: 'Arrêté Déclarations Environnementales & RE2020',
    directive2024Article: 'Réglementation RE2020 & Directive (UE) 2024/825',
    maxSanction: 'Interdiction de vente sous cette dénomination.',
    climatLocalSolution: 'fdes_epd',
    climatLocalOfferTitle: 'FDES Isolants & Éco-matériaux (Base INIES)',
    climatLocalOfferPitch: 'Création de vos FDES vérifiées par tierce partie pour répondre aux exigences des bureaux d’études thermiques.',
  },
  {
    term: 'menuiserie durable',
    category: 'materiau_fdes',
    severity: 'trompeur_sans_preuve',
    legalRiskSummary: 'Exige une FDES spécifique calculant le carbone biogène stocké et les impacts de fin de vie.',
    lawReference: 'Norme NF EN 15804+A2 & RE2020',
    directive2024Article: 'Réglementation RE2020 & Directive (UE) 2024/825',
    maxSanction: 'Rejet en commission d’appel d’offres public et litige maître d’ouvrage.',
    climatLocalSolution: 'fdes_epd',
    climatLocalOfferTitle: 'FDES Menuiseries Bois / Mixte & EPD Internationales',
    climatLocalOfferPitch: 'Prouvez la performance environnementale de vos menuiseries avec une FDES reconnue sur le marché français.',
  },
  {
    term: 'epd',
    category: 'materiau_fdes',
    severity: 'trompeur_sans_preuve',
    legalRiskSummary: 'L’acronyme EPD ne peut être utilisé sans enregistrement auprès d’un opérateur de programme certifié (ISO 14025).',
    lawReference: 'Norme ISO 14025 (Déclarations environnementales type III)',
    directive2024Article: 'Directive (UE) 2024/825',
    maxSanction: 'Sanction pour usage abusif de certification.',
    climatLocalSolution: 'fdes_epd',
    climatLocalOfferTitle: 'Déclarations Environnementales EPD (Marchés Export / Europe)',
    climatLocalOfferPitch: 'Déploiement d’EPD certifiées pour commercialiser vos produits industriels dans toute l’Union Européenne.',
  },

  // 5. Allégations Institutionnelles / Tertiaire / Entreprises de services (Raison d'Être & Sensibilisation)
  {
    term: 'entreprise éco-citoyenne',
    category: 'generique',
    severity: 'haut_risque',
    legalRiskSummary: 'Slogan institutionnel vague non vérifiable. Pour les entreprises de services peu vendeuses de produits, l’engagement doit être formalisé dans les statuts ou via la gouvernance.',
    lawReference: 'Directive (UE) 2024/825 (Point 4 quater) & Loi PACTE',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Injonction DGCCRF pour affirmation environnementale non démontrée.',
    climatLocalSolution: 'raison_d_etre',
    climatLocalOfferTitle: 'Raison d’Être & Société à Mission (Loi PACTE)',
    climatLocalOfferPitch: 'Pour les entreprises de services et B2B peu vendeuses : inscrivez vos engagements écologiques dans le marbre de vos statuts juridiques au lieu de simples déclarations marketing.',
  },
  {
    term: 'engagé pour le climat',
    category: 'futur_sans_tiers',
    severity: 'haut_risque',
    legalRiskSummary: 'Affirmer un engagement climatique sans trajectoire certifiée ni implication vérifiable des collaborateurs est proscrit.',
    lawReference: 'Directive (UE) 2024/825 (Point 4 bis)',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 bis (Engagements futurs sans tiers indépendant)',
    maxSanction: 'Sanction pour allégation prospective non auditée.',
    climatLocalSolution: 'sensibilisation_equipes',
    climatLocalOfferTitle: 'Sensibilisation des Équipes & Ateliers Fresque du Climat (Financé OPCO)',
    climatLocalOfferPitch: 'Embarquez vos consultants, managers et salariés dans des ateliers interactifs certifiés (Fresque du Climat, Ateliers 2tonnes) 100% finançables par votre OPCO.',
  },
  {
    term: 'conseil vert',
    category: 'generique',
    severity: 'haut_risque',
    legalRiskSummary: 'Utiliser la couleur ou l’adjectif "vert" pour qualifier une prestation intellectuelle sans méthodologie formalisée.',
    lawReference: 'Directive (UE) 2024/825 & Code de la consommation',
    directive2024Article: 'Directive (UE) 2024/825 - Point 4 quater',
    maxSanction: 'Mise en demeure pour pratique commerciale trompeuse.',
    climatLocalSolution: 'raison_d_etre',
    climatLocalOfferTitle: 'Accompagnement Société à Mission & Charte Éthique',
    climatLocalOfferPitch: 'Structurez votre proposition de valeur B2B avec des comités de mission et des objectifs mesurables reconnus.',
  },
];

export const SAMPLE_LOCAL_CARBON_PROJECTS: LocalCarbonProject[] = [
  {
    id: 'proj_1',
    title: 'Haies Bocagères & Agroforesterie en Auvergne-Rhône-Alpes',
    location: 'Puy-de-Dôme / Allier (Massif Central)',
    type: 'Agroforesterie & Haies',
    certification: 'Label Bas-Carbone (Ministère)',
    carbonStoredTons: 450,
    pricePerTon: 38,
    description: 'Plantation de 12 km de haies champêtres multi-strates chez 8 agriculteurs locaux. Stockage de carbone certifié, préservation de la ressource en eau et restauration de la biodiversité.',
    partnerFarmers: '8 fermes familiales (polyculture élevage et céréales bio)',
  },
  {
    id: 'proj_2',
    title: 'Transition vers le Non-Labour & Couverts Végétaux Permanents',
    location: 'Plaine de la Limagne / Région Auvergne',
    type: 'Transition Agricole Durable',
    certification: 'Label Bas-Carbone (Ministère)',
    carbonStoredTons: 820,
    pricePerTon: 34,
    description: 'Accompagnement de 5 exploitations pour l’arrêt du labour, l’implantation de couverts végétaux et l’augmentation du taux d’humus dans les sols pour capter le carbone durablement.',
    partnerFarmers: 'Groupement d’agriculteurs de la Limagne',
  },
  {
    id: 'proj_3',
    title: 'Restauration & Diversification Forestière Résiliente',
    location: 'Monts du Forez / Loire',
    type: 'Régénération Forestière',
    certification: 'Label Bas-Carbone (Ministère)',
    carbonStoredTons: 600,
    pricePerTon: 42,
    description: 'Régénération d’anciennes parcelles de résineux touchées par les scolytes par un reboisement diversifié d’essences feuillues locales résistantes au changement climatique.',
    partnerFarmers: 'Groupement forestier local & Communes rurales',
  },
];

// Alias pour compatibilité descendante
export const ECOCLAIM_BANNED_WORDS = EMPCO_BANNED_WORDS;

/**
 * Returns the official European / French legal text reference URL corresponding to a law article name.
 */
export function getLawArticleLink(lawArticle?: string): string {
  if (!lawArticle) return 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825';
  const lower = lawArticle.toLowerCase();
  if (lower.includes('2024/825') || lower.includes('empco') || lower.includes('directive')) {
    return 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825';
  }
  if (lower.includes('121-2') || lower.includes('132-2') || lower.includes('consommation') || lower.includes('dgccrf')) {
    return 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000049303668';
  }
  if (lower.includes('229-68') || lower.includes('climat') || lower.includes('539')) {
    return 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000045570611';
  }
  if (lower.includes('com(2023)') || lower.includes('green claims')) {
    return 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=COM:2023:166:FIN';
  }
  if (lower.includes('re2020') || lower.includes('inies') || lower.includes('fdes') || lower.includes('epd')) {
    return 'https://base-inies.fr/consultation/tableau-de-bord';
  }
  return 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825';
}
