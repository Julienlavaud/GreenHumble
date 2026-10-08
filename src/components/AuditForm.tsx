import React, { useState, useEffect, useMemo } from 'react';
import { 
  Globe, 
  ArrowRight, 
  Loader2, 
  CheckCircle2, 
  AlertOctagon, 
  Scale, 
  Search, 
  Building2, 
  Trash2, 
  ArrowUpDown, 
  Briefcase,
  PhoneCall,
  Mail,
  Copy,
  Check,
  Plus,
  FileText,
  Upload,
  ExternalLink,
  ClipboardList,
  ShieldAlert,
  Users,
  TrendingUp,
  FolderPlus,
  Sparkles,
  HelpCircle
} from 'lucide-react';
import { GreenwashingAudit, AgencyProfile, ListeRecord } from '../types.js';
import { PiliersRseView } from './PiliersRseView.js';
import { CommercialScriptGeneratorModal } from './CommercialScriptGeneratorModal.js';
import { getLawArticleLink } from '../utils/ecoClaimWords.js';

interface AuditFormProps {
  onStartAudit: (url: string, type_audit: string, liste_pre_audit: string[]) => Promise<void>;
  onStartDocumentAudit?: (doc: { documentText: string; documentName: string; companyName: string; type_audit: string; liste_pre_audit: string[] }) => Promise<void>;
  isLoading: boolean;
  agency?: AgencyProfile;
  collaborators?: Array<{ id: string; name: string; role: string }>;
  audits: GreenwashingAudit[];
  listes?: ListeRecord[];
  onSelectAudit: (audit: GreenwashingAudit) => void;
  onDeleteAudit?: (id: string) => void;
  onUpdateStatus?: (id: string, status: GreenwashingAudit['prospectStatus'], notes?: string) => void;
  onUpdateAudit?: (updatedAudit: GreenwashingAudit) => void;
  activeScanResult?: GreenwashingAudit | null;
}

export const AuditForm: React.FC<AuditFormProps> = ({
  onStartAudit,
  onStartDocumentAudit,
  isLoading,
  audits = [],
  onSelectAudit,
  onDeleteAudit,
  onUpdateAudit,
  activeScanResult = null,
}) => {
  const [url, setUrl] = useState('');
  const [typeAudit, setTypeAudit] = useState<'business' | 'conformité' | 'full'>('full');
  const [selectedListIds, setSelectedListIds] = useState<string[]>([]);
  const [loadingStep, setLoadingStep] = useState(0);

  // Scanner Mode: 'url' or 'document'
  const [scanMode, setScanMode] = useState<'url' | 'document'>('url');
  const [documentText, setDocumentText] = useState('');
  const [documentName, setDocumentName] = useState('Rapport_RSE_2025.pdf');
  const [docCompanyName, setDocCompanyName] = useState('');
  const [uploadFileName, setUploadFileName] = useState<string | null>(null);

  // Current displayed scan result (defaults to activeScanResult or selected audit from history)
  const [currentResult, setCurrentResult] = useState<GreenwashingAudit | null>(activeScanResult || (audits.length > 0 ? audits[0] : null));
  const [isScriptGeneratorOpen, setIsScriptGeneratorOpen] = useState(false);

  const handleKitUpdated = (newKit: any) => {
    if (currentResult) {
      const updated: GreenwashingAudit = {
        ...currentResult,
        commercialProspectingKit: newKit
      };
      setCurrentResult(updated);
      if (onUpdateAudit) {
        onUpdateAudit(updated);
      }
    }
  };

  // Listes de travail state
  const [availableListes, setAvailableListes] = useState<ListeRecord[]>([]);
  const [newListName, setNewListName] = useState('');
  const [isCreatingList, setIsCreatingList] = useState(false);

  // Status & copy feedback
  const [copiedPitch, setCopiedPitch] = useState(false);
  const [copiedEmailKey, setCopiedEmailKey] = useState<string | null>(null);
  const [projectAddedMsg, setProjectAddedMsg] = useState<string | null>(null);

  // Filters state for historical scans
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedSort, setSelectedSort] = useState<'date_desc' | 'score_asc' | 'score_desc'>('date_desc');

  // Load lists from server
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

  useEffect(() => {
    if (activeScanResult) {
      setCurrentResult(activeScanResult);
    }
  }, [activeScanResult]);

  const handleToggleList = (id: string) => {
    setSelectedListIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const handleToggleItemInList = async (listId: string, itemId: string) => {
    try {
      const res = await fetch('/api/listes/toggle-item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listId, itemId })
      });
      if (res.ok) {
        fetchListes();
      }
    } catch (_) {}
  };

  const handleCreateNewList = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newListName.trim()) return;
    try {
      const res = await fetch('/api/listes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nom: newListName.trim() })
      });
      if (res.ok) {
        setNewListName('');
        setIsCreatingList(false);
        fetchListes();
      }
    } catch (_) {}
  };

  const handleAddCurrentToProject = async () => {
    if (!currentResult) return;
    try {
      const domain = currentResult.cleanUrl || currentResult.url;
      const res = await fetch('/api/projets/add-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          scanId: currentResult.id,
          domain,
          entreprise_secteur: currentResult.entreprise_secteur || currentResult.sector,
          entreprise_ca: currentResult.entreprise_ca,
          entreprise_salaries: currentResult.entreprise_salaries
        })
      });
      if (res.ok) {
        setProjectAddedMsg(`Scan ajouté au projet "${domain}" avec succès !`);
        setTimeout(() => setProjectAddedMsg(null), 3500);
      }
    } catch (_) {}
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploadFileName(file.name);
    setDocumentName(file.name);
    const reader = new FileReader();
    reader.onload = (event) => {
      const content = event.target?.result as string;
      if (content) {
        setDocumentText(content);
        if (!docCompanyName) {
          const clean = file.name.replace(/\.[a-z0-9]+$/i, '').replace(/[-_]/g, ' ');
          setDocCompanyName(clean);
        }
      }
    };
    reader.readAsText(file);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!url.trim() || isLoading) return;

    setLoadingStep(1);
    const interval = setInterval(() => {
      setLoadingStep((prev) => (prev < 4 ? prev + 1 : prev));
    }, 1100);

    try {
      await onStartAudit(url.trim(), typeAudit, selectedListIds);
    } finally {
      clearInterval(interval);
      setLoadingStep(0);
    }
  };

  const handleDocumentSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!documentText.trim() || isLoading) return;

    setLoadingStep(1);
    const interval = setInterval(() => {
      setLoadingStep((prev) => (prev < 4 ? prev + 1 : prev));
    }, 1100);

    try {
      if (onStartDocumentAudit) {
        await onStartDocumentAudit({
          documentText,
          documentName: documentName || 'Rapport_Activite_RSE.pdf',
          companyName: docCompanyName || 'Entreprise Document',
          type_audit: typeAudit,
          liste_pre_audit: selectedListIds
        });
      }
    } finally {
      clearInterval(interval);
      setLoadingStep(0);
    }
  };

  const handleCopyPitch = () => {
    if (!currentResult?.commercialProspectingKit?.phonePitch) return;
    const p = currentResult.commercialProspectingKit.phonePitch;
    const pitchText = `ACCROCHE :\n${p.hook}\n\nCHIFFRE CLÉ :\n${p.keyFact}\n\nRISQUE JURIDIQUE :\n${p.legalWarning}\n\nAPPEL À L'ACTION :\n${p.callToAction}`;
    navigator.clipboard.writeText(pitchText);
    setCopiedPitch(true);
    setTimeout(() => setCopiedPitch(false), 2500);
  };

  const handleCopyEmail = (key: string, body: string) => {
    navigator.clipboard.writeText(body);
    setCopiedEmailKey(key);
    setTimeout(() => setCopiedEmailKey(null), 2500);
  };

  const filteredAudits = useMemo(() => {
    return audits
      .filter((a) => {
        if (!searchQuery.trim()) return true;
        const q = searchQuery.toLowerCase();
        return (
          a.companyName.toLowerCase().includes(q) ||
          a.cleanUrl.toLowerCase().includes(q) ||
          (a.entreprise_secteur || a.sector || '').toLowerCase().includes(q)
        );
      })
      .sort((a, b) => {
        if (selectedSort === 'score_asc') return a.riskScore - b.riskScore;
        if (selectedSort === 'score_desc') return b.riskScore - a.riskScore;
        return new Date(b.analyzedAt).getTime() - new Date(a.analyzedAt).getTime();
      });
  }, [audits, searchQuery, selectedSort]);

  return (
    <div className="space-y-8 animate-fadeIn max-w-[1700px] w-full mx-auto text-slate-800">
      {/* 1. SCANNER FORM (URL / DOC) */}
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div className="space-y-1">
            <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
              <Scale className="w-3.5 h-3.5 text-emerald-600" />
              <span>Scanner Greenwashing • Moteur IA Conforme Directive (UE) 2024/825</span>
            </div>
            <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              Analyse de conformité & Opportunités B2B
            </h1>
          </div>

          {/* Mode Switcher */}
          <div className="flex bg-slate-100 p-1 rounded-xl border border-slate-200 shrink-0">
            <button
              type="button"
              onClick={() => setScanMode('url')}
              className={`flex items-center space-x-2 py-1.5 px-3 rounded-lg text-xs font-bold transition-all ${
                scanMode === 'url' ? 'bg-[#1e40af] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Globe className="w-3.5 h-3.5" />
              <span>Site web (URL)</span>
            </button>
            <button
              type="button"
              onClick={() => setScanMode('document')}
              className={`flex items-center space-x-2 py-1.5 px-3 rounded-lg text-xs font-bold transition-all ${
                scanMode === 'document' ? 'bg-[#1e40af] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <FileText className="w-3.5 h-3.5" />
              <span>Document / PDF</span>
            </button>
          </div>
        </div>

        {scanMode === 'url' ? (
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <label htmlFor="url-input" className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Adresse web de l’entreprise cible à scanner
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                  <Globe className="w-5 h-5 text-emerald-600" />
                </div>
                <input
                  id="url-input"
                  type="text"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="ex : www.entreprise-cible.fr ou nom-de-domaine.com"
                  disabled={isLoading}
                  required
                  className="w-full pl-12 pr-4 py-3.5 rounded-2xl bg-white border border-slate-300 text-slate-900 placeholder-slate-400 text-sm font-medium focus:border-emerald-600 focus:outline-none transition-all"
                />
              </div>
            </div>

            {/* Type d'audit */}
            <div className="space-y-2">
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Type d'audit IA
              </label>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {[
                  { id: 'business', label: 'Opportunité business', desc: 'Priorité aux missions et subventions' },
                  { id: 'conformité', label: 'Conformité juridique', desc: 'Focus risques DGCCRF et articles de loi' },
                  { id: 'full', label: 'Full Audit complet', desc: 'Analyse intégrale Risques + Business' },
                ].map((t) => (
                  <button
                    key={t.id}
                    type="button"
                    onClick={() => setTypeAudit(t.id as any)}
                    className={`flex flex-col p-3 rounded-2xl border text-left transition-all ${
                      typeAudit === t.id
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-950 ring-1 ring-emerald-600/40'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                    }`}
                  >
                    <span className={`text-xs font-bold ${typeAudit === t.id ? 'text-emerald-800' : 'text-slate-800'}`}>
                      {t.label}
                    </span>
                    <span className="text-[10px] text-slate-500 mt-0.5">{t.desc}</span>
                  </button>
                ))}
              </div>
            </div>

            {/* Listes scans (sélection avant audit) */}
            {availableListes.length > 0 && (
              <div className="space-y-2">
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                  Ajouter automatiquement à une liste de scans
                </label>
                <div className="flex flex-wrap gap-2">
                  {availableListes.map((list) => (
                    <button
                      key={list.id}
                      type="button"
                      onClick={() => handleToggleList(list.id)}
                      className={`px-3 py-1.5 rounded-xl border text-xs font-bold flex items-center space-x-2 transition-all ${
                        selectedListIds.includes(list.id)
                          ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                          : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300'
                      }`}
                    >
                      <div className={`w-3.5 h-3.5 rounded border flex items-center justify-center ${selectedListIds.includes(list.id) ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300'}`}>
                        {selectedListIds.includes(list.id) && <Check className="w-2.5 h-2.5" />}
                      </div>
                      <span>{list.nom}</span>
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Launch Button */}
            <button
              type="submit"
              disabled={isLoading || !url.trim()}
              className={`w-full py-4 px-6 rounded-2xl font-black text-sm text-white flex items-center justify-center space-x-2 shadow-sm transition-all ${
                isLoading || !url.trim()
                  ? 'bg-slate-300 text-slate-500 cursor-not-allowed opacity-60'
                  : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.99]'
              }`}
            >
              {isLoading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin text-white" />
                  <span>Analyse IA en cours ({loadingStep}/4)...</span>
                </>
              ) : (
                <>
                  <Scale className="w-4 h-4 text-white" />
                  <span>Lancer le Scan Greenwashing & Auto-Déduction</span>
                  <ArrowRight className="w-4 h-4 text-white ml-1" />
                </>
              )}
            </button>
          </form>
        ) : (
          <form onSubmit={handleDocumentSubmit} className="space-y-6">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                  Nom du document ou rapport*
                </label>
                <input
                  type="text"
                  value={documentName}
                  onChange={(e) => setDocumentName(e.target.value)}
                  placeholder="ex : Rapport_RSE_2025.pdf"
                  required
                  className="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-white text-slate-900 focus:border-emerald-600 outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                  Nom de l'entreprise ou marque*
                </label>
                <input
                  type="text"
                  value={docCompanyName}
                  onChange={(e) => setDocCompanyName(e.target.value)}
                  placeholder="ex : Entreprise Cible"
                  required
                  className="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-white text-slate-900 focus:border-emerald-600 outline-none"
                />
              </div>
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                  Contenu du document à analyser*
                </label>
                <label className="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-emerald-700 rounded-lg text-xs font-bold border border-slate-200 cursor-pointer flex items-center space-x-1 transition-colors">
                  <Upload className="w-3.5 h-3.5" />
                  <span>{uploadFileName ? `Fichier : ${uploadFileName}` : 'Charger fichier (.pdf, .txt)'}</span>
                  <input type="file" accept=".pdf,.txt,.md,.json" onChange={handleFileUpload} className="hidden" />
                </label>
              </div>

              <textarea
                rows={5}
                value={documentText}
                onChange={(e) => setDocumentText(e.target.value)}
                placeholder="Collez ici les extraits de votre rapport d’activité, déclaration RSE, brochure ou charte éco-responsable..."
                required
                className="w-full p-4 rounded-2xl bg-white border border-slate-300 text-slate-900 text-xs font-mono focus:border-emerald-600 outline-none"
              />
            </div>

            <button
              type="submit"
              disabled={isLoading || !documentText.trim()}
              className={`w-full py-4 px-6 rounded-2xl font-black text-sm text-white flex items-center justify-center space-x-2 shadow-sm transition-all ${
                isLoading || !documentText.trim()
                  ? 'bg-slate-300 text-slate-500 cursor-not-allowed opacity-60'
                  : 'bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500'
              }`}
            >
              {isLoading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin text-white" />
                  <span>Analyse du document en cours...</span>
                </>
              ) : (
                <>
                  <FileText className="w-4 h-4 text-white" />
                  <span>Analyser ce Document RSE</span>
                </>
              )}
            </button>
          </form>
        )}

        {/* Loading status */}
        {isLoading && (
          <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center space-x-3">
            <Loader2 className="w-4 h-4 animate-spin text-emerald-600" />
            <span>Inspection du contenu et interrogation du Moteur IA selon les paramètres configurés...</span>
          </div>
        )}
      </div>

      {/* 2. SCAN RESULT DETAILS (UNIQUEMENT LES 6 BLOCS OBLIGATOIRES) */}
      {currentResult && (
        <div className="space-y-6 animate-fadeIn">
          {projectAddedMsg && (
            <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold flex items-center space-x-2">
              <CheckCircle2 className="w-4 h-4 text-emerald-600" />
              <span>{projectAddedMsg}</span>
            </div>
          )}

          {/* Top Bar: Company Name & Add to Project Button */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
            <div className="space-y-1">
              <div className="flex items-center space-x-2">
                <span className="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-black uppercase">
                  Scan actif : {currentResult.cleanUrl || currentResult.url}
                </span>
                <span className="text-slate-500 text-xs font-mono">
                  {new Date(currentResult.analyzedAt).toLocaleDateString('fr-FR')}
                </span>
              </div>
              <h2 className="text-2xl font-black text-slate-900">{currentResult.companyName}</h2>
            </div>

            {/* BOUTON OBLIGATOIRE : AJOUTER À UN PROJET */}
            <button
              onClick={handleAddCurrentToProject}
              className="flex items-center justify-center space-x-2 px-5 py-3 rounded-2xl bg-[#1e40af] hover:bg-[#1d4ed8] text-white font-black text-xs uppercase tracking-wider transition-all shadow-sm active:scale-95 cursor-pointer"
            >
              <FolderPlus className="w-4 h-4" />
              <span>Ajouter à un projet</span>
            </button>
          </div>

          {/* BLOC 1 : PROFIL ENTREPRISE AUTO-DÉDUIT */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
            <h3 className="text-sm font-black text-emerald-700 uppercase tracking-wider flex items-center space-x-2">
              <Building2 className="w-4 h-4" />
              <span>Profil Entreprise (Auto-déduit par l'IA)</span>
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1">
                <span className="text-[10px] font-black uppercase text-slate-500 flex items-center space-x-1">
                  <Briefcase className="w-3 h-3 text-emerald-600" />
                  <span>Secteur d'activité</span>
                </span>
                <div className="text-sm font-bold text-slate-900">
                  {currentResult.entreprise_secteur || currentResult.sector || 'Services & Industrie'}
                </div>
              </div>

              <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1">
                <span className="text-[10px] font-black uppercase text-slate-500 flex items-center space-x-1">
                  <Users className="w-3 h-3 text-teal-600" />
                  <span>Effectifs salariés</span>
                </span>
                <div className="text-sm font-bold text-teal-800">
                  {currentResult.entreprise_salaries || '11-50'}
                </div>
              </div>

              <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-1">
                <span className="text-[10px] font-black uppercase text-slate-500 flex items-center space-x-1">
                  <TrendingUp className="w-3 h-3 text-amber-600" />
                  <span>Chiffre d'affaires estimé</span>
                </span>
                <div className="text-sm font-bold text-amber-800">
                  {currentResult.entreprise_ca || '2M€-10M€'}
                </div>
              </div>
            </div>

            {/* Sirene / Localisation */}
            {(currentResult.city || currentResult.region || currentResult.siren) && (
              <div className="text-[11px] text-slate-600 pt-2 border-t border-slate-100 flex flex-wrap gap-4">
                {currentResult.city && <span>📍 Siège : <strong className="text-slate-900">{currentResult.city}</strong> ({currentResult.region})</span>}
                {currentResult.siren && <span>🏢 SIREN : <strong className="text-slate-900 font-mono">{currentResult.siren}</strong></span>}
                {currentResult.pappersUrl && (
                  <a href={currentResult.pappersUrl} target="_blank" rel="noreferrer" className="text-teal-700 hover:underline flex items-center space-x-1 font-semibold">
                    <ExternalLink className="w-3 h-3" />
                    <span>Fiche Sirene / Pappers</span>
                  </a>
                )}
              </div>
            )}
          </div>

          {/* BLOC 2 : RÉSULTAT IA (SCORES & ALLÉGATIONS) */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-6 shadow-sm">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <h3 className="text-sm font-black text-rose-700 uppercase tracking-wider flex items-center space-x-2">
                <ShieldAlert className="w-4 h-4" />
                <span>Résultat IA • Diagnostic & Risques Détectés</span>
              </h3>
              <div className="flex items-center space-x-3">
                <div className="text-right">
                  <span className="text-[10px] text-slate-500 uppercase font-black block">Score de conformité</span>
                  <span className={`text-xl font-black ${currentResult.isUnnotable || currentResult.riskScore === null ? 'text-amber-600' : 'text-emerald-700'}`}>
                    {currentResult.isUnnotable || currentResult.riskScore === null ? 'Non noté' : `${currentResult.riskScore}/100`}
                  </span>
                </div>
                <span className={`px-2.5 py-1 rounded-xl text-xs font-black uppercase ${
                  currentResult.isUnnotable || currentResult.riskScore === null ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                  currentResult.riskLevel === 'critique' ? 'bg-rose-100 text-rose-800 border border-rose-300' :
                  currentResult.riskLevel === 'eleve' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                  'bg-emerald-100 text-emerald-800 border border-emerald-300'
                }`}>
                  {currentResult.isUnnotable || currentResult.riskScore === null ? 'Site Indisponible' : `Risque ${currentResult.riskLevel}`}
                </span>
              </div>
            </div>

            {/* Verdict */}
            <div className={`p-4 rounded-2xl border space-y-2 text-xs ${
              currentResult.isUnnotable || currentResult.riskScore === null
                ? 'bg-amber-50 border-amber-200 text-amber-900'
                : 'bg-slate-50 border-slate-200 text-slate-700'
            }`}>
              <span className={`font-bold uppercase text-[10px] block ${
                currentResult.isUnnotable || currentResult.riskScore === null ? 'text-amber-800' : 'text-emerald-800'
              }`}>
                {currentResult.isUnnotable || currentResult.riskScore === null ? 'Notice - Impossible de noter l’entreprise' : 'Synthèse du diagnostic'}
              </span>
              <p className="leading-relaxed font-medium">
                {currentResult.unnotableReason || currentResult.clientSummary?.riskVerdict || currentResult.clientSummary?.headline || 'Audit de conformité réalisé.'}
              </p>
            </div>

            {/* Claims list */}
            <div className="space-y-3">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                  Allégations et manquements identifiés ({currentResult.detectedClaims?.length || 0}) :
                </span>
                <a
                  href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825"
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center space-x-1.5 text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-xl border border-rose-200 transition-colors w-fit"
                  title="Voir la Directive Européenne (UE) 2024/825 EmpCO sur les allégations environnementales"
                >
                  <Scale className="w-3.5 h-3.5 text-rose-600" />
                  <span>Directive (UE) 2024/825 EmpCO</span>
                  <ExternalLink className="w-3 h-3 opacity-70" />
                </a>
              </div>
              <div className="space-y-3">
                {(currentResult.detectedClaims || []).map((claim, idx) => {
                  const lawLink = getLawArticleLink(claim.lawArticle);
                  return (
                    <div key={idx} className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <span className="text-xs font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded border border-rose-200">
                          "{claim.term}"
                        </span>
                        <a
                          href={lawLink}
                          target="_blank"
                          rel="noreferrer"
                          className="text-[10px] font-mono font-bold text-[#1e40af] hover:text-blue-700 hover:underline inline-flex items-center space-x-1"
                          title="Lien vers l'article juridique officiel"
                        >
                          <span>{claim.lawArticle || 'Directive UE 2024/825'}</span>
                          <ExternalLink className="w-3 h-3" />
                        </a>
                      </div>
                      <p className="text-xs text-slate-700 italic">"{claim.quoteInText}"</p>
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
                        <div className="text-slate-600">
                          <strong className="text-rose-700">Sanction :</strong> {claim.sanction}
                        </div>
                        <div className="text-slate-600">
                          <strong className="text-emerald-700">Preuve exigée :</strong> {claim.requiredProof}
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* BLOC 2.5 : ÉVALUATION DES 5 PILIERS CLIMAT & RSE (ANALYSE IA) */}
          <PiliersRseView piliers={currentResult.piliers_evaluation} audit={currentResult} />

          {/* BLOC 3 : PROSPECTION (KIT COMMERCIAL) */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-6 shadow-sm">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
              <div className="flex items-center space-x-2.5">
                <div className="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-black border border-indigo-100">
                  <PhoneCall className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="text-sm font-black text-indigo-900 uppercase tracking-wider">
                    Kit de Prospection Commerciale
                  </h3>
                  {currentResult.commercialProspectingKit?.customComplements && (
                    <span className="text-[10px] text-emerald-700 font-bold">
                      Personnalisé avec vos compléments terrain
                    </span>
                  )}
                </div>
              </div>

              <button
                type="button"
                onClick={() => setIsScriptGeneratorOpen(true)}
                className="px-4 py-2 rounded-xl bg-[#1e40af] hover:bg-[#1d4ed8] text-white font-black text-xs uppercase tracking-wider flex items-center justify-center space-x-2 shadow-sm transition-all active:scale-95 cursor-pointer"
              >
                <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                <span>Adapter les scripts avec l’IA</span>
              </button>
            </div>

            {/* Badges directives actives */}
            {currentResult.commercialProspectingKit?.customComplements && (
              <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-wrap items-center gap-2 text-xs">
                <span className="text-slate-600 font-bold text-[11px]">Directives appliquées :</span>
                <span className="px-2 py-0.5 rounded bg-indigo-50 text-indigo-800 font-semibold border border-indigo-200 text-[11px]">
                  Cible : {currentResult.commercialProspectingKit.customComplements.targetPersona}
                </span>
                <span className="px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 text-[11px]">
                  Angle : {currentResult.commercialProspectingKit.customComplements.strategicAngle?.split('(')[0]}
                </span>
                {currentResult.commercialProspectingKit.customComplements.userComplements && currentResult.commercialProspectingKit.customComplements.userComplements !== 'Aucun complément libre spécifié.' && (
                  <span className="text-[11px] text-slate-500 italic line-clamp-1">
                    « {currentResult.commercialProspectingKit.customComplements.userComplements} »
                  </span>
                )}
              </div>
            )}

            {/* Phone Pitch */}
            {currentResult.commercialProspectingKit?.phonePitch && (
              <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-indigo-900 uppercase">Script d'appel téléphonique</span>
                  <button
                    onClick={handleCopyPitch}
                    className="flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition-colors cursor-pointer"
                  >
                    {copiedPitch ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                    <span>{copiedPitch ? 'Copié !' : 'Copier script'}</span>
                  </button>
                </div>
                <div className="space-y-2 text-xs text-slate-700 italic">
                  <p className="text-amber-900"><strong className="text-amber-700 not-italic font-bold">[Accroche] </strong>"{currentResult.commercialProspectingKit.phonePitch.hook}"</p>
                  <p><strong className="text-blue-700 not-italic font-bold">[Chiffre clé] </strong>"{currentResult.commercialProspectingKit.phonePitch.keyFact}"</p>
                  <p className="text-rose-900"><strong className="text-rose-700 not-italic font-bold">[Alerte légale] </strong>"{currentResult.commercialProspectingKit.phonePitch.legalWarning}"</p>
                  <p className="font-bold text-slate-900 not-italic"><strong className="text-emerald-700 font-bold">[Call to action] </strong>"{currentResult.commercialProspectingKit.phonePitch.callToAction}"</p>
                </div>
              </div>
            )}

            {/* Traitement des objections */}
            {Array.isArray(currentResult.commercialProspectingKit?.phonePitch?.objectionResponses) && currentResult.commercialProspectingKit.phonePitch.objectionResponses.length > 0 && (
              <div className="space-y-2.5">
                <span className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-2">
                  <HelpCircle className="w-3.5 h-3.5 text-amber-600" />
                  <span>Traitement des objections ({currentResult.commercialProspectingKit.phonePitch.objectionResponses.length}) :</span>
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {currentResult.commercialProspectingKit.phonePitch.objectionResponses.map((obj, i) => (
                    <div key={i} className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5 flex flex-col justify-between">
                      <div>
                        <span className="text-[10px] font-black uppercase text-rose-700 tracking-wider">Objection #{i + 1}</span>
                        <p className="text-xs font-bold text-slate-900">« {obj.objection} »</p>
                        <p className="text-xs text-slate-700 pt-1 border-t border-slate-200 leading-relaxed">
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

            {/* Emails */}
            {currentResult.commercialProspectingKit?.emailProspecting && (
              <div className="space-y-2">
                <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                  Modèles d'emails d'approche :
                </span>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {Object.entries(currentResult.commercialProspectingKit.emailProspecting).map(([key, mail]: any) => (
                    mail && (
                      <div key={key} className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                        <div className="flex items-center justify-between">
                          <span className="text-[11px] font-black uppercase text-slate-700">
                            Email : {key === 'legalSecurity' ? 'Sécurité Juridique' : key === 'businessTransformation' ? 'Opportunité Marchés' : key === 'relanceSubvention' ? 'Subvention Bpifrance' : key.replace(/([A-Z])/g, ' $1')}
                          </span>
                          <button
                            onClick={() => handleCopyEmail(key, mail.body)}
                            className="flex items-center space-x-1 px-2.5 py-1 rounded bg-slate-200 hover:bg-slate-300 text-slate-800 text-[10px] font-bold transition-colors cursor-pointer"
                          >
                            {copiedEmailKey === key ? <Check className="w-3 h-3 text-emerald-600" /> : <Mail className="w-3 h-3" />}
                            <span>{copiedEmailKey === key ? 'Copié' : 'Copier'}</span>
                          </button>
                        </div>
                        <div className="text-xs font-bold text-emerald-700">{mail.subject}</div>
                        <div className="text-[11px] text-slate-600 whitespace-pre-wrap leading-relaxed">
                          {mail.body}
                        </div>
                      </div>
                    )
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* BLOC 4 : RECOMMANDATIONS (PLAN DE MISE EN CONFORMITÉ & MISSIONS) */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-6 shadow-sm">
            <h3 className="text-sm font-black text-teal-800 uppercase tracking-wider flex items-center space-x-2">
              <CheckCircle2 className="w-4 h-4 text-teal-600" />
              <span>Recommandations & Plan d'Action</span>
            </h3>

            {/* Steps */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              {(currentResult.clientSummary?.stepsToCompliance || []).map((s, idx) => (
                <div key={idx} className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                  <div className="w-6 h-6 rounded-full bg-teal-600 text-white font-black text-xs flex items-center justify-center">
                    {s.step || idx + 1}
                  </div>
                  <h4 className="text-xs font-bold text-slate-900">{s.title}</h4>
                  <p className="text-[11px] text-slate-600 leading-relaxed">{s.description}</p>
                </div>
              ))}
            </div>

            {/* Business Opportunities */}
            {(currentResult.businessOpportunities || []).length > 0 && (
              <div className="space-y-3 pt-2">
                <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                  Missions d'accompagnement préconisées :
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {(currentResult.businessOpportunities || []).map((opp, idx) => (
                    <div key={idx} className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                      <div className="flex justify-between items-start">
                        <h4 className="text-xs font-black text-slate-900">{opp.serviceTitle}</h4>
                        <span className="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-black font-mono">
                          {opp.potentialBudget}
                        </span>
                      </div>
                      <p className="text-[11px] text-slate-600 leading-relaxed italic">"{opp.reason}"</p>
                      {opp.subsidiesEligible && (
                        <div className="text-[10px] font-bold text-teal-700 pt-1">
                          Aide : {opp.subsidiesEligible}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* BLOC 5 : MODULE “LISTES SCANS” */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-black text-emerald-800 uppercase tracking-wider flex items-center space-x-2">
                <ClipboardList className="w-4 h-4 text-emerald-600" />
                <span>Module "Listes scans"</span>
              </h3>
              <button
                onClick={() => setIsCreatingList(!isCreatingList)}
                className="flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 border border-slate-200 transition-colors cursor-pointer"
              >
                <Plus className="w-3.5 h-3.5" />
                <span>Créer une liste</span>
              </button>
            </div>

            {isCreatingList && (
              <form onSubmit={handleCreateNewList} className="flex gap-2 animate-fadeIn bg-slate-50 p-3 rounded-2xl border border-slate-200">
                <input
                  type="text"
                  value={newListName}
                  onChange={(e) => setNewListName(e.target.value)}
                  placeholder="Nom de la nouvelle liste (ex : Priorité Bilan Carbone)"
                  className="flex-1 px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-xs text-slate-900 outline-none focus:border-emerald-600"
                  autoFocus
                />
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition-colors cursor-pointer"
                >
                  Ajouter
                </button>
              </form>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
              {availableListes.length === 0 ? (
                <div className="sm:col-span-3 text-center py-4 text-xs text-slate-500 italic">
                  Aucune liste de scans créée. Cliquez sur "Créer une liste" pour organiser vos scans.
                </div>
              ) : (
                availableListes.map((list) => {
                  const isInList = Array.isArray(list.items) && list.items.includes(currentResult.id);
                  return (
                    <button
                      key={list.id}
                      onClick={() => handleToggleItemInList(list.id, currentResult.id)}
                      className={`p-3 rounded-2xl border text-left flex items-center justify-between transition-all cursor-pointer ${
                        isInList
                          ? 'bg-emerald-50 border-emerald-300 text-emerald-950'
                          : 'bg-slate-50 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-white'
                      }`}
                    >
                      <div className="flex items-center space-x-2.5 truncate">
                        <div className={`w-4 h-4 rounded border flex items-center justify-center shrink-0 ${
                          isInList ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300'
                        }`}>
                          {isInList && <Check className="w-3 h-3" />}
                        </div>
                        <span className="text-xs font-bold truncate">{list.nom}</span>
                      </div>
                      {isInList && (
                        <span className="text-[10px] font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded">
                          Classé
                        </span>
                      )}
                    </button>
                  );
                })
              )}
            </div>
          </div>

          {/* MODAL IA : PERSONNALISATION DES SCRIPTS COMMERCIAUX */}
          {currentResult && (
            <CommercialScriptGeneratorModal
              isOpen={isScriptGeneratorOpen}
              onClose={() => setIsScriptGeneratorOpen(false)}
              audit={currentResult}
              onKitUpdated={handleKitUpdated}
            />
          )}
        </div>
      )}

      {/* 3. HISTORIQUE DES ENTREPRISES SCANNÉES */}
      {audits.length > 0 && (
        <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div className="flex items-center space-x-2">
              <h3 className="text-sm font-black text-slate-900 uppercase tracking-wider">
                Historique des Scans ({audits.length})
              </h3>
            </div>

            <div className="flex items-center space-x-3">
              <div className="relative">
                <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Rechercher..."
                  className="pl-8 pr-3 py-1.5 rounded-xl bg-white border border-slate-300 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-600"
                />
              </div>

              <select
                value={selectedSort}
                onChange={(e: any) => setSelectedSort(e.target.value)}
                className="px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700 outline-none cursor-pointer"
              >
                <option value="date_desc">📅 Plus récents</option>
                <option value="score_asc">🚨 Risque critique</option>
                <option value="score_desc">🌿 Score élevé</option>
              </select>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {filteredAudits.map((item) => (
              <div
                key={item.id}
                onClick={() => {
                  setCurrentResult(item);
                  onSelectAudit(item);
                }}
                className={`p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between space-y-2 ${
                  currentResult?.id === item.id
                    ? 'bg-emerald-50/60 border-emerald-500 ring-1 ring-emerald-500/40 shadow-xs'
                    : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/60 shadow-2xs'
                }`}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="space-y-0.5 truncate">
                    <div className="text-xs font-bold text-slate-900 truncate">{item.companyName}</div>
                    <div className="text-[10px] text-slate-500 truncate">{item.cleanUrl}</div>
                  </div>
                  <span className={`px-2 py-0.5 rounded text-[10px] font-black shrink-0 ${
                    item.isUnnotable || item.riskScore === null
                      ? 'bg-amber-100 text-amber-800 border border-amber-200'
                      : (item.riskScore ?? 0) < 40
                      ? 'bg-rose-100 text-rose-800 border border-rose-200'
                      : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                  }`}>
                    {item.isUnnotable || item.riskScore === null ? 'Non noté' : `${item.riskScore}/100`}
                  </span>
                </div>

                <div className="flex items-center justify-between text-[10px] text-slate-500 pt-2 border-t border-slate-100">
                  <span>{item.entreprise_secteur || item.sector || 'Services'}</span>
                  {onDeleteAudit && (
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        if (confirm(`Supprimer le scan de ${item.companyName} ?`)) {
                          onDeleteAudit(item.id);
                        }
                      }}
                      className="text-slate-400 hover:text-rose-600 p-1 transition-colors cursor-pointer"
                      title="Supprimer ce scan"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
