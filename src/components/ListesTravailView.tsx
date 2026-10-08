import React, { useState, useEffect } from 'react';
import { 
  ClipboardList, 
  Plus, 
  Trash2, 
  Search, 
  CheckCircle2, 
  ExternalLink, 
  Globe, 
  ArrowRight,
  BookmarkPlus,
  FileText
} from 'lucide-react';
import { ListeRecord } from '../types.js';
import { cleanCompanyName } from '../utils/companyUtils.js';

interface ListesTravailViewProps {
  onOpenAuditForDomain: (domain: string) => void;
}

export const ListesTravailView: React.FC<ListesTravailViewProps> = ({
  onOpenAuditForDomain
}) => {
  const [listes, setListes] = useState<ListeRecord[]>([]);
  const [selectedListId, setSelectedListId] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [newNom, setNewNom] = useState('');
  const [newDesc, setNewDesc] = useState('');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [actionMsg, setActionMsg] = useState<string | null>(null);

  useEffect(() => {
    fetchListes();
  }, []);

  const fetchListes = async () => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/listes');
      if (res.ok) {
        const data = await res.json();
        const loadedListes: ListeRecord[] = data.listes || [];
        setListes(loadedListes);
        if (loadedListes.length > 0 && !selectedListId) {
          setSelectedListId(loadedListes[0].id);
        }
      }
    } catch (_) {}
    finally {
      setIsLoading(false);
    }
  };

  const handleCreateList = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newNom.trim()) return;

    try {
      const res = await fetch('/api/listes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nom: newNom.trim(),
          description: newDesc.trim()
        })
      });
      if (res.ok) {
        const data = await res.json();
        setListes(data.listes || []);
        setActionMsg(`Liste "${newNom}" créée avec succès !`);
        setNewNom('');
        setNewDesc('');
        setShowCreateModal(false);
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {
      setActionMsg('Erreur lors de la création de la liste.');
      setTimeout(() => setActionMsg(null), 3000);
    }
  };

  const handleRemoveItem = async (listId: string, item: string) => {
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
    const confirmed = window.confirm(
      `Êtes-vous sûr de vouloir supprimer la liste "${listName}" ?\n\n✓ Note importante : Les entreprises et audits scannés seront conservés intacts dans votre base de données, seule cette liste de regroupement sera supprimée.`
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
        setActionMsg(`Liste "${listName}" supprimée (les entreprises scannées sont conservées).`);
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

  return (
    <div className="space-y-6 text-slate-800">
      {/* Header bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
            <ClipboardList className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-xl font-black text-slate-900 font-serif tracking-tight">7. Liste scans</h2>
            <p className="text-xs text-slate-500">Organisation des cibles et listes de scans pour le suivi prospection</p>
          </div>
        </div>

        <button
          onClick={() => setShowCreateModal(true)}
          className="px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center space-x-2 hover:bg-blue-700 transition-all shadow-sm cursor-pointer"
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

      {/* Two column layout: Left list selector, Right list content */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Lists */}
        <div className="bg-white p-5 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
          <h3 className="text-xs font-black text-slate-500 uppercase tracking-wider px-2">
            Listes Disponibles ({listes.length})
          </h3>

          <div className="space-y-2">
            {listes.map((l) => (
              <div
                key={l.id}
                onClick={() => setSelectedListId(l.id)}
                className={`group w-full p-4 rounded-2xl text-left transition-all cursor-pointer flex items-center justify-between border ${
                  selectedListId === l.id
                    ? 'bg-blue-50/70 border-blue-400 shadow-xs'
                    : 'bg-slate-50 border-slate-200 hover:border-slate-300'
                }`}
              >
                <div className="flex-1 min-w-0 pr-2">
                  <div className="font-bold text-slate-900 text-sm truncate">{l.nom}</div>
                  {l.description && <p className="text-2xs text-slate-500 line-clamp-1 mt-0.5">{l.description}</p>}
                </div>
                <div className="flex items-center space-x-2 shrink-0">
                  <span className="font-mono text-2xs font-bold px-2 py-1 rounded-full bg-slate-200 text-slate-700">
                    {l.items?.length || 0}
                  </span>
                  <button
                    type="button"
                    onClick={(e) => handleDeleteList(l.id, l.nom, e)}
                    className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                    title="Supprimer cette liste (sans supprimer les entreprises scannées)"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Right Column: Items in selected list */}
        <div className="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 space-y-5 shadow-sm">
          {activeList ? (
            <>
              <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-lg font-black text-slate-900">{activeList.nom}</h3>
                    <span className="text-xs font-mono font-bold text-blue-700 px-3 py-0.5 rounded-xl bg-blue-50 border border-blue-200">
                      {activeList.items?.length || 0} élément(s)
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-1">{activeList.description || 'Aucune description fournie.'}</p>
                </div>

                <div className="flex items-center space-x-2">
                  <button
                    type="button"
                    onClick={() => handleDeleteList(activeList.id, activeList.nom)}
                    className="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-bold text-xs flex items-center space-x-1.5 transition-all cursor-pointer shadow-xs"
                    title="Supprimer la liste (sans supprimer les entreprises scannées)"
                  >
                    <Trash2 className="w-3.5 h-3.5 text-rose-600" />
                    <span>Supprimer la liste</span>
                  </button>
                </div>
              </div>

              {(!activeList.items || activeList.items.length === 0) ? (
                <div className="py-16 text-center text-slate-500 space-y-2">
                  <BookmarkPlus className="w-8 h-8 mx-auto text-slate-400" />
                  <p className="text-xs">Cette liste ne contient aucun élément pour le moment.</p>
                  <p className="text-2xs text-slate-500">
                    Cochez cette liste dans le Scanner lors d'un audit pour y rattacher automatiquement les domaines.
                  </p>
                </div>
              ) : (
                <div className="space-y-3">
                  {activeList.items.map((item, idx) => (
                    <div
                      key={idx}
                      className="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between hover:border-slate-300 transition-all"
                    >
                      <div className="flex items-center space-x-3">
                        <div className="w-8 h-8 rounded-xl bg-white flex items-center justify-center text-blue-600 border border-slate-200 shadow-xs">
                          <Globe className="w-4 h-4" />
                        </div>
                        <div>
                          <span className="font-bold text-sm text-slate-900">{item}</span>
                        </div>
                      </div>

                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => onOpenAuditForDomain(item)}
                          className="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors cursor-pointer flex items-center space-x-1 shadow-xs"
                        >
                          <span>Scanner</span>
                          <ArrowRight className="w-3.5 h-3.5" />
                        </button>
                        <button
                          onClick={() => handleRemoveItem(activeList.id, item)}
                          className="p-1.5 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer"
                          title="Retirer de la liste"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </>
          ) : (
            <div className="py-16 text-center text-slate-500 text-xs">
              Sélectionnez une liste à gauche pour afficher son contenu.
            </div>
          )}
        </div>
      </div>

      {/* Create Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-xs">
          <div className="bg-white w-full max-w-md rounded-3xl border border-slate-200 p-6 space-y-5 shadow-2xl text-slate-800">
            <h3 className="text-base font-black text-slate-900 flex items-center space-x-2">
              <ClipboardList className="w-5 h-5 text-blue-600" />
              <span>Créer une Liste de Scans</span>
            </h3>

            <form onSubmit={handleCreateList} className="space-y-4">
              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Nom de la liste*</label>
                <input
                  type="text"
                  placeholder="ex: Prospects CAC40 Bâtiment"
                  value={newNom}
                  onChange={(e) => setNewNom(e.target.value)}
                  required
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Description</label>
                <textarea
                  rows={3}
                  placeholder="Objectifs de la campagne, critères de prospection..."
                  value={newDesc}
                  onChange={(e) => setNewDesc(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div className="flex items-center space-x-3 pt-2">
                <button
                  type="submit"
                  className="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition-all cursor-pointer shadow-sm"
                >
                  Créer la liste
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold text-xs cursor-pointer"
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
