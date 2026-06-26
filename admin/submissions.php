<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('viewer');
$db = get_db();

$tab    = $_GET['tab'] ?? 'complete';
$search = trim($_GET['q'] ?? '');
$msg    = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && can('editor')) {
    if (isset($_POST['delete_sub'])) {
        $db->prepare("DELETE FROM form_submissions WHERE id=?")->execute([(int)$_POST['delete_sub']]);
        $msg = 'Submission deleted.';
    }
    if (isset($_POST['delete_draft'])) {
        $db->prepare("DELETE FROM form_drafts WHERE id=?")->execute([(int)$_POST['delete_draft']]);
        $msg = 'Draft deleted.';
    }
}

// Load submissions
$where = $search ? "WHERE (name LIKE ? OR email LIKE ?)" : "";
$params = $search ? ["%$search%", "%$search%"] : [];
$subs_st = $db->prepare("SELECT * FROM form_submissions $where ORDER BY submitted_at DESC");
$subs_st->execute($params);
$subs = $subs_st->fetchAll();

// Load drafts
$dw = $search ? "WHERE completed=0 AND (name LIKE ? OR email LIKE ?)" : "WHERE completed=0";
$dp = $search ? ["%$search%", "%$search%"] : [];
$drafts_st = $db->prepare("SELECT * FROM form_drafts $dw ORDER BY updated_at DESC");
$drafts_st->execute($dp);
$drafts = $drafts_st->fetchAll();

$export_actions = '
<a href="/admin/export?format=csv" class="btn btn-g btn-sm">⬇ CSV</a>
<a href="/admin/export?format=excel" class="btn btn-g btn-sm">📊 Excel</a>
<a href="/admin/export?format=pdf" class="btn btn-g btn-sm" target="_blank">🖨 Print/PDF</a>
<a href="/admin/api/remind" class="btn btn-a btn-sm" onclick="return confirm(\'Send reminder emails to all incomplete applicants?\')">📧 Send Reminders</a>
';

admin_start('Submissions', 'submissions', $export_actions);
?>

<?php if ($msg): ?><div class="alert al-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Search -->
<form class="flex ic g8 mb16" method="GET">
  <div class="sr" style="flex:1;max-width:320px">
    <span class="sic">🔍</span>
    <input class="si" name="q" placeholder="Search by name or email…" value="<?= htmlspecialchars($search) ?>">
  </div>
  <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
  <button class="btn btn-g btn-sm" type="submit">Search</button>
  <?php if ($search): ?><a href="?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-g btn-sm">Clear</a><?php endif; ?>
</form>

<!-- Tabs -->
<div class="tabs">
  <a href="?tab=complete<?= $search ? '&q='.urlencode($search) : '' ?>" class="tab <?= $tab==='complete' ? 'active' : '' ?>">
    ✅ Complete (<?= count($subs) ?>)
  </a>
  <a href="?tab=drafts<?= $search ? '&q='.urlencode($search) : '' ?>" class="tab <?= $tab==='drafts' ? 'active' : '' ?>">
    ⏳ In Progress (<?= count($drafts) ?>)
  </a>
</div>

<?php if ($tab === 'complete'): ?>
<!-- Complete submissions -->
<div class="card">
  <div class="tw">
    <?php if ($subs): ?>
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Lang</th><th>Support</th><th>Status</th><th>Date</th><th style="width:100px"></th></tr></thead>
      <tbody>
      <?php foreach ($subs as $s): ?>
      <tr <?= (isset($_GET['highlight']) && $_GET['highlight']==$s['id']) ? 'style="background:#FEF4E5"' : '' ?>>
        <td class="fw7"><?= htmlspecialchars($s['name'] ?: '—') ?><?php if (!empty($s['is_duplicate'])): ?> <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:10px">⚠ Duplicate email</span><?php endif; ?></td>
        <td class="tm"><?= htmlspecialchars($s['email'] ?: '—') ?></td>
        <td class="tm"><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
        <td><span class="badge b-b"><?= strtoupper($s['lang']) ?></span></td>
        <td><?php
          $sup = $s['support_program'];
          if ($sup==='yes') echo '<span class="badge b-g">Yes</span>';
          elseif ($sup==='no') echo '<span class="badge b-gr">No</span>';
          else echo '<span class="badge b-a">Undecided</span>';
        ?></td>
        <td><?php
          $cls = ['new'=>'b-a','reviewed'=>'b-b','accepted'=>'b-g','rejected'=>'b-r'][$s['status']] ?? 'b-gr';
          echo '<span class="badge '.$cls.'">'.htmlspecialchars($s['status']).'</span>';
        ?></td>
        <td class="tm fs12"><?= date('M j, Y', strtotime($s['submitted_at'])) ?></td>
        <td>
          <div class="flex g8">
            <a href="/admin/submission-edit?id=<?= $s['id'] ?>" class="btn btn-g btn-xs">Edit</a>
            <?php if (can('editor')): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this submission?')">
              <input type="hidden" name="delete_sub" value="<?= $s['id'] ?>">
              <button class="btn btn-d btn-xs" type="submit">✕</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty"><div class="empty-ic">📭</div><div class="empty-t">No submissions found</div>
      <?php if ($search): ?><p>No results for "<?= htmlspecialchars($search) ?>"</p><?php else: ?><p>Completed applications will appear here.</p><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- Drafts / In-progress -->
<div class="card">
  <div class="tw">
    <?php if ($drafts): ?>
    <table>
      <thead><tr><th>Name / Email</th><th>Lang</th><th>Step</th><th>Started</th><th>Last Active</th><th>Reminded</th><th style="width:100px"></th></tr></thead>
      <tbody>
      <?php foreach ($drafts as $d): ?>
      <tr <?= (isset($_GET['highlight']) && $_GET['highlight']==$d['id']) ? 'style="background:#FEF4E5"' : '' ?>>
        <td>
          <div class="fw7"><?= htmlspecialchars($d['name'] ?: 'Unknown') ?></div>
          <div class="tm fs12"><?= htmlspecialchars($d['email'] ?: '—') ?></div>
        </td>
        <td><span class="badge b-b"><?= strtoupper($d['lang']) ?></span></td>
        <td><span class="badge b-a">Step <?= $d['step_reached'] ?>/3</span></td>
        <td class="tm fs12"><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
        <td class="tm fs12"><?= date('M j, g:i a', strtotime($d['updated_at'])) ?></td>
        <td class="fs12">
          <?php if ($d['reminder_sent_at']): ?>
            <span class="badge b-b"><?= $d['reminder_count'] ?>× sent</span>
          <?php else: ?>
            <span class="tm">Not sent</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="flex g8">
            <?php if (can('editor')): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this draft?')">
              <input type="hidden" name="delete_draft" value="<?= $d['id'] ?>">
              <button class="btn btn-d btn-xs" type="submit">✕</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty"><div class="empty-ic">✅</div><div class="empty-t">No incomplete drafts</div><p>All started forms have been completed.</p></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php admin_end(); ?>
