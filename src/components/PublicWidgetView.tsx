import React, { useState, useEffect } from 'react';
import { 
  Building2, 
  ShieldAlert, 
  ArrowRight, 
  Lock, 
  CheckCircle2, 
  Globe, 
  Scale, 
  Loader2, 
  Briefcase, 
  TrendingUp, 
  Users, 
  Mail, 
  History, 
  Check,
  Send,
  Eye,
  MessageSquare,
  User,
  Phone,
  Sparkles,
  ExternalLink
} from 'lucide-react';
import { AgencyProfile, GreenwashingAudit, ListeRecord } from '../types.js';
import { ClimatLocalLogo } from './ClimatLocalLogo.tsx';
import { MentionsLegalesModal } from './MentionsLegalesModal.tsx';

interface PublicWidgetViewProps {
  agency: AgencyProfile;
  pageTexts: Record<string, string>;
  onOpenLogin: () => void;
  onAuditCompleted: (audit: GreenwashingAudit) => void;
}

export const PublicWidgetView: React.FC<PublicWidgetViewProps> = ({
  agency,
  pageTexts,
  onOpenLogin,
  onAuditCompleted,
}) => {
  const [url, setUrl] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [scanResult, setScanResult] = useState<GreenwashingAudit | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isMentionsOpen, setIsMentionsOpen] = useState(false);
  const [loadingStep, setLoadingStep] = useState(0);

  // Formulaire d'accompagnement Climat Local
  const [leadName, setLeadName] = useState('');
  const [leadEmail, setLeadEmail] = useState('');
  const [leadPhone, setLeadPhone] = useState('');
  const [leadMessageText, setLeadMessageText] = useState('');
  const [leadSubmitted, setLeadSubmitted] = useState(false);
  const [leadSubmitting, setLeadSubmitting] = useState(false);
  const [leadFeedback, setLeadFeedback] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!url.trim()) return;

    setIsLoading(true);
    setErrorMessage(null);
    setScanResult(null);
    setLeadSubmitted(false);
    setLeadFeedback(null);
    setLoadingStep(1);

    const interval = setInterval(() => {
      setLoadingStep((prev) => (prev < 4 ? prev + 1 : prev));
    }, 1100);

    try {
      const res = await fetch('/api/audit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          url: url.trim(),
          type_audit: 'full'
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.message || data.error || 'Erreur lors de l’analyse du site web');
      }

      const auditData: GreenwashingAudit = data.audit || data;
      setScanResult(auditData);
      onAuditCompleted(auditData);
    } catch (err: any) {
      setErrorMessage(err.message || 'Une erreur est survenue lors de l’analyse.');
    } finally {
      clearInterval(interval);
      setIsLoading(false);
      setLoadingStep(0);
    }
  };

  const handleLeadSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!leadEmail.trim() || !leadEmail.includes('@')) return;

    setLeadSubmitting(true);
    try {
      const res = await fetch('/api/public/lead', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: leadEmail.trim(),
          name: leadName.trim(),
          phone: leadPhone.trim(),
          company: scanResult?.companyName || scanResult?.cleanUrl || url,
          scanUrl: scanResult?.url || url,
          auditId: scanResult?.id,
          notes: leadMessageText.trim() || `Demande d'accompagnement par les experts Climat Local pour ${scanResult?.companyName || url}`
        })
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setLeadSubmitted(true);
        setLeadFeedback(`✅ Votre demande a bien été transmise aux experts de Climat Local ! Un consultant référent prendra contact avec vous à l'adresse ${leadEmail.trim()}.`);
      } else {
        setLeadFeedback(data.error || 'Erreur lors de l’envoi de vos coordonnées.');
      }
    } catch {
      setLeadFeedback('Erreur de connexion au serveur.');
    } finally {
      setLeadSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f8fafc] text-slate-800 flex flex-col selection:bg-emerald-600 selection:text-white">
      {/* Top Header */}
      <header className="border-b border-slate-200 bg-white sticky top-0 z-40 shadow-2xs">
        <div className="max-w-[1850px] mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <ClimatLocalLogo size="md" />
            <div>
              <div className="flex items-center space-x-2">
                <span className="text-xl sm:text-2xl font-black tracking-tight text-slate-900 font-serif">
                  GreenHumble
                </span>
              </div>
              <p className="text-xs text-slate-500 font-medium">
                Restons humbles face à nos responsabilités
              </p>
            </div>
          </div>

          <div className="flex items-center space-x-3">
            <button
              onClick={onOpenLogin}
              className="flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition-all cursor-pointer"
            >
              <Lock className="w-4 h-4" />
              <span>Connexion (Mot de passe)</span>
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 max-w-[1300px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
        {/* Hero Section */}
        <div className="text-center space-y-3 max-w-3xl mx-auto">
          <div className="text-xs font-black uppercase tracking-wider text-emerald-800 inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200">
            <Scale className="w-3.5 h-3.5 text-emerald-600" />
            <span>Directive Green Claims (UE) 2024/825 & Décret 2022-539</span>
          </div>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 font-serif leading-tight">
            {pageTexts.hero_title || 'Évaluer la sincérité de votre démarche durable'}
          </h1>
          <p className="text-sm sm:text-base text-slate-600 leading-relaxed">
            {pageTexts.widget_subtitle || 'Vérifiez en 30 secondes la conformités de vos supports'}
          </p>
        </div>

        {/* Scanner Form */}
        <div className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <label htmlFor="public-url-input" className="block text-xs font-bold uppercase tracking-wider text-slate-600">
                Adresse web de l’entreprise cible à auditer
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                  <Globe className="w-5 h-5 text-emerald-600" />
                </div>
                <input
                  id="public-url-input"
                  type="text"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="ex : www.mon-entreprise.fr ou mon-prospect.com"
                  disabled={isLoading}
                  required
                  className="w-full pl-12 pr-4 py-3.5 rounded-2xl bg-white border border-slate-200 text-slate-900 placeholder-slate-400 text-sm font-medium focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 focus:outline-none transition-all shadow-xs"
                />
              </div>
            </div>

            {/* Bouton de scan direct */}
            <button
              type="submit"
              disabled={isLoading || !url.trim()}
              className={`w-full py-4 px-6 rounded-2xl font-black text-sm flex items-center justify-center space-x-2 shadow-xs transition-all ${
                isLoading || !url.trim()
                  ? 'bg-slate-100 text-slate-400 cursor-not-allowed opacity-60'
                  : 'bg-emerald-600 hover:bg-emerald-700 text-white active:scale-[0.99] cursor-pointer'
              }`}
            >
              {isLoading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin text-white" />
                  <span>Analyse en cours ({loadingStep}/4)...</span>
                </>
              ) : (
                <>
                  <Scale className="w-4 h-4 text-white" />
                  <span>Lancer l'analyse</span>
                  <ArrowRight className="w-4 h-4 text-white ml-1" />
                </>
              )}
            </button>
          </form>

          {errorMessage && (
            <div className="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold flex items-center space-x-2">
              <ShieldAlert className="w-5 h-5 shrink-0 text-rose-600" />
              <span>{errorMessage}</span>
            </div>
          )}
        </div>

        {/* SCAN RESULT DISPLAY: MINI RÉSUMÉ AVEC LA NOTE + APPEL À LAISSER SES COORDONNÉES */}
        {scanResult && (
          <div className="space-y-6 animate-fadeIn">
            {/* MINI RÉSUMÉ AVEC LA NOTE */}
            <div className="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200">
                <div className="space-y-1">
                  <div className="flex items-center space-x-2">
                    <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-black uppercase tracking-wider">
                      Résultat d'analyse • {scanResult.cleanUrl || scanResult.url}
                    </span>
                    <span className="text-slate-500 text-xs font-mono">
                      {new Date(scanResult.analyzedAt).toLocaleDateString('fr-FR')}
                    </span>
                  </div>
                  <h2 className="text-2xl font-black text-slate-900">{scanResult.companyName}</h2>
                </div>

                <div className="flex items-center space-x-4 bg-slate-50 px-5 py-3 rounded-2xl border border-slate-200">
                  <div className="text-right">
                    <span className="text-[10px] text-slate-500 uppercase font-black block">Score de conformité</span>
                    <span className="text-3xl font-black text-emerald-600">{scanResult.riskScore}/100</span>
                  </div>
                  <span className={`px-3 py-1 rounded-xl text-xs font-black uppercase ${
                    scanResult.riskLevel === 'critique' ? 'bg-rose-900/60 text-rose-200 border border-rose-500/50' :
                    scanResult.riskLevel === 'eleve' ? 'bg-amber-900/60 text-amber-200 border border-amber-500/50' :
                    'bg-emerald-900/60 text-emerald-200 border border-emerald-500/50'
                  }`}>
                    Risque {scanResult.riskLevel}
                  </span>
                </div>
              </div>

              {/* Synthèse du mini résumé */}
              <div className="space-y-4">
                <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-2">
                  <span className="text-emerald-700 font-bold uppercase text-[10px] block">
                    Synthèse de l'évaluation
                  </span>
                  <p className="text-slate-800 text-sm leading-relaxed font-medium">
                    {scanResult.clientSummary?.riskVerdict || scanResult.clientSummary?.headline || 'Diagnostic de conformité et sincérité des allégations environnementales.'}
                  </p>
                </div>

                {/* Profil entreprise auto-déduit */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                  <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center space-x-2.5">
                    <Briefcase className="w-4 h-4 text-emerald-600 shrink-0" />
                    <div>
                      <span className="text-[10px] text-slate-500 block uppercase font-bold">Secteur</span>
                      <span className="font-bold text-slate-900 truncate block">{scanResult.entreprise_secteur || scanResult.sector || 'Services & Industrie'}</span>
                    </div>
                  </div>
                  <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center space-x-2.5">
                    <Users className="w-4 h-4 text-teal-600 shrink-0" />
                    <div>
                      <span className="text-[10px] text-slate-500 block uppercase font-bold">Effectif</span>
                      <span className="font-bold text-teal-800 block">{scanResult.entreprise_salaries || '11-50 salariés'}</span>
                    </div>
                  </div>
                  <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center space-x-2.5">
                    <TrendingUp className="w-4 h-4 text-amber-600 shrink-0" />
                    <div>
                      <span className="text-[10px] text-slate-500 block uppercase font-bold">CA estimé</span>
                      <span className="font-bold text-amber-800 block">{scanResult.entreprise_ca || '2M€-10M€'}</span>
                    </div>
                  </div>
                </div>

                {/* Exemple de point de vigilance */}
                {scanResult.detectedClaims && scanResult.detectedClaims.length > 0 && (
                  <div className="bg-rose-50/50 p-4 rounded-2xl border border-rose-200 space-y-2 text-xs">
                    <div className="flex items-center justify-between">
                      <span className="font-bold text-rose-700">Point d'attention détecté :</span>
                      <span className="text-[10px] font-mono text-slate-500">{scanResult.detectedClaims[0].lawArticle}</span>
                    </div>
                    <p className="italic text-slate-800">"{scanResult.detectedClaims[0].quoteInText}"</p>
                    <p className="text-[11px] text-slate-600">
                      <strong className="text-rose-700">Règle :</strong> {scanResult.detectedClaims[0].sanction}
                    </p>
                  </div>
                )}
              </div>
            </div>

            {/* APPEL À LAISSER SES COORDONNÉES POUR BÉNÉFICIER DE L'ACCOMPAGNEMENT DES EXPERTS DE CLIMAT LOCAL */}
            <div className="bg-emerald-50/70 p-6 sm:p-8 rounded-3xl border-2 border-emerald-300 shadow-md space-y-6">
              <div className="space-y-2 max-w-2xl">
                <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider border border-emerald-300">
                  <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                  <span>Accompagnement Climat Local</span>
                </div>
                <h3 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                  Bénéficiez de l'accompagnement des experts de Climat Local
                </h3>
                <p className="text-xs sm:text-sm text-slate-700 leading-relaxed">
                  Laissez vos coordonnées pour recevoir le plan d'action de mise en conformité et être recontacté par un consultant expert (Bilan Carbone®, stratégie RSE et sécurisation juridique).
                </p>
              </div>

              {leadSubmitted ? (
                <div className="p-6 rounded-2xl bg-white border border-emerald-300 text-slate-800 space-y-3 animate-fadeIn shadow-xs">
                  <div className="flex items-center space-x-3 text-emerald-700 font-black text-base">
                    <CheckCircle2 className="w-6 h-6 shrink-0" />
                    <span>Demande transmise avec succès !</span>
                  </div>
                  <p className="text-xs text-slate-700 leading-relaxed font-medium">
                    {leadFeedback}
                  </p>
                  <button
                    onClick={() => setLeadSubmitted(false)}
                    className="mt-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors cursor-pointer"
                  >
                    Faire une autre demande
                  </button>
                </div>
              ) : (
                <form onSubmit={handleLeadSubmit} className="space-y-4 max-w-2xl bg-white p-6 rounded-2xl border border-emerald-200 shadow-xs">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">
                        Nom & Prénom *
                      </label>
                      <div className="relative">
                        <User className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                          type="text"
                          required
                          value={leadName}
                          onChange={(e) => setLeadName(e.target.value)}
                          placeholder="Votre nom"
                          className="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">
                        Email professionnel *
                      </label>
                      <div className="relative">
                        <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                          type="email"
                          required
                          value={leadEmail}
                          onChange={(e) => setLeadEmail(e.target.value)}
                          placeholder="votre.email@entreprise.fr"
                          className="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">
                        Téléphone (optionnel)
                      </label>
                      <div className="relative">
                        <Phone className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                          type="tel"
                          value={leadPhone}
                          onChange={(e) => setLeadPhone(e.target.value)}
                          placeholder="06 XX XX XX XX"
                          className="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 mb-1">
                        Votre besoin / Question (optionnel)
                      </label>
                      <div className="relative">
                        <MessageSquare className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                          type="text"
                          value={leadMessageText}
                          onChange={(e) => setLeadMessageText(e.target.value)}
                          placeholder="Ex: Bilan carbone, mise en conformité..."
                          className="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors"
                        />
                      </div>
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={leadSubmitting || !leadEmail.trim()}
                    className="w-full py-3.5 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider transition-all shadow-xs flex items-center justify-center space-x-2 cursor-pointer active:scale-98 disabled:opacity-50"
                  >
                    {leadSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin text-white" />
                        <span>Transmission en cours...</span>
                      </>
                    ) : (
                      <>
                        <Send className="w-4 h-4" />
                        <span>Être recontacté par les experts de Climat Local</span>
                      </>
                    )}
                  </button>

                  <p className="text-[10px] text-slate-500 text-center">
                    🔒 Vos coordonnées restent strictement confidentielles et traitées par le cabinet Climat Local.
                  </p>
                </form>
              )}
            </div>
          </div>
        )}
      </main>

      {/* Footer */}
      <footer className="mt-auto border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        <div className="max-w-[1850px] mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="flex flex-wrap items-center space-x-2">
            <span className="font-bold text-slate-800">GreenHumble</span>
            <span>•</span>
            <span className="font-medium text-slate-500">Restons humbles face à nos responsabilités</span>
            <span>•</span>
            <a href="https://climatlocal.com" target="_blank" rel="noreferrer" className="text-emerald-700 font-bold hover:underline">
              www.climatlocal.com
            </a>
          </div>
          <div className="flex items-center space-x-4">
            <button
              onClick={() => setIsMentionsOpen(true)}
              className="hover:text-emerald-700 underline font-semibold cursor-pointer"
            >
              Mentions Légales & RGPD
            </button>
            <button
              onClick={onOpenLogin}
              className="hover:text-emerald-700 underline font-semibold cursor-pointer"
            >
              Accès Consultant
            </button>
          </div>
        </div>
      </footer>

      <MentionsLegalesModal
        isOpen={isMentionsOpen}
        onClose={() => setIsMentionsOpen(false)}
      />
    </div>
  );
};
