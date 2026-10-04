<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!empty($_SESSION['admin_id'])) { header('Location: admin/'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanText($_POST['username'] ?? '', 80);
    $password = (string)($_POST['password'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        $error = 'Security token expired. Refresh the page and try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name FROM admins WHERE username = :username AND is_active = 1 LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: admin/');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
$csrf = $_SESSION['csrf'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login — DeafConnect</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css"><link rel="stylesheet" href="assets/css/admin.css"></head><body class="auth-page"><div class="auth-shell"><a href="index.php" class="auth-brand"><span class="brand-mark"><i class="ti ti-ear"></i></span>Deaf<span>Connect</span></a><div class="auth-card"><div class="auth-icon"><i class="ti ti-shield-lock"></i></div><span class="eyebrow">Administrator access</span><h1>Sign in to the admin area</h1><p>Manage bookings, review messages and save the accessibility audit baseline.</p><?php if ($error): ?><div class="auth-error" role="alert"><i class="ti ti-alert-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="btn btn-primary" type="submit">Sign in <i class="ti ti-arrow-right"></i></button></form><a class="back-link" href="index.php"><i class="ti ti-arrow-left"></i> Back to DeafConnect</a></div><p class="auth-foot">Change the seeded admin password immediately after setup.</p></div></body></html>
