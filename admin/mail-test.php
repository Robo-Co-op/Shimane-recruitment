<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail.php';
require_auth('admin');

$result = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to   = trim($_POST['to'] ?? '');
    $type = $_POST['type'] ?? 'plain';

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $result = ['ok' => false, 'msg' => 'Invalid email address.'];
    } else {
        if ($type === 'plain') {
            $subject = 'Mail Test — Shimane IB';
            $body    = '<p>This is a plain test email from the Shimane IB admin panel.</p><p>If you see this, PHP mail() is working.</p>';
            $ok      = _admin_mail_html($to, $subject, $body);
            $result  = ['ok' => $ok, 'msg' => $ok ? 'mail() returned true — check your inbox (and spam).' : 'mail() returned false — PHP mail is not configured on this server.'];

        } elseif ($type === 'staff') {
            send_staff_notification('テスト 太郎 / Test Taro', $to, 'ja');
            $result = ['ok' => true, 'msg' => 'Staff notification sent to ' . htmlspecialchars($to)];

        } elseif ($type === 'applicant_ja') {
            $ok     = send_application_confirmation_ja($to, 'テスト 太郎');
            $result = ['ok' => $ok, 'msg' => $ok ? 'Applicant confirmation (JA) sent.' : 'mail() returned false.'];

        } elseif ($type === 'applicant_en') {
            $ok     = send_application_confirmation_en($to, 'Test Taro');
            $result = ['ok' => $ok, 'msg' => $ok ? 'Applicant confirmation (EN) sent.' : 'mail() returned false.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mail Test — Admin</title>
<style>
body { font-family: -apple-system, sans-serif; background: #F0F7F6; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.card { background: #fff; border-radius: 16px; padding: 36px 40px; max-width: 480px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
h2 { margin: 0 0 6px; font-size: 20px; color: #1E2D2B; }
p.sub { margin: 0 0 28px; font-size: 13px; color: #5A706B; }
label { display: block; font-size: 13px; font-weight: 600; color: #1E2D2B; margin-bottom: 6px; }
input, select { width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1.5px solid #C8DEDD; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
button { background: linear-gradient(135deg,#3DBFAF,#2A9485); color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; width: 100%; }
.result { margin-top: 20px; padding: 14px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; }
.ok  { background: #E6F9F2; color: #1A7A50; }
.err { background: #FDE8E8; color: #9B2222; }
.info { background: #EFF4F3; padding: 12px 16px; border-radius: 8px; font-size: 12px; color: #5A706B; margin-bottom: 20px; }
.info code { font-family: monospace; background: #dde; padding: 1px 4px; border-radius: 3px; }
a.back { display:inline-block; margin-top:18px; font-size:13px; color:#3DBFAF; text-decoration:none; }
</style>
</head>
<body>
<div class="card">
  <h2>📧 Mail Delivery Test</h2>
  <p class="sub">Send test emails to diagnose delivery issues.</p>

  <div class="info">
    SMTP_PASS configured: <code><?= (defined('SMTP_PASS') && SMTP_PASS !== '') ? 'YES ✓' : 'NO — secret not deployed yet' ?></code><br>
    SMTP host: <code>smtp.office365.com:587</code><br>
    From: <code>noreply@roboco-op.org</code>
  </div>

  <form method="post">
    <label>Send to</label>
    <input type="email" name="to" value="eliyahe@roboco-op.org" required>

    <label>Email type</label>
    <select name="type">
      <option value="plain">Plain test (bare mail())</option>
      <option value="staff">Staff notification (admin alert)</option>
      <option value="applicant_ja">Applicant confirmation — Japanese</option>
      <option value="applicant_en">Applicant confirmation — English</option>
    </select>

    <button type="submit">Send Test Email →</button>
  </form>

  <?php if ($result): ?>
  <div class="result <?= $result['ok'] ? 'ok' : 'err' ?>">
    <?= $result['ok'] ? '✓ ' : '✗ ' ?><?= htmlspecialchars($result['msg']) ?>
  </div>
  <?php endif; ?>

  <a class="back" href="/admin">← Back to dashboard</a>
</div>
</body>
</html>
