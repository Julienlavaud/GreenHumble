<?php
// /api/admin/documentation_calcul_scanner.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if (isset($_GET['download']) && $_GET['download'] === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="regles_calcul_scanner_climatlocal.txt"');
    echo "=================================================================\n";
    echo "CABINET CLIMAT LOCAL — DOCUMENTATION OFFICIELLE DES RÈGLES DE CALCUL\n";
    echo "Scanner de Conformité Environnementale & Empreinte Carbone Web\n";
    echo "=================================================================\n\n";
    echo "1. RÈGLES DE CALCUL DU SCORE DE CONFORMITÉ (0 À 100)\n";
    echo "-----------------------------------------------------------------\n";
    echo "Le score global de conformité (sur 100) est calculé sur la base d'une analyse multicritère pondérée répartie sur 6 piliers :\n\n";
    echo "A. Pilier 1 : Mots & Allégations Interdites (Directive EmpCO & Décret 2022-539)\n";
    echo "- Détection automatique des allégations absolues ou trompeuses (ex: 'neutre en carbone', '100% vert', 'éco-responsable', 'zéro émission').\n";
    echo "- Pénalité : -15 points par occurrence non étayée de terme strictement interdit, -8 points par terme à haut risque.\n\n";
    echo "B. Pilier 2 : Justification & Preuves Scientifiques\n";
    echo "- Vérification de la présence de données chiffrées publiques, de bilans GES Scopes 1-2-3 ou de certifications tierces (Label Bas-Carbone, Ecolabel).\n";
    echo "- Bonus de +20 points en cas de publication d'un rapport certifié par un OTI ou une FDES vérifiée.\n\n";
    echo "C. Pilier 3 : Clarté & Transparence de la Communication\n";
    echo "- Évaluation de la précision sémantique et de l'absence d'ambiguïté marketing.\n\n";
    echo "D. Pilier 4 : Risque Légal & Sanctions DGCCRF\n";
    echo "- Évaluation de l'exposition financière maximale au titre du Code de la Consommation (art. L.121-2) : jusqu'à 300 000 € ou 10% du CA annuel.\n\n";
    echo "E. Pilier 5 : Éco-conception Web & Empreinte Numérique\n";
    echo "- Mesure de l'efficacité énergétique du site, du volume des données transférées et du respect des bonnes pratiques GreenIT.\n\n";
    echo "F. Pilier 6 : Plan d'Action & Recommandations Correctives\n";
    echo "- Faisabilité et pertinence des correctifs proposés par le cabinet.\n\n";
    echo "Niveaux de Risque :\n";
    echo "- Score >= 75/100 : Risque FAIBLE (Conformité élevée)\n";
    echo "- Score 50-74/100 : Risque MODÉRÉ (Corrections recommandées)\n";
    echo "- Score < 50/100 : Risque CRITIQUE ou ÉLEVÉ (Exposition contentieuse majeure)\n\n";
    echo "-----------------------------------------------------------------\n";
    echo "2. RÈGLES DE CALCUL DE L'IMPACT CARBONE DU SCANNER\n";
    echo "-----------------------------------------------------------------\n";
    echo "L'empreinte carbone d'une page web scannée est estimée selon le modèle standardisé 'Sustainable Web Design Model' (SWDM) et les directives de l'ADEME :\n\n";
    echo "- Volume des données transférées (Poids de la page en Ko ou Mo).\n- Facteur d'émission par Mo de données transitées sur les réseaux et serveurs de datacenters (moyenne standard : 0,46 g CO2e par Mo).\n- Consommation énergétique des terminaux utilisateurs et des équipements réseau.\n\n";
    echo "Formule :\n";
    echo "Émissions (g CO2e) = [Poids de la page (Mo) x Facteur d'intensité carbone] + [Consommation active terminal].\n";
    echo "Un site mal éco-conçu (> 3 Mo par page) génère une empreinte par visite équivalente à plusieurs dizaines de grammes de CO2e, amplifiée par le nombre de visiteurs mensuels.\n\n";
    echo "=================================================================\n";
    echo "© 2026 Cabinet Climat Local - Tous droits réservés.\n";
    exit;
}

$page_title = "Documentation & Règles de Calcul du Scanner";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-[1850px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fadeIn">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 border border-emerald-500/20">
        <div class="space-y-2">
            <span class="px-3 py-1 bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 rounded-full text-xs font-bold uppercase tracking-wider">Référentiel Technique & Méthodologique</span>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Règles de Calcul du Scanner & Impact Carbone</h1>
            <p class="text-emerald-100/80 text-sm max-w-3xl">Documentation officielle des algorithmes d'évaluation de la conformité EmpCO / Green Claims et d'estimation de l'empreinte carbone numérique.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/api/admin/documentation_calcul_scanner.php?download=1" class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl text-xs font-bold transition-all shadow-md flex items-center space-x-2">
                <span>📥 Télécharger la Documentation (.TXT)</span>
            </a>
            <a href="/api/admin/console.php" class="px-4 py-3 bg-white/10 hover:bg-white/20 text-white rounded-2xl text-xs font-bold transition-all border border-white/10">
                <span>← Console Admin</span>
            </a>
        </div>
    </div>

    <!-- Documentation Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Card 1: Score Calculation -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 font-bold text-lg">
                    📊
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">1. Règles de Calcul du Score de Conformité (0 à 100)</h3>
                    <p class="text-xs text-slate-500">Évaluation multicritère répartie sur 6 piliers fondamentaux</p>
                </div>
            </div>

            <div class="space-y-4 text-xs text-slate-700">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5">
                    <h4 class="font-bold text-slate-900 text-sm">🔹 Pilier 1 : Mots & Allégations Interdites (Directive EmpCO)</h4>
                    <p class="text-slate-600 leading-relaxed">Détection des formulations absolues non prouvées (ex: <em>« neutre en carbone »</em>, <em>« 100% vert »</em>). Chaque terme interdit détecté applique une pénalité dégressive sur le score global (-15 pts par infraction critique).</p>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5">
                    <h4 class="font-bold text-slate-900 text-sm">🔹 Pilier 2 : Justification & Preuves Scientifiques</h4>
                    <p class="text-slate-600 leading-relaxed">Analyse de l'étayage scientifique : présence de bilans GES Scopes 1-2-3 publics, de méthodologies vérifiées par des tiers indépendants (OTI) ou de labels officiels reconnus.</p>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5">
                    <h4 class="font-bold text-slate-900 text-sm">🔹 Piliers 3, 4, 5 & 6 : Clarté, Risque Légal, Éco-conception & Plan d'Action</h4>
                    <p class="text-slate-600 leading-relaxed">Évaluation de l'exposition financière maximale au titre du Code de la Consommation (jusqu'à 300 000 € ou 10% du CA), de l'empreinte technique du site et de la pertinence des recommandations.</p>
                </div>

                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 font-semibold">
                    📈 Seuils de Risque : Vert (75-100), Ambre (50-74), Rouge (< 50 Critique).
                </div>
            </div>
        </div>

        <!-- Card 2: Carbon Footprint Calculation -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-700 font-bold text-lg">
                    🌱
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">2. Règles de Calcul de l'Impact Carbone du Scanner</h3>
                    <p class="text-xs text-slate-500">Modélisation de l'empreinte numérique selon le Sustainable Web Design Model</p>
                </div>
            </div>

            <div class="space-y-4 text-xs text-slate-700">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5">
                    <h4 class="font-bold text-slate-900 text-sm">🌿 Facteurs d'Émission du Web</h4>
                    <p class="text-slate-600 leading-relaxed">L'empreinte carbone d'une page web consultée dépend directement du volume de données transférées (en Ko/Mo) et de l'intensité carbone de l'électricité utilisée par les datacenters et les réseaux de télécommunication (référentiel moyen : 0,46 g CO2e par Mo).</p>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5">
                    <h4 class="font-bold text-slate-900 text-sm">⚡ Formule de Calcul Standard</h4>
                    <p class="text-slate-600 font-mono text-[11px] bg-white p-3 rounded-xl border border-slate-200">
                        Émissions CO2e / visite = (Poids Page Mo × 0.46 gCO2e) + Facteur Terminal
                    </p>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5">
                    <h4 class="font-bold text-slate-900 text-sm">💡 Levier d'Optimisation</h4>
                    <p class="text-slate-600 leading-relaxed">L'audit intègre systématiquement des préconisations d'éco-conception pour réduire le poids des ressources graphiques et scripts, combinant conformité réglementaire et sobriété numérique.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
