import React, { useState } from 'react';
import { 
  Sparkles, 
  X, 
  Loader2, 
  Check, 
  Copy, 
  PhoneCall, 
  Mail, 
  ShieldCheck, 
  Briefcase, 
  Target, 
  MessageSquare, 
  AlertCircle,
  HelpCircle,
  TrendingUp,
  FileText
} from 'lucide-react';
import { GreenwashingAudit, CommercialProspectingKit, CommercialComplements } from '../types.js';

interface CommercialScriptGeneratorModalProps {
  isOpen: boolean;
  onClose: () => void;
  audit: GreenwashingAudit;
  onKitUpdated: (newKit: CommercialProspectingKit) => void;
}

const PERSONA_PRESETS = [
  'Directeur Général / Gérant',
  'Directeur RSE / Développement Durable',
  'Responsable Achats / Supply Chain',
  'Directeur Marketing / Communication',
  'Directeur Technique / QHSE',
];

const STRATEGIC_ANGLES = [
  'Sécurité juridique (Directive Green Claims & sanctions DGCCRF)',
  'Évaluation du Bilan Carbone publié & vérification des preuves',
  'Financement & Achat de Crédits Carbone Locaux LBC (Financeur Contributeur)',
  'Dépôt de Projet certifié Label Bas-Carbone (Porteur LBC - Agri, Forêt, Bâtiment, Transport)',
  'Appels d’offres publics & Avantage concurrentiel',
  'Subvention Bpifrance Diag Décarbon’Action (aide 4 000 € à 6 000 €)',
  'Bilan Carbone Scopes 1-2-3 complet & Trajectoire 2030',
  'Consolidation bienveillante des démarches existantes',
];

const TONE_OPTIONS = [
  'Partenaire bienveillant Climat Local (Alliance constructive, félicitations des actions réelles & sécurisation)',
  'Conseiller stratégique de confiance (Pédagogie positive, valorisation en appels d’offres & subvention Bpifrance)',
  'Expert technique et juridique rigoureux (Prévention bienveillante des sanctions et sécurisation Green Claims)',
];

const OFFER_OPTIONS = [
  'Bilan Carbone Scopes 1-2-3 (subvention Diag Décarbon’Action Bpifrance)',
  'Évaluation & Revue critique du Bilan Carbone existant (Conformité Green Claims & Scopes 1-2-3)',
  'Financement & Achat de Crédits Carbone Locaux LBC (Financeur / Acheteur)',
  'Développement & Dépôt de Projet certifié Label Bas-Carbone (Porteur LBC - Agri, Forêt, Bâtiment RE2020, Transport)',
  'Pack Sécurisation Allégations Green Claims (audit de conformité juridique)',
  'Pack Stratégie RSE & Reporting CSRD (directives européennes)',
  'Pack ACV Écoconception & Données Produits (FDES/EPD)',
];

const QUICK_COMPLEMENT_TAGS = [
  'Approche bienveillante (féliciter les actions réelles)',
  'Mettre en avant l’aide Bpifrance (4 000 €)',
  'Entreprise sincère mais vocabulaire à sécuriser',
  'Ancrage local & projets Label Bas-Carbone',
  'Atout décisif en appels d’offres',
  'PME familiale attachée à sa réputation',
  'Déjà un Bilan Carbone publié',
  'Sous-traitant grands comptes / donneurs d’ordres',
];

export const CommercialScriptGeneratorModal: React.FC<CommercialScriptGeneratorModalProps> = ({
  isOpen,
  onClose,
  audit,
  onKitUpdated,
}) => {
  const existingComplements = audit.commercialProspectingKit?.customComplements;

  const [targetPersona, setTargetPersona] = useState<string>(
    existingComplements?.targetPersona || audit.commercialProspectingKit?.targetPersona || 'Directeur Général / Responsable RSE'
  );
  const [strategicAngle, setStrategicAngle] = useState<string>(
    existingComplements?.strategicAngle || 'Subvention Bpifrance Diag Décarbon’Action (aide 4 000 € à 6 000 €)'
  );
  const [userComplements, setUserComplements] = useState<string>(
    existingComplements?.userComplements || ''
  );
  const [tone, setTone] = useState<string>(
    existingComplements?.tone || TONE_OPTIONS[0]
  );
  const [preferredOffer, setPreferredOffer] = useState<string>(
    existingComplements?.preferredOffer || 'Bilan Carbone Scopes 1-2-3 (subvention Diag Décarbon’Action Bpifrance)'
  );

  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [previewKit, setPreviewKit] = useState<CommercialProspectingKit | null>(
    audit.commercialProspectingKit || null
  );
  const [activeTab, setActiveTab] = useState<'pitch' | 'objections' | 'emails' | 'offer'>('pitch');
  const [copiedKey, setCopiedKey] = useState<string | null>(null);

  // States & handlers for individual email optimization
  const [improveInstructions, setImproveInstructions] = useState({
    legalSecurity: '',
    businessTransformation: '',
    relanceSubvention: ''
  });
  const [isImproving, setIsImproving] = useState({
    legalSecurity: false,
    businessTransformation: false,
    relanceSubvention: false
  });

  const handleEmailChange = (key: 'legalSecurity' | 'businessTransformation' | 'relanceSubvention', field: 'subject' | 'body', val: string) => {
    if (!previewKit || !previewKit.emailProspecting) return;
    const updatedKit = {
      ...previewKit,
      emailProspecting: {
        ...previewKit.emailProspecting,
        [key]: {
          ...previewKit.emailProspecting[key] as any,
          [field]: val
        }
      }
    };
    setPreviewKit(updatedKit);
    onKitUpdated(updatedKit);
  };

  const handleImproveEmail = async (key: 'legalSecurity' | 'businessTransformation' | 'relanceSubvention') => {
    if (!previewKit || !previewKit.emailProspecting || !previewKit.emailProspecting[key]) return;
    const email = previewKit.emailProspecting[key];
    setIsImproving(prev => ({ ...prev, [key]: true }));
    setErrorMsg(null);
    try {
      const res = await fetch('/api/commercial-scripts/improve-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          subject: email.subject,
          body: email.body,
          instructions: improveInstructions[key],
          companyName: audit.companyName
        })
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || "Erreur d'amélioration de l'email.");
      }
      handleEmailChange(key, 'subject', data.subject);
      handleEmailChange(key, 'body', data.body);
      setImproveInstructions(prev => ({ ...prev, [key]: '' }));
      setSuccessMsg('Email optimisé par l\'IA avec succès (Ton Pro & Sincère) !');
      setTimeout(() => setSuccessMsg(null), 3500);
    } catch (err: any) {
      setErrorMsg(err.message || "Erreur d'amélioration.");
    } finally {
      setIsImproving(prev => ({ ...prev, [key]: false }));
    }
  };

  if (!isOpen) return null;

  const handleCopy = (text: string, key: string) => {
    navigator.clipboard.writeText(text);
    setCopiedKey(key);
    setTimeout(() => setCopiedKey(null), 2500);
  };

  const handleAddTag = (tag: string) => {
    setUserComplements(prev => {
      const cleanPrev = prev.trim();
      if (!cleanPrev) return tag;
      if (cleanPrev.includes(tag)) return cleanPrev;
      return `${cleanPrev}, ${tag}`;
    });
  };

  const handleGenerate = async () => {
    setIsLoading(true);
    setErrorMsg(null);
    setSuccessMsg(null);

    try {
      const res = await fetch('/api/commercial-scripts/regenerate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          auditId: audit.id,
          url: audit.url,
          companyName: audit.companyName,
          sector: audit.sector || audit.entreprise_secteur,
          headcount: audit.headcount || audit.entreprise_salaries,
          turnover: audit.turnover || audit.entreprise_ca,
          riskScore: audit.riskScore,
          riskLevel: audit.riskLevel,
          claims: audit.detectedClaims,
          piliers: audit.piliers_evaluation,
          opportuniteCommerciale: audit.opportunite_commerciale,
          // Complements :
          targetPersona,
          strategicAngle,
          userComplements,
          tone,
          preferredOffer
        })
      });

      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Erreur lors de la génération IA.');
      }

      setPreviewKit(data.commercialProspectingKit);
      onKitUpdated(data.commercialProspectingKit);
      setSuccessMsg('Scripts commerciaux adaptés avec succès par l’IA !');
      setTimeout(() => setSuccessMsg(null), 4000);
    } catch (err: any) {
      setErrorMsg(err.message || 'Une erreur est survenue lors de la génération.');
    } finally {
      setIsLoading(false);
    }
  };

  const getFullPitchText = (kit: CommercialProspectingKit) => {
    return `[ACCROCHE]\n"${kit.phonePitch.hook}"\n\n[FAIT MARQUANT]\n"${kit.phonePitch.keyFact}"\n\n[AVERTISSEMENT JURIDIQUE]\n"${kit.phonePitch.legalWarning}"\n\n[APPEL À L'ACTION]\n"${kit.phonePitch.callToAction}"`;
  };

  const getFullObjectionsText = (kit: CommercialProspectingKit) => {
    return (kit.phonePitch.objectionResponses || [])
      .map(o => `OBJECTION : "${o.objection}"\n-> RÉPONSE : "${o.answer}"`)
      .join('\n\n');
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/40 backdrop-blur-xs overflow-y-auto">
      <div className="relative w-full max-w-4xl max-h-[92vh] bg-white border border-slate-200 rounded-3xl shadow-2xl flex flex-col overflow-hidden text-slate-800 my-auto animate-fadeIn">
        
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-5 border-b border-slate-200 bg-slate-50">
          <div className="flex items-center space-x-3 min-w-0">
            <div className="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black border border-indigo-100">
              <Sparkles className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <h2 className="text-base sm:text-lg font-black text-slate-900 truncate">
                  Module IA • Personnalisation des Scripts Commerciaux
                </h2>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                  IA Spécialisée B2B
                </span>
              </div>
              <p className="text-xs text-slate-500">
                Adaptez le pitch téléphonique, les emails et les objections selon vos compléments terrain pour <strong className="text-slate-800">{audit.companyName}</strong>.
              </p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
            title="Fermer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scrollable Body */}
        <div className="flex-1 overflow-y-auto p-6 space-y-6">

          {/* Entreprise context recap badge */}
          <div className="flex flex-wrap items-center gap-2 p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
            <span className="font-bold text-slate-500">Contexte Entreprise :</span>
            <span className="px-2.5 py-0.5 rounded-md bg-white border border-slate-200 font-mono text-[11px] text-slate-900 font-bold">
              {audit.companyName}
            </span>
            <span className="text-slate-400">•</span>
            <span>{audit.sector || audit.entreprise_secteur || 'Services'}</span>
            <span className="text-slate-400">•</span>
            <span className="text-slate-500">{audit.headcount || audit.entreprise_salaries || 'PME'}</span>
            <span className="text-slate-400">•</span>
            <span className={`font-black ${audit.riskScore >= 70 ? 'text-emerald-600' : audit.riskScore >= 40 ? 'text-amber-600' : 'text-rose-600'}`}>
              Score : {audit.riskScore}/100
            </span>
            {audit.detectedClaims?.length ? (
              <span className="px-2 py-0.5 rounded bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">
                {audit.detectedClaims.length} allégation(s) auditée(s)
              </span>
            ) : null}
          </div>

          {/* FORMULAIRE DES COMPLÉMENTS */}
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-5">
            <h3 className="text-xs font-black uppercase tracking-wider text-indigo-700 flex items-center space-x-2">
              <Target className="w-4 h-4" />
              <span>Vos Compléments & Directives de Prospection</span>
            </h3>

            {/* 1. Cible / Persona */}
            <div className="space-y-2">
              <label className="text-xs font-bold text-slate-700 flex items-center justify-between">
                <span>1. Interlocuteur / Persona ciblé</span>
                <span className="text-[10px] text-slate-500 font-normal">Cliquez pour pré-remplir ou saisissez librement</span>
              </label>
              <div className="flex flex-wrap gap-1.5 pb-1">
                {PERSONA_PRESETS.map((p, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => setTargetPersona(p)}
                    className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer ${
                      targetPersona === p 
                        ? 'bg-indigo-600 text-white shadow-xs' 
                        : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'
                    }`}
                  >
                    {p}
                  </button>
                ))}
              </div>
              <input
                type="text"
                value={targetPersona}
                onChange={(e) => setTargetPersona(e.target.value)}
                placeholder="Ex : Directeur Général, Directeur RSE, Responsable Achats..."
                className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            {/* 2. Angle stratégique */}
            <div className="space-y-2">
              <label className="text-xs font-bold text-slate-700 flex items-center justify-between">
                <span>2. Angle d’attaque prioritaire</span>
                <span className="text-[10px] text-slate-500 font-normal">Axe majeur de persuasion</span>
              </label>
              <div className="flex flex-wrap gap-1.5 pb-1">
                {STRATEGIC_ANGLES.map((a, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => setStrategicAngle(a)}
                    className={`px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all cursor-pointer ${
                      strategicAngle === a 
                        ? 'bg-indigo-600 text-white shadow-xs' 
                        : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'
                    }`}
                  >
                    {a.split('(')[0].trim()}
                  </button>
                ))}
              </div>
              <input
                type="text"
                value={strategicAngle}
                onChange={(e) => setStrategicAngle(e.target.value)}
                placeholder="Ex : Sécurité juridique et subvention Diag Décarbon’Action..."
                className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            {/* 3. Tonalité & Offre Climat Local */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 block">3. Tonalité du pitch</label>
                <select
                  value={tone}
                  onChange={(e) => setTone(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                  {TONE_OPTIONS.map((t, idx) => (
                    <option key={idx} value={t}>{t}</option>
                  ))}
                </select>
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-bold text-slate-700 block">4. Offre Climat Local à valoriser</label>
                <select
                  value={preferredOffer}
                  onChange={(e) => setPreferredOffer(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                  {OFFER_OPTIONS.map((o, idx) => (
                    <option key={idx} value={o}>{o}</option>
                  ))}
                </select>
              </div>
            </div>

            {/* 5. Compléments libres et notes terrain */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-700 block">
                  5. Compléments libres & Informations terrain du consultant
                </label>
                <span className="text-[10px] text-slate-500">Optionnel mais fortement recommandé</span>
              </div>

              {/* Quick tags */}
              <div className="flex flex-wrap items-center gap-1.5">
                <span className="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Ajouter vite :</span>
                {QUICK_COMPLEMENT_TAGS.map((tag, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => handleAddTag(tag)}
                    className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-600 border border-slate-200 transition-colors cursor-pointer"
                  >
                    + {tag}
                  </button>
                ))}
              </div>

              <textarea
                rows={3}
                value={userComplements}
                onChange={(e) => setUserComplements(e.target.value)}
                placeholder="Ex : L’entreprise a déjà réalisé un premier bilan carbone interne mais souhaite auditer ses allégations avant de postuler à un grand marché public régional dans 2 mois. Le dirigeant est très attentif au budget et aux aides Bpifrance..."
                className="w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors leading-relaxed"
              />
            </div>

            {/* Bouton de génération */}
            <div className="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200">
              <span className="text-xs text-slate-500">
                L’IA va régénérer instantanément le script téléphonique, les objections et 3 modèles d’emails.
              </span>

              <button
                type="button"
                onClick={handleGenerate}
                disabled={isLoading}
                className="w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider flex items-center justify-center space-x-2 shadow-xs disabled:opacity-50 cursor-pointer transition-all active:scale-95"
              >
                {isLoading ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin text-white" />
                    <span>Génération des scripts IA en cours...</span>
                  </>
                ) : (
                  <>
                    <Sparkles className="w-4 h-4 text-amber-300" />
                    <span>⚡ Générer les scripts avec l’IA</span>
                  </>
                )}
              </button>
            </div>

            {errorMsg && (
              <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center space-x-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                <span>{errorMsg}</span>
              </div>
            )}

            {successMsg && (
              <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-2">
                <Check className="w-4 h-4 shrink-0 text-emerald-600" />
                <span>{successMsg}</span>
              </div>
            )}
          </div>

          {/* RÉSULTATS GÉNÉRÉS */}
          {previewKit && (
            <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden space-y-4 p-5 shadow-xs">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div className="flex items-center space-x-2">
                  <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
                  <h3 className="text-sm font-black text-slate-900 uppercase tracking-wider">
                    Kit Commercial Personnalisé ({previewKit.targetPersona})
                  </h3>
                </div>

                {previewKit.customComplements && (
                  <span className="text-[10px] text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                    Angle : <strong className="text-indigo-700">{previewKit.customComplements.strategicAngle?.split('(')[0]}</strong>
                  </span>
                )}
              </div>

              {previewKit.executiveSummary && (
                <p className="text-xs text-slate-700 italic bg-slate-50 p-3 rounded-xl border border-slate-200">
                  « {previewKit.executiveSummary} »
                </p>
              )}

              {/* Onglets de navigation des scripts */}
              <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-2">
                <button
                  type="button"
                  onClick={() => setActiveTab('pitch')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider flex items-center space-x-1.5 transition-colors cursor-pointer ${
                    activeTab === 'pitch' 
                      ? 'bg-indigo-600 text-white shadow-xs' 
                      : 'bg-slate-100 text-slate-600 hover:text-slate-900'
                  }`}
                >
                  <PhoneCall className="w-3.5 h-3.5" />
                  <span>Script Appel</span>
                </button>

                <button
                  type="button"
                  onClick={() => setActiveTab('objections')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider flex items-center space-x-1.5 transition-colors cursor-pointer ${
                    activeTab === 'objections' 
                      ? 'bg-indigo-600 text-white shadow-xs' 
                      : 'bg-slate-100 text-slate-600 hover:text-slate-900'
                  }`}
                >
                  <HelpCircle className="w-3.5 h-3.5" />
                  <span>Objections ({previewKit.phonePitch?.objectionResponses?.length || 0})</span>
                </button>

                <button
                  type="button"
                  onClick={() => setActiveTab('emails')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider flex items-center space-x-1.5 transition-colors cursor-pointer ${
                    activeTab === 'emails' 
                      ? 'bg-indigo-600 text-white shadow-xs' 
                      : 'bg-slate-100 text-slate-600 hover:text-slate-900'
                  }`}
                >
                  <Mail className="w-3.5 h-3.5" />
                  <span>Emails ({Object.keys(previewKit.emailProspecting || {}).length})</span>
                </button>

                <button
                  type="button"
                  onClick={() => setActiveTab('offer')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider flex items-center space-x-1.5 transition-colors cursor-pointer ${
                    activeTab === 'offer' 
                      ? 'bg-indigo-600 text-white shadow-xs' 
                      : 'bg-slate-100 text-slate-600 hover:text-slate-900'
                  }`}
                >
                  <Briefcase className="w-3.5 h-3.5" />
                  <span>Offre Climat Local</span>
                </button>
              </div>

              {/* TAB 1: SCRIPT TÉLÉPHONIQUE */}
              {activeTab === 'pitch' && (
                <div className="space-y-4 animate-fadeIn">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-slate-500 uppercase">Trame d'appel téléphonique :</span>
                    <button
                      type="button"
                      onClick={() => handleCopy(getFullPitchText(previewKit), 'full_pitch')}
                      className="flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition-colors cursor-pointer"
                    >
                      {copiedKey === 'full_pitch' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                      <span>{copiedKey === 'full_pitch' ? 'Pitch copié !' : 'Copier script complet'}</span>
                    </button>
                  </div>

                  <div className="space-y-3">
                    <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                      <span className="text-[10px] font-black uppercase text-amber-700 block tracking-wider">1. Accroche (10 secondes)</span>
                      <p className="text-xs text-slate-800 italic font-medium leading-relaxed">
                        "{previewKit.phonePitch?.hook}"
                      </p>
                    </div>

                    <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                      <span className="text-[10px] font-black uppercase text-cyan-700 block tracking-wider">2. Fait marquant / Constat</span>
                      <p className="text-xs text-slate-800 italic font-medium leading-relaxed">
                        "{previewKit.phonePitch?.keyFact}"
                      </p>
                    </div>

                    <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                      <span className="text-[10px] font-black uppercase text-rose-700 block tracking-wider">3. Avertissement juridique & Preuve</span>
                      <p className="text-xs text-slate-800 italic font-medium leading-relaxed">
                        "{previewKit.phonePitch?.legalWarning}"
                      </p>
                    </div>

                    <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                      <span className="text-[10px] font-black uppercase text-emerald-700 block tracking-wider">4. Appel à l'action (Call to Action)</span>
                      <p className="text-xs text-slate-900 not-italic font-bold leading-relaxed">
                        "{previewKit.phonePitch?.callToAction}"
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* TAB 2: OBJECTIONS & RÉPONSES */}
              {activeTab === 'objections' && (
                <div className="space-y-4 animate-fadeIn">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-slate-500 uppercase">Réponses prêtes à l'emploi face aux objections :</span>
                    <button
                      type="button"
                      onClick={() => handleCopy(getFullObjectionsText(previewKit), 'all_objections')}
                      className="flex items-center space-x-1 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold border border-indigo-200 transition-colors cursor-pointer"
                    >
                      {copiedKey === 'all_objections' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                      <span>{copiedKey === 'all_objections' ? 'Copié !' : 'Copier toutes les réponses'}</span>
                    </button>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {(previewKit.phonePitch?.objectionResponses || []).map((obj, i) => (
                      <div key={i} className="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 flex flex-col justify-between">
                        <div className="space-y-1.5">
                          <div className="flex items-center justify-between">
                            <span className="text-[10px] font-black uppercase text-rose-700 tracking-wider">
                              Objection #{i + 1}
                            </span>
                            <button
                              type="button"
                              onClick={() => handleCopy(`"${obj.objection}"\n-> "${obj.answer}"`, `obj_${i}`)}
                              className="text-[10px] text-slate-500 hover:text-indigo-600 flex items-center space-x-1 cursor-pointer"
                            >
                              {copiedKey === `obj_${i}` ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                              <span>{copiedKey === `obj_${i}` ? 'Copié' : 'Copier'}</span>
                            </button>
                          </div>
                          <p className="text-xs font-bold text-slate-900">« {obj.objection} »</p>
                          <p className="text-xs text-slate-700 leading-relaxed pt-1 border-t border-slate-200">
                            <strong className="text-emerald-700">Réponse : </strong>
                            "{obj.answer}"
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* TAB 3: EMAILS DE PROSPECTION */}
              {activeTab === 'emails' && (
                <div className="space-y-4 animate-fadeIn">
                  <div className="grid grid-cols-1 gap-6">
                    {/* Email 1: Juridique */}
                    {previewKit.emailProspecting?.legalSecurity && (
                      <div className="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center space-x-2">
                            <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">
                              Email 1 • Sécurité Juridique & Conformité
                            </span>
                          </div>
                          <button
                            type="button"
                            onClick={() => handleCopy(`Objet : ${previewKit.emailProspecting.legalSecurity.subject}\n\n${previewKit.emailProspecting.legalSecurity.body}`, 'email_legal')}
                            className="flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition-all cursor-pointer shadow-xs"
                          >
                            {copiedKey === 'email_legal' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                            <span>{copiedKey === 'email_legal' ? 'Copié' : 'Copier'}</span>
                          </button>
                        </div>
                        
                        <div className="space-y-2">
                          <div className="flex items-center space-x-2">
                            <span className="text-2xs font-bold text-slate-500 uppercase shrink-0">Objet :</span>
                            <input
                              type="text"
                              value={previewKit.emailProspecting.legalSecurity.subject}
                              onChange={(e) => handleEmailChange('legalSecurity', 'subject', e.target.value)}
                              className="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 font-medium focus:outline-none focus:border-blue-500 bg-white"
                            />
                          </div>
                          <textarea
                            rows={12}
                            value={previewKit.emailProspecting.legalSecurity.body}
                            onChange={(e) => handleEmailChange('legalSecurity', 'body', e.target.value)}
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed focus:outline-none focus:border-blue-500 bg-white font-sans"
                            placeholder="Corps du mail commercial..."
                          />
                        </div>

                        {/* IA Improvement Module */}
                        <div className="p-3 bg-white rounded-xl border border-slate-200 space-y-2.5 shadow-2xs">
                          <div className="flex items-center space-x-1.5">
                            <Sparkles className="w-3.5 h-3.5 text-blue-600 animate-pulse" />
                            <span className="text-[10px] font-black text-slate-700 uppercase tracking-wide">
                              Améliorer ce mail avec l'IA (Ton Pro, Sincère, pas d'exagération)
                            </span>
                          </div>
                          <div className="flex flex-col sm:flex-row gap-2">
                            <input
                              type="text"
                              placeholder="Consignes facultatives (ex: plus court, mettre plus en avant la RE2020, insister sur les sanctions...)"
                              value={improveInstructions.legalSecurity}
                              onChange={(e) => setImproveInstructions({ ...improveInstructions, legalSecurity: e.target.value })}
                              className="flex-1 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-2xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                            />
                            <button
                              type="button"
                              onClick={() => handleImproveEmail('legalSecurity')}
                              disabled={isImproving.legalSecurity}
                              className="px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-2xs font-bold transition-all cursor-pointer whitespace-nowrap disabled:opacity-50"
                            >
                              {isImproving.legalSecurity ? 'Optimisation...' : '⚡ Ajuster par l\'IA'}
                            </button>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Email 2: Business & Avantage concurrentiel */}
                    {previewKit.emailProspecting?.businessTransformation && (
                      <div className="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center space-x-2">
                            <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">
                              Email 2 • Avantage Concurrentiel & Appels d’Offres
                            </span>
                          </div>
                          <button
                            type="button"
                            onClick={() => handleCopy(`Objet : ${previewKit.emailProspecting.businessTransformation.subject}\n\n${previewKit.emailProspecting.businessTransformation.body}`, 'email_biz')}
                            className="flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition-all cursor-pointer shadow-xs"
                          >
                            {copiedKey === 'email_biz' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                            <span>{copiedKey === 'email_biz' ? 'Copié' : 'Copier'}</span>
                          </button>
                        </div>

                        <div className="space-y-2">
                          <div className="flex items-center space-x-2">
                            <span className="text-2xs font-bold text-slate-500 uppercase shrink-0">Objet :</span>
                            <input
                              type="text"
                              value={previewKit.emailProspecting.businessTransformation.subject}
                              onChange={(e) => handleEmailChange('businessTransformation', 'subject', e.target.value)}
                              className="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 font-medium focus:outline-none focus:border-blue-500 bg-white"
                            />
                          </div>
                          <textarea
                            rows={12}
                            value={previewKit.emailProspecting.businessTransformation.body}
                            onChange={(e) => handleEmailChange('businessTransformation', 'body', e.target.value)}
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed focus:outline-none focus:border-blue-500 bg-white font-sans"
                            placeholder="Corps du mail commercial..."
                          />
                        </div>

                        {/* IA Improvement Module */}
                        <div className="p-3 bg-white rounded-xl border border-slate-200 space-y-2.5 shadow-2xs">
                          <div className="flex items-center space-x-1.5">
                            <Sparkles className="w-3.5 h-3.5 text-blue-600 animate-pulse" />
                            <span className="text-[10px] font-black text-slate-700 uppercase tracking-wide">
                              Améliorer ce mail avec l'IA (Ton Pro, Sincère, pas d'exagération)
                            </span>
                          </div>
                          <div className="flex flex-col sm:flex-row gap-2">
                            <input
                              type="text"
                              placeholder="Consignes facultatives (ex: plus court, mentionner nos projets Label Bas-Carbone...)"
                              value={improveInstructions.businessTransformation}
                              onChange={(e) => setImproveInstructions({ ...improveInstructions, businessTransformation: e.target.value })}
                              className="flex-1 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-2xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                            />
                            <button
                              type="button"
                              onClick={() => handleImproveEmail('businessTransformation')}
                              disabled={isImproving.businessTransformation}
                              className="px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-2xs font-bold transition-all cursor-pointer whitespace-nowrap disabled:opacity-50"
                            >
                              {isImproving.businessTransformation ? 'Optimisation...' : '⚡ Ajuster par l\'IA'}
                            </button>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Email 3: Financement / Subvention */}
                    {(previewKit.emailProspecting?.relanceSubvention || (previewKit.emailProspecting as any)?.localCarbonContribution) && (
                      <div className="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center space-x-2">
                            <span className="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wider">
                              Email 3 • Financement & Subvention Bpifrance
                            </span>
                          </div>
                          <button
                            type="button"
                            onClick={() => {
                              const e = previewKit.emailProspecting?.relanceSubvention || (previewKit.emailProspecting as any)?.localCarbonContribution;
                              handleCopy(`Objet : ${e.subject}\n\n${e.body}`, 'email_sub');
                            }}
                            className="flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition-all cursor-pointer shadow-xs"
                          >
                            {copiedKey === 'email_sub' ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                            <span>{copiedKey === 'email_sub' ? 'Copié' : 'Copier'}</span>
                          </button>
                        </div>
                        {(() => {
                          const e = previewKit.emailProspecting?.relanceSubvention || (previewKit.emailProspecting as any)?.localCarbonContribution;
                          const emailKey = previewKit.emailProspecting?.relanceSubvention ? 'relanceSubvention' : 'localCarbonContribution' as any;
                          return (
                            <>
                              <div className="space-y-2">
                                <div className="flex items-center space-x-2">
                                  <span className="text-2xs font-bold text-slate-500 uppercase shrink-0">Objet :</span>
                                  <input
                                    type="text"
                                    value={e.subject}
                                    onChange={(val) => handleEmailChange(emailKey, 'subject', val.target.value)}
                                    className="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 font-medium focus:outline-none focus:border-blue-500 bg-white"
                                  />
                                </div>
                                <textarea
                                  rows={12}
                                  value={e.body}
                                  onChange={(val) => handleEmailChange(emailKey, 'body', val.target.value)}
                                  className="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed focus:outline-none focus:border-blue-500 bg-white font-sans"
                                  placeholder="Corps du mail commercial..."
                                />
                              </div>

                              {/* IA Improvement Module */}
                              <div className="p-3 bg-white rounded-xl border border-slate-200 space-y-2.5 shadow-2xs">
                                <div className="flex items-center space-x-1.5">
                                  <Sparkles className="w-3.5 h-3.5 text-blue-600 animate-pulse" />
                                  <span className="text-[10px] font-black text-slate-700 uppercase tracking-wide">
                                    Améliorer ce mail avec l'IA (Ton Pro, Sincère, pas d'exagération)
                                  </span>
                                </div>
                                <div className="flex flex-col sm:flex-row gap-2">
                                  <input
                                    type="text"
                                    placeholder="Consignes facultatives (ex: insister sur la subvention de 4000€, reste à charge client 6000€...)"
                                    value={improveInstructions.relanceSubvention}
                                    onChange={(val) => setImproveInstructions({ ...improveInstructions, relanceSubvention: val.target.value })}
                                    className="flex-1 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-2xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                                  />
                                  <button
                                    type="button"
                                    onClick={() => handleImproveEmail(emailKey)}
                                    disabled={isImproving.relanceSubvention}
                                    className="px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-2xs font-bold transition-all cursor-pointer whitespace-nowrap disabled:opacity-50"
                                  >
                                    {isImproving.relanceSubvention ? 'Optimisation...' : '⚡ Ajuster par l\'IA'}
                                  </button>
                                </div>
                              </div>
                            </>
                          );
                        })()}
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* TAB 4: OFFRE CLIMAT LOCAL */}
              {activeTab === 'offer' && (
                <div className="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-4 animate-fadeIn">
                  <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                      <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Mission recommandée</span>
                      <h4 className="text-base font-black text-slate-900">{previewKit.climatLocalProposal?.recommendedPackage}</h4>
                    </div>
                    <span className="px-3 py-1 rounded-xl text-sm font-black font-mono bg-emerald-100 text-emerald-800 border border-emerald-200">
                      {previewKit.climatLocalProposal?.pricingEstimate}
                    </span>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div className="p-3 rounded-lg bg-white border border-slate-200 space-y-1">
                      <span className="text-[10px] font-bold text-slate-500 uppercase">Subventions mobilisables</span>
                      <p className="text-emerald-700 font-semibold">{previewKit.climatLocalProposal?.subsidies}</p>
                    </div>

                    <div className="p-3 rounded-lg bg-white border border-slate-200 space-y-1">
                      <span className="text-[10px] font-bold text-slate-500 uppercase">Délai estimé</span>
                      <p className="text-slate-800 font-semibold">{previewKit.climatLocalProposal?.timeline}</p>
                    </div>
                  </div>

                  {previewKit.climatLocalProposal?.closingArgument && (
                    <div className="p-3.5 rounded-lg bg-indigo-50 border border-indigo-200 space-y-1">
                      <span className="text-[10px] font-bold text-indigo-700 uppercase block">Argument clé de closing</span>
                      <p className="text-xs text-slate-800 italic leading-relaxed font-medium">
                        "{previewKit.climatLocalProposal.closingArgument}"
                      </p>
                    </div>
                  )}
                </div>
              )}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 border border-slate-200 transition-colors cursor-pointer"
          >
            Fermer
          </button>

          {previewKit && (
            <button
              type="button"
              onClick={onClose}
              className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition-colors flex items-center space-x-1.5 cursor-pointer shadow-xs"
            >
              <Check className="w-4 h-4" />
              <span>Appliquer au dossier</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
};
