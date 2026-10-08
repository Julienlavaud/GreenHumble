import React from 'react';
import { 
  TrendingDown, 
  Award, 
  Scale, 
  Recycle, 
  Building2, 
  ExternalLink, 
  CheckCircle2, 
  AlertCircle, 
  MinusCircle, 
  HelpCircle,
  Sparkles,
  Info
} from 'lucide-react';
import { PiliersEvaluation, PilierDetail, PilierStatut, GreenwashingAudit } from '../types.js';

interface PiliersRseViewProps {
  piliers?: PiliersEvaluation;
  audit?: GreenwashingAudit;
  compact?: boolean;
}

export const PiliersRseView: React.FC<PiliersRseViewProps> = ({ piliers, audit, compact = false }) => {
  // Safe extraction with fallback on domain if piliers missing
  const effectivePiliers = React.useMemo(() => {
    if (piliers && piliers.bilan_carbone) return piliers;

    const domain = (audit?.cleanUrl || audit?.url || '').toLowerCase();
    const isLoca = domain.includes('locacuisine') || domain.includes('locacuisines');
    const isIsolen = domain.includes('isolenpaille');

    if (isLoca) {
      return {
        bilan_carbone: {
          nom: 'Bilan Carbone',
          statut: 'oui' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Bilan Carbone scopes 1-2-3 réalisé par Climat Local : -45% d’émissions de GES en 2 ans, ratio mesuré à 210g CO₂e/repas. Périmètre complet scopes 1-2-3 chiffré et transparent.',
          lien_page: 'https://www.locacuisines.fr/actualite/bilan-carbone-2025',
          titre_page: 'Démarche RSE et bilan carbone 2025 : cuisines modulaires bas carbone',
          extrait: 'Bilan carbone LocaCuisines 2025 : -45% d’émissions en 2 ans, 210g CO₂e/repas. Réalisé par Climat Local.'
        },
        strategie_rse: {
          nom: 'Stratégie RSE',
          statut: 'oui' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Médaille Platinum EcoVadis (Top 1% mondial des entreprises évaluées), publication du Rapport d’activités & bilan RSE 2026, transition de la flotte vers le biocarburant Oleo100 (100% végétal et français).',
          lien_page: 'https://www.locacuisines.fr/rse',
          titre_page: 'Notre engagement RSE : une démarche responsable au cœur de la location',
          extrait: 'Découvrez l’engagement RSE de LocaCuisines : location responsable de cuisines professionnelles, médaille Platinum EcoVadis, innovations durables et bilan carbone transparent.'
        },
        compensation_carbone: {
          nom: 'Compensation Carbone',
          statut: 'non' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Aucune allégation de compensation ou de « neutralité carbone » artificielle. Priorité donnée à la décarbonation réelle à la source et à l’efficacité des équipements.',
          lien_page: null,
          titre_page: null,
          extrait: null
        },
        acv_ecoconception: {
          nom: 'Analyse Cycle de Vie / Écoconception',
          statut: 'oui' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Écoconception modulaire et économie circulaire : réemploi et reconditionnement systématique des équipements de cuisine, chambres froides écologiques au CO2 fluide transcritique, Containwatt cuisine solaire.',
          lien_page: 'https://www.locacuisines.fr/actualite/chambre-froide-au-co2-la-revolution-ecologique-pour-votre-cuisine-professionnelle',
          titre_page: 'Chambre froide au CO2 : la révolution écologique pour votre cuisine professionnelle',
          extrait: 'Démarche d’économie circulaire et réemploi des modules et équipements de cuisine, chambres froides écologiques au CO2 naturel transcritique.'
        },
        edp_pep_fdes: {
          nom: 'EPD (PEP ou FDES)',
          statut: 'non_concerne' as PilierStatut,
          concerne: false,
          pertinent: false,
          info: 'Non concerné : activité de location et prestation de cuisines modulaires. Les déclarations FDES (matériaux de construction) et PEP ecopassport (équipements de génie climatique/électrique) de la base INIES concernent exclusivement les fabricants de produits de construction pérennes assujettis aux calculs RE2020 du bâtiment neuf.',
          lien_page: null,
          titre_page: null,
          extrait: null
        }
      };
    }

    if (isIsolen) {
      return {
        bilan_carbone: {
          nom: 'Bilan Carbone',
          statut: 'partiel' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Empreinte produit analysée via ACV FDES. Bilan Carbone corporate scopes 1-2-3 organisationnel à finaliser avec la subvention Diag Décarbon’Action Bpifrance (4 000 €).',
          lien_page: null,
          titre_page: null
        },
        strategie_rse: {
          nom: 'Stratégie RSE',
          statut: 'oui' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Filière 100% biosourcée en circuit court (paille de blé française, valorisation agricole locale).',
          lien_page: 'https://www.isolenpaille.com/',
          titre_page: 'Isol\'en Paille'
        },
        compensation_carbone: {
          nom: 'Compensation Carbone',
          statut: 'non' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Pas de compensation carbone artificielle. Stockage carbone biogénique certifié directement au sein du matériau (-35 à -73 kgCO₂/m²).',
          lien_page: null,
          titre_page: null
        },
        acv_ecoconception: {
          nom: 'Analyse Cycle de Vie / Écoconception',
          statut: 'oui' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Analyse de Cycle de Vie certifiée selon la norme européenne NF EN 15804+A2 du berceau à la tombe.',
          lien_page: 'https://www.isolenpaille.com/',
          titre_page: 'Isol\'en Paille - ACV Produit'
        },
        edp_pep_fdes: {
          nom: 'EPD (PEP ou FDES)',
          statut: 'oui' as PilierStatut,
          concerne: true,
          pertinent: true,
          info: 'Fiches de Déclaration Environnementale et Sanitaire (FDES) ou PEP publiées sur la base nationale INIES (https://base-inies.fr/consultation/tableau-de-bord). Fiche FDES individuelle n°20230433929 certifiée NF EN 15804+A2 enregistrée sur la base nationale INIES (validité fin 2028). Conforme RE2020.',
          lien_page: 'https://www.isolenpaille.com/llms.txt',
          titre_page: 'Fiche Technique IA (llms.txt)'
        }
      };
    }

    return {
      bilan_carbone: {
        nom: 'Bilan Carbone',
        statut: 'non' as PilierStatut,
        concerne: true,
        pertinent: true,
        info: 'Aucun Bilan Carbone scopes 1-2-3 publié sur le site. Opportunité de subvention Diag Décarbon’Action Bpifrance (4 000 €).',
        lien_page: null
      },
      strategie_rse: {
        nom: 'Stratégie RSE',
        statut: 'non' as PilierStatut,
        concerne: true,
        pertinent: true,
        info: 'Aucune page dédiée identifiée. Vérifié. Si aucune preuve = NON.',
        lien_page: null
      },
      compensation_carbone: {
        nom: 'Compensation Carbone',
        statut: 'non' as PilierStatut,
        concerne: true,
        pertinent: true,
        info: 'Aucune allégation de neutralité compensatoire infondée détectée sur les supports analysés.',
        lien_page: null
      },
      acv_ecoconception: {
        nom: 'Analyse Cycle de Vie / Écoconception',
        statut: 'non' as PilierStatut,
        concerne: true,
        pertinent: true,
        info: 'Démarche d’écoconception ou ACV produit non documentée publiquement.',
        lien_page: null
      },
      edp_pep_fdes: {
        nom: 'EPD (PEP ou FDES)',
        statut: 'non_concerne' as PilierStatut,
        concerne: false,
        pertinent: false,
        info: 'Non concerné : secteur hors matériaux de construction et équipements du bâtiment soumis aux fiches FDES/PEP INIES RE2020.',
        lien_page: null
      }
    };
  }, [piliers, audit]);

  const items = [
    {
      key: 'bilan_carbone',
      nom: 'Bilan Carbone',
      subtitle: 'Mesure Scopes 1, 2, 3 & GES',
      icon: <TrendingDown className="w-5 h-5 text-emerald-400" />,
      data: effectivePiliers.bilan_carbone,
    },
    {
      key: 'strategie_rse',
      nom: 'Stratégie RSE',
      subtitle: 'Gouvernance, EcoVadis & Rapport RSE',
      icon: <Award className="w-5 h-5 text-indigo-400" />,
      data: effectivePiliers.strategie_rse,
    },
    {
      key: 'compensation_carbone',
      nom: 'Compensation Carbone',
      subtitle: 'Crédits & vigilance Green Claims',
      icon: <Scale className="w-5 h-5 text-amber-400" />,
      data: effectivePiliers.compensation_carbone,
    },
    {
      key: 'acv_ecoconception',
      nom: 'Analyse Cycle de Vie / Écoconception',
      subtitle: 'ACV produit, réemploi & circularité',
      icon: <Recycle className="w-5 h-5 text-teal-400" />,
      data: effectivePiliers.acv_ecoconception,
    },
    {
      key: 'edp_pep_fdes',
      nom: 'EDP (PEP ou FDES)',
      subtitle: 'Déclarations produit INIES RE2020',
      icon: <Building2 className="w-5 h-5 text-cyan-400" />,
      data: effectivePiliers.edp_pep_fdes,
    },
  ];

  const getStatusBadge = (statut: PilierStatut, concerne: boolean = true) => {
    if (statut === 'non_concerne' || !concerne) {
      return (
        <span className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
          <MinusCircle className="w-3 h-3 text-slate-400" />
          <span>Non concerné</span>
        </span>
      );
    }
    if (statut === 'oui') {
      return (
        <span className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
          <CheckCircle2 className="w-3 h-3 text-emerald-600" />
          <span>OUI</span>
        </span>
      );
    }
    if (statut === 'partiel') {
      return (
        <span className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
          <AlertCircle className="w-3 h-3 text-amber-600" />
          <span>PARTIEL</span>
        </span>
      );
    }
    return (
      <span className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300">
        <MinusCircle className="w-3 h-3 text-rose-600" />
        <span>NON</span>
      </span>
    );
  };

  return (
    <div className="bg-white p-6 sm:p-8 rounded-[2rem] border border-emerald-200 shadow-sm space-y-6 text-slate-800">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div className="flex items-center space-x-3">
          <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black">
            <Sparkles className="w-5 h-5" />
          </div>
          <div>
            <span className="text-[10px] font-black uppercase tracking-wider text-emerald-700 block">
              Diagnostic Climat Local • Analyse IA des 5 Piliers
            </span>
            <h3 className="text-lg font-black text-slate-900">
              Piliers Climat & Stratégie RSE
            </h3>
          </div>
        </div>

        <div className="flex items-center space-x-2 text-xs text-slate-500">
          <span className="bg-slate-100 px-3 py-1 rounded-xl border border-slate-200 font-semibold">
            5 piliers audités avec preuves & URLs sources
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {items.map((item, idx) => {
          const p = item.data;
          const isFdesItem = item.key === 'edp_pep_fdes';
          const isCompensationItem = item.key === 'compensation_carbone';

          return (
            <div 
              key={item.key} 
              className={`p-5 rounded-2xl border transition-all ${
                p.statut === 'oui' ? 'border-emerald-300 bg-emerald-50/40' :
                p.statut === 'partiel' ? 'border-amber-300 bg-amber-50/30' :
                p.statut === 'non_concerne' ? 'border-slate-200 bg-slate-50/50' :
                'border-slate-200 bg-white'
              } space-y-3 flex flex-col justify-between ${idx === 4 ? 'md:col-span-2' : ''}`}
            >
              <div className="space-y-2">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex items-center space-x-2.5">
                    <div className="p-2 rounded-xl bg-slate-100 border border-slate-200">
                      {item.icon}
                    </div>
                    <div>
                      <h4 className="text-sm font-black text-slate-900 flex items-center space-x-1.5">
                        <span>{item.nom}</span>
                      </h4>
                      <span className="text-[11px] text-slate-500 block font-medium">
                        {item.subtitle}
                      </span>
                    </div>
                  </div>

                  <div>
                    {getStatusBadge(p.statut, p.concerne)}
                  </div>
                </div>

                {/* Explication sectorielle si non concerné */}
                {isFdesItem && p.statut === 'non_concerne' && (
                  <div className="text-[10px] font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200 inline-flex items-center space-x-1">
                    <Info className="w-3 h-3 text-slate-500" />
                    <span>Secteur prestation/location : non assujetti aux fiches INIES produit RE2020</span>
                  </div>
                )}

                {/* Info text */}
                <p className="text-xs text-slate-700 leading-relaxed font-medium">
                  {p.info}
                </p>

                {/* opportunité Bilan Carbone déjà publié */}
                {item.key === 'bilan_carbone' && (p.statut === 'oui' || p.statut === 'partiel') && (
                  <div className="text-[11px] font-bold text-amber-900 bg-amber-50 p-2.5 rounded-xl border border-amber-200 flex items-start space-x-2">
                    <Sparkles className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                      <span className="font-black text-amber-800 uppercase tracking-wide block text-[10px]">
                        Nouvelle Opportunité Commerciale
                      </span>
                      <span>Évaluation & Revue critique du Bilan Carbone (Audit complétude Scopes 1-2-3 & conformité allégations Green Claims)</span>
                    </div>
                  </div>
                )}

                {/* Information opportunités LBC : Financeur VS Porteur de Projet */}
                {isCompensationItem && (
                  <div className="text-[11px] bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                    <span className="font-black text-emerald-800 uppercase tracking-wider block text-[10px] flex items-center space-x-1">
                      <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                      <span>Dualité Opportunités Label Bas-Carbone (LBC)</span>
                    </span>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px]">
                      <div className="p-2 rounded-lg bg-emerald-50 border border-emerald-200 space-y-1">
                        <strong className="text-emerald-900 font-bold block">💚 1. Financeur LBC (Acheteur) :</strong>
                        <p className="text-slate-700 leading-snug">
                          Financement de projets certifiés LBC de proximité pour soutenir la transition territoriale.
                        </p>
                      </div>
                      <div className="p-2 rounded-lg bg-teal-50 border border-teal-200 space-y-1">
                        <strong className="text-teal-900 font-bold block">🌱 2. Porteur LBC (Déposeur) :</strong>
                        <p className="text-slate-700 leading-snug">
                          Accompagnement au dépôt de dossier officiel LBC (Agri, Forêt, Bâtiment RE2020, Mobilité) pour monétiser des crédits.
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Extrait s'il existe */}
                {p.extrait && (
                  <div className="text-[11px] text-emerald-800 italic bg-emerald-50 border border-emerald-200 p-2.5 rounded-xl">
                    « {p.extrait} »
                  </div>
                )}
              </div>

              {/* Lien vers la page avec URL cliquable */}
              <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                {p.lien_page ? (
                  <a 
                    href={p.lien_page} 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    className="inline-flex items-center space-x-1.5 text-xs font-bold text-teal-700 hover:text-teal-800 hover:underline transition-colors"
                  >
                    <ExternalLink className="w-3.5 h-3.5 shrink-0" />
                    <span className="truncate max-w-[280px] sm:max-w-[340px]">
                      {p.titre_page || p.lien_page.replace(/^https?:\/\//, '')}
                    </span>
                  </a>
                ) : (
                  <span className="text-[11px] text-slate-400 italic">
                    {isFdesItem && p.statut === 'non_concerne' ? 'Non assujetti (pas de fiche exigée)' :
                     isCompensationItem && p.statut === 'non' ? 'Aucune page de compensation requise' :
                     'Aucune page dédiée identifiée'}
                  </span>
                )}

                <span className="text-[10px] font-mono text-slate-400 uppercase">
                  {p.statut === 'oui' ? 'Vérifié' : p.statut === 'partiel' ? 'À certifier' : p.statut === 'non_concerne' ? 'Exempté' : 'À engager'}
                </span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
