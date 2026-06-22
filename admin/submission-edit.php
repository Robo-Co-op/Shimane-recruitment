<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('viewer');
$db  = get_db();
$id  = (int)($_GET['id'] ?? 0);
$msg = '';

$sub = $db->prepare("SELECT * FROM form_submissions WHERE id=?")->execute([$id])
    ? null : null;
$st = $db->prepare("SELECT * FROM form_submissions WHERE id=?");
$st->execute([$id]);
$sub = $st->fetch();
if (!$sub) { header('Location: /admin/submissions'); exit; }

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && can('editor')) {
    if (isset($_POST['update'])) {
        $fields = ['name','email','phone','how_heard','how_heard_other','resume_url',
                   'pc_skill','ai_experience','reason','interview_day','interview_day_other',
                   'interview_time','interview_time_other','support_program','status','notes'];
        $sets = implode(',', array_map(fn($f) => "$f=?", $fields));
        $vals = array_map(fn($f) => trim($_POST[$f] ?? ''), $fields);
        $vals[] = $id;
        $db->prepare("UPDATE form_submissions SET $sets WHERE id=?")->execute($vals);
        $msg = 'Saved successfully.';
        // Reload
        $st->execute([$id]);
        $sub = $st->fetch();
    }
}

$pc_labels = [
    'pc_1'=>'Little to no experience','pc_2'=>'Basic tasks (typing, browsing, email)',
    'pc_3'=>'Word & Excel (basic)','pc_4'=>'Regular work use + Excel functions',
    'pc_5'=>'Specialised (programming, dev, data analysis)',
];
$ai_labels = [
    'ai_1'=>'Never used AI tools','ai_2'=>'Tried but not familiar',
    'ai_3'=>'Simple tasks (writing, research, summarization)',
    'ai_4'=>'Work/learning with custom instructions',
    'ai_5'=>'Effective use: documents, workflows, reviewing outputs',
];

admin_start('View & Edit Submission #' . $id, 'submissions',
    '<a href="/admin/submissions" class="btn btn-g btn-sm">← Back to list</a>'
);
?>

<?php if ($msg): ?><div class="alert al-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="POST">
<div class="g2">
  <!-- Left column: form data -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct">👤 Basic Information</span></div>
      <div class="cb">
        <div class="g2">
          <div class="fg"><label class="fl">Name</label><input class="fc" name="name" value="<?= htmlspecialchars($sub['name']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
          <div class="fg"><label class="fl">Email</label><input class="fc" name="email" type="email" value="<?= htmlspecialchars($sub['email']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
          <div class="fg"><label class="fl">Phone</label><input class="fc" name="phone" value="<?= htmlspecialchars($sub['phone']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
          <div class="fg"><label class="fl">How Heard</label>
            <select class="fc" name="how_heard" <?= can('editor') ? '' : 'disabled' ?>>
              <?php foreach(['municipality'=>'Municipality/org','social_media'=>'Social media','recommendation'=>'Recommendation','robocoop_web'=>'Robo Co-op website','other'=>'Other'] as $v=>$l): ?>
              <option value="<?=$v?>" <?= $sub['how_heard']===$v?'selected':'' ?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php if ($sub['how_heard']==='other'): ?>
        <div class="fg"><label class="fl">How Heard (Other)</label><input class="fc" name="how_heard_other" value="<?= htmlspecialchars($sub['how_heard_other']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
        <?php else: ?><input type="hidden" name="how_heard_other" value="<?= htmlspecialchars($sub['how_heard_other']) ?>">
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb16">
      <div class="ch"><span class="ct">💻 Skills & Motivation</span></div>
      <div class="cb">
        <div class="fg"><label class="fl">Resume URL</label><input class="fc" name="resume_url" value="<?= htmlspecialchars($sub['resume_url']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
        <div class="fg"><label class="fl">PC Skill</label>
          <select class="fc" name="pc_skill" <?= can('editor') ? '' : 'disabled' ?>>
            <option value="">— not specified —</option>
            <?php foreach($pc_labels as $v=>$l): ?>
            <option value="<?=$v?>" <?= $sub['pc_skill']===$v?'selected':'' ?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label class="fl">AI Experience</label>
          <select class="fc" name="ai_experience" <?= can('editor') ? '' : 'disabled' ?>>
            <option value="">— not specified —</option>
            <?php foreach($ai_labels as $v=>$l): ?>
            <option value="<?=$v?>" <?= $sub['ai_experience']===$v?'selected':'' ?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label class="fl">Reason for Applying</label>
          <textarea class="fc" name="reason" rows="5" <?= can('editor') ? '' : 'readonly' ?>><?= htmlspecialchars($sub['reason']) ?></textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="ch"><span class="ct">📅 Interview Preference</span></div>
      <div class="cb">
        <div class="g2">
          <div class="fg"><label class="fl">Preferred Day</label>
            <select class="fc" name="interview_day" <?= can('editor') ? '' : 'disabled' ?>>
              <?php foreach(['weekdays'=>'Weekdays','weekends'=>'Weekends/Holidays','day_other'=>'Other'] as $v=>$l): ?>
              <option value="<?=$v?>" <?= $sub['interview_day']===$v?'selected':'' ?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg"><label class="fl">Day (Other)</label><input class="fc" name="interview_day_other" value="<?= htmlspecialchars($sub['interview_day_other']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
          <div class="fg"><label class="fl">Preferred Time</label>
            <select class="fc" name="interview_time" <?= can('editor') ? '' : 'disabled' ?>>
              <?php foreach(['9_12'=>'9:00–12:00','12_15'=>'12:00–15:00','15_18'=>'15:00–18:00','time_other'=>'Other'] as $v=>$l): ?>
              <option value="<?=$v?>" <?= $sub['interview_time']===$v?'selected':'' ?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg"><label class="fl">Time (Other)</label><input class="fc" name="interview_time_other" value="<?= htmlspecialchars($sub['interview_time_other']) ?>" <?= can('editor') ? '' : 'readonly' ?>></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right column: status, support, notes -->
  <div>
    <div class="card mb16">
      <div class="ch"><span class="ct">📊 Application Status</span></div>
      <div class="cb">
        <div class="fg"><label class="fl">Status</label>
          <select class="fc" name="status" <?= can('editor') ? '' : 'disabled' ?>>
            <?php foreach(['new'=>'New','reviewed'=>'Reviewed','interview'=>'Interview Scheduled','accepted'=>'Accepted','rejected'=>'Rejected','waitlist'=>'Waitlist'] as $v=>$l): ?>
            <option value="<?=$v?>" <?= $sub['status']===$v?'selected':'' ?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label class="fl">Support Program</label>
          <select class="fc" name="support_program" <?= can('editor') ? '' : 'disabled' ?>>
            <?php foreach(['yes'=>'Yes — wants support','undecided'=>'Undecided','no'=>'No'] as $v=>$l): ?>
            <option value="<?=$v?>" <?= $sub['support_program']===$v?'selected':'' ?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label class="fl">Language</label>
          <input class="fc" value="<?= strtoupper($sub['lang']) ?>" readonly style="background:#F7FAF9">
        </div>
        <div class="fg"><label class="fl">Submitted</label>
          <input class="fc" value="<?= date('F j, Y — g:i a', strtotime($sub['submitted_at'])) ?>" readonly style="background:#F7FAF9">
        </div>
        <div class="fg"><label class="fl">IP Address</label>
          <input class="fc" value="<?= htmlspecialchars($sub['ip_address'] ?: '—') ?>" readonly style="background:#F7FAF9">
        </div>
      </div>
    </div>

    <div class="card mb16">
      <div class="ch"><span class="ct">📝 Admin Notes</span></div>
      <div class="cb">
        <textarea class="fc" name="notes" rows="6" placeholder="Internal notes (not visible to applicant)…" <?= can('editor') ? '' : 'readonly' ?>><?= htmlspecialchars($sub['notes']) ?></textarea>
      </div>
    </div>

    <?php if (can('editor')): ?>
    <div style="display:flex;gap:10px">
      <button class="btn btn-p" name="update" type="submit" style="flex:1">💾 Save Changes</button>
    </div>
    <?php endif; ?>

    <?php if (can('editor')): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--bdr)">
      <a href="mailto:<?= htmlspecialchars($sub['email']) ?>?subject=Shimane IB Application Update" class="btn btn-g btn-sm" style="width:100%;justify-content:center">✉️ Email Applicant</a>
    </div>
    <?php endif; ?>
  </div>
</div>
</form>

<?php admin_end(); ?>
