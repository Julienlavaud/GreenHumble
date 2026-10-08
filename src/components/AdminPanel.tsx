import React, { useState } from 'react';
import { Settings, Shield, Server, Database, AlertCircle, AlertTriangle, RefreshCw } from 'lucide-react';

type TabType = 'config' | 'diagnostics' | 'backup' | 'logs' | 'security';

export const AdminPanel: React.FC = () => {
  const [activeTab, setActiveTab] = useState<TabType>('config');

  return (
    <div className="p-6 bg-slate-50 min-h-screen">
      <header className="mb-8">
        <h1 className="text-2xl font-black text-slate-900">Administration Système</h1>
        <p className="text-sm text-slate-500">Configuration, diagnostics et sécurité</p>
      </header>

      <div className="flex space-x-4 mb-6">
        {[
          { id: 'config' as TabType, label: 'Config Gemini', icon: Settings },
          { id: 'diagnostics' as TabType, label: 'Diagnostics', icon: Server },
          { id: 'backup' as TabType, label: 'Backup/Restore', icon: Database },
          { id: 'logs' as TabType, label: 'Logs', icon: AlertTriangle },
          { id: 'security' as TabType, label: 'Sécurité', icon: Shield },
        ].map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-bold ${
              activeTab === tab.id ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'
            }`}
          >
            <tab.icon className="w-4 h-4" />
            <span>{tab.label}</span>
          </button>
        ))}
      </div>

      <div className="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
        {activeTab === 'config' && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">Configuration Gemini API</h2>
            <div className="space-y-2">
              <label className="block text-xs font-bold text-slate-700">Clé API</label>
              <input type="password" className="w-full px-4 py-2 rounded-xl border border-slate-300 bg-slate-50" placeholder="AIza..." />
              <button className="px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold">Tester la connexion</button>
            </div>
          </div>
        )}
        {activeTab === 'diagnostics' && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">Diagnostics Serveur</h2>
            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-sm">
              <p>PHP: 8.2</p>
              <p>MySQL: 5.7</p>
              <p>Connexion Gemini: Active</p>
            </div>
          </div>
        )}
        {activeTab === 'backup' && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">Sauvegarde / Restauration</h2>
            <button className="px-4 py-2 bg-slate-800 text-white rounded-xl text-sm">Exporter Base (SQL)</button>
            <button className="px-4 py-2 bg-slate-800 text-white rounded-xl text-sm">Exporter Fichiers (ZIP)</button>
          </div>
        )}
        {activeTab === 'logs' && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">Logs système</h2>
            <div className="p-4 rounded-2xl bg-slate-900 text-emerald-400 font-mono text-xs">
              [2026-09-30 12:28] System initialized.<br/>
              [2026-09-30 12:29] Gemini API check success.
            </div>
          </div>
        )}
        {activeTab === 'security' && (
          <div className="space-y-4">
            <h2 className="text-lg font-black text-slate-900">Sécurité</h2>
            <button className="px-4 py-2 bg-rose-700 text-white rounded-xl text-sm">Rotation Clés API</button>
          </div>
        )}
      </div>
    </div>
  );
};
