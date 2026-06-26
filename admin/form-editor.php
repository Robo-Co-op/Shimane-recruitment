<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('editor');
$db = get_db();

$form_id = (int)($_GET['id'] ?? 0);
$form = null;
if ($form_id) {
    $form = $db->prepare("SELECT * FROM forms WHERE id=?")->execute([$form_id]) ?
            $db->prepare("SELECT * FROM forms WHERE id=?")->execute([$form_id]) ?: null : null;
    $st = $db->prepare("SELECT * FROM forms WHERE id=?");
    $st->execute([$form_id]);
    $form = $st->fetch();
}
if (!$form) {
    header("Location: " . base_url('/admin/forms'));
    exit;
}

$msg = '';

// ── Handle form save ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save_meta') {
        $db->prepare("UPDATE forms SET title=?,description=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([trim($_POST['title']),trim($_POST['description']),$_POST['status'],$form_id]);
        $msg = 'Form details saved.';
        $st = $db->prepare("SELECT * FROM forms WHERE id=?");
        $st->execute([$form_id]);
        $form = $st->fetch();
    }

    if ($action === 'save_questions') {
        foreach ($_POST['q'] ?? [] as $qid => $data) {
            $qid = (int)$qid;
            $opts_raw = $data['options'] ?? [];
            $opts = [];
            foreach ($opts_raw as $o) {
                $v = trim($o['value'] ?? '');
                $l = trim($o['label'] ?? '');
                if ($v !== '' || $l !== '') {
                    $opts[] = ['value'=>$v, 'label'=>$l, 'sub'=>trim($o['sub'] ?? '')];
                }
            }
            $db->prepare("UPDATE form_questions SET
                label=?,hint=?,placeholder=?,required=?,step=?,sort_order=?,options_json=?,max_length=?,active=?,
                updated_at=CURRENT_TIMESTAMP
                WHERE id=? AND form_id=?")
               ->execute([
                   trim($data['label'] ?? ''),
                   trim($data['hint'] ?? ''),
                   trim($data['placeholder'] ?? ''),
                   isset($data['required']) ? 1 : 0,
                   (int)($data['step'] ?? 1),
                   (int)($data['sort_order'] ?? 0),
                   json_encode($opts, JSON_UNESCAPED_UNICODE),
                   ($data['max_length'] !== '' ? (int)$data['max_length'] : null),
                   isset($data['active']) ? 1 : 0,
                   $qid, $form_id
               ]);
        }
        bust_form_questions_cache($form['slug']);
        $msg = 'Questions saved successfully.';
    }

    if ($action === 'add_question') {
        $max_sort = $db->prepare("SELECT MAX(sort_order) FROM form_questions WHERE form_id=?");
        $max_sort->execute([$form_id]);
        $next_sort = ((int)$max_sort->fetchColumn()) + 10;
        $db->prepare("INSERT INTO form_questions (form_id,step,sort_order,field_name,field_type,label,hint,placeholder,required,options_json) VALUES (?,?,?,?,?,?,?,?,?,?)")
           ->execute([$form_id, (int)($_POST['step']??1), $next_sort,
                      preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($_POST['field_name']??'new_field_'.$next_sort))),
                      $_POST['field_type']??'text',
                      trim($_POST['label']??'New Question'),
                      '', '', 0, '[]']);
        bust_form_questions_cache($form['slug']);
        $msg = 'Question added.';
    }

    if ($action === 'delete_question') {
        $qid = (int)($_POST['qid'] ?? 0);
        $db->prepare("DELETE FROM form_questions WHERE id=? AND form_id=?")->execute([$qid,$form_id]);
        bust_form_questions_cache($form['slug']);
        $msg = 'Question deleted.';
    }
}

// Load all questions
$questions = $db->prepare("SELECT * FROM form_questions WHERE form_id=? ORDER BY sort_order ASC");
$questions->execute([$form_id]);
$questions = $questions->fetchAll();
foreach ($questions as &$q) {
    $q['options'] = json_decode($q['options_json'] ?? '[]', true) ?: [];
}
unset($q);

$by_step = [1=>[], 2=>[], 3=>[]];
foreach ($questions as $q) {
    $s = max(1, min(3, (int)$q['step']));
    $by_step[$s][] = $q;
}

$active_step = (int)($_GET['step'] ?? 1);
if ($active_step < 1 || $active_step > 3) $active_step = 1;

$field_types = ['text','email','tel','url','textarea','radio','select','number'];
$live_url = $form['lang'] === 'ja' ? '/apply/ja' : '/apply';

admin_start(
    'Form Editor — ' . htmlspecialchars($form['title']),
    'forms',
    '<a href="/admin/forms" class="btn btn-g btn-sm">← All Forms</a>
     <a href="' . $live_url . '" target="_blank" class="btn btn-g btn-sm">🔗 Live Form</a>'
);
?>

<?php if ($msg): ?><div class="alert al-ok mb12"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Form Meta -->
<div class="card mb16">
  <div class="ch"><span class="ct">Form Details</span></div>
  <div class="cb">
    <form method="POST">
      <input type="hidden" name="action" value="save_meta">
      <div class="g2">
        <div class="fg">
          <label class="fl">Title</label>
          <input class="fc" name="title" value="<?= htmlspecialchars($form['title']) ?>" required>
        </div>
        <div class="fg">
          <label class="fl">Status</label>
          <select class="fc" name="status">
            <option value="active" <?= $form['status']==='active'?'selected':'' ?>>Active</option>
            <option value="draft" <?= $form['status']==='draft'?'selected':'' ?>>Draft</option>
            <option value="archived" <?= $form['status']==='archived'?'selected':'' ?>>Archived</option>
          </select>
        </div>
      </div>
      <div class="fg">
        <label class="fl">Description</label>
        <input class="fc" name="description" value="<?= htmlspecialchars($form['description']) ?>" placeholder="Short description (optional)">
      </div>
      <button class="btn btn-p btn-sm" type="submit">💾 Save Details</button>
    </form>
  </div>
</div>

<!-- Step Tabs -->
<div class="tabs">
  <a href="?id=<?= $form_id ?>&step=1" class="tab <?= $active_step===1?'active':'' ?>">
    Step 1 — <?= $form['lang']==='ja'?'基本情報':'Basic Info' ?>
    <span class="badge b-b" style="margin-left:6px"><?= count($by_step[1]) ?></span>
  </a>
  <a href="?id=<?= $form_id ?>&step=2" class="tab <?= $active_step===2?'active':'' ?>">
    Step 2 — <?= $form['lang']==='ja'?'経歴・スキル':'Background' ?>
    <span class="badge b-b" style="margin-left:6px"><?= count($by_step[2]) ?></span>
  </a>
  <a href="?id=<?= $form_id ?>&step=3" class="tab <?= $active_step===3?'active':'' ?>">
    Step 3 — <?= $form['lang']==='ja'?'サポート':'Support' ?>
    <span class="badge b-b" style="margin-left:6px"><?= count($by_step[3]) ?></span>
  </a>
</div>

<form method="POST" id="qform">
  <input type="hidden" name="action" value="save_questions">

  <?php $step_qs = $by_step[$active_step]; ?>
  <?php if (empty($step_qs)): ?>
  <div class="empty"><div class="empty-ic">📝</div><div class="empty-t">No questions in this step</div></div>
  <?php else: ?>

  <div id="questions-list">
  <?php foreach ($step_qs as $qi => $q):
    $qid = $q['id'];
  ?>
  <div class="card mb12 qcard" id="qcard-<?= $qid ?>">
    <div class="ch" style="cursor:pointer" onclick="toggleQ(<?= $qid ?>)">
      <div class="flex ic g8">
        <span class="badge b-gr" style="font-family:monospace"><?= htmlspecialchars($q['field_name']) ?></span>
        <span class="badge b-b"><?= htmlspecialchars($q['field_type']) ?></span>
        <?php if ($q['required']): ?><span class="badge b-r">required</span><?php endif; ?>
        <?php if (!$q['active']): ?><span class="badge" style="background:#eee;color:#999">inactive</span><?php endif; ?>
        <span class="fw7" style="font-size:13px"><?= htmlspecialchars($q['label']) ?></span>
      </div>
      <div class="flex ic g8">
        <span class="tm fs12">Sort: <?= $q['sort_order'] ?></span>
        <span class="tm fs12">▼</span>
      </div>
    </div>
    <div class="cb q-edit" id="qedit-<?= $qid ?>" style="display:none">
      <div class="g2">
        <div class="fg">
          <label class="fl">Label <span style="color:var(--red)">*</span></label>
          <input class="fc" name="q[<?= $qid ?>][label]" value="<?= htmlspecialchars($q['label']) ?>" required>
        </div>
        <div class="fg">
          <label class="fl">Field Name <span class="tm fs12">(read-only)</span></label>
          <input class="fc" value="<?= htmlspecialchars($q['field_name']) ?>" readonly style="background:#f5f5f5">
        </div>
      </div>
      <div class="fg">
        <label class="fl">Hint / Sub-text</label>
        <textarea class="fc" name="q[<?= $qid ?>][hint]" rows="2"><?= htmlspecialchars($q['hint']) ?></textarea>
      </div>
      <div class="g3">
        <div class="fg">
          <label class="fl">Placeholder</label>
          <input class="fc" name="q[<?= $qid ?>][placeholder]" value="<?= htmlspecialchars($q['placeholder']) ?>">
        </div>
        <div class="fg">
          <label class="fl">Step</label>
          <select class="fc" name="q[<?= $qid ?>][step]">
            <option value="1" <?= $q['step']==1?'selected':'' ?>>Step 1</option>
            <option value="2" <?= $q['step']==2?'selected':'' ?>>Step 2</option>
            <option value="3" <?= $q['step']==3?'selected':'' ?>>Step 3</option>
          </select>
        </div>
        <div class="fg">
          <label class="fl">Sort Order</label>
          <input class="fc" type="number" name="q[<?= $qid ?>][sort_order]" value="<?= $q['sort_order'] ?>" min="0" step="10">
        </div>
      </div>
      <div class="g2" style="margin-bottom:14px">
        <?php if ($q['field_type']==='textarea'): ?>
        <div class="fg">
          <label class="fl">Max Length</label>
          <input class="fc" type="number" name="q[<?= $qid ?>][max_length]" value="<?= $q['max_length'] ?? '' ?>" placeholder="e.g. 600">
        </div>
        <?php else: ?><div></div><?php endif; ?>
        <div class="fg" style="display:flex;gap:20px;align-items:flex-end;padding-bottom:4px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;font-weight:600">
            <input type="checkbox" name="q[<?= $qid ?>][required]" value="1" <?= $q['required']?'checked':'' ?>> Required
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;font-weight:600">
            <input type="checkbox" name="q[<?= $qid ?>][active]" value="1" <?= $q['active']?'checked':'' ?>> Active
          </label>
        </div>
      </div>

      <?php if (in_array($q['field_type'], ['radio','select'])): ?>
      <div class="fg" style="border-top:1px solid var(--bdr);padding-top:14px">
        <div class="flex ic jb mb8">
          <label class="fl" style="margin:0">Answer Options</label>
          <button type="button" class="btn btn-g btn-xs" onclick="addOption(<?= $qid ?>)">+ Add Option</button>
        </div>
        <div id="opts-<?= $qid ?>">
          <?php foreach ($q['options'] as $oi => $opt): ?>
          <div class="opt-row" style="display:grid;grid-template-columns:140px 1fr 1fr auto;gap:6px;margin-bottom:6px;align-items:center">
            <input class="fc" name="q[<?= $qid ?>][options][<?= $oi ?>][value]"
                   value="<?= htmlspecialchars($opt['value']) ?>" placeholder="value">
            <input class="fc" name="q[<?= $qid ?>][options][<?= $oi ?>][label]"
                   value="<?= htmlspecialchars($opt['label']) ?>" placeholder="label (shown to user)">
            <input class="fc" name="q[<?= $qid ?>][options][<?= $oi ?>][sub]"
                   value="<?= htmlspecialchars($opt['sub'] ?? '') ?>" placeholder="sub-text (optional)">
            <button type="button" class="btn btn-d btn-xs" onclick="this.closest('.opt-row').remove()">✕</button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="flex ic jb" style="margin-top:8px;border-top:1px solid var(--bdr);padding-top:12px">
        <form method="POST" onsubmit="return confirm('Delete this question?')">
          <input type="hidden" name="action" value="delete_question">
          <input type="hidden" name="qid" value="<?= $qid ?>">
          <button type="submit" class="btn btn-d btn-xs">🗑 Delete</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

  <div style="margin:16px 0">
    <button type="submit" class="btn btn-p">💾 Save All Changes</button>
    <span class="tm fs12" style="margin-left:12px">All open questions on this step are saved at once.</span>
  </div>

  <?php endif; ?>
</form>

<!-- Add Question -->
<div class="card">
  <div class="ch"><span class="ct">+ Add Question to Step <?= $active_step ?></span></div>
  <div class="cb">
    <form method="POST">
      <input type="hidden" name="action" value="add_question">
      <input type="hidden" name="step" value="<?= $active_step ?>">
      <div class="g3">
        <div class="fg">
          <label class="fl">Field Name</label>
          <input class="fc" name="field_name" placeholder="e.g. custom_question" pattern="[a-z0-9_]+">
          <div class="fs12 tm" style="margin-top:3px">Lowercase letters, numbers, underscores.</div>
        </div>
        <div class="fg">
          <label class="fl">Label</label>
          <input class="fc" name="label" placeholder="Question text shown to user">
        </div>
        <div class="fg">
          <label class="fl">Field Type</label>
          <select class="fc" name="field_type">
            <?php foreach ($field_types as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-p btn-sm">+ Add Question</button>
    </form>
  </div>
</div>

<script>
function toggleQ(id) {
  const el = document.getElementById('qedit-'+id);
  el.style.display = el.style.display === 'none' ? '' : 'none';
}

let optCounters = {};
function addOption(qid) {
  if (!optCounters[qid]) optCounters[qid] = 100;
  const idx = optCounters[qid]++;
  const row = document.createElement('div');
  row.className = 'opt-row';
  row.style.cssText = 'display:grid;grid-template-columns:140px 1fr 1fr auto;gap:6px;margin-bottom:6px;align-items:center';
  row.innerHTML = `
    <input class="fc" name="q[${qid}][options][${idx}][value]" placeholder="value">
    <input class="fc" name="q[${qid}][options][${idx}][label]" placeholder="label (shown to user)">
    <input class="fc" name="q[${qid}][options][${idx}][sub]" placeholder="sub-text (optional)">
    <button type="button" class="btn btn-d btn-xs" onclick="this.closest('.opt-row').remove()">✕</button>
  `;
  document.getElementById('opts-'+qid).appendChild(row);
}

// Auto-open question if there's only one
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('[id^="qedit-"]');
  if (cards.length === 1) cards[0].style.display = '';
});
</script>

<?php admin_end(); ?>
