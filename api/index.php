<?php
// /api/index.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/cms.php';

$page_title = "Audit RSE & Conformité Green Claims";
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section & Scanner Public Interactif -->
<div class="bg-gradient-to-b from-climat-950 via-climat-900 to-slate-900 text-white py-16 sm:py-24 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs sm:text-sm font-semibold bg-emerald-800/80 text-emerald-200 border border-emerald-600/60 mb-6 shadow-sm">
            <?php echo get_live_text('home', 'hero_badge', '✨ Détecteur officiel d\'allégations environnementales & Directive EmpCO'); ?>
        </span>
        <h1 class="text-4xl sm:text-6xl font-black tracking-tight max-w-4xl mx-auto leading-tight">
            <?php echo get_live_text('home', 'hero_title', 'Auditez la conformité environnementale de votre site en <span class="text-emerald-400">1 clic</span>'); ?>
        </h1>
        <p class="mt-6 text-lg sm:text-xl text-emerald-100/90 max-w-2xl mx-auto font-normal">
            <?php echo get_live_text('home', 'hero_subtitle', 'Détectez les allégations trompeuses, prévenez les risques de sanctions DGCCRF et alignez votre communication sur le corpus européen des 45+ référentiels RSE.'); ?>
        </p>

        <!-- Scanner public interactif -->
        <div class="mt-10 max-w-2xl mx-auto bg-white/10 backdrop-blur-md p-3 rounded-3xl border border-white/20 shadow-2xl">
            <form id="public_scan_form" onsubmit="handlePublicScan(event)" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-grow">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                    </div>
                    <input type="text" id="public_scan_url" required placeholder="ex: www.votre-entreprise.fr ou entreprise.com" class="w-full pl-11 pr-4 py-3.5 bg-white text-slate-900 placeholder-slate-400 rounded-2xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-400 border border-slate-200">
                </div>
                <button type="submit" id="public_scan_btn" class="px-6 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-2xl text-sm shadow-lg hover:shadow-emerald-500/25 transition-all flex items-center justify-center space-x-2">
                    <span id="public_scan_btn_text">Lancer le Pré-Scan</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </form>
            <p class="text-xs text-emerald-200/80 mt-2 text-center">Pré-analyse publique instantanée • Sans inscription préalable • Saisissez directement www. ou votre nom de domaine</p>
        </div>

        <!-- Zone de Pré-résultat Public (Affichée après le scan sans déconnexion) -->
        <div id="public_result_panel" class="hidden mt-8 max-w-3xl mx-auto bg-slate-900/95 border border-emerald-500/40 rounded-3xl p-6 sm:p-8 text-left shadow-2xl backdrop-blur-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Aperçu du Pré-Diagnostic</span>
                    <h3 id="res_company_url" class="text-xl font-black text-white mt-1">https://exemple.fr</h3>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <div class="text-xs text-slate-400">Score de Vigilance</div>
                        <div id="res_score_badge" class="text-2xl font-black text-amber-400">58 / 100</div>
                    </div>
                    <span id="res_risk_pill" class="px-3 py-1.5 bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded-xl text-xs font-bold">
                        Risque Modéré à Élevé
                    </span>
                </div>
            </div>

            <!-- Principaux signaux détectés -->
            <div class="py-6 space-y-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Signaux détectés nécessitant une justification légale :</h4>
                <div id="res_signals_list" class="space-y-3 text-xs">
                    <!-- Rempli en JS -->
                </div>
            </div>

            <!-- Formulaire de capture pour recevoir le diagnostic complet gratuit -->
            <div class="mt-4 p-6 bg-gradient-to-br from-slate-800/80 to-slate-950/80 rounded-2xl border border-emerald-500/30">
                <div class="text-center sm:text-left mb-4">
                    <h4 class="text-base font-bold text-white flex items-center space-x-2">
                        <span>📋</span>
                        <span>Recevoir le Diagnostic Climat Local Complet</span>
                    </h4>
                    <p class="text-xs text-slate-300 mt-1">
                        Obtenez l'audit juridique détaillé, le chiffrage précis de vos risques financiers (sanctions DGCCRF) et le plan d'action d'alignement pour votre entreprise.
                    </p>
                </div>

                <form id="lead_capture_form" onsubmit="submitLeadCapture(event)" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="hidden" id="lead_target_url" name="url">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">Nom de votre entreprise</label>
                        <input type="text" id="lead_company_name" required placeholder="Ex: SAS EcoTech" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">Nom & Prénom du responsable</label>
                        <input type="text" id="lead_contact_name" required placeholder="Ex: Alexandre Dupont" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">Email professionnel</label>
                        <input type="email" id="lead_email" required placeholder="contact@ecotech.fr" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">Téléphone de contact</label>
                        <input type="tel" id="lead_phone" placeholder="06 12 34 56 78" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 text-white rounded-xl text-xs focus:ring-2 focus:ring-emerald-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2 pt-2">
                        <button type="submit" id="lead_submit_btn" class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-xl text-xs shadow-lg transition-all flex items-center justify-center space-x-2">
                            <span>Demander mon Diagnostic Complet & Rapport PDF Gratuit</span>
                        </button>
                    </div>
                </form>

                <div id="lead_success_msg" class="hidden mt-4 p-4 bg-emerald-900/80 border border-emerald-500 text-emerald-200 text-xs rounded-xl font-bold text-center">
                    ✅ Votre demande a bien été enregistrée ! Un auditeur ClimatLocal vous transmettra votre dossier complet sous 24h ouvrées.
                </div>
            </div>
        </div>


    </div>
</div>

<!-- 6 Piliers d'Évaluation de Conformité (Basés sur les 45+ Textes & Référentiels) -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="text-center max-w-3xl mx-auto mb-16">
        <h2 class="text-xs uppercase tracking-widest text-emerald-700 font-black">
            <?php echo get_live_text('home', 'pillars_kicker', 'Méthodologie & Référentiels Européens'); ?>
        </h2>
        <p class="mt-2 text-3xl font-extrabold text-slate-900">
            <?php echo get_live_text('home', 'pillars_title', 'Les 6 Piliers d\'Évaluation de Conformité'); ?>
        </p>
        <p class="mt-3 text-sm text-slate-500 leading-relaxed">
            <?php echo get_live_text('home', 'pillars_subtitle', 'Notre moteur croise vos communications avec 45+ directives européennes, décrets français et normes internationales pour garantir l\'absence de greenwashing.'); ?>
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Pilier 1 -->
        <div class="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all hover:border-emerald-300">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xl mb-4">
                🇪🇺
            </div>
            <h3 class="text-base font-black text-slate-900 mb-2">
                <?php echo get_live_text('home', 'p1_title', '1. Directive EmpCO (2024/825) & Green Claims'); ?>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                <?php echo get_live_text('home', 'p1_desc', 'Interdiction stricte de revendiquer la neutralité carbone basée sur la seule compensation forestière et contrôle des allégations génériques sans label d\'excellence certifié.'); ?>
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-bold text-emerald-700">
                Code Conso L.121-2 &bull; Amende jusqu'à 300 000 €
            </div>
        </div>

        <!-- Pilier 2 -->
        <div class="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all hover:border-emerald-300">
            <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xl mb-4">
                📊
            </div>
            <h3 class="text-base font-black text-slate-900 mb-2">
                <?php echo get_live_text('home', 'p2_title', '2. Reporting CSRD & Normes ESRS'); ?>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                <?php echo get_live_text('home', 'p2_desc', 'Alignement avec les exigences de durabilité de la Directive (UE) 2022/2464, les normes ESRS (E1-Climat, E4-Biodiversité, E5-Économie Circulaire) et la Taxonomie Européenne.'); ?>
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-bold text-teal-700">
                DPEF L.225-102-1 &bull; Audit de Durabilité Obligatoire
            </div>
        </div>

        <!-- Pilier 3 -->
        <div class="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all hover:border-emerald-300">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xl mb-4">
                🌿
            </div>
            <h3 class="text-base font-black text-slate-900 mb-2">
                <?php echo get_live_text('home', 'p3_title', '3. Bilan GES, Décret BEGES & Normes Carbone'); ?>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                <?php echo get_live_text('home', 'p3_desc', 'Vérification de la comptabilité carbone selon le Décret n°2022-982, le GHG Protocol, l\'ISO 14064, la trajectoire SBTi et l\'application de la taxe MACF / CBAM aux frontières.'); ?>
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-bold text-emerald-700">
                Scope 1, 2, 3 &bull; Stratégie Nationale Bas Carbone (SNBC)
            </div>
        </div>

        <!-- Pilier 4 -->
        <div class="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all hover:border-emerald-300">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-xl mb-4">
                🏗️
            </div>
            <h3 class="text-base font-black text-slate-900 mb-2">
                <?php echo get_live_text('home', 'p4_title', '4. Produits, FDES / EPD & RE2020'); ?>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                <?php echo get_live_text('home', 'p4_desc', 'Conformité des fiches de déclaration environnementale et sanitaire (NF EN 15804), base INIES, Analyse du Cycle de Vie (ISO 14040) et règlement Écoconception ESPR.'); ?>
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-bold text-indigo-700">
                Bâtiment Durable &bull; ACV Dynamique Réglementaire
            </div>
        </div>

        <!-- Pilier 5 -->
        <div class="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all hover:border-emerald-300">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xl mb-4">
                🔄
            </div>
            <h3 class="text-base font-black text-slate-900 mb-2">
                <?php echo get_live_text('home', 'p5_title', '5. Économie Circulaire, Tri & Loi AGEC'); ?>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                <?php echo get_live_text('home', 'p5_desc', 'Audit des allégations de recyclabilité, traçabilité des filières REP, signalétique Info-Tri, réduction des emballages plastiques et obligations de réemploi.'); ?>
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-bold text-amber-700">
                Loi n°2020-105 &bull; Directive Déchets 2008/98/CE
            </div>
        </div>

        <!-- Pilier 6 -->
        <div class="bg-white p-7 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all hover:border-emerald-300">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xl mb-4">
                ⚖️
            </div>
            <h3 class="text-base font-black text-slate-900 mb-2">
                <?php echo get_live_text('home', 'p6_title', '6. Devoir de Vigilance, CS3D & Achats'); ?>
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                <?php echo get_live_text('home', 'p6_desc', 'Sécurisation de la chaîne de valeur, respect des droits humains et environnementaux (Loi n°2017-399, CS3D) et éligibilité aux clauses RSE des marchés publics.'); ?>
            </p>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] font-bold text-purple-700">
                Loi Sapin II &bull; Critères ESG Investisseurs & Grands Comptes
            </div>
        </div>
    </div>
</div>

<?php if ($isLoggedIn): ?>
<!-- Espace Pro & Outils RSE -->
<div class="bg-emerald-50 border-y border-emerald-100 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-climat-900 to-emerald-800 text-white rounded-3xl p-8 sm:p-12 shadow-xl flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="max-w-xl">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">
                    <?php echo get_live_text('home', 'pro_kicker', 'Espace Auditeur & Prospection Commerciale'); ?>
                </span>
                <h2 class="text-3xl font-black mt-2">
                    <?php echo get_live_text('home', 'pro_title', 'Accélérez vos missions d\'accompagnement RSE'); ?>
                </h2>
                <p class="mt-3 text-sm text-emerald-100 leading-relaxed">
                    <?php echo get_live_text('home', 'pro_desc', 'Générez des rapports PDF personnalisés, suivez votre pipeline de prospection, qualifiez les décideurs et générez des argumentaires de vente complets.'); ?>
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <a href="/api/scan.php" class="px-6 py-3.5 bg-white text-emerald-950 font-bold rounded-xl text-center shadow hover:bg-emerald-50 transition-colors">
                    Lancer un Audit Pro
                </a>
                <a href="/api/pipeline.php" class="px-6 py-3.5 bg-emerald-700/80 hover:bg-emerald-600 text-white font-bold rounded-xl text-center border border-emerald-500/50 transition-colors">
                    Pipeline Commercial
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
async function handlePublicScan(e) {
    e.preventDefault();
    const urlInput = document.getElementById('public_scan_url');
    const url = urlInput.value.trim();
    if (!url) return;

    const btn = document.getElementById('public_scan_btn');
    const btnText = document.getElementById('public_scan_btn_text');
    btnText.textContent = 'Scan IA en cours (10-20s)...';
    btn.disabled = true;

    const panel = document.getElementById('public_result_panel');
    const signalsList = document.getElementById('res_signals_list');

    try {
        const response = await fetch('/api/audit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ url: url })
        });

        const data = await response.json();
        btnText.textContent = 'Lancer le Pré-Scan';
        btn.disabled = false;

        if (data.error) {
            alert('Erreur : ' + data.error);
            return;
        }

        document.getElementById('res_company_url').textContent = data.targetUrl || url;
        document.getElementById('lead_target_url').value = data.targetUrl || url;

        const score = typeof data.score === 'number' ? data.score : (data.riskScore || 50);
        const scoreBadge = document.getElementById('res_score_badge');
        scoreBadge.textContent = score + ' / 100';
        
        if (score >= 75) {
            scoreBadge.className = 'text-2xl font-black text-emerald-400';
        } else if (score >= 50) {
            scoreBadge.className = 'text-2xl font-black text-amber-400';
        } else {
            scoreBadge.className = 'text-2xl font-black text-red-400';
        }

        const riskPill = document.getElementById('res_risk_pill');
        const riskLevel = data.riskLevel || (score < 40 ? 'critique' : (score < 60 ? 'eleve' : (score < 75 ? 'modere' : 'faible')));
        riskPill.textContent = 'Risque ' + riskLevel.toUpperCase();
        
        if (riskLevel === 'critique' || riskLevel === 'eleve') {
            riskPill.className = 'px-3 py-1.5 bg-red-500/20 text-red-300 border border-red-500/40 rounded-xl text-xs font-bold';
        } else if (riskLevel === 'modere') {
            riskPill.className = 'px-3 py-1.5 bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded-xl text-xs font-bold';
        } else {
            riskPill.className = 'px-3 py-1.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-xl text-xs font-bold';
        }

        // Display summary
        let html = '';
        if (data.summary) {
            html += `<div class="p-3.5 bg-slate-800/80 border border-emerald-500/30 rounded-xl text-slate-200 mb-4 leading-relaxed">
                <strong class="text-emerald-400 block mb-1">Synthèse de l'Analyse IA :</strong> ${data.summary}
            </div>`;
        }

        const claims = data.claims || [];
        if (claims.length === 0) {
            html += `<div class="p-3 bg-emerald-950/40 border border-emerald-800/60 rounded-xl text-emerald-200">
                ✅ Aucune allégation environnementale problématique majeure détectée sur cette page.
            </div>`;
        } else {
            claims.forEach(c => {
                const term = c.term || 'Allégation détectée';
                const quote = c.quoteInText || c.quote || '';
                const law = c.lawArticle || 'Directive Green Claims / Code Conso';
                const sanction = c.sanction || 'Sanctions réglementaires';
                const note = c.correctionNote || 'Mise en conformité requise.';

                html += `
                <div class="p-3.5 bg-red-950/40 border border-red-800/60 rounded-xl space-y-1.5">
                    <div class="flex items-center justify-between">
                        <strong class="text-red-300 text-sm">⚠️ ${term}</strong>
                        <span class="text-[10px] px-2 py-0.5 bg-red-900/60 text-red-200 rounded font-mono">${law}</span>
                    </div>
                    ${quote ? `<p class="text-slate-300 italic text-xs">« ${quote} »</p>` : ''}
                    <div class="text-[11px] text-amber-200 font-medium"><strong>Risque & Sanction :</strong> ${sanction}</div>
                    <div class="text-[11px] text-emerald-300"><strong>Action corrective :</strong> ${note}</div>
                </div>`;
            });
        }

        signalsList.innerHTML = html;
        panel.classList.remove('hidden');
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });

    } catch (err) {
        btnText.textContent = 'Lancer le Pré-Scan';
        btn.disabled = false;
        alert('Erreur lors du scan : ' + err.message);
    }
}

function submitLeadCapture(e) {
    e.preventDefault();
    const btn = document.getElementById('lead_submit_btn');
    btn.disabled = true;
    btn.textContent = 'Enregistrement de votre demande...';

    const payload = {
        url: document.getElementById('lead_target_url').value,
        company_name: document.getElementById('lead_company_name').value,
        contact_name: document.getElementById('lead_contact_name').value,
        email: document.getElementById('lead_email').value,
        phone: document.getElementById('lead_phone').value
    };

    fetch('/api/public_lead.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('lead_capture_form').classList.add('hidden');
            document.getElementById('lead_success_msg').classList.remove('hidden');
        } else {
            alert('Erreur : ' + (data.error || 'Impossible d\'enregistrer votre demande'));
            btn.disabled = false;
            btn.textContent = 'Demander mon Diagnostic Complet & Rapport PDF Gratuit';
        }
    })
    .catch(err => {
        alert('Erreur de connexion.');
        btn.disabled = false;
        btn.textContent = 'Demander mon Diagnostic Complet & Rapport PDF Gratuit';
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
