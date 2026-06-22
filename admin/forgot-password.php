<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mail.php';

$db   = get_db();
$msg  = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $st    = $db->prepare("SELECT * FROM admin_users WHERE email=? AND status='active' LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $db->prepare("UPDATE admin_users SET reset_token=?,reset_expires_at=? WHERE id=?")
           ->execute([$token, $expires, $user['id']]);
        $sent = send_password_reset($email, $user['name'], $token);
        if (!$sent) {
            // Dev fallback: show link on screen
            $base = (isset($_SERVER['HTTP_HOST'])
                ? ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST']
                : 'https://shimane-ib.roboco-op.org');
            $_SESSION['_reset_link'] = $base . '/admin/reset-password?token=' . $token;
        }
    }
    // Always show success to prevent email enumeration
    $msg = 'If that email is registered, a reset link has been sent.';
}

$reset_link = $_SESSION['_reset_link'] ?? '';
unset($_SESSION['_reset_link']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password — Shimane Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(155deg,#E5F6F4,#F8F2EE);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(61,191,175,.15);padding:40px 36px;width:100%;max-width:400px}
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
.ok{background:#E6FAF5;color:#1A6B56;border:1px solid #A8E6D5;border-radius:8px;padding:12px 14px;font-size:13px;margin-bottom:14px}
.warn{background:#FEF4E5;border:1px solid #F5A87A;color:#7A4A00;border-radius:8px;padding:12px 14px;font-size:13px;margin-bottom:14px}
.note{font-size:12px;color:#A8C4BF;text-align:center;margin-top:14px}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="mark">RC</div>
    <div><div class="lname">Robo Co-op</div><div class="lsub">Shimane Admin</div></div>
  </div>

  <h1>Forgot Password</h1>
  <p class="sub">Enter your email address and we'll send you a link to reset your password.</p>

  <?php if ($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($reset_link): ?>
  <div class="warn">
    <strong>Email could not be sent.</strong> Share this link manually:<br>
    <a href="<?= htmlspecialchars($reset_link) ?>" style="word-break:break-all;font-size:12px"><?= htmlspecialchars($reset_link) ?></a>
  </div>
  <?php endif; ?>

  <?php if (!$msg): ?>
  <form method="POST">
    <div class="fg">
      <label class="fl">Email Address</label>
      <input class="fc" type="email" name="email" required autofocus placeholder="your@email.com">
    </div>
    <button class="btn" type="submit">Send Reset Link →</button>
  </form>
  <?php endif; ?>

  <p class="note"><a href="/admin/login" style="color:#3DBFAF">← Back to sign in</a></p>
</div>
</body>
</html>
