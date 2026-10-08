import React from 'react';
import { RiskLevel } from '../types.js';
import { ShieldAlert, AlertTriangle, ShieldCheck } from 'lucide-react';

interface ScoreGaugeProps {
  score: number | null | undefined; // 0 to 100 or null if impossible to note
  riskLevel: RiskLevel;
  size?: 'sm' | 'md' | 'lg';
  showDetails?: boolean;
  isUnnotable?: boolean;
  unnotableReason?: string;
}

export const riskLevelConfig: Record<
  RiskLevel,
  { label: string; bg: string; text: string; border: string; badge: string; desc: string }
> = {
  critique: {
    label: 'Risque Juridique Critique',
    bg: 'bg-rose-600',
    text: 'text-rose-700',
    border: 'border-rose-300',
    badge: 'bg-rose-100 text-rose-900 border-rose-200',
    desc: 'Infractions directes au Décret 2022-539 ou Directive Green Claims (sanctions DGCCRF jusqu’à 100 000 €).',
  },
  eleve: {
    label: 'Risque Juridique Élevé',
    bg: 'bg-orange-500',
    text: 'text-orange-700',
    border: 'border-orange-300',
    badge: 'bg-orange-100 text-orange-900 border-orange-200',
    desc: 'Allégations vagues ou non étayées par une ACV ou Bilan Carbone audité.',
  },
  modere: {
    label: 'Risque Modéré à Préciser',
    bg: 'bg-amber-500',
    text: 'text-amber-700',
    border: 'border-amber-300',
    badge: 'bg-amber-100 text-amber-900 border-amber-200',
    desc: 'Démarche visible mais allégations manquant de preuves publiques certifiées.',
  },
  faible: {
    label: 'Conformité & Données Étayées',
    bg: 'bg-emerald-600',
    text: 'text-emerald-700',
    border: 'border-emerald-300',
    badge: 'bg-emerald-100 text-emerald-900 border-emerald-200',
    desc: 'Données chiffrées sans allégations trompeuses de neutralité.',
  },
};

export const ScoreGauge: React.FC<ScoreGaugeProps> = ({
  score,
  riskLevel,
  size = 'md',
  showDetails = true,
  isUnnotable,
  unnotableReason,
}) => {
  const isNullScore = score === null || score === undefined || isNaN(score as number) || isUnnotable;
  const config = riskLevelConfig[riskLevel] || riskLevelConfig.modere;

  const sizeClasses = {
    sm: { box: 'w-12 h-12 text-xs font-black', icon: 'w-5 h-5' },
    md: { box: 'w-20 h-20 text-lg font-black', icon: 'w-8 h-8' },
    lg: { box: 'w-28 h-28 text-2xl font-black', icon: 'w-12 h-12' },
  }[size];

  if (isNullScore) {
    return (
      <div className="flex flex-col items-center select-none text-center">
        {/* Box */}
        <div
          className={`${sizeClasses.box} bg-slate-800 text-slate-300 border border-slate-700 rounded-3xl flex flex-col items-center justify-center shadow-lg transition-transform hover:scale-105 p-2`}
        >
          <span className="leading-tight font-black text-amber-400">N/A</span>
          <span className="text-[9px] uppercase tracking-wider font-bold text-slate-400 mt-0.5">Non noté</span>
        </div>

        {showDetails && (
          <div className="mt-3 text-center space-y-1">
            <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border bg-amber-950/60 text-amber-300 border-amber-800/60">
              <AlertTriangle className="w-3.5 h-3.5 mr-1 text-amber-400" />
              Impossible à noter (Site Indisponible)
            </span>
            <p className="text-2xs text-slate-400 max-w-xs leading-normal">
              {unnotableReason || "Le site web renvoie une erreur 301/indisponibilité. Aucune allégation environnementale n'a pu être auditée."}
            </p>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center">
      {/* Box */}
      <div
        className={`${sizeClasses.box} ${config.bg} text-white rounded-3xl flex flex-col items-center justify-center shadow-lg transition-transform hover:scale-105 select-none text-center p-2`}
      >
        <span className="leading-none text-2xl font-black">{score}</span>
        <span className="text-3xs uppercase tracking-wider font-bold opacity-80 mt-0.5">/ 100</span>
      </div>

      {showDetails && (
        <div className="mt-3 text-center space-y-1">
          <span
            className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${config.badge}`}
          >
            {riskLevel === 'critique' || riskLevel === 'eleve' ? (
              <ShieldAlert className="w-3.5 h-3.5 mr-1 text-rose-600" />
            ) : (
              <ShieldCheck className="w-3.5 h-3.5 mr-1 text-emerald-600" />
            )}
            {config.label}
          </span>
          <p className="text-2xs text-slate-500 max-w-xs">{config.desc}</p>
        </div>
      )}
    </div>
  );
};
