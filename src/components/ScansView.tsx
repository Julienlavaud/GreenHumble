import React, { useState, useEffect, useMemo } from 'react';
import { 
  Database, 
  Search, 
  ArrowUpDown, 
  Trash2, 
  ExternalLink, 
  Building2, 
  Users, 
  TrendingUp, 
  CheckCircle2, 
  AlertOctagon, 
  Scale, 
  Plus, 
  FolderPlus,
  CheckSquare,
  Square,
  AlertTriangle,
  X,
  Layers,
  ChevronDown,
  ChevronUp,
  History,
  Sparkles,
  ArrowRight
} from 'lucide-react';
import { GreenwashingAudit, ScanRecord } from '../types.js';
import { cleanCompanyName } from '../utils/companyUtils.js';

interface ScansViewProps {
  audits: GreenwashingAudit[];
  onSelectAudit: (audit: GreenwashingAudit) => void;
  onDeleteAudit?: (id: string) => void;
  onAddToProject?: (scanId: string, domain: string) => void;
}

function normalizeStr(str: string): string {
  return (str || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

export const ScansView: React.FC<ScansViewProps> = ({
  audits,
  onSelectAudit,
  onDeleteAudit,
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [viewMode, setViewMode] = useState<'grouped' | 'individual'>('grouped');
  const [filterType, setFilterType] = useState<string>('all');
  const [filterRisk, setFilterRisk] = useState<string>('all');
  const [filterScoreRange, setFilterScoreRange] = useState<string>('all');
  const [sortBy, setSortBy] = useState<'date_desc' | 'scans_count' | 'score_asc' | 'score_desc' | 'name_asc'>('date_desc');
  const [serverScans, setServerScans] = useState<ScanRecord[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [actionMsg, setActionMsg] = useState<string | null>(null);

  // Multi-selection state
  const [selectedScanIds, setSelectedScanIds] = useState<string[]>([]);
  const [isConfirmingBulkDelete, setIsConfirmingBulkDelete] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  // Expanded companies in grouped view
  const [expandedDomains, setExpandedDomains] = useState<Set<string>>(new Set());

  useEffect(() => {
    fetchServerScans();

    const handleDataChanged = () => {
      fetchServerScans();
    };

    window.addEventListener('greenhumble_data_changed', handleDataChanged);
    return () => {
      window.removeEventListener('greenhumble_data_changed', handleDataChanged);
    };
  }, []);

  const fetchServerScans = async () => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/admin/souverain/file?file=scans');
      if (res.ok) {
        const data = await res.json();
        if (Array.isArray(data.content)) {
          setServerScans(data.content);
        }
      }
    } catch (_) {}
    finally {
      setIsLoading(false);
    }
  };

  const handleAddScanToProject = async (domain: string, scanId: string) => {
    try {
      const res = await fetch('/api/projets/add-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ domain, scanId })
      });
      if (res.ok) {
        setActionMsg(`Scan ${domain} associé au projet avec succès !`);
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {
      setActionMsg('Erreur lors de l’association au projet.');
      setTimeout(() => setActionMsg(null), 3000);
    }
  };

  // Combine live audits and serverScans into individual scan items
  const combinedIndividualList = useMemo(() => {
    const list: Array<{
      id: string;
      url: string;
      cleanUrl: string;
      companyName: string;
      date: string;
      score: number;
      riskLevel?: string;
      secteur?: string;
      salaries?: string;
      ca?: string;
      typeAudit?: string;
      claimsCount: number;
      auditObject?: GreenwashingAudit;
    }> = [];

    const seenIds = new Set<string>();

    audits.forEach((a) => {
      seenIds.add(a.id);
      if (a.scan_id) seenIds.add(a.scan_id);
      const clean = (a.cleanUrl || a.url || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0].toLowerCase();
      const compName = cleanCompanyName(a.companyName, clean || a.url);
      const s = a.isUnnotable || a.riskScore === null ? null : (a.riskScore ?? a.score_global ?? null);
      list.push({
        id: a.id,
        url: a.url,
        cleanUrl: clean || a.url,
        companyName: compName,
        date: a.analyzedAt || new Date().toISOString(),
        score: s,
        riskLevel: a.riskLevel,
        secteur: a.entreprise_secteur || a.sector,
        salaries: a.entreprise_salaries || a.headcount,
        ca: a.entreprise_ca || a.turnover,
        typeAudit: a.type_audit || 'full',
        claimsCount: a.detectedClaims?.length || 0,
        auditObject: {
          ...a,
          companyName: compName
        }
      });
    });

    serverScans.forEach((s) => {
      if (!seenIds.has(s.id)) {
        const clean = (s.url || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0].toLowerCase();
        const matchingAudit = audits.find(a => {
          if (a.scan_id && a.scan_id === s.id) return true;
          const aClean = (a.cleanUrl || a.url || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0].toLowerCase();
          return aClean === clean || (clean && aClean && (clean.includes(aClean) || aClean.includes(clean)));
        });

        // If this server scan matches an already listed client audit, do not create duplicate
        if (matchingAudit) {
          seenIds.add(s.id);
          return;
        }

        const compName = cleanCompanyName(s.resultat_ia?.entreprise_nom, clean || s.url);
        const scanScore = s.resultat_ia?.score_global ?? s.resultat_ia?.score ?? 50;

        const fallbackAudit: GreenwashingAudit = {
          id: s.id,
          url: s.url,
          cleanUrl: clean,
          companyName: compName,
          analyzedAt: s.date_scan || new Date().toISOString(),
          riskScore: scanScore,
          score_global: scanScore,
          riskLevel: (s.resultat_ia?.riskLevel || (scanScore >= 70 ? 'faible' : scanScore >= 40 ? 'modere' : 'critique')) as any,
          sector: s.entreprise_secteur || s.resultat_ia?.entreprise_secteur,
          detectedClaims: s.resultat_ia?.risks || [],
          clientSummary: {
            headline: s.resultat_ia?.summary || 'Audit de conformité',
            riskVerdict: s.resultat_ia?.summary || 'Diagnostic réalisé',
            keyUrgentAction: s.resultat_ia?.recommendations?.[0] || '',
            immediateRisks: [],
            stepsToCompliance: (s.resultat_ia?.recommendations || []).map((r: string, i: number) => ({
              step: i + 1,
              title: `Action ${i + 1}`,
              description: r
            }))
          },
          piliers_evaluation: s.resultat_ia?.piliers_evaluation,
          opportunite_commerciale: s.resultat_ia?.opportunite_commerciale,
          commercialProspectingKit: s.resultat_ia?.prospection ? {
            targetPersona: 'Responsable RSE / Direction',
            executiveSummary: s.resultat_ia.summary || '',
            phonePitch: s.resultat_ia.prospection.phonePitch,
            emailProspecting: s.resultat_ia.prospection.emailProspecting,
            climatLocalProposal: s.resultat_ia.prospection.climatLocalProposal
          } : undefined,
          totalBannedWordsDetected: (s.resultat_ia?.risks || []).length,
          businessOpportunities: s.resultat_ia?.opportunite_commerciale ? [s.resultat_ia.opportunite_commerciale] : [],
          source: 'interne',
          prospectStatus: 'nouveau'
        };

        list.push({
          id: s.id,
          url: s.url,
          cleanUrl: clean || s.url,
          companyName: compName,
          date: s.date_scan || new Date().toISOString(),
          score: scanScore,
          riskLevel: s.resultat_ia?.riskLevel || (scanScore >= 70 ? 'faible' : scanScore >= 40 ? 'modere' : 'critique'),
          secteur: s.entreprise_secteur || s.resultat_ia?.entreprise_secteur,
          salaries: s.entreprise_salaries || s.resultat_ia?.entreprise_salaries,
          ca: s.entreprise_ca || s.resultat_ia?.entreprise_ca,
          typeAudit: s.type_audit || 'full',
          claimsCount: (s.resultat_ia?.risks || s.resultat_ia?.claims || []).length,
          auditObject: fallbackAudit
        });
      }
    });

    return list;
  }, [audits, serverScans]);

  // Grouped by company / domain with multi-notes analysis (LE SCAN LE PLUS FAVORABLE = NOTE DE L'ENTREPRISE)
  const groupedCompanies = useMemo(() => {
    const map: Record<string, {
      domainKey: string;
      companyName: string;
      secteur?: string;
      salaries?: string;
      ca?: string;
      latestDate: string;
      latestScore: number;
      latestRiskLevel?: string;
      bestScore: number;
      bestRiskLevel: string;
      avgScore: number;
      minScore: number;
      maxScore: number;
      scores: number[];
      scans: typeof combinedIndividualList;
      latestItem: typeof combinedIndividualList[0];
      favorableItem: typeof combinedIndividualList[0];
    }> = {};

    combinedIndividualList.forEach((item) => {
      const dKey = item.cleanUrl.toLowerCase();
      const itemScore = item.score !== null && item.score !== undefined ? item.score : 50;

      if (!map[dKey]) {
        map[dKey] = {
          domainKey: dKey,
          companyName: item.companyName,
          secteur: item.secteur,
          salaries: item.salaries,
          ca: item.ca,
          latestDate: item.date,
          latestScore: itemScore,
          latestRiskLevel: item.riskLevel,
          bestScore: itemScore,
          bestRiskLevel: item.riskLevel || (itemScore >= 70 ? 'faible' : itemScore >= 40 ? 'modere' : 'critique'),
          avgScore: itemScore,
          minScore: itemScore,
          maxScore: itemScore,
          scores: [itemScore],
          scans: [item],
          latestItem: item,
          favorableItem: item
        };
      } else {
        map[dKey].scans.push(item);
        map[dKey].scores.push(itemScore);

        // Check if newer date
        if (new Date(item.date).getTime() > new Date(map[dKey].latestDate).getTime()) {
          map[dKey].latestDate = item.date;
          map[dKey].latestScore = itemScore;
          map[dKey].latestRiskLevel = item.riskLevel;
          map[dKey].latestItem = item;
          if (item.companyName && !item.companyName.includes('.')) {
            map[dKey].companyName = item.companyName;
          }
        }

        const allScores = map[dKey].scores;
        map[dKey].minScore = Math.min(...allScores);
        map[dKey].maxScore = Math.max(...allScores);
        // RÈGLE MÉTIER : "Le scan le plus favorable = note de l'entreprise"
        map[dKey].bestScore = Math.max(...allScores);
        map[dKey].bestRiskLevel = map[dKey].bestScore >= 70 ? 'faible' : map[dKey].bestScore >= 40 ? 'modere' : 'critique';
        map[dKey].avgScore = Math.round(allScores.reduce((a, b) => a + b, 0) / allScores.length);

        // Pick favorableItem
        const bestItem = map[dKey].scans.find(sc => (sc.score ?? 0) === map[dKey].bestScore);
        if (bestItem) {
          map[dKey].favorableItem = bestItem;
        }
      }
    });

    return Object.values(map);
  }, [combinedIndividualList]);

  // Filtered and sorted grouped list
  const filteredGrouped = useMemo(() => {
    const rawQuery = normalizeStr(searchTerm);
    const tokens = rawQuery.split(/\s+/).filter(Boolean);

    return groupedCompanies.filter((group) => {
      // Risk filter (evaluated against bestScore = company score)
      if (filterRisk !== 'all' && group.bestRiskLevel?.toLowerCase() !== filterRisk.toLowerCase()) {
        return false;
      }
      // Score range filter based on company score (scan le plus favorable)
      if (filterScoreRange === 'critical' && group.bestScore >= 40) return false;
      if (filterScoreRange === 'medium' && (group.bestScore < 40 || group.bestScore >= 70)) return false;
      if (filterScoreRange === 'compliant' && group.bestScore < 70) return false;

      if (tokens.length === 0) return true;

      const targetStr = normalizeStr(`
        ${group.companyName}
        ${group.domainKey}
        ${group.secteur || ''}
        ${group.scans.map(s => s.typeAudit || '').join(' ')}
      `);

      return tokens.every(token => {
        if (token.startsWith('>') || token.startsWith('<')) {
          const num = parseInt(token.substring(1), 10);
          if (!isNaN(num)) {
            return token.startsWith('>') ? group.bestScore >= num : group.bestScore <= num;
          }
        }
        return targetStr.includes(token);
      });
    }).sort((a, b) => {
      if (sortBy === 'scans_count') return b.scans.length - a.scans.length;
      if (sortBy === 'score_asc') return a.bestScore - b.bestScore;
      if (sortBy === 'score_desc') return b.bestScore - a.bestScore;
      if (sortBy === 'name_asc') return a.companyName.localeCompare(b.companyName);
      return new Date(b.latestDate).getTime() - new Date(a.latestDate).getTime();
    });
  }, [groupedCompanies, searchTerm, filterRisk, filterScoreRange, sortBy]);

  // Filtered individual list
  const filteredIndividual = useMemo(() => {
    const rawQuery = normalizeStr(searchTerm);
    const tokens = rawQuery.split(/\s+/).filter(Boolean);

    return combinedIndividualList.filter((item) => {
      if (filterType !== 'all' && item.typeAudit !== filterType) return false;
      if (filterRisk !== 'all' && item.riskLevel?.toLowerCase() !== filterRisk.toLowerCase()) return false;
      if (filterScoreRange === 'critical' && item.score >= 40) return false;
      if (filterScoreRange === 'medium' && (item.score < 40 || item.score >= 70)) return false;
      if (filterScoreRange === 'compliant' && item.score < 70) return false;

      if (tokens.length === 0) return true;

      const target = normalizeStr(`${item.companyName} ${item.cleanUrl} ${item.secteur || ''}`);
      return tokens.every(token => target.includes(token));
    }).sort((a, b) => {
      if (sortBy === 'score_asc') return a.score - b.score;
      if (sortBy === 'score_desc') return b.score - a.score;
      if (sortBy === 'name_asc') return a.companyName.localeCompare(b.companyName);
      return new Date(b.date).getTime() - new Date(a.date).getTime();
    });
  }, [combinedIndividualList, searchTerm, filterType, filterRisk, filterScoreRange, sortBy]);

  // Toggle company drawer
  const toggleExpand = (domainKey: string) => {
    setExpandedDomains(prev => {
      const next = new Set(prev);
      if (next.has(domainKey)) next.delete(domainKey);
      else next.add(domainKey);
      return next;
    });
  };

  // Toggle selection
  const handleToggleSelect = (id: string) => {
    setSelectedScanIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const isAllIndividualSelected = filteredIndividual.length > 0 && filteredIndividual.every(item => selectedScanIds.includes(item.id));
  const handleToggleSelectAll = () => {
    if (isAllIndividualSelected) {
      setSelectedScanIds(prev => prev.filter(id => !filteredIndividual.some(f => f.id === id)));
    } else {
      const ids = filteredIndividual.map(i => i.id);
      setSelectedScanIds(prev => Array.from(new Set([...prev, ...ids])));
    }
  };

  // Bulk delete execution
  const handleExecuteBulkDelete = async () => {
    if (selectedScanIds.length === 0) return;
    setIsDeleting(true);

    try {
      await fetch('/api/scans/bulk-delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: selectedScanIds })
      });

      selectedScanIds.forEach((scanId) => {
        if (onDeleteAudit) {
          onDeleteAudit(scanId);
        }
      });

      setServerScans(prev => prev.filter(s => !selectedScanIds.includes(s.id)));
      setActionMsg(`${selectedScanIds.length} scan(s) supprimé(s) avec succès.`);
      setSelectedScanIds([]);
      setTimeout(() => setActionMsg(null), 3000);
    } catch (_) {
      setActionMsg('Erreur lors de la suppression groupée.');
    } finally {
      setIsDeleting(false);
      setIsConfirmingBulkDelete(false);
    }
  };

  return (
    <div className="space-y-6 text-slate-800">
      {/* Header Bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
            <Database className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-xl font-black text-slate-900 font-serif tracking-tight">5. Historique & Moteur de Recherche Scans</h2>
            <p className="text-xs text-slate-500">Gestion multi-notes par entreprise, comparaison et recherche multicritères</p>
          </div>
        </div>

        {/* View Mode Switcher: Grouped vs Individual */}
        <div className="flex items-center space-x-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
          <button
            type="button"
            onClick={() => setViewMode('grouped')}
            className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 cursor-pointer ${
              viewMode === 'grouped'
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Building2 className="w-3.5 h-3.5" />
            <span>Vue Entreprises ({groupedCompanies.length})</span>
          </button>
          <button
            type="button"
            onClick={() => setViewMode('individual')}
            className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 cursor-pointer ${
              viewMode === 'individual'
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <Layers className="w-3.5 h-3.5" />
            <span>Tous les scans ({combinedIndividualList.length})</span>
          </button>
        </div>
      </div>

      {actionMsg && (
        <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2 animate-fadeIn">
          <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          <span>{actionMsg}</span>
        </div>
      )}

      {/* MULTI-SELECTION ACTION BAR */}
      {selectedScanIds.length > 0 && (
        <div className="bg-rose-50 p-4 rounded-2xl border border-rose-200 flex flex-wrap items-center justify-between gap-3 animate-fadeIn shadow-xs">
          <div className="flex items-center space-x-3">
            <span className="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 font-black text-xs flex items-center justify-center border border-rose-200">
              {selectedScanIds.length}
            </span>
            <span className="text-xs font-bold text-rose-900">
              {selectedScanIds.length} scan{selectedScanIds.length > 1 ? 's' : ''} sélectionné{selectedScanIds.length > 1 ? 's' : ''}
            </span>
          </div>

          <div className="flex items-center space-x-2">
            <button
              onClick={() => setSelectedScanIds([])}
              className="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold transition-colors cursor-pointer"
            >
              Désélectionner tout
            </button>
            <button
              onClick={() => setIsConfirmingBulkDelete(true)}
              className="px-4 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black flex items-center space-x-1.5 transition-colors shadow-xs cursor-pointer"
            >
              <Trash2 className="w-3.5 h-3.5" />
              <span>Supprimer la sélection ({selectedScanIds.length})</span>
            </button>
          </div>
        </div>
      )}

      {/* Advanced Search & Multi-Filters Toolbar */}
      <div className="bg-white p-5 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          {/* Main Search */}
          <div className="relative md:col-span-2">
            <Search className="w-4 h-4 text-slate-400 absolute left-4 top-3.5" />
            <input
              type="text"
              placeholder="Recherche puissante : Nom, Domaine, Secteur, >70, <40..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-11 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
            />
          </div>

          {/* Score Range Filter */}
          <select
            value={filterScoreRange}
            onChange={(e) => setFilterScoreRange(e.target.value)}
            className="px-3.5 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 focus:outline-none focus:border-blue-500 cursor-pointer"
          >
            <option value="all">🎯 Tous les scores</option>
            <option value="critical">🚨 Risque Critique (&lt; 40)</option>
            <option value="medium">⚠️ Risque Modéré (40 à 69)</option>
            <option value="compliant">✅ Conforme (&ge; 70)</option>
          </select>

          {/* Sort By */}
          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value as any)}
            className="px-3.5 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 focus:outline-none focus:border-blue-500 cursor-pointer"
          >
            <option value="date_desc">🕒 Plus récents d'abord</option>
            <option value="scans_count">📊 Plus d'analyses (Multi-notes)</option>
            <option value="score_asc">🚨 Note la plus basse (Risque max)</option>
            <option value="score_desc">🏆 Note la plus haute</option>
            <option value="name_asc">🔤 Nom A - Z</option>
          </select>
        </div>
      </div>

      {/* VIEW 1: GROUPED BY ENTERPRISE WITH MULTI-NOTES & HISTORIC ACCORDION */}
      {viewMode === 'grouped' ? (
        <div className="space-y-4">
          {filteredGrouped.length === 0 ? (
            <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center text-slate-500 space-y-2 shadow-sm">
              <Building2 className="w-8 h-8 mx-auto text-slate-400" />
              <p className="text-sm font-bold text-slate-700">Aucune entreprise trouvée</p>
              <p className="text-xs text-slate-500">Modifiez vos critères de recherche ou lancez un nouvel audit.</p>
            </div>
          ) : (
            filteredGrouped.map((company) => {
              const isExpanded = expandedDomains.has(company.domainKey);
              const hasMultiple = company.scans.length > 1;
              const isCritical = company.bestScore < 40;
              const isHigh = company.bestScore >= 70;

              return (
                <div 
                  key={company.domainKey}
                  className="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm transition-all hover:border-slate-300"
                >
                  {/* Main Company Row */}
                  <div className="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div className="flex items-start space-x-3 flex-1 min-w-0">
                      <button
                        type="button"
                        onClick={() => {
                          const companyScanIds = company.scans.map(s => s.id);
                          const allSelected = companyScanIds.every(id => selectedScanIds.includes(id));
                          if (allSelected) {
                            setSelectedScanIds(prev => prev.filter(id => !companyScanIds.includes(id)));
                          } else {
                            setSelectedScanIds(prev => Array.from(new Set([...prev, ...companyScanIds])));
                          }
                        }}
                        className="mt-1 text-slate-400 hover:text-slate-700 cursor-pointer shrink-0"
                        title="Sélectionner cette entreprise (tous ses scans)"
                      >
                        {company.scans.every(s => selectedScanIds.includes(s.id)) ? (
                          <CheckSquare className="w-5 h-5 text-blue-600" />
                        ) : company.scans.some(s => selectedScanIds.includes(s.id)) ? (
                          <CheckSquare className="w-5 h-5 text-blue-400" />
                        ) : (
                          <Square className="w-5 h-5 text-slate-300" />
                        )}
                      </button>

                      <div className="space-y-1.5 flex-1 min-w-0">
                        <div className="flex flex-wrap items-center gap-2.5">
                          <h3 className="text-base font-black text-slate-900 truncate">
                            {company.companyName}
                          </h3>
                          <a
                            href={`https://${company.domainKey}`}
                            target="_blank"
                            rel="noreferrer"
                            className="text-xs text-slate-500 hover:text-blue-600 flex items-center space-x-1 font-mono"
                          >
                            <span>{company.domainKey}</span>
                            <ExternalLink className="w-3 h-3 opacity-60" />
                          </a>
                          <a
                            href={`https://www.pappers.fr/recherche?q=${encodeURIComponent(company.companyName || company.domainKey)}`}
                            target="_blank"
                            rel="noreferrer"
                            className="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-blue-600 border border-slate-200 flex items-center space-x-1 transition-colors"
                            title="Consulter les données Pappers / Societe.com"
                          >
                            <span>Pappers / Societe.com</span>
                            <ExternalLink className="w-2.5 h-2.5 opacity-80" />
                          </a>
                          {hasMultiple && (
                            <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 flex items-center space-x-1">
                              <History className="w-3 h-3" />
                              <span>{company.scans.length} scans enregistrés</span>
                            </span>
                          )}
                        </div>

                        <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500 pt-0.5">
                          <span className="flex items-center space-x-1 text-slate-700">
                            <Building2 className="w-3.5 h-3.5 text-slate-400" />
                            <span>{company.secteur || 'Services'}</span>
                          </span>
                          <span>•</span>
                          <span>{company.salaries || '11-50'} sal.</span>
                          <span>•</span>
                          <span className="text-slate-400 font-mono text-[11px]">
                            Dernier audit : {new Date(company.latestDate).toLocaleDateString('fr-FR')}
                          </span>
                        </div>

                        {/* Multi-scores evolution badge */}
                        {hasMultiple && (
                          <div className="flex flex-wrap items-center gap-2 pt-2">
                            <span className="text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                              Historique des notes :
                            </span>
                            <div className="flex flex-wrap items-center gap-1.5">
                              {company.scores.map((sc, i) => {
                                const isBest = sc === company.bestScore;
                                return (
                                  <span
                                    key={i}
                                    className={`px-2 py-0.5 rounded text-[11px] font-mono font-bold ${
                                      isBest
                                        ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 ring-1 ring-emerald-300'
                                        : sc >= 70 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : sc >= 40 ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                                    }`}
                                    title={isBest ? "Scan le plus favorable (retenu comme note de l'entreprise)" : undefined}
                                  >
                                    {sc}/100 {isBest && '★ retenue'}
                                  </span>
                                );
                              })}
                            </div>
                            <span className="text-[11px] text-slate-500 font-medium">
                              (Moyenne : <strong className="text-slate-800">{company.avgScore}/100</strong> • Écart : {company.minScore} à {company.maxScore})
                            </span>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Right side: company score pill (best / most favorable scan) & actions */}
                    <div className="flex items-center space-x-3 shrink-0 self-end lg:self-center">
                      <div className="text-right bg-slate-50 px-4 py-2.5 rounded-2xl border border-slate-200">
                        <span className="text-[9px] uppercase font-bold text-emerald-700 block tracking-wider">
                          Note Entreprise
                        </span>
                        <div className={`text-xl font-black font-mono ${
                          company.bestScore < 40 ? 'text-rose-600' : company.bestScore >= 70 ? 'text-emerald-600' : 'text-amber-600'
                        }`}>
                          {company.bestScore}/100
                        </div>
                        <span className="text-[9px] text-slate-500 font-medium block">
                          Scan le plus favorable
                        </span>
                      </div>

                      <button
                        onClick={() => {
                          const target = company.favorableItem?.auditObject || company.latestItem.auditObject;
                          if (target) {
                            onSelectAudit(target);
                          }
                        }}
                        className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition-colors cursor-pointer shadow-sm"
                      >
                        Ouvrir le dossier
                      </button>

                      <button
                        onClick={() => handleAddScanToProject(company.domainKey, company.latestItem.id)}
                        className="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-emerald-700 transition-colors cursor-pointer border border-slate-200"
                        title="Ajouter au projet"
                      >
                        <FolderPlus className="w-4 h-4" />
                      </button>

                      {onDeleteAudit && (
                        <button
                          onClick={() => {
                            const companyScanIds = company.scans.map(s => s.id);
                            setSelectedScanIds(companyScanIds);
                            setIsConfirmingBulkDelete(true);
                          }}
                          className="p-2.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-colors cursor-pointer border border-slate-200"
                          title="Supprimer les scans de cette entreprise"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}

                      {hasMultiple && (
                        <button
                          onClick={() => toggleExpand(company.domainKey)}
                          className={`p-2.5 rounded-xl border transition-colors cursor-pointer flex items-center space-x-1 text-xs font-bold ${
                            isExpanded
                              ? 'bg-blue-50 text-blue-700 border-blue-200'
                              : 'bg-slate-100 text-slate-600 border-slate-200 hover:text-slate-900'
                          }`}
                          title="Voir tous les scans de cette entreprise"
                        >
                          <span>{company.scans.length} scans</span>
                          {isExpanded ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                        </button>
                      )}
                    </div>
                  </div>

                  {/* Expandable Drawer: Individual Scans for this Company */}
                  {isExpanded && (
                    <div className="bg-slate-50 p-5 border-t border-slate-200 space-y-3 animate-fadeIn">
                      <div className="flex items-center justify-between text-xs font-bold text-slate-600 px-1">
                        <span>Détail des {company.scans.length} analyses réalisées pour {company.companyName} :</span>
                      </div>

                      <div className="space-y-2">
                        {company.scans.map((scItem, idx) => {
                          const isScCritical = scItem.score < 40;
                          const isScHigh = scItem.score >= 70;
                          const isSelected = selectedScanIds.includes(scItem.id);

                          return (
                            <div
                              key={scItem.id}
                              className={`p-3.5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 ${
                                isSelected ? 'bg-blue-50 border-blue-300' : 'bg-white border-slate-200 hover:border-slate-300'
                              }`}
                            >
                              <div className="flex items-center space-x-3 min-w-0">
                                <button
                                  type="button"
                                  onClick={() => handleToggleSelect(scItem.id)}
                                  className="text-slate-400 hover:text-slate-700 cursor-pointer"
                                >
                                  {isSelected ? <CheckSquare className="w-4 h-4 text-blue-600" /> : <Square className="w-4 h-4 text-slate-300" />}
                                </button>

                                <div>
                                  <div className="flex items-center space-x-2">
                                    <span className="text-xs font-bold text-slate-900">Scan #{company.scans.length - idx}</span>
                                    <span className="text-[10px] font-mono text-slate-600 px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                                      {scItem.typeAudit === 'business' ? 'Opportunité Business' : scItem.typeAudit === 'conformité' ? 'Conformité' : 'Full Audit'}
                                    </span>
                                  </div>
                                  <div className="text-[11px] text-slate-500 flex items-center space-x-2 mt-0.5">
                                    <span>Date : {new Date(scItem.date).toLocaleString('fr-FR')}</span>
                                    <span>•</span>
                                    <span>{scItem.claimsCount} allégations</span>
                                  </div>
                                </div>
                              </div>

                              <div className="flex items-center space-x-3 self-end sm:self-center">
                                <div className={`text-base font-black font-mono ${
                                  isScCritical ? 'text-rose-600' : isScHigh ? 'text-emerald-600' : 'text-amber-600'
                                }`}>
                                  {scItem.score}/100
                                </div>

                                <button
                                  onClick={() => {
                                    if (scItem.auditObject) {
                                      onSelectAudit(scItem.auditObject);
                                    }
                                  }}
                                  className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold text-xs transition-colors cursor-pointer"
                                >
                                  Consulter
                                </button>

                                {onDeleteAudit && (
                                  <button
                                    onClick={() => {
                                      handleToggleSelect(scItem.id);
                                      setIsConfirmingBulkDelete(true);
                                    }}
                                    className="p-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-colors cursor-pointer border border-slate-200"
                                    title="Supprimer ce scan"
                                  >
                                    <Trash2 className="w-3.5 h-3.5" />
                                  </button>
                                )}
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>
              );
            })
          )}
        </div>
      ) : (
        /* VIEW 2: INDIVIDUAL SCANS TABLE */
        <div className="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-800">
              <thead className="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                  <th className="py-4 px-4 w-10 text-center">
                    <button
                      type="button"
                      onClick={handleToggleSelectAll}
                      className="text-slate-400 hover:text-slate-700 transition-colors"
                      title={isAllIndividualSelected ? 'Tout désélectionner' : 'Tout sélectionner'}
                    >
                      {isAllIndividualSelected ? (
                        <CheckSquare className="w-4 h-4 text-blue-600" />
                      ) : (
                        <Square className="w-4 h-4 text-slate-300" />
                      )}
                    </button>
                  </th>
                  <th className="py-4 px-5">Site & Entreprise</th>
                  <th className="py-4 px-5">Profil Auto-Déduit</th>
                  <th className="py-4 px-5 text-center">Note IA</th>
                  <th className="py-4 px-5">Allégations</th>
                  <th className="py-4 px-5">Date</th>
                  <th className="py-4 px-5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredIndividual.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="py-12 text-center text-slate-500">
                      Aucun scan trouvé pour ces critères de recherche.
                    </td>
                  </tr>
                ) : (
                  filteredIndividual.map((item) => {
                    const isHighRisk = item.score < 40;
                    const isMediumRisk = item.score >= 40 && item.score < 70;
                    const isSelected = selectedScanIds.includes(item.id);

                    return (
                      <tr 
                        key={item.id} 
                        className={`transition-colors ${isSelected ? 'bg-blue-50/70' : 'hover:bg-slate-50'}`}
                      >
                        <td className="py-4 px-4 text-center">
                          <button
                            type="button"
                            onClick={() => handleToggleSelect(item.id)}
                            className="text-slate-400 hover:text-slate-700 transition-colors cursor-pointer"
                          >
                            {isSelected ? (
                              <CheckSquare className="w-4 h-4 text-blue-600" />
                            ) : (
                              <Square className="w-4 h-4 text-slate-300" />
                            )}
                          </button>
                        </td>

                        <td className="py-4 px-5">
                          <div className="font-bold text-slate-900 text-sm">{item.companyName}</div>
                          <a 
                            href={item.url.startsWith('http') ? item.url : `https://${item.url}`} 
                            target="_blank" 
                            rel="noreferrer"
                            className="text-slate-500 hover:text-blue-600 flex items-center space-x-1 mt-0.5"
                          >
                            <span>{item.cleanUrl}</span>
                            <ExternalLink className="w-3 h-3 opacity-60" />
                          </a>
                        </td>

                        <td className="py-4 px-5">
                          <div className="space-y-1 text-[11px]">
                            <div className="flex items-center space-x-1 text-slate-700">
                              <Building2 className="w-3.5 h-3.5 text-blue-600" />
                              <span>{item.secteur || 'Non déterminé'}</span>
                            </div>
                            <div className="flex items-center space-x-3 text-slate-500 text-2xs">
                              <span>{item.salaries || '11-50'} sal.</span>
                              <span>•</span>
                              <span>{item.ca || '2M€-10M€'}</span>
                            </div>
                          </div>
                        </td>

                        <td className="py-4 px-5 text-center">
                          <div className="inline-flex flex-col items-center">
                            {item.score === null || item.auditObject?.isUnnotable ? (
                              <>
                                <span className="text-base font-black font-mono text-amber-600">
                                  Non noté
                                </span>
                                <span className="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200" title={item.auditObject?.unnotableReason || "Site indisponible"}>
                                  Site Indisponible
                                </span>
                              </>
                            ) : (
                              <>
                                <span className={`text-base font-black font-mono ${
                                  isHighRisk ? 'text-rose-600' : isMediumRisk ? 'text-amber-600' : 'text-emerald-600'
                                }`}>
                                  {item.score}/100
                                </span>
                                <span className={`text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${
                                  isHighRisk ? 'bg-rose-100 text-rose-800 border border-rose-200' : 
                                  isMediumRisk ? 'bg-amber-100 text-amber-800 border border-amber-200' : 
                                  'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                }`}>
                                  {item.riskLevel || 'Conforme'}
                                </span>
                              </>
                            )}
                          </div>
                        </td>

                        <td className="py-4 px-5">
                          <button
                            onClick={() => {
                              if (item.auditObject) {
                                onSelectAudit(item.auditObject);
                              }
                            }}
                            className="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-mono font-bold text-xs transition-colors cursor-pointer"
                            title="Lien direct vers la page des allégations détectées pour ce scan"
                          >
                            <span>{item.claimsCount}</span>
                            <span className="text-[10px] text-rose-600 font-sans font-semibold">allég.</span>
                          </button>
                        </td>

                        <td className="py-4 px-5 text-slate-500 text-2xs font-mono">
                          {new Date(item.date).toLocaleDateString('fr-FR')}
                        </td>

                        <td className="py-4 px-5 text-right">
                          <div className="flex items-center justify-end space-x-2">
                            <button
                              onClick={() => {
                                if (item.auditObject) {
                                  onSelectAudit(item.auditObject);
                                }
                              }}
                              className="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition-colors cursor-pointer shadow-sm"
                            >
                              Ouvrir
                            </button>

                            <button
                              onClick={() => handleAddScanToProject(item.cleanUrl, item.id)}
                              className="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-emerald-700 transition-colors cursor-pointer border border-slate-200"
                              title="Ajouter au projet"
                            >
                              <FolderPlus className="w-4 h-4" />
                            </button>

                            {onDeleteAudit && (
                              <button
                                onClick={() => {
                                  handleToggleSelect(item.id);
                                  setIsConfirmingBulkDelete(true);
                                }}
                                className="p-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-colors cursor-pointer border border-slate-200"
                                title="Supprimer ce scan"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* MODAL CONFIRMATION BULK DELETE */}
      {isConfirmingBulkDelete && (
        <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-md w-full border border-slate-200 p-6 space-y-5 shadow-2xl">
            <div className="flex items-center space-x-3 text-rose-600">
              <div className="w-10 h-10 rounded-2xl bg-rose-50 flex items-center justify-center">
                <AlertTriangle className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900">Confirmer la suppression</h3>
                <p className="text-xs text-slate-500">Suppression définitive de {selectedScanIds.length} scan(s)</p>
              </div>
            </div>

            <p className="text-xs text-slate-600 leading-relaxed">
              Êtes-vous sûr de vouloir supprimer définitivement les <strong>{selectedScanIds.length} scan(s)</strong> sélectionnés ? Cette action est irréversible.
            </p>

            <div className="flex items-center justify-end space-x-3 pt-2">
              <button
                type="button"
                onClick={() => setIsConfirmingBulkDelete(false)}
                disabled={isDeleting}
                className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-bold transition-colors cursor-pointer"
              >
                Annuler
              </button>
              <button
                type="button"
                onClick={handleExecuteBulkDelete}
                disabled={isDeleting}
                className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black transition-colors cursor-pointer flex items-center space-x-1.5 shadow-sm"
              >
                <Trash2 className="w-3.5 h-3.5" />
                <span>{isDeleting ? 'Suppression...' : 'Confirmer la suppression'}</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
