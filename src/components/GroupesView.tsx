import React, { useState, useEffect } from 'react';
import { 
  Sliders, 
  Plus, 
  Trash2, 
  Edit3, 
  Globe, 
  CheckCircle2, 
  Play, 
  Layers,
  Calendar,
  AlertCircle
} from 'lucide-react';
import { ScannerGroupRecord } from '../types.js';

interface GroupesViewProps {
  onOpenAuditForDomain: (domain: string) => void;
}

export const GroupesView: React.FC<GroupesViewProps> = ({
  onOpenAuditForDomain
}) => {
  const [groups, setGroups] = useState<ScannerGroupRecord[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [editingGroup, setEditingGroup] = useState<ScannerGroupRecord | null>(null);
  const [nom, setNom] = useState('');
  const [desc, setDesc] = useState('');
  const [actionMsg, setActionMsg] = useState<string | null>(null);

  useEffect(() => {
    fetchGroups();
  }, []);

  const fetchGroups = async () => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/admin/scanner-groups');
      if (res.ok) {
        const data = await res.json();
        setGroups(data.groups || []);
      }
    } catch (_) {}
    finally {
      setIsLoading(false);
    }
  };

  const handleSaveGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!nom.trim()) return;

    try {
      const payload = {
        id: editingGroup?.id,
        nom: nom.trim(),
        description: desc.trim()
      };
      const res = await fetch('/api/admin/scanner-groups', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (res.ok) {
        setActionMsg(editingGroup ? 'Groupe mis à jour !' : 'Nouveau groupe créé !');
        setNom('');
        setDesc('');
        setEditingGroup(null);
        setShowModal(false);
        fetchGroups();
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {
      setActionMsg('Erreur lors de l’enregistrement du groupe.');
      setTimeout(() => setActionMsg(null), 3000);
    }
  };

  const handleDeleteGroup = async (id: string, groupNom: string) => {
    if (!confirm(`Supprimer le groupe "${groupNom}" ?`)) return;
    try {
      const res = await fetch(`/api/admin/scanner-groups/${id}`, { method: 'DELETE' });
      if (res.ok) {
        setActionMsg(`Groupe "${groupNom}" supprimé.`);
        fetchGroups();
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {}
  };

  return (
    <div className="space-y-6 text-slate-800">
      {/* Header bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
            <Sliders className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-xl font-black text-slate-900 font-serif tracking-tight">5. Groupes de Scanners</h2>
            <p className="text-xs text-slate-500">Configuration et regroupement des scanners par profil d'analyse</p>
          </div>
        </div>

        <button
          onClick={() => {
            setEditingGroup(null);
            setNom('');
            setDesc('');
            setShowModal(true);
          }}
          className="px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center space-x-2 hover:bg-blue-700 transition-all shadow-sm cursor-pointer"
        >
          <Plus className="w-4 h-4" />
          <span>Nouveau Groupe</span>
        </button>
      </div>

      {actionMsg && (
        <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2 animate-fadeIn">
          <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          <span>{actionMsg}</span>
        </div>
      )}

      {/* Grid of groups */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {groups.length === 0 ? (
          <div className="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-200 space-y-3 shadow-sm">
            <Sliders className="w-12 h-12 text-slate-400 mx-auto" />
            <h3 className="text-sm font-bold text-slate-700">Aucun groupe de scanner configuré</h3>
            <p className="text-xs text-slate-500">
              Créez des groupes de scanners pour paramétrer vos cibles d'analyse par lot.
            </p>
          </div>
        ) : (
          groups.map((grp) => (
            <div
              key={grp.id}
              className="bg-white p-6 rounded-3xl border border-slate-200 hover:border-slate-300 transition-all flex flex-col justify-between space-y-5 shadow-sm"
            >
              <div className="space-y-3">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-2">
                    <div className="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                      <Layers className="w-4 h-4" />
                    </div>
                    <div>
                      <h3 className="text-base font-black text-slate-900 tracking-tight">{grp.nom}</h3>
                      {grp.is_default && (
                        <span className="text-[9px] px-2 py-0.5 rounded font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                          Groupe Système
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="flex items-center space-x-1">
                    <button
                      onClick={() => {
                        setEditingGroup(grp);
                        setNom(grp.nom);
                        setDesc(grp.description || '');
                        setShowModal(true);
                      }}
                      className="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors cursor-pointer"
                    >
                      <Edit3 className="w-4 h-4" />
                    </button>
                    {!grp.is_default && (
                      <button
                        onClick={() => handleDeleteGroup(grp.id, grp.nom)}
                        className="p-1.5 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                </div>

                <p className="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                  {grp.description || 'Aucune description spécifique pour ce groupe de scanners.'}
                </p>
              </div>

              {grp.date_creation && (
                <div className="text-2xs text-slate-400 flex items-center space-x-1 pt-2 border-t border-slate-100">
                  <Calendar className="w-3 h-3" />
                  <span>Configuré le {new Date(grp.date_creation).toLocaleDateString('fr-FR')}</span>
                </div>
              )}
            </div>
          ))
        )}
      </div>

      {/* Modal create / edit */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-xs">
          <div className="bg-white w-full max-w-md rounded-3xl border border-slate-200 p-6 space-y-5 shadow-2xl text-slate-800">
            <h3 className="text-base font-black text-slate-900 flex items-center space-x-2">
              <Sliders className="w-5 h-5 text-blue-600" />
              <span>{editingGroup ? 'Modifier le Groupe de Scanners' : 'Nouveau Groupe de Scanners'}</span>
            </h3>

            <form onSubmit={handleSaveGroup} className="space-y-4">
              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Nom du groupe*</label>
                <input
                  type="text"
                  placeholder="ex: Scanners PME Industrie Auvergne"
                  value={nom}
                  onChange={(e) => setNom(e.target.value)}
                  required
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Description / Consignes du groupe</label>
                <textarea
                  rows={3}
                  placeholder="Critères d'analyse, cibles et notes opérationnelles..."
                  value={desc}
                  onChange={(e) => setDesc(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white"
                />
              </div>

              <div className="flex items-center space-x-3 pt-2">
                <button
                  type="submit"
                  className="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition-all cursor-pointer shadow-sm"
                >
                  {editingGroup ? 'Mettre à jour' : 'Créer le groupe'}
                </button>
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
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
