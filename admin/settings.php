<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('viewer');
$db   = get_db();
$user = current_user();
$uid  = $user['id'];
$msg  = '';
$err  = '';

// ── App settings ─────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/includes/app_settings.php';

// ── Notification recipients helpers ──────────────────────────────────────────
function _notify_file(): string {
    return dirname(__DIR__) . '/db/notification_recipients.json';
}
function _load_recipients(): array {
    $f = _notify_file();
    if (file_exists($f)) {
        $d = json_decode(file_get_contents($f), true);
        if (is_array($d) && !empty($d)) return $d;
    }
    return ['midori.urashima@roboco-op.org', 'kazumi.hanaoka@roboco-op.org', 'eliyahe@roboco-op.org'];
}
function _save_recipients(array $r): void {
    $dir = dirname(__DIR__) . '/db';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/notification_recipients.json', json_encode(array_values($r)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_deadline' && can('admin')) {
        $nd = trim($_POST['app_deadline'] ?? '');
        $dt = $nd ? DateTime::createFromFormat('Y-m-d', $nd) : false;
        if (!$dt || $dt->format('Y-m-d') !== $nd) {
            $err = t('set_err_date');
        } else {
            save_app_deadline($nd);
            $fmt = admin_lang() === 'ja' ? date('Y年n月j日', strtotime($nd)) : date('j F Y', strtotime($nd));
            $msg = admin_lang() === 'ja'
                ? '締め切りを ' . $fmt . ' に更新しました。'
                : 'Application deadline updated to ' . $fmt . '.';
        }
    }

    if ($action === 'add_notify' && can('admin')) {
        $new_email = strtolower(trim($_POST['notify_email'] ?? ''));
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $err = t('set_err_email_inv');
        } else {
            $list = _load_recipients();
            if (in_array($new_email, $list)) {
                $err = t('set_err_email_lst');
            } else {
                $list[] = $new_email;
                _save_recipients($list);
                $msg = t('set_msg_rcpt_add');
            }
        }
    }

    if ($action === 'remove_notify' && can('admin')) {
        $rem = $_POST['notify_email'] ?? '';
        $list = array_values(array_filter(_load_recipients(), fn($e) => $e !== $rem));
        _save_recipients($list);
        $msg = t('set_msg_rcpt_del');
    }

    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!$name)  $err = t('set_err_name');
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = t('set_err_email_inv');
        else {
            $check = $db->prepare("SELECT id FROM admin_users WHERE email=? AND id!=?");
            $check->execute([$email, $uid]);
            if ($check->fetch()) {
                $err = t('set_err_email_use');
            } else {
                $db->prepare("UPDATE admin_users SET name=?,email=? WHERE id=?")
                   ->execute([$name, $email, $uid]);
                $_SESSION['admin_user']['name']  = $name;
                $_SESSION['admin_user']['email'] = $email;
                $user['name']  = $name;
                $user['email'] = $email;
                $msg = t('set_msg_profile');
            }
        }
    }

    if ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pw   = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $row = $db->prepare("SELECT password_hash FROM admin_users WHERE id=?");
        $row->execute([$uid]);
        $row = $row->fetch();

        if (!password_verify($current, $row['password_hash'])) {
            $err = t('set_err_cur_pw');
        } elseif (strlen($new_pw) < 8) {
            $err = t('set_err_pw_short');
        } elseif ($new_pw !== $confirm) {
            $err = t('set_err_pw_match');
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $db->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
            $msg = t('set_msg_pw');
        }
    }
}

// Reload fresh user row
$st = $db->prepare("SELECT * FROM admin_users WHERE id=?");
$st->execute([$uid]);
$user_row = $st->fetch();

admin_start(t('set_title'), '', '');
?>

<?php if ($msg): ?><div class="alert al-ok mb12"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err):  ?><div class="alert al-err mb12"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="g2" style="align-items:start">

  <!-- Profile Info -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct"><?= t('set_profile') ?></span></div>
      <div class="cb">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
            <div style="width:64px;height:64px;background:var(--mint);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:900;color:#fff;flex-shrink:0">
              <?= strtoupper(substr($user_row['name'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
              <div class="fw7" style="font-size:16px"><?= htmlspecialchars($user_row['name']) ?></div>
              <div class="tm fs12"><?= htmlspecialchars($user_row['email']) ?></div>
              <span class="badge b-b" style="margin-top:4px"><?= htmlspecialchars($user_row['role']) ?></span>
            </div>
          </div>
          <div class="fg">
            <label class="fl"><?= t('set_full_name') ?> <span style="color:var(--red)">*</span></label>
            <input class="fc" name="name" value="<?= htmlspecialchars($user_row['name']) ?>" required>
          </div>
          <div class="fg">
            <label class="fl"><?= t('set_email') ?> <span style="color:var(--red)">*</span></label>
            <input class="fc" type="email" name="email" value="<?= htmlspecialchars($user_row['email']) ?>" required>
          </div>
          <button type="submit" class="btn btn-p btn-sm"><?= t('set_save_profile') ?></button>
        </form>
      </div>
    </div>

    <!-- Account Info -->
    <div class="card">
      <div class="ch"><span class="ct"><?= t('set_account') ?></span></div>
      <div class="cb">
        <table style="width:100%">
          <tr>
            <td class="tm" style="padding:6px 0;font-size:13px"><?= t('set_role') ?></td>
            <td style="padding:6px 0"><span class="badge b-b"><?= htmlspecialchars($user_row['role']) ?></span></td>
          </tr>
          <tr>
            <td class="tm" style="padding:6px 0;font-size:13px"><?= t('set_created') ?></td>
            <td style="padding:6px 0;font-size:13px"><?= $user_row['created_at'] ? date('Y-m-d', strtotime($user_row['created_at'])) : '—' ?></td>
          </tr>
          <tr>
            <td class="tm" style="padding:6px 0;font-size:13px"><?= t('set_last_login') ?></td>
            <td style="padding:6px 0;font-size:13px"><?= $user_row['last_login'] ? date('Y-m-d H:i', strtotime($user_row['last_login'])) : t('set_this_session') ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="ch"><span class="ct"><?= t('set_change_pw') ?></span></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="fg">
          <label class="fl"><?= t('set_current_pw') ?></label>
          <input class="fc" type="password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="fg">
          <label class="fl"><?= t('set_new_pw') ?></label>
          <input class="fc" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
          <div class="fs12 tm" style="margin-top:3px"><?= t('set_pw_min') ?></div>
        </div>
        <div class="fg">
          <label class="fl"><?= t('set_confirm_pw') ?></label>
          <input class="fc" type="password" name="confirm_password" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-p btn-sm"><?= t('set_change_pw_btn') ?></button>
      </form>
    </div>
  </div>

</div>

<?php if (can('admin')): ?>
<?php $cur_deadline = get_app_deadline(); $app_open = is_application_open(); ?>
<?php $dl_fmt = admin_lang() === 'ja' ? date('Y年n月j日', strtotime($cur_deadline)) : date('j F Y', strtotime($cur_deadline)); ?>
<div class="card" style="margin-top:24px">
  <div class="ch"><span class="ct"><?= t('set_app_period') ?></span></div>
  <div class="cb">

    <!-- Status + live countdown -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:12px;
                  background:<?= $app_open ? '#ECFDF5' : '#FEF2F2' ?>;
                  border:1.5px solid <?= $app_open ? '#6EE7B7' : '#FECACA' ?>">
        <span style="font-size:20px"><?= $app_open ? '🟢' : '🔴' ?></span>
        <div>
          <div style="font-weight:800;font-size:13px;color:<?= $app_open ? '#065F46' : '#991B1B' ?>">
            <?= $app_open ? t('set_app_open') : t('set_app_closed_st') ?>
          </div>
          <div id="adm-countdown" style="font-size:12px;color:var(--warm-mid)">
            <?= $app_open ? t('set_app_calc') : t('set_app_passed') ?>
          </div>
        </div>
      </div>
    </div>

    <p class="tm fs13" style="margin-bottom:16px">
      <?= t('set_app_dl_lbl') ?> <strong><?= $dl_fmt ?></strong>
      <span style="color:var(--warm-light)">(<?= $cur_deadline ?>)</span><br>
      <?= t('set_app_dl_desc') ?>
    </p>

    <form method="POST" class="flex ic g8" style="max-width:440px;flex-wrap:wrap">
      <input type="hidden" name="action" value="update_deadline">
      <div style="flex:1;min-width:160px">
        <input class="fc" type="date" name="app_deadline"
               value="<?= htmlspecialchars($cur_deadline) ?>"
               min="<?= date('Y-m-d') ?>" required style="padding:10px 14px">
      </div>
      <button type="submit" class="btn btn-p btn-sm"><?= t('set_app_save_dl') ?></button>
    </form>

    <p class="tm fs12" style="margin-top:10px"><?= t('set_app_warning') ?></p>
  </div>
</div>

<script>
(function () {
  var dl     = new Date('<?= $cur_deadline ?>T23:59:59');
  var el     = document.getElementById('adm-countdown');
  var IS_JA  = <?= json_encode(admin_lang() === 'ja') ?>;
  var PASSED = <?= json_encode(t('set_app_passed')) ?>;
  var REM    = <?= json_encode(t('set_app_remaining')) ?>;
  function tick() {
    var diff = dl - new Date();
    if (diff <= 0) { if (el) el.textContent = PASSED; return; }
    var d = Math.floor(diff / 86400000);
    var h = Math.floor((diff % 86400000) / 3600000);
    var m = Math.floor((diff % 3600000)  / 60000);
    var s = Math.floor((diff % 60000)    / 1000);
    if (el) el.textContent = IS_JA
      ? (d + '日 ' + h + '時間 ' + m + '分 ' + s + '秒 ' + REM)
      : (d + 'd ' + h + 'h ' + m + 'm ' + s + 's ' + REM);
  }
  tick(); setInterval(tick, 1000);
})();
</script>
<?php endif; ?>

<?php if (can('admin')): ?>
<?php $recipients = _load_recipients(); ?>
<div class="card" style="margin-top:24px">
  <div class="ch"><span class="ct"><?= t('set_notify_title') ?></span></div>
  <div class="cb">
    <p class="tm fs13" style="margin-bottom:16px"><?= t('set_notify_desc') ?></p>

    <?php if ($recipients): ?>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px">
      <?php foreach ($recipients as $email): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg);border:1.5px solid var(--bdr);border-radius:8px;padding:10px 14px">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:32px;height:32px;border-radius:50%;background:var(--mint);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;color:#fff;flex-shrink:0">
            <?= strtoupper(substr($email, 0, 1)) ?>
          </div>
          <span style="font-size:14px;font-weight:600;color:var(--warm-dark)"><?= htmlspecialchars($email) ?></span>
        </div>
        <form method="POST" style="display:inline"
              onsubmit="return confirm(<?= json_encode(t('set_notify_rm_q')) ?>)">
          <input type="hidden" name="action" value="remove_notify">
          <input type="hidden" name="notify_email" value="<?= htmlspecialchars($email) ?>">
          <button type="submit" class="btn btn-d btn-xs"><?= t('remove') ?></button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert" style="background:#FEF4E5;border-color:#F5A87A;color:#7A4400;margin-bottom:16px">
      <?= t('set_notify_none') ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="flex ic g8" style="max-width:480px">
      <input type="hidden" name="action" value="add_notify">
      <div class="sr" style="flex:1">
        <span class="sic">✉️</span>
        <input class="si" type="email" name="notify_email"
               placeholder="<?= htmlspecialchars(t('set_notify_ph')) ?>" required>
      </div>
      <button type="submit" class="btn btn-p btn-sm"><?= t('set_notify_add') ?></button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php admin_end(); ?>
