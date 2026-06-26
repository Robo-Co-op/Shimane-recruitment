<?php
session_start();
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

// Already logged in
if (!empty($_SESSION['admin'])) {
    header('Location: ' . base_url('/admin')); exit;
}

$db     = get_db();
$error  = '';
$setup  = ($db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn() == 0);
$next   = base_url('/admin');

// ── First-time setup: create initial admin ──────────────────────────────────
if ($setup && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$name || !$email || strlen($pass) < 8) {
        $error = 'All fields required. Password must be at least 8 characters.';
    } else {
        $db->prepare("INSERT INTO admin_users (name,email,password_hash,role) VALUES (?,?,?,'admin')")
           ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $setup = false;
    }
}

// ── Login ───────────────────────────────────────────────────────────────────
if (!$setup && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = $db->prepare("SELECT * FROM admin_users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user && password_verify($pass, $user['password_hash'])) {
        if (($user['status'] ?? 'active') === 'pending') {
            $error = t('login_pending');
        } else {
            $_SESSION['admin'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
            $db->prepare("UPDATE admin_users SET last_login=CURRENT_TIMESTAMP WHERE id=?")->execute([$user['id']]);
            header('Location: ' . $next); exit;
        }
    } else {
        $error = t('login_invalid');
    }
}

$lang = admin_lang();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $setup ? t('setup_title') : t('sign_in') ?> — Shimane Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(155deg,#E5F6F4,#F8F2EE);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(61,191,175,.15);padding:40px 36px;width:100%;max-width:400px;position:relative}
.logo{display:flex;align-items:center;gap:10px;margin-bottom:28px}
.mark{width:40px;height:40px;background:linear-gradient(135deg,#3DBFAF,#2A9485);border-radius:11px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:14px}
.lname{font-size:16px;font-weight:700;color:#1E2D2B}
.lsub{font-size:11px;color:#A8C4BF}
h1{font-size:20px;font-weight:900;color:#1E2D2B;margin-bottom:6px}
p.sub{font-size:13px;color:#5A706B;margin-bottom:24px}
.fg{margin-bottom:14px}
.fl{display:block;font-size:13px;font-weight:700;margin-bottom:5px;color:#1E2D2B}
.fc{width:100%;padding:10px 12px;border:1.5px solid #E0EEEC;border-radius:9px;font-size:14px;outline:none;font-family:inherit;background:#fff;transition:border-color .15s}
.fc:focus{border-color:#3DBFAF;box-shadow:0 0 0 3px rgba(61,191,175,.12)}
.btn{width:100%;padding:12px;background:linear-gradient(135deg,#3DBFAF,#2A9485);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s}
.btn:hover{opacity:.9}
.err{background:#FEE8E8;color:#D94F4F;border:1px solid #F9C0C0;border-radius:8px;padding:10px 13px;font-size:13px;margin-bottom:14px}
.note{font-size:12px;color:#A8C4BF;text-align:center;margin-top:14px}
/* Language toggle */
.lang-tog{position:absolute;top:18px;right:20px;display:flex;gap:3px}
.lt-btn{font-size:11px;font-weight:700;padding:3px 8px;border-radius:5px;color:#A8C4BF;text-decoration:none;transition:all .15s;background:transparent;border:1.5px solid transparent}
.lt-btn.on{border-color:#3DBFAF;color:#2A9485;background:#E5F6F4}
.lt-btn:hover:not(.on){color:#5A706B}
</style>
</head>
<body>
<div class="box">
  <!-- Language toggle -->
  <div class="lang-tog">
    <a href="/admin/login?setlang=en" class="lt-btn <?= $lang==='en'?'on':'' ?>">EN</a>
    <a href="/admin/login?setlang=ja" class="lt-btn <?= $lang==='ja'?'on':'' ?>">日本語</a>
  </div>

  <div class="logo">
    <div class="mark">RC</div>
    <div><div class="lname">Robo Co-op</div><div class="lsub">Shimane Admin</div></div>
  </div>

  <?php if ($setup): ?>
  <h1><?= t('setup_title') ?></h1>
  <p class="sub"><?= t('setup_subtitle') ?></p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <div class="fg"><label class="fl"><?= t('setup_name') ?></label><input class="fc" name="name" type="text" required placeholder="e.g. Eliyah Eziwhuo" autofocus></div>
    <div class="fg"><label class="fl"><?= t('login_email') ?></label><input class="fc" name="email" type="email" required placeholder="you@roboco-op.org"></div>
    <div class="fg"><label class="fl"><?= t('login_password') ?> (min 8)</label><input class="fc" name="password" type="password" required minlength="8"></div>
    <button class="btn" type="submit" name="setup"><?= t('setup_btn') ?></button>
  </form>

  <?php else: ?>
  <h1><?= t('login_welcome') ?></h1>
  <p class="sub"><?= t('login_subtitle') ?></p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <div class="fg"><label class="fl"><?= t('login_email') ?></label><input class="fc" name="email" type="email" required placeholder="your@email.com" autofocus></div>
    <div class="fg"><label class="fl"><?= t('login_password') ?></label><input class="fc" name="password" type="password" required></div>
    <button class="btn" type="submit" name="login"><?= t('login_btn') ?></button>
  </form>
  <p class="note"><a href="/admin/forgot-password" style="color:#3DBFAF"><?= t('login_forgot') ?></a></p>
  <?php endif; ?>
</div>
</body>
</html>
