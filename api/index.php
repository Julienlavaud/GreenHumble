<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
$page_title = 'Pré-diagnostic environnemental';
require_once __DIR__ . '/includes/header.php';
?>
<main class="gh-public">
  <section class="gh-hero">
    <div class="gh-hero__inner">
      <p class="gh-eyebrow"><span class="gh-status-dot"></span> GreenHumble · Climat Local</p>
      <h1>Vos allégations environnementales méritent des preuves.</h1>
      <p class="gh-lead">Un pré-diagnostic de votre site repère les formulations à vérifier et les éléments qui peuvent demander une justification.</p>
      <form id="gh-scan-form" class="gh-scan-form" novalidate>
        <label for="gh-site-url">Adresse du site à analyser</label>
        <div class="gh-scan-form__row">
          <input id="gh-site-url" name="url" type="text" inputmode="url" autocomplete="url" placeholder="exemple.fr" required aria-describedby="gh-scan-help">
          <button id="gh-scan-submit" type="submit"><span class="gh-button-label">Lancer le pré-diagnostic</span><span aria-hidden="true">→</span></button>
        </div>
        <p id="gh-scan-help" class="gh-form-hint">Analyse publique · Sans compte · Le site doit être accessible en ligne</p>
      </form>
      <p class="gh-trust-note">Un signal automatisé est une piste de vérification, jamais une conclusion juridique.</p>
    </div>
    <div class="gh-hero__aside" aria-hidden="true">
      <div class="gh-orbit gh-orbit--one"></div><div class="gh-orbit gh-orbit--two"></div>
      <div class="gh-orbit__center"><span>GH</span><small>IMPACT<br>RESPONSABLE</small></div>
      <div class="gh-float-card gh-float-card--top"><span class="gh-mini-check">✓</span> Preuves à réunir</div>
      <div class="gh-float-card gh-float-card--bottom"><span class="gh-mini-leaf">↗</span> Signaux à examiner</div>
    </div>
  </section>

  <section id="gh-scan-status" class="gh-scan-status" aria-live="polite" aria-atomic="true" hidden></section>
  <section id="gh-scan-result" class="gh-result" aria-live="polite" hidden>
    <div class="gh-result__head">
      <div><p class="gh-eyebrow">Résultat du pré-diagnostic</p><h2 id="gh-result-domain"></h2></div>
      <div id="gh-result-score" class="gh-score"></div>
    </div>
    <p id="gh-result-summary" class="gh-result__summary"></p>
    <div class="gh-result__notice">Cette analyse automatique aide à prioriser une revue humaine. Elle ne vérifie pas les justificatifs internes de l’entreprise et ne constitue pas un avis juridique.</div>
    <div class="gh-claims">
      <h3>Signaux relevés</h3>
      <div id="gh-result-claims" class="gh-claims__list"></div>
    </div>
    <details class="gh-lead">
      <summary>Demander la revue complète de mon diagnostic</summary>
      <p>Vos coordonnées servent à traiter le suivi de cette demande et du scan associé. La case ci-dessous est facultative et concerne uniquement les communications commerciales ultérieures.</p>
      <form id="gh-lead-form" class="gh-lead-form">
        <input type="hidden" name="url" id="gh-lead-url">
        <input type="hidden" name="audit_ref" id="gh-lead-audit-ref">
        <label>Entreprise<input name="company_name" autocomplete="organization" maxlength="200" required></label>
        <label>Nom du contact<input name="contact_name" autocomplete="name" maxlength="150" required></label>
        <label>E-mail professionnel<input type="email" name="email" autocomplete="email" maxlength="150" required></label>
        <label>Téléphone <span>(facultatif)</span><input type="tel" name="phone" autocomplete="tel" maxlength="50"></label>
        <label class="gh-consent"><input type="checkbox" name="marketing_opt_in" value="true"><span>J’accepte de recevoir occasionnellement les informations et offres Climat Local. Facultatif.</span></label>
        <button type="submit" id="gh-lead-submit">Envoyer ma demande</button>
      </form>
      <p id="gh-lead-feedback" class="gh-lead-feedback" aria-live="polite"></p>
    </details>
  </section>

  <section class="gh-section">
    <div class="gh-section__heading"><p class="gh-eyebrow">Une première lecture, clairement cadrée</p><h2>Du repérage à la vérification</h2><p>Le pré-diagnostic aide à décider quoi examiner ensuite. Il ne remplace pas l’analyse du contexte, des preuves ni des obligations applicables à votre activité.</p></div>
    <div class="gh-feature-grid">
      <article class="gh-feature"><span class="gh-feature__number">01</span><h3>Repérer</h3><p>Le moteur analyse les contenus accessibles et fait remonter des formulations qui méritent une attention particulière.</p></article>
      <article class="gh-feature"><span class="gh-feature__number">02</span><h3>Documenter</h3><p>Chaque signal doit être rapproché de son contexte, de sa portée et des justificatifs détenus par l’entreprise.</p></article>
      <article class="gh-feature"><span class="gh-feature__number">03</span><h3>Agir</h3><p>Une personne qualifiée confirme les constats et détermine les prochaines étapes adaptées à la situation.</p></article>
    </div>
  </section>
  <section class="gh-bottom-cta"><div><p class="gh-eyebrow">Espace professionnel</p><h2>Retrouvez vos dossiers et outils de travail.</h2></div><a href="/api/login.php">Connexion à mon espace <span aria-hidden="true">→</span></a></section>
</main>
<script src="/api/assets/js/public-audit.js?v=2" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
