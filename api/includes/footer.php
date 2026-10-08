<?php
// /api/includes/footer.php
$isAdminUser = !empty($_SESSION['admin_logged_in']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$currentPageKey = basename($_SERVER['PHP_SELF'], '.php');
?>
    </div>
    
    <!-- Footer Global GreenHumble -->
    <footer class="bg-slate-900 text-slate-400 border-t border-slate-800 py-10 mt-16 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if ($isLoggedIn): ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-black text-sm">G</div>
                        <span class="text-lg font-bold text-white">Green<span class="text-emerald-400">Humble</span></span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">Une marque Climat Local (climatlocal.com). Audit automatisé des allégations environnementales en conformité avec la directive EmpCO (UE 2024/825) et Green Claims.</p>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-200 uppercase tracking-wider mb-3">Outils RSE & Métier</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="/api/scan.php" class="hover:text-emerald-400 transition-colors">Scanner Pro Site & Rapport RSE</a></li>
                        <li><a href="/api/analyse.php" class="hover:text-emerald-400 transition-colors">Dossier d'Analyse & Diagnostic</a></li>
                        <li><a href="/api/pipeline.php" class="hover:text-emerald-400 transition-colors">Pipeline Commercial</a></li>
                        <li><a href="/api/dashboard.php" class="hover:text-emerald-400 transition-colors">Tableau de Bord & KPIs</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-200 uppercase tracking-wider mb-3">Console d'Administration</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="/api/admin/index.php" class="hover:text-emerald-400 transition-colors">Configuration Multi-IA & Modèles</a></li>
                        <li><a href="/api/admin/users.php" class="hover:text-emerald-400 transition-colors">Collaborateurs & Régénération MDP</a></li>
                        <li><a href="/api/admin/teams.php" class="hover:text-emerald-400 transition-colors">Pôles & Équipes Régionales</a></li>
                        <li><a href="/api/admin/companies.php" class="hover:text-emerald-400 transition-colors">Répertoire Clients & SIREN</a></li>
                        <li><a href="/api/admin/widget.php" class="hover:text-emerald-400 transition-colors">Administration des Widgets</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-200 uppercase tracking-wider mb-3">Conformité Légale</h4>
                    <p class="text-xs text-slate-400 mb-2">45+ Textes Référencés : EmpCO, Green Claims, CSRD (ESRS), Décret BEGES, RE2020 & Barème DGCCRF.</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-950 text-emerald-300 border border-emerald-800">
                        Directive UE 2024/825 Ready
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <div class="pt-6 border-t border-slate-800 text-center text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-2">
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span>&copy; <?php echo date('Y'); ?> GreenHumble • Une marque Climat Local (<a href="https://climatlocal.com" target="_blank" class="text-emerald-400 hover:underline font-bold">www.climatlocal.com</a>).</span>
                    <span class="inline-flex items-center text-emerald-400 font-bold bg-emerald-950/80 px-2.5 py-0.5 rounded-full border border-emerald-800">
                        🌱 Éco-conçu : 1,5g CO2e / consultation (Note A+)
                    </span>
                </div>
                <?php if ($isLoggedIn): ?>
                <div class="flex flex-wrap items-center gap-4 text-xs">
                    <a href="https://www.legifrance.gouv.fr" target="_blank" rel="noopener" class="hover:text-emerald-400 font-semibold transition-colors flex items-center gap-1"><span>🇫🇷</span> Legifrance</a>
                    <a href="https://eur-lex.europa.eu" target="_blank" rel="noopener" class="hover:text-emerald-400 font-semibold transition-colors flex items-center gap-1"><span>🇪🇺</span> EUR-Lex</a>
                    <a href="https://cnil.fr" target="_blank" rel="noopener" class="hover:text-emerald-400 font-semibold transition-colors flex items-center gap-1"><span>🔒</span> CNIL (RGPD)</a>
                    <span class="text-slate-700">|</span>
                    <a href="/api/admin/texts.php" class="hover:text-slate-300">Dictionnaire des textes</a>
                    <a href="/api/admin/console.php" class="hover:text-slate-300">Diagnostics Système</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <?php if ($isAdminUser): ?>
    <!-- BARRE FLOTTANTE D'ÉDITION DE TEXTE EN DIRECT (MODE ADMIN LIVE CMS) -->
    <div id="cl_cms_toolbar" class="fixed bottom-4 right-4 z-50 flex items-center gap-2 bg-slate-900/95 backdrop-blur-md text-white px-4 py-2.5 rounded-2xl shadow-2xl border border-slate-700/80 transition-all">
        <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs font-bold text-slate-200">Mode Admin</span>
        </div>
        
        <button type="button" id="cl_cms_toggle_btn" onclick="toggleCmsEditMode()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black rounded-xl text-xs flex items-center space-x-1.5 shadow transition-transform active:scale-95">
            <span id="cl_cms_toggle_icon">✏️</span>
            <span id="cl_cms_toggle_label">Activer l'Édition du Texte</span>
        </button>

        <div id="cl_cms_actions" class="hidden items-center space-x-2 pl-2 border-l border-slate-700">
            <button type="button" onclick="saveAllCmsChanges('<?php echo htmlspecialchars($currentPageKey, ENT_QUOTES, 'UTF-8'); ?>')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs flex items-center space-x-1 shadow transition-transform active:scale-95">
                <span>💾 Enregistrer</span>
            </button>
            <button type="button" onclick="cancelCmsChanges()" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs transition-colors">
                Annuler
            </button>
        </div>
    </div>

    <!-- Notification Toast CMS -->
    <div id="cl_cms_toast" class="fixed top-20 right-6 z-50 hidden px-4 py-3 bg-slate-900 text-white text-xs font-bold rounded-2xl shadow-2xl border border-slate-700 flex items-center space-x-2">
        <span id="cl_cms_toast_icon">✅</span>
        <span id="cl_cms_toast_msg">Modifications enregistrées</span>
    </div>

    <style>
        .cl-cms-active .cl-cms-editable {
            outline: 2px dashed #f59e0b !important;
            outline-offset: 3px;
            background-color: rgba(245, 158, 11, 0.08) !important;
            border-radius: 6px;
            cursor: text;
            transition: all 0.2s ease;
        }
        .cl-cms-active .cl-cms-editable:hover {
            background-color: rgba(245, 158, 11, 0.15) !important;
        }
        .cl-cms-active .cl-cms-editable:focus {
            outline: 2px solid #10b981 !important;
            background-color: rgba(16, 185, 129, 0.1) !important;
        }
        .cl-cms-modified {
            outline: 2px solid #10b981 !important;
            background-color: rgba(16, 185, 129, 0.15) !important;
        }
    </style>

    <script>
    let isCmsEditActive = false;
    const originalContents = new Map();

    function toggleCmsEditMode() {
        isCmsEditActive = !isCmsEditActive;
        const body = document.body;
        const toggleBtn = document.getElementById('cl_cms_toggle_btn');
        const toggleLabel = document.getElementById('cl_cms_toggle_label');
        const actions = document.getElementById('cl_cms_actions');
        const editables = document.querySelectorAll('.cl-cms-editable');

        if (isCmsEditActive) {
            body.classList.add('cl-cms-active');
            toggleBtn.classList.remove('bg-amber-500', 'hover:bg-amber-600', 'text-slate-950');
            toggleBtn.classList.add('bg-slate-700', 'text-amber-300', 'border', 'border-amber-400');
            toggleLabel.textContent = "Mode Édition Actif";
            actions.classList.remove('hidden');
            actions.classList.add('flex');

            editables.forEach(el => {
                if (!originalContents.has(el)) {
                    originalContents.set(el, el.innerHTML);
                }
                el.setAttribute('contenteditable', 'true');
                el.addEventListener('input', () => {
                    el.classList.add('cl-cms-modified');
                });
            });
            showCmsToast('✏️ Mode édition activé : cliquez sur n\'importe quel texte encadré pour le corriger.');
        } else {
            body.classList.remove('cl-cms-active');
            toggleBtn.classList.add('bg-amber-500', 'hover:bg-amber-600', 'text-slate-950');
            toggleBtn.classList.remove('bg-slate-700', 'text-amber-300', 'border', 'border-amber-400');
            toggleLabel.textContent = "Activer l'Édition du Texte";
            actions.classList.add('hidden');
            actions.classList.remove('flex');

            editables.forEach(el => {
                el.removeAttribute('contenteditable');
            });
        }
    }

    function saveAllCmsChanges(pageKey) {
        const editables = document.querySelectorAll('.cl-cms-editable');
        const items = [];

        editables.forEach(el => {
            const page = el.getAttribute('data-page') || pageKey;
            const key = el.getAttribute('data-key');
            if (key) {
                // Clone and remove badge if present before saving
                const clone = el.cloneNode(true);
                const badge = clone.querySelector('.cl-cms-badge');
                if (badge) badge.remove();
                
                items.push({
                    page: page,
                    key: key,
                    content: clone.innerHTML.trim()
                });
            }
        });

        if (items.length === 0) {
            showCmsToast('Aucun texte à enregistrer.');
            return;
        }

        fetch('/api/admin/save_live_text.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save_batch', items: items })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                editables.forEach(el => el.classList.remove('cl-cms-modified'));
                showCmsToast('✅ ' + data.message);
            } else {
                showCmsToast('❌ Erreur : ' + (data.error || 'Sauvegarde impossible'));
            }
        })
        .catch(err => {
            showCmsToast('❌ Erreur réseau lors de la sauvegarde.');
        });
    }

    function cancelCmsChanges() {
        originalContents.forEach((originalHtml, el) => {
            el.innerHTML = originalHtml;
            el.classList.remove('cl-cms-modified');
        });
        showCmsToast('⏪ Modifications annulées.');
        toggleCmsEditMode();
    }

    function showCmsToast(msg) {
        const toast = document.getElementById('cl_cms_toast');
        const msgEl = document.getElementById('cl_cms_toast_msg');
        if (toast && msgEl) {
            msgEl.textContent = msg;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 4000);
        }
    }
    </script>
    <?php endif; ?>
    <!-- GreenHumble Viewer & Cockpit JS (Auto-versioned) -->
    <script src="/admin/assets/js/app.js?v=<?php echo floor(time()/60)*60; ?>"></script>
</body>
</html>
