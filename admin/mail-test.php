<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail.php';
require_auth('admin');

// ── Run all diagnostics automatically ────────────────────────────────────────
$diag = [];

// 1. SMTP_PASS defined?
$pass_ok = defined('SMTP_PASS') && SMTP_PASS !== '';
$diag[] = ['SMTP_PASS in config.php', $pass_ok, $pass_ok ? 'Defined ✓' : 'NOT defined — secret missing or config.php not deployed'];

// 2. Port 587 reachable?
$sock587 = @stream_socket_client('tcp://smtp.hostinger.com:587', $en, $es, 8);
$p587_ok = $sock587 !== false;
if ($sock587) { $greeting587 = trim(fgets($sock587, 256)); fclose($sock587); } else { $greeting587 = $es; }
$diag[] = ['Port 587 reachable (smtp.hostinger.com)', $p587_ok, $p587_ok ? 'Open — ' . $greeting587 : 'BLOCKED — ' . $es];

// 3. Port 465 reachable?
$sock465 = @stream_socket_client('tcp://smtp.hostinger.com:465', $en2, $es2, 8);
$p465_ok = $sock465 !== false;
if ($sock465) fclose($sock465);
$diag[] = ['Port 465 reachable (smtp.hostinger.com)', $p465_ok, $p465_ok ? 'Open ✓' : 'BLOCKED'];

// 4. Full SMTP AUTH test (only if 587 is open and pass is set)
$smtp_auth_ok = false;
$smtp_auth_msg = 'Skipped (requires port 587 open + SMTP_PASS set)';
if ($p587_ok && $pass_ok) {
    $s = @stream_socket_client('tcp://smtp.hostinger.com:587', $en3, $es3, 10);
    if ($s) {
        stream_set_timeout($s, 10);
        $rd = function() use (&$s): string {
            $o = ''; while (!feof($s)) { $l = fgets($s, 512); if ($l === false) break; $o .= $l; if (strlen($l) >= 4 && $l[3] === ' ') break; } return trim($o);
        };
        $rd();
        fwrite($s, "EHLO robocoop.org\r\n"); $rd();
        fwrite($s, "STARTTLS\r\n");          $rd();
        stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fwrite($s, "EHLO robocoop.org\r\n"); $rd();
        fwrite($s, "AUTH LOGIN\r\n");        $rd();
        fwrite($s, base64_encode('noreply@robocoop.org') . "\r\n"); $rd();
        fwrite($s, base64_encode(SMTP_PASS) . "\r\n");
        $auth_r = $rd();
        fwrite($s, "QUIT\r\n"); fclose($s);
        $smtp_auth_ok = strpos($auth_r, '235') !== false;
        $smtp_auth_msg = $smtp_auth_ok ? 'AUTH LOGIN succeeded ✓ (235)' : 'AUTH failed: ' . $auth_r;
    } else {
        $smtp_auth_msg = 'Could not connect: ' . $es3;
    }
}
$diag[] = ['SMTP AUTH LOGIN test', $smtp_auth_ok, $smtp_auth_msg];

// 5. Send a real email if auth passed
$send_ok = false;
$send_msg = 'Skipped';
if ($smtp_auth_ok) {
    $send_ok = _smtp_send('eliyahe@roboco-op.org', 'Mail Test ✓ — Shimane IB', '<p>SMTP is working. This test email was sent from the Shimane IB admin panel via smtp.hostinger.com.</p>');
    $send_msg = $send_ok ? 'Email sent to eliyahe@roboco-op.org ✓' : 'SMTP send returned false';
}
$diag[] = ['Send real email to eliyahe@roboco-op.org', $send_ok, $send_msg];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mail Diagnostics — Admin</title>
<style>
body{font-family:-apple-system,sans-serif;background:#F0F7F6;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;margin:0}
.card{background:#fff;border-radius:16px;padding:36px 40px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.08)}
h2{margin:0 0 6px;font-size:20px;color:#1E2D2B}
p.sub{margin:0 0 28px;font-size:13px;color:#5A706B}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;padding:8px 12px;background:#F0F7F6;color:#5A706B;font-weight:600;border-bottom:2px solid #E0EEEC}
td{padding:10px 12px;border-bottom:1px solid #F0F7F6;vertical-align:top}
.ok{color:#1A7A50;font-weight:700} .fail{color:#9B2222;font-weight:700}
.detail{color:#5A706B;font-size:12px;margin-top:2px;font-family:monospace;word-break:break-all}
.badge{display:inline-block;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700}
.b-ok{background:#E6F9F2;color:#1A7A50} .b-fail{background:#FDE8E8;color:#9B2222} .b-skip{background:#EEE;color:#666}
a.back{display:inline-block;margin-top:24px;font-size:13px;color:#3DBFAF;text-decoration:none}
form{margin-top:28px;padding-top:24px;border-top:2px solid #F0F7F6}
label{display:block;font-size:13px;font-weight:600;color:#1E2D2B;margin-bottom:6px}
input,select{width:100%;box-sizing:border-box;padding:10px 14px;border:1.5px solid #C8DEDD;border-radius:8px;font-size:14px;margin-bottom:14px}
button{background:linear-gradient(135deg,#3DBFAF,#2A9485);color:#fff;border:none;padding:12px 28px;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;width:100%}
.result{margin-top:16px;padding:14px 18px;border-radius:10px;font-size:14px;font-weight:600}
.r-ok{background:#E6F9F2;color:#1A7A50} .r-fail{background:#FDE8E8;color:#9B2222}
</style>
</head>
<body>
<div class="card">
  <h2>📧 Mail Diagnostics</h2>
  <p class="sub">Auto-running all checks now…</p>

  <table>
    <thead><tr><th>Check</th><th>Result</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($diag as [$label, $ok, $detail]): ?>
    <tr>
      <td><?= htmlspecialchars($label) ?></td>
      <td><span class="badge <?= $ok ? 'b-ok' : ($detail === 'Skipped' || strpos($detail,'Skipped') === 0 ? 'b-skip' : 'b-fail') ?>"><?= $ok ? 'PASS' : ($detail === 'Skipped' || strpos($detail,'Skipped') === 0 ? 'SKIP' : 'FAIL') ?></span></td>
      <td><div class="detail"><?= htmlspecialchars($detail) ?></div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php
  // Manual send form
  $result = null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $to   = trim($_POST['to'] ?? '');
      $type = $_POST['type'] ?? 'staff';
      if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
          if ($type === 'staff') {
              send_staff_notification('テスト太郎 / Test', $to, 'ja');
              $result = ['ok' => true, 'msg' => 'Staff notification sent to ' . $to];
          } elseif ($type === 'applicant_ja') {
              $ok = send_application_confirmation_ja($to, 'テスト太郎');
              $result = ['ok' => $ok, 'msg' => $ok ? 'Applicant confirmation (JA) sent to ' . $to : 'Send failed'];
          } elseif ($type === 'applicant_en') {
              $ok = send_application_confirmation_en($to, 'Test Taro');
              $result = ['ok' => $ok, 'msg' => $ok ? 'Applicant confirmation (EN) sent to ' . $to : 'Send failed'];
          }
      } else {
          $result = ['ok' => false, 'msg' => 'Invalid email address'];
      }
  }
  ?>

  <form method="post">
    <label>Send to</label>
    <input type="email" name="to" value="eliyahe@roboco-op.org" required>
    <label>Type</label>
    <select name="type">
      <option value="staff">Staff notification (admin alert)</option>
      <option value="applicant_ja">Applicant confirmation — Japanese</option>
      <option value="applicant_en">Applicant confirmation — English</option>
    </select>
    <button type="submit">Send Test →</button>
  </form>

  <?php if ($result): ?>
  <div class="result <?= $result['ok'] ? 'r-ok' : 'r-fail' ?>"><?= $result['ok'] ? '✓ ' : '✗ ' ?><?= htmlspecialchars($result['msg']) ?></div>
  <?php endif; ?>

  <a class="back" href="/admin">← Back to dashboard</a>
</div>
</body>
</html>
