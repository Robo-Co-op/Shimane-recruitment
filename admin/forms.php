<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('editor');
$db = get_db();

// Handle new form creation
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_form'])) {
    $title = trim($_POST['title'] ?? '');
    $lang  = in_array($_POST['lang'] ?? '', ['en','ja','both']) ? $_POST['lang'] : 'en';
    $slug  = trim($_POST['slug'] ?? '');
    $slug  = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug)) ?: 'form-' . time();
    if ($title) {
        try {
            $db->prepare("INSERT INTO forms (slug,lang,title,description) VALUES (?,?,?,?)")
               ->execute([$slug, $lang, $title, trim($_POST['description'] ?? '')]);
            $new_id = $db->lastInsertId();
            header("Location: /admin/form-editor?id={$new_id}");
            exit;
        } catch (\Throwable $e) {
            $msg = 'Slug already exists — choose a different one.';
        }
    } else {
        $msg = 'Form title is required.';
    }
}

// Load all forms with submission count
$forms = $db->query("
    SELECT f.*,
           (SELECT COUNT(*) FROM form_submissions WHERE lang=f.lang) AS sub_count,
           (SELECT COUNT(*) FROM form_questions WHERE form_id=f.id AND active=1) AS q_count
    FROM forms f ORDER BY f.id ASC
")->fetchAll();

admin_start('Forms', 'forms',
    '<button class="btn btn-p btn-sm" onclick="document.getElementById(\'new-form-mo\').classList.add(\'open\')">+ New Form</button>'
);
?>

<?php if ($msg): ?><div class="alert al-err"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="sg" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));margin-bottom:24px">
<?php foreach ($forms as $f): ?>
<div class="card" style="margin:0">
  <div class="cb" style="padding:20px">
    <div class="flex ic jb mb8">
      <span class="badge <?= $f['status']==='active' ? 'b-g' : 'b-gr' ?>"><?= htmlspecialchars($f['status']) ?></span>
      <span class="badge b-b"><?= strtoupper(htmlspecialchars($f['lang'])) ?></span>
    </div>
    <div class="fw7" style="font-size:15px;margin-bottom:4px"><?= htmlspecialchars($f['title']) ?></div>
    <div class="tm fs12 mb12"><?= htmlspecialchars($f['slug']) ?></div>
    <?php if ($f['description']): ?>
    <div class="tm fs12 mb12" style="line-height:1.5"><?= htmlspecialchars($f['description']) ?></div>
    <?php endif; ?>
    <div class="flex g8 mb12" style="flex-wrap:wrap">
      <span class="badge b-b">📋 <?= $f['q_count'] ?> questions</span>
      <span class="badge b-a">📨 <?= $f['sub_count'] ?> submissions</span>
    </div>
    <a href="/admin/form-editor?id=<?= $f['id'] ?>" class="btn btn-p btn-sm" style="width:100%;justify-content:center">
      ✏️ Edit Form Questions
    </a>
    <?php if ($f['lang'] === 'en'): ?>
    <a href="/apply" target="_blank" class="btn btn-g btn-sm" style="width:100%;justify-content:center;margin-top:8px">🔗 View Live</a>
    <?php elseif ($f['lang'] === 'ja'): ?>
    <a href="/apply/ja" target="_blank" class="btn btn-g btn-sm" style="width:100%;justify-content:center;margin-top:8px">🔗 View Live</a>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<div class="alert al-info" style="margin-top:0">
  <strong>How this works:</strong>
  Edit a form's questions, labels, hints, and options here.
  Changes are reflected live on the application form — no code changes needed.
</div>

<!-- New Form Modal -->
<div class="mo" id="new-form-mo" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="md">
    <div class="md-t">+ Create New Form</div>
    <form method="POST">
      <div class="fg">
        <label class="fl">Form Title <span style="color:var(--red)">*</span></label>
        <input class="fc" name="title" placeholder="e.g. Volunteer Application Form" required>
      </div>
      <div class="fg">
        <label class="fl">URL Slug</label>
        <input class="fc" name="slug" placeholder="e.g. volunteer-application">
        <div class="fs12 tm" style="margin-top:3px">Unique identifier. Lowercase letters, numbers, hyphens only.</div>
      </div>
      <div class="fg">
        <label class="fl">Language</label>
        <select class="fc" name="lang">
          <option value="en">English (EN)</option>
          <option value="ja">Japanese (JA)</option>
        </select>
      </div>
      <div class="fg">
        <label class="fl">Description</label>
        <textarea class="fc" name="description" rows="2" placeholder="Short description (optional)"></textarea>
      </div>
      <div class="md-f">
        <button type="button" class="btn btn-g" onclick="document.getElementById('new-form-mo').classList.remove('open')">Cancel</button>
        <button type="submit" name="create_form" class="btn btn-p">Create Form</button>
      </div>
    </form>
  </div>
</div>

<?php admin_end(); ?>
