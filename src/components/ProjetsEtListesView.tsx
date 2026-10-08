import React, { useState, useEffect, useMemo } from 'react';
import { 
  ClipboardList, 
  Plus, 
  Trash2, 
  Building2, 
  ArrowRight, 
  Search, 
  CheckCircle2, 
  ExternalLink,
  ShieldCheck,
  Star,
  FileText,
  Globe
} from 'lucide-react';
import { ListeRecord, GreenwashingAudit } from '../types.js';
import { cleanCompanyName } from '../utils/companyUtils.js';

interface ProjetsEtListesViewProps {
  onOpenAuditForDomain: (domain: string) => void;
  onSelectAudit?: (audit: GreenwashingAudit) => void;
  audits?: GreenwashingAudit[];
}

export const ProjetsEtListesView: React.FC<ProjetsEtListesViewProps> = ({
  onOpenAuditForDomain,
  onSelectAudit,
  audits = [],
}) => {
  const [actionMsg, setActionMsg] = useState<string | null>(null);

  // --- LISTES SCANS STATE ---
  const [listes, setListes] = useState<ListeRecord[]>(() => {
    try {
      const saved = localStorage.getItem('climat_local_listes');
      return saved ? JSON.parse(saved) : [];
    } catch { return []; }
  });
  const [selectedListId, setSelectedListId] = useState<string | null>(null);
  const [isLoadingListes, setIsLoadingListes] = useState(false);
  const [newListName, setNewListName] = useState('');
  const [newListDesc, setNewListDesc] = useState('');
  const [showCreateListModal, setShowCreateListModal] = useState(false);
  const [itemFilter, setItemFilter] = useState('');

  // Fallback audits from localStorage and /api/audits
  const [savedAudits, setSavedAudits] = useState<GreenwashingAudit[]>(() => {
    try {
      const saved = localStorage.getItem('climat_local_greenwashing_audits');
      return saved ? JSON.parse(saved) : [];
    } catch { return []; }
  });

  useEffect(() => {
    fetch('/api/audits')
      .then((r) => (r.ok ? r.json() : []))
      .then((data) => {
        if (Array.isArray(data) && data.length > 0) {
          setSavedAudits((prev) => {
            const map = new Map<string, GreenwashingAudit>();
            prev.forEach((a) => map.set(a.id, a));
            data.forEach((a: GreenwashingAudit) => map.set(a.id, a));
            return Array.from(map.values());
          });
        }
      })
      .catch(() => {});
  }, []);

  // Merged audits collection for resolving real company names
  const allAudits = useMemo(() => {
    const map = new Map<string, GreenwashingAudit>();
    savedAudits.forEach((a) => map.set(a.id, a));
    audits.forEach((a) => map.set(a.id, a));
    return Array.from(map.values());
  }, [savedAudits, audits]);

  // Helper function to resolve real company name and metadata for any list item
  const resolveItemDetails = (item: string) => {
    if (!item) return { companyName: 'Entreprise Auditée', domain: '', audit: undefined as GreenwashingAudit | undefined };

    const cleanItem = item.trim();
    const cleanLower = cleanItem.toLowerCase();
    const domainKey = cleanLower
      .replace(/^https?:\/\//i, '')
      .replace(/^www\./i, '')
      .split('/')[0]
      .split('?')[0];

    // 1. Try finding by ID or scan_id
    let foundAudit = allAudits.find(
      (a) => a.id === cleanItem || (a.scan_id && a.scan_id === cleanItem)
    );

    // 2. Try finding by cleanUrl or url
    if (!foundAudit) {
      foundAudit = allAudits.find((a) => {
        const aDom = (a.cleanUrl || a.url || '')
          .toLowerCase()
          .replace(/^https?:\/\//i, '')
          .replace(/^www\./i, '')
          .split('/')[0]
          .split('?')[0];
        return aDom && (aDom === domainKey || aDom === cleanLower);
      });
    }

    // 3. Try finding by companyName
    if (!foundAudit) {
      foundAudit = allAudits.find(
        (a) => a.companyName && a.companyName.toLowerCase().trim() === cleanLower
      );
    }

    // Determine the real company name
    let realName = '';
    if (foundAudit && foundAudit.companyName && foundAudit.companyName.trim()) {
      realName = foundAudit.companyName.trim();
    } else if (foundAudit && (foundAudit.cleanUrl || foundAudit.url)) {
      realName = cleanCompanyName(undefined, foundAudit.cleanUrl || foundAudit.url);
    } else {
      realName = cleanCompanyName(undefined, item);
    }

    // If realName still looks like a raw hash/ID (e.g. scan_1791... or gw_1791...), fall back to domain
    if (realName.startsWith('scan_') || realName.startsWith('gw_') || realName.startsWith('list_')) {
      if (foundAudit && (foundAudit.cleanUrl || foundAudit.url)) {
        realName = cleanCompanyName(undefined, foundAudit.cleanUrl || foundAudit.url);
      } else {
        realName = 'Entreprise Auditée';
      }
    }

    // Determine display domain
    const displayDomain =
      foundAudit?.cleanUrl || foundAudit?.url
        ? (foundAudit.cleanUrl || foundAudit.url)
            .replace(/^https?:\/\//i, '')
            .replace(/^www\./i, '')
            .split('/')[0]
        : domainKey.includes('.')
        ? domainKey
        : '';

    return {
      companyName: realName,
      domain: displayDomain,
      audit: foundAudit,
    };
  };

  useEffect(() => {
    fetchListes();

    const handleDataChanged = () => {
      fetchListes();
    };

    window.addEventListener('greenhumble_data_changed', handleDataChanged);
    return () => {
      window.removeEventListener('greenhumble_data_changed', handleDataChanged);
    };
  }, []);

  const fetchListes = async () => {
    setIsLoadingListes(true);
    try {
      const res = await fetch('/api/listes');
      if (res.ok) {
        const data = await res.json();
        const loadedListes: ListeRecord[] = data.listes || [];
        setListes(loadedListes);
        localStorage.setItem('climat_local_listes', JSON.stringify(loadedListes));
        if (loadedListes.length > 0) {
          if (!selectedListId || !loadedListes.some(l => l.id === selectedListId)) {
            setSelectedListId(loadedListes[0].id);
          }
        } else {
          setSelectedListId(null);
        }
      }
    } catch (_) {}
    finally {
      setIsLoadingListes(false);
    }
  };

  // Listes Scans Handlers
  const handleCreateList = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newListName.trim()) return;

    try {
      const res = await fetch('/api/listes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nom: newListName.trim(),
          description: newListDesc.trim()
        })
      });
      if (res.ok) {
        const data = await res.json();
        setListes(data.listes || []);
        setActionMsg(`Liste "${newListName}" créée avec succès !`);
        setNewListName('');
        setNewListDesc('');
        setShowCreateListModal(false);
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {
      setActionMsg('Erreur lors de la création de la liste.');
      setTimeout(() => setActionMsg(null), 3000);
    }
  };

  const handleRemoveItemFromList = async (listId: string, item: string) => {
    try {
      const res = await fetch('/api/listes/toggle-item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listId, itemId: item })
      });
      if (res.ok) {
        fetchListes();
      }
    } catch (_) {}
  };

  const handleDeleteList = async (listId: string, listName: string, e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    const target = listes.find(l => l.id === listId);
    if (target?.is_default || listId === 'liste_public_widget') {
      alert('La liste par défaut (Scans Publics & Widget) est protégée et ne peut pas être supprimée.');
      return;
    }

    const confirmed = window.confirm(
      `Êtes-vous sûr de vouloir supprimer la liste "${listName}" ?\n\n✓ Note : Les entreprises et audits scannés restent conservés intacts dans votre base de données.`
    );
    if (!confirmed) return;

    try {
      const res = await fetch(`/api/listes/${listId}`, { method: 'DELETE' });
      if (res.ok) {
        const remaining = listes.filter(l => l.id !== listId);
        setListes(remaining);
        if (selectedListId === listId) {
          setSelectedListId(remaining.length > 0 ? remaining[0].id : null);
        }
        setActionMsg(`Liste "${listName}" supprimée (scans conservés).`);
        setTimeout(() => setActionMsg(null), 4000);
      } else {
        setActionMsg('Erreur lors de la suppression de la liste.');
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {
      setActionMsg('Erreur lors de la suppression de la liste.');
      setTimeout(() => setActionMsg(null), 3000);
    }
  };

  const activeList = listes.find((l) => l.id === selectedListId) || listes[0];

  const filteredItems = (activeList?.items || []).filter((item) => {
    if (!itemFilter.trim()) return true;
    const filterLower = itemFilter.toLowerCase().trim();
    const details = resolveItemDetails(item);
    return (
      item.toLowerCase().includes(filterLower) ||
      details.companyName.toLowerCase().includes(filterLower) ||
      details.domain.toLowerCase().includes(filterLower) ||
      (details.audit?.sector && details.audit.sector.toLowerCase().includes(filterLower))
    );
  });

  return (
    <div className="space-y-6 animate-fadeIn max-w-[1750px] mx-auto text-slate-800">
      {/* 1. Header Bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
            <ClipboardList className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-xl font-black text-slate-900 font-serif tracking-tight">
              5. Listes de Scans & Campagnes
            </h2>
            <p className="text-xs text-slate-500">
              Organisation de vos cibles par liste thématique, avec la liste par défaut "Scans Publics & Widget"
            </p>
          </div>
        </div>

        <button
          onClick={() => setShowCreateListModal(true)}
          className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center space-x-2 shadow-sm transition-all cursor-pointer"
        >
          <Plus className="w-4 h-4" />
          <span>Nouvelle Liste de Scans</span>
        </button>
      </div>

      {actionMsg && (
        <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2 animate-fadeIn">
          <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          <span>{actionMsg}</span>
        </div>
      )}

      {/* 2. Main List Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Listes Scans selector */}
        <div className="bg-white p-5 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
          <h3 className="text-xs font-black text-slate-500 uppercase tracking-wider px-2 flex items-center justify-between">
            <span>Listes Disponibles ({listes.length})</span>
          </h3>

          <div className="space-y-2">
            {listes.map((l) => {
              const isDefault = l.is_default || l.id === 'liste_public_widget';
              const isSelected = selectedListId === l.id;

              return (
                <div
                  key={l.id}
                  onClick={() => setSelectedListId(l.id)}
                  className={`group w-full p-4 rounded-2xl text-left transition-all cursor-pointer flex items-center justify-between border ${
                    isSelected
                      ? 'bg-blue-50/70 border-blue-400 shadow-xs'
                      : 'bg-slate-50 border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <div className="flex-1 min-w-0 pr-2">
                    <div className="flex items-center space-x-1.5">
                      {isDefault && (
                        <span className="p-1 rounded bg-blue-100 text-blue-700" title="Liste par défaut">
                          <Star className="w-3 h-3 fill-current" />
                        </span>
                      )}
                      <span className="font-bold text-slate-900 text-sm truncate">{l.nom}</span>
                    </div>
                    {l.description && (
                      <p className="text-2xs text-slate-500 line-clamp-1 mt-0.5">{l.description}</p>
                    )}
                  </div>

                  <div className="flex items-center space-x-2 shrink-0">
                    <span className="font-mono text-2xs font-bold px-2 py-1 rounded-full bg-slate-200 text-slate-700">
                      {l.items?.length || 0}
                    </span>
                    {!isDefault && (
                      <button
                        type="button"
                        onClick={(e) => handleDeleteList(l.id, l.nom, e)}
                        className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                        title="Supprimer cette liste"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                </div>
              );
            })}

            {listes.length === 0 && (
              <div className="p-8 text-center text-xs text-slate-500">
                Chargement des listes de scans...
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Selected List Content */}
        <div className="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 space-y-6 shadow-sm">
          {activeList ? (
            <div className="space-y-6">
              <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-lg font-black text-slate-900">{activeList.nom}</h3>
                    {(activeList.is_default || activeList.id === 'liste_public_widget') && (
                      <span className="text-[10px] font-black px-2.5 py-0.5 bg-blue-50 text-blue-700 rounded-full border border-blue-200 uppercase tracking-wider">
                        ★ Liste par défaut
                      </span>
                    )}
                    <span className="text-xs font-bold px-3 py-0.5 bg-slate-100 text-slate-700 rounded-xl border border-slate-200">
                      {activeList.items?.length || 0} scan(s)
                    </span>
                  </div>
                  {activeList.description && (
                    <p className="text-xs text-slate-500 mt-1">{activeList.description}</p>
                  )}
                </div>

                {!activeList.is_default && activeList.id !== 'liste_public_widget' && (
                  <button
                    type="button"
                    onClick={() => handleDeleteList(activeList.id, activeList.nom)}
                    className="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-bold text-xs flex items-center space-x-1.5 transition-all cursor-pointer shadow-xs"
                    title="Supprimer la liste (sans supprimer les entreprises scannées)"
                  >
                    <Trash2 className="w-3.5 h-3.5 text-rose-600" />
                    <span>Supprimer la liste</span>
                  </button>
                )}
              </div>

              {/* Filter within active list */}
              {activeList.items && activeList.items.length > 0 && (
                <div className="relative">
                  <Search className="w-4 h-4 text-slate-400 absolute left-4 top-3" />
                  <input
                    type="text"
                    placeholder="Filtrer les scans de cette liste..."
                    value={itemFilter}
                    onChange={(e) => setItemFilter(e.target.value)}
                    className="w-full pl-11 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                  />
                </div>
              )}

              {/* Items in this list */}
              <div className="space-y-3">
                {(!activeList.items || activeList.items.length === 0) ? (
                  <div className="p-12 text-center text-slate-500 space-y-2">
                    <ClipboardList className="w-8 h-8 mx-auto text-slate-400" />
                    <p className="text-sm font-bold text-slate-700">Cette liste de scans est vide</p>
                    <p className="text-xs text-slate-500">
                      {(activeList.is_default || activeList.id === 'liste_public_widget')
                        ? 'Les scans générés depuis l’accueil public et le widget web apparaîtront automatiquement ici.'
                        : 'Ajoutez des scans à cette liste depuis le formulaire d’audit ou la vue des scans.'}
                    </p>
                  </div>
                ) : (
                  filteredItems.map((item, idx) => {
                    const details = resolveItemDetails(item);
                    const isScoreCritical =
                      details.audit?.riskScore !== null &&
                      details.audit?.riskScore !== undefined &&
                      details.audit.riskScore < 40;
                    const isScoreHigh =
                      details.audit?.riskScore !== null &&
                      details.audit?.riskScore !== undefined &&
                      details.audit.riskScore >= 70;

                    return (
                      <div 
                        key={idx}
                        className="bg-slate-50 p-4 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-slate-300 transition-colors shadow-2xs"
                      >
                        <div className="flex items-center space-x-3 min-w-0 flex-1">
                          <div className="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-black text-xs shrink-0">
                            {details.companyName ? details.companyName.charAt(0).toUpperCase() : idx + 1}
                          </div>
                          <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                              <h4 className="font-bold text-slate-900 text-sm truncate">
                                {details.companyName}
                              </h4>
                              {details.audit?.riskScore !== undefined && details.audit?.riskScore !== null && (
                                <span className={`px-2 py-0.5 rounded-md font-mono font-bold text-[10px] ${
                                  isScoreCritical 
                                    ? 'bg-rose-50 text-rose-700 border border-rose-200' 
                                    : isScoreHigh 
                                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' 
                                    : 'bg-amber-50 text-amber-800 border border-amber-200'
                                }`}>
                                  {details.audit.riskScore}/100
                                </span>
                              )}
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-2xs text-slate-500 mt-0.5">
                              {details.domain && (
                                <span className="flex items-center space-x-1 font-mono text-slate-600">
                                  <Globe className="w-3 h-3 text-slate-400" />
                                  <span>{details.domain}</span>
                                </span>
                              )}
                              {details.audit?.sector && (
                                <>
                                  <span>•</span>
                                  <span className="text-slate-600">{details.audit.sector}</span>
                                </>
                              )}
                              {details.audit?.analyzedAt && (
                                <>
                                  <span>•</span>
                                  <span className="text-slate-400">
                                    Scanné le {new Date(details.audit.analyzedAt).toLocaleDateString('fr-FR')}
                                  </span>
                                </>
                              )}
                            </div>
                          </div>
                        </div>

                        <div className="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                          <button
                            type="button"
                            onClick={() => {
                              if (onSelectAudit && details.audit) {
                                onSelectAudit(details.audit);
                              } else {
                                onOpenAuditForDomain(details.domain || item);
                              }
                            }}
                            className="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all cursor-pointer flex items-center space-x-1.5 shadow-xs"
                            title="Consulter la fiche complète de l'entreprise"
                          >
                            <FileText className="w-3.5 h-3.5" />
                            <span>Voir la fiche</span>
                          </button>
                          <button
                            type="button"
                            onClick={() => handleRemoveItemFromList(activeList.id, item)}
                            className="p-2 text-slate-400 hover:text-rose-600 rounded-xl hover:bg-rose-50 transition-colors cursor-pointer"
                            title="Retirer de cette liste"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          ) : (
            <div className="p-12 text-center text-slate-500">
              Sélectionnez une liste de scans à gauche pour afficher son contenu.
            </div>
          )}
        </div>
      </div>

      {/* CREATE LISTE SCANS MODAL */}
      {showCreateListModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white w-full max-w-md rounded-3xl border border-slate-200 p-6 space-y-5 shadow-2xl text-slate-800">
            <h3 className="text-base font-black text-slate-900 flex items-center space-x-2">
              <ClipboardList className="w-5 h-5 text-blue-600" />
              <span>Créer une Liste de Scans</span>
            </h3>

            <form onSubmit={handleCreateList} className="space-y-4">
              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Nom de la liste *</label>
                <input
                  type="text"
                  placeholder="ex: Campagne BTP Auvergne, Prospects CAC40..."
                  value={newListName}
                  onChange={(e) => setNewListName(e.target.value)}
                  required
                  autoFocus
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Description</label>
                <textarea
                  rows={3}
                  placeholder="Objectifs de prospection, critères ciblés..."
                  value={newListDesc}
                  onChange={(e) => setNewListDesc(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div className="flex items-center space-x-3 pt-2">
                <button
                  type="submit"
                  className="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition-all cursor-pointer shadow-sm"
                >
                  Créer la liste de scans
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreateListModal(false)}
                  className="py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-semibold cursor-pointer"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
