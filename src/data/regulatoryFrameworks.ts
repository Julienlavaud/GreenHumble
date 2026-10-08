import { CustomLegalText, ApplicableRegulationEvaluation } from '../types.js';

export interface RegulatoryFrameworkDefinition {
  id: string;
  title: string;
  reference: string;
  jurisdiction: 'europe' | 'france' | 'international' | 'sectoriel';
  family: string;
  sanction: string;
  description: string;
  relevantSectors: string[]; // Sectors particularly concerned or 'all'
  defaultApplicability: 'obligatoire_direct' | 'echeance_2026' | 'recommande_marches' | 'non_assujetti';
  defaultNote: string;
}

export const OFFICIAL_REGULATORY_FRAMEWORKS: RegulatoryFrameworkDefinition[] = [
  // ==========================================
  // 🇪🇺 I. TEXTES EUROPÉENS (UE)
  // ==========================================
  // A. Reporting & Transparence
  {
    id: 'ue_csrd',
    title: 'CSRD — Corporate Sustainability Reporting Directive',
    reference: 'Directive (UE) 2022/2464 + Omnibus 2026/470',
    jurisdiction: 'europe',
    family: 'Reporting & Transparence',
    sanction: 'Sanctions financières jusqu’à 75 000 € et mise en cause de la responsabilité des dirigeants',
    description: 'Obligation de reporting de durabilité extra-financière standardisé selon les normes ESRS avec audit par un tiers indépendant.',
    relevantSectors: ['all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Assujettissement direct selon seuils effectifs/CA ou indirect via les exigences de vos donneurs d’ordres grands comptes (Scope 3).',
  },
  {
    id: 'ue_esrs',
    title: 'ESRS — European Sustainability Reporting Standards',
    reference: 'Règlements délégués 2023–2026 (EFRAG)',
    jurisdiction: 'europe',
    family: 'Reporting & Transparence',
    sanction: 'Non-conformité du rapport de gestion et rejet de la déclaration CSRD',
    description: 'Normes sectorielles et trans-sectorielles imposant la double matérialité (impacts et risques financiers).',
    relevantSectors: ['all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Cadre méthodologique obligatoire pour structurer les données ESG et climat de l’entreprise.',
  },
  {
    id: 'ue_taxonomie',
    title: 'Taxonomie européenne des activités durables',
    reference: 'Règlement (UE) 2020/852',
    jurisdiction: 'europe',
    family: 'Reporting & Transparence',
    sanction: 'Disqualification des financements verts bancaires et pénalités de reporting',
    description: 'Classification des activités économiques contribuant substantiellement aux objectifs climatiques (critères DNSH).',
    relevantSectors: ['BTP', 'Industrie', 'Énergie', 'Transport', 'all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Indispensable pour valoriser les investissements verts auprès des banques partenaires et investisseurs.',
  },
  {
    id: 'ue_sfdr',
    title: 'SFDR — Sustainable Finance Disclosure Regulation',
    reference: 'Règlement (UE) 2019/2088',
    jurisdiction: 'europe',
    family: 'Reporting & Transparence',
    sanction: 'Sanctions AMF et déréférencement des fonds d’investissement',
    description: 'Transparence des acteurs financiers sur les risques de durabilité et impacts négatifs (PAI).',
    relevantSectors: ['Finance', 'Services', 'all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Exigences répercutées par les fonds d’investissement et actionnaires sur l’entreprise.',
  },
  {
    id: 'ue_nfrd',
    title: 'NFRD — Non Financial Reporting Directive (historique)',
    reference: 'Directive 2014/95/UE (remplacée par CSRD)',
    jurisdiction: 'europe',
    family: 'Reporting & Transparence',
    sanction: 'Cadre de transition remplacé par la CSRD',
    description: 'Ancien cadre de reporting extra-financier désormais transité vers la CSRD.',
    relevantSectors: ['all'],
    defaultApplicability: 'non_assujetti',
    defaultNote: 'Cadre historique ; la mise en conformité doit désormais se faire selon la CSRD / ESRS.',
  },

  // B. Carbone & Climat
  {
    id: 'ue_macf',
    title: 'MACF / CBAM — Mécanisme d’Ajustement Carbone aux Frontières',
    reference: 'Règlement (UE) 2023/956',
    jurisdiction: 'europe',
    family: 'Carbone & Climat',
    sanction: 'Amende de 10 € à 50 € par tonne d’émissions non déclarée et blocage en douane',
    description: 'Taxe carbone sur les importations d’acier, aluminium, ciment, engrais, électricité et hydrogène dans l’UE.',
    relevantSectors: ['Industrie', 'BTP', 'Matériaux', 'Métallurgie'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Obligation de reporting trimestriel des émissions directes et indirectes intégrées aux matières importées.',
  },
  {
    id: 'ue_eu_ets',
    title: 'EU ETS — Système d’échange de quotas d’émission',
    reference: 'Directive 2003/87/CE modifiée 2023',
    jurisdiction: 'europe',
    family: 'Carbone & Climat',
    sanction: 'Pénalité de 100 € par tonne de CO2 excédentaire + obligation de restituer les quotas',
    description: 'Marché carbone européen plafonnant les émissions industrielles et énergétiques.',
    relevantSectors: ['Industrie', 'Énergie', 'Chimie'],
    defaultApplicability: 'non_assujetti',
    defaultNote: 'Applicable si installations thermiques industrielles > 20 MW ou répercussion sur les coûts d’achat matières.',
  },
  {
    id: 'ue_crcf',
    title: 'CRCF — Cadre de certification des absorptions carbone',
    reference: 'Règlement (UE) 2024 / Modules 2026',
    jurisdiction: 'europe',
    family: 'Carbone & Climat',
    sanction: 'Interdiction de valoriser des crédits carbone non conformes aux critères de permanence',
    description: 'Harmonisation européenne de la certification des puits de carbone agricoles et forestiers (carbon farming).',
    relevantSectors: ['Agriculture', 'Agroalimentaire', 'Foncier', 'all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Donne pleine légitimité aux projets locaux Climat Local (haies, sols vivants, agroforesterie).',
  },
  {
    id: 'ue_esr',
    title: 'Effort Sharing Regulation (Répartition de l’effort)',
    reference: 'Règlement (UE) 2018/842',
    jurisdiction: 'europe',
    family: 'Carbone & Climat',
    sanction: 'Objectifs contraignants nationaux traduits en plans de transition sectoriels',
    description: 'Objectifs de réduction des émissions pour les secteurs hors quotas (bâtiment, transport, agriculture).',
    relevantSectors: ['Transport', 'Bâtiment', 'Agriculture', 'all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Cadre global contraignant répercuté dans la réglementation nationale française (SNBC).',
  },
  {
    id: 'ue_lulucf',
    title: 'LULUCF — Utilisation des terres, changement d’affectation et foresterie',
    reference: 'Règlement (UE) 2018/841',
    jurisdiction: 'europe',
    family: 'Carbone & Climat',
    sanction: 'Exigences de préservation des stocks carbone du sol et de la biomasse',
    description: 'Intégration du secteur des terres dans le cadre climat européen avec obligation de bilan neutre ou puits net.',
    relevantSectors: ['Bois', 'Agriculture', 'Aménagement', 'BTP'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Concerne l’approvisionnement en matières premières biosourcées et la valorisation du carbone biogène.',
  },

  // C. Produits, ACV, FDES/EPD
  {
    id: 'ue_en15804',
    title: 'EN 15804 — Norme européenne ACV produits de construction',
    reference: 'Norme NF EN 15804+A2',
    jurisdiction: 'europe',
    family: 'Produits, ACV, FDES/EPD',
    sanction: 'Refus de prescription sur chantiers RE2020 et rejet en base INIES',
    description: 'Règles méthodologiques de référence pour les Analyses de Cycle de Vie (ACV) des produits de construction.',
    relevantSectors: ['BTP', 'Matériaux', 'Construction', 'Menuiserie', 'Bois'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Incontournable pour tout fabricant ou distributeur de matériaux de construction souhaitant commercialiser en France/Europe.',
  },
  {
    id: 'ue_cpr',
    title: 'CPR — Construction Products Regulation',
    reference: 'Règlement (UE) 305/2011 révisé 2024',
    jurisdiction: 'europe',
    family: 'Produits, ACV, FDES/EPD',
    sanction: 'Interdiction de mise sur le marché européen et retrait du marquage CE',
    description: 'Conditions harmonisées de commercialisation des produits de construction avec déclaration des performances environnementales.',
    relevantSectors: ['BTP', 'Matériaux', 'Industrie'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Obligation de fournir les données de cycle de vie et durabilité pour l’obtention du marquage de conformité.',
  },
  {
    id: 'ue_espr',
    title: 'ESPR — Ecodesign for Sustainable Products Regulation',
    reference: 'Règlement (UE) 2024 (remplace Ecodesign 2009/125/CE)',
    jurisdiction: 'europe',
    family: 'Produits, ACV, FDES/EPD',
    sanction: 'Interdiction de destruction des invendus et obligation du passeport numérique de produit',
    description: 'Exigences étendues d’éco-conception : durabilité, réparabilité, recyclabilité et passeport numérique (DPP).',
    relevantSectors: ['Textile', 'Électronique', 'Ameublement', 'Chimie', 'all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Impacte la conception des produits dès 2025-2026 avec l’exigence d’un passeport numérique traçable.',
  },
  {
    id: 'ue_ecolabel',
    title: 'Écolabel européen officiel',
    reference: 'Règlement (CE) n° 66/2010',
    jurisdiction: 'europe',
    family: 'Produits, ACV, FDES/EPD',
    sanction: 'Poursuites pour contrefaçon de marque officielle en cas d’usage non certifié',
    description: 'Le seul label environnemental officiel reconnu dans toute l’Union européenne, validé par l’AFNOR en France.',
    relevantSectors: ['Hôtellerie', 'Nettoyage', 'Cosmétique', 'Textile', 'all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Permet de sécuriser légalement l’appellation "éco-responsable" sous la Directive Green Claims.',
  },

  // D. Greenwashing & Consommateurs
  {
    id: 'ue_empco',
    title: 'Directive EmpCo / ECGT — Empowering Consumers for the Green Transition',
    reference: 'Directive (UE) 2024/825 du 28 février 2024',
    jurisdiction: 'europe',
    family: 'Greenwashing & Consommateurs',
    sanction: 'Sanction jusqu’à 4% du CA annuel dans les États membres et amende DGCCRF jusqu’à 100 000 €',
    description: 'Interdiction absolue des allégations génériques ("vert", "éco", "bon pour la planète") et de la neutralité carbone basée sur la compensation.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Applicable immédiatement à toute communication publique, site web, plaquette et packaging.',
  },
  {
    id: 'ue_green_claims',
    title: 'Directive Green Claims (Allégations environnementales explicites)',
    reference: 'Proposition de Directive européenne COM(2023) 166',
    jurisdiction: 'europe',
    family: 'Greenwashing & Consommateurs',
    sanction: 'Exclusion des marchés publics et confiscation des revenus tirés des produits litigieux',
    description: 'Obligation de faire vérifier toute allégation écologique par un vérificateur accrédité indépendant AVANT diffusion.',
    relevantSectors: ['all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Nécessite d’auditer dès aujourd’hui tous vos messages et de fonder chaque argument sur une ACV ou Bilan Carbone.',
  },
  {
    id: 'ue_pratiques_deloyales',
    title: 'Directive pratiques commerciales déloyales',
    reference: 'Directive 2005/29/CE modifiée',
    jurisdiction: 'europe',
    family: 'Greenwashing & Consommateurs',
    sanction: 'Sanction pénale pour délit de tromperie (jusqu’à 2 ans d’emprisonnement et 300 000 € d’amende en France)',
    description: 'Interdiction de toute pratique induisant en erreur le consommateur sur les caractéristiques essentielles du bien.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Base juridique utilisée par les associations de consommateurs et la DGCCRF pour sanctionner le greenwashing.',
  },

  // E. Vigilance & Droits humains
  {
    id: 'ue_cs3d',
    title: 'CS3D — Corporate Sustainability Due Diligence Directive',
    reference: 'Directive (UE) 2024/1760',
    jurisdiction: 'europe',
    family: 'Vigilance & Droits humains',
    sanction: 'Amende jusqu’à 5% du chiffre d’affaires mondial net de l’entreprise',
    description: 'Devoir de vigilance européen sur les impacts environnementaux et droits humains sur l’ensemble de la chaîne de valeur amont et aval.',
    relevantSectors: ['all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Vos clients assujettis exigent des garanties strictes de traçabilité carbone et sociale auprès de leurs fournisseurs.',
  },
  {
    id: 'ue_conflict_minerals',
    title: 'Règlement Minerais de conflit',
    reference: 'Règlement (UE) 2017/821',
    jurisdiction: 'europe',
    family: 'Vigilance & Droits humains',
    sanction: 'Refus de dédouanement et sanctions administratives douanières',
    description: 'Devoir de diligence pour les importateurs d’étain, tantale, tungstène et or originaires de zones de conflit.',
    relevantSectors: ['Électronique', 'Industrie', 'Automobile', 'Horlogerie'],
    defaultApplicability: 'non_assujetti',
    defaultNote: 'Concerne les chaînes d’approvisionnement utilisant des métaux rares et composants électroniques.',
  },

  // F. Déchets & Économie circulaire
  {
    id: 'ue_circ_economy',
    title: 'Paquet européen économie circulaire & Directive déchets',
    reference: 'Directive 2008/98/CE modifiée par la Directive (UE) 2018/851',
    jurisdiction: 'europe',
    family: 'Déchets & Économie circulaire',
    sanction: 'Obligation de mise en conformité des flux de traitement des déchets',
    description: 'Hiérarchie des modes de traitement : prévention, réemploi, recyclage, valorisation matière.',
    relevantSectors: ['Industrie', 'Commerce', 'BTP', 'all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Impose la traçabilité des déchets de production et la priorité au recyclage.',
  },
  {
    id: 'ue_packaging',
    title: 'Règlement Emballages et Déchets d’Emballages (PPWR)',
    reference: 'Règlement (UE) 2024 (révision de la directive 94/62/CE)',
    jurisdiction: 'europe',
    family: 'Déchets & Économie circulaire',
    sanction: 'Interdiction de formats d’emballages plastique à usage unique et pénalités de recyclabilité',
    description: 'Objectifs obligatoires de réduction des emballages, de réemploi et d’incorporation de plastique recyclé d’ici 2030.',
    relevantSectors: ['Agroalimentaire', 'E-commerce', 'Cosmétique', 'Logistique', 'all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Tout emballage devra être recyclable d’ici 2030, nécessitant des revues d’éco-conception.',
  },
  {
    id: 'ue_rohs_weee',
    title: 'Directives RoHS & DEEE',
    reference: 'Directives 2011/65/UE (RoHS) et 2012/19/UE (DEEE)',
    jurisdiction: 'europe',
    family: 'Déchets & Économie circulaire',
    sanction: 'Interdiction de commercialisation et éco-contributions majorées',
    description: 'Limitation des substances dangereuses dans les équipements électriques/électroniques et filière de collecte/recyclage.',
    relevantSectors: ['Électronique', 'Équipementiers', 'Industrie'],
    defaultApplicability: 'non_assujetti',
    defaultNote: 'Applicable aux fabricants et distributeurs d’appareils intégrant des circuits électriques.',
  },

  // G. Eau, air, pollution
  {
    id: 'ue_ied',
    title: 'Directive Émissions Industrielles (IED)',
    reference: 'Directive 2010/75/UE révisée 2024',
    jurisdiction: 'europe',
    family: 'Eau, air, pollution',
    sanction: 'Suspension d’autorisation d’exploitation et astreintes journalières',
    description: 'Prévention et réduction intégrées de la pollution industrielle via l’application des Meilleures Techniques Disponibles (MTD).',
    relevantSectors: ['Industrie', 'Chimie', 'Agro-industrie', 'Élevage'],
    defaultApplicability: 'non_assujetti',
    defaultNote: 'Régule les rejets atmosphériques et aqueux des sites industriels de taille significative.',
  },

  // ==========================================
  // 🇫🇷 II. TEXTES FRANÇAIS
  // ==========================================
  // A. Reporting & Transparence
  {
    id: 'fr_dpef',
    title: 'DPEF — Déclaration de Performance Extra-Financière',
    reference: 'Code de commerce Art. L.225-102-1',
    jurisdiction: 'france',
    family: 'Reporting & Transparence',
    sanction: 'Injonction sous astreinte par le tribunal de commerce et exclusion de la commande publique',
    description: 'Obligation de reporting sur les conséquences sociales, environnementales et climatiques de l’activité.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Applicable aux grandes entreprises françaises et en transition progressive vers la CSRD.',
  },
  {
    id: 'fr_beges',
    title: 'Décret BEGES — Bilan GES Réglementaire obligatoire',
    reference: 'Décret n° 2022-982 du 1er juillet 2022 (Art. L.229-25 Code Env.)',
    jurisdiction: 'france',
    family: 'Reporting & Transparence',
    sanction: 'Amende administrative portée à 10 000 € (et 20 000 € en cas de récidive)',
    description: 'Obligation de Bilan Carbone complet intégrant désormais le Scope 3 (émissions indirectes) et un plan de transition.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Obligatoire tous les 4 ans pour les entreprises de plus de 500 salariés (250 en outre-mer) et fortement encouragé pour les PME.',
  },

  // B. Vigilance & Droits humains
  {
    id: 'fr_devoir_vigilance',
    title: 'Loi sur le Devoir de Vigilance des sociétés mères',
    reference: 'Loi n° 2017-399 du 27 mars 2017',
    jurisdiction: 'france',
    family: 'Vigilance & Droits humains',
    sanction: 'Assignation judiciaire, injonction sous astreinte et dommages-intérêts civils',
    description: 'Établissement d’un plan de vigilance pour prévenir les atteintes graves aux droits humains et à l’environnement.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Les PME sous-traitantes doivent répondre aux questionnaires de vigilance de leurs clients donneurs d’ordres.',
  },
  {
    id: 'fr_sapin2',
    title: 'Loi Sapin II — Transparence, lutte contre la corruption et éthique',
    reference: 'Loi n° 2016-1691 du 9 décembre 2016',
    jurisdiction: 'france',
    family: 'Vigilance & Droits humains',
    sanction: 'Sanctions de la Commission des sanctions de l’AFA pouvant atteindre 1 million d’euros',
    description: 'Obligation de cartographie des risques et dispositifs d’alerte interne pour la gouvernance responsable.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Pilier indispensable du volet Gouvernance ("G") dans toute démarche RSE crédible.',
  },

  // C. Climat & Transition
  {
    id: 'fr_climat_resilience',
    title: 'Loi Climat & Résilience',
    reference: 'Loi n° 2021-1104 du 22 août 2021',
    jurisdiction: 'france',
    family: 'Climat & Transition',
    sanction: 'Amende jusqu’à 100 000 € pour allégation de neutralité non justifiée (Art. L229-68)',
    description: 'Texte fondateur encadrant la publicité environnementale, les menus végétariens, l’éco-score et la trajectoire bas-carbone.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Interdit formellement de revendiquer la neutralité sans publier un bilan GES couvrant l’ensemble du cycle de vie.',
  },
  {
    id: 'fr_snbc',
    title: 'Stratégie Nationale Bas-Carbone (SNBC)',
    reference: 'Décret n° 2020-457 (révision SNBC 3 en cours)',
    jurisdiction: 'france',
    family: 'Climat & Transition',
    sanction: 'Incompatibilité avec les aides publiques à la transition et pénalités de transition',
    description: 'Feuille de route nationale de la France pour atteindre la neutralité carbone en 2050 avec des budgets carbone sectoriels.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Aligner la stratégie carbone de l’entreprise sur la SNBC est la garantie de pérennité face aux futures réglementations.',
  },

  // D. Produits, ACV, FDES/EPD
  {
    id: 'fr_fdes_inies',
    title: 'FDES — Fiches de Déclaration Environnementale et Sanitaire',
    reference: 'Arrêté du 23 décembre 2013 & Base nationale INIES',
    jurisdiction: 'france',
    family: 'Produits, ACV, FDES/EPD',
    sanction: 'Application de valeurs forfaitaires pénalisantes (DED) disqualifiant le bâtiment en RE2020',
    description: 'Déclaration environnementale normalisée calculant l’empreinte carbone berceau-à-tombe des composants du bâtiment.',
    relevantSectors: ['BTP', 'Matériaux', 'Bois', 'Second oeuvre', 'Équipements'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Sans FDES spécifique sur la base INIES, vos produits se voient attribuer des valeurs par défaut 2 à 3 fois plus émettrices.',
  },
  {
    id: 'fr_re2020',
    title: 'Réglementation Environnementale RE2020',
    reference: 'Décret n° 2021-1004 (Code de la construction et de l’habitation)',
    jurisdiction: 'france',
    family: 'Produits, ACV, FDES/EPD',
    sanction: 'Refus de permis de construire et non-conformité de livraison des ouvrages',
    description: 'Obligation de réaliser l’ACV complète du bâtiment avec des plafonds dégressifs d’émissions de CO2 (kgCO2eq/m²).',
    relevantSectors: ['BTP', 'Promoteurs', 'Architectes', 'Matériaux'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'La RE2020 impose à tous les maîtres d’ouvrage de privilégier les matériaux dotés de FDES bas-carbone.',
  },

  // E. Déchets & Économie circulaire
  {
    id: 'fr_agec',
    title: 'Loi AGEC — Anti-Gaspillage pour une Économie Circulaire',
    reference: 'Loi n° 2020-105 du 10 février 2020',
    jurisdiction: 'france',
    family: 'Déchets & Économie circulaire',
    sanction: 'Amendes administratives jusqu’à 15 000 € par manquement et interdiction de destruction d’invendus',
    description: 'Interdiction des mentions "biodégradable", fin du plastique jetable, obligation d’information sur les qualités environnementales.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Interdit formellement d’apposer "biodégradable" ou "respectueux de l’environnement" sur les produits.',
  },
  {
    id: 'fr_tri_5flux',
    title: 'Tri 5 flux / 7 flux des déchets professionnels',
    reference: 'Décret n° 2021-950 et Code de l’environnement Art. D543-278',
    jurisdiction: 'france',
    family: 'Déchets & Économie circulaire',
    sanction: 'Amende jusqu’à 75 000 € et 2 ans d’emprisonnement en cas de non-respect du tri',
    description: 'Obligation pour tous les établissements de trier papier, métal, plastique, verre, bois, carton et textile.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Obligation en vigueur pour tout bureau, entrepôt ou atelier générant des déchets professionnels.',
  },

  // F. Eau, air, pollution
  {
    id: 'fr_icpe',
    title: 'ICPE — Installations Classées pour la Protection de l’Environnement',
    reference: 'Code de l’environnement Livre V Titre Ier',
    jurisdiction: 'france',
    family: 'Eau, air, pollution',
    sanction: 'Fermeture administrative de site, consignation financière et sanctions pénales',
    description: 'Régime d’autorisation, d’enregistrement ou de déclaration pour les activités à impact environnemental ou risque d’accident.',
    relevantSectors: ['Industrie', 'Atelier', 'Stockage', 'Agricole'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'S’applique dès que des stockages de fluides, solvants ou capacités de production dépassent les seuils de nomenclature.',
  },

  // G. Achats responsables & Marchés publics
  {
    id: 'fr_achats_responsables',
    title: 'Clauses environnementales obligatoires dans la Commande Publique',
    reference: 'Code de la commande publique (échéance légale 2026)',
    jurisdiction: 'france',
    family: 'Achats responsables & Marchés publics',
    sanction: 'Rejet automatique de l’offre pour non-respect des critères environnementaux obligatoires',
    description: 'Obligation légale pour 100% des marchés publics de comporter au moins un critère de choix environnemental d’ici 2026.',
    relevantSectors: ['all'],
    defaultApplicability: 'echeance_2026',
    defaultNote: 'Disposer d’un Bilan Carbone officiel et d’une démarche RSE auditée devient une condition éliminatoire pour remporter les appels d’offres.',
  },

  // H. Consommateurs & Publicité
  {
    id: 'fr_arpp_conso',
    title: 'Recommandation ARPP "Développement Durable" & Code de la Consommation',
    reference: 'Code de la consommation Art. L121-2 & Recommandations ARPP',
    jurisdiction: 'france',
    family: 'Consommateurs & Publicité',
    sanction: 'Avis défavorable du Jury de Déontologie Publicitaire (JDP) et saisine DGCCRF',
    description: 'Règles déontologiques encadrant la véracité, la proportionnalité, la clarté et la loyauté des messages écologiques.',
    relevantSectors: ['all'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Toute campagne publicitaire doit être proportionnée et ne pas survendre un bénéfice écologique minime.',
  },

  // ==========================================
  // 🧩 III. NORMES INTERNATIONALES (Volontaires & Clés d'Accès aux Marchés)
  // ==========================================
  // A. ACV & Empreinte Carbone
  {
    id: 'iso_14040',
    title: 'ISO 14040 / 14044 — Analyse du Cycle de Vie (ACV)',
    reference: 'Normes internationales ISO 14040:2006 & ISO 14044:2006',
    jurisdiction: 'international',
    family: 'ACV & Empreinte Carbone',
    sanction: 'Allégation non opposable et contestation scientifique par des tiers',
    description: 'Méthodologie internationale pour évaluer les impacts environnementaux potentiels d’un produit sur l’ensemble de son cycle de vie.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'La référence scientifique absolue exigée pour prouver toute allégation d’éco-conception ou de produit vertueux.',
  },
  {
    id: 'iso_14067',
    title: 'ISO 14067 — Empreinte carbone des produits',
    reference: 'Norme internationale ISO 14067:2018',
    jurisdiction: 'international',
    family: 'ACV & Empreinte Carbone',
    sanction: 'Contestation par les auditeurs externes et disqualification des labels carbone',
    description: 'Principes, exigences et lignes directrices pour la quantification de l’empreinte carbone des produits.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Spécification détaillée indispensable pour afficher des déclarations de réduction d’émissions par unité de produit.',
  },
  {
    id: 'iso_ghg_protocol',
    title: 'GHG Protocol — Corporate Standard (Scopes 1, 2, 3)',
    reference: 'Standard international WRI / WBCSD',
    jurisdiction: 'international',
    family: 'ACV & Empreinte Carbone',
    sanction: 'Non-reconnaissance des bilans carbone par les investisseurs internationaux et la CSRD',
    description: 'Standard mondial le plus utilisé pour comptabiliser les émissions de gaz à effet de serre des organisations.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Parfaitement aligné avec la méthode Bilan Carbone® proposée par Climat Local.',
  },
  {
    id: 'pas_2050',
    title: 'PAS 2050 — Évaluation du cycle de vie des GES des biens et services',
    reference: 'British Standards Institution (BSI) / DEFRA',
    jurisdiction: 'international',
    family: 'ACV & Empreinte Carbone',
    sanction: 'Standard méthodologique complémentaire à l’ISO 14067',
    description: 'L’une des premières méthodes internationales pionnières d’ACV carbone pour les biens et services.',
    relevantSectors: ['Agroalimentaire', 'Biens de consommation'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Utilisé par de nombreux distributeurs pour l’affichage carbone en rayon.',
  },

  // B. Management Environnemental
  {
    id: 'iso_14001',
    title: 'ISO 14001 — Système de Management Environnemental (SME)',
    reference: 'Norme internationale ISO 14001:2015',
    jurisdiction: 'international',
    family: 'Management Environnemental',
    sanction: 'Perte de certification tierce et pénalités de sélection en appels d’offres',
    description: 'Cadre international pour maîtriser l’impact environnemental des activités et améliorer continuellement les performances.',
    relevantSectors: ['Industrie', 'BTP', 'Services', 'all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Le standard le plus demandé dans les cahiers des charges B2B pour attester d’une organisation environnementale rigoureuse.',
  },
  {
    id: 'iso_50001',
    title: 'ISO 50001 — Système de Management de l’Énergie',
    reference: 'Norme internationale ISO 50001:2018',
    jurisdiction: 'international',
    family: 'Management Environnemental',
    sanction: 'Exemption de l’audit énergétique obligatoire en France pour les entreprises certifiées',
    description: 'Méthodologie pour améliorer l’efficacité énergétique, réduire les consommations et optimiser les coûts associés.',
    relevantSectors: ['Industrie', 'Tertiaire lourd', 'Transport'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Permet de justifier légalement de la dispense d’audit énergétique réglementaire tout en générant des économies directes.',
  },

  // C. RSE & Gouvernance
  {
    id: 'iso_26000',
    title: 'ISO 26000 — Lignes directrices relatives à la Responsabilité Sociétale',
    reference: 'Norme internationale ISO 26000:2010',
    jurisdiction: 'international',
    family: 'RSE & Gouvernance',
    sanction: 'Non-labellisation et vulnérabilité face aux accusations de RSE opportuniste',
    description: 'Cadre universel articulé autour de 7 questions centrales : gouvernance, droits de l’homme, relations de travail, environnement, loyauté des pratiques...',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Le socle fondateur utilisé par Climat Local pour bâtir la stratégie RSE et préparer aux labels (Engagé RSE, B Corp).',
  },
  {
    id: 'gri_standards',
    title: 'GRI Standards — Global Reporting Initiative',
    reference: 'Standards internationaux GRI (GSSB)',
    jurisdiction: 'international',
    family: 'RSE & Gouvernance',
    sanction: 'Dégradation des notations ESG et indices extra-financiers',
    description: 'Référentiel mondial historique de reporting de durabilité pour communiquer de manière transparente sur les impacts.',
    relevantSectors: ['all'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Largement interconnecté avec les normes européennes ESRS de la directive CSRD.',
  },

  // ==========================================
  // 🧨 IV. TEXTES SECTORIELS
  // ==========================================
  {
    id: 'sec_inco',
    title: 'Règlement INCO (Information des Consommateurs sur les Denrées)',
    reference: 'Règlement (UE) n° 1169/2011',
    jurisdiction: 'sectoriel',
    family: 'Agroalimentaire',
    sanction: 'Retrait de lots alimentaires et sanctions DGCCRF pour étiquetage trompeur',
    description: 'Règles strictes sur l’étiquetage nutritionnel, l’origine géographique et les mentions environnementales sur les denrées alimentaires.',
    relevantSectors: ['Agroalimentaire', 'Boisson', 'Restauration'],
    defaultApplicability: 'obligatoire_direct',
    defaultNote: 'Interdit toute allégation nutritionnelle ou environnementale qui ne repose pas sur des preuves analytiques tangibles.',
  },
  {
    id: 'sec_cle_verte',
    title: 'Label Clé Verte & Écolabel Hébergement Touristique',
    reference: 'Référentiels certifiés Teragir / ADEME',
    jurisdiction: 'sectoriel',
    family: 'Tourisme / Hôtellerie',
    sanction: 'Perte de labellisation et exclusion des plateformes éco-voyages',
    description: 'Premier écolabel pour l’hébergement touristique et la restauration durable en France et à l’international.',
    relevantSectors: ['Hôtellerie', 'Tourisme', 'Camping', 'Restauration'],
    defaultApplicability: 'recommande_marches',
    defaultNote: 'Permet aux établissements touristiques de prouver leur engagement sans risque de greenwashing.',
  },
];

/**
 * Generate company-specific evaluation for regulatory frameworks based on audit sector and activity
 */
export function generateCompanyRegulatoryEvaluations(
  companyName: string,
  sector: string,
  detectedClaimsCount: number
): ApplicableRegulationEvaluation[] {
  const lowerSector = (sector || '').toLowerCase();
  const isBTP = lowerSector.includes('bâtiment') || lowerSector.includes('btp') || lowerSector.includes('construction') || lowerSector.includes('menuiserie') || lowerSector.includes('bois');
  const isIndustry = lowerSector.includes('industrie') || lowerSector.includes('matériaux') || lowerSector.includes('fabrication') || lowerSector.includes('métallurgie');
  const isAgro = lowerSector.includes('agro') || lowerSector.includes('agricole') || lowerSector.includes('alimentaire') || lowerSector.includes('nourriture');
  const isTourism = lowerSector.includes('tourisme') || lowerSector.includes('hôtel') || lowerSector.includes('hébergement') || lowerSector.includes('restauration');

  return OFFICIAL_REGULATORY_FRAMEWORKS.map((fw) => {
    let isApplicable = true;
    let status = fw.defaultApplicability;
    let customNote = fw.defaultNote;

    // Tailor specifically to the company and sector
    if (fw.id === 'ue_empco') {
      isApplicable = true;
      status = 'obligatoire_direct';
      customNote = `${detectedClaimsCount > 0 ? `${detectedClaimsCount} allégation(s) détectée(s) sur vos supports tombent sous le coup direct` : `Vos communications écologiques sont soumises`} aux interdictions strictes de la Directive (UE) 2024/825.`;
    } else if (fw.id === 'fr_climat_resilience') {
      isApplicable = true;
      status = 'obligatoire_direct';
      customNote = `En tant qu’entreprise communiquant en France, l’article L229-68 vous interdit toute mention de neutralité sans bilan GES public couvrant l’ensemble du cycle de vie.`;
    } else if (fw.id === 'fr_fdes_inies' || fw.id === 'ue_en15804') {
      if (isBTP || isIndustry) {
        isApplicable = true;
        status = 'obligatoire_direct';
        customNote = `Prioritaire pour ${companyName} : la commercialisation de vos matériaux sur les chantiers neufs RE2020 exige des FDES conformes NF EN 15804+A2 déposées sur INIES.`;
      } else {
        isApplicable = false;
        status = 'non_assujetti';
        customNote = `Non concerné directement pour ce type d'activité de services.`;
      }
    } else if (fw.id === 'fr_re2020') {
      if (isBTP) {
        isApplicable = true;
        status = 'obligatoire_direct';
        customNote = `Impact direct sur le choix de vos produits par les maîtres d’ouvrage et bureaux d’études thermiques/carbone.`;
      } else {
        isApplicable = false;
        status = 'non_assujetti';
      }
    } else if (fw.id === 'sec_inco') {
      isApplicable = isAgro;
      status = isAgro ? 'obligatoire_direct' : 'non_assujetti';
      customNote = isAgro ? `Régule directement les allégations figurant sur vos étiquetages alimentaires.` : 'Non applicable à ce secteur.';
    } else if (fw.id === 'sec_cle_verte') {
      isApplicable = isTourism;
      status = isTourism ? 'recommande_marches' : 'non_assujetti';
      customNote = isTourism ? `Label recommandé pour sécuriser la communication de vos établissements.` : 'Non applicable.';
    } else if (fw.id === 'fr_achats_responsables') {
      isApplicable = true;
      status = 'echeance_2026';
      customNote = `Dès 2026, 100% des acheteurs publics doivent noter l'impact environnemental. Climat Local vous prépare pour sécuriser vos candidatures.`;
    } else if (fw.id === 'ue_csrd') {
      isApplicable = true;
      status = 'echeance_2026';
      customNote = `Même si vous êtes une PME, vos clients de rang supérieur assujettis à la CSRD vous demanderont vos données d'émissions Scope 1, 2, 3 dès 2025/2026.`;
    }

    return {
      id: fw.id,
      title: fw.title,
      reference: fw.reference,
      jurisdiction: fw.jurisdiction,
      family: fw.family,
      isApplicable,
      applicabilityStatus: status,
      customCompanyNote: customNote,
      sanctionMax: fw.sanction,
    };
  });
}
