<?php
// /api/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/app_state.php';
$st = load_app_state();
$agency = $st['agency'] ?? [];

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_logged_in']);
$userRole = $_SESSION['role'] ?? (isset($_SESSION['admin_logged_in']) ? 'admin' : '');
$userName = $_SESSION['username'] ?? ($_SESSION['admin_logged_in'] ? 'Admin' : '');
?>
<!DOCTYPE html>
<html lang="fr" class="h-full" style="background-color: #121212;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — GreenHumble' : 'GREENHUMBLE — Restons humbles face à l’impact réel.'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/admin/assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/api/assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232A7AE2'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>">
    <style>
        :root {
            --bg-main: #121212;
            --text-main: #E0E0E0;
            --text-secondary: #AAAAAA;
            --text-title: #FFFFFF;
            --card-bg: #1E1E1E;
            --shadow-soft: 0 4px 12px rgba(0, 0, 0, 0.5);
            --header-bg: #0D1117;
            --accent-primary: #2A7AE2;
            --accent-secondary: #00A67E;
            --font-family: 'Inter', system-ui, -apple-system, sans-serif;
            --font-size-base: 15px;
        }
        body { 
            font-family: var(--font-family); 
            font-size: var(--font-size-base);
            background-color: var(--bg-main); 
            color: var(--text-main); 
            line-height: 1.5;
            letter-spacing: 0.3px;
            font-weight: 400;
        }
        h1, h2, h3, h4, h5, h6 { color: var(--text-title); font-weight: 600; letter-spacing: 0.2px; }
        h1 { font-weight: 700; color: #FFFFFF; }
        h2 { font-weight: 700; color: #FFFFFF; }
        h3 { font-weight: 600; color: #FFFFFF; }
        .text-main { color: #E0E0E0; }
        .text-secondary { color: #AAAAAA; }
        button, .btn {
            background: #2A7AE2;
            color: #FFFFFF;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        button:hover, .btn:hover { background: #1D5BBF; color: #FFFFFF; }
        button.secondary, .btn.secondary, .btn-secondary { background: #00A67E; color: #FFFFFF; }
        button.secondary:hover, .btn.secondary:hover, .btn-secondary:hover { background: #008f6c; }
        .card, .gh-card {
            background: #1E1E1E;
            color: #E0E0E0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            padding: 20px;
            margin: 15px 0;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .card h3, .gh-card h3 { color: #FFFFFF; margin-bottom: 8px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"], input[type="url"], input[type="number"], input[type="tel"], select, textarea {
            background-color: #242424;
            color: #E0E0E0;
            border: 1px solid #383838;
            border-radius: 6px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #2A7AE2 !important;
            box-shadow: 0 0 0 3px rgba(42, 122, 226, 0.25) !important;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            bg: '#121212',
                            primary: '#2A7AE2',
                            primaryHover: '#1D5BBF',
                            secondary: '#00A67E',
                            secondaryHover: '#008f6c',
                            text: '#E0E0E0',
                            muted: '#AAAAAA',
                            header: '#0D1117',
                            card: '#1E1E1E'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full flex flex-col antialiased" style="background-color: #121212; color: #E0E0E0;">
    <!-- Navigation / Header (Fond sobre #0D1117, Logo GreenHumble à gauche, Nom d'utilisateur à droite) -->
    <header style="background-color: #0D1117;" class="text-white shadow-md sticky top-0 z-50 border-b border-white/10">
        <div class="max-w-[1850px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo GreenHumble à gauche -->
                <div class="flex items-center space-x-3">
                    <a href="/api/index.php" class="flex items-center space-x-2.5 group">
                        <div class="w-8 h-8 rounded-md bg-[#2A7AE2] flex items-center justify-center text-white font-bold shadow-xs">
                            <span class="text-base font-black">G</span>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white hover:text-[#2A7AE2] transition-colors">GreenHumble</span>
                    </a>
                </div>

                <!-- Navigation Links (Texte clair, liens accentués au survol) -->
                <nav class="hidden lg:flex items-center space-x-1">
                    <a href="/api/index.php" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'index.php' && $currentDir !== 'admin' ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Accueil</a>
                    
                    <a href="/api/audit.php" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'audit.php' ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Audit & Prospection</a>
                    
                    <a href="/api/scan.php" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'scan.php' ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Scanner Pro</a>
                    
                    <a href="/api/analyse.php" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'analyse.php' ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Dossier & Diag</a>
                    
                    <a href="/api/pipeline.php" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'pipeline.php' ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Pipeline</a>
                    
                    <a href="/api/dashboard.php" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'dashboard.php' || $currentPage === 'widgets.php' ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Dashboard</a>

                    <a href="/api/admin/index.php?tab=style" class="px-3.5 py-2 rounded-md text-sm font-semibold transition-colors <?php echo $currentPage === 'style.php' || (isset($_GET['tab']) && $_GET['tab'] === 'style') ? 'bg-[#2A7AE2] text-white' : 'text-slate-300 hover:text-[#2A7AE2] hover:bg-white/5'; ?>">Édition de texte</a>

                    <?php if ($userRole === 'admin' || isset($_SESSION['admin_logged_in'])): ?>
                    <a href="/api/admin/index.php" class="px-3.5 py-2 rounded-md text-sm font-semibold text-[#00A67E] hover:text-white hover:bg-white/5 transition-colors flex items-center space-x-1">
                        <span>Console Admin</span>
                    </a>
                    <?php endif; ?>
                </nav>

                <!-- Nom d'utilisateur à droite -->
                <div class="flex items-center space-x-3">
                    <?php 
                    $displayName = !empty($userName) ? $userName : (!empty($agency['consultantName']) ? $agency['consultantName'] : 'Julien Lavaud');
                    ?>
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-full bg-[#00A67E] text-white flex items-center justify-center font-bold text-xs">
                            <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                        </div>
                        <span class="text-sm font-semibold text-white hidden sm:inline"><?php echo htmlspecialchars($displayName); ?></span>
                    </div>

                    <?php if ($isLoggedIn): ?>
                        <a href="/api/logout.php" class="text-xs text-slate-300 hover:text-white px-2.5 py-1.5 rounded-md hover:bg-white/10 transition-colors">Déconnexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    <div class="flex-grow" style="background-color: #121212;">
