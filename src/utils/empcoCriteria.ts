/**
 * Référentiel Officiel EmpCO - Écologie, Matériel, Performance, Conception, Organisation
 * Méthodologie de calcul normée pour l'audit d'éco-conception et d'empreinte carbone web
 * Conforme aux référentiels RGESN (ADEME/ARCEP), EcoIndex (GreenIT) et Sustainable Web Design (SWD v3)
 */

export interface EmpcoCriterion {
  id: string;
  pillar: 'C' | 'P' | 'M' | 'E' | 'O';
  code: string;
  name: string;
  maxPoints: number;
  description: string;
  measurementMethod: string;
  targetThreshold: string;
  scoringFormula: string;
  pointsEarned?: number;
  actualValue?: string | number;
  status?: 'conforme' | 'ameliorer' | 'non_conforme';
}

export interface EmpcoPillarSpec {
  code: 'C' | 'P' | 'M' | 'E' | 'O';
  letter: string;
  title: string;
  subtitle: string;
  maxPoints: number;
  color: string;
  criteria: EmpcoCriterion[];
}

export const EMPCO_SPECIFICATION: EmpcoPillarSpec[] = [
  {
    code: 'C',
    letter: 'C',
    title: 'Conception & Poids des Données',
    subtitle: 'Sobriété des volumes transférés et optimisation des médias',
    maxPoints: 25,
    color: 'emerald',
    criteria: [
      {
        id: 'C1',
        pillar: 'C',
        code: 'C1',
        name: 'Poids total de la page transférée (Ko)',
        maxPoints: 12,
        description: 'Volume total de données transmises au premier chargement (HTML, CSS, JS, polices, images).',
        measurementMethod: 'Mesure HTTP du flux total transféré (payload compressé).',
        targetThreshold: '< 500 Ko (Idéal sobriété)',
        scoringFormula: '≤ 500 Ko : 12 pts | 501-1000 Ko : 9 pts | 1001-2000 Ko : 6 pts | 2001-4000 Ko : 3 pts | > 4000 Ko : 0 pt',
      },
      {
        id: 'C2',
        pillar: 'C',
        code: 'C2',
        name: 'Optimisation des médias (WebP, AVIF, SVG)',
        maxPoints: 8,
        description: 'Utilisation de formats graphiques de dernière génération hautement compressés.',
        measurementMethod: 'Ratio des images en formats modernes (WebP, AVIF, SVG vectoriel) versus JPEG/PNG bruts.',
        targetThreshold: '100% des images optimisées',
        scoringFormula: '≥ 90% WebP/AVIF : 8 pts | 60-89% : 5 pts | 30-59% : 2 pts | < 30% : 0 pt',
      },
      {
        id: 'C3',
        pillar: 'C',
        code: 'C3',
        name: 'Sobriété éditoriale & Absence de médias intrusifs',
        maxPoints: 5,
        description: 'Suppression des flux vidéo en autoplay automatique, carrousels infinis et animations permanentes.',
        measurementMethod: 'Détection des attributs autoplay vidéo et iframes en chargement synchrone.',
        targetThreshold: 'Lancement de vidéo uniquement sur action explicite de l’utilisateur',
        scoringFormula: 'Aucun autoplay : 5 pts | Autoplay vidéo muet différé : 2 pts | Autoplay systématique : 0 pt',
      },
    ],
  },
  {
    code: 'P',
    letter: 'P',
    title: 'Performance & Efficience Technique',
    subtitle: 'Minimisation du travail CPU client et optimisation des requêtes réseau',
    maxPoints: 20,
    color: 'teal',
    criteria: [
      {
        id: 'P1',
        pillar: 'P',
        code: 'P1',
        name: 'Nombre de requêtes HTTP initiales',
        maxPoints: 8,
        description: 'Nombre de requêtes nécessaires pour afficher la vue principale de la page.',
        measurementMethod: 'Comptage des requêtes HTTP (scripts, stylesheets, fonts, images, API).',
        targetThreshold: '≤ 25 requêtes',
        scoringFormula: '≤ 25 requêtes : 8 pts | 26-50 requêtes : 5 pts | 51-80 requêtes : 2 pts | > 80 : 0 pt',
      },
      {
        id: 'P2',
        pillar: 'P',
        code: 'P2',
        name: 'Complexité du DOM (Document Object Model)',
        maxPoints: 6,
        description: 'Nombre total de nœuds HTML générés. Un DOM lourd surchauffe le CPU des terminaux mobiles.',
        measurementMethod: 'Comptage des éléments DOM dans le navigateur (document.querySelectorAll("*").length).',
        targetThreshold: '< 600 nœuds DOM',
        scoringFormula: '< 600 nœuds : 6 pts | 600-1200 nœuds : 4 pts | 1201-2000 : 2 pts | > 2000 : 0 pt',
      },
      {
        id: 'P3',
        pillar: 'P',
        code: 'P3',
        name: 'Compression serveur & Mise en cache HTTP',
        maxPoints: 6,
        description: 'Activation de Brotli ou Gzip et directives Cache-Control longue durée sur les assets statiques.',
        measurementMethod: 'Vérification des headers HTTP content-encoding et cache-control.',
        targetThreshold: 'Brotli actif + Cache > 30 jours',
        scoringFormula: 'Brotli + Cache long : 6 pts | Gzip seul ou cache partiel : 4 pts | Aucun : 0 pt',
      },
    ],
  },
  {
    code: 'M',
    letter: 'M',
    title: 'Matériel & Hébergement Durable',
    subtitle: 'Énergie renouvelable, efficacité des datacenters et ancrage local',
    maxPoints: 20,
    color: 'cyan',
    criteria: [
      {
        id: 'M1',
        pillar: 'M',
        code: 'M1',
        name: 'Énergie renouvelable & Certification Green Web',
        maxPoints: 10,
        description: 'L’hébergeur utilise une électricité 100% d’origine renouvelable certifiée (ISO 50001, garanties d’origine).',
        measurementMethod: 'Vérification du fournisseur DNS/IP auprès de The Green Web Foundation et audits d’engagements.',
        targetThreshold: '100% renouvelable certifié',
        scoringFormula: 'Certifié Green Web / Énergie verte vérifiée : 10 pts | Partiellement compensé : 5 pts | Non certifié : 0 pt',
      },
      {
        id: 'M2',
        pillar: 'M',
        code: 'M2',
        name: 'Localisation du Datacenter (Proximité & Souveraineté)',
        maxPoints: 5,
        description: 'Hébergement géographiquement proche des utilisateurs cibles pour limiter les bonds réseau mondiaux.',
        measurementMethod: 'Géolocalisation IP du serveur web.',
        targetThreshold: 'Serveur situé en France métropolitaine',
        scoringFormula: 'France : 5 pts | Union Européenne : 4 pts | Hors UE : 1 pt',
      },
      {
        id: 'M3',
        pillar: 'M',
        code: 'M3',
        name: 'Efficience PUE (Power Usage Effectiveness)',
        maxPoints: 5,
        description: 'Rendement énergétique du datacenter. Ratio entre l’énergie totale consommée et l’énergie informatique utile.',
        measurementMethod: 'Rapport RSE de l’hébergeur ou moyenne du site d’hébergement.',
        targetThreshold: 'PUE < 1.25 (Haute efficience)',
        scoringFormula: 'PUE < 1.25 : 5 pts | PUE 1.25 à 1.45 : 3 pts | PUE > 1.45 ou inconnu : 1 pt',
      },
    ],
  },
  {
    code: 'E',
    letter: 'E',
    title: 'Écologie & Impact Carbone Estimé',
    subtitle: 'Intensité carbone par page vue et équivalences GES annuelles',
    maxPoints: 20,
    color: 'emerald',
    criteria: [
      {
        id: 'E1',
        pillar: 'E',
        code: 'E1',
        name: 'Intensité carbone unitaire (gCO2e / visite)',
        maxPoints: 12,
        description: 'Grammes de CO2 équivalent émis par page vue selon le modèle Sustainable Web Design (SWD v3).',
        measurementMethod: 'Calcul SWD : [Poids (Go) × 0.81 kWh/Go × Facteur Mix Électrique France/Monde].',
        targetThreshold: '≤ 0.50 gCO2e (Classe A d’impact)',
        scoringFormula: '≤ 0.50 g : 12 pts | 0.51-0.90 g : 10 pts | 0.91-1.40 g : 7 pts | 1.41-2.00 g : 4 pts | > 2.00 g : 1 pt',
      },
      {
        id: 'E2',
        pillar: 'E',
        code: 'E2',
        name: 'Sobriété énergétique globale (kWh / 1000 visites)',
        maxPoints: 8,
        description: 'Énergie électrique absorbée par les serveurs, le réseau de transmission et le terminal utilisateur.',
        measurementMethod: 'Modélisation 1-byte Shift Project / Sustainable Web Design.',
        targetThreshold: '< 0.0010 kWh par page vue',
        scoringFormula: '< 0.0008 kWh : 8 pts | 0.0008-0.0014 kWh : 5 pts | 0.0015-0.0025 kWh : 3 pts | > 0.0025 kWh : 1 pt',
      },
    ],
  },
  {
    code: 'O',
    letter: 'O',
    title: 'Organisation, RGESN & Gouvernance',
    subtitle: 'Conformité au référentiel d’État, sobriété fonctionnelle et RGPD',
    maxPoints: 15,
    color: 'amber',
    criteria: [
      {
        id: 'O1',
        pillar: 'O',
        code: 'O1',
        name: 'Taux de conformité RGESN (ADEME / ARCEP / DINUM)',
        maxPoints: 7,
        description: 'Adéquation avec les 79 critères du Référentiel Général d’Éco-conception des Services Numériques.',
        measurementMethod: 'Évaluation des 7 axes du référentiel officiel français.',
        targetThreshold: '> 75% de critères respectés',
        scoringFormula: '≥ 75% : 7 pts | 50-74% : 5 pts | 30-49% : 3 pts | < 30% : 1 pt',
      },
      {
        id: 'O2',
        pillar: 'O',
        code: 'O2',
        name: 'Sobriété des trackers tiers & Respect de la vie privée',
        maxPoints: 4,
        description: 'Absence de pixels publicitaires invasifs (Meta, TikTok) surchargeant le réseau et les requêtes.',
        measurementMethod: 'Détection des scripts tiers marketing et conformité cookies.',
        targetThreshold: '0 tracker publicitaire ou analytics sobre exempté de consentement (ex: Plausible, Matomo)',
        scoringFormula: '0 tracker superflu : 4 pts | 1-2 trackers : 2 pts | > 2 trackers intrusifs : 0 pt',
      },
      {
        id: 'O3',
        pillar: 'O',
        code: 'O3',
        name: 'Cycle de vie, Dark Mode & Préservation de batterie',
        maxPoints: 4,
        description: 'Prise en charge du thème sombre (OLED) pour économiser la batterie des smartphones et fin de vie des données.',
        measurementMethod: 'Présence de media query prefers-color-scheme ou bouton d’éco-mode.',
        targetThreshold: 'Mode sombre ou éco-conçu présent',
        scoringFormula: 'Mode sombre/éco-mode actif : 4 pts | Partiel : 2 pts | Absent : 1 pt',
      },
    ],
  },
];

/**
 * Calculateur officiel de conformité EmpCO
 */
export function calculateEmpcoScoreDetailed(params: {
  pageSizeKb: number;
  requestsCount: number;
  domNodes: number;
  hasWebP: boolean;
  hasHeavyVideos: boolean;
  hasBrotliOrGzip: boolean;
  isGreenHosting: boolean;
  hostingCountry: string;
  pueEstimated?: number;
  carbonPerVisit: number;
  rgesnCompliancePercent: number;
  trackersCount: number;
  hasDarkMode?: boolean;
}) {
  let scoreC = 0;
  let scoreP = 0;
  let scoreM = 0;
  let scoreE = 0;
  let scoreO = 0;

  const evaluatedCriteria: EmpcoCriterion[] = [];

  // --- C1: Poids total (12 pts)
  let c1Pts = 0;
  if (params.pageSizeKb <= 500) c1Pts = 12;
  else if (params.pageSizeKb <= 1000) c1Pts = 9;
  else if (params.pageSizeKb <= 2000) c1Pts = 6;
  else if (params.pageSizeKb <= 4000) c1Pts = 3;
  else c1Pts = 0;
  scoreC += c1Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[0].criteria[0],
    pointsEarned: c1Pts,
    actualValue: `${params.pageSizeKb} Ko`,
    status: c1Pts >= 9 ? 'conforme' : c1Pts >= 6 ? 'ameliorer' : 'non_conforme',
  });

  // --- C2: Optimisation des médias (8 pts)
  const c2Pts = params.hasWebP ? 8 : 2;
  scoreC += c2Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[0].criteria[1],
    pointsEarned: c2Pts,
    actualValue: params.hasWebP ? 'Formats WebP/AVIF détectés' : 'Formats anciens (JPEG/PNG non optimisés)',
    status: c2Pts >= 6 ? 'conforme' : 'ameliorer',
  });

  // --- C3: Autoplay médias (5 pts)
  const c3Pts = params.hasHeavyVideos ? 0 : 5;
  scoreC += c3Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[0].criteria[2],
    pointsEarned: c3Pts,
    actualValue: params.hasHeavyVideos ? 'Vidéos/Iframes lourdes détectées' : 'Aucun flux vidéo intrusif',
    status: c3Pts === 5 ? 'conforme' : 'non_conforme',
  });

  // --- P1: Requêtes HTTP (8 pts)
  let p1Pts = 0;
  if (params.requestsCount <= 25) p1Pts = 8;
  else if (params.requestsCount <= 50) p1Pts = 5;
  else if (params.requestsCount <= 80) p1Pts = 2;
  else p1Pts = 0;
  scoreP += p1Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[1].criteria[0],
    pointsEarned: p1Pts,
    actualValue: `${params.requestsCount} requêtes`,
    status: p1Pts >= 5 ? 'conforme' : 'ameliorer',
  });

  // --- P2: DOM (6 pts)
  let p2Pts = 0;
  if (params.domNodes < 600) p2Pts = 6;
  else if (params.domNodes <= 1200) p2Pts = 4;
  else if (params.domNodes <= 2000) p2Pts = 2;
  else p2Pts = 0;
  scoreP += p2Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[1].criteria[1],
    pointsEarned: p2Pts,
    actualValue: `${params.domNodes} éléments DOM`,
    status: p2Pts >= 4 ? 'conforme' : 'ameliorer',
  });

  // --- P3: Compression & Cache (6 pts)
  const p3Pts = params.hasBrotliOrGzip ? 5 : 1;
  scoreP += p3Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[1].criteria[2],
    pointsEarned: p3Pts,
    actualValue: params.hasBrotliOrGzip ? 'Compression HTTP active' : 'Pas de compression détectée',
    status: p3Pts >= 4 ? 'conforme' : 'non_conforme',
  });

  // --- M1: Hébergeur vert (10 pts)
  const m1Pts = params.isGreenHosting ? 10 : 3;
  scoreM += m1Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[2].criteria[0],
    pointsEarned: m1Pts,
    actualValue: params.isGreenHosting ? 'Hébergeur éco-certifié' : 'Hébergement standard non certifié',
    status: m1Pts >= 8 ? 'conforme' : 'ameliorer',
  });

  // --- M2: Localisation Datacenter (5 pts)
  const isFrance = params.hostingCountry.toLowerCase().includes('france');
  const m2Pts = isFrance ? 5 : 3;
  scoreM += m2Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[2].criteria[1],
    pointsEarned: m2Pts,
    actualValue: params.hostingCountry || 'France / UE',
    status: isFrance ? 'conforme' : 'ameliorer',
  });

  // --- M3: PUE (5 pts)
  const pue = params.pueEstimated || 1.35;
  const m3Pts = pue < 1.25 ? 5 : pue <= 1.45 ? 3 : 1;
  scoreM += m3Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[2].criteria[2],
    pointsEarned: m3Pts,
    actualValue: `PUE estimé : ${pue}`,
    status: m3Pts >= 4 ? 'conforme' : 'ameliorer',
  });

  // --- E1: Intensité carbone unitaire (12 pts)
  let e1Pts = 0;
  if (params.carbonPerVisit <= 0.5) e1Pts = 12;
  else if (params.carbonPerVisit <= 0.9) e1Pts = 10;
  else if (params.carbonPerVisit <= 1.4) e1Pts = 7;
  else if (params.carbonPerVisit <= 2.0) e1Pts = 4;
  else e1Pts = 1;
  scoreE += e1Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[3].criteria[0],
    pointsEarned: e1Pts,
    actualValue: `${params.carbonPerVisit} gCO2e / visite`,
    status: e1Pts >= 10 ? 'conforme' : e1Pts >= 7 ? 'ameliorer' : 'non_conforme',
  });

  // --- E2: Consommation énergétique (8 pts)
  const e2Pts = params.carbonPerVisit <= 0.8 ? 8 : params.carbonPerVisit <= 1.5 ? 5 : 2;
  scoreE += e2Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[3].criteria[1],
    pointsEarned: e2Pts,
    actualValue: `${(params.carbonPerVisit * 0.0011).toFixed(4)} kWh / visite`,
    status: e2Pts >= 5 ? 'conforme' : 'ameliorer',
  });

  // --- O1: Conformité RGESN (7 pts)
  let o1Pts = 0;
  if (params.rgesnCompliancePercent >= 75) o1Pts = 7;
  else if (params.rgesnCompliancePercent >= 50) o1Pts = 5;
  else if (params.rgesnCompliancePercent >= 30) o1Pts = 3;
  else o1Pts = 1;
  scoreO += o1Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[4].criteria[0],
    pointsEarned: o1Pts,
    actualValue: `${params.rgesnCompliancePercent}% de conformité`,
    status: o1Pts >= 5 ? 'conforme' : 'ameliorer',
  });

  // --- O2: Trackers (4 pts)
  const o2Pts = params.trackersCount === 0 ? 4 : params.trackersCount <= 2 ? 2 : 0;
  scoreO += o2Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[4].criteria[1],
    pointsEarned: o2Pts,
    actualValue: `${params.trackersCount} tracker(s) détecté(s)`,
    status: o2Pts === 4 ? 'conforme' : o2Pts === 2 ? 'ameliorer' : 'non_conforme',
  });

  // --- O3: Dark Mode / Batterie (4 pts)
  const o3Pts = params.hasDarkMode ? 4 : 2;
  scoreO += o3Pts;
  evaluatedCriteria.push({
    ...EMPCO_SPECIFICATION[4].criteria[2],
    pointsEarned: o3Pts,
    actualValue: params.hasDarkMode ? 'Mode sombre / éco présent' : 'Pas d’éco-mode dédié',
    status: o3Pts >= 3 ? 'conforme' : 'ameliorer',
  });

  const totalScore = Math.min(100, Math.max(5, scoreC + scoreP + scoreM + scoreE + scoreO));

  let grade: 'A' | 'B' | 'C' | 'D' | 'E' | 'F' | 'G' = 'D';
  if (totalScore >= 80) grade = 'A';
  else if (totalScore >= 70) grade = 'B';
  else if (totalScore >= 55) grade = 'C';
  else if (totalScore >= 40) grade = 'D';
  else if (totalScore >= 25) grade = 'E';
  else if (totalScore >= 10) grade = 'F';
  else grade = 'G';

  return {
    totalScore,
    grade,
    scoreC,
    scoreP,
    scoreM,
    scoreE,
    scoreO,
    evaluatedCriteria,
  };
}
