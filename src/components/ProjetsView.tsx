import React, { useState, useEffect } from 'react';
import { 
  FolderGit2, 
  Plus, 
  Trash2, 
  Building2, 
  Globe, 
  Calendar, 
  FileText, 
  ArrowRight, 
  Search, 
  CheckCircle2, 
  Users, 
  TrendingUp,
  ScanLine,
  ExternalLink
} from 'lucide-react';
import { ProjetRecord, GreenwashingAudit } from '../types.js';
import { cleanCompanyName } from '../utils/companyUtils.js';

interface ProjetsViewProps {
  onOpenAuditForDomain: (domain: string) => void;
  onSelectAudit?: (audit: GreenwashingAudit) => void;
}

export const ProjetsView: React.FC<ProjetsViewProps> = ({
  onOpenAuditForDomain
}) => {
  const [projets, setProjets] = useState<ProjetRecord[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newDomaine, setNewDomaine] = useState('');
  const [newSecteur, setNewSecteur] = useState('Industrie & Services');
  const [newCA, setNewCA] = useState('2M€-10M€');
  const [newSalaries, setNewSalaries] = useState('11-50');
  const [actionMsg, setActionMsg] = useState<string | null>(null);

  useEffect(() => {
    fetchProjets();
  }, []);

  const fetchProjets = async () => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/projets/list');
      if (res.ok) {
        const data = await res.json();
        setProjets(data.projets || []);
      }
    } catch (_) {}
    finally {
      setIsLoading(false);
    }
  };

  const handleCreateProjet = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newDomaine.trim()) return;

    try {
      const res = await fetch('/api/projets/add-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          domain: newDomaine.trim(),
          entreprise_secteur: newSecteur,
          entreprise_ca: newCA,
          entreprise_salaries: newSalaries
        })
      });
      if (res.ok) {
        setActionMsg(`Projet pour "${newDomaine}" créé avec succès !`);
        setNewDomaine('');
        setShowCreateModal(false);
        fetchProjets();
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {
      setActionMsg('Erreur lors de la création du projet.');
      setTimeout(() => setActionMsg(null), 3000);
    }
  };

  const handleDeleteProjet = async (id: string, domain: string) => {
    if (!confirm(`Supprimer le projet "${domain}" ?`)) return;
    try {
      const res = await fetch(`/api/projets/${id}`, { method: 'DELETE' });
      if (res.ok) {
        setActionMsg(`Projet "${domain}" supprimé.`);
        fetchProjets();
        setTimeout(() => setActionMsg(null), 3000);
      }
    } catch (_) {}
  };

  const filteredProjets = projets.filter((p) =>
    p.domaine.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (p.entreprise_secteur && p.entreprise_secteur.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  return (
    <div className="space-y-6 text-slate-800">
      {/* Header bar */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
            <FolderGit2 className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-xl font-black text-slate-900 font-serif tracking-tight">3. Gestion des Projets</h2>
            <p className="text-xs text-slate-500">Regroupement des scans par domaine et suivi de mise en conformité</p>
          </div>
        </div>

        <div className="flex items-center space-x-3">
          <button
            onClick={() => setShowCreateModal(true)}
            className="px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center space-x-2 hover:bg-blue-700 transition-all shadow-xs cursor-pointer"
          >
            <Plus className="w-4 h-4" />
            <span>Nouveau Projet</span>
          </button>
        </div>
      </div>

      {actionMsg && (
        <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2 animate-fadeIn">
          <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          <span>{actionMsg}</span>
        </div>
      )}

      {/* Search bar */}
      <div className="relative">
        <Search className="w-4 h-4 text-slate-400 absolute left-4 top-3.5" />
        <input
          type="text"
          placeholder="Rechercher un projet par domaine ou secteur d'activité..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full pl-11 pr-4 py-3 rounded-2xl bg-white border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 shadow-xs"
        />
      </div>

      {/* Projects Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredProjets.length === 0 ? (
          <div className="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <FolderGit2 className="w-12 h-12 text-slate-300 mx-auto" />
            <h3 className="text-sm font-bold text-slate-700">Aucun projet trouvé</h3>
            <p className="text-xs text-slate-500">
              Créez un nouveau projet ou lancez un scan pour auto-générer un projet par domaine.
            </p>
          </div>
        ) : (
          filteredProjets.map((proj) => (
            <div
              key={proj.id}
              className="bg-white p-6 rounded-3xl border border-slate-200 hover:border-slate-300 transition-all flex flex-col justify-between space-y-5 shadow-sm hover:shadow group"
            >
              <div className="space-y-3">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-2 min-w-0">
                    <div className="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100 shrink-0">
                      <Building2 className="w-4 h-4" />
                    </div>
                    <div className="min-w-0">
                      <h3 className="text-base font-black text-slate-900 tracking-tight group-hover:text-blue-600 transition-colors truncate">
                        {proj.entreprise_nom || cleanCompanyName(undefined, proj.domaine)}
                      </h3>
                      <div className="flex flex-wrap items-center gap-1.5 text-2xs text-slate-500 mt-0.5">
                        <Globe className="w-3 h-3 text-blue-600" />
                        <span className="font-mono truncate">{proj.domaine.replace(/^https?:\/\//i, '').replace(/^www\./i, '')}</span>
                        <span>•</span>
                        <Calendar className="w-3 h-3 text-slate-400" />
                        <span>Créé le {new Date(proj.date_creation).toLocaleDateString('fr-FR')}</span>
                      </div>
                    </div>
                  </div>
                  <button
                    onClick={() => handleDeleteProjet(proj.id, proj.domaine)}
                    title="Supprimer le projet"
                    className="p-1.5 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer shrink-0"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                {/* Auto-deduced profile pill with Pappers link */}
                <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                  <div className="flex items-center justify-between text-slate-700">
                    <div className="flex items-center space-x-2">
                      <Building2 className="w-3.5 h-3.5 text-blue-600" />
                      <span className="font-semibold text-[11px]">{proj.entreprise_secteur || 'Secteur non spécifié'}</span>
                    </div>
                    <a
                      href={`https://www.pappers.fr/recherche?q=${encodeURIComponent(proj.entreprise_nom || proj.domaine)}`}
                      target="_blank"
                      rel="noreferrer"
                      className="text-[10px] font-bold text-slate-500 hover:text-blue-600 flex items-center space-x-1"
                      title="Consulter les informations d'entreprise sur Pappers / Societe.com"
                    >
                      <span>Infos Pappers</span>
                      <ExternalLink className="w-2.5 h-2.5" />
                    </a>
                  </div>
                  <div className="flex items-center space-x-4 text-2xs text-slate-500">
                    <span className="flex items-center space-x-1">
                      <Users className="w-3 h-3 text-slate-400" />
                      <span>{proj.entreprise_salaries || '11-50'} salariés</span>
                    </span>
                    <span className="flex items-center space-x-1">
                      <TrendingUp className="w-3 h-3 text-slate-400" />
                      <span>{proj.entreprise_ca || '2M€-10M€'}</span>
                    </span>
                  </div>
                </div>

                {/* Scans Count */}
                <div className="flex items-center justify-between text-xs pt-1">
                  <span className="text-slate-500">Scans rattachés :</span>
                  <span className="font-mono font-bold text-slate-800 px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200">
                    {proj.scans?.length || 0} scan(s)
                  </span>
                </div>
              </div>

              {/* Action Button */}
              <button
                onClick={() => onOpenAuditForDomain(proj.domaine)}
                className="w-full py-2.5 rounded-xl bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white font-bold text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer border border-blue-200"
              >
                <ScanLine className="w-3.5 h-3.5" />
                <span>Lancer un audit sur ce projet</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </button>
            </div>
          ))
        )}
      </div>

      {/* Create Project Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
          <div className="bg-white w-full max-w-md rounded-3xl border border-slate-200 p-6 space-y-5 shadow-2xl text-slate-800">
            <h3 className="text-base font-black text-slate-900 flex items-center space-x-2">
              <FolderGit2 className="w-5 h-5 text-blue-600" />
              <span>Nouveau Projet</span>
            </h3>

            <form onSubmit={handleCreateProjet} className="space-y-4">
              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Domaine / URL du projet*</label>
                <input
                  type="text"
                  placeholder="ex: exemple.fr ou https://entreprise.com"
                  value={newDomaine}
                  onChange={(e) => setNewDomaine(e.target.value)}
                  required
                  className="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-blue-500"
                />
              </div>

              <div className="space-y-1">
                <label className="text-2xs font-bold text-slate-600 uppercase">Secteur d'activité</label>
                <input
                  type="text"
                  value={newSecteur}
                  onChange={(e) => setNewSecteur(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-blue-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <label className="text-2xs font-bold text-slate-600 uppercase">Salariés</label>
                  <select
                    value={newSalaries}
                    onChange={(e) => setNewSalaries(e.target.value)}
                    className="w-full px-3 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-blue-500"
                  >
                    <option value="1-10">1-10 salariés</option>
                    <option value="11-50">11-50 salariés</option>
                    <option value="51-250">51-250 salariés</option>
                    <option value="> 250">+250 salariés</option>
                  </select>
                </div>
                <div className="space-y-1">
                  <label className="text-2xs font-bold text-slate-600 uppercase">Chiffre d'Affaires</label>
                  <select
                    value={newCA}
                    onChange={(e) => setNewCA(e.target.value)}
                    className="w-full px-3 py-2.5 rounded-xl bg-white border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-blue-500"
                  >
                    <option value="< 2M€">&lt; 2M€</option>
                    <option value="2M€-10M€">2M€-10M€</option>
                    <option value="10M€-50M€">10M€-50M€</option>
                    <option value="> 50M€">&gt; 50M€</option>
                  </select>
                </div>
              </div>

              <div className="flex items-center space-x-3 pt-2">
                <button
                  type="submit"
                  className="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 transition-all cursor-pointer shadow-xs"
                >
                  Créer le projet
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
