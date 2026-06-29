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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && can('editor')) {

    // ── Bulk export CSV (before any HTML output) ─────────────────────────────
    if (isset($_POST['bulk_export'])) {
        $ids = array_values(array_filter(array_map('intval', $_POST['sel_sub'] ?? [])));
        if ($ids) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $st = $db->prepare("SELECT * FROM form_submissions WHERE id IN ($ph) ORDER BY submitted_at DESC");
            $st->execute($ids);
            $rows = $st->fetchAll();
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="submissions_' . date('Ymd_His') . '.csv"');
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['ID','Name','Email','Phone','Language','PC Skill','AI Experience','Reason','Interview Day','Interview Time','Support Program','Status','Submitted']);
            foreach ($rows as $r) {
                fputcsv($fp, [$r['id'],$r['name'],$r['email'],$r['phone'],strtoupper($r['lang']),$r['pc_skill'],$r['ai_experience'],$r['reason'],$r['interview_day'],$r['interview_time'],$r['support_program'],$r['status'],$r['submitted_at']]);
            }
            fclose($fp);
            exit;
        }
    }

    if (isset($_POST['bulk_export_draft'])) {
        $ids = array_values(array_filter(array_map('intval', $_POST['sel_draft'] ?? [])));
        if ($ids) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $st = $db->prepare("SELECT * FROM form_drafts WHERE id IN ($ph) ORDER BY updated_at DESC");
            $st->execute($ids);
            $rows = $st->fetchAll();
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="drafts_' . date('Ymd_His') . '.csv"');
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['ID','Name','Email','Language','Step','Started','Last Active','Reminders Sent']);
            foreach ($rows as $r) {
                fputcsv($fp, [$r['id'],$r['name'],$r['email'],strtoupper($r['lang']),'Step '.$r['step_reached'].'/3',$r['created_at'],$r['updated_at'],$r['reminder_sent_at'] ? $r['reminder_count'].'x sent' : 'Not sent']);
            }
            fclose($fp);
            exit;
        }
    }

    // ── Single deletes ────────────────────────────────────────────────────────
    if (isset($_POST['delete_sub'])) {
        $db->prepare("DELETE FROM form_submissions WHERE id=?")->execute([(int)$_POST['delete_sub']]);
        $msg = 'Submission deleted.';
    }
    if (isset($_POST['delete_draft'])) {
        $db->prepare("DELETE FROM form_drafts WHERE id=?")->execute([(int)$_POST['delete_draft']]);
        $msg = 'Draft deleted.';
    }

    // ── Bulk deletes ──────────────────────────────────────────────────────────
    if (isset($_POST['bulk_delete'])) {
        $ids = array_values(array_filter(array_map('intval', $_POST['sel_sub'] ?? [])));
        if ($ids) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM form_submissions WHERE id IN ($ph)")->execute($ids);
            $msg = count($ids) . ' submission(s) deleted.';
        }
    }
    if (isset($_POST['bulk_delete_draft'])) {
        $ids = array_values(array_filter(array_map('intval', $_POST['sel_draft'] ?? [])));
        if ($ids) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM form_drafts WHERE id IN ($ph)")->execute($ids);
            $msg = count($ids) . ' draft(s) deleted.';
        }
    }
}

// Load submissions
$where  = $search ? "WHERE (name LIKE ? OR email LIKE ?)" : "";
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
<!-- ── Complete submissions ────────────────────────────────────────────────── -->
<form method="POST" id="bulk-form-subs">

  <?php if (can('editor') && $subs): ?>
  <div id="bulk-bar-subs" style="display:none;align-items:center;gap:10px;background:#fff;border:1.5px solid var(--mint);border-radius:10px;padding:10px 16px;margin-bottom:12px;flex-wrap:wrap">
    <span id="bulk-count-subs" style="font-weight:700;font-size:13px;color:var(--warm-dark)">0 selected</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button type="submit" name="bulk_delete" class="btn btn-d btn-sm" onclick="return confirm('Permanently delete the selected submissions?')">🗑 Delete Selected</button>
      <button type="submit" name="bulk_export" class="btn btn-g btn-sm">⬇ Export CSV</button>
      <button type="button" class="btn btn-g btn-sm" onclick="toggleAll('sel_sub[]',false);updateBulk('subs')">✕ Clear</button>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="tw">
      <?php if ($subs): ?>
      <table>
        <thead><tr>
          <?php if (can('editor')): ?>
          <th style="width:36px;text-align:center;padding:10px 8px">
            <input type="checkbox" id="sa-subs" title="Select all"
                   onchange="toggleAll('sel_sub[]',this.checked);updateBulk('subs')"
                   style="width:16px;height:16px;cursor:pointer">
          </th>
          <?php endif; ?>
          <th>Name</th><th>Email</th><th>Phone</th><th>Lang</th><th>Support</th><th>Status</th><th>Date</th><th style="width:80px"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($subs as $s): ?>
        <tr <?= (isset($_GET['highlight']) && $_GET['highlight']==$s['id']) ? 'style="background:#FEF4E5"' : '' ?>>
          <?php if (can('editor')): ?>
          <td style="text-align:center;padding:10px 8px">
            <input type="checkbox" name="sel_sub[]" value="<?= $s['id'] ?>"
                   onchange="updateBulk('subs')" style="width:16px;height:16px;cursor:pointer">
          </td>
          <?php endif; ?>
          <td class="fw7">
            <?= htmlspecialchars($s['name'] ?: '—') ?>
            <?php if (!empty($s['is_duplicate'])): ?>
              <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:10px">⚠ Dup</span>
            <?php endif; ?>
          </td>
          <td class="tm"><?= htmlspecialchars($s['email'] ?: '—') ?></td>
          <td class="tm"><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
          <td><span class="badge b-b"><?= strtoupper($s['lang']) ?></span></td>
          <td><?php
            $sup = $s['support_program'];
            if ($sup==='yes')       echo '<span class="badge b-g">Yes</span>';
            elseif ($sup==='no')    echo '<span class="badge b-gr">No</span>';
            else                    echo '<span class="badge b-a">?</span>';
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
              <button type="submit" name="delete_sub" value="<?= $s['id'] ?>"
                      class="btn btn-d btn-xs"
                      onclick="return confirm('Delete this submission?')">✕</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty">
        <div class="empty-ic">📭</div>
        <div class="empty-t">No submissions found</div>
        <?php if ($search): ?>
          <p>No results for "<?= htmlspecialchars($search) ?>"</p>
        <?php else: ?>
          <p>Completed applications will appear here.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php else: ?>
<!-- ── In-progress drafts ──────────────────────────────────────────────────── -->
<form method="POST" id="bulk-form-drafts">

  <?php if (can('editor') && $drafts): ?>
  <div id="bulk-bar-drafts" style="display:none;align-items:center;gap:10px;background:#fff;border:1.5px solid var(--mint);border-radius:10px;padding:10px 16px;margin-bottom:12px;flex-wrap:wrap">
    <span id="bulk-count-drafts" style="font-weight:700;font-size:13px;color:var(--warm-dark)">0 selected</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button type="submit" name="bulk_delete_draft" class="btn btn-d btn-sm" onclick="return confirm('Permanently delete the selected drafts?')">🗑 Delete Selected</button>
      <button type="submit" name="bulk_export_draft" class="btn btn-g btn-sm">⬇ Export CSV</button>
      <button type="button" class="btn btn-g btn-sm" onclick="toggleAll('sel_draft[]',false);updateBulk('drafts')">✕ Clear</button>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="tw">
      <?php if ($drafts): ?>
      <table>
        <thead><tr>
          <?php if (can('editor')): ?>
          <th style="width:36px;text-align:center;padding:10px 8px">
            <input type="checkbox" id="sa-drafts" title="Select all"
                   onchange="toggleAll('sel_draft[]',this.checked);updateBulk('drafts')"
                   style="width:16px;height:16px;cursor:pointer">
          </th>
          <?php endif; ?>
          <th>Name / Email</th><th>Lang</th><th>Step</th><th>Started</th><th>Last Active</th><th>Reminded</th><th style="width:60px"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($drafts as $d): ?>
        <tr <?= (isset($_GET['highlight']) && $_GET['highlight']==$d['id']) ? 'style="background:#FEF4E5"' : '' ?>>
          <?php if (can('editor')): ?>
          <td style="text-align:center;padding:10px 8px">
            <input type="checkbox" name="sel_draft[]" value="<?= $d['id'] ?>"
                   onchange="updateBulk('drafts')" style="width:16px;height:16px;cursor:pointer">
          </td>
          <?php endif; ?>
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
            <?php if (can('editor')): ?>
            <button type="submit" name="delete_draft" value="<?= $d['id'] ?>"
                    class="btn btn-d btn-xs"
                    onclick="return confirm('Delete this draft?')">✕</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty">
        <div class="empty-ic">✅</div>
        <div class="empty-t">No incomplete drafts</div>
        <p>All started forms have been completed.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</form>
<?php endif; ?>

<script>
function toggleAll(name, checked) {
  document.querySelectorAll('input[name="' + name + '"]').forEach(cb => cb.checked = checked);
}
function updateBulk(type) {
  var name    = type === 'subs' ? 'sel_sub[]' : 'sel_draft[]';
  var checked = document.querySelectorAll('input[name="' + name + '"]:checked').length;
  var total   = document.querySelectorAll('input[name="' + name + '"]').length;
  var bar     = document.getElementById('bulk-bar-' + type);
  var countEl = document.getElementById('bulk-count-' + type);
  var saEl    = document.getElementById('sa-' + type);
  if (bar)     bar.style.display = checked > 0 ? 'flex' : 'none';
  if (countEl) countEl.textContent = checked + ' selected';
  if (saEl) {
    saEl.indeterminate = checked > 0 && checked < total;
    saEl.checked = total > 0 && checked === total;
  }
}
</script>

<?php admin_end(); ?>
