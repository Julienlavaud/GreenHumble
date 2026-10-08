import React, { useState, useMemo } from 'react';
import { GreenwashingAudit, Collaborator } from '../types.js';
import { riskLevelConfig } from './ScoreGauge.js';
import { getDealBudget } from '../utils/budgetParser.js';
import { getUniqueCompanyAudits, cleanCompanyName } from '../utils/companyUtils.js';
import { 
  Users, 
  Search, 
  Download, 
  Eye, 
  Trash2, 
  AlertOctagon, 
  Target, 
  Scale, 
  Briefcase,
  Globe,
  UserCheck,
  Phone,
  Mail,
  Filter,
  Kanban,
  List,
  Sparkles,
  ArrowRight,
  ArrowLeft,
  ChevronRight,
  TrendingUp,
  DollarSign,
  Calendar,
  CheckCircle2,
  Clock,
  Edit3,
  X,
  Save,
  Plus
} from 'lucide-react';

interface PipelineTrackerProps {
  audits: GreenwashingAudit[];
  collaborators?: Collaborator[];
  onSelectAudit: (audit: GreenwashingAudit) => void;
  onUpdateStatus: (id: string, status: GreenwashingAudit['prospectStatus'], notes?: string) => void;
  onDeleteAudit: (id: string) => void;
  onAssignAudit?: (auditId: string, collaboratorId: string) => void;
}

type ProspectStatusKey = NonNullable<GreenwashingAudit['prospectStatus']>;

const PIPELINE_STAGES: Array<{
  key: ProspectStatusKey;
  label: string;
  shortLabel: string;
  icon: string;
  color: string;
  badge: string;
  headerBg: string;
}> = [
  { key: 'nouveau', label: '1. Nouveaux Leads', shortLabel: 'Nouveaux', icon: '📥', color: 'border-slate-300', badge: 'bg-slate-100 text-slate-800 border-slate-200', headerBg: 'bg-slate-900 text-white' },
  { key: 'contacte', label: '2. Premier Contact', shortLabel: 'Contacté', icon: '💬', color: 'border-blue-300', badge: 'bg-blue-100 text-blue-900 border-blue-200', headerBg: 'bg-blue-900 text-white' },
  { key: 'relance', label: '3. Relance Prévue', shortLabel: 'Relance', icon: '⏳', color: 'border-amber-300', badge: 'bg-amber-100 text-amber-900 border-amber-200', headerBg: 'bg-amber-900 text-white' },
  { key: 'rdv_fixe', label: '4. RDV & Diagnostic', shortLabel: 'RDV Fixé', icon: '📊', color: 'border-purple-300', badge: 'bg-purple-100 text-purple-900 border-purple-200', headerBg: 'bg-purple-900 text-white' },
  { key: 'devis_envoye', label: '5. Offre / Devis', shortLabel: 'Devis', icon: '📝', color: 'border-indigo-300', badge: 'bg-indigo-100 text-indigo-900 border-indigo-200', headerBg: 'bg-indigo-900 text-white' },
  { key: 'signe', label: '6. Missions Signées !', shortLabel: 'Signé', icon: '✍️', color: 'border-emerald-400', badge: 'bg-emerald-100 text-emerald-950 border-emerald-300 font-black', headerBg: 'bg-emerald-800 text-white' },
];

export const PipelineTracker: React.FC<PipelineTrackerProps> = ({
  audits,
  collaborators = [],
  onSelectAudit,
  onUpdateStatus,
  onDeleteAudit,
  onAssignAudit,
}) => {
  const [viewMode, setViewMode] = useState<'kanban' | 'list'>('kanban');
  const [search, setSearch] = useState('');
  const [filterSource, setFilterSource] = useState<string>('all');
  const [filterAssigned, setFilterAssigned] = useState<string>('all');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [selectedYear, setSelectedYear] = useState<string>('all');

  // Quick Note Modal State
  const [editingAudit, setEditingAudit] = useState<GreenwashingAudit | null>(null);
  const [noteInput, setNoteInput] = useState<string>('');
  const [statusInput, setStatusInput] = useState<ProspectStatusKey>('nouveau');
  const [assigneeInput, setAssigneeInput] = useState<string>('');

  // Deduplicate audits: 1 deal per unique company in the pipeline
  const uniqueCompanyAudits = useMemo(() => {
    return getUniqueCompanyAudits(audits);
  }, [audits]);

  // Extract available years
  const availableYears = useMemo(() => {
    const yearsSet = new Set<string>();
    yearsSet.add('2026');
    uniqueCompanyAudits.forEach((a) => {
      if (a.analyzedAt) {
        const yr = new Date(a.analyzedAt).getFullYear().toString();
        if (!isNaN(parseInt(yr, 10))) yearsSet.add(yr);
      }
    });
    return Array.from(yearsSet).sort().reverse();
  }, [uniqueCompanyAudits]);

  // Yearly Breakdown Calculation
  const yearlyBreakdown = useMemo(() => {
    const map: Record<string, { count: number; value: number; signedValue: number; signedCount: number; avgRiskSum: number }> = {};
    uniqueCompanyAudits.forEach((a) => {
      const yr = a.analyzedAt ? new Date(a.analyzedAt).getFullYear().toString() : '2026';
      if (!map[yr]) {
        map[yr] = { count: 0, value: 0, signedValue: 0, signedCount: 0, avgRiskSum: 0 };
      }
      const val = getDealBudget(a);
      map[yr].count += 1;
      map[yr].value += val;
      if ((a.prospectStatus || 'nouveau') === 'signe') {
        map[yr].signedValue += val;
        map[yr].signedCount += 1;
      }
      map[yr].avgRiskSum += a.riskScore || 0;
    });
    return map;
  }, [uniqueCompanyAudits]);

  // Filtered Audits (1 deal per unique company)
  const filteredAudits = useMemo(() => {
    return uniqueCompanyAudits.filter((a) => {
      const matchesSearch =
        (a.companyName || '').toLowerCase().includes(search.toLowerCase()) ||
        (a.cleanUrl || '').toLowerCase().includes(search.toLowerCase()) ||
        (a.sector || '').toLowerCase().includes(search.toLowerCase()) ||
        (a.leadContact?.name || '').toLowerCase().includes(search.toLowerCase()) ||
        (a.leadContact?.email || '').toLowerCase().includes(search.toLowerCase());

      const matchesStatus = filterStatus === 'all' || (a.prospectStatus || 'nouveau') === filterStatus;
      const matchesSource =
        filterSource === 'all' ||
        (filterSource === 'wordpress' && a.source === 'widget_wordpress') ||
        (filterSource === 'interne' && a.source !== 'widget_wordpress');

      const matchesAssigned =
        filterAssigned === 'all' ||
        (filterAssigned === 'unassigned' && !a.assignedTo) ||
        a.assignedTo === filterAssigned;

      const auditYear = a.analyzedAt ? new Date(a.analyzedAt).getFullYear().toString() : '2026';
      const matchesYear = selectedYear === 'all' || auditYear === selectedYear;

      return matchesSearch && matchesStatus && matchesSource && matchesAssigned && matchesYear;
    });
  }, [uniqueCompanyAudits, search, filterStatus, filterSource, filterAssigned, selectedYear]);

  // Global Pipeline KPIs
  const pipelineStats = useMemo(() => {
    const totalCount = filteredAudits.length;
    let totalValue = 0;
    let signedValue = 0;
    let signedCount = 0;

    filteredAudits.forEach((a) => {
      const val = getDealBudget(a);
      totalValue += val;
      if ((a.prospectStatus || 'nouveau') === 'signe') {
        signedValue += val;
        signedCount += 1;
      }
    });

    const conversionRate = totalCount > 0 ? Math.round((signedCount / totalCount) * 100) : 0;
    const countWordPress = filteredAudits.filter((a) => a.source === 'widget_wordpress').length;

    // Carbon estimation for tool run
    const kwhConsumed = (Math.max(1, totalCount) * 0.0065 + 14.2);
    const tCO2eTool = ((kwhConsumed * 0.230) / 1000);

    return {
      totalCount,
      totalValue,
      signedValue,
      signedCount,
      conversionRate,
      countWordPress,
      tCO2eTool,
      kwhConsumed,
    };
  }, [filteredAudits]);

  // Move audit step forward or backward
  const handleMoveStep = (audit: GreenwashingAudit, direction: 'next' | 'prev') => {
    const currentKey = audit.prospectStatus || 'nouveau';
    const currentIndex = PIPELINE_STAGES.findIndex((s) => s.key === currentKey);
    if (currentIndex === -1) return;

    let targetIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1;
    if (targetIndex < 0) targetIndex = 0;
    if (targetIndex >= PIPELINE_STAGES.length) targetIndex = PIPELINE_STAGES.length - 1;

    const targetKey = PIPELINE_STAGES[targetIndex].key;
    onUpdateStatus(audit.id, targetKey, audit.prospectNotes);
  };

  // Export CSV
  const handleExportCsv = () => {
    const headers = [
      'Entreprise',
      'URL',
      'Source Lead',
      'Contact Nom',
      'Contact Email',
      'Téléphone',
      'Niveau Risque',
      'Score Risque',
      'Budget Estimé (€)',
      'Auteur / Assigné',
      'Étape Pipeline',
      'Notes Prospection',
      'Date Analyse',
    ];

    const rows = filteredAudits.map((a) => {
      const assignedCollab = collaborators.find((c) => c.id === a.assignedTo)?.name || 'Non assigné';
      const stage = PIPELINE_STAGES.find((s) => s.key === (a.prospectStatus || 'nouveau'))?.label || 'Nouveau';
      return [
        `"${a.companyName.replace(/"/g, '""')}"`,
        `"${a.cleanUrl}"`,
        `"${a.source === 'widget_wordpress' ? 'Site Web' : 'Interne'}"`,
        `"${(a.leadContact?.name || '').replace(/"/g, '""')}"`,
        `"${(a.leadContact?.email || '').replace(/"/g, '""')}"`,
        `"${(a.leadContact?.phone || '').replace(/"/g, '""')}"`,
        a.riskLevel,
        a.riskScore,
        getDealBudget(a),
        `"${assignedCollab}"`,
        `"${stage}"`,
        `"${(a.prospectNotes || '').replace(/"/g, '""')}"`,
        new Date(a.analyzedAt).toLocaleDateString('fr-FR'),
      ];
    });

    const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [headers.join(';'), ...rows.map((r) => r.join(';'))].join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `pipeline-commercial-greenhumble-${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  };

  return (
    <div className="space-y-6 animate-fadeIn max-w-[1850px] mx-auto pb-12">
      {/* 1. HEADER BAR WITH TITLE & CONTROLS */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center space-x-2">
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-950 border border-emerald-200">
              Cockpit Commercial & Pipeline CRM
            </span>
            {pipelineStats.countWordPress > 0 && (
              <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-900 border border-teal-200">
                🌐 {pipelineStats.countWordPress} lead(s) web entrants
              </span>
            )}
          </div>
          <h2 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Suivi des Opportunités & Transformation Commerciale ({audits.length})
          </h2>
          <p className="text-xs text-slate-500">
            Convertissez les alertes d'allégations et de greenwashing en missions de Bilan Carbone, RSE, ACV et Décarbonation.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2 shrink-0">
          {/* View Mode Switcher */}
          <div className="flex items-center bg-slate-100 p-1 rounded-2xl border border-slate-200">
            <button
              onClick={() => setViewMode('kanban')}
              className={`flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
                viewMode === 'kanban'
                  ? 'bg-slate-900 text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Kanban className="w-3.5 h-3.5" />
              <span>Vue Kanban</span>
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${
                viewMode === 'list'
                  ? 'bg-slate-900 text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <List className="w-3.5 h-3.5" />
              <span>Vue Liste</span>
            </button>
          </div>

          <button
            onClick={handleExportCsv}
            disabled={audits.length === 0}
            className="flex items-center space-x-2 px-4 py-2.5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold shadow-sm transition-colors"
          >
            <Download className="w-4 h-4 text-emerald-300" />
            <span>Exporter CSV</span>
          </button>
        </div>
      </div>

      {/* 2. KPI METRICS CARDS */}
      <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Box 1: Pipeline Value */}
        <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs space-y-1">
          <div className="flex items-center justify-between">
            <span className="text-3xs font-extrabold uppercase tracking-wider text-slate-400">Valeur Totale du Pipeline</span>
            <DollarSign className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl sm:text-3xl font-black text-slate-900">
            {pipelineStats.totalValue.toLocaleString('fr-FR')} €
          </div>
          <p className="text-2xs text-slate-500 font-medium">
            {pipelineStats.totalCount} dossier(s) en cours d'accompagnement
          </p>
        </div>

        {/* Box 2: Signed Deals */}
        <div className="bg-emerald-50 text-emerald-900 p-5 rounded-3xl border border-emerald-200 shadow-xs space-y-1">
          <div className="flex items-center justify-between">
            <span className="text-3xs font-extrabold uppercase tracking-wider text-emerald-800">Missions Signées (€)</span>
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl sm:text-3xl font-black text-emerald-800">
            {pipelineStats.signedValue.toLocaleString('fr-FR')} €
          </div>
          <p className="text-2xs text-emerald-700 font-medium">
            {pipelineStats.signedCount} contrat(s) • Taux de conversion : <strong className="text-emerald-900">{pipelineStats.conversionRate}%</strong>
          </p>
        </div>

        {/* Box 3: Carbon Impact */}
        <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs space-y-1">
          <div className="flex items-center justify-between">
            <span className="text-3xs font-extrabold uppercase tracking-wider text-slate-400">Bilan GES Outil</span>
            <span className="text-xs">🌱</span>
          </div>
          <div className="flex items-baseline space-x-1.5">
            <span className="text-2xl sm:text-3xl font-black text-emerald-800">
              {pipelineStats.tCO2eTool.toFixed(4)}
            </span>
            <span className="text-2xs font-extrabold text-slate-500">tCO2e</span>
          </div>
          <p className="text-2xs text-slate-500">
            ~1.5 g CO2e / audit • Éco-conçu
          </p>
        </div>

        {/* Box 4: AI Energy */}
        <div className="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs space-y-1">
          <div className="flex items-center justify-between">
            <span className="text-3xs font-extrabold uppercase tracking-wider text-slate-400">Énergie IA Consommée</span>
            <span className="text-xs">⚡</span>
          </div>
          <div className="flex items-baseline space-x-1.5">
            <span className="text-2xl sm:text-3xl font-black text-amber-600">
              {pipelineStats.kwhConsumed.toFixed(1)}
            </span>
            <span className="text-2xs font-extrabold text-slate-500">kWh</span>
          </div>
          <p className="text-2xs text-slate-500">
            Calculs serveur et analyses d'allégations
          </p>
        </div>
      </div>

      {/* 2. YEARLY FILTER & ANNUAL BREAKDOWN (SUIVI DE L'ÉVOLUTION ANNUELLE) */}
      <div className="bg-white text-slate-800 rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div className="flex items-center space-x-3">
            <div className="p-2.5 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-200">
              <Calendar className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-base font-black text-slate-900 tracking-tight">Tableau de Bord & Évolution par Année</h3>
              <p className="text-xs text-slate-500">Filtrer l'activité, le chiffre d'affaires et la volumétrie d'audits par exercice annuel.</p>
            </div>
          </div>

          {/* Year Pills */}
          <div className="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
            <button
              onClick={() => setSelectedYear('all')}
              className={`px-3 py-1.5 rounded-xl text-xs font-black transition-all ${
                selectedYear === 'all'
                  ? 'bg-[#1e40af] text-white shadow-xs'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'
              }`}
            >
              Toutes les années
            </button>
            {availableYears.map((yr) => (
              <button
                key={yr}
                onClick={() => setSelectedYear(yr)}
                className={`px-3 py-1.5 rounded-xl text-xs font-black transition-all ${
                  selectedYear === yr
                    ? 'bg-[#1e40af] text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'
                }`}
              >
                {yr}
              </button>
            ))}
          </div>
        </div>

        {/* Annual Comparative Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
          {availableYears.map((yr) => {
            const data = yearlyBreakdown[yr] || { count: 0, value: 0, signedValue: 0, signedCount: 0, avgRiskSum: 0 };
            const isSelected = selectedYear === yr;
            return (
              <div
                key={yr}
                onClick={() => setSelectedYear(yr)}
                className={`p-4 rounded-2xl border cursor-pointer transition-all ${
                  isSelected
                    ? 'bg-emerald-50/80 border-emerald-500 shadow-sm ring-1 ring-emerald-500'
                    : 'bg-slate-50 border-slate-200 hover:bg-slate-100 hover:border-slate-300'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <span className="text-xs font-black uppercase text-emerald-800 tracking-wider">Exercice {yr}</span>
                  <span className="text-3xs font-extrabold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">
                    {data.count} scan(s)
                  </span>
                </div>
                <div className="space-y-1">
                  <div className="flex justify-between items-baseline">
                    <span className="text-3xs text-slate-500 font-bold uppercase">Pipeline potentiel</span>
                    <span className="text-sm font-black text-slate-900">{data.value.toLocaleString('fr-FR')} €</span>
                  </div>
                  <div className="flex justify-between items-baseline">
                    <span className="text-3xs text-slate-500 font-bold uppercase">Missions signées</span>
                    <span className="text-sm font-black text-emerald-700">{data.signedValue.toLocaleString('fr-FR')} €</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* 3. FILTERS & SEARCH TOOLBAR */}
      <div className="bg-white p-4 rounded-3xl border border-slate-200 shadow-2xs space-y-3">
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          {/* Search Bar */}
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            <input
              type="text"
              placeholder="Rechercher une entreprise, un nom, un email, un secteur..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
            />
          </div>

          {/* Filter 1: Source */}
          <div className="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-2xs">
            <button
              onClick={() => setFilterSource('all')}
              className={`px-3 py-1.5 rounded-lg font-extrabold transition-colors ${
                filterSource === 'all' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Toutes Origines
            </button>
            <button
              onClick={() => setFilterSource('wordpress')}
              className={`px-3 py-1.5 rounded-lg font-extrabold flex items-center space-x-1 transition-colors ${
                filterSource === 'wordpress' ? 'bg-teal-700 text-white' : 'text-teal-900 hover:bg-teal-100'
              }`}
            >
              <Globe className="w-3 h-3" />
              <span>Lead Web ({pipelineStats.countWordPress})</span>
            </button>
            <button
              onClick={() => setFilterSource('interne')}
              className={`px-3 py-1.5 rounded-lg font-extrabold transition-colors ${
                filterSource === 'interne' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Scan Interne ({pipelineStats.totalCount - pipelineStats.countWordPress})
            </button>
          </div>

          {/* Filter 2: Collaborator Assignee */}
          {collaborators.length > 0 && (
            <div className="flex items-center space-x-1.5 text-xs shrink-0">
              <span className="text-2xs font-bold text-slate-400 uppercase">Responsable :</span>
              <select
                value={filterAssigned}
                onChange={(e) => setFilterAssigned(e.target.value)}
                className="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 outline-none"
              >
                <option value="all">Tous les consultants</option>
                <option value="unassigned">Non assignés uniquement</option>
                {collaborators.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name} ({c.role})
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        {/* Status Pill Filters (Visible on both list & kanban) */}
        <div className="flex items-center space-x-1 overflow-x-auto pt-1 no-scrollbar">
          <button
            onClick={() => setFilterStatus('all')}
            className={`px-3 py-1 rounded-xl text-3xs font-extrabold transition-colors whitespace-nowrap ${
              filterStatus === 'all' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`}
          >
            Toutes les Étapes ({audits.length})
          </button>
          {PIPELINE_STAGES.map((st) => {
            const count = audits.filter((a) => (a.prospectStatus || 'nouveau') === st.key).length;
            return (
              <button
                key={st.key}
                onClick={() => setFilterStatus(st.key)}
                className={`px-3 py-1 rounded-xl text-3xs font-extrabold transition-colors whitespace-nowrap flex items-center space-x-1 ${
                  filterStatus === st.key ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                <span>{st.icon}</span>
                <span>{st.shortLabel}</span>
                <span className="px-1.5 py-0.2 rounded-full bg-white/30 text-2xs font-mono">{count}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* 4. MAIN CONTENT DISPLAY (KANBAN OR LIST) */}
      {filteredAudits.length === 0 ? (
        <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
            <Users className="w-6 h-6" />
          </div>
          <h3 className="font-bold text-slate-800 text-base">Aucun dossier trouvé</h3>
          <p className="text-xs text-slate-500 max-w-sm mx-auto">
            Aucun prospect ne correspond aux critères sélectionnés. Réinitialisez vos filtres ou lancez un nouveau scan.
          </p>
        </div>
      ) : viewMode === 'kanban' ? (
        /* KANBAN BOARD VIEW */
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 overflow-x-auto pb-4">
          {PIPELINE_STAGES.map((stage) => {
            const stageAudits = filteredAudits.filter(
              (a) => (a.prospectStatus || 'nouveau') === stage.key
            );
            const stageBudgetSum = stageAudits.reduce((acc, a) => acc + getDealBudget(a), 0);

            return (
              <div
                key={stage.key}
                className="bg-slate-100/70 rounded-3xl p-3 border border-slate-200 flex flex-col justify-between space-y-3 min-w-[220px]"
              >
                {/* Stage Header */}
                <div className="space-y-1">
                  <div className={`p-2.5 rounded-2xl ${stage.headerBg} flex items-center justify-between shadow-xs`}>
                    <div className="flex items-center space-x-1.5 font-black text-xs">
                      <span>{stage.icon}</span>
                      <span className="truncate">{stage.shortLabel}</span>
                    </div>
                    <span className="px-2 py-0.5 rounded-full text-3xs font-extrabold bg-white/20 text-white font-mono">
                      {stageAudits.length}
                    </span>
                  </div>
                  <div className="text-2xs font-extrabold text-slate-500 text-right px-1 pt-0.5">
                    Total : {stageBudgetSum.toLocaleString('fr-FR')} €
                  </div>
                </div>

                {/* Cards in Column */}
                <div className="space-y-3 flex-1 overflow-y-auto max-h-[650px] pr-1 no-scrollbar">
                  {stageAudits.length === 0 ? (
                    <div className="p-6 text-center text-3xs text-slate-400 border border-dashed border-slate-300 rounded-2xl">
                      Aucun dossier à cette étape
                    </div>
                  ) : (
                    stageAudits.map((item) => {
                      const riskMeta = riskLevelConfig[item.riskLevel] || riskLevelConfig.modere;
                      const dealBudget = getDealBudget(item);
                      const assignedCollab = collaborators.find((c) => c.id === item.assignedTo);

                      return (
                        <div
                          key={item.id}
                          className="bg-white rounded-2xl p-3.5 border border-slate-200 hover:border-emerald-400 shadow-2xs hover:shadow-md transition-all space-y-2.5 group relative"
                        >
                          {/* Top: Score Badge + Company Name */}
                          <div className="flex items-start justify-between gap-2">
                            <div className="space-y-0.5 min-w-0">
                              <h4
                                onClick={() => onSelectAudit(item)}
                                className="font-extrabold text-slate-900 text-xs hover:text-emerald-700 cursor-pointer truncate transition-colors"
                              >
                                {item.companyName}
                              </h4>
                              <p className="text-3xs text-slate-400 font-mono truncate">{item.cleanUrl}</p>
                            </div>

                            <span
                              className={`px-2 py-0.5 rounded-xl text-3xs font-black shrink-0 ${
                                item.isUnnotable || item.riskScore === null ? 'bg-amber-600 text-white' : `${riskMeta.bg} text-white`
                              }`}
                            >
                              {item.isUnnotable || item.riskScore === null ? 'Non noté' : `${item.riskScore}/100`}
                            </span>
                          </div>

                          {/* Budget & Opportunities */}
                          <div className="flex items-center justify-between text-3xs font-extrabold pt-1 border-t border-slate-100">
                            <span className="text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                              💰 {dealBudget.toLocaleString('fr-FR')} €
                            </span>
                            <span className="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded">
                              {item.totalBannedWordsDetected} allégation(s)
                            </span>
                          </div>

                          {/* Lead Origin & Assignee */}
                          <div className="flex items-center justify-between text-3xs text-slate-500 pt-0.5">
                            {item.source === 'widget_wordpress' ? (
                              <span className="text-teal-800 font-bold bg-teal-50 px-1.5 py-0.5 rounded border border-teal-200 flex items-center space-x-0.5">
                                <Globe className="w-2.5 h-2.5 text-teal-600" />
                                <span>Web Lead</span>
                              </span>
                            ) : (
                              <span className="text-slate-400">Scan Interne</span>
                            )}

                            <span className="font-bold text-slate-700 truncate max-w-[90px]">
                              {assignedCollab ? `👤 ${assignedCollab.name.split(' ')[0]}` : 'Non assigné'}
                            </span>
                          </div>

                          {/* Contact Note Preview */}
                          {item.prospectNotes && (
                            <p className="text-3xs text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-200/80 italic truncate">
                              "{item.prospectNotes}"
                            </p>
                          )}

                          {/* Column Action Buttons (Move Left, Open Folder, Move Right) */}
                          <div className="flex items-center justify-between pt-1 border-t border-slate-100">
                            <button
                              onClick={() => handleMoveStep(item, 'prev')}
                              disabled={stage.key === 'nouveau'}
                              className="p-1 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed text-slate-600"
                              title="Reculer d'une étape"
                            >
                              <ArrowLeft className="w-3 h-3" />
                            </button>

                            <div className="flex items-center space-x-1">
                              <button
                                onClick={() => {
                                  setEditingAudit(item);
                                  setNoteInput(item.prospectNotes || '');
                                  setStatusInput(item.prospectStatus || 'nouveau');
                                  setAssigneeInput(item.assignedTo || '');
                                }}
                                className="p-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700"
                                title="Modifier la note & le statut"
                              >
                                <Edit3 className="w-3 h-3" />
                              </button>

                              <button
                                onClick={() => onSelectAudit(item)}
                                className="px-2 py-1 bg-slate-900 hover:bg-emerald-800 text-white font-bold rounded-lg text-3xs flex items-center space-x-0.5 shadow-2xs"
                                title="Ouvrir le dossier complet d'analyse"
                              >
                                <Eye className="w-3 h-3" />
                                <span>Projet</span>
                              </button>
                            </div>

                            <button
                              onClick={() => handleMoveStep(item, 'next')}
                              disabled={stage.key === 'signe'}
                              className="p-1 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed text-slate-600"
                              title="Avancer d'une étape"
                            >
                              <ArrowRight className="w-3 h-3" />
                            </button>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        /* DETAILED LIST VIEW */
        <div className="space-y-3">
          {filteredAudits.map((item) => {
            const stageConfig = PIPELINE_STAGES.find((s) => s.key === (item.prospectStatus || 'nouveau')) || PIPELINE_STAGES[0];
            const riskConfig = riskLevelConfig[item.riskLevel] || riskLevelConfig.modere;
            const dealBudget = getDealBudget(item);
            const assignedCollab = collaborators.find((c) => c.id === item.assignedTo);

            return (
              <div
                key={item.id}
                className="bg-white rounded-3xl p-5 border border-slate-200 hover:border-emerald-300 shadow-2xs transition-all space-y-4"
              >
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                  {/* Company & Score */}
                  <div className="flex items-start space-x-3">
                    <div
                      className={`w-12 h-12 rounded-2xl ${
                        item.isUnnotable || item.riskScore === null ? 'bg-amber-800' : riskConfig.bg
                      } text-white flex flex-col items-center justify-center font-black text-xs shrink-0 shadow-xs text-center p-1`}
                    >
                      <span>{item.isUnnotable || item.riskScore === null ? 'N/A' : item.riskScore}</span>
                      <span className="text-[8px] uppercase font-bold opacity-80 leading-none">
                        {item.isUnnotable || item.riskScore === null ? 'Non noté' : '/100'}
                      </span>
                    </div>

                    <div className="space-y-1">
                      <div className="flex items-center space-x-2">
                        <h3
                          onClick={() => onSelectAudit(item)}
                          className="font-black text-slate-900 text-base hover:text-emerald-700 cursor-pointer transition-colors"
                        >
                          {item.companyName}
                        </h3>
                        {item.source === 'widget_wordpress' ? (
                          <span className="px-2 py-0.5 rounded-full text-3xs font-extrabold uppercase bg-teal-100 text-teal-900 border border-teal-200 flex items-center space-x-1">
                            <Globe className="w-3 h-3 text-teal-700" />
                            <span>Lead Web</span>
                          </span>
                        ) : (
                          <span className="px-2 py-0.5 rounded-full text-3xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            Scan Interne
                          </span>
                        )}
                      </div>

                      <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                        <span className="font-mono text-slate-600">{item.cleanUrl}</span>
                        <span>•</span>
                        <span className="font-semibold text-slate-700">{item.sector}</span>
                        {item.headcount && (
                          <>
                            <span>•</span>
                            <span className="text-emerald-700 font-medium">👥 {item.headcount}</span>
                          </>
                        )}
                        {item.turnover && (
                          <>
                            <span>•</span>
                            <span className="text-teal-700 font-medium">💶 {item.turnover}</span>
                          </>
                        )}
                        <span>•</span>
                        <span className="font-extrabold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                          Budget Estimé : {dealBudget.toLocaleString('fr-FR')} €
                        </span>
                      </div>

                      {/* Lead Contact Info */}
                      {item.leadContact && (item.leadContact.name || item.leadContact.email) && (
                        <div className="flex items-center space-x-3 text-xs text-emerald-950 bg-emerald-50/70 px-2.5 py-1 rounded-xl border border-emerald-200 mt-1 max-w-lg">
                          <span className="font-bold">{item.leadContact.name || 'Prospect'}</span>
                          {item.leadContact.email && (
                            <a href={`mailto:${item.leadContact.email}`} className="flex items-center space-x-1 hover:underline">
                              <Mail className="w-3.5 h-3.5 text-emerald-700" />
                              <span>{item.leadContact.email}</span>
                            </a>
                          )}
                          {item.leadContact.phone && (
                            <a href={`tel:${item.leadContact.phone}`} className="flex items-center space-x-1 hover:underline">
                              <Phone className="w-3.5 h-3.5 text-emerald-700" />
                              <span>{item.leadContact.phone}</span>
                            </a>
                          )}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Right Actions & Status Selector */}
                  <div className="flex flex-wrap items-center gap-3 border-t md:border-t-0 pt-3 md:pt-0 border-slate-100">
                    {/* Assignee */}
                    {collaborators.length > 0 && onAssignAudit && (
                      <div className="space-y-0.5">
                        <label className="text-3xs uppercase font-bold text-slate-400 block">Responsable :</label>
                        <select
                          value={item.assignedTo || ''}
                          onChange={(e) => onAssignAudit(item.id, e.target.value)}
                          className="text-xs font-bold px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white"
                        >
                          <option value="">Non assigné</option>
                          {collaborators.map((c) => (
                            <option key={c.id} value={c.id}>
                              {c.name}
                            </option>
                          ))}
                        </select>
                      </div>
                    )}

                    {/* Step Dropdown */}
                    <div className="space-y-0.5">
                      <label className="text-3xs uppercase font-bold text-slate-400 block">Étape Pipeline :</label>
                      <select
                        value={item.prospectStatus || 'nouveau'}
                        onChange={(e: any) => onUpdateStatus(item.id, e.target.value, item.prospectNotes)}
                        className={`text-xs font-bold px-3 py-1.5 rounded-xl border ${stageConfig.badge}`}
                      >
                        {PIPELINE_STAGES.map((st) => (
                          <option key={st.key} value={st.key}>
                            {st.icon} {st.label}
                          </option>
                        ))}
                      </select>
                    </div>

                    <button
                      onClick={() => onSelectAudit(item)}
                      className="flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-emerald-800 text-white font-extrabold text-xs shadow-2xs transition-colors"
                    >
                      <Eye className="w-3.5 h-3.5" />
                      <span>Consulter Projet</span>
                    </button>

                    <button
                      onClick={() => onDeleteAudit(item.id)}
                      className="p-2 text-slate-400 hover:text-rose-600 rounded-xl hover:bg-rose-50 transition-colors"
                      title="Supprimer ce prospect"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                {/* Note line */}
                <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                  <span className="italic text-slate-600">
                    {item.prospectNotes ? `📝 "${item.prospectNotes}"` : 'Aucune note de prospection enregistrée.'}
                  </span>
                  <button
                    onClick={() => {
                      setEditingAudit(item);
                      setNoteInput(item.prospectNotes || '');
                      setStatusInput(item.prospectStatus || 'nouveau');
                      setAssigneeInput(item.assignedTo || '');
                    }}
                    className="text-2xs font-extrabold text-emerald-800 hover:underline flex items-center space-x-1"
                  >
                    <Edit3 className="w-3 h-3" />
                    <span>Éditer Note & Statut</span>
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* 5. QUICK EDIT AUDIT NOTE & STATUS MODAL */}
      {editingAudit && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5">
            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 className="font-black text-slate-900 text-lg">Mise à jour Commerciale</h3>
                <p className="text-2xs font-bold text-emerald-800">{editingAudit.companyName} ({editingAudit.cleanUrl})</p>
              </div>
              <button
                onClick={() => setEditingAudit(null)}
                className="p-1 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-4 text-xs">
              <div>
                <label className="block text-2xs font-bold uppercase text-slate-700 mb-1">
                  Étape du Pipeline Commercial
                </label>
                <select
                  value={statusInput}
                  onChange={(e: any) => setStatusInput(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 font-bold text-slate-900"
                >
                  {PIPELINE_STAGES.map((st) => (
                    <option key={st.key} value={st.key}>
                      {st.icon} {st.label}
                    </option>
                  ))}
                </select>
              </div>

              {collaborators.length > 0 && onAssignAudit && (
                <div>
                  <label className="block text-2xs font-bold uppercase text-slate-700 mb-1">
                    Consultant Responsable
                  </label>
                  <select
                    value={assigneeInput}
                    onChange={(e) => setAssigneeInput(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 font-bold text-slate-900"
                  >
                    <option value="">Non assigné</option>
                    {collaborators.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name} ({c.role})
                      </option>
                    ))}
                  </select>
                </div>
              )}

              <div>
                <label className="block text-2xs font-bold uppercase text-slate-700 mb-1">
                  Notes & Suivi de Prospection (Devis, rendez-vous, objections...)
                </label>
                <textarea
                  rows={4}
                  value={noteInput}
                  onChange={(e) => setNoteInput(e.target.value)}
                  placeholder="Ex : Devis de Bilan Carbone Scope 1-2-3 envoyé le 12/03. Le responsable RSE demande une option pour la contribution carbone locale..."
                  className="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-slate-50 text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500"
                />
              </div>
            </div>

            <div className="flex justify-end space-x-2 pt-2 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setEditingAudit(null)}
                className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={() => {
                  onUpdateStatus(editingAudit.id, statusInput, noteInput);
                  if (onAssignAudit && assigneeInput !== editingAudit.assignedTo) {
                    onAssignAudit(editingAudit.id, assigneeInput);
                  }
                  setEditingAudit(null);
                }}
                className="px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs flex items-center space-x-1.5"
              >
                <Save className="w-4 h-4" />
                <span>Enregistrer la mise à jour</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
