<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('admin');  // Only admins can manage the team
$db  = get_db();
$me  = current_user();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add member
    if (isset($_POST['add'])) {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'viewer';
        $pass  = $_POST['password'] ?? '';
        if (!in_array($role, ['viewer','editor','admin'])) $role = 'viewer';
        if (!$name || !$email || strlen($pass) < 8) {
            $err = 'All fields required. Password must be at least 8 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Invalid email address.';
        } else {
            try {
                $db->prepare("INSERT INTO admin_users (name,email,password_hash,role) VALUES (?,?,?,?)")
                   ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
                $msg = "Team member {$name} added successfully.";
            } catch (\PDOException $e) {
                $err = 'Email already in use.';
            }
        }
    }
    // Update role
    if (isset($_POST['update_role'])) {
        $uid  = (int)$_POST['uid'];
        $role = $_POST['role'] ?? 'viewer';
        if ($uid !== $me['id'] && in_array($role, ['viewer','editor','admin'])) {
            $db->prepare("UPDATE admin_users SET role=? WHERE id=?")->execute([$role, $uid]);
            $msg = 'Role updated.';
        } else {
            $err = 'Cannot change your own role.';
        }
    }
    // Remove member
    if (isset($_POST['remove'])) {
        $uid = (int)$_POST['uid'];
        if ($uid === $me['id']) {
            $err = 'You cannot remove your own account.';
        } else {
            $db->prepare("DELETE FROM admin_users WHERE id=?")->execute([$uid]);
            $msg = 'Team member removed.';
        }
    }
    // Change own password
    if (isset($_POST['change_pass'])) {
        $cur  = $_POST['current_pass'] ?? '';
        $new  = $_POST['new_pass'] ?? '';
        $st   = $db->prepare("SELECT password_hash FROM admin_users WHERE id=?");
        $st->execute([$me['id']]);
        $row  = $st->fetch();
        if (!password_verify($cur, $row['password_hash'])) {
            $err = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $err = 'New password must be at least 8 characters.';
        } else {
            $db->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")
               ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            $msg = 'Password changed successfully.';
        }
    }
}

$members = $db->query("SELECT * FROM admin_users ORDER BY created_at")->fetchAll();

admin_start('Team Management', 'team');
?>

<?php if ($msg): ?><div class="alert al-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert al-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="g2" style="align-items:start">
  <!-- Members table -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct">👥 Team Members (<?= count($members) ?>)</span></div>
      <div class="tw">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td>
              <div class="fw7"><?= htmlspecialchars($m['name']) ?>
                <?php if ($m['id'] == $me['id']): ?><span class="badge b-b" style="margin-left:5px">You</span><?php endif; ?>
              </div>
            </td>
            <td class="tm"><?= htmlspecialchars($m['email']) ?></td>
            <td>
              <?php if ($m['id'] != $me['id']): ?>
              <form method="POST" class="flex ic g8">
                <input type="hidden" name="uid" value="<?= $m['id'] ?>">
                <select name="role" class="fc" style="width:auto;padding:4px 8px;font-size:12px">
                  <?php foreach(['viewer'=>'Viewer','editor'=>'Editor','admin'=>'Admin'] as $r=>$rl): ?>
                  <option value="<?=$r?>" <?= $m['role']===$r?'selected':'' ?>><?=$rl?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-p btn-xs" name="update_role" type="submit">Save</button>
              </form>
              <?php else: ?>
              <span class="badge <?= ['viewer'=>'b-gr','editor'=>'b-b','admin'=>'b-g'][$m['role']] ?? 'b-gr' ?>"><?= ucfirst($m['role']) ?></span>
              <?php endif; ?>
            </td>
            <td class="tm fs12"><?= $m['last_login'] ? date('M j, Y', strtotime($m['last_login'])) : 'Never' ?></td>
            <td>
              <?php if ($m['id'] != $me['id']): ?>
              <form method="POST" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($m['name'])) ?> from the team?')">
                <input type="hidden" name="uid" value="<?= $m['id'] ?>">
                <button class="btn btn-d btn-xs" name="remove" type="submit">Remove</button>
              </form>
              <?php else: ?><span class="tm fs12">—</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Role key -->
    <div class="card">
      <div class="ch"><span class="ct">🔑 Access Levels</span></div>
      <div class="cb" style="display:flex;flex-direction:column;gap:10px">
        <div><span class="badge b-gr" style="margin-right:8px">Viewer</span>Can view submissions and analytics. Cannot edit.</div>
        <div><span class="badge b-b" style="margin-right:8px">Editor</span>Can view, edit, delete submissions and manage content.</div>
        <div><span class="badge b-g" style="margin-right:8px">Admin</span>Full access including team management.</div>
      </div>
    </div>
  </div>

  <!-- Right column: Add + change password -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct">➕ Add Team Member</span></div>
      <div class="cb">
        <form method="POST">
          <div class="fg"><label class="fl">Full Name</label><input class="fc" name="name" placeholder="e.g. Yuki Tanaka" required></div>
          <div class="fg"><label class="fl">Email Address</label><input class="fc" name="email" type="email" placeholder="team@roboco-op.org" required></div>
          <div class="fg"><label class="fl">Role</label>
            <select class="fc" name="role">
              <option value="viewer">Viewer — read only</option>
              <option value="editor">Editor — can edit</option>
              <option value="admin">Admin — full access</option>
            </select>
          </div>
          <div class="fg"><label class="fl">Temporary Password</label><input class="fc" name="password" type="password" placeholder="Min 8 characters" minlength="8" required></div>
          <button class="btn btn-p" name="add" type="submit" style="width:100%">➕ Add Team Member</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="ch"><span class="ct">🔐 Change Your Password</span></div>
      <div class="cb">
        <form method="POST">
          <div class="fg"><label class="fl">Current Password</label><input class="fc" name="current_pass" type="password" required></div>
          <div class="fg"><label class="fl">New Password</label><input class="fc" name="new_pass" type="password" minlength="8" required placeholder="Min 8 characters"></div>
          <button class="btn btn-g" name="change_pass" type="submit" style="width:100%">🔐 Change Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php admin_end(); ?>
