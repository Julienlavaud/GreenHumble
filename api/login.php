<?php
/**
 * GreenHumble v1.1 — Connexion Admin / Pipeline
 * /api/login.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collaborator_auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? $_POST['username'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($identifier) && !empty($password)) {
        $user = authenticate_user($identifier, $password);
        if (!$user) {
            $user = gh_authenticate_collaborator($identifier, $password);
        }
        if ($user) {
            // Route each authenticated role to its own workspace; never send collaborators to admin.
            $destination = (($user['role'] ?? '') === 'admin')
                ? '/api/admin/index.php'
                : '/api/collaborateur/index.php';
            header('Location: ' . $destination);
            exit;
        } else {
            $error = "Identifiants incorrects";
        }
    } else {
        $error = "Identifiants incorrects";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" style="background-color: #121212;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — GreenHumble</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #121212; color: #E0E0E0; min-height: 100vh; display: flex; align-items: center; justify-content: center; p: 20px; }
        .login-card { background-color: #1E1E1E; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; width: 100%; max-width: 380px; padding: 32px; }
        .login-title { font-size: 20px; font-weight: 700; color: #FFFFFF; margin-bottom: 24px; text-align: center; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #E0E0E0; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px 14px; background-color: #242424; border: 1px solid #383838; border-radius: 6px; color: #FFFFFF; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #2A7AE2; }
        .btn-submit { width: 100%; padding: 12px; background-color: #2A7AE2; color: #FFFFFF; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; margin-top: 8px; }
        .btn-submit:hover { background-color: #1D5BBF; }
        .error-box { background-color: rgba(220, 38, 38, 0.15); border: 1px solid #dc2626; color: #fca5a5; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1 class="login-title">Connexion GreenHumble</h1>
        
        <?php if ($error): ?>
            <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="/api/login.php">
            <div class="form-group">
                <label for="identifier">Identifiant</label>
                <input type="text" id="identifier" name="identifier" required placeholder="votre adresse e-mail ou identifiant" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
