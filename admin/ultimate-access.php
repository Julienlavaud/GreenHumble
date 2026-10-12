<?php
/**
 * GreenHumble v1.1 — Console d'Accès Ultime Serveur (Mode Secours)
 * /admin/ultimate-access.php
 * 
 * Fonctionne de manière 100% autonome et isolée.
 * ZÉRO dépendance externe (ni CSS, ni JS, ni IA, ni base de données, ni fichiers d'authentification).
 * Utilisable même si tout le reste du serveur ou du frontend est corrompu ou cassé.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configurer des cookies de session ultra-sécurisés
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Déconnexion
if (isset($_GET['logout'])) {
    $_SESSION['rescue_auth'] = false;
    unset($_SESSION['rescue_auth']);
    session_destroy();
    header('Location: ?');
    exit;
}

// Authentification autonome
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rescue_password'])) {
    $passwordInput = trim($_POST['rescue_password']);
    if ($passwordInput === 'Climatlocal31000') {
        $_SESSION['rescue_auth'] = true;
        header('Location: ?');
        exit;
    } else {
        $error = "Identifiants incorrects";
    }
}

// Vérifier l'accès
$isAuthenticated = isset($_SESSION['rescue_auth']) && $_SESSION['rescue_auth'] === true;

// Auto-découverte résiliente du dossier /data/
$dataDir = null;
$possibleDirs = [
    __DIR__ . '/../data/',
    __DIR__ . '/../../data/',
    __DIR__ . '/data/',
    dirname(__DIR__) . '/data/',
    dirname(dirname(__DIR__)) . '/data/'
];
foreach ($possibleDirs as $dir) {
    if (is_dir($dir)) {
        $dataDir = realpath($dir) . '/';
        break;
    }
}
if (!$dataDir) {
    $dataDir = realpath(__DIR__) . '/data/';
}

$filesToManage = [
    'scans' => 'scans.json',
    'dossiers' => 'dossiers.json',
    'listes' => 'listes.json',
    'scanner_groups' => 'scanner_groups.json',
    'user_style' => 'user_style.json'
];

// Si authentifié et action d'export complet demandée
if ($isAuthenticated && isset($_GET['export_all'])) {
    $exportData = [
        'platform' => 'GreenHumble Rescue Export',
        'exported_at' => date('c'),
        'data' => []
    ];
    foreach ($filesToManage as $key => $fileName) {
        $path = $dataDir . $fileName;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $parsed = json_decode($content, true);
            $exportData['data'][$key] = (json_last_error() === JSON_ERROR_NONE) ? $parsed : $content;
        } else {
            $exportData['data'][$key] = null;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=greenhumble-rescue-export-all-' . time() . '.json');
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Si authentifié et action de téléchargement individuel demandée
if ($isAuthenticated && isset($_GET['download']) && isset($filesToManage[$_GET['download']])) {
    $fileName = $filesToManage[$_GET['download']];
    $filePath = $dataDir . $fileName;
    if (file_exists($filePath)) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $fileName);
        readfile($filePath);
        exit;
    } else {
        die("Erreur : Fichier " . htmlspecialchars($fileName) . " introuvable sur le serveur.");
    }
}

// Analyse d'intégrité et de conformité des schémas
$reports = [];
if ($isAuthenticated) {
    foreach ($filesToManage as $key => $fileName) {
        $path = $dataDir . $fileName;
        $report = [
            'file' => $fileName,
            'path' => $path,
            'exists' => false,
            'size' => 0,
            'valid_json' => false,
            'corrupted' => false,
            'item_count' => 0,
            'schema_version' => '1.1.0',
            'warnings' => [],
            'errors' => []
        ];

        if (file_exists($path)) {
            $report['exists'] = true;
            $report['size'] = filesize($path);
            $rawContent = file_get_contents($path);
            
            if (empty(trim($rawContent))) {
                $report['warnings'][] = "Le fichier est vide.";
                $report['valid_json'] = true;
                $parsed = [];
            } else {
                $parsed = json_decode($rawContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $report['valid_json'] = true;
                    if (is_array($parsed)) {
                        $report['item_count'] = count($parsed);
                        
                        // Analyse de schéma et détection des corruptions d'enregistrements
                        if ($key === 'scans') {
                            foreach ($parsed as $idx => $item) {
                                if (!is_array($item)) {
                                    $report['errors'][] = "Ligne #$idx : Enregistrement non-structuré (doit être un objet).";
                                    $report['corrupted'] = true;
                                    continue;
                                }
                                if (!isset($item['id']) || empty($item['id'])) {
                                    $report['errors'][] = "Enregistrement #$idx : ID système manquant.";
                                    $report['corrupted'] = true;
                                }
                                if (!isset($item['url']) || empty($item['url'])) {
                                    $report['errors'][] = "Scan ID " . ($item['id'] ?? "#$idx") . " : URL manquante ou vide.";
                                }
                                if (!isset($item['type_scan']) || !in_array($item['type_scan'], ['diagnostic', 'pipeline', 'widget'])) {
                                    $report['warnings'][] = "Scan ID " . ($item['id'] ?? "#$idx") . " : 'type_scan' non standard ou manquant.";
                                }
                                if (!isset($item['date_scan'])) {
                                    $report['warnings'][] = "Scan ID " . ($item['id'] ?? "#$idx") . " : 'date_scan' temporelle manquante.";
                                }
                            }
                        } elseif ($key === 'dossiers') {
                            foreach ($parsed as $idx => $item) {
                                if (!is_array($item)) {
                                    $report['errors'][] = "Ligne #$idx : Enregistrement non-structuré.";
                                    $report['corrupted'] = true;
                                    continue;
                                }
                                if (!isset($item['id']) || empty($item['id'])) {
                                    $report['errors'][] = "Dossier #$idx : ID manquant.";
                                    $report['corrupted'] = true;
                                }
                                if (!isset($item['domaine']) || empty($item['domaine'])) {
                                    $report['errors'][] = "Dossier " . ($item['id'] ?? "#$idx") . " : Domaine manquant.";
                                    $report['corrupted'] = true;
                                }
                                if (!isset($item['scans']) || !is_array($item['scans'])) {
                                    $report['warnings'][] = "Dossier " . ($item['id'] ?? "#$idx") . " : Liste de scans 'scans' absente ou invalide.";
                                }
                            }
                        } elseif ($key === 'listes') {
                            foreach ($parsed as $idx => $item) {
                                if (!is_array($item) || !isset($item['id']) || !isset($item['nom'])) {
                                    $report['errors'][] = "Liste #$idx : Structure invalide ou clés obligatoires absentes.";
                                    $report['corrupted'] = true;
                                }
                            }
                        } elseif ($key === 'scanner_groups') {
                            foreach ($parsed as $idx => $item) {
                                if (!is_array($item) || !isset($item['id']) || !isset($item['nom'])) {
                                    $report['errors'][] = "Groupe #$idx : Clés système ID/Nom manquantes.";
                                    $report['corrupted'] = true;
                                }
                            }
                        }
                    } else if (is_object($parsed)) {
                        $report['item_count'] = 1;
                    } else {
                        $report['errors'][] = "Format JSON valide mais le conteneur racine n'est pas un tableau ou un objet standard.";
                        $report['corrupted'] = true;
                    }
                } else {
                    $report['corrupted'] = true;
                    $report['errors'][] = "JSON Corrompu : " . json_last_error_msg();
                }
            }
        } else {
            $report['errors'][] = "Fichier absent du disque dans " . htmlspecialchars($path);
        }
        $reports[$key] = $report;
    }
}

// Charger le contenu brut pour l'affichage à l'écran
$viewContent = '';
$viewTitle = '';
if ($isAuthenticated && isset($_GET['view']) && isset($filesToManage[$_GET['view']])) {
    $fileKey = $_GET['view'];
    $fileName = $filesToManage[$fileKey];
    $filePath = $dataDir . $fileName;
    $viewTitle = $fileName;
    if (file_exists($filePath)) {
        $viewContent = file_get_contents($filePath);
    } else {
        $viewContent = "Erreur : Fichier introuvable.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console Secours — Accès Ultime Serveur</title>
    <style>
        /* Thème sombre ultra-sobre et résilient (Zéro dépendance) */
        body {
            background-color: #0b0b0b;
            color: #d1d5db;
            font-family: 'Courier New', Courier, monospace;
            padding: 20px;
            margin: 0;
            line-height: 1.5;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .header {
            border-bottom: 2px solid #2a7ae2;
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .logout-link {
            color: #fca5a5;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            border: 1px solid #7f1d1d;
            padding: 4px 10px;
            background: #1f1212;
            border-radius: 4px;
        }
        .logout-link:hover {
            background: #7f1d1d;
            color: #ffffff;
        }
        .card {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-cols: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        .file-title {
            color: #ffffff;
            font-size: 15px;
            margin-top: 0;
            margin-bottom: 12px;
            border-bottom: 1px solid #222;
            padding-bottom: 6px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-success { background: #064e3b; color: #34d399; border: 1px solid #047857; }
        .badge-warning { background: #78350f; color: #fcd34d; border: 1px solid #b45309; }
        .badge-danger { background: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c; }
        
        .btn {
            display: inline-block;
            background-color: #2a7ae2;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
        }
        .btn:hover { background-color: #1d5bbf; }
        .btn-secondary { background-color: #2b2b2b; color: #e5e7eb; border: 1px solid #444; }
        .btn-secondary:hover { background-color: #444; }
        .btn-danger { background-color: #991b1b; }
        .btn-danger:hover { background-color: #b91c1c; }
        
        .alert-box {
            background-color: #1a0f0f;
            border-left: 4px solid #b91c1c;
            color: #fca5a5;
            padding: 10px;
            font-size: 11px;
            margin-top: 10px;
            max-height: 120px;
            overflow-y: auto;
        }
        .warning-box {
            background-color: #1c150c;
            border-left: 4px solid #d97706;
            color: #fde047;
            padding: 10px;
            font-size: 11px;
            margin-top: 10px;
            max-height: 100px;
            overflow-y: auto;
        }
        
        /* Formulaire de login noir minimaliste */
        .login-box {
            max-width: 380px;
            margin: 100px auto;
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
        }
        .form-control {
            width: 100%;
            background: #000000;
            border: 1px solid #333333;
            color: #ffffff;
            padding: 10px;
            font-family: monospace;
            font-size: 14px;
            border-radius: 4px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #2a7ae2;
            outline: none;
        }
        .login-error {
            color: #ef4444;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        pre {
            background-color: #050505;
            color: #34d399;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #222;
            font-size: 11px;
            overflow-x: auto;
            max-height: 400px;
            white-space: pre-wrap;
        }
        .action-bar {
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        code {
            background: #222;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 11px;
            color: #e5e7eb;
        }
    </style>
</head>
<body>
<div class="container">

    <?php if (!$isAuthenticated): ?>
        <!-- Formulaire de secours d'Accès Ultime (Indépendant) -->
        <div class="login-box">
            <h2 style="color:#ffffff; font-size: 16px; margin-bottom: 25px; font-weight: bold; text-transform: uppercase;">
                🔒 Mode Secours Administrateur
            </h2>
            <?php if ($error): ?>
                <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="?">
                <input 
                    type="password" 
                    name="rescue_password" 
                    class="form-control" 
                    placeholder="Mot de passe système" 
                    required 
                    autofocus
                />
                <button type="submit" class="btn" style="width: 100%; padding: 12px; font-size: 13px;">
                    S'authentifier sur le serveur
                </button>
            </form>
        </div>
    <?php else: ?>
        <!-- Console d'administration autonome connectée -->
        <div class="header">
            <h1>🛠️ CONSOLE SOUVERAINE DE SECOURS — ACCÈS ULTIME SERVEUR</h1>
            <a href="?logout=true" class="logout-link">Quitter le Mode Secours</a>
        </div>

        <div class="card" style="border-left: 4px solid #2a7ae2;">
            <p style="margin: 0; font-size: 12px;">
                💡 <strong>Mode Secours Actif</strong> : Cet utilitaire fonctionne sans base de données tierce, sans javascript complexe, sans styles cloud, ni moteurs IA. Il lit et écrit directement dans le dossier local <code><?php echo htmlspecialchars($dataDir); ?></code>.
            </p>
        </div>

        <div class="action-bar">
            <a href="?export_all=true" class="btn" style="background-color: #047857; font-size: 12px; padding: 10px 18px;">
                📦 EXPORTER TOUT EN UN FICHIER JSON GLOBAL
            </a>
            <a href="?" class="btn btn-secondary" style="font-size: 12px; padding: 10px 18px;">
                🔄 Actualiser l'analyse
            </a>
        </div>

        <div class="grid">
            <?php foreach ($reports as $key => $rep): ?>
                <div class="card">
                    <div class="file-title">📄 <?php echo htmlspecialchars($rep['file']); ?></div>
                    
                    <table style="width: 100%; font-size: 11px; border-collapse: collapse; margin-bottom: 15px;">
                        <tr>
                            <td style="padding: 4px 0; color: #888;">Présence disque :</td>
                            <td style="text-align: right;">
                                <?php if ($rep['exists']): ?>
                                    <span class="badge badge-success">OK</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">MANQUANT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; color: #888;">Taille fichier :</td>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo number_format($rep['size'] / 1024, 2); ?> Ko
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; color: #888;">Enregistrements :</td>
                            <td style="text-align: right; font-weight: bold; color: #2a7ae2;">
                                <?php echo $rep['item_count']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; color: #888;">Format JSON :</td>
                            <td style="text-align: right;">
                                <?php if ($rep['valid_json']): ?>
                                    <span class="badge badge-success">VALIDE</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">ERREUR</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; color: #888;">État d'intégrité :</td>
                            <td style="text-align: right;">
                                <?php if ($rep['corrupted']): ?>
                                    <span class="badge badge-danger">CORROMPU</span>
                                <?php else: ?>
                                    <span class="badge badge-success">SÉCURISÉ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <!-- Alertes d'Intégrité / Schéma -->
                    <?php if (!empty($rep['errors'])): ?>
                        <div class="alert-box">
                            <strong>❌ Erreurs de schéma détectées :</strong>
                            <?php foreach ($rep['errors'] as $err): ?>
                                <div style="margin-top: 3px;">• <?php echo htmlspecialchars($err); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($rep['warnings'])): ?>
                        <div class="warning-box">
                            <strong>⚠️ Avertissements (Non standard) :</strong>
                            <?php foreach ($rep['warnings'] as $warn): ?>
                                <div style="margin-top: 3px;">• <?php echo htmlspecialchars($warn); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 8px; margin-top: 15px; border-t: 1px solid #222; padding-top: 12px;">
                        <a href="?view=<?php echo urlencode($key); ?>#viewer" class="btn btn-secondary" style="flex: 1;">
                            👁️ Lire le brut
                        </a>
                        <a href="?download=<?php echo urlencode($key); ?>" class="btn" style="flex: 1;">
                            📥 Télécharger
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Afficheur de Fichier Brut (100% PHP, pas de JS requis pour afficher) -->
        <?php if (!empty($viewContent)): ?>
            <div id="viewer" class="card" style="margin-top: 25px; border-top: 3px solid #34d399;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="color:#ffffff; font-size:14px; margin:0;">
                        🔎 Contenu brut en lecture seule : <code><?php echo htmlspecialchars($viewTitle); ?></code>
                    </h3>
                    <a href="?" class="btn btn-secondary" style="padding: 4px 10px; font-size: 10px;">Masquer</a>
                </div>
                <pre><?php echo htmlspecialchars($viewContent); ?></pre>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>
</body>
</html>
