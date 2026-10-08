<?php
// /api/admin/style.php — Module d'Édition de texte & Style Personnalisé
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Édition de Texte & Style";
include __DIR__ . '/../includes/header.php';

$userStylePath = __DIR__ . '/../../data/user_style.json';
if (!file_exists($userStylePath)) {
    $userStylePath = __DIR__ . '/../data/user_style.json';
}

$currentStyle = [
    'fontFamily' => 'Inter',
    'fontSize' => 15,
    'textColor' => '#E0E0E0',
    'headingColor' => '#FFFFFF',
    'bgMain' => '#121212',
    'cardBg' => '#1E1E1E',
    'accentColor' => '#2A7AE2',
    'sampleText' => 'GreenHumble v1.1 — Détection et audit de conformité environnementale selon les directives EmpCO et Green Claims.'
];

if (file_exists($userStylePath)) {
    $loaded = json_decode(file_get_contents($userStylePath), true);
    if (is_array($loaded)) {
        $currentStyle = array_merge($currentStyle, $loaded);
    }
}
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fadeIn">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="/api/admin/index.php" class="hover:text-[#2A7AE2]">Administration</a>
                <span>/</span>
                <span class="text-white font-semibold">Édition de Texte & Style</span>
            </div>
            <h1 class="text-3xl font-black text-white">Module d’Édition de Texte & Typographie</h1>
            <p class="text-sm text-[#AAAAAA] mt-1">Personnalisez la typographie, la taille de police et la palette de l'application sans toucher au code.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/api/admin/index.php" class="px-4 py-2 bg-[#1E1E1E] hover:bg-[#252525] text-white border border-white/10 rounded-md text-xs font-bold transition-colors">
                ← Retour Cockpit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulaire de sélection (7 colonnes) -->
        <div class="lg:col-span-7 bg-[#1E1E1E] p-6 sm:p-8 rounded-2xl border border-white/10 shadow-xl space-y-6">
            <h3 class="text-lg font-bold text-white mb-4">Sélecteurs Typographiques</h3>

            <!-- 1. Police (Dropdown Google Fonts) -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Choisir la Police (Google Fonts)</label>
                <select id="style-font-family" class="w-full px-4 py-3 bg-[#242424] text-[#E0E0E0] border border-[#383838] rounded-md font-semibold text-sm focus:ring-2 focus:ring-[#2A7AE2] focus:outline-none">
                    <option value="Inter" <?php echo ($currentStyle['fontFamily'] === 'Inter') ? 'selected' : ''; ?>>Inter (Moderne & Sobre — Recommandé)</option>
                    <option value="Roboto" <?php echo ($currentStyle['fontFamily'] === 'Roboto') ? 'selected' : ''; ?>>Roboto (Neutre & Institutionnel)</option>
                    <option value="Plus Jakarta Sans" <?php echo ($currentStyle['fontFamily'] === 'Plus Jakarta Sans') ? 'selected' : ''; ?>>Plus Jakarta Sans (Moderne AI Studio)</option>
                    <option value="Outfit" <?php echo ($currentStyle['fontFamily'] === 'Outfit') ? 'selected' : ''; ?>>Outfit (Tech & Élégant)</option>
                    <option value="Poppins" <?php echo ($currentStyle['fontFamily'] === 'Poppins') ? 'selected' : ''; ?>>Poppins (Géométrique)</option>
                    <option value="Montserrat" <?php echo ($currentStyle['fontFamily'] === 'Montserrat') ? 'selected' : ''; ?>>Montserrat (Impact & Lisibilité)</option>
                </select>
            </div>

            <!-- 2. Taille de police (Slider px) -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider">Choisir la Taille de Police</label>
                    <span id="style-font-size-display" class="px-2.5 py-0.5 bg-[#2A7AE2] text-white font-mono text-xs font-bold rounded">
                        <?php echo $currentStyle['fontSize']; ?>px
                    </span>
                </div>
                <input type="range" id="style-font-size" min="12" max="22" value="<?php echo $currentStyle['fontSize']; ?>" step="1" 
                    class="w-full h-2 bg-[#2A2A2A] rounded-lg appearance-none cursor-pointer accent-[#2A7AE2]">
                <div class="flex justify-between text-[11px] text-slate-500 mt-1">
                    <span>12px (Compact)</span>
                    <span>15px (Standard)</span>
                    <span>22px (Grand)</span>
                </div>
            </div>

            <!-- 3. Couleur du texte principal (Picker) -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Choisir la Couleur du Texte Principal</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="style-text-color" value="<?php echo $currentStyle['textColor']; ?>" 
                        class="w-12 h-10 rounded border border-[#383838] bg-transparent cursor-pointer p-0.5">
                    <input type="text" id="style-text-color-hex" value="<?php echo $currentStyle['textColor']; ?>" 
                        class="flex-1 px-4 py-2.5 bg-[#242424] text-[#E0E0E0] border border-[#383838] rounded-md font-mono text-xs uppercase">
                </div>
            </div>

            <!-- 4. Couleurs de la palette -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-white/5">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1.5">Fond Général</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="style-bg-color" value="<?php echo $currentStyle['bgMain']; ?>" class="w-8 h-8 rounded border border-[#383838] p-0.5 cursor-pointer">
                        <span class="text-xs font-mono text-slate-400">#121212</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1.5">Fond Carte</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="style-card-color" value="<?php echo $currentStyle['cardBg']; ?>" class="w-8 h-8 rounded border border-[#383838] p-0.5 cursor-pointer">
                        <span class="text-xs font-mono text-slate-400">#1E1E1E</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1.5">Accent Bouton</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="style-accent-color" value="<?php echo $currentStyle['accentColor']; ?>" class="w-8 h-8 rounded border border-[#383838] p-0.5 cursor-pointer">
                        <span class="text-xs font-mono text-slate-400">#2A7AE2</span>
                    </div>
                </div>
            </div>

            <!-- 5. Texte d'exemple -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Texte d'Exemple pour la Prévisualisation</label>
                <input type="text" id="style-sample-text" value="<?php echo htmlspecialchars($currentStyle['sampleText']); ?>" 
                    class="w-full px-4 py-2.5 bg-[#242424] text-[#E0E0E0] border border-[#383838] rounded-md text-xs">
            </div>

            <div class="pt-4">
                <button id="style-apply-btn" class="w-full py-3.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-md text-sm transition-colors shadow-lg flex items-center justify-center space-x-2">
                    <span>💾 Appliquer & Enregistrer dans /data/user_style.json</span>
                </button>
            </div>
        </div>

        <!-- Zone de Prévisualisation Live (5 colonnes) -->
        <div class="lg:col-span-5 flex flex-col justify-between space-y-6">
            <div class="bg-[#121212] p-6 rounded-2xl border border-white/10 shadow-2xl">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">👁️ Prévisualisation Live</span>
                    <span class="text-[10px] px-2 py-0.5 bg-[#00A67E] text-white rounded font-bold">Rendu en direct</span>
                </div>

                <!-- Bloc Carte Prévisualisée -->
                <div id="style-live-preview-card" class="p-5 rounded-lg border border-white/10 transition-all" style="background-color: <?php echo $currentStyle['cardBg']; ?>; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                    <h3 id="style-live-preview-title" class="text-lg font-bold mb-2 transition-all" style="color: <?php echo $currentStyle['headingColor']; ?>; font-family: '<?php echo $currentStyle['fontFamily']; ?>', sans-serif;">
                        Audit de Conformité Green Claims
                    </h3>
                    
                    <p id="style-live-preview-text" class="leading-relaxed mb-4 transition-all" style="color: <?php echo $currentStyle['textColor']; ?>; font-size: <?php echo $currentStyle['fontSize']; ?>px; font-family: '<?php echo $currentStyle['fontFamily']; ?>', sans-serif;">
                        <?php echo htmlspecialchars($currentStyle['sampleText']); ?>
                    </p>

                    <div class="flex items-center justify-between pt-3 border-t border-white/5">
                        <span class="text-xs text-slate-400">Score de conformité : <strong class="text-[#00A67E]">88/100</strong></span>
                        <button id="style-live-preview-btn" class="px-4 py-2 text-xs font-semibold rounded" style="background-color: <?php echo $currentStyle['accentColor']; ?>; color: #FFFFFF;">
                            Consulter l'Audit
                        </button>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-[#1E1E1E] rounded-lg border border-white/5 text-xs text-slate-400 space-y-2">
                    <div class="font-bold text-white text-[11px] uppercase">Règles de style actives :</div>
                    <div>• Police courante : <span class="font-mono text-[#2A7AE2]"><?php echo $currentStyle['fontFamily']; ?></span></div>
                    <div>• Taille de base : <span class="font-mono text-[#2A7AE2]"><?php echo $currentStyle['fontSize']; ?>px</span></div>
                    <div>• Fichier cible : <code>/data/user_style.json</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
