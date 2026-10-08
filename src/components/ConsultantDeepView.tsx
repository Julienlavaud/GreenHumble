import React, { useState, useEffect, useMemo } from 'react';
import { 
  GreenwashingAudit, 
  AgencyProfile, 
  ClimatLocalServiceKey, 
  Collaborator,
  CompanyContact,
  ApplicableRegulationEvaluation
} from '../types.js';
import { ScoreGauge } from './ScoreGauge.js';
import { PiliersRseView } from './PiliersRseView.js';
import { CommercialScriptGeneratorModal } from './CommercialScriptGeneratorModal.js';
import { getLawArticleLink } from '../utils/ecoClaimWords.js';
import {
  FileCode,
  FileSpreadsheet,
  Printer,
  Copy,
  Check,
  ShieldAlert,
  AlertTriangle,
  Scale,
  Briefcase,
  PhoneCall,
  ChevronDown,
  Info,
  ExternalLink,
  Target,
  FileCheck,
  Layers,
  Leaf,
  Compass,
  GraduationCap,
  Trees,
  ClipboardList,
  CheckCircle2,
  Building2,
  User,
  Mail,
  Phone,
  X,
  Plus,
  Sparkles,
  Loader2,
  Eye,
  Save,
  ChevronRight,
  Kanban,
  MessageSquare,
  Search,
  Filter,
  HelpCircle,
  Trash2,
  Pencil,
  HeartHandshake,
  ShieldCheck,
  TrendingUp,
  Send
} from 'lucide-react';

interface ConsultantDeepViewProps {
  audit: GreenwashingAudit;
  allAudits?: GreenwashingAudit[];
  agency: AgencyProfile;
  collaborators?: Collaborator[];
  listes?: any[];
  onPrint: () => void;
  onOpenMethodology: () => void;
  onSelectAudit?: (audit: GreenwashingAudit) => void;
  onUpdateAudit?: (updatedAudit: GreenwashingAudit) => void;
  onUpdateStatus?: (id: string, status: GreenwashingAudit['prospectStatus'], notes?: string) => void;
  onSwitchToPipeline?: () => void;
  onDeleteAudit?: (id: string) => void;
  onAddToList?: (listId: string, scanId: string) => void;
  initialTab?: 'all' | 'piliers' | 'claims' | 'business' | 'commercial';
  onChangeTab?: (tab: 'all' | 'piliers' | 'claims' | 'business' | 'commercial') => void;
}

const serviceIcons: Record<ClimatLocalServiceKey, { label: string; icon: any; color: string }> = {
  bilan_carbone: { label: 'Bilan Carbone® (Scopes 1, 2, 3)', icon: Target, color: 'text-emerald-700 bg-emerald-100' },
  evaluation_bilan_carbone: { label: 'Évaluation & Revue du Bilan Carbone', icon: Target, color: 'text-amber-800 bg-amber-100' },
  credits_carbone_locaux: { label: 'Financement Crédits Carbone LBC (Acheteur)', icon: Leaf, color: 'text-emerald-800 bg-emerald-200' },
  depot_projet_lbc: { label: 'Dépôt de Projet Label Bas-Carbone (Porteur LBC)', icon: Trees, color: 'text-teal-900 bg-teal-200 border border-teal-400' },
  strategie_rse: { label: 'Stratégie RSE & Reporting CSRD', icon: Layers, color: 'text-teal-700 bg-teal-100' },
  raison_d_etre: { label: 'Raison d’Être & Société à Mission', icon: Compass, color: 'text-amber-800 bg-amber-100' },
  sensibilisation_equipes: { label: 'Sensibilisation & Ateliers Climat', icon: GraduationCap, color: 'text-purple-800 bg-purple-100' },
  acv: { label: 'ACV Produit (ISO 14040/44)', icon: Leaf, color: 'text-cyan-700 bg-cyan-100' },
  fdes_epd: { label: 'FDES & EPD (Matériaux RE2020)', icon: FileCheck, color: 'text-indigo-700 bg-indigo-100' },
  biodiversite_locale: { label: 'Biodiversité & Foncier Responsable', icon: Trees, color: 'text-green-800 bg-green-100' },
};

const PIPELINE_STATUSES: Array<{
  key: NonNullable<GreenwashingAudit['prospectStatus']>;
  label: string;
  icon: string;
  color: string;
}> = [
  { key: 'nouveau', label: 'Nouveau', icon: '📥', color: 'bg-slate-800 text-slate-200 border-slate-700 hover:bg-slate-700' },
  { key: 'contacte', label: 'Contacté', icon: '💬', color: 'bg-blue-950 text-blue-300 border-blue-800 hover:bg-blue-900' },
  { key: 'relance', label: 'Relancé', icon: '⏳', color: 'bg-amber-950 text-amber-300 border-amber-800 hover:bg-amber-900' },
  { key: 'rdv_fixe', label: 'RDV Fixé', icon: '📊', color: 'bg-purple-950 text-purple-300 border-purple-800 hover:bg-purple-900' },
  { key: 'devis_envoye', label: 'Devis Envoyé', icon: '📝', color: 'bg-indigo-950 text-indigo-300 border-indigo-800 hover:bg-indigo-900' },
  { key: 'signe', label: 'Signé !', icon: '✍️', color: 'bg-emerald-950 text-emerald-300 border-emerald-700 hover:bg-emerald-900' },
];

const CLIMAT_LOCAL_CORE_OBJECTIONS = [
  {
    objection: "On ne fait pas de greenwashing, nous sommes sincères et de bonne foi.",
    answer: "Nous le savons parfaitement et vos actions concrètes le démontrent. Le piège de la nouvelle Directive Green Claims européenne (UE 2024/825) et des contrôles DGCCRF est qu'ils sanctionnent désormais les maladresses de vocabulaire non vérifiées, même chez les entreprises les plus vertueuses. Notre rôle chez Climat Local est de vous protéger avec un bouclier juridique étayé."
  },
  {
    objection: "Nous n'avons aucun budget RSE ou transition cette année.",
    answer: "C'est précisément l'atout du programme Diag Décarbon’Action (Bpifrance / ADEME) que nous mobilisons : l'État finance 4 000 € d'aide directe sur un montant global de prestation de 10 000 € HT. Votre reste à charge net est ramené à seulement 6 000 € HT pour un Bilan Carbone complet scopes 1-2-3 et un plan d'actions certifié. C'est un investissement stratégique, rentabilisé dès votre premier appel d'offres."
  },
  {
    objection: "Nos clients et donneurs d'ordres ne nous demandent rien sur le carbone.",
    answer: "La directive européenne CSRD impose aux donneurs d'ordres de publier le bilan carbone de l'ensemble de leur chaîne de valeur (Scope 3). Les critères RSE sont devenus éliminatoires dans plus de 60% des consultations. Vous positionner dès aujourd'hui comme un partenaire exemplaire vous donne une avance décisive sur vos concurrents."
  },
  {
    objection: "Nous compensons déjà nos émissions avec des plantations d'arbres à l'international.",
    answer: "Le Décret n° 2022-539 interdit strictement de prétendre à la 'neutralité carbone' par de la compensation sans réduction préalable et sans publication scientifique. Chez Climat Local, nous réorientons vos contributions vers des projets labellisés Label Bas-Carbone (LBC) par l'État français sur nos territoires (agriculture bas-carbone, forêts durables), 100% vérifiables et valorisables localement."
  },
  {
    objection: "Nous avons déjà fait un Bilan Carbone il y a 3 ou 4 ans.",
    answer: "C'est un excellent socle ! La réglementation exige cependant une mise à jour régulière et surtout un plan de décarbonation chiffré à horizon 2030. Nous intervenons en revue critique rapide pour actualiser vos facteurs d'émissions, sécuriser vos allégations marketing et mobiliser les subventions Bpifrance."
  },
  {
    objection: "Notre entreprise est trop petite pour être concernée par ces obligations.",
    answer: "Bien que non assujetties directement aux rapports CSRD lourds, les PME sont impactées par cascade via les exigences des banques (critères ESG pour les financements) et les grilles d'évaluation des donneurs d'ordres (critère environnemental souvent pondéré à 15-25% de la note finale)."
  }
];

export const ConsultantDeepView: React.FC<ConsultantDeepViewProps> = ({
  audit,
  allAudits = [],
  agency,
  collaborators = [],
  listes = [],
  onPrint,
  onOpenMethodology,
  onSelectAudit,
  onUpdateAudit,
  onUpdateStatus,
  onSwitchToPipeline,
  onDeleteAudit,
  onAddToList,
  initialTab = 'all',
  onChangeTab,
}) => {
  const [activeTab, setActiveTab] = useState<'all' | 'piliers' | 'claims' | 'business' | 'commercial'>(initialTab as any);
  const [copiedEmailKey, setCopiedEmailKey] = useState<string | null>(null);
  const [copiedPitch, setCopiedPitch] = useState(false);
  const [addedToProjectMsg, setAddedToProjectMsg] = useState<string | null>(null);
  const [isScriptGeneratorOpen, setIsScriptGeneratorOpen] = useState(false);
  const [showCommercialGuide, setShowCommercialGuide] = useState<boolean>(true);

  // Edit Prospect Contact Modal state
  const [isContactModalOpen, setIsContactModalOpen] = useState<boolean>(false);
  const [contactNameInput, setContactNameInput] = useState<string>('');
  const [contactEmailInput, setContactEmailInput] = useState<string>('');
  const [contactPhoneInput, setContactPhoneInput] = useState<string>('');
  const [contactRoleInput, setContactRoleInput] = useState<string>('');
  const [contactNotesInput, setContactNotesInput] = useState<string>('');

  const domainClean = (audit.cleanUrl || audit.url || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0];
  const effectiveEmail = audit.prospectEmail || audit.leadContact?.email || audit.companyContacts?.[0]?.email || `contact@${domainClean}`;
  const effectivePhone = audit.prospectPhone || audit.leadContact?.phone || audit.companyContacts?.[0]?.phone || '04 73 00 00 00';
  const effectiveName = audit.prospectName || audit.leadContact?.name || audit.companyContacts?.[0]?.name || 'Direction / Responsable RSE';
  const effectiveRole = audit.leadContact?.role || audit.companyContacts?.[0]?.roleTitle || 'Direction Générale / RSE';

  // Auto-remplissage proactif des coordonnées prospect pour qu'elles ne soient jamais vides
  useEffect(() => {
    if ((!audit.prospectEmail || !audit.prospectPhone || !audit.prospectName) && onUpdateAudit) {
      const updatedAudit: GreenwashingAudit = {
        ...audit,
        prospectEmail: effectiveEmail,
        prospectPhone: effectivePhone,
        prospectName: effectiveName,
        leadContact: audit.leadContact || {
          name: effectiveName,
          email: effectiveEmail,
          phone: effectivePhone,
          role: effectiveRole,
        },
      };
      onUpdateAudit(updatedAudit);
    }
  }, [audit.id]);

  const handleOpenContactModal = () => {
    setContactNameInput(effectiveName);
    setContactEmailInput(effectiveEmail);
    setContactPhoneInput(effectivePhone);
    setContactRoleInput(effectiveRole);
    setContactNotesInput(audit.prospectNotes || '');
    setIsContactModalOpen(true);
  };

  const handleSaveContactModal = async () => {
    const updatedLead = {
      name: contactNameInput.trim() || 'Direction / Responsable RSE',
      email: contactEmailInput.trim() || `contact@${domainClean}`,
      phone: contactPhoneInput.trim() || '04 73 00 00 00',
      role: contactRoleInput.trim() || 'Direction & RSE'
    };

    const updatedAudit: GreenwashingAudit = {
      ...audit,
      prospectName: updatedLead.name,
      prospectEmail: updatedLead.email,
      prospectPhone: updatedLead.phone,
      prospectNotes: contactNotesInput.trim(),
      leadContact: updatedLead,
      companyContacts: audit.companyContacts && audit.companyContacts.length > 0
        ? audit.companyContacts.map((c, i) => i === 0 ? {
            ...c,
            name: updatedLead.name,
            email: updatedLead.email,
            phone: updatedLead.phone,
            roleTitle: updatedLead.role
          } : c)
        : [
            {
              id: 'c_1',
              name: updatedLead.name,
              email: updatedLead.email,
              phone: updatedLead.phone,
              roleTitle: updatedLead.role,
              contactType: 'RSE'
            }
          ]
    };

    if (onUpdateAudit) {
      onUpdateAudit(updatedAudit);
    }

    try {
      await fetch('/api/audit/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ audit: updatedAudit })
      });
    } catch (_) {}

    setIsContactModalOpen(false);
  };

  const handleSelectSuggestedContact = async (contact: any) => {
    const updatedLead = {
      name: contact.name,
      email: contact.email,
      phone: contact.phone,
      role: contact.roleTitle || contact.contactType || 'Direction & RSE'
    };

    const updatedAudit: GreenwashingAudit = {
      ...audit,
      prospectName: updatedLead.name,
      prospectEmail: updatedLead.email,
      prospectPhone: updatedLead.phone,
      leadContact: updatedLead
    };

    if (onUpdateAudit) {
      onUpdateAudit(updatedAudit);
    }

    try {
      await fetch('/api/audit/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ audit: updatedAudit })
      });
    } catch (_) {}
  };

  // AI Claim Correction Modal state
  const [editingClaim, setEditingClaim] = useState<any | null>(null);
  const [correctionReason, setCorrectionReason] = useState<string>(
    "Relation commerciale / relationnel client durable (ne concerne pas un produit ou impact écologique)"
  );
  const [trainAiRule, setTrainAiRule] = useState<boolean>(true);
  const [isSubmittingCorrection, setIsSubmittingCorrection] = useState<boolean>(false);
  const [correctionMsg, setCorrectionMsg] = useState<string | null>(null);

  const handleOpenCorrectionModal = (claim: any) => {
    setEditingClaim(claim);
    if (claim.quoteInText?.toLowerCase().includes('relation') || claim.quoteInText?.toLowerCase().includes('client')) {
      setCorrectionReason("Relation commerciale / relationnel client durable (ne concerne pas un produit ou impact écologique)");
    } else {
      setCorrectionReason("Faux positif — Contexte sémantique non environnemental");
    }
  };

  const handleApplyCorrection = async () => {
    if (!editingClaim) return;
    setIsSubmittingCorrection(true);

    try {
      // 1. Remove or disqualify corrected claim
      const updatedClaims = (audit.detectedClaims || []).filter(c => 
        (c.id && editingClaim.id) ? c.id !== editingClaim.id : c.term !== editingClaim.term
      );

      // 2. Dynamic Score Recalculation
      let newScore = 90;
      if (updatedClaims.length === 0) {
        newScore = 95;
      } else {
        const penalty = updatedClaims.reduce((acc, c) => {
          if (c.severity === 'interdit_strict' || c.severity === 'haut_risque') return acc + 25;
          if (c.severity === 'trompeur_sans_preuve') return acc + 15;
          return acc + 10;
        }, 0);
        newScore = Math.max(20, Math.min(95, 100 - penalty));
      }

      const newLevel = newScore >= 70 ? 'faible' : newScore >= 40 ? 'modere' : 'critique';

      // 3. Updated audit object with score_global and level synchronized
      const updatedAudit: GreenwashingAudit = {
        ...audit,
        riskScore: newScore,
        score_global: newScore,
        riskLevel: newLevel as any,
        niveau_risque: (newLevel === 'modere' ? 'modéré' : (newLevel as string) === 'eleve' ? 'élevé' : newLevel) as any,
        totalBannedWordsDetected: updatedClaims.length,
        detectedClaims: updatedClaims,
        clientSummary: {
          ...audit.clientSummary,
          headline: updatedClaims.length === 0 
            ? 'Diagnostic Réévalué : Aucune allégation écologique non conforme' 
            : audit.clientSummary?.headline || 'Audit réévalué',
          riskVerdict: updatedClaims.length === 0
            ? `Correction manuelle appliquée : l’expression "${editingClaim.term}" relevait du contexte relationnel/commercial et non d’une allégation produit. La note globale a été mise à jour à ${newScore}/100.`
            : audit.clientSummary?.riskVerdict || 'Conformité réévaluée'
        }
      };

      // 4. Send backend feedback for AI Training & persist score in stored audits & scans
      await fetch('/api/ai/correct-claim', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          scanId: audit.id,
          newScore,
          updatedAudit,
          updatedClaims,
          term: editingClaim.term,
          quote: editingClaim.quoteInText,
          reason: correctionReason,
          trainAi: trainAiRule
        })
      }).catch(() => {});

      // 5. Explicitly persist to server audit update endpoint
      await fetch('/api/audit/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ audit: updatedAudit })
      }).catch(() => {});

      // 6. Update local state & localStorage
      if (onUpdateAudit) {
        onUpdateAudit(updatedAudit);
      }

      setCorrectionMsg(`Correction appliquée avec succès ! L’allégation a été annulée, la note globale réévaluée à ${newScore}/100 et l’apprentissage IA enregistré.`);
      setEditingClaim(null);
      setTimeout(() => setCorrectionMsg(null), 5000);
    } catch (_) {
      setCorrectionMsg('Erreur lors de la réévaluation.');
      setTimeout(() => setCorrectionMsg(null), 4000);
    } finally {
      setIsSubmittingCorrection(false);
    }
  };

  const handleKitUpdated = (newKit: any) => {
    if (onUpdateAudit) {
      onUpdateAudit({
        ...audit,
        commercialProspectingKit: newKit
      });
    }
  };

  // Filter and company picker states
  const [selectedListFilter, setSelectedListFilter] = useState<string>('all');
  const [companySearch, setCompanySearch] = useState<string>('');

  // Note dialog state
  const [isAddingNote, setIsAddingNote] = useState(false);
  const [noteContent, setNoteContent] = useState('');

  useEffect(() => {
    if (initialTab && initialTab !== 'all') {
      setActiveTab(initialTab as any);
    }
  }, [initialTab]);

  const handleTabChange = (tab: 'all' | 'piliers' | 'claims' | 'business' | 'commercial') => {
    setActiveTab(tab);
    if (onChangeTab) onChangeTab(tab);
  };

  const handleCopyPitch = () => {
    if (!audit.commercialProspectingKit?.phonePitch) return;
    const p = audit.commercialProspectingKit.phonePitch;
    const pitchText = `ACCROCHE :\n${p.hook}\n\nCHIFFRE CLÉ :\n${p.keyFact}\n\nRISQUE JURIDIQUE :\n${p.legalWarning}\n\nAPPEL À L'ACTION :\n${p.callToAction}`;
    navigator.clipboard.writeText(pitchText);
    setCopiedPitch(true);
    setTimeout(() => setCopiedPitch(false), 2500);
  };

  const handleCopyEmail = (key: string, text: string) => {
    navigator.clipboard.writeText(text);
    setCopiedEmailKey(key);
    setTimeout(() => setCopiedEmailKey(null), 2500);
  };

  const handleAddProject = async () => {
    try {
      const domain = audit.cleanUrl || audit.url;
      const res = await fetch('/api/projets/add-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          scanId: audit.id,
          domain,
          entreprise_secteur: audit.entreprise_secteur || audit.sector,
          entreprise_ca: audit.entreprise_ca,
          entreprise_salaries: audit.entreprise_salaries
        })
      });
      if (res.ok) {
        setAddedToProjectMsg(`Scan ajouté au projet "${domain}" !`);
        setTimeout(() => setAddedToProjectMsg(null), 3500);
      }
    } catch (_) {}
  };

  // Filtered available audits for the top selector
  const selectableAudits = useMemo(() => {
    return allAudits.filter((a) => {
      // Filter by selected list
      if (selectedListFilter !== 'all') {
        const targetList = listes.find(l => l.id === selectedListFilter);
        if (!targetList || !Array.isArray(targetList.items) || !targetList.items.includes(a.id)) {
          return false;
        }
      }
      // Filter by search text
      if (companySearch.trim()) {
        const term = companySearch.toLowerCase();
        const matchName = (a.companyName || '').toLowerCase().includes(term);
        const matchUrl = (a.cleanUrl || a.url || '').toLowerCase().includes(term);
        const matchSector = (a.entreprise_secteur || a.sector || '').toLowerCase().includes(term);
        if (!matchName && !matchUrl && !matchSector) return false;
      }
      return true;
    });
  }, [allAudits, selectedListFilter, listes, companySearch]);

  const handleSaveNote = () => {
    if (!noteContent.trim()) return;
    if (onUpdateStatus) {
      onUpdateStatus(audit.id, audit.prospectStatus || 'nouveau', noteContent.trim());
    }
    setNoteContent('');
    setIsAddingNote(false);
  };

  return (
    <div className="space-y-6 animate-fadeIn max-w-[1650px] mx-auto text-slate-800">
      {/* 0. TOP BAR: CHOISIR L'ENTREPRISE SCANNÉE & FILTRE LISTE SCANS */}
      <div className="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div className="flex flex-wrap items-center gap-3 flex-1">
            {/* Filtre Liste Scans */}
            <div className="flex items-center space-x-2">
              <Filter className="w-4 h-4 text-emerald-600 shrink-0" />
              <select
                value={selectedListFilter}
                onChange={(e) => setSelectedListFilter(e.target.value)}
                className="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-500 cursor-pointer shadow-xs"
              >
                <option value="all">📂 Toutes les listes scans</option>
                {listes.map((l) => (
                  <option key={l.id} value={l.id}>
                    📋 {l.nom} ({Array.isArray(l.items) ? l.items.length : 0})
                  </option>
                ))}
              </select>
            </div>

            {/* Sélecteur d'entreprise scannée */}
            <div className="flex-1 min-w-[240px]">
              <select
                value={audit.id}
                onChange={(e) => {
                  const found = allAudits.find(a => a.id === e.target.value);
                  if (found && onSelectAudit) {
                    onSelectAudit(found);
                  }
                }}
                className="w-full px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-500 cursor-pointer shadow-xs"
              >
                {selectableAudits.length === 0 ? (
                  <option value={audit.id}>Entreprise actuelle : {audit.companyName}</option>
                ) : (
                  selectableAudits.map((a) => (
                    <option key={a.id} value={a.id}>
                      🏢 {a.companyName || a.cleanUrl} — {a.isUnnotable || a.riskScore === null ? 'Non noté' : `Score ${a.riskScore}/100`} ({a.prospectStatus || 'Nouveau'})
                    </option>
                  ))
                )}
              </select>
            </div>
          </div>

          {/* Recherche rapide */}
          <div className="relative w-full lg:w-64">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input
              type="text"
              value={companySearch}
              onChange={(e) => setCompanySearch(e.target.value)}
              placeholder="Chercher une entreprise..."
              className="w-full pl-9 pr-3 py-2 rounded-xl bg-white border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-500 shadow-xs"
            />
          </div>
        </div>

        {/* PIPELINE ACTION BAR INTEGRATED IN PROSPECTION */}
        <div className="pt-3 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider mr-1">
              Statut Pipeline :
            </span>
            {PIPELINE_STATUSES.map((st) => {
              const isActive = (audit.prospectStatus || 'nouveau') === st.key;
              return (
                <button
                  key={st.key}
                  type="button"
                  onClick={() => onUpdateStatus && onUpdateStatus(audit.id, st.key)}
                  className={`px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center space-x-1.5 transition-all cursor-pointer ${
                    isActive 
                      ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs font-black' 
                      : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                  }`}
                >
                  <span>{st.icon}</span>
                  <span>{st.label}</span>
                </button>
              );
            })}
          </div>

          <div className="flex items-center space-x-2">
            <button
              onClick={() => setIsAddingNote(true)}
              className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-bold flex items-center space-x-1.5 transition-colors cursor-pointer"
            >
              <MessageSquare className="w-3.5 h-3.5 text-emerald-600" />
              <span>Ajouter note</span>
            </button>

            {onSwitchToPipeline && (
              <button
                onClick={onSwitchToPipeline}
                className="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold flex items-center space-x-1.5 transition-all shadow-xs cursor-pointer"
              >
                <Kanban className="w-3.5 h-3.5" />
                <span>Ouvrir dans Pipeline →</span>
              </button>
            )}

            {onDeleteAudit && (
              <button
                onClick={() => {
                  if (window.confirm(`Êtes-vous sûr de vouloir supprimer définitivement la fiche "${audit.companyName}" ?`)) {
                    onDeleteAudit(audit.id);
                  }
                }}
                className="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold flex items-center space-x-1.5 transition-colors shadow-2xs cursor-pointer"
                title="Supprimer la fiche"
              >
                <Trash2 className="w-3.5 h-3.5 text-rose-600" />
                <span>Supprimer</span>
              </button>
            )}
          </div>
        </div>

        {/* Inline Note Modal */}
        {isAddingNote && (
          <div className="p-4 rounded-2xl bg-slate-50 border border-emerald-300 space-y-3 animate-fadeIn">
            <div className="flex items-center justify-between text-xs font-bold text-slate-900">
              <span>Ajouter une note de suivi pour {audit.companyName}</span>
              <button onClick={() => setIsAddingNote(false)} className="text-slate-400 hover:text-slate-700">
                <X className="w-4 h-4" />
              </button>
            </div>
            <textarea
              value={noteContent}
              onChange={(e) => setNoteContent(e.target.value)}
              rows={2}
              placeholder="Ex: Échange téléphonique très positif avec la direction RSE. Devis de Bilan Carbone demandé pour le 15..."
              className="w-full p-3 rounded-xl bg-white border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-500"
            />
            <div className="flex justify-end space-x-2">
              <button
                onClick={() => setIsAddingNote(false)}
                className="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 text-xs font-bold cursor-pointer"
              >
                Annuler
              </button>
              <button
                onClick={handleSaveNote}
                className="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer shadow-xs"
              >
                Enregistrer la note
              </button>
            </div>
          </div>
        )}
      </div>

      {/* 1. HEADER AUDIT ACTIF */}
      <div className="bg-white text-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div className="space-y-2">
            <div className="flex items-center space-x-2">
              <span className="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                Cabinet {agency.agencyName}
              </span>
              <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200">
                {audit.prospectStatus || 'Nouveau prospect'}
              </span>
            </div>
            <h1 className="text-2xl sm:text-4xl font-black tracking-tight text-slate-900 font-serif">
              {audit.companyName}
            </h1>
            <p className="text-xs text-slate-500">
              Audit {audit.type_audit === 'business' ? 'Opportunité Business' : audit.type_audit === 'conformité' ? 'Conformité' : 'Full Audit'} • {new Date(audit.analyzedAt).toLocaleDateString('fr-FR')}
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <button onClick={onPrint} className="flex items-center space-x-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-colors cursor-pointer">
              <Printer className="w-4 h-4 text-emerald-600" />
              <span>Projet PDF</span>
            </button>
            <button onClick={onOpenMethodology} className="flex items-center space-x-1.5 px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-colors cursor-pointer">
              <Info className="w-3.5 h-3.5 text-emerald-600" />
              <span>Méthodologie</span>
            </button>
          </div>
        </div>

        {/* 2. AUTO-DEDUCED PROFILE */}
        <div className="mt-6 pt-6 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
          <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-2">
            <span className="text-[10px] uppercase font-black tracking-widest text-emerald-700 flex items-center space-x-1">
              <Building2 className="w-3.5 h-3.5 mr-1" />
              <span>Profil Entreprise (IA)</span>
            </span>
            <div className="space-y-1.5 pt-1">
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Secteur :</span>
                <span className="text-slate-900 font-bold">{audit.entreprise_secteur || audit.sector || 'Non détecté'}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Effectifs :</span>
                <span className="text-emerald-700 font-bold">{audit.entreprise_salaries || 'Non détecté'}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-slate-500">CA Estimé :</span>
                <span className="text-amber-700 font-bold">{audit.entreprise_ca || 'Non détecté'}</span>
              </div>
            </div>
          </div>

          <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-2">
            <span className="text-[10px] uppercase font-black tracking-widest text-emerald-700 flex items-center space-x-1">
              <Scale className="w-3.5 h-3.5 mr-1" />
              <span>Conformité & Risque</span>
            </span>
            <div className="space-y-1.5 pt-1">
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Score de conformité :</span>
                <span className={`font-black text-sm ${audit.isUnnotable || audit.riskScore === null ? 'text-amber-600' : 'text-emerald-600'}`}>
                  {audit.isUnnotable || audit.riskScore === null ? 'Non noté' : `${audit.riskScore}/100`}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Niveau de risque :</span>
                <span className="text-slate-900 font-bold uppercase">
                  {audit.isUnnotable || audit.riskScore === null ? 'Site Indisponible' : audit.riskLevel}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Allégations relevées :</span>
                <span className="text-rose-600 font-bold">{audit.detectedClaims?.length || 0}</span>
              </div>
            </div>
          </div>

          {/* CARTE CONTACT PROSPECT (AUTO-REMPLI & ÉDITABLE) */}
          <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-[10px] uppercase font-black tracking-widest text-emerald-700 flex items-center space-x-1">
                <PhoneCall className="w-3.5 h-3.5 mr-1" />
                <span>Contact Prospect</span>
              </span>
              <button
                type="button"
                onClick={handleOpenContactModal}
                className="flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors cursor-pointer border border-slate-200"
                title="Modifier les coordonnées du prospect"
              >
                <Pencil className="w-3 h-3 text-emerald-600" />
                <span>Modifier</span>
              </button>
            </div>

            <div className="space-y-2 pt-0.5">
              <div className="flex justify-between items-center text-xs">
                <span className="text-slate-500">Interlocuteur :</span>
                <span className="text-slate-900 font-bold truncate max-w-[170px]" title={effectiveName}>
                  {effectiveName}
                </span>
              </div>
              <div className="flex justify-between items-center text-xs">
                <span className="text-slate-500">Fonction :</span>
                <span className="text-slate-700 font-medium truncate max-w-[170px]" title={effectiveRole}>
                  {effectiveRole}
                </span>
              </div>
              <div className="flex justify-between items-center text-xs">
                <span className="text-slate-500">Email :</span>
                <div className="flex items-center space-x-1.5 truncate max-w-[180px]">
                  <a href={`mailto:${effectiveEmail}`} className="text-emerald-700 hover:underline font-bold truncate" title={effectiveEmail}>
                    {effectiveEmail}
                  </a>
                  <button
                    type="button"
                    onClick={() => {
                      navigator.clipboard.writeText(effectiveEmail);
                      setCopiedEmailKey('lead_email');
                      setTimeout(() => setCopiedEmailKey(null), 2000);
                    }}
                    className="text-slate-400 hover:text-slate-700 p-0.5 cursor-pointer"
                    title="Copier l'email"
                  >
                    {copiedEmailKey === 'lead_email' ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                  </button>
                </div>
              </div>
              <div className="flex justify-between items-center text-xs">
                <span className="text-slate-500">Téléphone :</span>
                <div className="flex items-center space-x-1.5">
                  <a href={`tel:${effectivePhone}`} className="text-teal-700 font-bold hover:underline">
                    {effectivePhone}
                  </a>
                  <button
                    type="button"
                    onClick={() => {
                      navigator.clipboard.writeText(effectivePhone);
                      setCopiedEmailKey('lead_phone');
                      setTimeout(() => setCopiedEmailKey(null), 2000);
                    }}
                    className="text-slate-400 hover:text-slate-700 p-0.5 cursor-pointer"
                    title="Copier le téléphone"
                  >
                    {copiedEmailKey === 'lead_phone' ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                  </button>
                </div>
              </div>
              {audit.prospectNotes && (
                <div className="flex justify-between items-start text-xs pt-1 border-t border-slate-200">
                  <span className="text-slate-500">Notes :</span>
                  <span className="text-slate-700 italic truncate max-w-[170px]" title={audit.prospectNotes}>
                    {audit.prospectNotes}
                  </span>
                </div>
              )}
            </div>

            {/* Multiple Suggested Contacts quick switcher if available */}
            {Array.isArray(audit.companyContacts) && audit.companyContacts.length > 1 && (
              <div className="pt-2 border-t border-slate-200 space-y-1.5">
                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">
                  Changer de contact clé :
                </span>
                <div className="flex flex-wrap gap-1.5">
                  {audit.companyContacts.map((c) => (
                    <button
                      key={c.id}
                      type="button"
                      onClick={() => handleSelectSuggestedContact(c)}
                      className={`text-[10px] px-2 py-0.5 rounded-lg border font-bold transition-colors cursor-pointer truncate max-w-[150px] ${
                        effectiveEmail === c.email 
                          ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                          : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200'
                      }`}
                      title={`Sélectionner ${c.name} (${c.roleTitle || c.contactType})`}
                    >
                      {c.roleTitle?.split('/')[0] || c.name}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* 3. TABS CONTENU PROSPECTION */}
      <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        {[
          { id: 'all', label: 'Vue d’ensemble & Plan d’action' },
          { id: 'piliers', label: '5 Piliers Climat & RSE (Analyse IA)' },
          { id: 'commercial', label: 'Kit de Prospection Commerciale (B2B)' },
          { id: 'claims', label: 'Allégations & Manquements Relevés' },
          { id: 'business', label: 'Opportunités de Missions Climat Local' },
        ].map((t) => (
          <button
            key={t.id}
            onClick={() => handleTabChange(t.id as any)}
            className={`px-4 py-2.5 rounded-2xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === t.id
                ? 'bg-emerald-600 text-white font-black shadow-xs'
                : 'bg-slate-100 text-slate-700 hover:text-slate-900 hover:bg-slate-200 border border-slate-200'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* 4. MAIN CONTENT & SIDEBAR */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 space-y-8">
          {/* 5 PILIERS CLIMAT & RSE */}
          {(activeTab === 'all' || activeTab === 'piliers') && (
            <PiliersRseView piliers={audit.piliers_evaluation} audit={audit} />
          )}

          {/* MODULE D'OPPORTUNITÉ COMMERCIALE ACV / FDES / EPD / PEP (Uniquement pour fabricants assujettis aux déclarations de matériaux du bâtiment) */}
          {audit.opportunite_commerciale && audit.opportunite_commerciale.presence_fdes_epd !== 'non_concerne' && (activeTab === 'all' || activeTab === 'business') && (
            <div className="bg-white p-6 sm:p-8 rounded-[2rem] border-2 border-emerald-300 shadow-sm space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-emerald-100 pb-4">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-black">
                    <Leaf className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] font-black uppercase tracking-wider text-emerald-700 block">
                      Module Expert • Données Environnementales RE2020
                    </span>
                    <h3 className="text-lg font-black text-slate-900">
                      Opportunité Commerciale ACV / FDES / EPD / PEP
                    </h3>
                  </div>
                </div>

                <div className="flex items-center space-x-2">
                  <span className={`px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider ${
                    audit.opportunite_commerciale.niveau_opportunite === 'critique' ? 'bg-rose-100 text-rose-800 border border-rose-300' :
                    audit.opportunite_commerciale.niveau_opportunite === 'forte' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' :
                    audit.opportunite_commerciale.niveau_opportunite === 'modérée' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                    'bg-slate-100 text-slate-700 border border-slate-200'
                  }`}>
                    Opportunité {audit.opportunite_commerciale.niveau_opportunite}
                  </span>
                </div>
              </div>

              {/* Statuts et Produits concernés */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1.5">
                  <span className="text-[10px] uppercase font-bold text-slate-500">Présence FDES / EPD (INIES)</span>
                  <div className="flex items-center space-x-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-amber-500" />
                    <span className="text-slate-900 font-bold text-sm capitalize">
                      {audit.opportunite_commerciale.presence_fdes_epd}
                    </span>
                  </div>
                </div>

                <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1.5">
                  <span className="text-[10px] uppercase font-bold text-slate-500">Produits & Matériaux ciblés</span>
                  <div className="flex flex-wrap gap-1.5">
                    {(audit.opportunite_commerciale.produits_concernes || []).map((p, idx) => (
                      <span key={idx} className="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 font-semibold text-[11px] border border-emerald-200">
                        {p}
                      </span>
                    ))}
                  </div>
                </div>
              </div>

              {/* Justification stratégique */}
              <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-2">
                <span className="text-[10px] uppercase font-black tracking-wider text-emerald-700 block">
                  Justification d'opportunité :
                </span>
                <p className="text-xs text-slate-700 leading-relaxed font-medium">
                  {audit.opportunite_commerciale.justification}
                </p>
              </div>

              {/* Actions recommandées */}
              {audit.opportunite_commerciale.actions_recommandees && audit.opportunite_commerciale.actions_recommandees.length > 0 && (
                <div className="space-y-2 pt-1">
                  <span className="text-[10px] uppercase font-black tracking-wider text-slate-500 block">
                    Actions Recommandées (Prescription Climat Local) :
                  </span>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    {audit.opportunite_commerciale.actions_recommandees.map((act, i) => (
                      <div key={i} className="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs text-slate-700 flex items-start space-x-2">
                        <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                        <span className="leading-snug">{act}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* POSITIONNEMENT STRATÉGIQUE SI NON ASSUJETTI FDES */}
          {audit.opportunite_commerciale && audit.opportunite_commerciale.presence_fdes_epd === 'non_concerne' && (activeTab === 'all' || activeTab === 'business') && (
            <div className="bg-white p-6 sm:p-8 rounded-[2rem] border border-slate-200 shadow-sm space-y-4">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 border border-teal-200 flex items-center justify-center font-black">
                    <Briefcase className="w-5 h-5" />
                  </div>
                  <div>
                    <span className="text-[10px] font-black uppercase tracking-wider text-teal-700 block">
                      Positionnement Stratégique • Climat Local
                    </span>
                    <h3 className="text-base font-black text-slate-900">
                      Secteur Non Assujetti aux Déclarations Produit FDES / PEP INIES
                    </h3>
                  </div>
                </div>
                <span className="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                  Fiches Produit Non Requises
                </span>
              </div>
              <p className="text-xs text-slate-600 leading-relaxed font-medium">
                {audit.opportunite_commerciale.justification}
              </p>
              {audit.opportunite_commerciale.actions_recommandees && audit.opportunite_commerciale.actions_recommandees.length > 0 && (
                <div className="space-y-2 pt-1">
                  <span className="text-[10px] uppercase font-black tracking-wider text-slate-500 block">
                    Actions Recommandées (Prescription Climat Local) :
                  </span>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    {audit.opportunite_commerciale.actions_recommandees.map((act, i) => (
                      <div key={i} className="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs text-slate-700 flex items-start space-x-2">
                        <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                        <span className="leading-snug">{act}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* VUE D'ENSEMBLE */}
          {(activeTab === 'all' || activeTab === 'claims') && (
            <div id="allegations-section" className="bg-white p-6 sm:p-8 rounded-[2rem] border border-slate-200 shadow-sm space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4">
                <h3 className="text-base font-black text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                  <ShieldAlert className="w-5 h-5 text-rose-600" />
                  <span>Détail des Allégations & Risques Juridiques ({audit.detectedClaims?.length || 0})</span>
                </h3>
                <a
                  href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825"
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all cursor-pointer"
                  title="Consulter le texte officiel de la Directive (UE) 2024/825 EmpCO sur EUR-Lex"
                >
                  <Scale className="w-3.5 h-3.5 text-rose-600" />
                  <span>Directive (UE) 2024/825 EmpCO</span>
                  <ExternalLink className="w-3 h-3 opacity-70" />
                </a>
              </div>

              {/* Notification de correction réévaluée */}
              {correctionMsg && (
                <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold flex items-center space-x-2 animate-fadeIn shadow-xs">
                  <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                  <span>{correctionMsg}</span>
                </div>
              )}

              <div className="space-y-4">
                {(!audit.detectedClaims || audit.detectedClaims.length === 0) ? (
                  <div className="p-8 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-2">
                    <CheckCircle2 className="w-10 h-10 text-emerald-600 mx-auto" />
                    <h4 className="text-base font-bold text-slate-900">Aucune allégation écologique non conforme</h4>
                    <p className="text-xs text-slate-600 max-w-md mx-auto">
                      L’analyse réévaluée ne présente aucune infraction au Décret 2022-539 ni à la Directive Green Claims. La note globale a été mise à jour à <strong className="text-emerald-700">{audit.riskScore}/100</strong>.
                    </p>
                  </div>
                ) : (
                  audit.detectedClaims.map((claim, idx) => {
                    const lawLink = getLawArticleLink(claim.lawArticle);
                    return (
                      <div key={idx} className="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3 hover:border-slate-300 transition-colors">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <span className="text-xs font-black text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-md border border-rose-200">
                            "{claim.term}"
                          </span>
                          <div className="flex items-center space-x-2">
                            <button
                              type="button"
                              onClick={() => handleOpenCorrectionModal(claim)}
                              className="px-3 py-1 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition-all cursor-pointer flex items-center space-x-1.5 shadow-2xs"
                              title="Corriger l'analyse IA, marquer comme faux positif et réévaluer la note"
                            >
                              <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                              <span>Corriger l'analyse de l'IA</span>
                            </button>
                            <a
                              href={lawLink}
                              target="_blank"
                              rel="noreferrer"
                              className="text-[11px] font-mono font-bold text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center space-x-1"
                              title="Voir l’article de loi / directive officielle"
                            >
                              <span>{claim.lawArticle || 'Directive UE 2024/825'}</span>
                              <ExternalLink className="w-3 h-3" />
                            </a>
                          </div>
                        </div>
                        <p className="text-xs text-slate-700 italic bg-white p-3 rounded-xl border border-slate-200">"{claim.quoteInText}"</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-1">
                          <div className="text-slate-600">
                            <strong className="text-rose-700">Sanction légale :</strong> {claim.sanction}
                          </div>
                          <div className="text-slate-600">
                            <strong className="text-emerald-700">Preuve exigée :</strong> {claim.requiredProof}
                          </div>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          )}

          {/* KIT COMMERCIAL & SCRIPTS AVEC MODULE IA */}
          {(activeTab === 'all' || activeTab === 'commercial') && audit.commercialProspectingKit && (
            <div className="bg-white p-6 sm:p-8 rounded-[2rem] border border-slate-200 shadow-sm space-y-6">
              
              {/* Header avec bouton de personnalisation IA */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-black border border-indigo-200">
                    <PhoneCall className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center space-x-2">
                      <span className="text-[10px] font-black uppercase tracking-wider text-indigo-700 block">
                        Prospection B2B • Climat Local
                      </span>
                      {audit.commercialProspectingKit.customComplements && (
                        <span className="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-emerald-50 text-emerald-800 border border-emerald-300">
                          Personnalisé par IA
                        </span>
                      )}
                    </div>
                    <h3 className="text-base font-black text-slate-900">
                      Kit Commercial & Scripts de Prospection
                    </h3>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setIsScriptGeneratorOpen(true)}
                  className="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider flex items-center justify-center space-x-2 shadow-sm transition-all active:scale-95 cursor-pointer"
                >
                  <Sparkles className="w-4 h-4 text-amber-200" />
                  <span>Adapter avec l’IA (Compléments)</span>
                </button>
              </div>

              {/* Badges des compléments actifs */}
              {audit.commercialProspectingKit.customComplements && (
                <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center gap-2 text-xs">
                  <span className="text-slate-600 font-bold text-[11px]">Directives actives :</span>
                  <span className="px-2.5 py-0.5 rounded-md bg-indigo-100 text-indigo-800 font-semibold border border-indigo-200">
                    Cible : {audit.commercialProspectingKit.customComplements.targetPersona || audit.commercialProspectingKit.targetPersona}
                  </span>
                  <span className="px-2.5 py-0.5 rounded-md bg-white text-slate-700 border border-slate-200">
                    Angle : {audit.commercialProspectingKit.customComplements.strategicAngle?.split('(')[0]}
                  </span>
                  {audit.commercialProspectingKit.customComplements.userComplements && audit.commercialProspectingKit.customComplements.userComplements !== 'Aucun complément libre spécifié.' && (
                    <span className="text-[11px] text-slate-600 italic line-clamp-1">
                      « {audit.commercialProspectingKit.customComplements.userComplements} »
                    </span>
                  )}
                </div>
              )}

              {/* Synthèse stratégique */}
              {audit.commercialProspectingKit.executiveSummary && (
                <p className="text-xs text-slate-700 italic bg-slate-50 p-4 rounded-xl border border-slate-200 leading-relaxed">
                  « {audit.commercialProspectingKit.executiveSummary} »
                </p>
              )}

              {/* SCRIPT D'APPEL */}
              {audit.commercialProspectingKit.phonePitch && (
                <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-indigo-800 uppercase flex items-center space-x-2">
                      <PhoneCall className="w-3.5 h-3.5 text-indigo-600" />
                      <span>Script d'appel téléphonique</span>
                    </span>
                    <button
                      onClick={handleCopyPitch}
                      className="flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition-colors cursor-pointer"
                    >
                      {copiedPitch ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                      <span>{copiedPitch ? 'Copié !' : 'Copier script'}</span>
                    </button>
                  </div>
                  <div className="space-y-2 text-xs text-slate-700 italic">
                    <p><strong className="text-amber-700 font-bold not-italic">[Accroche] </strong>"{audit.commercialProspectingKit.phonePitch.hook}"</p>
                    <p><strong className="text-cyan-800 font-bold not-italic">[Chiffre clé] </strong>"{audit.commercialProspectingKit.phonePitch.keyFact}"</p>
                    <p><strong className="text-rose-700 font-bold not-italic">[Alerte légale] </strong>"{audit.commercialProspectingKit.phonePitch.legalWarning}"</p>
                    <p className="font-bold text-slate-900 not-italic"><strong className="text-emerald-700 font-bold">[Call to action] </strong>"{audit.commercialProspectingKit.phonePitch.callToAction}"</p>
                  </div>
                </div>
              )}

              {/* TRAITEMENT DES OBJECTIONS */}
              {Array.isArray(audit.commercialProspectingKit.phonePitch?.objectionResponses) && audit.commercialProspectingKit.phonePitch.objectionResponses.length > 0 && (
                <div className="space-y-3">
                  <span className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-2">
                    <HelpCircle className="w-3.5 h-3.5 text-amber-600" />
                    <span>Traitement des objections ({audit.commercialProspectingKit.phonePitch.objectionResponses.length}) :</span>
                  </span>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {audit.commercialProspectingKit.phonePitch.objectionResponses.map((obj, i) => (
                      <div key={i} className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 flex flex-col justify-between">
                        <div className="space-y-1">
                          <span className="text-[10px] font-black uppercase text-rose-700 tracking-wider">Objection #{i + 1}</span>
                          <p className="text-xs font-bold text-slate-900">« {obj.objection} »</p>
                          <p className="text-xs text-slate-700 pt-1.5 border-t border-slate-200 leading-relaxed">
                            <strong className="text-emerald-700">Réponse : </strong>"{obj.answer}"
                          </p>
                        </div>
                        <button
                          type="button"
                          onClick={() => {
                            navigator.clipboard.writeText(`"${obj.objection}"\n-> "${obj.answer}"`);
                            setCopiedEmailKey(`obj_${i}`);
                            setTimeout(() => setCopiedEmailKey(null), 2000);
                          }}
                          className="self-end text-[10px] text-slate-500 hover:text-indigo-600 flex items-center space-x-1 cursor-pointer pt-1"
                        >
                          {copiedEmailKey === `obj_${i}` ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                          <span>{copiedEmailKey === `obj_${i}` ? 'Copié' : 'Copier'}</span>
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* MODÈLES D'EMAILS */}
              {audit.commercialProspectingKit.emailProspecting && (
                <div className="space-y-3 pt-1">
                  <span className="text-xs font-bold text-slate-800 uppercase block tracking-wider">
                    Modèles d'emails d'approche :
                  </span>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {audit.commercialProspectingKit.emailProspecting.legalSecurity && (
                      <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 flex flex-col justify-between">
                        <div className="space-y-1">
                          <div className="flex items-center justify-between">
                            <span className="text-[10px] font-black text-rose-700 uppercase">Email 1 • Sécurité</span>
                            <button
                              onClick={() => handleCopyEmail('legal', audit.commercialProspectingKit.emailProspecting.legalSecurity.body)}
                              className="text-[10px] text-indigo-600 font-bold hover:underline cursor-pointer"
                            >
                              {copiedEmailKey === 'legal' ? 'Copié !' : 'Copier'}
                            </button>
                          </div>
                          <p className="text-[11px] text-slate-900 font-bold">Objet : {audit.commercialProspectingKit.emailProspecting.legalSecurity.subject}</p>
                          <p className="text-xs text-slate-700 line-clamp-4 italic">"{audit.commercialProspectingKit.emailProspecting.legalSecurity.body}"</p>
                        </div>
                      </div>
                    )}

                    {audit.commercialProspectingKit.emailProspecting.businessTransformation && (
                      <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 flex flex-col justify-between">
                        <div className="space-y-1">
                          <div className="flex items-center justify-between">
                            <span className="text-[10px] font-black text-indigo-700 uppercase">Email 2 • Business</span>
                            <button
                              onClick={() => handleCopyEmail('biz', audit.commercialProspectingKit.emailProspecting.businessTransformation.body)}
                              className="text-[10px] text-indigo-600 font-bold hover:underline cursor-pointer"
                            >
                              {copiedEmailKey === 'biz' ? 'Copié !' : 'Copier'}
                            </button>
                          </div>
                          <p className="text-[11px] text-slate-900 font-bold">Objet : {audit.commercialProspectingKit.emailProspecting.businessTransformation.subject}</p>
                          <p className="text-xs text-slate-700 line-clamp-4 italic">"{audit.commercialProspectingKit.emailProspecting.businessTransformation.body}"</p>
                        </div>
                      </div>
                    )}

                    {(audit.commercialProspectingKit.emailProspecting.relanceSubvention || (audit.commercialProspectingKit.emailProspecting as any)?.localCarbonContribution) && (
                      <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 flex flex-col justify-between">
                        <div className="space-y-1">
                          <div className="flex items-center justify-between">
                            <span className="text-[10px] font-black text-emerald-700 uppercase">Email 3 • Subvention</span>
                            <button
                              onClick={() => {
                                const b = (audit.commercialProspectingKit.emailProspecting.relanceSubvention || (audit.commercialProspectingKit.emailProspecting as any)?.localCarbonContribution)?.body;
                                handleCopyEmail('sub', b);
                              }}
                              className="text-[10px] text-indigo-600 font-bold hover:underline cursor-pointer"
                            >
                              {copiedEmailKey === 'sub' ? 'Copié !' : 'Copier'}
                            </button>
                          </div>
                          {(() => {
                            const e = audit.commercialProspectingKit.emailProspecting.relanceSubvention || (audit.commercialProspectingKit.emailProspecting as any)?.localCarbonContribution;
                            return (
                              <>
                                <p className="text-[11px] text-slate-900 font-bold">Objet : {e?.subject}</p>
                                <p className="text-xs text-slate-700 line-clamp-4 italic">"{e?.body}"</p>
                              </>
                            );
                          })()}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* PROPOSITION D'ACCOMPAGNEMENT CLIMAT LOCAL */}
              {audit.commercialProspectingKit.climatLocalProposal && (
                <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-2">
                    <span className="text-xs font-bold text-slate-800 uppercase flex items-center space-x-1.5">
                      <Briefcase className="w-3.5 h-3.5 text-emerald-600" />
                      <span>Proposition de mission : <strong className="text-slate-900">{audit.commercialProspectingKit.climatLocalProposal.recommendedPackage}</strong></span>
                    </span>
                    <span className="font-mono font-black text-emerald-700 text-xs px-2.5 py-0.5 rounded bg-emerald-100 border border-emerald-300">
                      {audit.commercialProspectingKit.climatLocalProposal.pricingEstimate}
                    </span>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600">
                    <div>
                      <strong className="text-slate-800">Subvention : </strong>
                      <span className="text-emerald-700 font-semibold">{audit.commercialProspectingKit.climatLocalProposal.subsidies}</span>
                    </div>
                    <div>
                      <strong className="text-slate-800">Délai : </strong>
                      <span>{audit.commercialProspectingKit.climatLocalProposal.timeline}</span>
                    </div>
                  </div>
                  {audit.commercialProspectingKit.climatLocalProposal.closingArgument && (
                    <p className="text-xs text-slate-700 italic pt-1 border-t border-slate-200">
                      <strong className="text-indigo-800 not-italic">Argument clé : </strong>
                      "{audit.commercialProspectingKit.climatLocalProposal.closingArgument}"
                    </p>
                  )}
                </div>
              )}
            </div>
          )}

          {/* PLAN DE MISE EN CONFORMITÉ */}
          {activeTab === 'all' && (
            <div className="bg-white p-6 sm:p-8 rounded-[2rem] border border-slate-200 shadow-sm space-y-6">
              <h3 className="text-base font-black text-emerald-800 uppercase tracking-wider flex items-center space-x-2">
                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                <span>Plan de Mise en Conformité Recommandé</span>
              </h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {(audit.clientSummary?.stepsToCompliance || []).map((step, i) => (
                  <div key={i} className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-2">
                    <div className="flex items-center space-x-2">
                      <span className="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center border border-emerald-200">
                        {step.step || i + 1}
                      </span>
                      <h4 className="font-bold text-slate-900 text-xs">{step.title}</h4>
                    </div>
                    <p className="text-xs text-slate-600 leading-relaxed pl-8">{step.description}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* RIGHT SIDEBAR: Liste scans & Projets */}
        <div className="space-y-6">
          <div className="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm space-y-6 sticky top-6">
            <div className="space-y-2 text-slate-800">
              <div className="flex items-center space-x-2 text-emerald-700">
                <ClipboardList className="w-5 h-5" />
                <h3 className="font-black uppercase tracking-wider text-sm">Liste scans</h3>
              </div>
              <p className="text-[10px] text-slate-500 leading-relaxed font-medium">
                Organisez vos prospects et audits dans des listes thématiques de scans pour un suivi optimal.
              </p>
            </div>

            {/* List Selection */}
            <div className="space-y-3">
              {listes.length === 0 ? (
                <div className="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center">
                  <p className="text-[10px] text-slate-500 font-bold italic">Aucune liste de scans disponible</p>
                </div>
              ) : (
                <div className="space-y-2 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                  {listes.map((list) => {
                    const isInList = Array.isArray(list.items) && list.items.includes(audit.id);
                    return (
                      <button
                        key={list.id}
                        onClick={() => onAddToList && onAddToList(list.id, audit.id)}
                        className={`w-full flex items-center justify-between p-3 rounded-xl border transition-all ${
                          isInList 
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-bold' 
                            : 'bg-slate-50 border-slate-200 text-slate-600 hover:border-slate-300 hover:text-slate-900'
                        }`}
                      >
                        <div className="flex items-center space-x-3 truncate">
                          <div className={`w-4 h-4 rounded border flex items-center justify-center shrink-0 ${isInList ? 'bg-emerald-600 border-emerald-600' : 'border-slate-300'}`}>
                            {isInList && <Check className="w-3 h-3 text-white" />}
                          </div>
                          <span className="text-xs truncate">{list.nom}</span>
                        </div>
                        {isInList && <span className="text-[10px] font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded-md uppercase tracking-tighter shrink-0">Actif</span>}
                      </button>
                    );
                  })}
                </div>
              )}
            </div>

            <div className="pt-4 border-t border-slate-200 space-y-4">
              {addedToProjectMsg && (
                <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold flex items-center space-x-2">
                  <Check className="w-3.5 h-3.5 text-emerald-600" />
                  <span>{addedToProjectMsg}</span>
                </div>
              )}

              <button
                onClick={handleAddProject}
                className="w-full py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest flex items-center justify-center space-x-2 shadow-sm transition-all active:scale-95 cursor-pointer"
              >
                <Plus className="w-4 h-4" />
                <span>Ajouter au projet</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* MODAL IA : PERSONNALISATION DES SCRIPTS COMMERCIAUX */}
      <CommercialScriptGeneratorModal
        isOpen={isScriptGeneratorOpen}
        onClose={() => setIsScriptGeneratorOpen(false)}
        audit={audit}
        onKitUpdated={handleKitUpdated}
      />

      {/* MODAL IA : CORRECTION D'ANALYSE & ENTRAÎNEMENT REGLE */}
      {editingClaim && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white w-full max-w-lg rounded-3xl border border-slate-200 p-6 space-y-5 shadow-xl text-slate-800">
            <div className="flex items-start justify-between border-b border-slate-200 pb-4">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center font-black">
                  <Sparkles className="w-5 h-5 text-amber-500" />
                </div>
                <div>
                  <h3 className="text-base font-black text-slate-900">Corriger l'Analyse IA & Entraîner le Modèle</h3>
                  <p className="text-xs text-slate-500">Allégation ciblée : "{editingClaim.term}"</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setEditingClaim(null)}
                className="text-slate-400 hover:text-slate-700 p-1 rounded-xl cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-4 text-xs">
              <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                <span className="text-[10px] font-black uppercase text-slate-500 tracking-wider">Citation extraite du site :</span>
                <p className="text-slate-800 italic">« {editingClaim.quoteInText} »</p>
              </div>

              <div className="space-y-2">
                <label className="text-2xs font-black uppercase text-slate-500 tracking-wider">
                  Motif de correction & qualification :
                </label>

                <div className="space-y-2">
                  <label className="flex items-start space-x-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 hover:border-indigo-400 cursor-pointer">
                    <input
                      type="radio"
                      name="correctionType"
                      checked={correctionReason.includes("Relation commerciale")}
                      onChange={() => setCorrectionReason("Relation commerciale / relationnel client durable (ne concerne pas un produit ou impact écologique)")}
                      className="mt-0.5 text-emerald-600"
                    />
                    <div>
                      <span className="font-bold text-slate-900 block">🛑 Faux positif — Contexte relation client</span>
                      <span className="text-2xs text-slate-500">Ex: "relation durable avec nos clients", "partenariat pérenne". Ce n'est pas une allégation produit/environnement.</span>
                    </div>
                  </label>

                  <label className="flex items-start space-x-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 hover:border-indigo-400 cursor-pointer">
                    <input
                      type="radio"
                      name="correctionType"
                      checked={correctionReason.includes("Faux positif — Contexte sémantique")}
                      onChange={() => setCorrectionReason("Faux positif — Contexte sémantique non environnemental")}
                      className="mt-0.5 text-emerald-600"
                    />
                    <div>
                      <span className="font-bold text-slate-900 block">🛑 Faux positif — Autre usage non environnemental</span>
                      <span className="text-2xs text-slate-500">Le terme n'est pas utilisé au sens d'une allégation écologique risquée.</span>
                    </div>
                  </label>
                </div>
              </div>

              <div className="space-y-1">
                <label className="text-2xs font-black uppercase text-slate-500 tracking-wider">
                  Commentaire explicatif pour le rapport :
                </label>
                <textarea
                  rows={2}
                  value={correctionReason}
                  onChange={(e) => setCorrectionReason(e.target.value)}
                  className="w-full p-3 rounded-2xl bg-white border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-indigo-500"
                />
              </div>

              <label className="flex items-center space-x-3 p-3 rounded-2xl bg-indigo-50/70 border border-indigo-200 cursor-pointer">
                <input
                  type="checkbox"
                  checked={trainAiRule}
                  onChange={(e) => setTrainAiRule(e.target.checked)}
                  className="w-4 h-4 rounded text-emerald-600"
                />
                <div>
                  <span className="font-bold text-indigo-900 block text-xs">☑️ Entraîner le moteur IA pour les futurs scans</span>
                  <span className="text-2xs text-indigo-700">Ajoute la règle d'exclusion d'apprentissage au dictionnaire métier pour ne plus répéter cette confusion.</span>
                </div>
              </label>
            </div>

            <div className="flex items-center space-x-3 pt-2">
              <button
                type="button"
                onClick={handleApplyCorrection}
                disabled={isSubmittingCorrection}
                className="flex-1 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider flex items-center justify-center space-x-2 shadow-sm transition-all cursor-pointer active:scale-95 disabled:opacity-50"
              >
                <CheckCircle2 className="w-4 h-4 text-white" />
                <span>{isSubmittingCorrection ? 'Réévaluation...' : 'Disqualifier & Réévaluer la Note'}</span>
              </button>
              <button
                type="button"
                onClick={() => setEditingClaim(null)}
                className="py-3.5 px-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold cursor-pointer"
              >
                Annuler
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
