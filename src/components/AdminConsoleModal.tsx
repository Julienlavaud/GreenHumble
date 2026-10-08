import React, { useState, useEffect, useRef } from 'react';
import { 
  Collaborator, 
  IASettings,
} from '../types.js';
import { 
  Users, 
  X, 
  Plus, 
  Trash2, 
  Edit3,
  Save, 
  Check, 
  Database,
  Download,
  Upload,
  Brain,
  MessageSquare,
  ShieldCheck,
  Briefcase,
  Scale,
  Palette,
  Sliders,
  Type
} from 'lucide-react';

interface AdminConsoleModalProps {
  isOpen: boolean;
  onClose: () => void;
  onOpenServerAdmin?: () => void;
  onClearAllData?: () => void;
}

export const AdminConsoleModal: React.FC<AdminConsoleModalProps> = ({
  isOpen,
  onClose,
  onClearAllData,
}) => {
  // Tabs: IA, Collaborateurs, Style, Édition de texte, Sauvegarde
  const [activeTab, setActiveTab] = useState<'ia' | 'collaborateurs' | 'style' | 'textes' | 'backup'>('ia');
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Collaborators State
  const [collaborators, setCollaborators] = useState<Collaborator[]>([]);
  const [editingUser, setEditingUser] = useState<Collaborator | null>(null);
  const [confirmDeleteCollabId, setConfirmDeleteCollabId] = useState<string | null>(null);
  const userFormRef = useRef<HTMLDivElement>(null);
  const [userName, setUserName] = useState('');
  const [userEmail, setUserEmail] = useState('');
  const [userRole, setUserRole] = useState<'admin' | 'pipeline' | 'lecture seule'>('lecture seule');
  const [userPassword, setUserPassword] = useState('');
  const [userPhone, setUserPhone] = useState('');

  // AI Settings State
  const [aiSettings, setAiSettings] = useState<IASettings>({
    model: 'gemini-3.8-flash',
    temperature: 0.3,
    maxTokens: 4096,
    systemPrompt: '',
    userPrompt: '',
    dictionaries: '',
    rules: '',
    businessOpportunityParams: '',
    greenClaimsParams: '',
  });

  // Style State
  const [styleSettings, setStyleSettings] = useState({
    primaryColor: '#059669',
    darkThemeIntensity: '#ffffff',
    surfaceDark: '#f8fafc',
    accentEmerald: '#059669',
    fontScale: 'standard',
    slogan: 'GREENHUMBLE — Restons humbles face à l’impact réel.',
    logoBadge: 'GreenHumble',
    compactMode: false
  });

  // Texts State
  const [pageTexts, setPageTexts] = useState<Record<string, string>>({});
  const [newTextKey, setNewTextKey] = useState('');
  const [newTextValue, setNewTextValue] = useState('');

  useEffect(() => {
    if (isOpen) {
      fetchCollaborators();
      fetchAiSettings();
      fetchTexts();
      fetchStyle();
    }
  }, [isOpen]);

  const fetchCollaborators = async () => {
    try {
      const res = await fetch('/api/admin/collaborators');
      if (res.ok) {
        const data = await res.json();
        if (Array.isArray(data)) {
          setCollaborators(data);
        } else if (data.collaborators && Array.isArray(data.collaborators)) {
          setCollaborators(data.collaborators);
        } else {
          setCollaborators([]);
        }
      }
    } catch (_) {}
  };

  const fetchAiSettings = async () => {
    try {
      const res = await fetch('/api/admin/ia-settings');
      if (res.ok) {
        const data = await res.json();
        if (data.settings || data.iaSettings || data.aiSettings) {
          setAiSettings(data.settings || data.iaSettings || data.aiSettings);
        }
      }
    } catch (_) {}
  };

  const fetchTexts = async () => {
    try {
      const res = await fetch('/api/admin/texts');
      if (res.ok) {
        const data = await res.json();
        setPageTexts(data.texts || data || {});
      }
    } catch (_) {}
  };

  const fetchStyle = async () => {
    try {
      const res = await fetch('/api/admin/style');
      if (res.ok) {
        const data = await res.json();
        if (data.style) {
          setStyleSettings(data.style);
        }
      }
    } catch (_) {}
  };

  const handleSaveUser = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const payload = {
        id: editingUser?.id,
        name: userName,
        email: userEmail,
        role: userRole,
        password: userPassword,
        phone: userPhone
      };
      const res = await fetch('/api/admin/collaborators', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (res.ok) {
        const data = await res.json();
        if (data.collaborators && Array.isArray(data.collaborators)) {
          setCollaborators(data.collaborators);
        } else if (Array.isArray(data)) {
          setCollaborators(data);
        } else {
          fetchCollaborators();
        }
        setEditingUser(null);
        setUserName('');
        setUserEmail('');
        setUserPassword('');
        setUserPhone('');
        await triggerDirectBackupDownload();
        setSuccessMsg(editingUser ? 'Collaborateur modifié ! Téléchargement direct du fichier JSON démarré.' : 'Collaborateur créé ! Téléchargement direct du fichier JSON démarré.');
        setTimeout(() => setSuccessMsg(null), 3500);
      } else {
        const err = await res.json().catch(() => ({}));
        setSuccessMsg(`Erreur : ${err.error || 'Impossible d’enregistrer le collaborateur'}`);
        setTimeout(() => setSuccessMsg(null), 4000);
      }
    } catch (e: any) {
      setSuccessMsg(`Erreur : ${e.message}`);
      setTimeout(() => setSuccessMsg(null), 4000);
    }
  };

  const handleDeleteUser = async (id: string) => {
    try {
      const res = await fetch(`/api/admin/collaborators/${id}`, { method: 'DELETE' });
      if (res.ok) {
        const data = await res.json();
        if (data.collaborators && Array.isArray(data.collaborators)) {
          setCollaborators(data.collaborators);
        } else if (Array.isArray(data)) {
          setCollaborators(data);
        } else {
          setCollaborators(prev => prev.filter(c => c.id !== id));
        }
        setSuccessMsg('Collaborateur supprimé !');
        setTimeout(() => setSuccessMsg(null), 3000);
      } else {
        // Fallback POST delete
        const res2 = await fetch('/api/admin/collaborators/delete', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        if (res2.ok) {
          const data2 = await res2.json();
          if (data2.collaborators && Array.isArray(data2.collaborators)) {
            setCollaborators(data2.collaborators);
          } else if (Array.isArray(data2)) {
            setCollaborators(data2);
          } else {
            setCollaborators(prev => prev.filter(c => c.id !== id));
          }
          setSuccessMsg('Collaborateur supprimé !');
          setTimeout(() => setSuccessMsg(null), 3000);
        } else {
          setCollaborators(prev => prev.filter(c => c.id !== id));
        }
      }
    } catch (e: any) {
      console.error('Erreur suppression collaborateur:', e);
      setCollaborators(prev => prev.filter(c => c.id !== id));
    } finally {
      setConfirmDeleteCollabId(null);
    }
  };

  const triggerDirectBackupDownload = async () => {
    try {
      const res = await fetch('/api/admin/backup/export-direct');
      if (res.ok) {
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const dateStr = new Date().toISOString().slice(0, 10);
        a.download = `greenhumble-backup-${dateStr}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        return true;
      }
    } catch (e) {
      console.error('Direct download error:', e);
    }
    return false;
  };

  const handleSaveAiSettings = async () => {
    const res = await fetch('/api/admin/ia-settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(aiSettings)
    });
    if (res.ok) {
      await triggerDirectBackupDownload();
      setSuccessMsg('Paramètres IA enregistrés ! Téléchargement direct du fichier JSON démarré.');
      setTimeout(() => setSuccessMsg(null), 3500);
    }
  };

  const handleSaveStyle = async () => {
    const res = await fetch('/api/admin/style', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ style: styleSettings })
    });
    if (res.ok) {
      await triggerDirectBackupDownload();
      setSuccessMsg('Paramètres de style enregistrés ! Téléchargement direct du fichier JSON démarré.');
      setTimeout(() => setSuccessMsg(null), 3500);
    }
  };

  const handleSaveTexts = async () => {
    const res = await fetch('/api/admin/texts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ texts: pageTexts })
    });
    if (res.ok) {
      await triggerDirectBackupDownload();
      setSuccessMsg('Textes enregistrés ! Téléchargement direct du fichier JSON démarré.');
      setTimeout(() => setSuccessMsg(null), 3500);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div className="bg-white w-full max-w-6xl h-[90vh] rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col border border-slate-200 text-slate-800">
        {/* Header */}
        <div className="bg-slate-50 text-slate-900 p-6 flex items-center justify-between border-b border-slate-200">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center shadow-md text-white">
              <ShieldCheck className="w-6 h-6 text-white" />
            </div>
            <div>
              <h2 className="text-xl font-black tracking-tight text-slate-900 font-serif">Cockpit Admin GreenHumble</h2>
              <p className="text-xs text-slate-500">Configuration centrale et souveraineté des données</p>
            </div>
          </div>
          <button onClick={onClose} className="p-2 hover:bg-slate-200 text-slate-500 hover:text-slate-800 rounded-xl transition-colors cursor-pointer">
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Tabs: IA, Collaborateurs, Style, Édition de texte, Sauvegarde */}
        <div className="bg-slate-100 p-2 flex space-x-1 border-b border-slate-200 overflow-x-auto">
          {[
            { id: 'ia', label: 'IA', icon: Brain },
            { id: 'collaborateurs', label: 'Collaborateurs', icon: Users },
            { id: 'style', label: 'Style', icon: Palette },
            { id: 'textes', label: 'Édition de texte', icon: MessageSquare },
            { id: 'backup', label: 'Sauvegarde', icon: Database },
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as any)}
              className={`flex items-center space-x-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                activeTab === tab.id ? 'bg-white text-blue-600 shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'
              }`}
            >
              <tab.icon className="w-4 h-4" />
              <span>{tab.label}</span>
            </button>
          ))}
        </div>

        {/* Success Msg */}
        {successMsg && (
          <div className="mx-6 mt-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2 animate-fadeIn">
            <Check className="w-4 h-4 text-emerald-600" />
            <span>{successMsg}</span>
          </div>
        )}

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/60">
          {/* TAB 1: IA (Moteur IA) */}
          {activeTab === 'ia' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                  <h3 className="text-base font-black text-slate-900 flex items-center space-x-2">
                    <Brain className="w-5 h-5 text-blue-600" />
                    <span>Paramètres du Moteur IA & Clé API</span>
                  </h3>
                  <p className="text-xs text-slate-500">
                    Configuration sauvegardée dans <code className="text-blue-600 font-mono">/data/ia_settings.json</code> et appliquée à chaque audit.
                  </p>
                </div>
                <button
                  onClick={handleSaveAiSettings}
                  className="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center space-x-2 shadow-sm hover:bg-blue-700 transition-all cursor-pointer"
                >
                  <Save className="w-4 h-4" />
                  <span>Enregistrer dans /data/ia_settings.json</span>
                </button>
              </div>

              {/* API Key & Provider Card */}
              <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-black text-slate-900 text-xs uppercase flex items-center space-x-2">
                      <ShieldCheck className="w-4 h-4 text-blue-600" />
                      <span>Connexion & Clé API (Fournisseur & Clé d'authentification)</span>
                    </h4>
                    <p className="text-[11px] text-slate-500 mt-1">Indiquez votre clé API Google AI Studio (Gemini) ou Groq pour alimenter l'analyse sémantique.</p>
                  </div>
                  <button
                    type="button"
                    onClick={async () => {
                      try {
                        const res = await fetch('/api/admin/test-ai', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify(aiSettings)
                        });
                        const data = await res.json();
                        if (res.ok) {
                          setSuccessMsg('✅ ' + data.message);
                        } else {
                          setSuccessMsg('❌ Erreur : ' + (data.error || 'Clé ou modèle invalide'));
                        }
                      } catch (err: any) {
                        setSuccessMsg('❌ Erreur réseau lors du test IA');
                      }
                      setTimeout(() => setSuccessMsg(null), 5000);
                    }}
                    className="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all cursor-pointer flex items-center space-x-1.5"
                  >
                    <Check className="w-4 h-4" />
                    <span>Tester la connexion IA</span>
                  </button>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">Fournisseur d'IA</label>
                    <select
                      value={aiSettings.provider || 'gemini'}
                      onChange={(e) => setAiSettings({ ...aiSettings, provider: e.target.value })}
                      className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white"
                    >
                      <option value="gemini">Google Gemini (AI Studio)</option>
                      <option value="groq">Groq (Llama 3.3)</option>
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">Clé API (API Key)</label>
                    <input
                      type="password"
                      placeholder="AIzaSy... ou gsk_..."
                      value={aiSettings.apiKey || ''}
                      onChange={(e) => setAiSettings({ ...aiSettings, apiKey: e.target.value })}
                      className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-mono text-emerald-700 focus:outline-none focus:border-blue-500 focus:bg-white"
                    />
                  </div>
                </div>
              </div>

              {/* Grid 1: Model & Hyperparameters */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white p-5 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <label className="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                    <Sliders className="w-4 h-4 text-blue-600" />
                    <span>Modèle IA</span>
                  </label>
                  <select
                    value={aiSettings.model}
                    onChange={(e) => setAiSettings({ ...aiSettings, model: e.target.value })}
                    className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-blue-700 focus:outline-none focus:border-blue-500 focus:bg-white"
                  >
                    <option value="gemini-3.5-flash-lite">Gemini 3.5 Flash Lite (Recommandé & Haute Disponibilité)</option>
                    <option value="gemini-3.1-flash-lite">Gemini 3.1 Flash Lite (Ultra-rapide)</option>
                    <option value="gemini-flash-lite-latest">Gemini Flash Lite (Dernière version)</option>
                    <option value="gemini-flash-latest">Gemini Flash (Standard)</option>
                    <option value="gemini-3.8-flash">Gemini 3.8 Flash</option>
                  </select>
                  <p className="text-[10px] text-slate-500">Moteur LLM certifié pour l'analyse sémantique.</p>
                </div>

                <div className="bg-white p-5 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <div className="flex justify-between items-center">
                    <label className="text-xs font-black text-slate-900 uppercase tracking-wider">Température</label>
                    <span className="text-xs font-mono font-bold text-emerald-700">{aiSettings.temperature}</span>
                  </div>
                  <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    value={aiSettings.temperature}
                    onChange={(e) => setAiSettings({ ...aiSettings, temperature: parseFloat(e.target.value) })}
                    className="w-full accent-blue-600 cursor-pointer"
                  />
                  <p className="text-[10px] text-slate-500">0.0 = Déterministe / Rigoureux, 1.0 = Créatif</p>
                </div>

                <div className="bg-white p-5 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <div className="flex justify-between items-center">
                    <label className="text-xs font-black text-slate-900 uppercase tracking-wider">Longueur Max (Tokens)</label>
                    <span className="text-xs font-mono font-bold text-blue-700">{aiSettings.maxTokens}</span>
                  </div>
                  <input
                    type="number"
                    min="512"
                    max="8192"
                    step="256"
                    value={aiSettings.maxTokens}
                    onChange={(e) => setAiSettings({ ...aiSettings, maxTokens: parseInt(e.target.value) || 4096 })}
                    className="w-full px-4 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white"
                  />
                  <p className="text-[10px] text-slate-500">Plafond de tokens par réponse de diagnostic.</p>
                </div>
              </div>

              {/* Grid 2: Dictionaries & Rules */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <h4 className="font-black text-slate-900 text-xs uppercase flex items-center space-x-2">
                    <Sliders className="w-4 h-4 text-blue-600" />
                    <span>Dictionnaires IA (Mots-clés & Expressions)</span>
                  </h4>
                  <textarea
                    rows={4}
                    value={aiSettings.dictionaries}
                    onChange={(e) => setAiSettings({ ...aiSettings, dictionaries: e.target.value })}
                    className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-mono text-emerald-700 focus:outline-none focus:border-blue-500 focus:bg-white"
                    placeholder="neutre en carbone, éco-responsable, zéro émission..."
                  />
                  <p className="text-[10px] text-slate-500">Termes et déclencheurs sémantiques ciblés lors des scans.</p>
                </div>

                <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <h4 className="font-black text-slate-900 text-xs uppercase flex items-center space-x-2">
                    <Scale className="w-4 h-4 text-rose-600" />
                    <span>Règles IA & Critères d'infraction</span>
                  </h4>
                  <textarea
                    rows={4}
                    value={aiSettings.rules}
                    onChange={(e) => setAiSettings({ ...aiSettings, rules: e.target.value })}
                    className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-mono text-emerald-700 focus:outline-none focus:border-blue-500 focus:bg-white"
                    placeholder="Règles d'évaluation des allégations environnementales..."
                  />
                  <p className="text-[10px] text-slate-500">Directives d'évaluation selon la législation européenne et française.</p>
                </div>
              </div>

              {/* Prompts Section */}
              <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                <h4 className="text-xs font-black text-slate-900 uppercase flex items-center space-x-2">
                  <MessageSquare className="w-4 h-4 text-blue-600" />
                  <span>Prompts Système & Utilisateur</span>
                </h4>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase">Prompt Système (Persona & Rôle Juridique)</label>
                    <textarea
                      rows={6}
                      value={aiSettings.systemPrompt}
                      onChange={(e) => setAiSettings({ ...aiSettings, systemPrompt: e.target.value })}
                      className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase">Prompt Utilisateur (Consignes de scan & extraction)</label>
                    <textarea
                      rows={6}
                      value={aiSettings.userPrompt}
                      onChange={(e) => setAiSettings({ ...aiSettings, userPrompt: e.target.value })}
                      className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white"
                    />
                  </div>
                </div>
              </div>

              {/* Business & Green Claims */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="p-6 bg-white rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <h4 className="font-black text-slate-900 text-xs uppercase flex items-center space-x-2">
                    <Briefcase className="w-4 h-4 text-blue-600" />
                    <span>Paramètres Opportunités Business & Missions</span>
                  </h4>
                  <textarea
                    rows={4}
                    value={aiSettings.businessOpportunityParams}
                    onChange={(e) => setAiSettings({ ...aiSettings, businessOpportunityParams: e.target.value })}
                    className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white"
                    placeholder="Missions d'accompagnement Bilan Carbone, ACV, RSE..."
                  />
                </div>
                <div className="p-6 bg-white rounded-3xl border border-slate-200 space-y-3 shadow-sm">
                  <h4 className="font-black text-slate-900 text-xs uppercase flex items-center space-x-2">
                    <Scale className="w-4 h-4 text-rose-600" />
                    <span>Paramètres Conformité Green Claims</span>
                  </h4>
                  <textarea
                    rows={4}
                    value={aiSettings.greenClaimsParams}
                    onChange={(e) => setAiSettings({ ...aiSettings, greenClaimsParams: e.target.value })}
                    className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-800 focus:outline-none focus:border-blue-500 focus:bg-white"
                    placeholder="Articles de loi L.229-68, Directive EmpCO 2024/825..."
                  />
                </div>
              </div>
            </div>
          )}

          {/* TAB 2: COLLABORATEURS */}
          {activeTab === 'collaborateurs' && (
            <div className="space-y-6 animate-fadeIn">
              {/* Form */}
              <div ref={userFormRef} className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-sm font-black text-slate-900 flex items-center space-x-2">
                    <Plus className="w-4 h-4 text-blue-600" />
                    <span>{editingUser ? `Modifier : ${editingUser.name}` : 'Nouveau collaborateur'}</span>
                  </h3>
                  {editingUser && (
                    <span className="text-2xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-bold border border-amber-200">
                      Mode Édition actif
                    </span>
                  )}
                </div>
                <form onSubmit={handleSaveUser} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">Nom complet*</label>
                    <input
                      type="text"
                      value={userName}
                      onChange={(e) => setUserName(e.target.value)}
                      required
                      className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:bg-white focus:border-blue-500"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">Email pro*</label>
                    <input
                      type="email"
                      value={userEmail}
                      onChange={(e) => setUserEmail(e.target.value)}
                      required
                      className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:bg-white focus:border-blue-500"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">Rôle*</label>
                    <select
                      value={userRole}
                      onChange={(e) => setUserRole(e.target.value as any)}
                      className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-900 focus:bg-white focus:border-blue-500"
                    >
                      <option value="admin">Administrateur</option>
                      <option value="pipeline">Pipeline Manager</option>
                      <option value="lecture seule">Lecture seule</option>
                    </select>
                  </div>
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">
                      {editingUser ? 'Nouveau mot de passe (laisser vide pour inchangé)' : 'Mot de passe*'}
                    </label>
                    <input
                      type="password"
                      value={userPassword}
                      onChange={(e) => setUserPassword(e.target.value)}
                      required={!editingUser}
                      placeholder={editingUser ? 'Laisser vide pour ne pas modifier' : ''}
                      className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:bg-white focus:border-blue-500"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="text-2xs font-bold text-slate-500 uppercase tracking-wider">Téléphone</label>
                    <input
                      type="text"
                      value={userPhone}
                      onChange={(e) => setUserPhone(e.target.value)}
                      className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-900 focus:bg-white focus:border-blue-500"
                    />
                  </div>
                  <div className="flex items-end space-x-2">
                    <button type="submit" className="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 shadow-sm cursor-pointer">
                      {editingUser ? 'Enregistrer les modifications' : 'Créer le collaborateur'}
                    </button>
                    {editingUser && (
                      <button 
                        type="button" 
                        onClick={() => { setEditingUser(null); setUserName(''); setUserEmail(''); setUserPassword(''); setUserPhone(''); }}
                        className="py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold text-sm cursor-pointer"
                      >
                        Annuler
                      </button>
                    )}
                  </div>
                </form>
              </div>

              {/* List */}
              <div className="space-y-3">
                <h3 className="text-sm font-black text-slate-900">Collaborateurs actifs ({collaborators.length})</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {collaborators.map((c) => (
                    <div key={c.id} className="p-4 bg-white border border-slate-200 rounded-3xl flex items-center justify-between shadow-sm">
                      <div className="flex items-center space-x-3">
                        <div className="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 font-black text-xl">
                          {c.name.charAt(0)}
                        </div>
                        <div>
                          <div className="flex items-center space-x-2">
                            <span className="font-bold text-sm text-slate-900">{c.name}</span>
                            <span className="text-2xs px-2 py-0.5 rounded-full font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                              {c.role}
                            </span>
                          </div>
                          <p className="text-xs text-slate-500">{c.email}</p>
                          {c.phone && <p className="text-2xs text-slate-400">{c.phone}</p>}
                        </div>
                      </div>
                      <div className="flex items-center space-x-1">
                        {confirmDeleteCollabId === c.id ? (
                          <div className="flex items-center space-x-1.5 bg-rose-50 p-1.5 rounded-xl border border-rose-200">
                            <span className="text-[10px] text-rose-700 font-bold">Confirmer ?</span>
                            <button
                              onClick={() => handleDeleteUser(c.id)}
                              className="px-2 py-1 text-[10px] bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg cursor-pointer"
                            >
                              Oui
                            </button>
                            <button
                              onClick={() => setConfirmDeleteCollabId(null)}
                              className="px-2 py-1 text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg cursor-pointer"
                            >
                              Non
                            </button>
                          </div>
                        ) : (
                          <>
                            <button 
                              onClick={() => {
                                setEditingUser(c);
                                setUserName(c.name);
                                setUserEmail(c.email);
                                setUserRole(c.role);
                                setUserPhone(c.phone || '');
                                setUserPassword('');
                                userFormRef.current?.scrollIntoView({ behavior: 'smooth' });
                              }}
                              title="Modifier ce collaborateur"
                              className="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-700 cursor-pointer"
                            >
                              <Edit3 className="w-4 h-4" />
                            </button>
                            <button 
                              onClick={() => setConfirmDeleteCollabId(c.id)}
                              title="Supprimer ce collaborateur"
                              className="p-2 hover:bg-rose-50 rounded-xl text-slate-400 hover:text-rose-600 cursor-pointer"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* TAB 3: STYLE */}
          {activeTab === 'style' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="flex items-center justify-between border-b border-slate-200 pb-4">
                <div>
                  <h3 className="text-base font-black text-slate-900 flex items-center space-x-2">
                    <Palette className="w-5 h-5 text-blue-600" />
                    <span>Personnalisation du Thème & Identité Sobre</span>
                  </h3>
                  <p className="text-xs text-slate-500">
                    Ajustez l'identité visuelle, les couleurs d'accentuation et les slogans pour l'éco-conception.
                  </p>
                </div>
                <button
                  onClick={handleSaveStyle}
                  className="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center space-x-2 shadow-sm hover:bg-blue-700 transition-all cursor-pointer"
                >
                  <Save className="w-4 h-4" />
                  <span>Enregistrer le style</span>
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Palette selection */}
                <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                  <h4 className="text-xs font-black text-slate-900 uppercase flex items-center space-x-2">
                    <Palette className="w-4 h-4 text-blue-600" />
                    <span>Couleur d'accentuation principale</span>
                  </h4>
                  <div className="grid grid-cols-4 gap-3">
                    {[
                      { name: 'Bleu Sobre', hex: '#1e40af' },
                      { name: 'Émeraude Climat', hex: '#059669' },
                      { name: 'Indigo Minéral', hex: '#4f46e5' },
                      { name: 'Ambre Sobriété', hex: '#d97706' },
                    ].map((c) => (
                      <button
                        key={c.hex}
                        onClick={() => setStyleSettings({ ...styleSettings, primaryColor: c.hex })}
                        className={`p-3 rounded-2xl border text-center transition-all cursor-pointer flex flex-col items-center space-y-2 ${
                          styleSettings.primaryColor === c.hex ? 'border-blue-600 bg-blue-50 shadow-sm' : 'border-slate-200 bg-slate-50 hover:border-slate-300'
                        }`}
                      >
                        <span className="w-6 h-6 rounded-full shadow-inner" style={{ backgroundColor: c.hex }} />
                        <span className="text-[11px] font-bold text-slate-800">{c.name}</span>
                      </button>
                    ))}
                  </div>

                  <div className="pt-2 space-y-2">
                    <label className="text-2xs font-bold text-slate-500 uppercase">Couleur Hex personnalisée</label>
                    <input
                      type="text"
                      value={styleSettings.primaryColor}
                      onChange={(e) => setStyleSettings({ ...styleSettings, primaryColor: e.target.value })}
                      className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-mono text-slate-900 focus:bg-white focus:border-blue-500"
                    />
                  </div>
                </div>

                {/* Slogan & Badge */}
                <div className="bg-white p-6 rounded-3xl border border-slate-200 space-y-4 shadow-sm">
                  <h4 className="text-xs font-black text-slate-900 uppercase flex items-center space-x-2">
                    <Type className="w-4 h-4 text-blue-600" />
                    <span>Slogan & Identité de Marque</span>
                  </h4>
                  <div className="space-y-3">
                    <div className="space-y-1">
                      <label className="text-2xs font-bold text-slate-500 uppercase">Nom / Badge Logo</label>
                      <input
                        type="text"
                        value={styleSettings.logoBadge}
                        onChange={(e) => setStyleSettings({ ...styleSettings, logoBadge: e.target.value })}
                        className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 focus:bg-white focus:border-blue-500"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="text-2xs font-bold text-slate-500 uppercase">Slogan officiel</label>
                      <input
                        type="text"
                        value={styleSettings.slogan}
                        onChange={(e) => setStyleSettings({ ...styleSettings, slogan: e.target.value })}
                        className="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-900 focus:bg-white focus:border-blue-500"
                      />
                    </div>
                  </div>

                  <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <span className="text-[10px] font-bold uppercase text-slate-500 tracking-wider">Aperçu en direct</span>
                    <div className="flex items-center space-x-2">
                      <div className="w-7 h-7 rounded-lg flex items-center justify-center text-white font-black text-xs" style={{ backgroundColor: styleSettings.primaryColor }}>
                        G
                      </div>
                      <span className="text-sm font-black text-slate-900 font-serif">{styleSettings.logoBadge}</span>
                      <span className="text-[10px] px-2 py-0.5 rounded font-bold uppercase" style={{ backgroundColor: `${styleSettings.primaryColor}15`, color: styleSettings.primaryColor, border: `1px solid ${styleSettings.primaryColor}40` }}>
                        ÉCO-CONCEPTION
                      </span>
                    </div>
                    <p className="text-2xs text-slate-600 italic">{styleSettings.slogan}</p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* TAB 4: ÉDITION DE TEXTE */}
          {activeTab === 'textes' && (
            <div className="space-y-6 animate-fadeIn">
              <div className="bg-white p-6 rounded-[2.5rem] border border-slate-200 space-y-4 shadow-sm">
                <div className="flex items-center justify-between">
                  <h3 className="text-sm font-black text-slate-900 flex items-center space-x-2">
                    <MessageSquare className="w-4 h-4 text-blue-600" />
                    <span>Éditeur de textes & mentions</span>
                  </h3>
                  <button onClick={handleSaveTexts} className="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center space-x-2 shadow-sm hover:bg-blue-700 transition-all cursor-pointer">
                    <Save className="w-4 h-4" />
                    <span>Enregistrer les textes</span>
                  </button>
                </div>

                {/* Add new text key */}
                <div className="flex gap-2 p-4 bg-slate-50 rounded-2xl border border-slate-200 shadow-xs">
                  <input
                    type="text"
                    placeholder="Clé (ex: accueil_titre)"
                    value={newTextKey}
                    onChange={(e) => setNewTextKey(e.target.value)}
                    className="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-blue-700 focus:outline-none focus:border-blue-500"
                  />
                  <input
                    type="text"
                    placeholder="Valeur du texte"
                    value={newTextValue}
                    onChange={(e) => setNewTextValue(e.target.value)}
                    className="flex-[2] px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-blue-500"
                  />
                  <button 
                    onClick={() => {
                      if (newTextKey && newTextValue) {
                        setPageTexts({ ...pageTexts, [newTextKey]: newTextValue });
                        setNewTextKey('');
                        setNewTextValue('');
                      }
                    }}
                    className="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs cursor-pointer hover:bg-blue-700 transition-colors shadow-xs"
                  >
                    Ajouter
                  </button>
                </div>

                {/* Text List */}
                <div className="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                  {Object.entries(pageTexts).map(([key, value]) => (
                    <div key={key} className="space-y-1 p-4 bg-slate-50 rounded-2xl border border-slate-200 shadow-xs hover:border-blue-300 transition-colors">
                      <div className="flex items-center justify-between">
                        <span className="text-[10px] font-black text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full uppercase tracking-widest border border-blue-200">{key}</span>
                        <button 
                          onClick={() => {
                            const updated = { ...pageTexts };
                            delete updated[key];
                            setPageTexts(updated);
                          }}
                          className="text-slate-400 hover:text-rose-600 cursor-pointer"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                      <textarea
                        value={value}
                        onChange={(e) => setPageTexts({ ...pageTexts, [key]: e.target.value })}
                        className="w-full px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-blue-500 transition-colors"
                        rows={2}
                      />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* TAB 5: SAUVEGARDE */}
          {activeTab === 'backup' && (
            <div className="space-y-8 animate-fadeIn">
              {/* Highlight Banner: Direct Instant Download */}
              <div className="p-8 bg-gradient-to-r from-blue-50 via-slate-50 to-blue-50 rounded-[3rem] border border-blue-200 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
                <div className="flex items-center space-x-6">
                  <div className="w-20 h-20 rounded-[2rem] bg-blue-600 flex items-center justify-center shrink-0 shadow-md text-white">
                    <Download className="w-10 h-10 text-white" />
                  </div>
                  <div>
                    <span className="px-3 py-1 rounded-full bg-blue-100 text-blue-800 font-black text-[10px] uppercase tracking-widest border border-blue-200">
                      Téléchargement Direct lors de la Sauvegarde
                    </span>
                    <h3 className="text-2xl font-black text-slate-900 mt-1">Télécharger le Backup JSON Directement</h3>
                    <p className="text-xs text-slate-600 mt-1 max-w-xl">
                      Obtenez immédiatement le fichier JSON complet contenant l’intégralité de vos listes, scans, projets, audits et paramétrages. Ce fichier est généré à chaque sauvegarde par la console admin.
                    </p>
                  </div>
                </div>
                <div className="flex flex-col sm:flex-row gap-3 shrink-0 w-full md:w-auto">
                  <button
                    onClick={async () => {
                      const success = await triggerDirectBackupDownload();
                      if (success) {
                        setSuccessMsg('Téléchargement direct du fichier de sauvegarde JSON démarré !');
                        setTimeout(() => setSuccessMsg(null), 3500);
                      } else {
                        setSuccessMsg('Erreur lors de la génération du téléchargement.');
                        setTimeout(() => setSuccessMsg(null), 3500);
                      }
                    }}
                    className="px-6 py-4 rounded-[1.5rem] bg-blue-600 hover:bg-blue-700 text-white font-black text-sm shadow-md transition-all flex items-center justify-center space-x-3 cursor-pointer"
                  >
                    <Download className="w-5 h-5" />
                    <span>Télécharger Directement (.json)</span>
                  </button>
                  <label className="px-6 py-4 rounded-[1.5rem] bg-white hover:bg-slate-100 text-slate-800 font-black text-sm shadow-sm transition-all flex items-center justify-center space-x-3 cursor-pointer border border-slate-200">
                    <Upload className="w-5 h-5 text-emerald-600" />
                    <span>Importer / Restaurer (.json)</span>
                    <input
                      type="file"
                      className="hidden"
                      accept=".json"
                      onChange={async (e) => {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        try {
                          const text = await file.text();
                          const parsed = JSON.parse(text);
                          const res = await fetch('/api/admin/backup/import-json', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(parsed)
                          });
                          if (res.ok) {
                            setSuccessMsg('Base de données restaurée avec succès ! Rechargement...');
                            setTimeout(() => window.location.reload(), 1200);
                          } else {
                            const err = await res.json();
                            setSuccessMsg('Erreur : ' + (err.error || 'Impossible d’importer'));
                            setTimeout(() => setSuccessMsg(null), 4000);
                          }
                        } catch (e: any) {
                          setSuccessMsg('Erreur de lecture du fichier JSON : ' + e.message);
                          setTimeout(() => setSuccessMsg(null), 4000);
                        }
                      }}
                    />
                  </label>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Export ZIP */}
                <div className="bg-white p-8 rounded-[3rem] border border-slate-200 flex flex-col items-center text-center space-y-6 shadow-sm">
                  <div className="w-20 h-20 rounded-[2rem] bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                    <Download className="w-10 h-10 text-blue-600" />
                  </div>
                  <div>
                    <h3 className="text-xl font-black text-slate-900">Archive ZIP</h3>
                    <p className="text-sm text-slate-500 mt-2">
                      Générez un package ZIP (UTF-8) contenant séparément tous vos fichiers de données /data/*.json.
                    </p>
                  </div>
                  <a
                    href="/api/admin/backup/download-zip"
                    className="w-full py-4 rounded-[1.5rem] bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-black text-sm shadow-xs transition-all flex items-center justify-center space-x-3 cursor-pointer"
                  >
                    <Download className="w-5 h-5" />
                    <span>Télécharger l'Archive (.zip)</span>
                  </a>
                </div>

                {/* Import ZIP */}
                <div className="bg-white p-8 rounded-[3rem] border border-slate-200 flex flex-col items-center text-center space-y-6 shadow-sm">
                  <div className="w-20 h-20 rounded-[2rem] bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                    <Upload className="w-10 h-10 text-indigo-600" />
                  </div>
                  <div>
                    <h3 className="text-xl font-black text-slate-900">Restauration Archive ZIP</h3>
                    <p className="text-sm text-slate-500 mt-2">
                      Restaurez un fichier archive .zip de vos dossiers de données.
                    </p>
                  </div>
                  <label className="w-full py-4 rounded-[1.5rem] bg-slate-50 hover:bg-slate-100 text-slate-800 font-black text-sm shadow-xs transition-all flex items-center justify-center space-x-3 cursor-pointer border border-slate-200">
                    <Upload className="w-5 h-5 text-indigo-600" />
                    <span>Importer une archive (.zip)</span>
                    <input 
                      type="file" 
                      className="hidden" 
                      accept=".zip"
                      onChange={async (e) => {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        
                        const formData = new FormData();
                        formData.append('backup', file);
                        const res = await fetch('/api/admin/backup/import-zip', {
                          method: 'POST',
                          body: formData
                        });
                        if (res.ok) {
                          setSuccessMsg('Système restauré avec succès ! Rechargement...');
                          setTimeout(() => window.location.reload(), 1500);
                        } else {
                          const data = await res.json();
                          setSuccessMsg('Erreur : ' + (data.error || 'Erreur importation'));
                          setTimeout(() => setSuccessMsg(null), 4000);
                        }
                      }}
                    />
                  </label>
                </div>

                {/* Clear / Reset */}
                <div className="bg-white p-8 rounded-[3rem] border border-slate-200 flex flex-col items-center text-center space-y-6 shadow-sm">
                  <div className="w-20 h-20 rounded-[2rem] bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-100">
                    <Trash2 className="w-10 h-10 text-rose-600" />
                  </div>
                  <div>
                    <h3 className="text-xl font-black text-slate-900">Effacer les exemples</h3>
                    <p className="text-sm text-slate-500 mt-2">
                      Supprimez tous les audits et scans d'exemple pour démarrer avec une base vierge.
                    </p>
                  </div>
                  <button
                    onClick={async () => {
                      if (!window.confirm("Êtes-vous sûr de vouloir effacer tous les scans et exemples ?\n\n✓ Cela permet de démarrer sur une base professionnelle vierge sans données de bac à sable.")) {
                        return;
                      }
                      try {
                        onClearAllData?.();
                        localStorage.removeItem('climat_local_greenwashing_audits');
                        localStorage.removeItem('climat_local_scans');
                        localStorage.removeItem('climat_local_projets');
                        localStorage.removeItem('climat_local_scanner_groups');
                        localStorage.removeItem('climat_local_current_audit');

                        const res = await fetch('/api/admin/clear-scans', { method: 'POST' });
                        if (res.ok) {
                          onClearAllData?.();
                          setSuccessMsg('Base de données réinitialisée avec succès ! Environnement pro vierge actif.');
                          setTimeout(() => window.location.reload(), 800);
                        } else {
                          setSuccessMsg('Erreur lors du nettoyage');
                          setTimeout(() => setSuccessMsg(null), 4000);
                        }
                      } catch (e: any) {
                        setSuccessMsg(`Erreur : ${e.message}`);
                        setTimeout(() => setSuccessMsg(null), 4000);
                      }
                    }}
                    className="w-full py-4 rounded-[1.5rem] bg-rose-600 text-white font-black text-sm shadow-md hover:bg-rose-700 transition-all flex items-center justify-center space-x-3 cursor-pointer"
                  >
                    <Trash2 className="w-5 h-5" />
                    <span>Effacer les exemples & scans</span>
                  </button>
                </div>
              </div>

              {/* Security Banner */}
              <div className="p-6 bg-slate-900 rounded-[2.5rem] text-white flex items-center space-x-6 border border-slate-800 shadow-sm">
                <div className="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center shrink-0">
                  <ShieldCheck className="w-8 h-8 text-emerald-400" />
                </div>
                <div>
                  <h4 className="font-black text-lg text-white">Souveraineté & Persistance Locale</h4>
                  <p className="text-sm text-slate-300">
                    Vos données sont hébergées et stockées dans <code className="text-blue-400">/data/</code> au format JSON propre sans dépendance cloud externe.
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
