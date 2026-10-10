<?php
require_once __DIR__ . '/includes/auth.php';

$role = get_current_role();
$auditId = trim((string)($_GET['id'] ?? ''));
if (!in_array($role, ['admin', 'pipeline', 'collaborator', 'consultant'], true)) {
    $next = '/api/audit-report.php';
    if (preg_match('/^aud_[A-Za-z0-9_-]{8,80}$/', $auditId)) $next .= '?id=' . rawurlencode($auditId);
    header('Location: /api/login.php?next=' . rawurlencode($next), true, 303);
    exit;
}
$page_title = 'Rapport d’audit';
$activeTab = 'scans';
require_once __DIR__ . '/includes/header.php';
?>
<style>
:root{color-scheme:light;--green:#087f5b;--green-dark:#145044;--mint:#eaf6f0;--ink:#18332c;--muted:#687b75;--line:#dce7e1;--paper:#fff;--bg:#f4f7f5;--amber:#946200;--red:#a33b36}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.55 Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
a{color:var(--green)}.shell{max-width:1240px;margin:auto;padding:24px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:26px}.brand{font-weight:850;font-size:21px;letter-spacing:-.04em;color:var(--green-dark)}.brand small{display:block;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);font-weight:750}
.actions{display:flex;gap:9px;flex-wrap:wrap}.btn{appearance:none;border:1px solid var(--line);border-radius:10px;background:white;color:var(--ink);padding:10px 14px;font:inherit;font-weight:700;text-decoration:none;cursor:pointer}.btn.primary{background:var(--green);border-color:var(--green);color:white}.btn:focus-visible,input:focus-visible{outline:3px solid #75c9a8;outline-offset:2px}
.hero{background:linear-gradient(120deg,#fff 0%,#f0f8f4 100%);border:1px solid var(--line);border-radius:20px;padding:28px;margin-bottom:18px}.eyebrow{font-size:11px;font-weight:850;letter-spacing:.15em;text-transform:uppercase;color:var(--green)}h1{font-size:clamp(27px,4vw,40px);line-height:1.12;letter-spacing:-.045em;margin:7px 0 8px}.subtitle{margin:0;color:var(--muted);max-width:760px}.lookup{display:flex;gap:9px;margin-top:20px;max-width:620px}.lookup input{flex:1;min-width:0;border:1px solid #ccdcd3;border-radius:10px;padding:12px;font:inherit;background:white}.meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;color:var(--muted);font-size:13px}.pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;background:var(--mint);border-radius:99px;color:var(--green-dark);font-size:12px;font-weight:800}
.grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(260px,1fr);gap:18px;align-items:start}.card{background:var(--paper);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px;min-width:0}.card h2{font-size:18px;letter-spacing:-.02em;margin:0 0 5px}.card .intro{color:var(--muted);margin:0 0 17px;font-size:13px}.scoreline{display:flex;align-items:center;gap:22px}.score{width:112px;height:112px;flex:0 0 112px;border:8px solid var(--mint);border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--green-dark)}.score strong{font-size:30px;line-height:1}.score span{font-size:11px;color:var(--muted)}.scoretext{min-width:0}.scoretext h2{font-size:20px}.muted{color:var(--muted)}.notice{padding:13px 15px;background:#fff8e8;border:1px solid #f1dfb4;border-radius:10px;color:#73520b;margin:14px 0}.empty{padding:18px;border:1px dashed #cbd9d1;border-radius:12px;color:var(--muted);text-align:center}.item{border-top:1px solid var(--line);padding:15px 0}.item:first-of-type{border-top:0}.item h3{font-size:15px;margin:0 0 5px}.item p{margin:5px 0;overflow-wrap:anywhere}.tag{display:inline-block;border-radius:6px;background:#f0f3f1;padding:3px 7px;margin:0 5px 5px 0;font-size:11px;font-weight:750;color:#52645d}.list{padding-left:19px;margin:10px 0}.list li{margin:7px 0}.facts{display:grid;grid-template-columns:1fr 1fr;gap:12px}.fact{background:#f7faf8;border-radius:10px;padding:12px;min-width:0}.fact span{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.06em}.fact strong{display:block;margin-top:4px;overflow-wrap:anywhere}.fact small{display:block;margin-top:5px;font-size:11px;color:var(--muted)}.footer{font-size:12px;color:var(--muted);padding:12px 3px 24px}.hidden{display:none!important}.error{color:var(--red)}.commercial{border-left:3px solid var(--green);padding-left:13px;margin:12px 0}.mono{font-variant-numeric:tabular-nums}
@media(max-width:800px){.shell{padding:14px}.topbar{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr}.hero,.card{padding:18px}.scoreline{align-items:flex-start;gap:15px}.score{width:92px;height:92px;flex-basis:92px}.lookup{flex-direction:column}.facts{grid-template-columns:1fr 1fr}}
@media print{body{background:white;font-size:11pt}.shell{max-width:none;padding:0}.topbar .actions,.lookup,.no-print{display:none!important}.hero,.card{break-inside:avoid;border:1px solid #ccc;box-shadow:none;margin-bottom:10px;padding:15px}.grid{grid-template-columns:1.5fr 1fr;gap:10px}.footer{padding-bottom:0}a{color:inherit;text-decoration:none}}

/* Rapport d'audit — finition visuelle */
.gh-report-page{--green:#087f5b;--green-dark:#145044;--mint:#eaf6f0;--ink:#18332c;--muted:#687b75;--line:#dce7e1;--paper:#fff;--bg:#f4f7f5}
.gh-report-page .shell{max-width:1320px;padding:30px clamp(16px,3vw,38px) 18px}
.gh-report-page .hero{position:relative;overflow:hidden;padding:clamp(22px,4vw,40px);border-radius:24px;background:linear-gradient(118deg,#fff 8%,#f2faf5 65%,#e8f5ed);box-shadow:0 14px 38px rgba(25,67,51,.06)}
.gh-report-page .hero:after{content:"";position:absolute;width:260px;height:260px;border-radius:50%;right:-110px;top:-155px;background:rgba(8,127,91,.08);pointer-events:none}
.report-toolbar{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:14px}
.report-toolbar .eyebrow{display:inline-flex;align-items:center;gap:8px;padding:7px 11px;border:1px solid #cfe5d8;border-radius:99px;background:rgba(255,255,255,.8)}
.report-toolbar .eyebrow:before{content:"";width:7px;height:7px;border-radius:50%;background:#087f5b;box-shadow:0 0 0 3px #dff1e7}
.gh-report-page h1{max-width:850px;margin:16px 0 10px;font-size:clamp(30px,4.5vw,46px);letter-spacing:-.05em}
.gh-report-page .subtitle{max-width:800px;font-size:15px;line-height:1.7}
.gh-report-page .lookup{position:relative;z-index:1;margin-top:24px}
.gh-report-page .lookup input{min-height:46px;box-shadow:0 2px 8px rgba(24,51,44,.04)}
.gh-report-page .btn{transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease}
.gh-report-page .btn:hover{transform:translateY(-1px);box-shadow:0 7px 18px rgba(24,51,44,.1)}
.gh-report-page .btn.primary:hover{background:#066d4e}
.gh-report-page .meta{position:relative;z-index:1;align-items:center;gap:12px;margin-top:20px}
.gh-report-page .pill{padding:7px 12px}
.gh-report-page .grid{grid-template-columns:minmax(0,1.72fr) minmax(290px,.92fr);gap:20px}
.gh-report-page .card{padding:clamp(18px,2.3vw,26px);border-radius:18px;box-shadow:0 8px 26px rgba(25,67,51,.045);transition:border-color .18s ease,box-shadow .18s ease}
.gh-report-page .card h2{font-size:19px}
.gh-report-page .card .intro{line-height:1.65}
.gh-report-page .scoreline{gap:24px}
.gh-report-page .score{background:radial-gradient(circle at 50% 45%,#fff 0 52%,#f4faf6 53%);border-color:#d9eee2;box-shadow:inset 0 0 0 1px rgba(8,127,91,.04)}
.gh-report-page #second-opinion-card{background:linear-gradient(145deg,#fff,#f5faf7)}
.gh-report-page #second-opinion-card .btn{min-height:44px}
.gh-report-page .item{padding:17px 0}
.gh-report-page .item h3{font-size:15px;line-height:1.45}
.gh-report-page .fact{border:1px solid #edf2ee;background:#f8fbf9;padding:13px}
.gh-report-page .fact span{font-weight:800}
.gh-report-page .commercial{border-left-width:3px;background:#f8fbf9;border-radius:0 10px 10px 0;padding:12px 14px}
.gh-report-page .empty{background:#fafcfb}
.gh-report-page .footer{padding-top:8px}
@media(max-width:900px){.gh-report-page .grid{grid-template-columns:1fr}.gh-report-page .grid aside{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:start}.gh-report-page .grid aside .card{margin:0}}
@media(max-width:600px){.gh-report-page .shell{padding:14px 12px}.report-toolbar{align-items:flex-start}.report-toolbar .btn{padding:8px 10px;font-size:13px}.gh-report-page .hero{border-radius:17px;padding:21px 17px}.gh-report-page .grid aside{display:block}.gh-report-page .grid aside .card{margin-bottom:14px}.gh-report-page .scoreline{gap:14px}.gh-report-page .score{width:82px;height:82px;flex-basis:82px;border-width:6px}.gh-report-page .score strong{font-size:25px}.gh-report-page .facts{gap:8px}.gh-report-page .fact{padding:10px}}
@media(prefers-reduced-motion:reduce){.gh-report-page .btn,.gh-report-page .card{transition:none}}
@media print{.report-toolbar .print-report{display:none!important}.gh-report-page .shell{padding:0}.gh-report-page .hero{background:#fff;box-shadow:none}.gh-report-page .card{box-shadow:none}}

</style>
<main class="gh-report-page"><div class="shell"><section class="hero">
  <div class="report-toolbar"><div class="eyebrow">Rapport d’audit</div><button class="btn print-report" type="button" onclick="window.print()" aria-label="Imprimer ou enregistrer ce rapport en PDF">Imprimer / PDF</button></div>
  <h1 id="report-title">Rapport de conformité environnementale</h1>
  <p class="subtitle">Synthèse des éléments analysés, des allégations repérées et des pistes d’amélioration. Le rapport restitue les données enregistrées par GreenHumble sans remplacer une validation juridique.</p>
  <form class="lookup" id="lookup-form">
    <input id="audit-id" name="id" type="text" value="<?= htmlspecialchars($auditId, ENT_QUOTES, 'UTF-8') ?>" placeholder="Identifiant de l’audit" aria-label="Identifiant de l’audit" required>
    <button class="btn primary" type="submit">Afficher le rapport</button>
  </form>
  <div class="meta"><span class="pill" id="status-pill">En attente d’un audit</span><span id="report-date">Sélectionnez un audit enregistré pour afficher son rapport.</span></div>
  <div id="error-box" class="notice hidden" role="alert"></div>
</section>
<div id="report-content" class="hidden">
  <div class="grid">
    <main>
      <section class="card">
        <div class="scoreline"><div class="score"><strong id="score-value">—</strong><span>/ 100</span></div><div class="scoretext"><span class="eyebrow">Indicateur enregistré</span><h2 id="risk-level">Niveau de risque à évaluer</h2><p id="summary" class="muted">Aucune synthèse disponible.</p></div></div>
        <div id="incomplete-notice" class="notice hidden">Cette analyse est signalée comme incomplète. Aucun score ne doit être interprété comme une conclusion fiable.</div>
      </section>
      <section class="card" id="second-opinion-card">
        <h2>Seconde lecture IA</h2>
        <p class="intro">Une autre lecture critique compare les constats et le score enregistrés. Elle peut renforcer la convergence, nuancer ou affaiblir la confiance. Cela déclenche un nouvel appel avec la configuration IA active (ce n’est pas un avis indépendant). Le corpus complet du site n’étant pas conservé, l’IA examine les éléments sauvegardés sans revisiter le site.</p>
        <button class="btn primary no-print" id="run-second-opinion" type="button">Demander une seconde lecture IA</button>
        <div id="second-opinion-status" class="notice hidden" role="status" aria-live="polite"></div>
        <div id="second-opinion-result" class="hidden">
          <p><strong id="second-opinion-label"></strong></p>
          <p id="second-opinion-summary" class="muted"></p>
          <p id="second-opinion-comparison" class="muted"></p>
          <div id="second-opinion-claims"></div>
          <p id="second-opinion-notice" class="intro"></p>
        </div>
      </section>
      <section class="card"><h2>Allégations détectées</h2><p class="intro">Éléments relevés dans le résultat d’analyse. Une détection constitue un signal à vérifier, pas une preuve automatique de non-conformité.</p><div id="claims-list"></div></section>
      <section class="card"><h2>Actions et recommandations</h2><p class="intro">Pistes présentes dans le résultat sauvegardé, à confirmer au regard des preuves et du contexte.</p><div id="actions-list"></div></section>
      <section class="card"><h2>Opportunités d’accompagnement</h2><p class="intro">Informations commerciales disponibles dans le kit enregistré, si celui-ci a été produit.</p><div id="commercial-list"></div></section>
    </main>
    <aside>
      <section class="card"><h2>Entreprise analysée</h2><p class="intro">Informations associées à cet audit</p><div class="facts">
        <div class="fact"><span>Entreprise</span><strong id="company-name">—</strong><small id="company-name-source">Source non renseignée</small></div>
        <div class="fact"><span>SIREN</span><strong id="company-siren">—</strong><small id="company-siren-source">Non communiqué</small></div>
        <div class="fact"><span>Secteur</span><strong id="company-sector">—</strong><small id="company-sector-source">Non communiqué</small></div>
        <div class="fact"><span>Effectif</span><strong id="company-headcount">—</strong><small id="company-headcount-source">Non communiqué</small></div>
        <div class="fact"><span>Chiffre d’affaires</span><strong id="company-turnover">—</strong><small id="company-turnover-source">Non communiqué</small></div>
        <div class="fact"><span>Code NAF</span><strong id="company-naf">—</strong><small id="company-naf-source">Non communiqué</small></div>
      </div><div class="item"><h3>Site web</h3><p id="company-url">—</p></div><div class="item"><h3>Identifiant audit</h3><p id="company-id" class="mono">—</p></div></section>
      <section class="card" id="analysis-quality-card"><h2>Qualité observable de l’analyse</h2><p id="quality-grade" class="scoretext">Évaluation en cours</p><p id="quality-detail" class="muted">Contrôle de la complétude, des citations et des pages sources enregistrées.</p><p id="quality-model" class="intro"></p></section>
      <section class="card"><h2>À retenir</h2><p id="urgent-action">Aucune action prioritaire fournie dans le résultat.</p><div id="immediate-risks"></div></section>
      <section class="card"><h2>Coordonnées publiques du site</h2><div id="website-contacts" class="facts"></div></section>
      <section class="card"><h2>Limites du rapport</h2><p class="muted">Le score et les constats dépendent des pages accessibles, des éléments fournis et de la qualité de l’analyse automatique. Vérifiez les justificatifs, la portée exacte des allégations et les éventuelles exceptions avant toute décision.</p></section>
    </aside>
  </div>
</div>
<div class="footer">GreenHumble · Document de travail confidentiel · À vérifier avant diffusion externe.</div>
</div>
<script>
const $ = (id) => document.getElementById(id);
let currentAuditId = '';
let secondOpinionPending = false;
const text = (value, fallback='Non renseigné') => {
  if (value === null || value === undefined || value === '') return fallback;
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') return String(value);
  return '';
};
function setText(id, value, fallback) { $(id).textContent = text(value, fallback); }
function clear(node) { while (node.firstChild) node.removeChild(node.firstChild); }
function addText(parent, tag, value, className) {
  const el = document.createElement(tag);
  el.textContent = text(value);
  if (className) el.className = className;
  parent.appendChild(el);
  return el;
}
function displayValue(value) {
  if (value === null || value === undefined || value === '') return 'Non renseigné';
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') return String(value);
  return JSON.stringify(value, null, 2);
}
function renderGenericList(targetId, values, emptyMessage, titleKeys, detailKeys) {
  const target = $(targetId); clear(target);
  if (!Array.isArray(values) || values.length === 0) { addText(target, 'div', emptyMessage, 'empty'); return; }
  values.forEach((value, index) => {
    const item = document.createElement('article'); item.className = 'item';
    if (typeof value === 'string' || typeof value === 'number') { addText(item, 'p', value); }
    else if (value && typeof value === 'object') {
      const title = titleKeys.map(k => value[k]).find(v => typeof v === 'string' && v.trim());
      addText(item, 'h3', title || ('Élément ' + (index + 1)));
      detailKeys.forEach(k => { if (value[k] !== undefined && value[k] !== null && value[k] !== '') addText(item, 'p', k.replace(/([A-Z])/g,' $1') + ' : ' + displayValue(value[k])); });
      const sourceUrl = value.sourceUrl || value.source_url || value.pageUrl || value.url;
      if (typeof sourceUrl === 'string' && /^https?:\/\//i.test(sourceUrl)) {
        try {
          const parsedSource = new URL(sourceUrl);
          if (['http:', 'https:'].includes(parsedSource.protocol)) {
            const link = document.createElement('a');
            link.href = parsedSource.href;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.className = 'source-link';
            link.textContent = 'Voir la page source ↗';
            item.appendChild(link);
          }
        } catch (_) {}
      }
      const known = new Set([...titleKeys, ...detailKeys, 'sourceUrl', 'source_url', 'pageUrl', 'url']);
      Object.keys(value).filter(k => !known.has(k)).forEach(k => addText(item, 'p', k + ' : ' + displayValue(value[k])));
    } else addText(item, 'p', displayValue(value));
    target.appendChild(item);
  });
}
function renderSteps(values) {
  const target = $('actions-list'); clear(target);
  if (!Array.isArray(values) || values.length === 0) { addText(target,'div','Aucune recommandation structurée disponible dans les données enregistrées.','empty'); return; }
  const list = document.createElement('ol'); list.className = 'list';
  values.forEach(v => { const li = document.createElement('li'); if (typeof v === 'string') li.textContent = v; else if (v && typeof v === 'object') { const heading = [v.title, v.step ? 'Étape ' + v.step : null].filter(Boolean).join(' — '); li.textContent = (heading ? heading + ': ' : '') + text(v.description || v.action || v.recommendation, displayValue(v)); } else li.textContent = displayValue(v); list.appendChild(li); });
  target.appendChild(list);
}
function renderCommercial(kit) {
  const target = $('commercial-list'); clear(target);
  if (!kit || typeof kit !== 'object') { addText(target,'div','Aucun kit commercial disponible pour cet audit.','empty'); return; }
  const fields = [
    ['Priorité commerciale', kit.priority],
    ['Pourquoi cette priorité', kit.priorityRationale],
    ['Score de potentiel commercial', kit.scoreProspect],
    ['Prochaine action', kit.nextAction],
    ['Offre recommandée', kit.recommendedOffer && (kit.recommendedOffer.serviceTitle || kit.recommendedOffer)],
    ['Raison du choix de l’offre', kit.recommendedOffer && kit.recommendedOffer.reason],
    ['Arguments liés aux constats', kit.evidenceBasedReasons],
    ['Message d’approche', kit.messageApproche],
    ['Recommandation', kit.recommandation],
    ['Profil cible', kit.targetPersona], ['Synthèse commerciale', kit.executiveSummary],
    ['Accroche', kit.phonePitch && kit.phonePitch.hook], ['Fait à vérifier', kit.phonePitch && kit.phonePitch.keyFact],
    ['Appel à l’action', kit.phonePitch && kit.phonePitch.callToAction],
    ['Offre du catalogue', kit.climatLocalProposal && kit.climatLocalProposal.recommendedPackage],
    ['Calendrier proposé', kit.climatLocalProposal && kit.climatLocalProposal.timeline]
  ];
  fields.forEach(([label,value]) => { if (value !== undefined && value !== null && value !== '') { const wrap=document.createElement('div'); wrap.className='commercial'; addText(wrap,'strong',label); addText(wrap,'p',displayValue(value)); target.appendChild(wrap); } });
  const email = kit.emailProspecting && kit.emailProspecting.legalSecurity;
  if (email && typeof email === 'object') { const wrap=document.createElement('div'); wrap.className='commercial'; addText(wrap,'strong',email.subject || 'Courriel de prospection'); if(email.body) addText(wrap,'p',email.body); target.appendChild(wrap); }
  if (!target.childNodes.length) addText(target,'div','Le kit existe mais ne contient aucun champ affichable reconnu.','empty');
}
const companySourceLabels = {
  registre_public_insee: 'Registre public (INSEE)',
  registre_public_etat: 'Registre public (État)',
  saisie_utilisateur: 'Saisie utilisateur',
  estimation_non_verifiee: 'Estimation non vérifiée',
  non_communique: 'Non communiqué'
};
function provenanceText(source, value, period) {
  const hasValue = value !== null && value !== undefined && String(value).trim() !== '';
  let label = companySourceLabels[source] || (hasValue ? 'Source non renseignée pour cet audit' : 'Non communiqué');
  if (source === 'non_communique' && hasValue) label = 'Source non renseignée pour cet audit';
  if (period !== null && period !== undefined && String(period).trim() !== '') label += ' · ' + String(period);
  return label;
}
function renderAudit(a) {
  currentAuditId = String(a.id || a.scan_id || '').trim();
  const reviewButton = $('run-second-opinion');
  if (reviewButton) reviewButton.disabled = !/^aud_[A-Za-z0-9_-]{8,80}$/.test(currentAuditId);
  $('second-opinion-result').classList.add('hidden');
  $('second-opinion-status').classList.add('hidden');
  const summary = a.clientSummary || {};
  const complete = a.analysisComplete !== false && a.status !== 'analysis_incomplete' && a.riskScore !== null && a.riskScore !== undefined;
  $('report-content').classList.remove('hidden');
  $('error-box').classList.add('hidden');
  setText('report-title', a.companyName || a.url || 'Rapport de conformité environnementale');
  setText('report-date', a.analyzedAt ? 'Analysé le ' + new Date(a.analyzedAt).toLocaleString('fr-FR') : 'Date d’analyse non renseignée');
  setText('status-pill', complete ? 'Analyse enregistrée' : 'Analyse incomplète');
  $('status-pill').style.background = complete ? 'var(--mint)' : '#fff0ed';
  $('status-pill').style.color = complete ? 'var(--green-dark)' : 'var(--red)';
  setText('score-value', complete ? a.riskScore : '—');
  setText('risk-level', complete ? (a.riskLevel || 'Niveau non précisé') : 'Analyse incomplète — risque non évalué');
  setText('summary', summary.riskVerdict || summary.headline || a.summary || 'Aucune synthèse disponible.');
  $('incomplete-notice').classList.toggle('hidden', complete);
  const companyRegistry = (a.companyRegistry && typeof a.companyRegistry === 'object') ? a.companyRegistry : ((a.resultat_ia && a.resultat_ia.companyRegistry) || {});
  const companyName = a.companyName || (a.url ? new URL(a.url, window.location.origin).hostname : null);
  const companySector = a.entreprise_secteur;
  const companyHeadcount = a.entreprise_salaries;
  const companyTurnover = a.entreprise_ca;
  const siren = companyRegistry.siren;
  const naf = companyRegistry.naf;
  setText('company-name', companyName);
  setText('company-name-source', companyRegistry.provider && siren ? 'Nom rapproché du registre public (SIREN confirmé)' : provenanceText(null, companyName));
  setText('company-siren', siren);
  setText('company-siren-source', siren ? 'Rapprochement confirmé via le registre public' : 'Aucun SIREN confirmé');
  setText('company-sector', companySector);
  setText('company-sector-source', provenanceText(companyRegistry.sectorSource, companySector));
  setText('company-headcount', companyHeadcount);
  setText('company-headcount-source', provenanceText(companyRegistry.headcountSource, companyHeadcount, companyRegistry.headcountYear ? 'année ' + companyRegistry.headcountYear : null));
  setText('company-turnover', companyTurnover);
  setText('company-turnover-source', provenanceText(companyRegistry.turnoverSource, companyTurnover, companyRegistry.turnoverPeriod ? 'période ' + companyRegistry.turnoverPeriod : null));
  setText('company-naf', naf);
  setText('company-naf-source', provenanceText(siren ? 'registre_public_insee' : null, naf));
  setText('company-url', a.url);
  setText('company-id', a.id || a.scan_id);
  setText('urgent-action', summary.keyUrgentAction || 'Aucune action prioritaire fournie dans le résultat.');
  const claims = a.detectedClaims || a.claims || a.allegations || (a.resultat_ia && (a.resultat_ia.detectedClaims || a.resultat_ia.claims || a.resultat_ia.allegations)) || [];
  renderGenericList('claims-list', claims, 'Aucune allégation détectée ou enregistrée dans le résultat.', ['claim','text','claimText','allegation','title','label'], ['evidence','proof','justification','riskLevel','severity','explanation','recommendation']);
  const claimRows = Array.isArray(claims) ? claims : [];
  const sourcedClaims = claimRows.filter(claim => claim && typeof claim === 'object' && typeof (claim.sourceUrl || claim.source_url || claim.pageUrl) === 'string' && /^https?:\/\//i.test(claim.sourceUrl || claim.source_url || claim.pageUrl)).length;
  const quotedClaims = claimRows.filter(claim => claim && typeof claim === 'object' && String(claim.quoteInText || claim.quote || claim.evidence || '').trim() !== '').length;
  const qualityGrade = !complete ? 'Analyse incomplète' : (claimRows.length === 0 ? 'Aucun constat à contrôler' : (sourcedClaims === claimRows.length && quotedClaims === claimRows.length ? 'Traçabilité complète' : (sourcedClaims > 0 || quotedClaims > 0 ? 'Traçabilité partielle' : 'Traçabilité à compléter')));
  setText('quality-grade', qualityGrade);
  setText('quality-detail', claimRows.length + ' allégation(s) · ' + sourcedClaims + ' page(s) source liée(s) · ' + quotedClaims + ' citation(s) enregistrée(s). Cet indicateur décrit les éléments vérifiables sauvegardés, pas une note de fiabilité absolue du fournisseur IA.');
  const modelName = a.model || (a.raw && a.raw.model) || (a.resultat_ia && a.resultat_ia.raw && a.resultat_ia.raw.model);
  setText('quality-model', modelName ? 'Modèle déclaré dans l’audit : ' + modelName : 'Modèle IA non communiqué dans les données de cet audit.');
  const contactTarget = $('website-contacts'); clear(contactTarget);
  const websiteContacts = Array.isArray(a.websiteContacts) ? a.websiteContacts : [];
  if (!websiteContacts.length) addText(contactTarget, 'p', 'Aucune coordonnée publique n’a été enregistrée pour ce scan.', 'muted');
  websiteContacts.forEach(contact => {
    if (!contact || !contact.value) return;
    const wrap = document.createElement('div'); wrap.className = 'fact';
    addText(wrap, 'span', contact.type === 'email' ? 'E-mail public' : 'Téléphone public');
    addText(wrap, 'strong', contact.value);
    if (typeof contact.sourceUrl === 'string' && /^https?:\/\//i.test(contact.sourceUrl)) {
      try { const url = new URL(contact.sourceUrl); if (['http:', 'https:'].includes(url.protocol)) { const link = document.createElement('a'); link.href = url.href; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = 'Page source ↗'; wrap.appendChild(link); } } catch (_) {}
    }
    contactTarget.appendChild(wrap);
  });
  renderSteps(summary.stepsToCompliance || a.recommendations || (a.resultat_ia && a.resultat_ia.recommendations));
  renderCommercial(a.commercialProspectingKit || (a.resultat_ia && a.resultat_ia.prospection));
  const risks = summary.immediateRisks;
  const riskTarget = $('immediate-risks'); clear(riskTarget);
  if (Array.isArray(risks) && risks.length) { const list=document.createElement('ul'); list.className='list'; risks.forEach(r => addText(list,'li',displayValue(r))); riskTarget.appendChild(list); }
}
async function loadAudit(id) {
  $('error-box').classList.add('hidden'); $('report-content').classList.add('hidden');
  if (!id.trim()) return;
  $('status-pill').textContent = 'Chargement…';
  try {
    const response = await fetch('/api/audit.php?id=' + encodeURIComponent(id.trim()), {credentials:'same-origin',headers:{'Accept':'application/json'}});
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Impossible de charger cet audit (HTTP ' + response.status + ').');
    if (!data || typeof data !== 'object' || Array.isArray(data)) throw new Error('Réponse de l’API invalide.');
    renderAudit(data);
  } catch (err) {
    $('status-pill').textContent = 'Chargement impossible';
    $('error-box').textContent = err.message || 'Erreur inattendue lors du chargement.';
    $('error-box').classList.remove('hidden');
  }
}
function showSecondOpinion(result) {
  const labels = {
    convergence: 'Convergence — confiance renforcée',
    nuance: 'Lecture à nuancer',
    desaccord: 'Désaccord — confiance affaiblie',
    prudence: 'Confiance non renforcée'
  };
  $('second-opinion-label').textContent = labels[result.agreement] || result.label || 'Seconde lecture';
  $('second-opinion-summary').textContent = result.summary || 'Aucune synthèse disponible.';
  $('second-opinion-comparison').textContent = 'Score initial : ' + result.originalScore + '/100 · seconde lecture : ' + result.reviewScore + '/100 · allégations concordantes : ' + result.matchedClaims + ' sur ' + result.originalClaims + ' initialement (' + result.reviewClaims + ' relues).';
  $('second-opinion-notice').textContent = result.notice || '';
  renderGenericList('second-opinion-claims', result.claims || [], 'Aucune allégation retenue lors de la seconde lecture.', ['term'], ['quoteInText','riskRationale','evidenceStatus','requiredProof']);
  $('second-opinion-result').classList.remove('hidden');
}
$('run-second-opinion').addEventListener('click', async () => {
  const button = $('run-second-opinion');
  const status = $('second-opinion-status');
  if (secondOpinionPending || !/^aud_[A-Za-z0-9_-]{8,80}$/.test(currentAuditId)) return;
  secondOpinionPending = true;
  button.disabled = true;
  button.textContent = 'Seconde lecture en cours…';
  status.textContent = 'Appel au fournisseur IA en cours. Le dossier initial ne sera pas modifié.';
  status.classList.remove('hidden');
  try {
    const response = await fetch('/api/audit-second-opinion.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: JSON.stringify({id: currentAuditId})
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.success) throw new Error(result.error || 'La seconde lecture n’a pas abouti.');
    showSecondOpinion(result);
    status.classList.add('hidden');
  } catch (error) {
    status.textContent = error instanceof Error ? error.message : 'La seconde lecture a échoué. Le dossier initial reste inchangé.';
    status.classList.remove('hidden');
  } finally {
    secondOpinionPending = false;
    button.disabled = !/^aud_[A-Za-z0-9_-]{8,80}$/.test(currentAuditId);
    button.textContent = 'Demander une seconde lecture IA';
  }
});
$('lookup-form').addEventListener('submit', event => { event.preventDefault(); const id=$('audit-id').value.trim(); const url=new URL(window.location.href); if(id) url.searchParams.set('id',id); else url.searchParams.delete('id'); window.history.replaceState({},'',url); loadAudit(id); });
if ($('audit-id').value.trim()) loadAudit($('audit-id').value.trim());
</script>
</div></main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
