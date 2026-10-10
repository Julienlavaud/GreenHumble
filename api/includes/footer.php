<?php
$isAdminUser = !empty($_SESSION['admin_logged_in']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
</div><!-- .gh-content -->
<footer class="gh-footer"><span>© <?= date('Y') ?> GreenHumble · Une marque Climat Local</span><span>Les signaux automatisés demandent une vérification humaine.</span></footer>
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
    <?php if ($isLoggedIn): ?><script src="/api/assets/js/app.js?v=<?php echo floor(time()/60)*60; ?>"></script><?php endif; ?>
</div><!-- .gh-main-column -->
</div><!-- .gh-shell -->
</body>
</html>
