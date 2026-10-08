<?php
// /api/admin/users.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Location: /api/admin/index.php');
    exit;
}

$db = get_db_connection();
$msg = null;
$err = null;
$generatedPassInfo = null;

// Création d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'collaborateur';
    $teamId = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;

    if (!empty($username) && !empty($email) && !empty($password)) {
        try {
            $stmt = $db->prepare("INSERT INTO cl_users (username, email, password_hash, role, team_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $teamId]);
            $msg = "Collaborateur '$username' créé avec succès.";
        } catch (Exception $e) {
            $err = "Erreur : cet email ou pseudo existe déjà.";
        }
    } else {
        $err = "Veuillez renseigner tous les champs obligatoires.";
    }
}

// Régénération / Réinitialisation du mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = intval($_POST['user_id']);
    $customPass = trim($_POST['custom_password'] ?? '');
    
    // Si aucun mot de passe manuel n'est saisi, en générer un automatiquement
    $newPass = !empty($customPass) ? $customPass : 'Climat' . date('Y') . '!' . substr(bin2hex(random_bytes(3)), 0, 4);

    try {
        $stmt = $db->prepare("UPDATE cl_users SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
        
        $stmtU = $db->prepare("SELECT username, email FROM cl_users WHERE id = ?");
        $stmtU->execute([$userId]);
        $uInfo = $stmtU->fetch();

        $generatedPassInfo = [
            'user' => $uInfo['username'] ?? "ID #$userId",
            'email' => $uInfo['email'] ?? '',
            'password' => $newPass
        ];
        $msg = "Mot de passe réinitialisé avec succès pour " . htmlspecialchars($uInfo['username'] ?? '');
    } catch (Exception $e) {
        $err = "Erreur lors de la réinitialisation du mot de passe.";
    }
}

// Modification de l'équipe et du rôle d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = intval($_POST['user_id']);
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'collaborateur';
    $teamId = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;

    try {
        $stmt = $db->prepare("UPDATE cl_users SET username = ?, role = ?, team_id = ? WHERE id = ?");
        $stmt->execute([$username, $role, $teamId, $userId]);
        $msg = "Profil mis à jour.";
    } catch (Exception $e) {
        $err = "Erreur lors de la mise à jour.";
    }
}

// Suppression d'utilisateur
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM cl_users WHERE id = ?");
        $stmt->execute([$delId]);
        $msg = "Utilisateur supprimé.";
    } catch (Exception $e) {
        $err = "Impossible de supprimer cet utilisateur.";
    }
}

$teams = [];
try {
    $teams = $db->query("SELECT * FROM cl_teams ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

$users = [];
try {
    $users = $db->query("SELECT u.*, t.name as team_name FROM cl_users u LEFT JOIN cl_teams t ON u.team_id = t.id ORDER BY u.id DESC")->fetchAll();
} catch (Exception $e) {}

$page_title = "Gestion des Collaborateurs & Mots de Passe";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Utilisateurs & Équipes</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Gestion des Collaborateurs & Accès</h1>
            <p class="text-sm text-slate-500 mt-1">Créez des comptes, attribuez les équipes et régénérez les mots de passe</p>
        </div>
        <a href="/api/admin/teams.php" class="px-4 py-2 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold hover:bg-emerald-100 flex items-center space-x-1.5">
            <span>👥 Gérer les Pôles & Équipes</span>
        </a>
    </div>

    <?php if ($generatedPassInfo): ?>
        <div class="mb-6 p-6 bg-emerald-900 text-white rounded-3xl shadow-xl border border-emerald-700">
            <div class="flex items-center space-x-3 mb-2">
                <span class="text-2xl">🔑</span>
                <h3 class="text-lg font-black text-white">Nouveau Mot de Passe Généré</h3>
            </div>
            <p class="text-xs text-emerald-200 mb-3">Transmettez ces identifiants de connexion au collaborateur :</p>
            <div class="p-4 bg-emerald-950/80 rounded-2xl border border-emerald-600/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 font-mono text-sm">
                <div>
                    <span class="text-emerald-400">Email :</span> <strong><?php echo htmlspecialchars($generatedPassInfo['email']); ?></strong><br>
                    <span class="text-emerald-400">Mot de passe :</span> <span class="bg-emerald-800/80 px-2 py-0.5 rounded text-white font-black"><?php echo htmlspecialchars($generatedPassInfo['password']); ?></span>
                </div>
                <button onclick="navigator.clipboard.writeText('Email: <?php echo htmlspecialchars($generatedPassInfo['email']); ?> | MDP: <?php echo htmlspecialchars($generatedPassInfo['password']); ?>'); alert('Identifiants copiés !');" class="px-4 py-2 bg-white text-emerald-950 font-bold rounded-xl text-xs hover:bg-emerald-100 transition-colors">
                    📋 Copier
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center space-x-2">
            <span>✅ <?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl font-bold">
            <?php echo htmlspecialchars($err); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulaire Création Collaborateur -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm sticky top-24">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">+</span>
                    <span>Nouveau Compte Collaborateur</span>
                </h2>

                <form method="POST" action="/api/admin/users.php" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nom / Prénom</label>
                        <input type="text" name="username" required placeholder="Sarah Benali" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email professionnel</label>
                        <input type="email" name="email" required placeholder="sarah@climatlocal.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mot de passe initial</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Rôle Métier</label>
                        <select name="role" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="collaborateur">Consultant / Auditeur RSE</option>
                            <option value="manager">Manager d'équipe régionale</option>
                            <option value="admin">Administrateur complet</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Pôle / Équipe</label>
                        <select name="team_id" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Sans équipe --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="create_user" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold rounded-xl text-sm shadow transition-all">
                        + Créer le Collaborateur
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des Utilisateurs avec Actions Mot de Passe & Équipe -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Comptes Actifs (<?php echo count($users); ?>)</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($users)): ?>
                        <div class="p-8 text-center text-xs text-slate-400">
                            Aucun compte utilisateur.
                        </div>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <div class="p-6 hover:bg-slate-50/80 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($u['username']); ?></h3>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black <?php echo $u['role'] === 'admin' ? 'bg-red-100 text-red-800' : ($u['role'] === 'manager' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800'); ?>">
                                                <?php echo htmlspecialchars(strtoupper($u['role'])); ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-mono mt-0.5"><?php echo htmlspecialchars($u['email']); ?></p>
                                        <p class="text-xs text-emerald-800 font-semibold mt-1">Pôle : <?php echo htmlspecialchars($u['team_name'] ?? 'Non assigné'); ?></p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Bouton Régénérer MDP -->
                                        <form method="POST" action="/api/admin/users.php" class="inline" onsubmit="return confirm('Générer un nouveau mot de passe pour <?php echo htmlspecialchars($u['username']); ?> ?');">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="reset_password" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 rounded-xl text-xs font-bold transition-colors">
                                                🔑 Régénérer MDP
                                            </button>
                                        </form>

                                        <!-- Bouton Supprimer -->
                                        <a href="/api/admin/users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('Supprimer ce compte ?');" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-xs font-bold transition-colors">
                                            Supprimer
                                        </a>
                                    </div>
                                </div>

                                <!-- Éditeur rapide d'équipe et de rôle -->
                                <details class="mt-3 pt-3 border-t border-slate-100 text-xs">
                                    <summary class="font-bold text-emerald-700 cursor-pointer hover:underline">
                                        Modifier le rôle ou l'équipe
                                    </summary>
                                    <form method="POST" action="/api/admin/users.php" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($u['username']); ?>" class="p-2 border rounded-xl text-xs bg-white">
                                        <select name="role" class="p-2 border rounded-xl text-xs bg-white">
                                            <option value="collaborateur" <?php echo $u['role'] === 'collaborateur' ? 'selected' : ''; ?>>Collaborateur</option>
                                            <option value="manager" <?php echo $u['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <select name="team_id" class="p-2 border rounded-xl text-xs bg-white">
                                            <option value="">-- Sans équipe --</option>
                                            <?php foreach ($teams as $t): ?>
                                                <option value="<?php echo $t['id']; ?>" <?php echo $u['team_id'] == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_user" class="sm:col-span-3 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800">
                                            Enregistrer les modifications
                                        </button>
                                    </form>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
