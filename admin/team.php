<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/mail.php';
require_auth('admin');
$db  = get_db();
$me  = current_user();
$msg = '';
$err = '';
$invite_link = ''; // shown on screen if mail() fails

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Invite new member ───────────────────────────────────────────────────
    if (isset($_POST['add'])) {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role  = $_POST['role'] ?? 'viewer';
        if (!in_array($role, ['viewer','editor','admin'])) $role = 'viewer';
        if (!$name || !$email) {
            $err = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Invalid email address.';
        } else {
            try {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
                $db->prepare("INSERT INTO admin_users
                    (name,email,password_hash,role,status,invite_token,invite_expires_at)
                    VALUES (?,?,?,?,?,?,?)")
                   ->execute([$name, $email, '', $role, 'pending', $token, $expires]);
                $sent = send_admin_invite($email, $name, $token);
                if ($sent) {
                    $msg = "Invitation sent to {$email}. They will receive an email to set their password.";
                } else {
                    $base = (isset($_SERVER['HTTP_HOST'])
                        ? ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST']
                        : 'https://shimane-ib.roboco-op.org');
                    $invite_link = $base . '/admin/accept-invite?token=' . $token;
                    $msg = "Account created for {$name}. Email could not be sent — share the invite link below manually.";
                }
            } catch (\PDOException $e) {
                $err = 'That email address is already in use.';
            }
        }
    }

    // ── Resend invite ───────────────────────────────────────────────────────
    if (isset($_POST['resend_invite'])) {
        $uid = (int)$_POST['uid'];
        $st  = $db->prepare("SELECT * FROM admin_users WHERE id=? AND status='pending'");
        $st->execute([$uid]);
        $member = $st->fetch();
        if ($member) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
            $db->prepare("UPDATE admin_users SET invite_token=?,invite_expires_at=? WHERE id=?")
               ->execute([$token, $expires, $uid]);
            $sent = send_admin_invite($member['email'], $member['name'], $token);
            if ($sent) {
                $msg = "Invite resent to {$member['email']}.";
            } else {
                $base = (isset($_SERVER['HTTP_HOST'])
                    ? ((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http').'://'.$_SERVER['HTTP_HOST']
                    : 'https://shimane-ib.roboco-op.org');
                $invite_link = $base . '/admin/accept-invite?token=' . $token;
                $msg = "Email could not be sent — share the invite link below manually.";
            }
        }
    }

    // ── Update role ─────────────────────────────────────────────────────────
    if (isset($_POST['update_role'])) {
        $uid  = (int)$_POST['uid'];
        $role = $_POST['role'] ?? 'viewer';
        if ($uid === $me['id']) {
            $err = 'You cannot change your own role.';
        } elseif (in_array($role, ['viewer','editor','admin'])) {
            $db->prepare("UPDATE admin_users SET role=? WHERE id=?")->execute([$role, $uid]);
            $msg = 'Role updated.';
        }
    }

    // ── Remove member ───────────────────────────────────────────────────────
    if (isset($_POST['remove'])) {
        $uid = (int)$_POST['uid'];
        if ($uid === $me['id']) {
            $err = 'You cannot remove your own account.';
        } else {
            $db->prepare("DELETE FROM admin_users WHERE id=?")->execute([$uid]);
            $msg = 'Team member removed.';
        }
    }
}

$members = $db->query("SELECT * FROM admin_users ORDER BY created_at")->fetchAll();

admin_start('Team Management', 'team');
?>

<?php if ($msg): ?><div class="alert al-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert al-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($invite_link): ?>
<div class="alert" style="background:#FEF4E5;border:1px solid #F5A87A;color:#7A4A00;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">
  <strong>Share this invite link manually:</strong><br>
  <a href="<?= htmlspecialchars($invite_link) ?>" style="word-break:break-all"><?= htmlspecialchars($invite_link) ?></a>
</div>
<?php endif; ?>

<div class="g2" style="align-items:start">

  <!-- Members table -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct">👥 Team Members (<?= count($members) ?>)</span></div>
      <div class="tw">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
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
              <?php $status = $m['status'] ?? 'active'; ?>
              <?php if ($status === 'pending'): ?>
                <span class="badge b-a">⏳ Pending</span>
              <?php else: ?>
                <span class="badge b-g">✓ Active</span>
              <?php endif; ?>
            </td>
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
            <td class="tm fs12">
              <?php if (($m['status'] ?? 'active') === 'pending'): ?>
                <span class="tm">Not yet</span>
              <?php else: ?>
                <?= $m['last_login'] ? date('M j, Y', strtotime($m['last_login'])) : 'Never' ?>
              <?php endif; ?>
            </td>
            <td>
              <div class="flex g8">
                <?php if (($m['status'] ?? 'active') === 'pending'): ?>
                <form method="POST">
                  <input type="hidden" name="uid" value="<?= $m['id'] ?>">
                  <button class="btn btn-g btn-xs" name="resend_invite" type="submit">Resend</button>
                </form>
                <?php endif; ?>
                <?php if ($m['id'] != $me['id']): ?>
                <form method="POST" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($m['name'])) ?> from the team?')">
                  <input type="hidden" name="uid" value="<?= $m['id'] ?>">
                  <button class="btn btn-d btn-xs" name="remove" type="submit">Remove</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="ch"><span class="ct">🔑 Access Levels</span></div>
      <div class="cb" style="display:flex;flex-direction:column;gap:10px">
        <div><span class="badge b-gr" style="margin-right:8px">Viewer</span>Can view submissions and analytics. Cannot edit.</div>
        <div><span class="badge b-b" style="margin-right:8px">Editor</span>Can view, edit, delete submissions and manage content.</div>
        <div><span class="badge b-g" style="margin-right:8px">Admin</span>Full access including team management.</div>
      </div>
    </div>
  </div>

  <!-- Right column: Invite -->
  <div class="card">
    <div class="ch"><span class="ct">✉️ Invite Team Member</span></div>
    <div class="cb">
      <p class="tm fs12" style="margin-bottom:14px">An invitation email will be sent to the new member with a link to set their own password and activate their account.</p>
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
        <button class="btn btn-p" name="add" type="submit" style="width:100%">✉️ Send Invitation</button>
      </form>
    </div>
  </div>

</div>

<?php admin_end(); ?>
