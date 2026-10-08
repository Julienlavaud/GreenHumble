import React from 'react';
import { X, ShieldAlert, AlertOctagon, Scale, ExternalLink, Check, Target, Layers, Leaf, FileCheck } from 'lucide-react';
import { EMPCO_BANNED_WORDS } from '../utils/ecoClaimWords.js';

interface MethodologyModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const MethodologyModal: React.FC<MethodologyModalProps> = ({ isOpen, onClose }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs no-print animate-fadeIn">
      <div className="bg-white rounded-3xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
        {/* Header */}
        <div className="sticky top-0 bg-white/95 backdrop-blur-md px-6 py-5 border-b border-slate-200 flex items-center justify-between z-10">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center">
              <AlertOctagon className="w-6 h-6" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">
                Référentiel des Mots Bannis EmpCO & Allégations Greenwashing (Directive UE 2024/825)
              </h2>
              <p className="text-xs text-slate-500">
                Directive EmpCO (UE) 2024/825 • Directive Green Claims • Décret français n° 2022-539
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 sm:p-8 space-y-6">
          <div className="bg-amber-50 rounded-2xl p-5 border border-amber-200 space-y-2 text-xs sm:text-sm text-amber-950">
            <h3 className="font-bold flex items-center space-x-2 text-amber-900">
              <Scale className="w-4 h-4 text-amber-700" />
              <span>Pourquoi ces allégations sont désormais interdites ?</span>
            </h3>
            <p className="leading-relaxed">
              La <strong>Directive européenne EmpCO (UE) 2024/825</strong> (Empowering Consumers for the Green Transition) et le <strong>Décret français n° 2022-539</strong> 
              sanctionnent sévèrement les allégations trompeuses qui promettent la <em>"neutralité carbone"</em> ou utilisent des adjectifs vagues sans preuve scientifique indépendante. 
              Pour être autorisée à communiquer, l'entreprise doit obligatoirement réaliser des audits normés : 
              <strong>Bilan Carbone® (Scopes 1-2-3)</strong>, <strong>ACV (ISO 14040/44)</strong> ou <strong>FDES/EPD</strong>.
            </p>
            <div className="flex items-center space-x-3 pt-2 text-xs">
              <span className="inline-flex items-center space-x-1 text-emerald-800 font-bold">
                <span>Critères EmpCO (UE) 2024/825 & Article L229-68</span>
              </span>
            </div>
          </div>

          {/* 4 Pillars of Services for Climat Local */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Les 4 Services de Climat Local pour régulariser les allégations :
            </h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-1">
                <div className="flex items-center space-x-2 font-bold text-emerald-950">
                  <Target className="w-4 h-4 text-emerald-700" />
                  <span>1. Bilan Carbone® (Scopes 1, 2, 3)</span>
                </div>
                <p className="text-emerald-900 leading-relaxed">
                  Obligatoire pour justifier toute communication climat et remplacer la fausse "neutralité" par une trajectoire de réduction ADEME/SBTi.
                </p>
                <span className="text-2xs text-emerald-700 font-bold block pt-1">Éligible Diag Décarbon’Action Bpifrance (4 000 € d’aide).</span>
              </div>

              <div className="p-4 rounded-2xl bg-teal-50 border border-teal-200 space-y-1">
                <div className="flex items-center space-x-2 font-bold text-teal-950">
                  <Layers className="w-4 h-4 text-teal-700" />
                  <span>2. Stratégie RSE & Reporting CSRD</span>
                </div>
                <p className="text-teal-900 leading-relaxed">
                  Transforme les qualificatifs flous ("éco-responsable", "durable") en démarche ESG auditée conforme ISO 26000 et labellisée.
                </p>
              </div>

              <div className="p-4 rounded-2xl bg-cyan-50 border border-cyan-200 space-y-1">
                <div className="flex items-center space-x-2 font-bold text-cyan-950">
                  <Leaf className="w-4 h-4 text-cyan-700" />
                  <span>3. ACV Produit (ISO 14040 / ISO 14044)</span>
                </div>
                <p className="text-cyan-900 leading-relaxed">
                  Mesure scientifique des 16 catégories d’impacts du berceau à la tombe pour prouver l’éco-conception d'un produit.
                </p>
              </div>

              <div className="p-4 rounded-2xl bg-indigo-50 border border-indigo-200 space-y-1">
                <div className="flex items-center space-x-2 font-bold text-indigo-950">
                  <FileCheck className="w-4 h-4 text-indigo-700" />
                  <span>4. FDES (Base INIES) & EPD Internationales</span>
                </div>
                <p className="text-indigo-900 leading-relaxed">
                  Indispensable pour les fabricants de matériaux (bois, isolants, menuiserie, béton) afin d'accéder aux marchés de la RE2020.
                </p>
              </div>
            </div>
          </div>

          {/* Official Bibliography / Bibliothèque des Sources */}
          <div className="space-y-3 bg-slate-50 p-5 rounded-2xl border border-slate-200">
            <h4 className="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
              <span>📚 Bibliothèque de Référence & Sources Officielles</span>
            </h4>
            <p className="text-2xs text-slate-500 mb-3">
              Retrouvez l'intégralité des textes de lois et directives officielles régissant les allégations écologiques et les bilans d'émissions.
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5 text-xs">
              <a 
                href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32024L0825" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-3.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 transition-all flex flex-col justify-between hover:shadow-2xs group"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="font-extrabold text-slate-900 group-hover:text-emerald-800 transition-colors">1. Directive EmpCO (UE) 2024/825</span>
                    <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 shrink-0" />
                  </div>
                  <p className="text-3xs text-slate-500 mt-1 leading-relaxed">
                    Modifie les directives 2005/29/CE et 2011/83/UE concernant le renforcement du pouvoir des consommateurs pour la transition verte par la protection contre le greenwashing.
                  </p>
                </div>
                <span className="text-3xs font-semibold text-emerald-800 mt-2 block">Source : EUR-Lex (Journal Officiel de l'UE)</span>
              </a>

              <a 
                href="https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000045579625" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-3.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 transition-all flex flex-col justify-between hover:shadow-2xs group"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="font-extrabold text-slate-900 group-hover:text-emerald-800 transition-colors">2. Décret Français n° 2022-539</span>
                    <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 shrink-0" />
                  </div>
                  <p className="text-3xs text-slate-500 mt-1 leading-relaxed">
                    Relatif à la compensation carbone et aux allégations de neutralité carbone dans la publicité (Article L229-68 du Code de l'environnement).
                  </p>
                </div>
                <span className="text-3xs font-semibold text-emerald-800 mt-2 block">Source : Légifrance (République Française)</span>
              </a>

              <a 
                href="https://ec.europa.eu/environment/publications/proposal-directive-green-claims_en" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-3.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 transition-all flex flex-col justify-between hover:shadow-2xs group"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="font-extrabold text-slate-900 group-hover:text-emerald-800 transition-colors">3. Directive Green Claims (Proposition)</span>
                    <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 shrink-0" />
                  </div>
                  <p className="text-3xs text-slate-500 mt-1 leading-relaxed">
                    Proposition de directive relative à la justification et à la communication des allégations environnementales explicites (ex-ante verification framework).
                  </p>
                </div>
                <span className="text-3xs font-semibold text-emerald-800 mt-2 block">Source : Commission Européenne</span>
              </a>

              <a 
                href="https://www.ecologie.gouv.fr/reglementation-environnementale-re2020" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-3.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 transition-all flex flex-col justify-between hover:shadow-2xs group"
              >
                <div>
                  <div className="flex items-center justify-between">
                    <span className="font-extrabold text-slate-900 group-hover:text-emerald-800 transition-colors">4. RE2020 & Norme NF EN 15804+A2</span>
                    <ExternalLink className="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 shrink-0" />
                  </div>
                  <p className="text-3xs text-slate-500 mt-1 leading-relaxed">
                    Normes d'Analyses du Cycle de Vie (ACV) des produits du bâtiment et base réglementaire INIES pour le dépôt des FDES et EPD.
                  </p>
                </div>
                <span className="text-3xs font-semibold text-emerald-800 mt-2 block">Source : Ministère de la Transition Écologique</span>
              </a>
            </div>
          </div>

          {/* Dictionary of Banned Words */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Dictionnaire des Mots Bannis EmpCO Référencés ({EMPCO_BANNED_WORDS.length} termes)
            </h4>
            <div className="space-y-2">
              {EMPCO_BANNED_WORDS.map((w, idx) => (
                <div key={idx} className="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                  <div className="space-y-1 md:max-w-xl">
                    <div className="flex items-center space-x-2">
                      <span className="font-black text-rose-900 bg-rose-100 px-2 py-0.5 rounded-md">
                        "{w.term}"
                      </span>
                      <span className="text-2xs font-bold uppercase text-slate-500">
                        {w.category}
                      </span>
                    </div>
                    <p className="text-slate-600">{w.legalRiskSummary}</p>
                    <span className="text-2xs text-rose-700 font-mono block">Sanction : {w.maxSanction}</span>
                  </div>

                  <div className="shrink-0 text-right">
                    <span className="text-2xs text-slate-400 block">Mission Climat Local associée</span>
                    <span className="font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 inline-block mt-0.5">
                      {w.climatLocalOfferTitle.split('(')[0]}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="sticky bottom-0 bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end">
          <button
            onClick={onClose}
            className="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-colors"
          >
            Fermer le référentiel
          </button>
        </div>
      </div>
    </div>
  );
};
