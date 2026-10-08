import { AuditTask, RoleAssignment, AuditHistoryLog, Collaborator } from '../types.js';

export function createDefaultTasks(companyName?: string): AuditTask[] {
  const now = new Date().toISOString();
  return [
    {
      id: 'task_qualif',
      title: "Qualifier le profil d'entreprise (secteur, effectif, région)",
      category: 'qualification',
      isDone: true,
      completedAt: now,
      completedBy: 'Julien Lavaud',
      mandatory: true,
      notes: 'Données extraites automatiquement et qualifiées lors du diagnostic initial.',
    },
    {
      id: 'task_audit_claims',
      title: 'Vérifier les allégations détectées au regard de la Directive (UE) 2024/825',
      category: 'audit_juridique',
      isDone: true,
      completedAt: now,
      completedBy: 'Système Climat Local',
      mandatory: true,
      notes: 'Analyse légale des risques de sanctions DGCCRF (jusqu’à 100 000 €).',
    },
    {
      id: 'task_val_assignment',
      title: 'Valider formellement l’attribution du dossier au consultant référent',
      category: 'qualification',
      isDone: false,
      mandatory: true,
      notes: 'Validation requise par le lead de mission ou l’administrateur.',
    },
    {
      id: 'task_phone_contact',
      title: 'Premier appel de qualification téléphonique avec le dirigeant (Pitch 15 min)',
      category: 'contact_dirigeant',
      isDone: false,
      mandatory: true,
      notes: 'Utiliser l’accroche juridique sans agressivité (posture de tiers de confiance).',
    },
    {
      id: 'task_present_risk',
      title: 'Présentation du risque DGCCRF et remise de la synthèse client',
      category: 'audit_juridique',
      isDone: false,
      mandatory: false,
      notes: 'Sensibiliser le client à la mise en conformité de ses écrits web.',
    },
    {
      id: 'task_prop_bc',
      title: 'Proposition Bilan Carbone® & Décarbon’Action (subvention 4 000 € ADEME)',
      category: 'proposition_mission',
      isDone: false,
      mandatory: false,
      notes: 'Priorité pour les entreprises sans bilan Scope 1, 2, 3.',
    },
    {
      id: 'task_prop_rse',
      title: 'Proposition Raison d’Être (Loi PACTE) & Sensibilisation des équipes (OPCO)',
      category: 'proposition_mission',
      isDone: false,
      mandatory: false,
      notes: 'Particulièrement adapté aux entreprises de services et tertiaires.',
    },
    {
      id: 'task_prop_carbon',
      title: 'Proposition Contribution Carbone Locale (fermes & haies sur climatlocal.com)',
      category: 'proposition_mission',
      isDone: false,
      mandatory: false,
      notes: 'Alternative vertueuse à la compensation lointaine illégale.',
    },
    {
      id: 'task_devis_sent',
      title: 'Établissement et envoi de la proposition commerciale / devis',
      category: 'cloture',
      isDone: false,
      mandatory: true,
      notes: 'Préciser les aides mobilisables (Bpifrance, OPCO, Région).',
    },
    {
      id: 'task_signed',
      title: 'Signature de la lettre de mission et démarrage opérationnel',
      category: 'cloture',
      isDone: false,
      mandatory: true,
      notes: 'Validation du bon de commande et calendrier d’audit.',
    },
  ];
}

export function createDefaultRoleAssignments(
  leadName = 'Julien Lavaud',
  leadId = 'collab_1'
): RoleAssignment[] {
  const now = new Date().toISOString();
  return [
    {
      id: 'role_lead',
      roleKey: 'lead_referent',
      roleTitle: 'Consultant Référent (Lead Dossier)',
      collaboratorId: leadId,
      collaboratorName: leadName,
      assignedAt: now,
      status: 'validee',
      validatedBy: 'Julien Lavaud',
      validatedAt: now,
      notes: 'Responsable du pilotage commercial et de la relation client.',
    },
    {
      id: 'role_bc',
      roleKey: 'auditeur_carbone',
      roleTitle: 'Auditeur Bilan Carbone® & ACV',
      collaboratorId: 'collab_2',
      collaboratorName: 'Claire Delmas',
      assignedAt: now,
      status: 'en_attente',
      notes: 'En attente de confirmation de disponibilité pour le calcul d’empreinte.',
    },
    {
      id: 'role_rse',
      roleKey: 'expert_rse',
      roleTitle: 'Expert Raison d’Être & RSE',
      collaboratorId: 'collab_3',
      collaboratorName: 'Thomas Renoir',
      assignedAt: now,
      status: 'en_attente',
      notes: 'Mobilisable pour la formalisation des engagements Loi PACTE.',
    },
    {
      id: 'role_carbon',
      roleKey: 'projets_locaux',
      roleTitle: 'Chargé Projets Carbone Territoriaux',
      collaboratorId: leadId,
      collaboratorName: leadName,
      assignedAt: now,
      status: 'validee',
      validatedBy: 'Julien Lavaud',
      validatedAt: now,
      notes: 'Sélection des fermes et haies locales sur climatlocal.com.',
    },
  ];
}

export function createDefaultHistoryLogs(
  companyName: string,
  authorName = 'Julien Lavaud'
): AuditHistoryLog[] {
  const now = new Date().toISOString();
  const past5min = new Date(Date.now() - 5 * 60 * 1000).toISOString();
  const past15min = new Date(Date.now() - 15 * 60 * 1000).toISOString();

  return [
    {
      id: 'log_' + Date.now() + '_1',
      timestamp: now,
      authorName: authorName,
      actionType: 'role_validated',
      description: `Attribution du rôle "Consultant Référent" validée pour ${authorName}.`,
      details: 'Validation formelle pour la prise en charge du dossier.',
    },
    {
      id: 'log_' + Date.now() + '_2',
      timestamp: past5min,
      authorName: 'Système Climat Local',
      actionType: 'task_completed',
      description: 'Action "Vérifier les allégations détectées" marquée comme FAITE.',
      details: 'Conformité analysée selon la Directive (UE) 2024/825.',
    },
    {
      id: 'log_' + Date.now() + '_3',
      timestamp: past15min,
      authorName: 'Système Climat Local',
      actionType: 'audit_created',
      description: `Audit Greenwashing généré pour ${companyName}.`,
      details: 'Initialisation du diagnostic, calcul du score de risque et opportunités B2B.',
    },
  ];
}
