import React, { useState, useEffect, useMemo, useRef } from 'react';
import { 
  Search, 
  X, 
  Building2, 
  ArrowRight, 
  Calendar, 
  TrendingUp, 
  ShieldAlert, 
  ExternalLink, 
  History, 
  Sliders, 
  CheckCircle2, 
  FileSearch, 
  FolderGit2, 
  ClipboardList,
  Sparkles,
  Command
} from 'lucide-react';
import { GreenwashingAudit, ScanRecord } from '../types.js';

interface GlobalSearchModalProps {
  isOpen: boolean;
  onClose: () => void;
  audits: GreenwashingAudit[];
  onSelectAudit: (audit: GreenwashingAudit) => void;
  onOpenAuditForDomain?: (domain: string) => void;
  onNavigateTab?: (tab: string) => void;
}

function normalizeStr(str: string): string {
  return (str || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

export const GlobalSearchModal: React.FC<GlobalSearchModalProps> = ({
  isOpen,
  onClose,
  audits,
  onSelectAudit,
  onOpenAuditForDomain,
  onNavigateTab,
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [activeFilter, setActiveFilter] = useState<'all' | 'companies' | 'critical' | 'recent'>('all');
  const [selectedIndex, setSelectedIndex] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 50);
      setSelectedIndex(0);
    } else {
      setSearchTerm('');
    }
  }, [isOpen]);

  // Group all audits by domain / company
  const groupedCompanies = useMemo(() => {
    const map: Record<string, {
      cleanDomain: string;
      companyName: string;
      sector: string;
      salaries: string;
      ca: string;
      latestAudit: GreenwashingAudit;
      allAudits: GreenwashingAudit[];
      scores: number[];
      latestScore: number;
      avgScore: number;
      minScore: number;
      maxScore: number;
      latestDate: string;
    }> = {};

    audits.forEach((a) => {
      const rawDomain = (a.cleanUrl || a.url || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0].toLowerCase();
      const domain = rawDomain || 'entreprise.fr';

      if (!map[domain]) {
        map[domain] = {
          cleanDomain: domain,
          companyName: a.companyName || domain.toUpperCase(),
          sector: a.entreprise_secteur || a.sector || 'Services & Industrie',
          salaries: a.entreprise_salaries || a.headcount || '11-50',
          ca: a.entreprise_ca || a.turnover || '2M€-10M€',
          latestAudit: a,
          allAudits: [a],
          scores: [a.riskScore ?? 50],
          latestScore: a.riskScore ?? 50,
          avgScore: a.riskScore ?? 50,
          minScore: a.riskScore ?? 50,
          maxScore: a.riskScore ?? 50,
          latestDate: a.analyzedAt || new Date().toISOString()
        };
      } else {
        map[domain].allAudits.push(a);
        const currentScore = a.riskScore ?? 50;
        map[domain].scores.push(currentScore);
        
        // If this audit is newer than the current recorded latest
        const isNewer = new Date(a.analyzedAt || 0).getTime() > new Date(map[domain].latestDate).getTime();
        if (isNewer) {
          map[domain].latestAudit = a;
          map[domain].latestScore = currentScore;
          map[domain].latestDate = a.analyzedAt || new Date().toISOString();
        }

        const scs = map[domain].scores;
        map[domain].minScore = Math.min(...scs);
        map[domain].maxScore = Math.max(...scs);
        map[domain].avgScore = Math.round(scs.reduce((sum, v) => sum + v, 0) / scs.length);
      }
    });

    return Object.values(map);
  }, [audits]);

  // Search matching results
  const searchResults = useMemo(() => {
    const rawQuery = normalizeStr(searchTerm);
    const tokens = rawQuery.split(/\s+/).filter(Boolean);

    return groupedCompanies.filter((company) => {
      // Filter tab
      if (activeFilter === 'critical' && company.latestScore >= 40) return false;
      if (activeFilter === 'recent') {
        const diffDays = (Date.now() - new Date(company.latestDate).getTime()) / (1000 * 3600 * 24);
        if (diffDays > 14) return false;
      }

      if (tokens.length === 0) return true;

      // Match all tokens against domain, companyName, sector, claims terms, etc.
      const searchTarget = normalizeStr(`
        ${company.companyName} 
        ${company.cleanDomain} 
        ${company.sector} 
        ${company.latestAudit.clientSummary?.riskVerdict || ''}
        ${company.allAudits.map(a => (a.detectedClaims || []).map(c => c.term).join(' ')).join(' ')}
      `);

      return tokens.every(token => {
        // Special score filter syntax like >70 or <40
        if (token.startsWith('>') || token.startsWith('<')) {
          const num = parseInt(token.substring(1), 10);
          if (!isNaN(num)) {
            return token.startsWith('>') ? company.latestScore >= num : company.latestScore <= num;
          }
        }
        return searchTarget.includes(token);
      });
    }).sort((a, b) => {
      // If search query is present, prioritize exact name/domain match
      if (rawQuery) {
        const aExact = normalizeStr(a.companyName).includes(rawQuery) || normalizeStr(a.cleanDomain).includes(rawQuery);
        const bExact = normalizeStr(b.companyName).includes(rawQuery) || normalizeStr(b.cleanDomain).includes(rawQuery);
        if (aExact && !bExact) return -1;
        if (!aExact && bExact) return 1;
      }
      return new Date(b.latestDate).getTime() - new Date(a.latestDate).getTime();
    });
  }, [groupedCompanies, searchTerm, activeFilter]);

  // Keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSelectedIndex((prev) => (prev < searchResults.length - 1 ? prev + 1 : prev));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSelectedIndex((prev) => (prev > 0 ? prev - 1 : 0));
    } else if (e.key === 'Enter' && searchResults[selectedIndex]) {
      e.preventDefault();
      handleSelectCompany(searchResults[selectedIndex]);
    } else if (e.key === 'Escape') {
      onClose();
    }
  };

  const handleSelectCompany = (company: typeof groupedCompanies[0]) => {
    onSelectAudit(company.latestAudit);
    if (onNavigateTab) onNavigateTab('prospection');
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-start justify-center p-4 sm:p-6 pt-16 sm:pt-20 animate-fadeIn">
      <div 
        className="bg-white rounded-3xl max-w-3xl w-full border border-slate-200 shadow-2xl overflow-hidden flex flex-col max-h-[85vh] text-slate-800"
        onKeyDown={handleKeyDown}
      >
        {/* Search Input Bar */}
        <div className="p-4 sm:p-5 border-b border-slate-200 flex items-center space-x-3 bg-white">
          <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
            <Search className="w-5 h-5" />
          </div>
          <input
            ref={inputRef}
            type="text"
            value={searchTerm}
            onChange={(e) => {
              setSearchTerm(e.target.value);
              setSelectedIndex(0);
            }}
            placeholder="Rechercher par entreprise, domaine, secteur, mot-clé, >70, <40..."
            className="w-full bg-transparent text-slate-900 placeholder-slate-400 text-sm sm:text-base font-semibold focus:outline-none"
          />
          {searchTerm && (
            <button
              onClick={() => setSearchTerm('')}
              className="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100"
            >
              <X className="w-4 h-4" />
            </button>
          )}
          <div className="hidden sm:flex items-center space-x-1 px-2 py-1 rounded-lg bg-slate-100 border border-slate-200 text-[10px] text-slate-500 font-mono">
            <span>ESC</span>
          </div>
        </div>

        {/* Quick Filters */}
        <div className="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
          <div className="flex items-center space-x-2">
            {[
              { id: 'all', label: `Toutes (${groupedCompanies.length})` },
              { id: 'companies', label: 'Multi-notes (Scans multiples)' },
              { id: 'critical', label: '🚨 Risque Critique (<40)' },
              { id: 'recent', label: '🕒 Scans récents' },
            ].map((f) => (
              <button
                key={f.id}
                type="button"
                onClick={() => {
                  setActiveFilter(f.id as any);
                  setSelectedIndex(0);
                }}
                className={`px-3 py-1 rounded-xl text-xs font-bold transition-all ${
                  activeFilter === f.id
                    ? 'bg-emerald-700 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>
          <span className="text-[11px] text-slate-500 font-mono hidden md:inline">
            {searchResults.length} résultat{searchResults.length > 1 ? 's' : ''}
          </span>
        </div>

        {/* Results List */}
        <div className="overflow-y-auto p-4 space-y-2.5 flex-1 custom-scrollbar">
          {searchResults.length === 0 ? (
            <div className="py-16 text-center space-y-3 text-slate-500">
              <Building2 className="w-10 h-10 mx-auto text-slate-400 opacity-60" />
              <p className="text-sm font-semibold text-slate-700">Aucune entreprise trouvée pour "{searchTerm}"</p>
              <p className="text-xs text-slate-500">
                Essayez avec un nom de domaine, un secteur d'activité ou lancez un nouvel audit.
              </p>
            </div>
          ) : (
            searchResults.map((company, index) => {
              const isSelected = selectedIndex === index;
              const hasMultipleScans = company.allAudits.length > 1;
              const isScoreCritical = company.latestScore < 40;
              const isScoreHigh = company.latestScore >= 70;

              return (
                <div
                  key={company.cleanDomain}
                  onClick={() => handleSelectCompany(company)}
                  onMouseEnter={() => setSelectedIndex(index)}
                  className={`p-4 rounded-2xl border transition-all cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4 ${
                    isSelected
                      ? 'bg-emerald-50/60 border-emerald-400 shadow-xs'
                      : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/80'
                  }`}
                >
                  <div className="space-y-1.5 flex-1 min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h4 className="text-sm font-black text-slate-900 truncate">
                        {company.companyName}
                      </h4>
                      <span className="text-xs text-slate-500 font-mono">
                        ({company.cleanDomain})
                      </span>
                      {hasMultipleScans && (
                        <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                          {company.allAudits.length} analyses
                        </span>
                      )}
                    </div>

                    <div className="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                      <span className="flex items-center space-x-1 text-slate-700 font-medium">
                        <Building2 className="w-3.5 h-3.5 text-slate-400" />
                        <span>{company.sector}</span>
                      </span>
                      <span>•</span>
                      <span className="text-slate-500 text-[11px]">
                        {company.salaries} sal.
                      </span>
                      <span>•</span>
                      <span className="text-slate-500 font-mono text-[11px]">
                        Dernier scan : {new Date(company.latestDate).toLocaleDateString('fr-FR')}
                      </span>
                    </div>

                    {/* Multi-notes history badges */}
                    {hasMultipleScans && (
                      <div className="flex flex-wrap items-center gap-1.5 pt-1">
                        <span className="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                          Historique des notes :
                        </span>
                        {company.scores.map((sc, i) => (
                          <span
                            key={i}
                            className={`px-1.5 py-0.5 rounded text-[10px] font-mono font-bold ${
                              sc >= 70 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : sc >= 40 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-800 border border-rose-200'
                            }`}
                          >
                            {sc}/100
                          </span>
                        ))}
                        <span className="text-[10px] text-slate-600 ml-1">
                          (Moyenne : <strong className="text-slate-900">{company.avgScore}/100</strong>)
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Score pill and action */}
                  <div className="flex items-center space-x-3 shrink-0 self-end sm:self-center">
                    <div className="text-right">
                      <div className={`text-lg font-black font-mono ${
                        isScoreCritical ? 'text-rose-600' : isScoreHigh ? 'text-emerald-700' : 'text-amber-600'
                      }`}>
                        {company.latestScore}/100
                      </div>
                      <span className="text-[9px] uppercase font-bold text-slate-500 block">
                        Dernière note
                      </span>
                    </div>

                    <button
                      type="button"
                      className="px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-colors flex items-center space-x-1"
                    >
                      <span>Ouvrir</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Footer info */}
        <div className="p-3 bg-slate-50 border-t border-slate-200 px-5 flex flex-wrap items-center justify-between text-[11px] text-slate-500">
          <div className="flex items-center space-x-3">
            <span>↑↓ pour naviguer</span>
            <span>•</span>
            <span>Entrée pour ouvrir</span>
            <span>•</span>
            <span>ESC pour fermer</span>
          </div>
          <span className="text-emerald-700 font-bold">
            ⚡ Moteur de recherche unifié GreenHumble
          </span>
        </div>
      </div>
    </div>
  );
};
