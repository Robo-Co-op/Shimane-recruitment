<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$db    = get_db();
$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

// Look up the invite
$st = $db->prepare("SELECT * FROM admin_users WHERE invite_token=? AND status='pending' LIMIT 1");
$st->execute([$token]);
$user = $st->fetch();

if (!$token || !$user) {
    $error = 'This invitation link is invalid or has already been used.';
} elseif ($user['invite_expires_at'] && strtotime($user['invite_expires_at']) < time()) {
    $error = 'This invitation link has expired. Please ask an admin to resend your invite.';
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm']  ?? '';
    if (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db->prepare("UPDATE admin_users
            SET password_hash=?, status='active', invite_token=NULL, invite_expires_at=NULL
            WHERE id=?")
           ->execute([password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accept Invitation — Shimane Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(155deg,#E5F6F4,#F8F2EE);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(61,191,175,.15);padding:40px 36px;width:100%;max-width:420px}
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
.ok{background:#E6FAF5;color:#1A6B56;border:1px solid #A8E6D5;border-radius:8px;padding:14px 16px;font-size:14px;margin-bottom:14px}
.note{font-size:12px;color:#A8C4BF;text-align:center;margin-top:14px}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="mark">RC</div>
    <div><div class="lname">Robo Co-op</div><div class="lsub">Shimane Admin</div></div>
  </div>

  <?php if ($done): ?>
    <div class="ok">✅ <strong>Account activated!</strong> Your password has been set and your account is now active.</div>
    <a href="/admin/login" class="btn" style="display:block;text-align:center;text-decoration:none;line-height:1.5">Sign In →</a>

  <?php elseif ($error): ?>
    <h1>Invalid Link</h1>
    <div class="err"><?= htmlspecialchars($error) ?></div>
    <p class="note"><a href="/admin/login" style="color:#3DBFAF">Back to sign in</a></p>

  <?php else: ?>
    <h1>Accept Invitation</h1>
    <p class="sub">Hi <?= htmlspecialchars($user['name']) ?>, set a password to activate your account.</p>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="/admin/accept-invite?token=<?= urlencode($token) ?>">
      <div class="fg">
        <label class="fl">New Password</label>
        <input class="fc" type="password" name="password" minlength="8" required autofocus placeholder="At least 8 characters">
      </div>
      <div class="fg">
        <label class="fl">Confirm Password</label>
        <input class="fc" type="password" name="confirm" required placeholder="Repeat your password">
      </div>
      <button class="btn" type="submit">Activate Account →</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
