import React from 'react';
import { ShieldCheck, X, FileText, Scale, Lock } from 'lucide-react';

interface MentionsLegalesModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const MentionsLegalesModal: React.FC<MentionsLegalesModalProps> = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs animate-fadeIn no-print">
      <div className="bg-white text-slate-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6 max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-xl shrink-0">
              <Scale className="w-5 h-5 text-emerald-700" />
            </div>
            <div>
              <h3 className="font-black text-slate-900 text-lg">Mentions Légales & RGPD</h3>
              <p className="text-2xs font-bold text-emerald-800 uppercase tracking-wider">GreenHumble • Une marque Climat Local</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="space-y-5 text-xs text-slate-700 leading-relaxed">
          <section className="space-y-1.5">
            <h4 className="font-extrabold text-slate-900 text-sm flex items-center space-x-1.5">
              <FileText className="w-4 h-4 text-emerald-600" />
              <span>1. Éditeur de la Plateforme</span>
            </h4>
            <p>
              Le service <strong>GreenHumble</strong> est une marque et un outil développé par le Cabinet <strong>Climat Local</strong> (climatlocal.com).
            </p>
            <p className="text-2xs text-slate-500">
              Siège social : France / Auvergne-Rhône-Alpes • Contact : contact@climatlocal.com
            </p>
          </section>

          <section className="space-y-1.5">
            <h4 className="font-extrabold text-slate-900 text-sm flex items-center space-x-1.5">
              <ShieldCheck className="w-4 h-4 text-emerald-600" />
              <span>2. Propriété Intellectuelle & Méthodologie</span>
            </h4>
            <p>
              L’algorithme d’analyse sémantique, la grille d'audit de conformité à la Directive Green Claims (UE 2024/825 EmpCO) et le catalogue des 45+ référentiels juridiques sont protégés au titre du droit d’auteur et de la propriété intellectuelle.
            </p>
          </section>

          <section className="space-y-1.5">
            <h4 className="font-extrabold text-slate-900 text-sm flex items-center space-x-1.5">
              <Lock className="w-4 h-4 text-emerald-600" />
              <span>3. Protection des Données Personnelles (RGPD)</span>
            </h4>
            <p>
              Les données d’URL et d'entreprises saisies dans le scanner font l'objet d'un traitement sécurisé aux fins exclusives d'analyse de conformité RSE, d’établissement de diagnostic environnemental et de mise en relation conseil.
            </p>
            <p className="text-2xs text-slate-500">
              Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification et de suppression de vos données sur simple demande à contact@climatlocal.com.
            </p>
          </section>
        </div>

        <div className="flex justify-end pt-2 border-t border-slate-100">
          <button
            onClick={onClose}
            className="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-xs"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
};
