(() => {
  'use strict';
  const form = document.getElementById('gh-scan-form');
  if (!form) return;
  const input = document.getElementById('gh-site-url');
  const submit = document.getElementById('gh-scan-submit');
  const status = document.getElementById('gh-scan-status');
  const result = document.getElementById('gh-scan-result');
  let activeUrl = '';
  let activeAuditRef = '';

  const showMessage = (message, kind) => {
    status.textContent = message;
    status.className = 'gh-scan-status gh-scan-status--' + kind;
    status.hidden = false;
  };
  const normalizeUrl = (value) => {
    const trimmed = value.trim();
    if (/^[a-z][a-z0-9+.-]*:\/\//i.test(trimmed) && !/^https?:\/\//i.test(trimmed)) {
      throw new Error('Utilisez une adresse de site commençant par http:// ou https://.');
    }
    const parsed = new URL(/^https?:\/\//i.test(trimmed) ? trimmed : 'https://' + trimmed);
    if (!['http:', 'https:'].includes(parsed.protocol) || !parsed.hostname || parsed.username || parsed.password || trimmed.length > 2048) {
      throw new Error('Cette adresse de site n’est pas valide.');
    }
    return parsed.toString();
  };
  const readJson = async (response) => {
    const type = response.headers.get('content-type') || '';
    if (!type.includes('application/json')) throw new Error('Le serveur a renvoyé une réponse inattendue. Réessayez dans quelques instants.');
    return response.json();
  };
  const claimText = (claim, keys, fallback) => {
    if (typeof claim === 'string') return claim;
    if (!claim || typeof claim !== 'object') return fallback;
    for (const key of keys) if (typeof claim[key] === 'string' && claim[key].trim()) return claim[key];
    return fallback;
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const value = input.value.trim();
    if (!value) {
      input.focus();
      showMessage('Saisissez l’adresse du site à analyser.', 'error');
      return;
    }
    try { activeUrl = normalizeUrl(value); }
    catch (error) { showMessage(error instanceof Error ? error.message : 'Adresse invalide.', 'error'); input.focus(); return; }
    result.hidden = true;
    status.hidden = false;
    showMessage('Analyse en cours. Cela peut prendre quelques instants…', 'loading');
    submit.disabled = true;
    submit.querySelector('.gh-button-label').textContent = 'Analyse en cours…';
    try {
      const response = await fetch('/api/audit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ url: activeUrl, type_audit: 'full' })
      });
      const data = await readJson(response);
      if (!response.ok || data.analysisComplete === false || data.status === 'analysis_incomplete') {
        const message = data.error || data.message || (response.status === 503
          ? 'L’analyse n’a pas pu être finalisée. Vérifiez la configuration du service puis réessayez.'
          : 'Le pré-diagnostic n’a pas abouti. Vérifiez l’adresse du site puis réessayez.');
        const details = [data.summary, data.diagnosticCode ? 'Référence technique : ' + data.diagnosticCode : ''].filter((item) => typeof item === 'string' && item.trim() && item.trim() !== message);
        throw new Error([message, ...details].join(' '));
      }
      activeAuditRef = typeof data.id === 'string' && /^aud_[A-Za-z0-9_-]{8,80}$/.test(data.id) ? data.id : '';
      const rawScore = data.riskScore ?? data.score;
      const score = Number(rawScore);
      if (rawScore === null || rawScore === undefined || rawScore === '' || !Number.isFinite(score) || score < 0 || score > 100) {
        throw new Error('Le serveur n’a pas fourni de score exploitable. Aucun résultat incomplet ne sera affiché comme un audit réussi.');
      }
      document.getElementById('gh-result-domain').textContent = data.companyName || data.suggested_domain || new URL(activeUrl).hostname;
      document.getElementById('gh-result-score').textContent = score + ' / 100';
      const summary = data.clientSummary && (data.clientSummary.riskVerdict || data.clientSummary.headline);
      document.getElementById('gh-result-summary').textContent = summary || 'Le scan est terminé. Examinez les signaux avant de tirer une conclusion.';
      document.getElementById('gh-lead-url').value = activeUrl;
      document.getElementById('gh-lead-audit-ref').value = activeAuditRef;
      const list = document.getElementById('gh-result-claims');
      list.replaceChildren();
      const claims = Array.isArray(data.detectedClaims) ? data.detectedClaims : (Array.isArray(data.claims) ? data.claims : []);
      if (!claims.length) {
        const empty = document.createElement('p');
        empty.className = 'gh-claim-empty';
        empty.textContent = 'Aucun signal prioritaire remonté par cette pré-analyse. Cela ne garantit pas la conformité du site.';
        list.append(empty);
      } else {
        claims.slice(0, 20).forEach((claim) => {
          const card = document.createElement('article');
          card.className = 'gh-claim';
          const title = document.createElement('h4');
          title.textContent = claimText(claim, ['term', 'claim', 'title', 'phrase'], 'Signal à examiner');
          card.append(title);
          const evidence = claimText(claim, ['quoteInText', 'quote', 'evidence'], '');
          const reference = claimText(claim, ['lawArticle', 'legalReference'], '');
          if (evidence) {
            const quote = document.createElement('p');
            quote.className = 'gh-claim__evidence';
            quote.textContent = '« ' + evidence + ' »';
            card.append(quote);
          }
          if (reference) {
            const legal = document.createElement('p');
            legal.className = 'gh-claim__reference';
            legal.textContent = 'Référence à vérifier : ' + reference;
            card.append(legal);
          }
          list.append(card);
        });
      }
      status.hidden = true;
      result.hidden = false;
      result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
      showMessage(error instanceof Error ? error.message : 'Une erreur est survenue. Réessayez.', 'error');
    } finally {
      submit.disabled = false;
      submit.querySelector('.gh-button-label').textContent = 'Lancer le pré-diagnostic';
    }
  });

  const leadForm = document.getElementById('gh-lead-form');
  leadForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = document.getElementById('gh-lead-submit');
    const feedback = document.getElementById('gh-lead-feedback');
    const fields = new FormData(leadForm);
    const payload = Object.fromEntries(fields.entries());
    payload.marketing_opt_in = fields.get('marketing_opt_in') === 'true';
    button.disabled = true;
    feedback.textContent = 'Envoi de votre demande…';
    try {
      const response = await fetch('/api/public_lead.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });
      const data = await readJson(response);
      if (!response.ok || data.success !== true) throw new Error(data.error || 'Votre demande n’a pas pu être enregistrée.');
      leadForm.reset();
      document.getElementById('gh-lead-url').value = activeUrl;
      document.getElementById('gh-lead-audit-ref').value = activeAuditRef;
      feedback.textContent = 'Votre demande est enregistrée. L’équipe pourra vous recontacter au sujet de ce diagnostic.';
      feedback.className = 'gh-lead-feedback gh-lead-feedback--success';
    } catch (error) {
      feedback.textContent = error instanceof Error ? error.message : 'Une erreur est survenue. Réessayez.';
      feedback.className = 'gh-lead-feedback gh-lead-feedback--error';
    } finally {
      button.disabled = false;
    }
  });
})();
