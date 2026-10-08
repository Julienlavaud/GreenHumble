import React from 'react';
import { GreenwashingAudit, AgencyProfile } from '../types.js';
import { riskLevelConfig } from './ScoreGauge.js';
import { Scale, ShieldAlert, CheckCircle2, Award, AlertOctagon } from 'lucide-react';

interface PrintableReportProps {
  audit: GreenwashingAudit;
  agency: AgencyProfile;
}

export const PrintableReport: React.FC<PrintableReportProps> = ({ audit, agency }) => {
  if (!audit) return null;

  const riskMeta = riskLevelConfig[audit.riskLevel] || riskLevelConfig.modere;

  // Safety check to prevent crashes if audit data is incomplete
  if (!audit.clientSummary) {
    return (
      <div className="print-only max-w-4xl mx-auto p-8 text-black bg-white space-y-8 font-sans">
        <div className="p-8 text-center border-2 border-dashed border-slate-200 rounded-3xl">
          <p className="text-slate-500 font-bold">Données de synthèse indisponibles pour ce rapport.</p>
        </div>
      </div>
    );
  }

  const detectedClaims = Array.isArray(audit.detectedClaims) ? audit.detectedClaims : [];
  const businessOpportunities = Array.isArray(audit.businessOpportunities) ? audit.businessOpportunities : [];
  const immediateRisks = Array.isArray(audit.clientSummary?.immediateRisks) ? audit.clientSummary.immediateRisks : [];
  const stepsToCompliance = Array.isArray(audit.clientSummary?.stepsToCompliance) ? audit.clientSummary.stepsToCompliance : [];

  return (
    <div className="print-only max-w-4xl mx-auto p-8 text-black bg-white space-y-8 font-sans">
      {/* PAGE 1: SYNTHÈSE DIRECTION & RISQUE JURIDIQUE */}
      <div className="space-y-6">
        {/* Letterhead */}
        <div className="flex items-center justify-between pb-6 border-b-2 border-slate-900">
          <div className="flex items-center space-x-3">
            <div className="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold">
              <Scale className="w-7 h-7" />
            </div>
            <div>
              <h1 className="text-2xl font-black tracking-tight text-slate-950 font-serif">
                {agency?.agencyName || 'Cabinet Expert Climat Local'}
              </h1>
              <p className="text-xs text-slate-600 font-semibold">{agency?.slogan || 'Accompagnement & Sécurisation Environnementale'}</p>
            </div>
          </div>
          <div className="text-right text-xs text-slate-600">
            <p className="font-bold text-slate-900">{agency?.consultantName || 'Auditeur Climat Local'}</p>
            <p>{agency?.consultantRole || 'Consultant Expert'}</p>
            <p>{agency?.agencyPhone || ''} • {agency?.agencyEmail || ''}</p>
            <p className="text-slate-400 mt-1">Dossier N° {audit.id || 'N/A'}</p>
          </div>
        </div>

        {/* Title */}
        <div className="text-center space-y-1">
          <span className="text-xs font-bold uppercase tracking-widest text-amber-900 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
            Rapport Confidentiel • Audit de Conformité aux Allégations Environnementales
          </span>
          <h2 className="text-2xl font-black text-slate-900 pt-2">
            Évaluation Greenwashing : {audit.companyName || audit.cleanUrl || 'Entreprise Auditée'}
          </h2>
          <p className="text-xs text-slate-500 font-mono">
            URL : {audit.url || audit.cleanUrl} • Date : {audit.analyzedAt ? new Date(audit.analyzedAt).toLocaleDateString('fr-FR') : new Date().toLocaleDateString('fr-FR')} • Référentiel Mots bannis EmpCO (Directive UE 2024/825) / Directive Green Claims
          </p>
        </div>

        {/* Risk Card */}
        <div className="grid grid-cols-12 gap-6 p-6 rounded-2xl border-2 border-slate-200 bg-slate-50 items-center">
          <div className="col-span-4 flex flex-col items-center justify-center border-r border-slate-200 pr-4 text-center">
            <div
              className={`w-24 h-24 rounded-2xl ${riskMeta.bg} text-white flex flex-col items-center justify-center shadow-md`}
            >
              <span className="text-3xl font-black leading-none">{audit.riskScore ?? 0}</span>
              <span className="text-3xs uppercase font-bold opacity-80 mt-1">/ 100</span>
            </div>
            <span className="mt-2 text-xs font-bold text-slate-800">
              {riskMeta.label}
            </span>
            <span className="text-2xs font-semibold text-rose-700 mt-1">
              {audit.totalBannedWordsDetected ?? detectedClaims.length} allégation(s) à risque
            </span>
          </div>

          <div className="col-span-8 space-y-3 pl-2">
            <h3 className="text-sm font-bold uppercase tracking-wider text-rose-900">
              Verdict Réglementaire pour la Direction
            </h3>
            <p className="text-xs text-slate-700 leading-relaxed font-semibold">
              {audit.clientSummary?.headline || "Synthèse en cours..."}
            </p>
            <p className="text-xs text-slate-600 leading-relaxed">
              {audit.clientSummary?.riskVerdict || ""}
            </p>
            <div className="pt-2 text-2xs space-y-1">
              {immediateRisks.slice(0, 2).map((r, i) => (
                <div key={i} className="text-rose-900 font-medium">
                  ⚠️ {r}
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Hero Single Key Recommendation */}
        <div className="p-5 rounded-2xl bg-slate-900 text-white space-y-2">
          <span className="text-2xs font-bold uppercase tracking-wider text-emerald-400">
            Recommandation Clé & Action Urgente
          </span>
          <h4 className="text-base font-bold text-white">{audit.clientSummary?.keyUrgentAction || "Audit approfondi requis"}</h4>
          <p className="text-xs text-slate-300 leading-relaxed">
            Régulariser par la preuve : Bilan Carbone® certifié ADEME, FDES enregistrée sur la base INIES, ou ACV produit normée ISO 14040/44.
          </p>
        </div>

        {/* Simple 3-step action plan */}
        <div className="p-4 rounded-xl border border-slate-200 space-y-2">
          <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">
            Plan d’Accompagnement Climat Local pour Sécuriser l’Entreprise
          </h4>
          <div className="grid grid-cols-3 gap-2 text-2xs text-slate-600">
            {stepsToCompliance.length > 0 ? (
              stepsToCompliance.map((step) => (
                <div key={step.step} className="p-2.5 bg-slate-50 rounded-lg space-y-1">
                  <strong className="text-slate-900 block">{step.step}. {step.title}</strong>
                  <p>{step.description}</p>
                </div>
              ))
            ) : (
              <p className="col-span-3 text-center py-4">Plan d'action non disponible</p>
            )}
          </div>
        </div>
      </div>

      {/* PAGE BREAK FOR PRINT */}
      <div className="page-break-before space-y-6 pt-6">
        <div className="flex items-center justify-between pb-3 border-b border-slate-200">
          <span className="text-xs font-bold text-slate-400">Relevé des Infractions & Opportunités Business • {audit.cleanUrl || audit.url}</span>
          <span className="text-xs text-slate-400">Page 2 / 2</span>
        </div>

        {/* Claims Table */}
        <div className="space-y-3">
          <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900">
            Détail des Allégations Interdites Relevées sur le Site ({detectedClaims.length})
          </h3>
          <div className="space-y-2 text-xs">
            {detectedClaims.length > 0 ? (
              detectedClaims.map((claim, idx) => (
                <div key={idx} className="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-rose-900 bg-rose-100 px-2 py-0.5 rounded text-2xs">
                      Terme : "{claim.term}"
                    </span>
                    <span className="text-2xs text-slate-500 font-mono">{claim.lawArticle}</span>
                  </div>
                  <p className="text-slate-800 italic text-2xs">Extrait : "{claim.quoteInText}"</p>
                  <div className="flex justify-between text-2xs pt-1">
                    <span className="text-rose-700 font-semibold">{claim.sanction}</span>
                    <span className="text-emerald-800 font-bold">Solution : {(claim.recommendedService || 'Audit & Bilan Carbone').toUpperCase()}</span>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-slate-500 italic p-3 text-center">Aucune allégation interdite détectée.</p>
            )}
          </div>
        </div>

        {/* Business Opportunities / Subsidies */}
        <div className="space-y-3 pt-2">
          <h3 className="text-sm font-bold uppercase tracking-wider text-slate-900">
            Missions Recommandées par Climat Local & Financement
          </h3>
          <div className="grid grid-cols-2 gap-3 text-xs">
            {businessOpportunities.length > 0 ? (
              businessOpportunities.slice(0, 2).map((opp, idx) => (
                <div key={idx} className="p-3.5 rounded-xl border border-emerald-300 bg-emerald-50/50 space-y-1.5">
                  <strong className="text-emerald-950 font-bold block">{opp.serviceTitle}</strong>
                  <p className="text-2xs text-slate-600">{opp.reason}</p>
                  <div className="text-2xs font-bold text-slate-900 pt-1">
                    Budget : {opp.potentialBudget}
                  </div>
                  <span className="text-2xs text-emerald-800 font-semibold block">
                    {opp.subsidiesEligible}
                  </span>
                </div>
              ))
            ) : (
              <p className="col-span-2 text-slate-500 italic p-3 text-center">Aucune mission identifiée.</p>
            )}
          </div>
        </div>

        {/* Consultant signature stamp */}
        <div className="pt-8 flex justify-between items-end border-t border-slate-200 text-xs text-slate-600">
          <div>
            <p className="font-bold text-slate-900">{agency?.agencyName || 'Cabinet Expert Climat Local'}</p>
            <p>{agency?.consultantName || 'Auditeur'} • {agency?.consultantRole || 'Consultant'}</p>
            <p>{agency?.agencyCity || 'France'} • {agency?.agencyWebsite || ''}</p>
          </div>
          <div className="text-right border-t border-slate-400 pt-2 px-6">
            <p className="text-2xs text-slate-400">Visa de l’auditeur :</p>
            <p className="font-serif italic font-bold text-slate-800 text-sm mt-1">{agency?.consultantName || 'Climat Local'}</p>
          </div>
        </div>
      </div>
    </div>
  );
};
