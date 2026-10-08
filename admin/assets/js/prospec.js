/**
 * GreenHumble v1.1 — Module Prospection REST B2B Frontend
 * Communication directe avec GET/POST /api/prospec et restitution du Pipeline Kanban
 */

document.addEventListener('DOMContentLoaded', () => {
  loadPipelineData();
});

async function loadPipelineData() {
  const board = document.getElementById('kanban-board');
  if (!board) return;

  try {
    const response = await fetch('/api/prospec');
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const data = await response.json();
    console.log('🚀 [IA GreenHumble] Données réelles du Pipeline B2B :', data);

    renderKanbanBoard(data.prospects || data.audits || []);

  } catch (error) {
    console.error('❌ Erreur chargement pipeline prospec :', error);
  }
}

async function triggerProspecScan(targetUrl) {
  if (!targetUrl) return;

  try {
    const token = localStorage.getItem('greenhumble_jwt_token') || '';
    const res = await fetch('/api/prospec', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': token ? `Bearer ${token}` : ''
      },
      body: JSON.stringify({ url: targetUrl })
    });

    const data = await res.json();
    console.log('🚀 [IA GreenHumble] Opportunité B2B générée :', data);
    loadPipelineData();

  } catch (err) {
    console.error('❌ Erreur scan prospec B2B :', err);
  }
}

function renderKanbanBoard(prospects) {
  const container = document.getElementById('kanban-board');
  if (!container) return;

  if (prospects.length === 0) {
    container.innerHTML = `
      <div class="col-span-4 bg-slate-900 border border-slate-800 rounded-xl p-8 text-center text-slate-400">
        Aucune opportunité B2B enregistrée. Lancez un audit pour alimenter le pipeline commercial.
      </div>
    `;
    return;
  }

  let html = `<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 w-full">`;

  prospects.forEach((p) => {
    const company = p.companyName || p.cleanUrl || 'Entreprise Prospect';
    const score = p.riskScore ?? p.score ?? 50;
    const opps = p.businessOpportunities || [];

    html += `
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 shadow-lg flex flex-col justify-between">
        <div>
          <div class="flex justify-between items-start mb-2">
            <h4 class="font-bold text-white text-md">${company}</h4>
            <span class="text-xs font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400">${score}/100</span>
          </div>
          <p class="text-xs text-slate-400 mb-3">${p.sector || 'Secteur Général'}</p>
          
          <div class="space-y-2 mb-4">
            ${opps.slice(0, 2).map(o => `
              <div class="bg-slate-950 p-2 rounded border border-slate-800 text-[11px]">
                <p class="font-semibold text-emerald-400">${o.serviceTitle || 'Bilan Carbone'}</p>
                <p class="text-slate-400 mt-0.5">${o.potentialBudget || '5 000 € HT'}</p>
              </div>
            `).join('')}
          </div>
        </div>

        <button onclick="triggerProspecScan('${p.url || p.cleanUrl}')" class="w-full bg-slate-800 hover:bg-slate-700 text-xs font-semibold py-2 rounded text-slate-200 transition-colors">
          🔄 Actualiser l'Analyse IA
        </button>
      </div>
    `;
  });

  html += `</div>`;
  container.innerHTML = html;
}
