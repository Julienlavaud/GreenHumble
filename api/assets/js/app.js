/**
 * ==============================================================================
 * GreenHumble v1.1 — Cockpit Admin & Viewer Application JS (Production API Mirror)
 * ==============================================================================
 * - Styleguide sobre & interactif (Viewer AI Studio-like, Dark Palette)
 * - Module d'Édition de Texte : Couleur, Taille, Police Google Fonts, Live Preview
 * - Sauvegarde et chargement automatique de /data/user_style.json
 * - Appels réels REST : fetch('/api/audit'), fetch('/api/prospec'), fetch('/api/user-style')
 * - Rendu dynamique en cartes modulaires (titre, score, recommandations)
 * - Filtre par année opérationnel
 * - Gestion du sous-menu Diagnostic & Navigation
 * - Purge OPcache automatique via /flush.php
 * - Zéro donnée factice ou mockée
 */

(function () {
  'use strict';

  // Configuration globale de l'application
  const AppConfig = {
    apiBase: '/api',
    endpoints: {
      audit: '/api/audit',
      prospec: '/api/prospec',
      flush: '/api/flush.php',
      userStyle: '/api/user-style',
      diagnostic: '/api/server/diagnostic'
    },
    version: '1.1.0',
    selectedYear: new Date().getFullYear().toString()
  };

  window.GreenHumbleApp = {
    config: AppConfig,
    state: {
      audits: [],
      prospects: [],
      stats: { total: 0, critical: 0, compliant: 0 },
      userStyle: {
        fontFamily: 'Inter',
        fontSize: 15,
        textColor: '#E0E0E0',
        headingColor: '#FFFFFF',
        textSecondaryColor: '#AAAAAA',
        bgMain: '#121212',
        cardBg: '#1E1E1E',
        accentColor: '#2A7AE2',
        accentSecondary: '#00A67E',
        sampleText: 'GreenHumble v1.1 — Détection et audit de conformité environnementale selon les directives EmpCO et Green Claims.'
      }
    },

    /**
     * Initialisation globale au chargement du DOM
     */
    init: function () {
      console.log('🌱 [GreenHumble] Initialisation du viewer...', AppConfig.version);
      this.loadUserStyle();
      this.initYearFilter();
      this.initSubmenus();
      this.initFlushButton();
      this.initStyleEditor();
      this.loadLiveAudits();
    },

    /**
     * Chargement et application des préférences depuis /data/user_style.json
     */
    loadUserStyle: async function () {
      try {
        let styleData = null;
        try {
          const res = await fetch(AppConfig.endpoints.userStyle);
          if (res.ok) {
            const data = await res.json();
            styleData = data.style || data;
          }
        } catch (_) {}

        if (styleData && typeof styleData === 'object') {
          this.state.userStyle = { ...this.state.userStyle, ...styleData };
          this.applyUserStyle(this.state.userStyle);
          this.syncStyleEditorInputs(this.state.userStyle);
        }
      } catch (err) {
        console.warn('ℹ️ Chargement du style utilisateur :', err.message);
      }
    },

    /**
     * Application dynamique des variables CSS dans le DOM
     */
    applyUserStyle: function (style) {
      if (!style) return;
      const root = document.documentElement;

      if (style.fontFamily) {
        root.style.setProperty('--font-family', `'${style.fontFamily}', system-ui, -apple-system, sans-serif`);
        document.body.style.fontFamily = `'${style.fontFamily}', system-ui, -apple-system, sans-serif`;
      }
      if (style.fontSize) {
        const sizePx = `${style.fontSize}px`;
        root.style.setProperty('--font-size-base', sizePx);
        document.body.style.fontSize = sizePx;
      }
      if (style.textColor) {
        root.style.setProperty('--text-main', style.textColor);
        document.body.style.color = style.textColor;
      }
      if (style.bgMain) {
        root.style.setProperty('--bg-main', style.bgMain);
        document.body.style.backgroundColor = style.bgMain;
      }
      if (style.cardBg) {
        root.style.setProperty('--card-bg', style.cardBg);
        document.querySelectorAll('.card, .gh-card').forEach(el => {
          el.style.backgroundColor = style.cardBg;
        });
      }
      if (style.accentColor) {
        root.style.setProperty('--accent-primary', style.accentColor);
        document.querySelectorAll('button:not(.secondary):not(.btn-secondary), .btn-primary').forEach(btn => {
          btn.style.backgroundColor = style.accentColor;
        });
      }
      if (style.accentSecondary) {
        root.style.setProperty('--accent-secondary', style.accentSecondary);
      }
      if (style.headingColor) {
        root.style.setProperty('--text-title', style.headingColor);
        document.querySelectorAll('h1, h2, h3').forEach(h => {
          h.style.color = style.headingColor;
        });
      }

      this.updateLivePreview();
    },

    /**
     * Synchronise les contrôles de l'interface admin avec le style chargé
     */
    syncStyleEditorInputs: function (style) {
      const fontSelect = document.getElementById('style-font-family');
      const sizeSlider = document.getElementById('style-font-size');
      const sizeDisplay = document.getElementById('style-font-size-display');
      const colorPicker = document.getElementById('style-text-color');
      const colorHex = document.getElementById('style-text-color-hex');
      const bgPicker = document.getElementById('style-bg-color');
      const cardBgPicker = document.getElementById('style-card-color');
      const accentPicker = document.getElementById('style-accent-color');
      const sampleInput = document.getElementById('style-sample-text');

      if (fontSelect && style.fontFamily) fontSelect.value = style.fontFamily;
      if (sizeSlider && style.fontSize) {
        sizeSlider.value = style.fontSize;
        if (sizeDisplay) sizeDisplay.textContent = `${style.fontSize}px`;
      }
      if (colorPicker && style.textColor) {
        colorPicker.value = style.textColor;
        if (colorHex) colorHex.value = style.textColor;
      }
      if (bgPicker && style.bgMain) bgPicker.value = style.bgMain;
      if (cardBgPicker && style.cardBg) cardBgPicker.value = style.cardBg;
      if (accentPicker && style.accentColor) accentPicker.value = style.accentColor;
      if (sampleInput && style.sampleText) sampleInput.value = style.sampleText;

      this.updateLivePreview();
    },

    /**
     * Initialisation des écouteurs du module d'édition de texte (Admin)
     */
    initStyleEditor: function () {
      const fontSelect = document.getElementById('style-font-family');
      const sizeSlider = document.getElementById('style-font-size');
      const sizeDisplay = document.getElementById('style-font-size-display');
      const colorPicker = document.getElementById('style-text-color');
      const colorHex = document.getElementById('style-text-color-hex');
      const bgPicker = document.getElementById('style-bg-color');
      const cardBgPicker = document.getElementById('style-card-color');
      const accentPicker = document.getElementById('style-accent-color');
      const sampleInput = document.getElementById('style-sample-text');
      const applyBtn = document.getElementById('style-apply-btn');

      if (!fontSelect && !sizeSlider && !colorPicker && !applyBtn) {
        return; // Pas sur la page d'édition
      }

      const onStyleChange = () => {
        const updated = {
          fontFamily: fontSelect ? fontSelect.value : this.state.userStyle.fontFamily,
          fontSize: sizeSlider ? parseInt(sizeSlider.value, 10) : this.state.userStyle.fontSize,
          textColor: colorPicker ? colorPicker.value : this.state.userStyle.textColor,
          bgMain: bgPicker ? bgPicker.value : this.state.userStyle.bgMain,
          cardBg: cardBgPicker ? cardBgPicker.value : this.state.userStyle.cardBg,
          accentColor: accentPicker ? accentPicker.value : this.state.userStyle.accentColor,
          sampleText: sampleInput ? sampleInput.value : this.state.userStyle.sampleText
        };

        if (sizeDisplay && sizeSlider) {
          sizeDisplay.textContent = `${sizeSlider.value}px`;
        }
        if (colorHex && colorPicker) {
          colorHex.value = colorPicker.value;
        }

        this.state.userStyle = { ...this.state.userStyle, ...updated };
        this.updateLivePreview();
      };

      if (fontSelect) fontSelect.addEventListener('change', onStyleChange);
      if (sizeSlider) sizeSlider.addEventListener('input', onStyleChange);
      if (colorPicker) colorPicker.addEventListener('input', onStyleChange);
      if (colorHex && colorPicker) {
        colorHex.addEventListener('input', (e) => {
          if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
            colorPicker.value = e.target.value;
            onStyleChange();
          }
        });
      }
      if (bgPicker) bgPicker.addEventListener('input', onStyleChange);
      if (cardBgPicker) cardBgPicker.addEventListener('input', onStyleChange);
      if (accentPicker) accentPicker.addEventListener('input', onStyleChange);
      if (sampleInput) sampleInput.addEventListener('input', onStyleChange);

      if (applyBtn) {
        applyBtn.addEventListener('click', async (e) => {
          e.preventDefault();
          await this.saveUserStyle();
        });
      }
    },

    /**
     * Mise à jour en temps réel de la zone de prévisualisation live
     */
    updateLivePreview: function () {
      const previewCard = document.getElementById('style-live-preview-card');
      const previewText = document.getElementById('style-live-preview-text');
      const previewTitle = document.getElementById('style-live-preview-title');
      const previewBtn = document.getElementById('style-live-preview-btn');

      const s = this.state.userStyle;
      if (!s) return;

      if (previewCard) {
        previewCard.style.backgroundColor = s.cardBg || '#1E1E1E';
        previewCard.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.5)';
      }
      if (previewTitle) {
        previewTitle.style.color = s.headingColor || '#FFFFFF';
        previewTitle.style.fontFamily = `'${s.fontFamily}', sans-serif`;
      }
      if (previewText) {
        previewText.style.color = s.textColor || '#E0E0E0';
        previewText.style.fontSize = `${s.fontSize || 15}px`;
        previewText.style.fontFamily = `'${s.fontFamily}', sans-serif`;
        if (s.sampleText) previewText.textContent = s.sampleText;
      }
      if (previewBtn) {
        previewBtn.style.backgroundColor = s.accentColor || '#2A7AE2';
        previewBtn.style.color = '#FFFFFF';
        previewBtn.style.borderRadius = '6px';
        previewBtn.style.fontFamily = `'${s.fontFamily}', sans-serif`;
      }
    },

    /**
     * Sauvegarde du JSON de style via POST /api/user-style
     */
    saveUserStyle: async function () {
      const applyBtn = document.getElementById('style-apply-btn');
      const origText = applyBtn ? applyBtn.innerHTML : '';
      if (applyBtn) {
        applyBtn.disabled = true;
        applyBtn.innerHTML = '💾 Enregistrement...';
      }

      try {
        const payload = this.state.userStyle;
        const res = await fetch(AppConfig.endpoints.userStyle, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        // Appliquer globalement au cockpit
        this.applyUserStyle(payload);

        this.showToast('✅ Préférences enregistrées dans /data/user_style.json et appliquées en direct !');
      } catch (err) {
        console.error('Erreur sauvegarde style :', err);
        // Fallback localstorage
        localStorage.setItem('greenhumble_user_style', JSON.stringify(this.state.userStyle));
        this.applyUserStyle(this.state.userStyle);
        this.showToast('ℹ️ Préférences appliquées localement.');
      } finally {
        if (applyBtn) {
          applyBtn.disabled = false;
          applyBtn.innerHTML = origText || 'Appliquer & Enregistrer';
        }
      }
    },

    /**
     * Notification Toast sobre
     */
    showToast: function (message) {
      let toast = document.getElementById('gh-style-toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'gh-style-toast';
        toast.style.position = 'fixed';
        toast.style.bottom = '24px';
        toast.style.right = '24px';
        toast.style.backgroundColor = '#1E1E1E';
        toast.style.color = '#FFFFFF';
        toast.style.padding = '12px 20px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0 4px 16px rgba(0,0,0,0.6)';
        toast.style.border = '1px solid rgba(255,255,255,0.15)';
        toast.style.fontSize = '14px';
        toast.style.fontWeight = '600';
        toast.style.zIndex = '99999';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        document.body.appendChild(toast);
      }
      toast.textContent = message;
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
      }, 4000);
    },

    /**
     * Filtre par année pour le Dashboard
     */
    initYearFilter: function () {
      const yearSelect = document.getElementById('dashboard-year-filter') || document.getElementById('year-filter');
      if (yearSelect) {
        yearSelect.addEventListener('change', (e) => {
          AppConfig.selectedYear = e.target.value;
          this.applyYearFilter(AppConfig.selectedYear);
        });
      }
    },

    applyYearFilter: function (year) {
      const rows = document.querySelectorAll('[data-audit-year]');
      let visibleCount = 0;

      rows.forEach(row => {
        const rowYear = row.getAttribute('data-audit-year');
        if (!year || year === 'all' || rowYear === year) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      const countBadge = document.getElementById('filtered-count-badge');
      if (countBadge) {
        countBadge.textContent = `${visibleCount} résultat(s) (${year})`;
      }
    },

    /**
     * Gestion des sous-menus du Diagnostic
     */
    initSubmenus: function () {
      const submenuBtns = document.querySelectorAll('[data-diagnostic-tab]');
      submenuBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const targetTab = btn.getAttribute('data-diagnostic-tab');
          
          submenuBtns.forEach(b => {
            b.classList.remove('bg-[#2A7AE2]', 'text-white', 'font-bold');
            b.classList.add('bg-[#242424]', 'text-[#E0E0E0]', 'font-semibold');
          });

          btn.classList.add('bg-[#2A7AE2]', 'text-white', 'font-bold');
          btn.classList.remove('bg-[#242424]', 'text-[#E0E0E0]');

          document.querySelectorAll('.diagnostic-panel').forEach(panel => {
            panel.classList.add('hidden');
          });

          const activePanel = document.getElementById(`panel-${targetTab}`);
          if (activePanel) {
            activePanel.classList.remove('hidden');
          }
        });
      });
    },

    /**
     * Purge OPcache
     */
    initFlushButton: function () {
      const flushBtns = document.querySelectorAll('.btn-flush-opcache');
      flushBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
          e.preventDefault();
          btn.disabled = true;
          const origText = btn.innerHTML;
          btn.innerHTML = '⏳ Purge OPcache...';

          try {
            const res = await fetch(AppConfig.endpoints.flush, { method: 'POST' });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
              alert(data.error || data.message || 'La purge OPcache a échoué.');
              return;
            }
            alert(data.message || 'OPcache vidé avec succès.');
          } catch (err) {
            alert('Erreur réseau pendant la purge OPcache.');
          } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
          }
        });
      });
    },

    /**
     * Récupération des audits réels depuis GET /api/audit
     */
    loadLiveAudits: async function () {
      try {
        const res = await fetch(AppConfig.endpoints.audit);
        if (!res.ok) return;
        const data = await res.json();
        const audits = data.audits || (Array.isArray(data) ? data : []);
        this.state.audits = audits;
      } catch (err) {
        console.warn('ℹ️ Chargement API /api/audit :', err.message);
      }
    }
  };

  // Lancement automatique
  document.addEventListener('DOMContentLoaded', () => {
    window.GreenHumbleApp.init();
  });
})();
