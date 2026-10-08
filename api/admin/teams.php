<?php
// /api/admin/teams.php
// REST API Modulaire & Contrôleur d'Équipes GreenHumble
// Gère : equipes, membres, roles, permissions, groupes_metiers
// Actions : GET, POST, PUT, DELETE

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/app_state.php';

// Détection d'un appel REST API (Client JSON, Fetch API, method PUT/DELETE ou paramètre REST)
$isRest = false;
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$reqMethod = $_SERVER['REQUEST_METHOD'];

if (strpos($accept, 'application/json') !== false || 
    strpos($contentType, 'application/json') !== false || 
    in_array($reqMethod, ['PUT', 'DELETE']) || 
    isset($_GET['rest']) || isset($_GET['api']) || isset($_GET['json']) ||
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/admin/teams') !== false && !strpos($_SERVER['REQUEST_URI'] ?? '', '.php')) {
    $isRest = true;
}

if ($isRest) {
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    if ($reqMethod === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $appState = load_app_state();
    $cabinets = $appState['cabinets'] ?? [];
    $collaborateurs = $appState['collaborators'] ?? [];

    switch ($reqMethod) {
        case 'GET':
            // GET_teams
            $db = get_db_connection();
            $dbTeams = [];
            $dbUsers = [];
            try {
                if ($db) {
                    $dbTeams = $db->query("SELECT * FROM cl_teams ORDER BY name ASC")->fetchAll();
                    $dbUsers = $db->query("SELECT u.id, u.username, u.email, u.role, u.team_id, t.name as team_name FROM cl_users u LEFT JOIN cl_teams t ON u.team_id = t.id")->fetchAll();
                }
            } catch (Exception $e) {}

            $equipesList = [];
            if (!empty($dbTeams)) {
                foreach ($dbTeams as $t) {
                    $equipesList[] = [
                        "id" => "team_" . $t['id'],
                        "nom" => $t['name'],
                        "region" => $t['region'] ?? '',
                        "chef" => $t['manager_name'] ?? $t['email'] ?? '',
                        "actif" => true
                    ];
                }
            } else {
                foreach ($cabinets as $c) {
                    $equipesList[] = [
                        "id" => $c['id'],
                        "nom" => $c['cabinetName'] ?? $c['nom'] ?? 'Cabinet Régional',
                        "region" => $c['region'] ?? 'France',
                        "chef" => $c['managerName'] ?? $c['email'] ?? '',
                        "actif" => true
                    ];
                }
            }

            $membresList = [];
            if (!empty($dbUsers)) {
                foreach ($dbUsers as $u) {
                    $membresList[] = [
                        "id" => "usr_" . $u['id'],
                        "nom" => $u['username'],
                        "email" => $u['email'],
                        "role" => $u['role'] ?? 'Auditeur RSE',
                        "equipe" => $u['team_name'] ?? 'Pôle Principal',
                        "statut" => "actif"
                    ];
                }
            } else {
                foreach ($collaborateurs as $col) {
                    $membresList[] = [
                        "id" => $col['id'],
                        "nom" => $col['name'],
                        "email" => $col['email'],
                        "role" => $col['role'] ?? 'Consultant',
                        "equipe" => "Pôle Audit & Conformité RSE",
                        "statut" => "actif"
                    ];
                }
            }

            echo json_encode([
                "success" => true,
                "endpoint" => "/api/admin/teams",
                "equipes" => $equipesList,
                "membres" => $membresList,
                "roles" => [
                    ["id" => "role_super_admin", "nom" => "Super Administrateur", "niveau" => 100, "description" => "Accès absolu à l'ensemble du cockpit"],
                    ["id" => "role_auditeur", "nom" => "Auditeur RSE", "niveau" => 50, "description" => "Gestion des scans, diagnostics et exports"],
                    ["id" => "role_commercial", "nom" => "Commercial B2B", "niveau" => 30, "description" => "Gestion de la prospection et des contacts"]
                ],
                "permissions" => [
                    ["code" => "config:ai:write", "label" => "Modifier le moteur IA"],
                    ["code" => "audit:run", "label" => "Lancer un audit RSE"],
                    ["code" => "teams:admin", "label" => "Administrer les équipes"]
                ],
                "groupes_metiers" => [
                    ["id" => "grp_climat", "nom" => "Experts Climat & ACV", "specialite" => "Bilan Carbone"],
                    ["id" => "grp_juridique", "nom" => "Juristes Green Claims", "specialite" => "Loi Climat & CSRD"]
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        case 'POST':
            // POST_teams : ajout collaborateur ou équipe
            $raw = file_get_contents("php://input");
            $body = json_decode($raw, true) ?: $_POST;

            $type = $body['type'] ?? 'membre';
            $nom = trim($body['nom'] ?? '');
            $email = trim($body['email'] ?? '');
            $role = trim($body['role'] ?? 'Auditeur RSE');
            $equipe = trim($body['equipe'] ?? $body['equipe_id'] ?? 'Pôle Audit & Conformité RSE');

            if (empty($nom)) {
                http_response_code(400);
                echo json_encode(["success" => false, "error" => "Le nom est obligatoire."]);
                exit;
            }

            if ($type === 'membre') {
                $newMembre = [
                    "id" => "usr_" . time(),
                    "nom" => $nom,
                    "email" => $email ?: strtolower(preg_replace('/\s+/', '.', $nom)) . "@greenhumble.local",
                    "role" => $role,
                    "equipe" => $equipe,
                    "statut" => "actif",
                    "created_at" => date('c')
                ];

                $collaborateurs[] = [
                    "id" => $newMembre['id'],
                    "name" => $newMembre['nom'],
                    "email" => $newMembre['email'],
                    "role" => $newMembre['role'],
                    "avatarColor" => "bg-emerald-600"
                ];
                $appState['collaborators'] = $collaborateurs;
                save_app_state($appState);

                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Nouveau collaborateur ajouté à l'équipe avec succès",
                    "membre" => $newMembre
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $newTeam = [
                    "id" => "cab_" . time(),
                    "cabinetName" => $nom,
                    "region" => $body['region'] ?? 'France',
                    "email" => $email,
                    "managerName" => $body['manager'] ?? $nom
                ];
                $cabinets[] = $newTeam;
                $appState['cabinets'] = $cabinets;
                save_app_state($appState);

                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Nouvelle équipe créée avec succès",
                    "equipe" => $newTeam
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'PUT':
            // PUT_teams : modification rôle / membre
            $raw = file_get_contents("php://input");
            $body = json_decode($raw, true) ?: [];

            $membreId = $body['membre_id'] ?? $body['id'] ?? '';
            $nouveauRole = $body['role'] ?? $body['role_id'] ?? null;
            $nouvelleEquipe = $body['equipe'] ?? $body['equipe_id'] ?? null;

            if (empty($membreId)) {
                http_response_code(400);
                echo json_encode(["success" => false, "error" => "Identifiant membre requis."]);
                exit;
            }

            $updated = false;
            foreach ($collaborateurs as &$col) {
                if ($col['id'] === $membreId) {
                    if ($nouveauRole) $col['role'] = $nouveauRole;
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                $appState['collaborators'] = $collaborateurs;
                save_app_state($appState);
            }

            echo json_encode([
                "success" => true,
                "message" => "Rôle et affectation mis à jour",
                "updated_member" => [
                    "id" => $membreId,
                    "role" => $nouveauRole,
                    "equipe" => $nouvelleEquipe,
                    "updated_at" => date('c')
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        case 'DELETE':
            // DELETE_teams : retrait membre / équipe
            $raw = file_get_contents("php://input");
            $body = json_decode($raw, true) ?: [];
            $delId = $_GET['membre_id'] ?? $_GET['id'] ?? $body['membre_id'] ?? $body['id'] ?? '';

            if (empty($delId)) {
                http_response_code(400);
                echo json_encode(["success" => false, "error" => "Identifiant requis."]);
                exit;
            }

            $appState['collaborators'] = array_values(array_filter($collaborateurs, function($c) use ($delId) {
                return $c['id'] !== $delId;
            }));
            $appState['cabinets'] = array_values(array_filter($cabinets, function($c) use ($delId) {
                return $c['id'] !== $delId;
            }));
            save_app_state($appState);

            echo json_encode([
                "success" => true,
                "message" => "Membre ou équipe retiré avec succès",
                "deleted_id" => $delId
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }
}

// Fallback HTML Dashboard si appel standard navigateur
if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Location: /api/admin/index.php');
    exit;
}

$db = get_db_connection();
$msg = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_team'])) {
    $name = trim($_POST['name'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $manager = trim($_POST['manager_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($name)) {
        try {
            $stmt = $db->prepare("INSERT INTO cl_teams (name, region, manager_name, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $region, $manager, $email, $phone]);
            $msg = "Équipe / Pôle '$name' créé avec succès.";
        } catch (Exception $e) {
            $err = "Erreur lors de la création de l'équipe.";
        }
    } else {
        $err = "Le nom de l'équipe est requis.";
    }
}

$teams = [];
try {
    $teams = $db->query("SELECT t.*, COUNT(u.id) as user_count FROM cl_teams t LEFT JOIN cl_users u ON t.id = u.team_id GROUP BY t.id ORDER BY t.name ASC")->fetchAll();
} catch (Exception $e) {}

$page_title = "Gestion des Équipes & Pôles Régionaux - ClimatLocal";
include __DIR__ . '/../includes/header.php';
?>
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Pôles & Équipes</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Gestion des Pôles & Équipes</h1>
            <p class="text-sm text-slate-500 mt-1">Structurez vos consultants par pôle géographique, typologie d'audit ou responsable d'équipe</p>
        </div>
        <a href="/api/admin/users.php" class="px-4 py-2 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold hover:bg-emerald-100 transition-colors flex items-center space-x-1.5">
            <span>👥 Gérer les Collaborateurs</span>
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center space-x-2">
            <span>✅ <?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm sticky top-24">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">+</span>
                    <span>Nouveau Pôle / Équipe</span>
                </h2>
                <form method="POST" action="/api/admin/teams.php" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nom de l'équipe *</label>
                        <input type="text" name="name" required placeholder="Ex: Pôle Sud-Ouest & Occitanie" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Région géographique</label>
                        <input type="text" name="region" placeholder="Nouvelle-Aquitaine, Occitanie..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Responsable du pôle</label>
                        <input type="text" name="manager_name" placeholder="Marc Vasseur" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <button type="submit" name="create_team" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold rounded-xl text-sm shadow transition-all">
                        + Créer le Pôle
                    </button>
                </form>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6">
                <h2 class="text-base font-bold text-slate-900 mb-4">Pôles Configurés (<?php echo count($teams); ?>)</h2>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($teams as $t): ?>
                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-sm text-slate-900"><?php echo htmlspecialchars($t['name']); ?></h3>
                                <p class="text-xs text-slate-500"><?php echo htmlspecialchars($t['region'] ?? ''); ?> • Manager : <?php echo htmlspecialchars($t['manager_name'] ?? 'Non défini'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
