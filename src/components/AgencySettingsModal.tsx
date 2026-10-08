import React, { useState } from 'react';
import { X, Building2, User, Mail, Phone, MapPin, Globe, Save } from 'lucide-react';
import { AgencyProfile } from '../types.js';

interface AgencySettingsModalProps {
  isOpen: boolean;
  onClose: () => void;
  agency: AgencyProfile;
  onSave: (updated: AgencyProfile) => void;
}

export const AgencySettingsModal: React.FC<AgencySettingsModalProps> = ({
  isOpen,
  onClose,
  agency,
  onSave,
}) => {
  const [formData, setFormData] = useState<AgencyProfile>(agency);

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(formData);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs no-print animate-fadeIn">
      <div className="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
        {/* Header */}
        <div className="px-6 py-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
              <Building2 className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-base font-bold text-slate-900">Coordonnées de l’Agence Climat Local</h2>
              <p className="text-xs text-slate-500">Personnalisez vos coordonnées figurant sur les rapports et devis</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
              Nom de l’agence
            </label>
            <input
              type="text"
              value={formData.agencyName}
              onChange={(e) => setFormData({ ...formData, agencyName: e.target.value })}
              required
              className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Nom du Consultant
              </label>
              <input
                type="text"
                value={formData.consultantName}
                onChange={(e) => setFormData({ ...formData, consultantName: e.target.value })}
                required
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
              />
            </div>
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Titre / Rôle
              </label>
              <input
                type="text"
                value={formData.consultantRole}
                onChange={(e) => setFormData({ ...formData, consultantRole: e.target.value })}
                required
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Email de contact
              </label>
              <input
                type="email"
                value={formData.agencyEmail}
                onChange={(e) => setFormData({ ...formData, agencyEmail: e.target.value })}
                required
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
              />
            </div>
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Téléphone
              </label>
              <input
                type="text"
                value={formData.agencyPhone}
                onChange={(e) => setFormData({ ...formData, agencyPhone: e.target.value })}
                required
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Ville / Territoire
              </label>
              <input
                type="text"
                value={formData.agencyCity}
                onChange={(e) => setFormData({ ...formData, agencyCity: e.target.value })}
                required
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
              />
            </div>
            <div>
              <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Site Web de l’agence
              </label>
              <input
                type="text"
                value={formData.agencyWebsite}
                onChange={(e) => setFormData({ ...formData, agencyWebsite: e.target.value })}
                required
                className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-hidden"
              />
            </div>
          </div>

          <div className="pt-4 border-t border-slate-200 flex justify-end space-x-3">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800"
            >
              Annuler
            </button>
            <button
              type="submit"
              className="flex items-center space-x-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors"
            >
              <Save className="w-4 h-4" />
              <span>Enregistrer les coordonnées</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
