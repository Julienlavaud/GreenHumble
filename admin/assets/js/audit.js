/**
 * GreenHumble v1.1 — Module Audit REST Frontend
 * Communication directe avec POST /api/audit et restitution dynamique des retours IA en cartes
 */

async function submitAuditForm(event) {
  event.preventDefault();
  
  const urlInput = document.getElementById('audit-url');
  const resultsContainer = document.getElementById('audit-results-container');
  const submitBtn = document.getElementById('submit-btn');

  if (!urlInput || !urlInput.value.trim()) return;

  const targetUrl = urlInput.value.trim();
  
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Analyse IA en cours par Gemini...';
  }

  if (resultsContainer) {
    resultsContainer.innerHTML = `
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-8 text-center animate-pulse">
        <div class="w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <h3 class="text-lg font-bold text-white">Analyse des allégations environnementales en cours...</h3>
        <p class="text-sm text-slate-400 mt-2">Confrontation du contenu de ${targetUrl} aux Directives EmpCO, Green Claims et Décret n° 2022-539.</p>
      </div>
    `;
  }

  try {
    const token = localStorage.getItem('greenhumble_jwt_token') || sessionStorage.getItem('greenhumble_jwt_token') || '';

    const response = await fetch('/api/audit', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': token ? `Bearer ${token}` : ''
      },
      body: JSON.stringify({
        url: targetUrl,
        scanVars: { strictnessLevel: 'strict' }
      })
    });

    if (!response.ok) {
      throw new Error(`Erreur serveur HTTP ${response.status}`);
    }

    const data = await response.json();
    console.log('🚀 [IA GreenHumble] Résultat brut de l’audit REST :', data);

    renderAuditCards(data);

  } catch (error) {
    console.error('❌ Erreur lors de l’audit REST :', error);
    if (resultsContainer) {
      resultsContainer.innerHTML = `
        <div class="bg-rose-950/40 border border-rose-800 rounded-xl p-6 text-rose-200">
          <h3 class="font-bold text-lg">⚠️ Échec du scan d'audit</h3>
          <p class="text-sm mt-1">${error.message || 'Impossible de joindre l’API d’audit.'}</p>
        </div>
      `;
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '🚀 Lancer l\'Audit REST';
    }
  }
}

function renderAuditCards(data) {
  const container = document.getElementById('audit-results-container');
  if (!container) return;

  const company = data.companyName || data.company || 'Entreprise analysée';
  const score = data.riskScore ?? data.score ?? 50;
  const level = data.riskLevel || (score < 40 ? 'critique' : score < 60 ? 'eleve' : 'modere');
  const claims = data.detectedClaims || data.claims || [];
  const summary = data.clientSummary?.headline || data.summary || 'Synthèse non disponible';

  let levelBadgeClass = 'bg-amber-500/20 text-amber-400 border-amber-500/30';
  if (level === 'critique' || score < 40) {
    levelBadgeClass = 'bg-rose-500/20 text-rose-400 border-rose-500/30';
  } else if (score >= 70) {
    levelBadgeClass = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
  }

  let html = `
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl mb-6">
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4 mb-4">
        <div>
          <h2 class="text-xl font-bold text-white">${company}</h2>
          <p class="text-xs text-slate-400 mt-1">Secteur : ${data.sector || 'Général'} | URL : <a href="${data.url || '#'}" target="_blank" class="text-emerald-400 underline">${data.cleanUrl || data.url || ''}</a></p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-xs font-semibold px-3 py-1 rounded-full border ${levelBadgeClass} uppercase">Risque ${level}</span>
          <div class="text-right">
            <span class="text-2xl font-black text-emerald-400">${score}</span><span class="text-xs text-slate-500">/100</span>
          </div>
        </div>
      </div>
      <p class="text-sm text-slate-300 bg-slate-950/60 p-4 rounded-lg border border-slate-800/80 mb-6">📌 <strong>Synthèse IA :</strong> ${summary}</p>
      
      <h3 class="text-md font-bold text-slate-200 mb-4 flex items-center gap-2">
        <span>⚠️ Allégations Environnementales Relevées (${claims.length})</span>
      </h3>
  `;

  if (claims.length === 0) {
    html += `<p class="text-sm text-slate-400 italic">Aucune allégation à haut risque immédiat détectée par l'IA.</p>`;
  } else {
    html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;
    claims.forEach((c, i) => {
      html += `
        <div class="bg-slate-950 border-l-4 border-emerald-500 border-t border-r border-b border-slate-800 rounded-r-xl p-4 text-xs">
          <div class="flex justify-between items-start gap-2 mb-2">
            <span class="font-bold text-emerald-400 text-sm">« ${c.term || c.quoteInText || 'Allégation'} »</span>
            <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded text-[10px]">${c.lawArticle || 'Green Claims'}</span>
          </div>
          <p class="text-slate-300 mb-2 italic">${c.quoteInText || ''}</p>
          <p class="text-rose-400 mb-1"><strong>Risque :</strong> ${c.legalRisk || 'Risque de sanction administrative'}</p>
          <p class="text-slate-400"><strong>Preuve requise :</strong> ${c.requiredProof || 'Bilan Carbone ou ACV ISO 14040/44'}</p>
        </div>
      `;
    });
    html += `</div>`;
  }

  html += `</div>`;
  container.innerHTML = html;
}
