import React, { useState, useMemo } from 'react';
import { getDealBudget } from '../utils/budgetParser.js';
import { getUniqueCompanyAudits } from '../utils/companyUtils.js';
import { 
  LayoutDashboard, 
  TrendingUp, 
  AlertTriangle, 
  ShieldAlert, 
  CheckCircle2, 
  Building2, 
  Euro, 
  Kanban, 
  ArrowRight, 
  Search, 
  Filter, 
  Sparkles, 
  BarChart3, 
  PieChart, 
  Scale, 
  Calendar,
  Users,
  Target,
  Briefcase,
  Layers,
  PhoneCall
} from 'lucide-react';
import { GreenwashingAudit, Collaborator } from '../types.js';
import { PipelineTracker } from './PipelineTracker.js';

interface DashboardViewProps {
  audits: GreenwashingAudit[];
  collaborators?: Collaborator[];
  onSelectAudit: (audit: GreenwashingAudit) => void;
  onNavigateToProspection?: (audit: GreenwashingAudit) => void;
  onNavigateToScans?: () => void;
  onStartAudit?: () => void;
  onUpdateStatus?: (id: string, status: GreenwashingAudit['prospectStatus'], notes?: string) => void;
  onDeleteAudit?: (id: string) => void;
  onAssignAudit?: (auditId: string, collaboratorId: string) => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({
  audits = [],
  collaborators = [],
  onSelectAudit,
  onNavigateToProspection,
  onNavigateToScans,
  onStartAudit,
  onUpdateStatus,
  onDeleteAudit,
  onAssignAudit,
}) => {
  const [activeSubTab, setActiveSubTab] = useState<'both' | 'kpis' | 'pipeline'>('both');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRiskFilter, setSelectedRiskFilter] = useState<'all' | 'critique' | 'eleve' | 'faible'>('all');

  // Stats calculation
  const stats = useMemo(() => {
    const totalScans = audits.length;
    if (totalScans === 0) {
      return {
        totalScans: 0,
        uniqueCompanies: 0,
        avgScore: 0,
        criticalCount: 0,
        highCount: 0,
        compliantCount: 0,
        pipelineCounts: { nouveau: 0, contacte: 0, relance: 0, rdv_fixe: 0, devis_envoye: 0, signe: 0 },
        potentialRevenue: 0,
        signedRevenue: 0,
        sectorDistribution: {} as Record<string, number>,
        topBannedWords: [] as Array<{ word: string; count: number }>
      };
    }

    // RÈGLE MÉTIER : 1 entreprise = 1 note officielle (le scan le plus favorable = note de l'entreprise)
    const uniqueCompanyAudits = getUniqueCompanyAudits(audits);
    const uniqueDomains = new Set(uniqueCompanyAudits.map(a => (a.cleanUrl || a.url).toLowerCase()));
    const scoredAudits = uniqueCompanyAudits.filter(a => !a.isUnnotable && a.riskScore !== null && a.riskScore !== undefined);
    const scoreSum = scoredAudits.reduce((sum, a) => sum + (a.riskScore as number), 0);
    const avgScore = scoredAudits.length > 0 ? Math.round(scoreSum / scoredAudits.length) : 0;

    let criticalCount = 0;
    let highCount = 0;
    let compliantCount = 0;

    const pipelineCounts = {
      nouveau: 0,
      contacte: 0,
      relance: 0,
      rdv_fixe: 0,
      devis_envoye: 0,
      signe: 0
    };

    const sectorDistribution: Record<string, number> = {};
    const bannedWordMap: Record<string, number> = {};

    uniqueCompanyAudits.forEach((a) => {
      const score = a.riskScore ?? 50;
      if (score < 40 || a.riskLevel === 'critique') criticalCount++;
      else if (score < 70 || a.riskLevel === 'eleve') highCount++;
      else compliantCount++;

      const status = (a.prospectStatus || 'nouveau') as keyof typeof pipelineCounts;
      if (pipelineCounts[status] !== undefined) {
        pipelineCounts[status]++;
      } else {
        pipelineCounts.nouveau++;
      }

      const sector = a.entreprise_secteur || a.sector || 'Services & Industrie';
      sectorDistribution[sector] = (sectorDistribution[sector] || 0) + 1;

      if (Array.isArray(a.detectedClaims)) {
        a.detectedClaims.forEach(c => {
          if (c.lawArticle) {
            bannedWordMap[c.lawArticle] = (bannedWordMap[c.lawArticle] || 0) + 1;
          }
          if (c.term) {
            bannedWordMap[c.term] = (bannedWordMap[c.term] || 0) + 1;
          }
        });
      }
    });

    // Dynamic calculation of potential and signed revenue from deal budgets (1 deal per unique company)
    let potentialRevenue = 0;
    let signedRevenue = 0;

    uniqueCompanyAudits.forEach((a) => {
      const status = a.prospectStatus || 'nouveau';
      const dealVal = getDealBudget(a);
      if (['nouveau', 'contacte', 'relance', 'rdv_fixe', 'devis_envoye'].includes(status)) {
        potentialRevenue += dealVal;
      } else if (status === 'signe') {
        signedRevenue += dealVal;
      }
    });

    const topBannedWords = Object.entries(bannedWordMap)
      .map(([word, count]) => ({ word, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 5);

    return {
      totalScans,
      uniqueCompanies: uniqueDomains.size,
      avgScore,
      criticalCount,
      highCount,
      compliantCount,
      pipelineCounts,
      potentialRevenue,
      signedRevenue,
      sectorDistribution,
      topBannedWords
    };
  }, [audits]);

  // Filtered recent audits for the dashboard list
  const filteredAudits = useMemo(() => {
    return audits.filter(a => {
      if (selectedRiskFilter !== 'all') {
        const score = a.riskScore ?? 50;
        if (selectedRiskFilter === 'critique' && (score >= 40 && a.riskLevel !== 'critique')) return false;
        if (selectedRiskFilter === 'eleve' && ((score < 40 || score >= 70) && a.riskLevel !== 'eleve')) return false;
        if (selectedRiskFilter === 'faible' && (score < 70 && a.riskLevel !== 'faible')) return false;
      }
      if (searchTerm.trim()) {
        const term = searchTerm.toLowerCase();
        const matchName = (a.companyName || '').toLowerCase().includes(term);
        const matchUrl = (a.cleanUrl || a.url || '').toLowerCase().includes(term);
        const matchSector = (a.entreprise_secteur || a.sector || '').toLowerCase().includes(term);
        return matchName || matchUrl || matchSector;
      }
      return true;
    }).slice(0, 10);
  }, [audits, selectedRiskFilter, searchTerm]);

  return (
    <div className="space-y-8 animate-fadeIn max-w-[1750px] mx-auto text-slate-800">
      {/* 1. Header du Tableau de bord & Pipeline */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
        <div className="space-y-1.5">
          <div className="flex items-center space-x-2 text-xs font-black uppercase tracking-wider text-[#1e40af]">
            <LayoutDashboard className="w-4 h-4" />
            <span>Cockpit Intégré GreenHumble</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Tableau de bord & Pipeline CRM
          </h2>
          <p className="text-xs sm:text-sm text-slate-600">
            Vision 360° : Pilotage stratégique des conformités Green Claims et gestion du pipeline commercial.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          {/* Sub-view switcher */}
          <div className="flex items-center space-x-1 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
            <button
              onClick={() => setActiveSubTab('both')}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                activeSubTab === 'both' ? 'bg-[#1e40af] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Vue Complète (KPIs + Kanban)
            </button>
            <button
              onClick={() => setActiveSubTab('kpis')}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                activeSubTab === 'kpis' ? 'bg-[#1e40af] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              KPIs & Stats
            </button>
            <button
              onClick={() => setActiveSubTab('pipeline')}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center space-x-1 ${
                activeSubTab === 'pipeline' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Kanban className="w-3.5 h-3.5" />
              <span>Pipeline Seul</span>
            </button>
          </div>

          {onStartAudit && (
            <button
              onClick={onStartAudit}
              className="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-sm transition-all cursor-pointer flex items-center space-x-2"
            >
              <Sparkles className="w-3.5 h-3.5" />
              <span>Nouvel Audit</span>
            </button>
          )}
        </div>
      </div>

      {/* 2. Top KPI Cards (shown in 'both' and 'kpis' modes) */}
      {(activeSubTab === 'both' || activeSubTab === 'kpis') && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
          {/* Total Scans & Entreprises */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Audits Réalisés</span>
              <div className="w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center font-black border border-blue-100">
                <Building2 className="w-5 h-5" />
              </div>
            </div>
            <div className="mt-4 flex items-baseline space-x-2">
              <span className="text-3xl sm:text-4xl font-black text-slate-900">{stats.totalScans}</span>
              <span className="text-xs text-slate-500">scans ({stats.uniqueCompanies} ent.)</span>
            </div>
            <div className="mt-3 text-[11px] text-slate-600 flex items-center space-x-1.5">
              <span className="w-2 h-2 rounded-full bg-emerald-500" />
              <span>Taux de conversion actif : {stats.totalScans > 0 ? Math.round(((stats.pipelineCounts.contacte + stats.pipelineCounts.rdv_fixe + stats.pipelineCounts.signe) / stats.totalScans) * 100) : 0}%</span>
            </div>
          </div>

          {/* Score Moyen de Conformité */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Score Moyen</span>
              <div className="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center font-black border border-amber-100">
                <Scale className="w-5 h-5" />
              </div>
            </div>
            <div className="mt-4 flex items-baseline space-x-2">
              <span className={`text-3xl sm:text-4xl font-black ${
                stats.avgScore < 40 ? 'text-rose-600' : stats.avgScore < 70 ? 'text-amber-600' : 'text-emerald-600'
              }`}>
                {stats.avgScore}/100
              </span>
              <span className="text-xs text-slate-500">sincérité</span>
            </div>
            <div className="mt-3 text-[11px] text-slate-600 flex items-center space-x-2">
              <span className="text-rose-700 font-bold">{stats.criticalCount} critiques</span>
              <span>•</span>
              <span className="text-amber-700 font-bold">{stats.highCount} élevés</span>
            </div>
          </div>

          {/* Pipeline & Dossiers en cours */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">En Prospection</span>
              <div className="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center font-black border border-teal-100">
                <Kanban className="w-5 h-5" />
              </div>
            </div>
            <div className="mt-4 flex items-baseline space-x-2">
              <span className="text-3xl sm:text-4xl font-black text-teal-700">
                {stats.pipelineCounts.contacte + stats.pipelineCounts.relance + stats.pipelineCounts.rdv_fixe + stats.pipelineCounts.devis_envoye}
              </span>
              <span className="text-xs text-slate-500">prospects actifs</span>
            </div>
            <div className="mt-3 text-[11px] text-emerald-700 font-bold flex items-center space-x-1.5">
              <CheckCircle2 className="w-3.5 h-3.5" />
              <span>{stats.pipelineCounts.signe} mission(s) signée(s)</span>
            </div>
          </div>

          {/* Pipeline CA Potentiel Décarbonation */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">CA Potentiel</span>
              <div className="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-black border border-emerald-100">
                <Euro className="w-5 h-5" />
              </div>
            </div>
            <div className="mt-4 flex items-baseline space-x-2">
              <span className="text-3xl sm:text-4xl font-black text-emerald-700">
                {stats.potentialRevenue.toLocaleString('fr-FR')} €
              </span>
            </div>
            <div className="mt-3 text-[11px] text-slate-600 flex items-center space-x-1.5">
              <span>Signé : <strong className="text-slate-900">{stats.signedRevenue.toLocaleString('fr-FR')} € HT</strong></span>
            </div>
          </div>
        </div>
      )}

      {/* 3. Section Graphiques & Répartitions (shown in 'kpis' mode or 'both') */}
      {(activeSubTab === 'both' || activeSubTab === 'kpis') && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Colonne 1: Répartition des Scores & Risques */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <div className="flex items-center justify-between pb-4 border-b border-slate-100">
              <div className="flex items-center space-x-2">
                <BarChart3 className="w-4 h-4 text-emerald-600" />
                <h3 className="text-sm font-bold text-slate-900 uppercase tracking-wider">Conformité Green Claims</h3>
              </div>
              <span className="text-xs text-slate-500">{stats.totalScans} total</span>
            </div>

            <div className="space-y-4">
              <div>
                <div className="flex justify-between text-xs font-bold mb-1.5">
                  <span className="text-rose-700">Risque Critique (&lt; 40/100)</span>
                  <span className="text-slate-900">{stats.criticalCount} ({stats.totalScans > 0 ? Math.round((stats.criticalCount / stats.totalScans) * 100) : 0}%)</span>
                </div>
                <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                  <div 
                    className="h-full bg-rose-500 rounded-full transition-all duration-500" 
                    style={{ width: `${stats.totalScans > 0 ? (stats.criticalCount / stats.totalScans) * 100 : 0}%` }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-bold mb-1.5">
                  <span className="text-amber-700">Risque Élevé (40 - 69/100)</span>
                  <span className="text-slate-900">{stats.highCount} ({stats.totalScans > 0 ? Math.round((stats.highCount / stats.totalScans) * 100) : 0}%)</span>
                </div>
                <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                  <div 
                    className="h-full bg-amber-500 rounded-full transition-all duration-500" 
                    style={{ width: `${stats.totalScans > 0 ? (stats.highCount / stats.totalScans) * 100 : 0}%` }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-bold mb-1.5">
                  <span className="text-emerald-700">Conforme / Justifié (&ge; 70/100)</span>
                  <span className="text-slate-900">{stats.compliantCount} ({stats.totalScans > 0 ? Math.round((stats.compliantCount / stats.totalScans) * 100) : 0}%)</span>
                </div>
                <div className="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                  <div 
                    className="h-full bg-emerald-500 rounded-full transition-all duration-500" 
                    style={{ width: `${stats.totalScans > 0 ? (stats.compliantCount / stats.totalScans) * 100 : 0}%` }}
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Colonne 2: Tunnel Commercial */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <div className="flex items-center justify-between pb-4 border-b border-slate-100">
              <div className="flex items-center space-x-2">
                <Kanban className="w-4 h-4 text-teal-600" />
                <h3 className="text-sm font-bold text-slate-900 uppercase tracking-wider">Entonnoir Commercial</h3>
              </div>
              <button 
                onClick={() => setActiveSubTab('pipeline')}
                className="text-xs font-bold text-[#1e40af] hover:underline cursor-pointer"
              >
                Focus Kanban &rarr;
              </button>
            </div>

            <div className="space-y-2.5 text-xs">
              <div className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span className="font-semibold text-slate-700 flex items-center space-x-2">
                  <span>📥</span> <span>Nouveau / À qualifier</span>
                </span>
                <span className="font-black text-slate-800 px-2 py-0.5 rounded-md bg-slate-200">
                  {stats.pipelineCounts.nouveau}
                </span>
              </div>

              <div className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span className="font-semibold text-blue-800 flex items-center space-x-2">
                  <span>💬</span> <span>Contacté</span>
                </span>
                <span className="font-black text-blue-800 px-2 py-0.5 rounded-md bg-blue-100">
                  {stats.pipelineCounts.contacte}
                </span>
              </div>

              <div className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span className="font-semibold text-amber-800 flex items-center space-x-2">
                  <span>⏳</span> <span>Relancé</span>
                </span>
                <span className="font-black text-amber-800 px-2 py-0.5 rounded-md bg-amber-100">
                  {stats.pipelineCounts.relance}
                </span>
              </div>

              <div className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span className="font-semibold text-purple-800 flex items-center space-x-2">
                  <span>📊</span> <span>RDV Fixé</span>
                </span>
                <span className="font-black text-purple-800 px-2 py-0.5 rounded-md bg-purple-100">
                  {stats.pipelineCounts.rdv_fixe}
                </span>
              </div>

              <div className="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span className="font-semibold text-indigo-800 flex items-center space-x-2">
                  <span>📝</span> <span>Devis Envoyé</span>
                </span>
                <span className="font-black text-indigo-800 px-2 py-0.5 rounded-md bg-indigo-100">
                  {stats.pipelineCounts.devis_envoye}
                </span>
              </div>

              <div className="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 border border-emerald-300">
                <span className="font-bold text-emerald-800 flex items-center space-x-2">
                  <span>✍️</span> <span>Signé / Gagné !</span>
                </span>
                <span className="font-black text-emerald-800 px-2 py-0.5 rounded-md bg-emerald-200">
                  {stats.pipelineCounts.signe}
                </span>
              </div>
            </div>
          </div>

          {/* Colonne 3: Secteurs d'activité & Allégations */}
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <div className="flex items-center justify-between pb-4 border-b border-slate-100">
              <div className="flex items-center space-x-2">
                <PieChart className="w-4 h-4 text-purple-600" />
                <h3 className="text-sm font-bold text-slate-900 uppercase tracking-wider">Top Secteurs Cibles</h3>
              </div>
              <span className="text-xs text-slate-500">{Object.keys(stats.sectorDistribution).length} secteurs</span>
            </div>

            <div className="space-y-3">
              {Object.entries(stats.sectorDistribution).slice(0, 5).map(([sec, count]) => (
                <div key={sec} className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="font-semibold text-slate-700 truncate max-w-[200px]">{sec}</span>
                    <span className="font-bold text-slate-500">{count} audit(s)</span>
                  </div>
                  <div className="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div 
                      className="h-full bg-teal-500 rounded-full" 
                      style={{ width: `${(count / stats.totalScans) * 100}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>

            {stats.topBannedWords.length > 0 && (
              <div className="pt-2 border-t border-slate-100 space-y-2">
                <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                  Points de vigilance récurrents :
                </span>
                <div className="flex flex-wrap gap-1.5">
                  {stats.topBannedWords.map((item, idx) => (
                    <span key={idx} className="px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-[10px] font-mono text-rose-700">
                      {item.word} ({item.count})
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* 4. PIPELINE KANBAN CRM SECTION (shown in 'both' and 'pipeline' modes) */}
      {(activeSubTab === 'both' || activeSubTab === 'pipeline') && (
        <div className="space-y-4">
          <div className="flex items-center justify-between bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div className="flex items-center space-x-3">
              <div className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center">
                <Kanban className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-base font-black text-slate-900">Pipeline de Prospection & Suivi Commercial</h3>
                <p className="text-xs text-slate-500">Glissez-déposez ou mettez à jour les statuts de vos leads et opportunités</p>
              </div>
            </div>
          </div>

          <PipelineTracker
            audits={audits}
            collaborators={collaborators}
            onSelectAudit={(selected) => {
              if (onNavigateToProspection) {
                onNavigateToProspection(selected);
              } else {
                onSelectAudit(selected);
              }
            }}
            onUpdateStatus={onUpdateStatus || (() => {})}
            onDeleteAudit={onDeleteAudit || (() => {})}
            onAssignAudit={onAssignAudit}
          />
        </div>
      )}

      {/* 5. TABLE DES DERNIERS SCANS (shown in 'kpis' mode only to keep 'both' clean) */}
      {activeSubTab === 'kpis' && (
        <div className="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div className="space-y-1">
              <h3 className="text-lg font-black text-slate-900">Derniers Scans & Entreprises Auditées</h3>
              <p className="text-xs text-slate-500">Accédez directement au dossier de prospection en 1 clic.</p>
            </div>

            <div className="flex flex-wrap items-center gap-3">
              <div className="relative">
                <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Filtrer nom, URL..."
                  className="pl-8 pr-3 py-1.5 rounded-xl bg-white border border-slate-300 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-600"
                />
              </div>

              {onNavigateToScans && (
                <button
                  onClick={onNavigateToScans}
                  className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors cursor-pointer border border-slate-200"
                >
                  Voir tous les scans &rarr;
                </button>
              )}
            </div>
          </div>

          {filteredAudits.length === 0 ? (
            <div className="p-8 text-center text-xs text-slate-500 bg-slate-50 rounded-2xl border border-slate-200">
              Aucun scan correspondant aux critères.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="text-[11px] font-black uppercase tracking-wider text-slate-500 border-b border-slate-200 bg-slate-50">
                  <tr>
                    <th className="py-3 px-4">Entreprise</th>
                    <th className="py-3 px-4">Secteur</th>
                    <th className="py-3 px-4">Score Sincérité</th>
                    <th className="py-3 px-4">Statut Pipeline</th>
                    <th className="py-3 px-4">Date Scan</th>
                    <th className="py-3 px-4 text-right">Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {filteredAudits.map((a) => (
                    <tr key={a.id} className="hover:bg-slate-50 transition-colors">
                      <td className="py-3 px-4 font-bold text-slate-900">
                        <div className="flex items-center space-x-2">
                          <Building2 className="w-4 h-4 text-slate-400 shrink-0" />
                          <div>
                            <span>{a.companyName || a.cleanUrl}</span>
                            <span className="block text-[10px] text-slate-500 font-mono font-normal">
                              {a.cleanUrl || a.url}
                            </span>
                          </div>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-slate-600">
                        {a.entreprise_secteur || a.sector || 'Services'}
                      </td>
                      <td className="py-3 px-4">
                        <div className="flex items-center space-x-2">
                          {a.isUnnotable || a.riskScore === null ? (
                            <>
                              <span className="font-black text-sm text-amber-700">Non noté</span>
                              <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                Indisponible
                              </span>
                            </>
                          ) : (
                            <>
                              <span className={`font-black text-sm ${
                                (a.riskScore ?? 0) < 40 ? 'text-rose-700' : (a.riskScore ?? 0) < 70 ? 'text-amber-700' : 'text-emerald-700'
                              }`}>
                                {a.riskScore}/100
                              </span>
                              <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                a.riskLevel === 'critique' ? 'bg-rose-50 text-rose-800 border border-rose-200' :
                                a.riskLevel === 'eleve' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
                                'bg-emerald-50 text-emerald-800 border border-emerald-200'
                              }`}>
                                {a.riskLevel || 'Modéré'}
                              </span>
                            </>
                          )}
                        </div>
                      </td>
                      <td className="py-3 px-4">
                        <span className="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 font-semibold text-slate-700 text-[11px] capitalize">
                          {a.prospectStatus || 'Nouveau'}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-slate-500 text-[11px]">
                        {new Date(a.analyzedAt || Date.now()).toLocaleDateString('fr-FR')}
                      </td>
                      <td className="py-3 px-4 text-right">
                        <button
                          onClick={() => {
                            if (onNavigateToProspection) {
                              onNavigateToProspection(a);
                            } else {
                              onSelectAudit(a);
                            }
                          }}
                          className="px-3 py-1.5 rounded-xl bg-[#1e40af] hover:bg-[#1d4ed8] text-white font-bold text-xs transition-all shadow-sm cursor-pointer inline-flex items-center space-x-1.5"
                        >
                          <Briefcase className="w-3.5 h-3.5" />
                          <span>Prospection</span>
                          <ArrowRight className="w-3.5 h-3.5" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
