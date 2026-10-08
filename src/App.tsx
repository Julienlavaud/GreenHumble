import React, { useState, useEffect } from 'react';
import { GreenwashingAudit, AgencyProfile, Collaborator, AuditHistoryLog, AgencyCabinet, CustomLegalText, CustomServiceOpportunity, UserRole } from './types.js';
import { initialSampleAudits } from './data/sampleAudits.js';
import { initialCollaborators } from './data/sampleCollaborators.js';
import { 
  createDefaultTasks, 
  createDefaultRoleAssignments, 
  createDefaultHistoryLogs 
} from './utils/auditTracking.js';
import { Header, NavTab } from './components/Header.js';
import { AuditForm } from './components/AuditForm.js';
import { ClientSummaryView } from './components/ClientSummaryView.tsx';
import { ConsultantDeepView } from './components/ConsultantDeepView.tsx';
import { PipelineTracker } from './components/PipelineTracker.js';
import { WordPressIntegrationView } from './components/WordPressIntegrationView.tsx';

import { MethodologyModal } from './components/MethodologyModal.js';
import { AgencySettingsModal } from './components/AgencySettingsModal.js';
import { PrintableReport } from './components/PrintableReport.js';
import { LoginModal } from './components/LoginModal.tsx';
import { AdminConsoleModal } from './components/AdminConsoleModal.tsx';
import { PublicWidgetView } from './components/PublicWidgetView.tsx';
import { MentionsLegalesModal } from './components/MentionsLegalesModal.tsx';
import { DashboardView } from './components/DashboardView.tsx';
import { ScansView } from './components/ScansView.tsx';
import { ProjetsEtListesView } from './components/ProjetsEtListesView.tsx';
import { GroupesView } from './components/GroupesView.tsx';
import { 
  UserCheck, 
  Briefcase, 
  HelpCircle, 
  AlertCircle,
  Globe,
  BellRing,
  X,
  ShieldCheck,
  LogOut,
  Sliders,
  Building2,
  FileSearch
} from 'lucide-react';

const defaultAgency: AgencyProfile = {
  agencyName: 'GreenHumble',
  consultantName: 'Julien Lavaud',
  consultantRole: 'Consultant Fondateur • Climat & Décarbonation',
  agencyEmail: 'contact@climatlocal.com',
  agencyPhone: '04 73 00 00 00',
  agencyCity: 'Auvergne-Rhône-Alpes / France',
  agencyWebsite: 'https://climatlocal.com',
  slogan: 'Restons humbles face à nos responsabilités',
};

function ensureAuditTracking(audit: GreenwashingAudit): GreenwashingAudit {
  return {
    ...audit,
    tasks: audit.tasks && audit.tasks.length > 0 ? audit.tasks : createDefaultTasks(audit.companyName),
    roleAssignments: audit.roleAssignments && audit.roleAssignments.length > 0 ? audit.roleAssignments : createDefaultRoleAssignments(),
    historyLogs: audit.historyLogs && audit.historyLogs.length > 0 ? audit.historyLogs : createDefaultHistoryLogs(audit.companyName),
  };
}

export default function App() {
  // Set title
  useEffect(() => {
    document.title = 'Restons humbles face à nos responsabilités';
  }, []);

  // Authentication state (defaults to Julien Lavaud / Cockpit Métier)
  const [currentUser, setCurrentUser] = useState<Collaborator | null>(() => {
    try {
      const saved = localStorage.getItem('climat_local_current_user');
      if (saved && saved !== 'null' && saved !== 'undefined') {
        const parsed = JSON.parse(saved);
        if (parsed && parsed.name) return parsed;
      }
      return initialCollaborators[0];
    } catch {
      return initialCollaborators[0];
    }
  });

  const [isLoginOpen, setIsLoginOpen] = useState(false);
  const [isAdminOpen, setIsAdminOpen] = useState(false);

  // Passwords map state
  const [passwordsMap, setPasswordsMap] = useState<Record<string, string>>(() => {
    try {
      const saved = localStorage.getItem('climat_local_passwords');
      const loaded = saved ? JSON.parse(saved) : {};
      if (!loaded['collab_1']) {
        loaded['collab_1'] = 'Climatlocal31000';
      }
      return loaded;
    } catch {
      return { 'collab_1': 'Climatlocal31000' };
    }
  });

  // Page texts state
  const [pageTexts, setPageTexts] = useState<Record<string, string>>(() => {
    try {
      const saved = localStorage.getItem('climat_local_page_texts');
      return saved ? JSON.parse(saved) : {
        hero_title: 'Évaluer la sincérité de votre démarche durable',
        widget_subtitle: 'Vérifiez en 30 secondes la conformités de vos supports',
        footer_text: 'Cabinet Climat Local • Bilan Carbone®, Stratégie RSE, ACV, FDES & Projets Territoriaux',
      };
    } catch {
      return {
        hero_title: 'Évaluer la sincérité de votre démarche durable',
        widget_subtitle: 'Vérifiez en 30 secondes la conformités de vos supports',
        footer_text: 'Cabinet Climat Local • Bilan Carbone®, Stratégie RSE, ACV, FDES & Projets Territoriaux',
      };
    }
  });

  // Scan variables state
  const [scanVars, setScanVars] = useState(() => {
    try {
      const saved = localStorage.getItem('climat_local_scan_vars');
      return saved ? JSON.parse(saved) : {
        aiModel: 'gemini-3.1-flash-lite',
        strictnessLevel: 'standard',
        criticalThreshold: 40,
        bpifranceSubsidy: 4000,
        customBannedKeywords: 'neutre en carbone, éco-responsable, zéro émission, compensé carbone, 100% vert, matériau bas carbone',
        aiKeywordInstructions: 'Distinguer les véritables démarches attestées (Bilan Carbone ADEME, FDES sur base INIES, Écolabel européen) des déclarations d’intention. Pour les allégations de neutralité, vérifier la couverture des Scopes 1, 2 et 3 et le recours à des contributions locales plutôt qu’à de la fausse compensation.',
      };
    } catch {
      return {
        aiModel: 'gemini-3.1-flash-lite',
        strictnessLevel: 'standard',
        criticalThreshold: 40,
        bpifranceSubsidy: 4000,
        customBannedKeywords: 'neutre en carbone, éco-responsable, zéro émission, compensé carbone, 100% vert, matériau bas carbone',
        aiKeywordInstructions: 'Distinguer les véritables démarches attestées (Bilan Carbone ADEME, FDES sur base INIES, Écolabel européen) des déclarations d’intention. Pour les allégations de neutralité, vérifier la couverture des Scopes 1, 2 et 3 et le recours à des contributions locales plutôt qu’à de la fausse compensation.',
      };
    }
  });

  // Cabinets state
  const [cabinets, setCabinets] = useState<AgencyCabinet[]>(() => {
    const defaultCabinets: AgencyCabinet[] = [
      { id: 'cab_1', cabinetName: 'Climat Local Siège Auvergne', region: 'Auvergne-Rhône-Alpes (Puy-de-Dôme)', email: 'contact@climatlocal.com', phone: '04 73 00 00 00', website: 'https://climatlocal.com', managerName: 'Julien Lavaud' },
      { id: 'cab_2', cabinetName: 'Climat Local Antenne Métropole de Lyon', region: 'Auvergne-Rhône-Alpes (Rhône)', email: 'lyon@climatlocal.com', phone: '04 72 00 00 00', website: 'https://climatlocal.com/lyon', managerName: 'Sarah Benali' },
      { id: 'cab_3', cabinetName: 'Climat Local Bureau Île-de-France', region: 'Île-de-France (Paris)', email: 'paris@climatlocal.com', phone: '01 40 00 00 00', website: 'https://climatlocal.com/paris', managerName: 'Thomas Roche' },
      { id: 'cab_4', cabinetName: 'Climat Local Antenne Nouvelle-Aquitaine', region: 'Nouvelle-Aquitaine (Bordeaux)', email: 'bordeaux@climatlocal.com', phone: '05 56 00 00 00', website: 'https://climatlocal.com/bordeaux', managerName: 'Camille Dupuis' }
    ];
    try {
      const saved = localStorage.getItem('climat_local_cabinets');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed && Array.isArray(parsed) && parsed.length > 0) return parsed;
      }
      return defaultCabinets;
    } catch {
      return defaultCabinets;
    }
  });

  // Legal texts state
  const [legalTexts, setLegalTexts] = useState<CustomLegalText[]>(() => {
    try {
      const saved = localStorage.getItem('climat_local_legal_texts');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed && parsed.length > 0) return parsed;
      }
    } catch (e) {}

    return [
      {
        id: 'leg_1',
        title: 'Loi Climat & Résilience (Article L229-68)',
        reference: 'Loi n° 2021-1104 / Décret 2022-539',
        sanction: 'Amende jusqu’à 100 000 € (et montant de la publicité)',
        description: 'Interdiction d’affirmer qu’un produit ou service est "neutre en carbone" sans publier un bilan GES complet (Scopes 1, 2, 3) et une trajectoire de réduction.',
        link: 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000045570611',
        weight: 10,
        isActive: true
      },
      {
        id: 'leg_2',
        title: 'Directive EmpCO — Empowering Consumers for Green Transition',
        reference: 'Directive (UE) 2024/825 du 28 février 2024',
        sanction: 'Amendes jusqu’à 4% du CA annuel dans l’UE',
        description: 'Interdiction stricte des allégations environnementales génériques (ex: "vert", "éco", "bon pour la planète") sans excellence environnementale certifiée par un label tiers officiel.',
        link: 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825',
        weight: 9,
        isActive: true
      },
      {
        id: 'leg_3',
        title: 'CSRD — Corporate Sustainability Reporting Directive',
        reference: 'Directive (UE) 2022/2464',
        sanction: 'Jusqu’à 75 000 € d’amende et exclusion de marchés publics',
        description: 'Cadre unifié européen de reporting extra-financier de durabilité standardisé selon les normes ESRS avec audit obligatoire par un OTI.',
        link: 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32022L2464',
        weight: 8,
        isActive: true
      },
      {
        id: 'leg_4',
        title: 'Loi AGEC — Anti-Gaspillage pour une Économie Circulaire',
        reference: 'Loi n° 2020-105 du 10 février 2020',
        sanction: 'Amende de 15 000 € pour les personnes morales',
        description: 'Régulation de l’affichage environnemental, interdiction de la mention "biodégradable", obligations d’info-tri et fin des emballages plastiques.',
        link: 'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000041553759/',
        weight: 8,
        isActive: true
      },
      {
        id: 'leg_5',
        title: 'Décret BEGES — Bilan GES Réglementaire Scope 1-2-3',
        reference: 'Décret n° 2022-982 / Code de l’env. L. 229-25',
        sanction: 'Amende administrative de 10 000 € (20 000 € récidive)',
        description: 'Obligation de réaliser et publier un Bilan d’Émissions de Gaz à Effet de Serre complet incluant obligatoirement le Scope 3 pour les entreprises de plus de 500 salariés.',
        link: 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000046005527',
        weight: 7,
        isActive: true
      },
      {
        id: 'leg_6',
        title: 'Code de la Consommation (Pratiques Commerciales Trompeuses)',
        reference: 'Code de la consommation art. L. 121-2 & L. 132-2',
        sanction: 'Amende de 300 000 € (portée à 10% du CA annuel) et 2 ans de prison',
        description: 'Sanction sévère de tout greenwashing, allégation environnementale fausse ou de nature à induire en erreur sur la performance écologique réelle.',
        link: 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000049303668',
        weight: 10,
        isActive: true
      },
      {
        id: 'leg_7',
        title: 'Directive Green Claims (Allégations explicites)',
        reference: 'Proposition de Directive européenne COM(2023) 166',
        sanction: 'Sanctions financières dissuasives et obligation de rectification',
        description: 'Exigence de vérification préalable ex-ante de chaque allégation environnementale par un organisme tiers accrédité avant toute diffusion marketing.',
        link: 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=COM:2023:166:FIN',
        weight: 8,
        isActive: true
      },
      {
        id: 'leg_8',
        title: 'Règlementation Environnementale RE2020 (ACV Bâtiment)',
        reference: 'Décret n° 2021-1004 (Code construction et habitation)',
        sanction: 'Refus de permis de construire et non-conformité légale',
        description: 'Obligation d’Analyse du Cycle de Vie (ACV) dynamique du bâtiment neuf, imposant aux fabricants de fournir des FDES vérifiées sur INIES.',
        link: 'https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000043876100',
        weight: 7,
        isActive: true
      }
    ];
  });

  // Service opportunities / métiers state
  const [serviceOpps, setServiceOpps] = useState<CustomServiceOpportunity[]>(() => {
    try {
      const saved = localStorage.getItem('climat_local_service_opps');
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed && Array.isArray(parsed) && parsed.length > 0) return parsed;
      }
      return [];
    } catch {
      return [];
    }
  });

  // Load saved agency
  const [agency, setAgency] = useState<AgencyProfile>(() => {
    try {
      const saved = localStorage.getItem('climat_local_agency');
      return saved ? JSON.parse(saved) : defaultAgency;
    } catch {
      return defaultAgency;
    }
  });

  // Load saved audits
  const [audits, setAudits] = useState<GreenwashingAudit[]>(() => {
    try {
      const saved = localStorage.getItem('climat_local_greenwashing_audits');
      const loaded: GreenwashingAudit[] = saved ? JSON.parse(saved) : [];
      return loaded.length > 0 ? loaded.map(ensureAuditTracking) : [];
    } catch {
      return [];
    }
  });

  // Load saved collaborators
  const [collaborators, setCollaborators] = useState<Collaborator[]>(() => {
    try {
      const saved = localStorage.getItem('climat_local_collaborators');
      return saved ? JSON.parse(saved) : initialCollaborators;
    } catch {
      return initialCollaborators;
    }
  });

  // Current selected audit
  const [currentAudit, setCurrentAudit] = useState<GreenwashingAudit | null>(() => {
    return audits.length > 0 ? ensureAuditTracking(audits[0]) : null;
  });

  // Main active navigation tab (Default: 1. Audit)
  const [activeTab, setActiveTab] = useState<NavTab>('audit');

  // Listes de scans
  const [availableListes, setAvailableListes] = useState<any[]>([]);

  const fetchListes = async () => {
    try {
      const res = await fetch('/api/listes');
      if (res.ok) {
        const data = await res.json();
        if (data.success && Array.isArray(data.listes)) {
          setAvailableListes(data.listes);
        }
      }
    } catch (_) {}
  };

  useEffect(() => {
    fetchListes();
  }, []);

  // Commutateur vue simple / vue approfondie
  const [viewMode, setViewMode] = useState<'client' | 'consultant'>('consultant');

  // Modals state
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);
  const [isMethodologyOpen, setIsMethodologyOpen] = useState(false);
  const [isMentionsOpen, setIsMentionsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [newLeadNotification, setNewLeadNotification] = useState<string | null>(null);

  // Sync to localStorage
  useEffect(() => {
    try {
      localStorage.setItem('climat_local_current_user', JSON.stringify(currentUser));
    } catch (e) {}
  }, [currentUser]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_passwords', JSON.stringify(passwordsMap));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ passwordsMap }),
      }).catch(() => {});
    } catch (e) {}
  }, [passwordsMap]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_page_texts', JSON.stringify(pageTexts));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pageTexts }),
      }).catch(() => {});
    } catch (e) {}
  }, [pageTexts]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_cabinets', JSON.stringify(cabinets));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cabinets }),
      }).catch(() => {});
    } catch (e) {}
  }, [cabinets]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_legal_texts', JSON.stringify(legalTexts));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ legalTexts }),
      }).catch(() => {});
    } catch (e) {}
  }, [legalTexts]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_service_opps', JSON.stringify(serviceOpps));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ serviceOpps }),
      }).catch(() => {});
    } catch (e) {}
  }, [serviceOpps]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_scan_vars', JSON.stringify(scanVars));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ scanVars }),
      }).catch(() => {});
    } catch (e) {}
  }, [scanVars]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_agency', JSON.stringify(agency));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ agency }),
      }).catch(() => {});
    } catch (e) {}
  }, [agency]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_greenwashing_audits', JSON.stringify(audits));
    } catch (e) {}
  }, [audits]);

  useEffect(() => {
    try {
      localStorage.setItem('climat_local_collaborators', JSON.stringify(collaborators));
      fetch('/api/state', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ collaborators }),
      }).catch(() => {});
    } catch (e) {}
  }, [collaborators]);

  // Multiposte Server Synchronization: Initial Boot & Periodic Background Poll
  useEffect(() => {
    const syncWithServer = async () => {
      try {
        const res = await fetch('/api/state');
        if (res.ok) {
          const data = await res.json();
          if (data && data.state) {
            const st = data.state;
            if (st.audits && Array.isArray(st.audits)) {
              const trackedAudits = st.audits.map(ensureAuditTracking);
              setAudits(trackedAudits);
              try {
                localStorage.setItem('climat_local_greenwashing_audits', JSON.stringify(trackedAudits));
              } catch (_) {}
            }
            if (st.agency && typeof st.agency === 'object' && st.agency.agencyName) {
              setAgency((prev) => ({ ...prev, ...st.agency }));
            }
            if (st.collaborators && Array.isArray(st.collaborators) && st.collaborators.length > 0) {
              setCollaborators(st.collaborators);
            }
            if (st.cabinets && Array.isArray(st.cabinets) && st.cabinets.length > 0) {
              setCabinets(st.cabinets);
            }
            if (st.scanVars && typeof st.scanVars === 'object' && st.scanVars.aiModel) {
              setScanVars(st.scanVars);
            }
            if (st.legalTexts && Array.isArray(st.legalTexts) && st.legalTexts.length > 0) {
              setLegalTexts(st.legalTexts);
            }
            if (st.serviceOpps && Array.isArray(st.serviceOpps) && st.serviceOpps.length > 0) {
              setServiceOpps(st.serviceOpps);
            }
            if (st.pageTexts && typeof st.pageTexts === 'object') {
              setPageTexts(st.pageTexts);
            }
            if (st.passwordsMap && typeof st.passwordsMap === 'object') {
              setPasswordsMap((prev) => ({ ...prev, ...st.passwordsMap }));
            }
          }
        }
      } catch (e) {
        // Silent catch for background poll
      }
    };

    syncWithServer();
    const interval = setInterval(syncWithServer, 15000);
    return () => clearInterval(interval);
  }, []);

  // Role state (admin, pipeline, public)
  const [userRole, setUserRole] = useState<UserRole>(() => {
    try {
      const savedRole = localStorage.getItem('greenhumble_user_role');
      if (savedRole && ['admin', 'pipeline', 'public'].includes(savedRole)) {
        return savedRole as UserRole;
      }
      return 'admin';
    } catch {
      return 'admin';
    }
  });

  // Login handler
  const handleLogin = async (identifier: string, pass: string): Promise<boolean> => {
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identifier, password: pass })
      });
      
      if (res.ok) {
        const data = await res.json();
        if (data.success && data.user) {
          setCurrentUser(data.user);
          setUserRole(data.user.role === 'admin' ? 'admin' : (data.user.role === 'pipeline' ? 'pipeline' : 'public'));
          localStorage.setItem('greenhumble_user_role', data.user.role);
          localStorage.setItem('greenhumble_jwt_token', 'token_' + Date.now());
          return true;
        }
      }
    } catch (e) {
      console.error('Login error:', e);
    }
    return false;
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setUserRole('public');
    localStorage.setItem('greenhumble_user_role', 'public');
    localStorage.removeItem('greenhumble_jwt_token');
    try {
      localStorage.removeItem('climat_local_current_user');
    } catch (e) {}
  };

  // Filtered audits for the logged-in user ("clients suivi")
  const followedAudits = audits.filter((a) => {
    if (!currentUser) return true;
    if (currentUser.role === 'admin' || currentUser.name === 'Julien Lavaud') return true;
    return a.assignedTo === currentUser.id;
  });

  // Launch audit via backend API
  const handleStartAudit = async (url: string, type_audit: string, liste_pre_audit: string[]) => {
    setIsLoading(true);
    setErrorMessage(null);
    try {
      const token = localStorage.getItem('greenhumble_jwt_token') || sessionStorage.getItem('greenhumble_jwt_token') || '';
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const res = await fetch('/api/audit', {
        method: 'POST',
        headers,
        body: JSON.stringify({
          url,
          agencyProfile: agency,
          scanVars,
          type_audit,
          liste_pre_audit
        }),
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        throw new Error(errorData.error || 'Erreur lors du scan du site web');
      }

      const data = await res.json();
      const newAudit: GreenwashingAudit | undefined = data?.audit || data;
      if (!newAudit || !newAudit.id) {
        throw new Error('Réponse d’audit invalide reçue du serveur.');
      }
      // If user is logged in as collaborator, assign by default to them
      if (currentUser && currentUser.role !== 'admin') {
        newAudit.assignedTo = currentUser.id;
      }

      setCurrentAudit(newAudit);
      setAudits((prev) => [newAudit, ...prev.filter((a) => a.cleanUrl !== (newAudit.cleanUrl || ''))]);
      setActiveTab('audit');
      setViewMode('consultant');
    } catch (err: any) {
      setErrorMessage(err.message || 'Une erreur est survenue lors du scan.');
    } finally {
      setIsLoading(false);
    }
  };

  // Launch document audit via backend API
  const handleStartDocumentAudit = async (doc: {
    documentText: string;
    documentName: string;
    companyName: string;
    type_audit: string;
    liste_pre_audit: string[];
  }) => {
    setIsLoading(true);
    setErrorMessage(null);
    try {
      const token = localStorage.getItem('greenhumble_jwt_token') || sessionStorage.getItem('greenhumble_jwt_token') || '';
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const res = await fetch('/api/audit-document', {
        method: 'POST',
        headers,
        body: JSON.stringify({
          ...doc,
          agencyProfile: agency,
          scanVars,
        }),
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        throw new Error(errorData.error || 'Erreur lors de l’analyse du document');
      }

      const data = await res.json();
      const newAudit: GreenwashingAudit | undefined = data?.audit || data;
      if (!newAudit || !newAudit.id) {
        throw new Error('Réponse d’analyse de document invalide reçue du serveur.');
      }
      if (currentUser && currentUser.role !== 'admin') {
        newAudit.assignedTo = currentUser.id;
      }

      setCurrentAudit(newAudit);
      setAudits((prev) => [newAudit, ...prev.filter((a) => a.id !== (newAudit.id || ''))]);
      setActiveTab('audit');
      setViewMode('consultant');
    } catch (err: any) {
      setErrorMessage(err.message || 'Une erreur est survenue lors de l’analyse du document.');
    } finally {
      setIsLoading(false);
    }
  };

  // Handler when a lead arrives from widget
  const handleNewWidgetLead = (leadAudit: GreenwashingAudit) => {
    setCurrentAudit(leadAudit);
    setAudits((prev) => [leadAudit, ...prev.filter((a) => a.id !== leadAudit.id)]);
    setNewLeadNotification(`Nouveau prospect reçu : ${leadAudit.companyName} (${leadAudit.cleanUrl})`);
    if (currentUser) {
      setActiveTab('audit');
    }
  };

  const handleUpdateStatus = (id: string, status: GreenwashingAudit['prospectStatus'], notes?: string) => {
    const statusLabels: Record<string, string> = {
      nouveau: 'Nouveau prospect',
      contacte: 'Contacté',
      relance: 'Relancé',
      rdv_fixe: 'RDV fixé',
      devis_envoye: 'Devis envoyé',
      signe: 'Mission signée',
    };

    const statusLog: AuditHistoryLog = {
      id: 'log_' + Date.now(),
      timestamp: new Date().toISOString(),
      authorName: currentUser?.name || agency.consultantName,
      actionType: 'status_changed',
      description: `Statut passé à : "${statusLabels[status] || status}"`,
      details: notes ? `Note : ${notes}` : undefined,
    };

    setAudits((prev) =>
      prev.map((a) => {
        if (a.id !== id) return a;
        return {
          ...a,
          prospectStatus: status,
          prospectNotes: notes ?? a.prospectNotes,
          historyLogs: [statusLog, ...(a.historyLogs || [])],
        };
      })
    );

    if (currentAudit && currentAudit.id === id) {
      setCurrentAudit((prev) =>
        prev
          ? {
              ...prev,
              prospectStatus: status,
              prospectNotes: notes ?? prev.prospectNotes,
              historyLogs: [statusLog, ...(prev.historyLogs || [])],
            }
          : null
      );
    }
  };

  const handleAssignAudit = (auditId: string, collaboratorId: string) => {
    const targetCollab = collaborators.find((c) => c.id === collaboratorId);
    const collabName = targetCollab?.name || 'Julien Lavaud';

    const assignLog: AuditHistoryLog = {
      id: 'log_' + Date.now(),
      timestamp: new Date().toISOString(),
      authorName: currentUser?.name || agency.consultantName,
      actionType: 'role_assigned',
      description: `Rôle Consultant Lead attribué à ${collabName} (en attente de validation).`,
      details: 'Attribution effectuée depuis le Cockpit CRM.',
    };

    setAudits((prev) =>
      prev.map((a) => {
        if (a.id !== auditId) return a;
        const currentRoles = a.roleAssignments || createDefaultRoleAssignments();
        const updatedRoles = currentRoles.map((r) =>
          r.roleKey === 'lead_referent'
            ? {
                ...r,
                collaboratorId,
                collaboratorName: collabName,
                status: 'en_attente' as const,
                assignedAt: new Date().toISOString(),
                validatedBy: undefined,
                validatedAt: undefined,
              }
            : r
        );
        return {
          ...a,
          assignedTo: collaboratorId,
          roleAssignments: updatedRoles,
          historyLogs: [assignLog, ...(a.historyLogs || [])],
        };
      })
    );

    if (currentAudit && currentAudit.id === auditId) {
      setCurrentAudit((prev) => {
        if (!prev) return null;
        const currentRoles = prev.roleAssignments || createDefaultRoleAssignments();
        const updatedRoles = currentRoles.map((r) =>
          r.roleKey === 'lead_referent'
            ? {
                ...r,
                collaboratorId,
                collaboratorName: collabName,
                status: 'en_attente' as const,
                assignedAt: new Date().toISOString(),
                validatedBy: undefined,
                validatedAt: undefined,
              }
            : r
        );
        return {
          ...prev,
          assignedTo: collaboratorId,
          roleAssignments: updatedRoles,
          historyLogs: [assignLog, ...(prev.historyLogs || [])],
        };
      });
    }
  };

  const handleUpdateAudit = async (updatedAudit: GreenwashingAudit) => {
    const targetDomain = (updatedAudit.cleanUrl || updatedAudit.url || '')
      .replace(/^https?:\/\//i, '')
      .replace(/^www\./i, '')
      .split('/')[0]
      .split('?')[0]
      .toLowerCase();

    // 1. Update React state across audits
    setAudits((prev) =>
      prev.map((a) => {
        const aDomain = (a.cleanUrl || a.url || '')
          .replace(/^https?:\/\//i, '')
          .replace(/^www\./i, '')
          .split('/')[0]
          .split('?')[0]
          .toLowerCase();
        if (a.id === updatedAudit.id || (targetDomain && aDomain === targetDomain)) {
          return {
            ...a,
            ...updatedAudit,
            riskScore: updatedAudit.riskScore,
            score_global: updatedAudit.riskScore,
            riskLevel: updatedAudit.riskLevel,
            prospectEmail: updatedAudit.prospectEmail || a.prospectEmail,
            prospectPhone: updatedAudit.prospectPhone || a.prospectPhone,
            prospectName: updatedAudit.prospectName || a.prospectName,
            leadContact: updatedAudit.leadContact || a.leadContact,
          };
        }
        return a;
      })
    );

    if (currentAudit) {
      const cDomain = (currentAudit.cleanUrl || currentAudit.url || '')
        .replace(/^https?:\/\//i, '')
        .replace(/^www\./i, '')
        .split('/')[0]
        .split('?')[0]
        .toLowerCase();
      if (currentAudit.id === updatedAudit.id || (targetDomain && cDomain === targetDomain)) {
        setCurrentAudit(updatedAudit);
      }
    }

    // 2. Persist to localStorage
    try {
      const stored = JSON.parse(localStorage.getItem('climat_local_greenwashing_audits') || '[]');
      let matchFound = false;
      const newStored = stored.map((a: any) => {
        const aDomain = (a.cleanUrl || a.url || '')
          .replace(/^https?:\/\//i, '')
          .replace(/^www\./i, '')
          .split('/')[0]
          .split('?')[0]
          .toLowerCase();
        if (a.id === updatedAudit.id || (targetDomain && aDomain === targetDomain)) {
          matchFound = true;
          return { ...a, ...updatedAudit };
        }
        return a;
      });
      if (!matchFound) {
        newStored.unshift(updatedAudit);
      }
      localStorage.setItem('climat_local_greenwashing_audits', JSON.stringify(newStored));
    } catch (_) {}

    // 3. Persist to server backend storage and scans.json
    try {
      await fetch('/api/audit/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ audit: updatedAudit })
      });
    } catch (e) {
      console.warn('Error updating audit on backend:', e);
    }

    // 4. Broadcast global event so all views (ScansView, Dashboard, etc.) refresh in unison
    window.dispatchEvent(new CustomEvent('greenhumble_data_changed', { detail: { action: 'update', audit: updatedAudit } }));
  };

  const handleDeleteAudit = async (id: string) => {
    const targetAudit = audits.find(a => a.id === id);
    const cleanUrl = targetAudit?.cleanUrl;

    // 1. Remove from React state
    setAudits((prev) => prev.filter((a) => a.id !== id && (!cleanUrl || a.cleanUrl !== cleanUrl)));
    if (currentAudit && (currentAudit.id === id || (cleanUrl && currentAudit.cleanUrl === cleanUrl))) {
      const remaining = audits.filter((a) => a.id !== id && (!cleanUrl || a.cleanUrl !== cleanUrl));
      setCurrentAudit(remaining.length > 0 ? remaining[0] : null);
    }

    // 2. Clean localStorage audits
    try {
      const savedAudits = localStorage.getItem('climat_local_greenwashing_audits');
      if (savedAudits) {
        const parsed = JSON.parse(savedAudits);
        const filtered = parsed.filter((a: any) => a.id !== id && (!cleanUrl || a.cleanUrl !== cleanUrl));
        localStorage.setItem('climat_local_greenwashing_audits', JSON.stringify(filtered));
      }
    } catch (_) {}

    // 3. Clean localStorage projets & listes
    try {
      const savedProjets = localStorage.getItem('climat_local_projets');
      if (savedProjets) {
        const parsed = JSON.parse(savedProjets);
        parsed.forEach((p: any) => {
          if (Array.isArray(p.scans)) {
            p.scans = p.scans.filter((s: string) => s !== id && (!cleanUrl || !s.includes(cleanUrl)));
          }
        });
        localStorage.setItem('climat_local_projets', JSON.stringify(parsed));
      }

      const savedListes = localStorage.getItem('climat_local_listes');
      if (savedListes) {
        const parsed = JSON.parse(savedListes);
        parsed.forEach((l: any) => {
          if (Array.isArray(l.items)) {
            l.items = l.items.filter((item: string) => item !== id && (!cleanUrl || !item.includes(cleanUrl)));
          }
        });
        localStorage.setItem('climat_local_listes', JSON.stringify(parsed));
      }
    } catch (_) {}

    // 4. Send server delete request
    try {
      await fetch('/api/scans/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      await fetch(`/api/audit?id=${id}`, { method: 'DELETE' });
    } catch (_) {}

    // 5. Broadcast global event so all mounted views refresh in unison
    window.dispatchEvent(new CustomEvent('greenhumble_data_changed', { detail: { action: 'delete', id } }));
  };

  const handleClearAllData = () => {
    setAudits([]);
    setCurrentAudit(null);
    try {
      localStorage.removeItem('climat_local_greenwashing_audits');
      localStorage.removeItem('climat_local_scans');
      localStorage.removeItem('climat_local_projets');
      localStorage.removeItem('climat_local_scanner_groups');
      localStorage.removeItem('climat_local_current_audit');
    } catch (_) {}
  };

  const handleSelectAudit = (selected: GreenwashingAudit) => {
    setCurrentAudit(selected);
    setActiveTab('audit');
  };

  const handleAddCollaborator = (newCollab: Omit<Collaborator, 'id'>) => {
    const created: Collaborator = {
      ...newCollab,
      id: 'collab_' + Date.now(),
    };
    setCollaborators((prev) => [...prev, created]);
  };

  const handleDeleteCollaborator = (collabId: string) => {
    setCollaborators((prev) => prev.filter((c) => c.id !== collabId));
  };

  const handlePrint = () => {
    window.print();
  };

  // IF NO USER IS LOGGED IN OR PUBLIC ROLE, SHOW PUBLIC WIDGET VIEW
  if (!currentUser || userRole === 'public') {
    return (
      <>
        <PublicWidgetView
          agency={agency}
          pageTexts={pageTexts}
          onOpenLogin={() => setIsLoginOpen(true)}
          onAuditCompleted={(audit) => {
            handleNewWidgetLead(audit);
          }}
        />

        <LoginModal
          isOpen={isLoginOpen}
          onClose={() => setIsLoginOpen(false)}
          onLogin={handleLogin}
          onSwitchToPublic={() => {
            setUserRole('public');
            setCurrentUser(null);
            setIsLoginOpen(false);
          }}
        />
      </>
    );
  }

  // OTHERWISE, SHOW THE PROFESSIONAL COCKPIT (AUTHENTICATED)
  return (
    <div className="min-h-screen bg-[#f8fafc] text-slate-800 font-sans flex flex-col selection:bg-blue-600 selection:text-white">
      {/* Top Navigation */}
      <Header
        activeTab={activeTab}
        setActiveTab={(tab) => {
          setActiveTab(tab);
        }}
        agency={agency}
        userName={currentUser?.name}
        userRole={userRole}
        onOpenSettings={() => setIsSettingsOpen(true)}
        onOpenMethodology={() => setIsMethodologyOpen(true)}
        onOpenAdmin={() => setIsAdminOpen(true)}
        onOpenLogin={() => setIsLoginOpen(true)}
        onLogout={handleLogout}
        onPrint={handlePrint}
        hasCurrentAudit={!!currentAudit}
        auditsCount={followedAudits.length}
      />

      {/* Logged in user info & Admin console trigger bar */}
      <div className="bg-slate-100 text-slate-700 border-b border-slate-200 text-xs py-2 px-4 sm:px-8 no-print">
        <div className="max-w-[1850px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
          <div className="flex items-center space-x-2">
            <span className="w-2 h-2 rounded-full bg-emerald-600" />
            <span className="text-xs">Connecté : <strong className="text-slate-900">{currentUser.name}</strong> ({currentUser.role})</span>
          </div>

          <div className="flex items-center space-x-3">
            <button
              onClick={() => setIsAdminOpen(true)}
              className="text-emerald-700 hover:text-emerald-800 font-semibold text-xs transition-colors flex items-center space-x-1.5"
            >
              <Sliders className="w-3.5 h-3.5" />
              <span>Console Admin</span>
            </button>

            <button
              onClick={handleLogout}
              className="px-2.5 py-1 bg-white hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-md font-semibold flex items-center space-x-1.5 transition-colors shadow-2xs"
            >
              <LogOut className="w-3.5 h-3.5 text-rose-500" />
              <span>Déconnexion</span>
            </button>
          </div>
        </div>
      </div>

      {/* Main Body */}
      <main className="flex-1 max-w-[1850px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 pb-24 lg:pb-10">
        {/* Real-time incoming lead toast notification */}
        {newLeadNotification && (
          <div className="mb-6 p-4 rounded-2xl bg-teal-900 text-white flex items-center justify-between shadow-lg animate-fadeIn border border-teal-700 no-print">
            <div className="flex items-center space-x-3">
              <div className="w-9 h-9 rounded-xl bg-teal-800 flex items-center justify-center shrink-0">
                <BellRing className="w-5 h-5 text-emerald-300 animate-bounce" />
              </div>
              <div>
                <span className="text-xs font-bold uppercase tracking-wider text-emerald-300 block">
                  Nouveau Lead Entrant climatlocal.com
                </span>
                <p className="text-xs sm:text-sm font-semibold text-white">{newLeadNotification}</p>
              </div>
            </div>
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setActiveTab('scans')}
                className="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-colors"
              >
                Voir dans les scans
              </button>
              <button
                onClick={() => setNewLeadNotification(null)}
                className="p-1 rounded-lg hover:bg-teal-800 text-teal-200"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}

        {/* Error notification */}
        {errorMessage && (
          <div className="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start justify-between no-print">
            <div className="flex items-start space-x-3">
              <AlertCircle className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
              <div>
                <span className="font-bold block text-sm">Erreur d’analyse :</span>
                <p className="text-xs">{errorMessage}</p>
              </div>
            </div>
            <button
              onClick={() => setErrorMessage(null)}
              className="text-xs font-bold text-rose-600 hover:text-rose-900 ml-4"
            >
              Fermer
            </button>
          </div>
        )}

        {/* TAB 1: AUDIT */}
        {activeTab === 'audit' && (
          <div className="no-print">
            <AuditForm
              onStartAudit={handleStartAudit}
              onStartDocumentAudit={handleStartDocumentAudit}
              isLoading={isLoading}
              agency={agency}
              collaborators={collaborators}
              audits={followedAudits}
              onSelectAudit={(selected) => {
                handleSelectAudit(selected);
                setActiveTab('prospection');
              }}
              onDeleteAudit={handleDeleteAudit}
              onUpdateStatus={handleUpdateStatus}
              onUpdateAudit={handleUpdateAudit}
            />
          </div>
        )}

        {/* TAB 2: PROSPECTION (DOSSIER CONSULTANT & KIT COMMERCIAL B2B) */}
        {activeTab === 'prospection' && (
          <div className="no-print animate-fadeIn">
            {(currentAudit || followedAudits.length > 0) ? (
              <ConsultantDeepView
                audit={currentAudit || followedAudits[0]}
                allAudits={followedAudits}
                agency={agency}
                collaborators={collaborators}
                listes={availableListes}
                onPrint={handlePrint}
                onOpenMethodology={() => setIsMethodologyOpen(true)}
                onSelectAudit={(selected) => setCurrentAudit(selected)}
                onUpdateAudit={handleUpdateAudit}
                onUpdateStatus={handleUpdateStatus}
                onDeleteAudit={handleDeleteAudit}
                onSwitchToPipeline={() => setActiveTab('dashboard')}
                onAddToList={(listId, scanId) => {
                  fetch('/api/listes/toggle-item', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ listId, itemId: scanId })
                  }).then(() => fetchListes());
                }}
              />
            ) : (
              <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-4 max-w-2xl mx-auto shadow-sm">
                <div className="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto border border-indigo-100">
                  <Briefcase className="w-7 h-7" />
                </div>
                <h3 className="text-lg font-bold text-slate-900">Aucun dossier de prospection sélectionné</h3>
                <p className="text-xs text-slate-500">
                  Sélectionnez un scan pour accéder au kit commercial complet (pitchs téléphoniques DG, emails types et offres B2B subventionnées).
                </p>
                <div className="pt-2 flex justify-center space-x-3">
                  <button
                    onClick={() => setActiveTab('audit')}
                    className="px-5 py-2.5 rounded-xl bg-[#1e40af] text-white font-bold text-xs hover:bg-[#1d4ed8] transition-all cursor-pointer shadow-sm"
                  >
                    Lancer un Audit
                  </button>
                  <button
                    onClick={() => setActiveTab('scans')}
                    className="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all cursor-pointer border border-slate-200"
                  >
                    Consulter les Scans
                  </button>
                </div>
              </div>
            )}
          </div>
        )}

        {/* TAB 3: TABLEAU DE BORD & PIPELINE (UNIFIÉS) */}
        {(activeTab === 'dashboard' || activeTab === 'pipeline') && (
          <div className="no-print animate-fadeIn">
            <DashboardView
              audits={followedAudits}
              collaborators={collaborators}
              onSelectAudit={(selected) => {
                handleSelectAudit(selected);
                setActiveTab('prospection');
              }}
              onNavigateToProspection={(selected) => {
                handleSelectAudit(selected);
                setActiveTab('prospection');
              }}
              onNavigateToScans={() => setActiveTab('scans')}
              onStartAudit={() => setActiveTab('audit')}
              onUpdateStatus={handleUpdateStatus}
              onDeleteAudit={handleDeleteAudit}
              onAssignAudit={handleAssignAudit}
            />
          </div>
        )}

        {/* TAB 4: SCANS (RECHERCHE MULTI-NOTES & HISTORIQUE) */}
        {activeTab === 'scans' && (
          <div className="no-print animate-fadeIn">
            <ScansView
              audits={followedAudits}
              onSelectAudit={(selected) => {
                handleSelectAudit(selected);
                setActiveTab('prospection');
              }}
              onDeleteAudit={handleDeleteAudit}
            />
          </div>
        )}

        {/* TAB 5: LISTES DE SCANS */}
        {(activeTab === 'listes' || activeTab === 'projets' || activeTab === 'groupes') && (
          <div className="no-print animate-fadeIn">
            <ProjetsEtListesView
              audits={followedAudits}
              onOpenAuditForDomain={(domain) => {
                handleStartAudit(domain, 'full', []);
                setActiveTab('audit');
              }}
              onSelectAudit={(selected) => {
                handleSelectAudit(selected);
                setActiveTab('prospection');
              }}
            />
          </div>
        )}

        {/* Dedicated Printable Report layout (rendered only when printing) */}
        {currentAudit && <PrintableReport audit={currentAudit} agency={agency} />}
      </main>

      {/* Footer (Thème sobre éco-conception) */}
      <footer className="mt-auto border-t border-slate-200 bg-slate-100 py-6 text-center text-xs text-slate-600 no-print">
        <div className="max-w-[1850px] mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="flex flex-wrap items-center space-x-2">
            <span className="font-bold text-slate-900">GreenHumble</span>
            <span>•</span>
            <span className="font-medium text-slate-600">Une marque Climat Local</span>
            <span>•</span>
            <a href="https://climatlocal.com" target="_blank" rel="noreferrer" className="text-[#1e40af] font-bold hover:underline">
              www.climatlocal.com
            </a>
            <span>•</span>
            <span className="inline-flex items-center text-emerald-800 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
              🌱 Site éco-conçu : 1,5g CO2e / consultation (Note A+)
            </span>
          </div>
          <div className="flex items-center space-x-4">
            <button
              onClick={() => setIsMentionsOpen(true)}
              className="hover:text-[#1e40af] underline font-semibold cursor-pointer"
            >
              Mentions Légales & RGPD
            </button>
            <button
              onClick={() => setIsMethodologyOpen(true)}
              className="hover:text-[#1e40af] underline cursor-pointer"
            >
              Directive (UE) 2024/825 & Mots Bannis
            </button>
            <button
              onClick={() => setIsSettingsOpen(true)}
              className="hover:text-[#2A7AE2] underline cursor-pointer"
            >
              Paramètres Cabinet
            </button>
          </div>
        </div>
      </footer>

      {/* Modals */}
      <MentionsLegalesModal
        isOpen={isMentionsOpen}
        onClose={() => setIsMentionsOpen(false)}
      />

      {/* Modals */}
      <MethodologyModal
        isOpen={isMethodologyOpen}
        onClose={() => setIsMethodologyOpen(false)}
      />

      <AgencySettingsModal
        isOpen={isSettingsOpen}
        onClose={() => setIsSettingsOpen(false)}
        agency={agency}
        onSave={(updated) => setAgency(updated)}
      />

      <AdminConsoleModal
        isOpen={isAdminOpen}
        onClose={() => setIsAdminOpen(false)}
        onOpenServerAdmin={() => {}}
        onClearAllData={handleClearAllData}
      />
    </div>
  );
}
