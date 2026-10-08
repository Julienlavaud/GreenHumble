import React from 'react';
import { GreenwashingAudit, AgencyProfile } from '../types.js';
import { ScoreGauge, riskLevelConfig } from './ScoreGauge.js';
import { 
  ShieldAlert, 
  Sparkles, 
  ArrowRight, 
  FileCheck2, 
  AlertOctagon, 
  Mail, 
  Phone, 
  Printer, 
  CheckCircle2,
  Scale
} from 'lucide-react';

interface ClientSummaryViewProps {
  audit: GreenwashingAudit;
  agency: AgencyProfile;
  onPrint: () => void;
  onSwitchToConsultant: () => void;
}

export const ClientSummaryView: React.FC<ClientSummaryViewProps> = ({
  audit,
  agency,
  onPrint,
  onSwitchToConsultant,
}) => {
  const isHighRisk = audit.riskLevel === 'critique' || audit.riskLevel === 'eleve';
  const riskMeta = riskLevelConfig[audit.riskLevel] || riskLevelConfig.modere;

  // Safety check to prevent crashes if audit data is incomplete
  if (!audit.clientSummary) {
    return (
      <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-4">
        <AlertOctagon className="w-12 h-12 text-slate-300 mx-auto" />
        <h3 className="text-lg font-bold text-slate-800">Données de synthèse indisponibles</h3>
        <p className="text-sm text-slate-500 max-w-md mx-auto">
          Les informations de synthèse pour ce client ne sont pas encore prêtes ou ont été corrompues.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-8 animate-fadeIn">
      {/* Top Banner with mode indicator */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between bg-amber-50/80 border border-amber-200/80 rounded-2xl p-4 sm:p-5 gap-3">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center shadow-xs">
            <Scale className="w-5 h-5" />
          </div>
          <div>
            <span className="inline-block text-xs font-bold uppercase tracking-wider text-amber-900">
              Interface Publique • Synthèse Rapide
            </span>
            <h2 className="text-base font-bold text-slate-900">
              Audit des Allégations Environnementales & Risque Greenwashing : {audit.companyName}
            </h2>
          </div>
        </div>
        <div className="flex items-center space-x-2">
          <button
            onClick={onPrint}
            className="flex items-center space-x-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 shadow-2xs transition-colors"
          >
            <Printer className="w-3.5 h-3.5" />
            <span>Imprimer la Fiche Synthèse</span>
          </button>
          <button
            onClick={onSwitchToConsultant}
            className="flex items-center space-x-1.5 px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors"
          >
            <span>Espace Cabinet {agency.agencyName}</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      {/* Main Hero Card: Note Simple & Verdict */}
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-10">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          {/* Left: Score & Risk Gauge */}
          <div className="lg:col-span-5 flex flex-col items-center justify-center p-6 bg-slate-50 rounded-3xl border border-slate-100">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">
              Score de Conformité Écologique
            </span>
            <ScoreGauge 
              score={audit.riskScore} 
              riskLevel={audit.riskLevel} 
              size="lg" 
              isUnnotable={audit.isUnnotable}
              unnotableReason={audit.unnotableReason}
            />
            <div className="mt-6 text-center">
              <span className="text-xs font-medium text-slate-500">Site analysé :</span>
              <p className="text-sm font-bold text-slate-900 break-all">{audit.cleanUrl}</p>
              <div className="mt-2 inline-flex items-center space-x-1 px-3 py-1 rounded-full bg-rose-50 text-rose-800 text-xs font-bold border border-rose-200">
                <AlertOctagon className="w-3.5 h-3.5 text-rose-600" />
                <span>{audit.totalBannedWordsDetected} allégation(s) à risque relevée(s)</span>
              </div>
            </div>
          </div>

          {/* Right: Simple Explanation for the Executive */}
          <div className="lg:col-span-7 space-y-4">
            <div>
              <span className="text-xs font-bold uppercase tracking-wider text-rose-700">
                Verdict Juridique & Réglementaire
              </span>
              <h1 className="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                {audit.clientSummary.headline}
              </h1>
              <p className="mt-3 text-slate-600 leading-relaxed text-sm sm:text-base">
                {audit.clientSummary.riskVerdict}
              </p>
            </div>

            {/* Immediate Legal Risks */}
            <div className="space-y-2 pt-2">
              <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                Principaux risques encourus :
              </span>
              <div className="space-y-1.5">
                {(audit.clientSummary.immediateRisks || []).map((risk, idx) => (
                  <div key={idx} className="flex items-start space-x-2 text-xs text-rose-900 bg-rose-50/70 p-2.5 rounded-xl border border-rose-200/60">
                    <ShieldAlert className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                    <span>{risk}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Hero Single Key Recommendation */}
      <div className="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-6 sm:p-8 shadow-md relative overflow-hidden">
        <div className="space-y-4 relative z-10">
          <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-400/30">
            <Sparkles className="w-3.5 h-3.5" />
            <span>La Recommandation Clé & Urgente</span>
          </div>

          <h3 className="text-xl sm:text-2xl font-bold tracking-tight text-white">
            {audit.clientSummary.keyUrgentAction}
          </h3>

          <p className="text-slate-300 text-sm leading-relaxed max-w-3xl">
            Remplacer les adjectifs vagues et allégations non étayées par des données certifiées 
            (<strong>Bilan Carbone® ADEME</strong>, <strong>ACV produit</strong>, ou <strong>FDES/EPD</strong> sur la base INIES). 
            Cela vous protège des contrôles DGCCRF et devient un atout décisif pour remporter des appels d’offres.
          </p>

          <div className="flex flex-wrap items-center gap-3 pt-2">
            <span className="text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/10 text-emerald-300">
              🛡️ Sécurité juridique garantie
            </span>
            <span className="text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/10 text-emerald-300">
              💶 Subvention Bpifrance Décarbon’Action jusqu'à 4 000 €
            </span>
          </div>
        </div>
      </div>

      {/* 3 Simple Action Steps */}
      <div className="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <h3 className="text-base font-bold text-slate-900 mb-1">
          Votre Plan de Mise en Conformité en 3 Étapes
        </h3>
        <p className="text-xs text-slate-500 mb-6">
          Un accompagnement rapide et subventionné avec le cabinet {agency.agencyName}.
        </p>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {(audit.clientSummary.stepsToCompliance || []).map((step) => (
            <div key={step.step} className="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
              <div className="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-black text-sm flex items-center justify-center">
                {step.step}
              </div>
              <h4 className="font-bold text-slate-900 text-sm">{step.title}</h4>
              <p className="text-xs text-slate-600 leading-relaxed">{step.description}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Contact Climat Local */}
      <div className="bg-gradient-to-r from-emerald-900 to-teal-950 text-white rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
        <div className="space-y-2 text-center md:text-left">
          <span className="text-xs font-bold uppercase tracking-wider text-emerald-300">
            Cabinet Conseil Climat & Transition Écologique
          </span>
          <h3 className="text-xl font-bold text-white">
            {agency.agencyName} - {agency.consultantName}
          </h3>
          <p className="text-xs text-emerald-100 max-w-lg">
            {agency.consultantRole} basé à {agency.agencyCity}. 
            Accompagnement Bilan Carbone®, Stratégie RSE, ACV et FDES éligible aux financements Bpifrance & ADEME.
          </p>
        </div>

        <div className="flex flex-col sm:flex-row items-center gap-3 shrink-0">
          <a
            href={`mailto:${agency.agencyEmail}?subject=Audit de conformité allégations environnementales - ${audit.cleanUrl}`}
            className="flex items-center space-x-2 px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-md transition-colors"
          >
            <Mail className="w-4 h-4" />
            <span>Sécuriser nos allégations (Échange de 20 min)</span>
          </a>
          <a
            href={`tel:${agency.agencyPhone}`}
            className="flex items-center space-x-2 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm border border-white/10 transition-colors"
          >
            <Phone className="w-4 h-4 text-emerald-400" />
            <span>{agency.agencyPhone}</span>
          </a>
        </div>
      </div>
    </div>
  );
};
