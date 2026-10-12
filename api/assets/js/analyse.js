document.addEventListener('click', async (event) => {
    const filterButton = event.target.closest('.mission-filter-btn');
    if (filterButton) {
        const mode = filterButton.dataset.filter;
        document.querySelectorAll('#mission-catalogue .mission-pro-card').forEach(card => {
            card.hidden = mode === 'recommended' && card.dataset.recommended !== '1';
        });
        document.querySelectorAll('.mission-filter-btn').forEach(button => {
            const selected = button === filterButton;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('bg-emerald-800', selected);
            button.classList.toggle('text-white', selected);
            button.classList.toggle('border', !selected);
            button.classList.toggle('border-slate-200', !selected);
            button.classList.toggle('bg-white', !selected);
            button.classList.toggle('text-slate-700', !selected);
        });
        const count = mode === 'all'
            ? document.querySelectorAll('#mission-catalogue .mission-pro-card').length
            : document.querySelectorAll('#mission-catalogue .mission-pro-card[data-recommended="1"]').length;
        const status = document.getElementById('mission-filter-status');
        if (status) status.textContent = mode === 'all' ? count + ' mission(s) dans le catalogue.' : count + ' mission(s) mise(s) en avant à partir des résultats du scan.';
        return;
    }
    const copyButton = event.target.closest('.mission-copy-btn');
    if (copyButton) {
        try {
            await navigator.clipboard.writeText(copyButton.dataset.copy || '');
            copyButton.innerHTML = '<span>Copié pour le devis</span><span aria-hidden="true">✓</span>';
        } catch (error) {
            window.prompt('Copiez le texte de l’offre :', copyButton.dataset.copy || '');
        }
    }
});

function openClaimCorrectionModal(claim) {
    document.getElementById('modal_claim_id').value = claim.id;
    document.getElementById('modal_claim_term').value = claim.term;
    document.getElementById('modal_validation_status').value = claim.consultantValidationStatus || 'en_attente';
    document.getElementById('modal_claim_severity').value = claim.severity || 'haut_risque';
    document.getElementById('modal_correction_note').value = claim.correctionNote || '';

    document.getElementById('claim_modal').classList.remove('hidden');
}

function closeClaimCorrectionModal() {
    document.getElementById('claim_modal').classList.add('hidden');
}

function openPdfModal() {
    document.getElementById('pdf_customizer_modal').classList.remove('hidden');
}

function closePdfModal() {
    document.getElementById('pdf_customizer_modal').classList.add('hidden');
}

function generateAndPrintPdf() {
    closePdfModal();

    const optHeader = document.getElementById('pdf_opt_header').checked;
    const optSynthese = document.getElementById('pdf_opt_synthese').checked;
    const optAllegations = document.getElementById('pdf_opt_allegations').checked;
    const optSanctions = document.getElementById('pdf_opt_sanctions').checked;
    const optMissions = document.getElementById('pdf_opt_missions').checked;
    const optTextes = document.getElementById('pdf_opt_textes').checked;

    const elHeader = document.getElementById('section_header_pro');
    const elSynthese = document.getElementById('section_synthese');
    const elAllegations = document.getElementById('section_allegations');
    const elSanctions = document.getElementById('section_sanctions');
    const elMissions = document.getElementById('section_missions');
    const elTextes = document.getElementById('section_textes');

    if (elHeader) elHeader.classList.toggle('pdf-hidden', !optHeader);
    if (elSynthese) elSynthese.classList.toggle('pdf-hidden', !optSynthese);
    if (elAllegations) elAllegations.classList.toggle('pdf-hidden', !optAllegations);
    if (elSanctions) elSanctions.classList.toggle('pdf-hidden', !optSanctions);
    if (elMissions) elMissions.classList.toggle('pdf-hidden', !optMissions);
    if (elTextes) elTextes.classList.toggle('pdf-hidden', !optTextes);

    setTimeout(() => {
        window.print();
        document.querySelectorAll('.pdf-hidden').forEach(el => el.classList.remove('pdf-hidden'));
    }, 200);
}
