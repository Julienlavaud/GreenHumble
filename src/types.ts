export type RiskLevel = 'critique' | 'eleve' | 'modere' | 'faible';

export type ClaimCategory = 'neutralite' | 'generique' | 'produit_acv' | 'materiau_fdes' | 'label_non_certifie' | 'futur_sans_tiers';

export type ClaimSeverity = 'interdit_strict' | 'haut_risque' | 'trompeur_sans_preuve';

export type ClimatLocalServiceKey = 
  | 'bilan_carbone' 
  | 'evaluation_bilan_carbone'
  | 'credits_carbone_locaux' 
  | 'depot_projet_lbc'
  | 'strategie_rse' 
  | 'raison_d_etre' 
  | 'sensibilisation_equipes' 
  | 'acv' 
  | 'fdes_epd'
  | 'biodiversite_locale';

export interface DetectedClaim {
  id: string;
  term: string;
  quoteInText: string;
  category: ClaimCategory;
  severity: ClaimSeverity;
  legalRisk: string;
  lawArticle: string;
  sanction: string;
  requiredProof: string;
  recommendedService: ClimatLocalServiceKey;
  isUnfounded?: boolean;
  correctionNote?: string;
  consultantValidationStatus?: 'valide' | 'non_fonde' | 'justifie_par_preuve';
}

export interface BusinessOpportunity {
  serviceKey: ClimatLocalServiceKey;
  serviceTitle: string;
  relevanceScore: number; // 0 à 100
  reason: string;
  potentialBudget: string;
  subsidiesEligible: string;
  deliverables: string[];
  salesArguments: string[];
}

export interface ClientQuickSummary {
  headline: string;
  riskVerdict: string;
  keyUrgentAction: string;
  immediateRisks: string[];
  stepsToCompliance: Array<{
    step: number;
    title: string;
    description: string;
  }>;
}

export interface CommercialProspectingKit {
  targetPersona: string;
  executiveSummary: string;
  phonePitch: {
    hook: string;
    keyFact: string;
    legalWarning: string;
    callToAction: string;
    objectionResponses: Array<{
      objection: string;
      answer: string;
    }>;
  };
  emailProspecting: {
    legalSecurity: {
      subject: string;
      body: string;
    };
    businessTransformation: {
      subject: string;
      body: string;
    };
    localCarbonContribution?: {
      subject: string;
      body: string;
    };
    reputationProtection?: {
      subject: string;
      body: string;
    };
    relanceSubvention?: {
      subject: string;
      body: string;
    };
  };
  climatLocalProposal: {
    recommendedPackage: string;
    pricingEstimate: string;
    subsidies: string;
    timeline: string;
    closingArgument?: string;
  };
  customComplements?: CommercialComplements;
}

export interface CommercialComplements {
  targetPersona?: string;
  strategicAngle?: string;
  userComplements?: string;
  tone?: string;
  preferredOffer?: string;
  generatedAt?: string;
}

export interface LeadContact {
  id?: string;
  name?: string;
  email?: string;
  phone?: string;
  role?: string;
  roleTitle?: string;
  company?: string;
  message?: string;
}

export interface RoleAssignment {
  id: string;
  roleKey: 'lead_referent' | 'auditeur_carbone' | 'expert_rse' | 'projets_locaux';
  roleTitle: string;
  collaboratorId: string;
  collaboratorName: string;
  assignedAt: string;
  status: 'en_attente' | 'validee' | 'refusee';
  validatedBy?: string;
  validatedAt?: string;
  notes?: string;
}

export interface AuditTask {
  id: string;
  title: string;
  category: 'qualification' | 'audit_juridique' | 'contact_dirigeant' | 'proposition_mission' | 'cloture';
  isDone: boolean; // Fait / Pas fait
  completedAt?: string;
  completedBy?: string; // Nom de qui a fait
  mandatory?: boolean;
  notes?: string;
}

export interface AuditHistoryLog {
  id: string;
  timestamp: string;
  authorName: string;
  actionType: 
    | 'role_assigned' 
    | 'role_validated' 
    | 'role_rejected'
    | 'task_completed' 
    | 'task_uncompleted' 
    | 'task_added'
    | 'status_changed' 
    | 'note_added' 
    | 'audit_created';
  description: string;
  details?: string;
}

export interface CompanyContact {
  id?: string;
  name: string;
  email: string;
  phone: string;
  roleTitle?: string;
  contactType?: 'RSE' | 'QSE' | 'Direction' | 'Achats' | 'Communication' | 'Autre';
  linkedinUrl?: string;
  notes?: string;
}

export interface AnalysisQualitySummary {
  pagesScannedCount: number;
  pagesList: Array<{ url: string; title: string; type?: string }>;
  scannedImagesCount: number;
  imageBadgesDetected: Array<{ label: string; src?: string; status: 'suspect' | 'neutre' | 'certifie' }>;
  totalCharactersAnalyzed: number;
  scanDurationSeconds: number;
  confidenceScore: number;
  sourceType: 'site_web' | 'document_pdf_rapport';
  documentName?: string;
}

export interface ApplicableRegulationEvaluation {
  id: string;
  title: string;
  reference: string;
  jurisdiction: 'europe' | 'france' | 'international' | 'sectoriel';
  family: string;
  isApplicable: boolean;
  applicabilityStatus: 'obligatoire_direct' | 'echeance_2026' | 'recommande_marches' | 'non_assujetti' | 'non_fonde';
  customCompanyNote: string;
  sanctionMax?: string;
  isUnfounded?: boolean;
  correctionReason?: string;
}

export interface PageAnalysee {
  url: string;
  priorite: 1 | 2 | 3;
  nb_mots: number;
  title: string;
  type?: string;
}

export interface MotCleDetecte {
  mot: string;
  phrase: string;
  url: string;
}

export interface AllegationDetectee {
  type: string;
  description: string;
  gravite: string;
}

export interface ManquementReglementaire {
  article: string;
  description: string;
}

export interface PreuvesDetectees {
  fdes_epd: 'oui' | 'non' | 'collective' | 'obsolète' | 'inconnue';
  bilan_carbone: 'oui' | 'non' | 'partiel' | 'inconnu';
  beges_ademe: 'oui' | 'non' | 'inconnu';
  strategie_rse: 'oui' | 'non' | 'partiel' | 'inconnu';
  labels: string[];
}

export interface MultiPageItem {
  url: string;
  titre: string;
  priorite: 1 | 2 | 3;
  nb_mots: number;
  mots_cles_detectes: MotCleDetecte[];
  preuves_detectees: PreuvesDetectees;
  allegations_detectees: AllegationDetectee[];
  manquements_reglementaires: ManquementReglementaire[];
  signal_commercial: 'faible' | 'modéré' | 'fort' | 'critique';
}

export interface SyntheseGlobaleMultiPages {
  score_global: number;
  niveau_risque: 'faible' | 'modéré' | 'élevé' | 'critique';
  maturite_rse: 'débutant' | 'intermédiaire' | 'avancé' | 'expert';
  opportunite_commerciale: 'faible' | 'modérée' | 'forte' | 'critique';
  priorite_commerciale: 'A' | 'B' | 'C';
  justification: string;
}

export interface AnalyseMultiPages {
  nb_pages_total: number;
  nb_mots_total: number;
  pages: MultiPageItem[];
  synthese_globale: SyntheseGlobaleMultiPages;
}

export type PilierStatut = 'oui' | 'non' | 'partiel' | 'non_concerne' | 'inconnu';

export interface PilierDetail {
  nom?: string;
  statut: PilierStatut;
  concerne?: boolean;
  pertinent?: boolean;
  info: string;
  lien_page?: string | null;
  titre_page?: string | null;
  extrait?: string | null;
}

export interface PiliersEvaluation {
  bilan_carbone: PilierDetail;
  strategie_rse: PilierDetail;
  compensation_carbone: PilierDetail;
  acv_ecoconception: PilierDetail;
  edp_pep_fdes: PilierDetail;
}

export interface OpportuniteCommerciale {
  presence_fdes_epd: 'oui' | 'non' | 'collective' | 'obsolète' | 'inconnue' | 'non_concerne';
  produits_concernes: string[];
  niveau_opportunite: 'faible' | 'modérée' | 'forte' | 'critique';
  justification: string;
  actions_recommandees: string[];
}

export interface GreenwashingAudit {
  id: string;
  scan_id?: string;
  url: string;
  cleanUrl: string;
  companyName: string;
  sector: string;
  entreprise_secteur?: string;
  entreprise_salaries?: string;
  entreprise_ca?: string;
  type_audit?: 'business' | 'conformité' | 'full';
  liste_pre_audit?: string[];
  headcount?: string;
  turnover?: string;
  region?: string; // ex: 'Auvergne-Rhône-Alpes', 'Île-de-France', etc.
  department?: string; // ex: '63 - Puy-de-Dôme', '69 - Rhône', '75 - Paris', etc.
  siret?: string;
  siren?: string;
  address?: string;
  postalCode?: string;
  city?: string;
  legalForm?: string;
  nafCode?: string;
  pappersUrl?: string;
  analyzedAt: string;
  riskLevel: RiskLevel;
  riskScore: number | null; // 0 = risque maximal, 100 = irréprochable, null = non noté (site indisponible/erreur 301)
  isUnnotable?: boolean;
  unnotableReason?: string;
  isUnreachable?: boolean;
  totalBannedWordsDetected: number;
  detectedClaims: DetectedClaim[];
  businessOpportunities: BusinessOpportunity[];
  clientSummary: ClientQuickSummary;
  commercialProspectingKit: CommercialProspectingKit;
  source: 'interne' | 'widget_wordpress';
  assignedTo?: string; // Collaborator ID
  projet_id?: string;
  prospectStatus: 'nouveau' | 'contacte' | 'relance' | 'rdv_fixe' | 'devis_envoye' | 'signe';
  prospectNotes?: string;
  prospectEmail?: string;
  prospectName?: string;
  prospectPhone?: string;
  leadContact?: LeadContact;
  companyContacts?: CompanyContact[];
  roleAssignments?: RoleAssignment[];
  tasks?: AuditTask[];
  historyLogs?: AuditHistoryLog[];
  analysisQuality?: AnalysisQualitySummary;
  applicableRegulations?: ApplicableRegulationEvaluation[];
  // Official GreenHumble structured analysis format
  score_global?: number | null;
  niveau_risque?: 'faible' | 'modéré' | 'élevé' | 'critique';
  resume?: string;
  pages_analysees?: PageAnalysee[];
  nb_pages_total?: number;
  nb_mots_total?: number;
  mots_cles_detectes?: MotCleDetecte[];
  allegations_detectees?: AllegationDetectee[];
  manquements_reglementaires?: ManquementReglementaire[];
  actions_correctives?: string[];
  opportunite_commerciale?: OpportuniteCommerciale;
  piliers_evaluation?: PiliersEvaluation;
  analyse_multi_pages?: AnalyseMultiPages;
  rawText?: string;
  hasVerifiedFdesEvidence?: boolean;
  consultantFeedback?: {
    originalScore?: number;
    scoreAdjustment?: number;
    adjustedRiskLevel?: RiskLevel;
    generalCorrectionNotes?: string;
    lastReviewedAt?: string;
    reviewedBy?: string;
    unfoundedClaimsCount?: number;
    aiLearningRulesAppended?: string[];
  };
}

export interface Collaborator {
  id: string;
  name: string;
  email: string;
  role: 'admin' | 'pipeline' | 'lecture seule';
  password?: string; // Hashed password
  phone?: string;
  cabinetName?: string;
  avatarColor?: string;
  assignedProspectsCount?: number;
}

export interface LocalCarbonProject {
  id: string;
  title: string;
  location: string;
  type: 'Agroforesterie & Haies' | 'Transition Agricole Durable' | 'Régénération Forestière' | 'Éco-pâturage';
  certification: 'Label Bas-Carbone (Ministère)' | 'Certification Régionale Reconnue';
  carbonStoredTons: number;
  pricePerTon: number; // in €
  description: string;
  partnerFarmers: string;
}

export interface AgencyProfile {
  agencyName: string;
  consultantName: string;
  consultantRole: string;
  agencyEmail: string;
  agencyPhone: string;
  agencyCity: string;
  agencyWebsite: string;
  slogan: string;
  apiKeyWidget?: string;
}

export interface AgencyCabinet {
  id: string;
  cabinetName: string;
  region: string;
  email: string;
  phone: string;
  website: string;
  managerName: string;
}

export interface CustomLegalText {
  id: string;
  title: string;
  reference: string;
  jurisdiction?: 'europe' | 'france' | 'international' | 'sectoriel';
  family?: string;
  link?: string;
  weight?: number; // pondération dans l'analyse globale
  sanction: string;
  description: string;
  isActive: boolean;
}

export interface CustomServiceOpportunity {
  id: string;
  serviceKey: string;
  serviceTitle: string;
  defaultBudget: string;
  subsidyLink?: string;
  subsidyAmount?: string;
  duration?: string;
  description: string;
  isActive: boolean;
}

export interface ProjetRecord {
  id: string;
  domaine: string;
  date_creation: string;
  scans: string[];
  entreprise_nom?: string;
  entreprise_secteur?: string;
  entreprise_ca?: string;
  entreprise_salaries?: string;
}

export interface IASettings {
  model: string;
  temperature: number;
  maxTokens: number;
  systemPrompt: string;
  userPrompt: string;
  dictionaries: string;
  rules: string;
  businessOpportunityParams: string;
  greenClaimsParams: string;
  apiKey?: string;
  provider?: string;
}

export interface ScanRecord {
  id: string;
  url: string;
  type_scan: 'diagnostic' | 'pipeline' | 'widget';
  projet_id: string | null;
  date_scan: string;
  entreprise_secteur?: string;
  entreprise_salaries?: string;
  entreprise_ca?: string;
  resultat_ia: {
    score: number;
    riskLevel?: RiskLevel;
    summary?: string;
    recommendations?: string[];
    raw?: {
      model?: string;
      [key: string]: any;
    };
    claims?: any[];
    entreprise_secteur?: string;
    entreprise_salaries?: string;
    entreprise_ca?: string;
    [key: string]: any;
  };
  statut: string;
  utilisateur: string;
  type_audit?: 'business' | 'conformité' | 'full';
  liste_pre_audit?: string[];
}

export interface ListeRecord {
  id: string;
  nom: string;
  description: string;
  items: string[];
  is_default?: boolean;
  color?: string;
  created_at?: string;
}

export interface ScannerGroupRecord {
  id: string;
  nom: string;
  description: string;
  date_creation?: string;
  is_default?: boolean;
}

export type UserRole = 'admin' | 'pipeline' | 'public';

export interface UserAccount {
  id: string;
  username: string;
  role: UserRole;
  name: string;
  token?: string;
}



