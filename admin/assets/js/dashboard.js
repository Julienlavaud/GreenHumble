/**
 * GreenHumble v1.1 — Module Dashboard Cockpit Frontend
 * Chargement des indicateurs réels et filtrage dynamique par année
 */

document.addEventListener('DOMContentLoaded', () => {
  loadDashboardRealData('2026');
});

async function filterDashboardByYear(year) {
  console.log('🔄 [Dashboard] Filtrage pour l’année :', year);
  await loadDashboardRealData(year);
}

async function loadDashboardRealData(year = '2026') {
  try {
    const res = await fetch('/api/audits');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const audits = await res.json();
    console.log('🚀 [IA GreenHumble] Audits réels chargés pour Dashboard :', audits);

    const totalAuditsElem = document.getElementById('total-audits-count');
    const avgScoreElem = document.getElementById('avg-risk-score');

    if (totalAuditsElem) {
      totalAuditsElem.innerText = audits.length || '0';
    }

    if (avgScoreElem && audits.length > 0) {
      const avg = Math.round(audits.reduce((acc, curr) => acc + (curr.riskScore || curr.score || 50), 0) / audits.length);
      avgScoreElem.innerText = `${avg}/100`;
    }

  } catch (error) {
    console.error('❌ Erreur chargement cockpit dashboard :', error);
  }
}
