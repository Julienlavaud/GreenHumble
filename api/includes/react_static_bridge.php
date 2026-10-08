<?php
/**
 * Bridge Statique React ➔ PHP (Généré par react2static)
 * Permet d'inclure instantanément l'interface React compilée dans n'importe quel fichier PHP
 */
$reactStaticJs = 'assets/index-CqJyYbDm.js';
$reactStaticCss = 'assets/index-DtDJeF8j.css';
$reactStaticVersion = '1791034215946';

function render_greenhumble_react_app($rootId = 'root') {
    global $reactStaticJs, $reactStaticCss, $reactStaticVersion;
    $v = floor(time() / 60) * 60;
    $html = '';
    if (!empty($reactStaticCss)) {
        $html .= '<link rel="stylesheet" href="/dist/' . $reactStaticCss . '?v=' . $v . '">' . PHP_EOL;
    }
    $html .= '<div id="' . htmlspecialchars($rootId) . '"></div>' . PHP_EOL;
    if (!empty($reactStaticJs)) {
        $html .= '<script type="module" src="/dist/' . $reactStaticJs . '?v=' . $v . '"></script>' . PHP_EOL;
    }
    return $html;
}
?>