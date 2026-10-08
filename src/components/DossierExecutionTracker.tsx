import React, { useState } from 'react';
import { 
  GreenwashingAudit, 
  Collaborator, 
  RoleAssignment, 
  AuditTask, 
  AuditHistoryLog,
  AgencyProfile 
} from '../types.js';
import { 
  CheckCircle2, 
  Circle, 
  Clock, 
  UserCheck, 
  ShieldCheck, 
  AlertCircle, 
  Plus, 
  History, 
  UserPlus, 
  Check, 
  X, 
  Filter, 
  Send,
  Calendar,
  Sparkles,
  ArrowRight,
  ClipboardList,
  Target
} from 'lucide-react';

interface DossierExecutionTrackerProps {
  audit: GreenwashingAudit;
  collaborators: Collaborator[];
  agency: AgencyProfile;
  onUpdateAudit: (updatedAudit: GreenwashingAudit) => void;
}

export const DossierExecutionTracker: React.FC<DossierExecutionTrackerProps> = ({
  audit,
  collaborators,
  agency,
  onUpdateAudit,
}) => {
  const [taskFilter, setTaskFilter] = useState<'all' | 'todo' | 'done'>('all');
  const [newTaskTitle, setNewTaskTitle] = useState('');
  const [newTaskCategory, setNewTaskCategory] = useState<AuditTask['category']>('contact_dirigeant');
  const [isAddingTask, setIsAddingTask] = useState(false);
  const [newLogNote, setNewLogNote] = useState('');
  const [activeSubTab, setActiveSubTab] = useState<'tasks' | 'roles' | 'history'>('tasks');

  const currentTasks = audit.tasks || [];
  const currentRoles = audit.roleAssignments || [];
  const currentHistory = audit.historyLogs || [];

  const completedTasksCount = currentTasks.filter((t) => t.isDone).length;
  const totalTasksCount = currentTasks.length;
  const progressPercent = totalTasksCount > 0 ? Math.round((completedTasksCount / totalTasksCount) * 100) : 0;

  // Toggle Task Fait / Pas fait
  const handleToggleTask = (taskId: string) => {
    const updatedTasks = currentTasks.map((task) => {
      if (task.id === taskId) {
        const nextState = !task.isDone;
        return {
          ...task,
          isDone: nextState,
          completedAt: nextState ? new Date().toISOString() : undefined,
          completedBy: nextState ? agency.consultantName : undefined,
        };
      }
      return task;
    });

    const changedTask = currentTasks.find((t) => t.id === taskId);
    const wasDone = changedTask?.isDone;

    const newLog: AuditHistoryLog = {
      id: 'log_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
      timestamp: new Date().toISOString(),
      authorName: agency.consultantName,
      actionType: wasDone ? 'task_uncompleted' : 'task_completed',
      description: wasDone
        ? `Action remise à faire : "${changedTask?.title}"`
        : `Action validée comme FAITE : "${changedTask?.title}"`,
      details: wasDone ? 'Statut réinitialisé à "Pas fait".' : `Effectuée par ${agency.consultantName}.`,
    };

    onUpdateAudit({
      ...audit,
      tasks: updatedTasks,
      historyLogs: [newLog, ...currentHistory],
    });
  };

  // Add Custom Task
  const handleAddTask = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTaskTitle.trim()) return;

    const newTask: AuditTask = {
      id: 'task_' + Date.now(),
      title: newTaskTitle.trim(),
      category: newTaskCategory,
      isDone: false,
    };

    const newLog: AuditHistoryLog = {
      id: 'log_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
      timestamp: new Date().toISOString(),
      authorName: agency.consultantName,
      actionType: 'task_added',
      description: `Nouvelle action ajoutée : "${newTask.title}"`,
      details: `Catégorie : ${newTask.category}`,
    };

    onUpdateAudit({
      ...audit,
      tasks: [...currentTasks, newTask],
      historyLogs: [newLog, ...currentHistory],
    });

    setNewTaskTitle('');
    setIsAddingTask(false);
  };

  // Validate or Change Role Assignment
  const handleValidateRole = (roleId: string, isValidated: boolean) => {
    const updatedRoles = currentRoles.map((role) => {
      if (role.id === roleId) {
        return {
          ...role,
          status: isValidated ? ('validee' as const) : ('refusee' as const),
          validatedBy: agency.consultantName,
          validatedAt: new Date().toISOString(),
        };
      }
      return role;
    });

    const targetRole = currentRoles.find((r) => r.id === roleId);

    const newLog: AuditHistoryLog = {
      id: 'log_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
      timestamp: new Date().toISOString(),
      authorName: agency.consultantName,
      actionType: isValidated ? 'role_validated' : 'role_rejected',
      description: isValidated
        ? `Attribution du rôle "${targetRole?.roleTitle}" validée pour ${targetRole?.collaboratorName}`
        : `Attribution du rôle "${targetRole?.roleTitle}" refusée / remise en attente`,
      details: `Opération réalisée par ${agency.consultantName} le ${new Date().toLocaleDateString('fr-FR')}`,
    };

    onUpdateAudit({
      ...audit,
      roleAssignments: updatedRoles,
      historyLogs: [newLog, ...currentHistory],
    });
  };

  // Reassign Collaborator to Role
  const handleChangeRoleCollaborator = (roleId: string, newCollaboratorId: string) => {
    const collab = collaborators.find((c) => c.id === newCollaboratorId);
    if (!collab) return;

    const updatedRoles = currentRoles.map((role) => {
      if (role.id === roleId) {
        return {
          ...role,
          collaboratorId: collab.id,
          collaboratorName: collab.name,
          status: 'en_attente' as const,
          assignedAt: new Date().toISOString(),
          validatedBy: undefined,
          validatedAt: undefined,
        };
      }
      return role;
    });

    const targetRole = currentRoles.find((r) => r.id === roleId);

    const newLog: AuditHistoryLog = {
      id: 'log_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
      timestamp: new Date().toISOString(),
      authorName: agency.consultantName,
      actionType: 'role_assigned',
      description: `Rôle "${targetRole?.roleTitle}" réattribué à ${collab.name}`,
      details: `En attente de validation formelle.`,
    };

    onUpdateAudit({
      ...audit,
      roleAssignments: updatedRoles,
      historyLogs: [newLog, ...currentHistory],
    });
  };

  // Add Manual Note to History Log
  const handleAddHistoryNote = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newLogNote.trim()) return;

    const newLog: AuditHistoryLog = {
      id: 'log_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
      timestamp: new Date().toISOString(),
      authorName: agency.consultantName,
      actionType: 'note_added',
      description: newLogNote.trim(),
      details: 'Note de suivi consignée manuellement dans le journal d’audit.',
    };

    onUpdateAudit({
      ...audit,
      historyLogs: [newLog, ...currentHistory],
    });

    setNewLogNote('');
  };

  const filteredTasks = currentTasks.filter((t) => {
    if (taskFilter === 'todo') return !t.isDone;
    if (taskFilter === 'done') return t.isDone;
    return true;
  });

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-6 p-6 sm:p-8">
      {/* Top Header with Progress and Sub-tabs */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
        <div className="space-y-1">
          <div className="flex items-center space-x-2">
            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-200">
              Traçabilité & Exécution du Dossier
            </span>
            <span className="text-xs text-slate-500">
              {audit.companyName}
            </span>
          </div>
          <h2 className="text-xl sm:text-2xl font-black text-slate-900">
            Attribution des Rôles, Suivi des Tâches & Historique
          </h2>
          <p className="text-xs text-slate-500">
            Validez les consultants affectés, cochez les actions menées (Fait / Pas fait) et conservez un journal d’audit inaltérable.
          </p>
        </div>

        {/* Global Progress Bar */}
        <div className="bg-slate-50 border border-slate-200 rounded-2xl p-4 min-w-[260px] space-y-2">
          <div className="flex items-center justify-between text-xs font-bold">
            <span className="text-slate-700">Avancement Global :</span>
            <span className="text-emerald-700 font-black">
              {completedTasksCount} / {totalTasksCount} ({progressPercent}%)
            </span>
          </div>
          <div className="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
            <div
              className="bg-emerald-600 h-full rounded-full transition-all duration-500"
              style={{ width: `${progressPercent}%` }}
            />
          </div>
          <div className="flex items-center justify-between text-3xs text-slate-500 pt-1">
            <span>{currentTasks.filter((t) => !t.isDone).length} action(s) restante(s)</span>
            <span className="text-emerald-600 font-bold">{completedTasksCount} validée(s)</span>
          </div>
        </div>
      </div>

      {/* Sub-Tabs: 1. Actions Fait/Pas fait | 2. Validation des Rôles | 3. Historique */}
      <div className="flex flex-wrap gap-2 border-b border-slate-100 pb-3">
        <button
          onClick={() => setActiveSubTab('tasks')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all ${
            activeSubTab === 'tasks'
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 text-slate-600 hover:text-slate-900'
          }`}
        >
          <ClipboardList className="w-4 h-4" />
          <span>1. Actions Fait / Pas fait ({completedTasksCount}/{totalTasksCount})</span>
        </button>

        <button
          onClick={() => setActiveSubTab('roles')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all ${
            activeSubTab === 'roles'
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 text-slate-600 hover:text-slate-900'
          }`}
        >
          <UserCheck className="w-4 h-4" />
          <span>2. Attributions de Rôles & Validations ({currentRoles.filter(r => r.status === 'validee').length}/{currentRoles.length})</span>
        </button>

        <button
          onClick={() => setActiveSubTab('history')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all ${
            activeSubTab === 'history'
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 text-slate-600 hover:text-slate-900'
          }`}
        >
          <History className="w-4 h-4" />
          <span>3. Journal Historique & Traçabilité ({currentHistory.length})</span>
        </button>
      </div>

      {/* SUB-TAB 1: CHECKLIST DES ACTIONS (FAIT / PAS FAIT) */}
      {activeSubTab === 'tasks' && (
        <div className="space-y-6">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            {/* Filter Pills */}
            <div className="flex items-center space-x-1.5 bg-slate-100 p-1 rounded-xl">
              <button
                onClick={() => setTaskFilter('all')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                  taskFilter === 'all'
                    ? 'bg-white text-slate-900 shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                Toutes ({currentTasks.length})
              </button>
              <button
                onClick={() => setTaskFilter('todo')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                  taskFilter === 'todo'
                    ? 'bg-amber-100 text-amber-900 font-extrabold shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                À faire / Pas fait ({currentTasks.filter((t) => !t.isDone).length})
              </button>
              <button
                onClick={() => setTaskFilter('done')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                  taskFilter === 'done'
                    ? 'bg-emerald-100 text-emerald-900 font-extrabold shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                Faites ({completedTasksCount})
              </button>
            </div>

            {/* Add Task Button */}
            <button
              onClick={() => setIsAddingTask(!isAddingTask)}
              className="flex items-center space-x-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-colors"
            >
              <Plus className="w-4 h-4" />
              <span>{isAddingTask ? 'Fermer le formulaire' : 'Ajouter une action'}</span>
            </button>
          </div>

          {/* New Task Inline Form */}
          {isAddingTask && (
            <form onSubmit={handleAddTask} className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3 animate-fadeIn">
              <div className="font-bold text-slate-900 text-xs flex items-center space-x-2">
                <Sparkles className="w-4 h-4 text-emerald-600" />
                <span>Ajouter une action sur mesure pour ce client</span>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="sm:col-span-2">
                  <input
                    type="text"
                    required
                    value={newTaskTitle}
                    onChange={(e) => setNewTaskTitle(e.target.value)}
                    placeholder="ex: Rappeler la directrice RSE mardi à 10h pour le devis Bilan Carbone"
                    className="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl bg-white"
                  />
                </div>
                <div>
                  <select
                    value={newTaskCategory}
                    onChange={(e) => setNewTaskCategory(e.target.value as any)}
                    className="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl bg-white"
                  >
                    <option value="qualification">Qualification Entreprise</option>
                    <option value="audit_juridique">Audit Juridique</option>
                    <option value="contact_dirigeant">Contact Dirigeant</option>
                    <option value="proposition_mission">Proposition Mission</option>
                    <option value="cloture">Clôture & Signature</option>
                  </select>
                </div>
              </div>
              <div className="flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setIsAddingTask(false)}
                  className="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-700 font-semibold"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs"
                >
                  Enregistrer l'action
                </button>
              </div>
            </form>
          )}

          {/* Tasks List */}
          <div className="space-y-2.5">
            {filteredTasks.length === 0 ? (
              <div className="text-center py-8 text-slate-400 text-xs italic">
                Aucune action ne correspond à ce filtre.
              </div>
            ) : (
              filteredTasks.map((task) => (
                <div
                  key={task.id}
                  onClick={() => handleToggleTask(task.id)}
                  className={`p-3.5 sm:p-4 rounded-2xl border transition-all cursor-pointer flex items-start justify-between gap-4 ${
                    task.isDone
                      ? 'bg-emerald-50/70 border-emerald-200 hover:bg-emerald-50'
                      : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                  }`}
                >
                  <div className="flex items-start space-x-3">
                    <button
                      type="button"
                      className={`mt-0.5 w-5 h-5 rounded-lg flex items-center justify-center transition-colors shrink-0 ${
                        task.isDone
                          ? 'bg-emerald-600 text-white shadow-xs'
                          : 'border-2 border-slate-300 hover:border-emerald-600 bg-white'
                      }`}
                    >
                      {task.isDone ? <Check className="w-3.5 h-3.5 stroke-[3]" /> : null}
                    </button>

                    <div className="space-y-1">
                      <div className="flex items-center space-x-2">
                        <span
                          className={`text-xs font-bold leading-snug ${
                            task.isDone ? 'line-through text-slate-400 font-semibold' : 'text-slate-900 font-bold'
                          }`}
                        >
                          {task.title}
                        </span>
                        {task.mandatory && (
                          <span className="text-3xs font-extrabold uppercase px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            Étape clé
                          </span>
                        )}
                      </div>

                      {task.notes && (
                        <p className="text-2xs text-slate-500 leading-relaxed">
                          {task.notes}
                        </p>
                      )}

                      {task.isDone && task.completedBy && (
                        <div className="flex items-center space-x-2 text-3xs text-emerald-800 pt-0.5 font-semibold">
                          <CheckCircle2 className="w-3 h-3 text-emerald-600 shrink-0" />
                          <span>
                            Validé par <strong>{task.completedBy}</strong>
                            {task.completedAt && ` le ${new Date(task.completedAt).toLocaleDateString('fr-FR')} à ${new Date(task.completedAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Status Badge & Quick Action */}
                  <div className="shrink-0 flex items-center space-x-2">
                    {task.isDone ? (
                      <span className="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-600 text-white uppercase tracking-wider flex items-center space-x-1 shadow-xs">
                        <Check className="w-3 h-3" />
                        <span>Fait</span>
                      </span>
                    ) : (
                      <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200 uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-colors">
                        À faire (Pas fait)
                      </span>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}

      {/* SUB-TAB 2: VALIDATION DES ATTRIBUTIONS DE RÔLES */}
      {activeSubTab === 'roles' && (
        <div className="space-y-6">
          <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-700 flex items-start space-x-3">
            <UserCheck className="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" />
            <div className="space-y-1">
              <strong className="text-slate-900 font-bold block">
                Validation & Responsabilités Métier
              </strong>
              <p>
                Chaque dossier d'audit nécessite la validation formelle des rôles : le <strong>Consultant Référent</strong> pilote le prospect, tandis que les experts (Bilan Carbone®, Raison d’Être, Projets Locaux) valident leur prise en charge technique.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {currentRoles.map((role) => {
              const assignedCollab = collaborators.find((c) => c.id === role.collaboratorId);
              const isValidated = role.status === 'validee';

              return (
                <div
                  key={role.id}
                  className={`p-5 rounded-2xl border transition-all space-y-4 ${
                    isValidated
                      ? 'bg-white border-emerald-300 shadow-sm'
                      : 'bg-amber-50/50 border-amber-300 shadow-xs'
                  }`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <span className="text-2xs font-extrabold uppercase tracking-wider text-slate-400 block">
                        Rôle Métier
                      </span>
                      <h4 className="text-sm font-black text-slate-900">
                        {role.roleTitle}
                      </h4>
                    </div>

                    {isValidated ? (
                      <span className="px-2.5 py-1 rounded-full text-3xs font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center space-x-1 shrink-0">
                        <CheckCircle2 className="w-3 h-3 text-emerald-700" />
                        <span>Attribution Validée</span>
                      </span>
                    ) : (
                      <span className="px-2.5 py-1 rounded-full text-3xs font-extrabold uppercase tracking-wider bg-amber-200 text-amber-950 border border-amber-300 flex items-center space-x-1 shrink-0 animate-pulse">
                        <Clock className="w-3 h-3 text-amber-800" />
                        <span>En attente de validation</span>
                      </span>
                    )}
                  </div>

                  {/* Collaborator Selector */}
                  <div className="space-y-1.5">
                    <label className="block text-2xs font-bold text-slate-600">
                      Collaborateur assigné :
                    </label>
                    <select
                      value={role.collaboratorId}
                      onChange={(e) => handleChangeRoleCollaborator(role.id, e.target.value)}
                      className="w-full px-3 py-2 text-xs font-semibold text-slate-900 bg-white border border-slate-300 rounded-xl"
                    >
                      {collaborators.map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.name} ({c.role})
                        </option>
                      ))}
                    </select>
                  </div>

                  {/* Details or Validation Metadata */}
                  <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 text-2xs space-y-1">
                    <div className="flex items-center justify-between text-slate-600">
                      <span>Attribué le :</span>
                      <span className="font-semibold text-slate-900">
                        {new Date(role.assignedAt).toLocaleDateString('fr-FR')}
                      </span>
                    </div>

                    {isValidated && role.validatedBy && (
                      <div className="flex items-center justify-between text-emerald-800 font-semibold pt-1 border-t border-slate-200">
                        <span>Validé par :</span>
                        <span>{role.validatedBy} ({new Date(role.validatedAt || '').toLocaleDateString('fr-FR')})</span>
                      </div>
                    )}

                    {role.notes && (
                      <p className="text-slate-500 italic pt-1">
                        "{role.notes}"
                      </p>
                    )}
                  </div>

                  {/* Action Buttons for Validation */}
                  <div className="pt-2 flex items-center space-x-2">
                    {!isValidated ? (
                      <button
                        onClick={() => handleValidateRole(role.id, true)}
                        className="flex-1 py-2 px-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 transition-colors shadow-xs"
                      >
                        <Check className="w-4 h-4" />
                        <span>Valider cette attribution</span>
                      </button>
                    ) : (
                      <button
                        onClick={() => handleValidateRole(role.id, false)}
                        className="flex-1 py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors"
                      >
                        <span>Remettre en attente</span>
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* SUB-TAB 3: JOURNAL HISTORIQUE & TRAÇABILITÉ (QUI A FAIT QUOI ET QUAND) */}
      {activeSubTab === 'history' && (
        <div className="space-y-6">
          {/* Quick Note Input */}
          <form onSubmit={handleAddHistoryNote} className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
            <label className="block text-xs font-bold text-slate-900">
              Consigner un événement ou un compte-rendu dans l’historique :
            </label>
            <div className="flex gap-2">
              <input
                type="text"
                required
                value={newLogNote}
                onChange={(e) => setNewLogNote(e.target.value)}
                placeholder="ex: Échange téléphonique concluant avec le DG : intéressé par l'audit Bilan Carbone"
                className="flex-1 px-3 py-2 text-xs border border-slate-300 rounded-xl bg-white"
              />
              <button
                type="submit"
                className="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-xs transition-colors shrink-0"
              >
                <Send className="w-3.5 h-3.5" />
                <span>Enregistrer</span>
              </button>
            </div>
          </form>

          {/* Timeline Feed */}
          <div className="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            {currentHistory.length === 0 ? (
              <div className="text-center py-8 text-slate-400 text-xs italic">
                Aucun historique enregistré pour ce dossier.
              </div>
            ) : (
              currentHistory.map((log) => {
                const dateObj = new Date(log.timestamp);
                const formattedDate = dateObj.toLocaleDateString('fr-FR', {
                  day: 'numeric',
                  month: 'short',
                  year: 'numeric',
                });
                const formattedTime = dateObj.toLocaleTimeString('fr-FR', {
                  hour: '2-digit',
                  minute: '2-digit',
                });

                return (
                  <div key={log.id} className="relative group">
                    {/* Timeline bullet */}
                    <div className="absolute -left-6 top-1.5 w-3.5 h-3.5 rounded-full bg-white border-2 border-emerald-600 shadow-2xs group-hover:scale-125 transition-transform" />

                    <div className="p-3.5 rounded-2xl bg-white border border-slate-200 hover:border-slate-300 transition-colors shadow-2xs space-y-1">
                      <div className="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div className="flex items-center space-x-2">
                          <span className="font-bold text-slate-900">{log.authorName}</span>
                          <span className="px-2 py-0.5 rounded-full text-3xs font-extrabold uppercase bg-slate-100 text-slate-700">
                            {log.actionType.replace('_', ' ')}
                          </span>
                        </div>
                        <span className="text-2xs text-slate-400 font-medium">
                          {formattedDate} à {formattedTime}
                        </span>
                      </div>

                      <p className="text-xs text-slate-800 font-semibold leading-snug">
                        {log.description}
                      </p>

                      {log.details && (
                        <p className="text-2xs text-slate-500 italic">
                          {log.details}
                        </p>
                      )}
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </div>
      )}
    </div>
  );
};
