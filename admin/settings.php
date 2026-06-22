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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!$name)  $err = 'Name is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Please enter a valid email address.';
        else {
            // Check email uniqueness (exclude self)
            $check = $db->prepare("SELECT id FROM admin_users WHERE email=? AND id!=?");
            $check->execute([$email, $uid]);
            if ($check->fetch()) {
                $err = 'That email address is already used by another account.';
            } else {
                $db->prepare("UPDATE admin_users SET name=?,email=? WHERE id=?")
                   ->execute([$name, $email, $uid]);
                $_SESSION['admin_user']['name']  = $name;
                $_SESSION['admin_user']['email'] = $email;
                $user['name']  = $name;
                $user['email'] = $email;
                $msg = 'Profile updated successfully.';
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
            $err = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 8) {
            $err = 'New password must be at least 8 characters.';
        } elseif ($new_pw !== $confirm) {
            $err = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $db->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
            $msg = 'Password changed successfully.';
        }
    }
}

// Reload fresh user row
$st = $db->prepare("SELECT * FROM admin_users WHERE id=?");
$st->execute([$uid]);
$user_row = $st->fetch();

admin_start('My Settings', '', '');
?>

<?php if ($msg): ?><div class="alert al-ok mb12"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err):  ?><div class="alert al-err mb12"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="g2" style="align-items:start">

  <!-- Profile Info -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct">👤 Profile</span></div>
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
            <label class="fl">Full Name <span style="color:var(--red)">*</span></label>
            <input class="fc" name="name" value="<?= htmlspecialchars($user_row['name']) ?>" required>
          </div>
          <div class="fg">
            <label class="fl">Email Address <span style="color:var(--red)">*</span></label>
            <input class="fc" type="email" name="email" value="<?= htmlspecialchars($user_row['email']) ?>" required>
          </div>
          <button type="submit" class="btn btn-p btn-sm">💾 Save Profile</button>
        </form>
      </div>
    </div>

    <!-- Account Info -->
    <div class="card">
      <div class="ch"><span class="ct">ℹ️ Account Info</span></div>
      <div class="cb">
        <table style="width:100%">
          <tr>
            <td class="tm" style="padding:6px 0;font-size:13px">Role</td>
            <td style="padding:6px 0"><span class="badge b-b"><?= htmlspecialchars($user_row['role']) ?></span></td>
          </tr>
          <tr>
            <td class="tm" style="padding:6px 0;font-size:13px">Account created</td>
            <td style="padding:6px 0;font-size:13px"><?= $user_row['created_at'] ? date('Y-m-d', strtotime($user_row['created_at'])) : '—' ?></td>
          </tr>
          <tr>
            <td class="tm" style="padding:6px 0;font-size:13px">Last login</td>
            <td style="padding:6px 0;font-size:13px"><?= $user_row['last_login'] ? date('Y-m-d H:i', strtotime($user_row['last_login'])) : 'This session' ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="ch"><span class="ct">🔒 Change Password</span></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="fg">
          <label class="fl">Current Password</label>
          <input class="fc" type="password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="fg">
          <label class="fl">New Password</label>
          <input class="fc" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
          <div class="fs12 tm" style="margin-top:3px">Minimum 8 characters.</div>
        </div>
        <div class="fg">
          <label class="fl">Confirm New Password</label>
          <input class="fc" type="password" name="confirm_password" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-p btn-sm">🔑 Change Password</button>
      </form>
    </div>
  </div>

</div>

<?php admin_end(); ?>
