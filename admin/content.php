<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_auth('editor');
$db  = get_db();
$msg = '';
$tab = $_GET['tab'] ?? 'en';

// Editable content blocks
$content_blocks = [
    'en' => [
        'hero.badge'     => ['label'=>'Hero Badge Text',      'type'=>'text',     'default'=>'🏔️ Shimane Prefecture × Robo Co-op | FY2026 Digital Talent Development Program'],
        'hero.title'     => ['label'=>'Hero Main Title',      'type'=>'textarea', 'default'=>"Build your career\nwith AI skills."],
        'hero.lead'      => ['label'=>'Hero Lead Text',       'type'=>'textarea', 'default'=>"Not great with computers? Raising kids? Busy schedule? No problem.\nLearn AI and IT at your own pace, side by side with a supportive community."],
        'cta.main'       => ['label'=>'Main CTA Button',      'type'=>'text',     'default'=>'📝 Apply Now'],
        'apply.card.h3'  => ['label'=>'Apply Card Heading',   'type'=>'text',     'default'=>'Application Form'],
        'apply.card.p'   => ['label'=>'Apply Card Sub-text',  'type'=>'textarea', 'default'=>"Complete the form to apply.\nOur team will contact you shortly."],
    ],
    'ja' => [
        'hero.badge'     => ['label'=>'ヒーローバッジ',         'type'=>'text',     'default'=>'🏔️ 島根県 × Robo Co-op　令和8年度 デジタル人材育成研修'],
        'hero.title'     => ['label'=>'ヒーロータイトル',        'type'=>'textarea', 'default'=>"AIのスキルで、\nあなたらしいキャリアを\nはじめよう。"],
        'hero.lead'      => ['label'=>'ヒーローリード文',        'type'=>'textarea', 'default'=>"パソコンが苦手でも、育児中でも、忙しくても大丈夫。\n仲間と一緒に、自分のペースで\nAIやITの力を身につけられる研修です。"],
        'cta.main'       => ['label'=>'CTAボタン',              'type'=>'text',     'default'=>'📝 応募フォーム'],
    ],
];

$form_questions = [
    ['key'=>'q1_label','lang'=>'en','label'=>'Q1 Name Label','default'=>'1. Name'],
    ['key'=>'q2_label','lang'=>'en','label'=>'Q2 Email Label','default'=>'2. Email address'],
    ['key'=>'q9_label','lang'=>'en','label'=>'Q9 Reason Label','default'=>'9. Reason for applying'],
    ['key'=>'q9_hint','lang'=>'en','label'=>'Q9 Hint Text','default'=>'Please describe your motivation for applying (around 500 characters).'],
    ['key'=>'support_desc','lang'=>'en','label'=>'Support Program Description','default'=>'We offer an "Intensive Learning Support Program" for individuals who may have difficulty securing sufficient study time due to financial circumstances or other challenges.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    foreach ($_POST['content'] ?? [] as $key => $values) {
        foreach ($values as $lang => $value) {
            $db->prepare("INSERT INTO site_content (content_key,lang,value,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP)
                          ON CONFLICT(content_key,lang) DO UPDATE SET value=excluded.value,updated_at=CURRENT_TIMESTAMP")
               ->execute([$key, $lang, $value]);
        }
    }
    $msg = 'Content saved successfully.';
}

// Load current values
function get_saved(PDO $db, string $key, string $lang): ?string {
    $st = $db->prepare("SELECT value FROM site_content WHERE content_key=? AND lang=?");
    $st->execute([$key,$lang]);
    $row = $st->fetch();
    return $row ? $row['value'] : null;
}

admin_start('Content Editor', 'content',
    '<a href="/" target="_blank" class="btn btn-g btn-sm">🗾 View JA</a>
     <a href="/en" target="_blank" class="btn btn-g btn-sm">🌐 View EN</a>'
);
?>

<?php if ($msg): ?><div class="alert al-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="alert al-info mb12">
  To edit form questions, labels, and options go to <a href="/admin/forms" style="font-weight:700;color:var(--mint-d)">Forms →</a>
</div>

<div class="tabs">
  <a href="?tab=en" class="tab <?= $tab==='en'?'active':'' ?>">🌐 English Page</a>
  <a href="?tab=ja" class="tab <?= $tab==='ja'?'active':'' ?>">🗾 Japanese Page</a>
</div>

<form method="POST">

<?php if (isset($content_blocks[$tab])): ?>
<div class="card">
  <div class="ch">
    <span class="ct"><?= $tab==='en' ? '🌐 English Landing Page Content' : '🗾 Japanese Landing Page Content' ?></span>
    <span class="tm fs12">Note: After saving, clear your browser cache to see changes on the live site.</span>
  </div>
  <div class="cb">
    <?php foreach ($content_blocks[$tab] as $key => $cfg):
      $saved = get_saved($db, $key, $tab);
      $val   = $saved ?? $cfg['default'];
    ?>
    <div class="fg">
      <label class="fl"><?= htmlspecialchars($cfg['label']) ?></label>
      <?php if ($cfg['type'] === 'textarea'): ?>
      <textarea class="fc" name="content[<?= $key ?>][<?= $tab ?>]" rows="3"><?= htmlspecialchars($val) ?></textarea>
      <?php else: ?>
      <input class="fc" name="content[<?= $key ?>][<?= $tab ?>]" value="<?= htmlspecialchars($val) ?>">
      <?php endif; ?>
      <?php if ($saved === null): ?><div class="fs12 tm" style="margin-top:3px">Using default value. Edit to override.</div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div style="margin-top:16px">
  <button class="btn btn-p" name="save_content" type="submit">💾 Save Content</button>
  <span class="tm fs12" style="margin-left:12px">Changes are saved to the database and applied on the next page load.</span>
</div>
</form>

<?php admin_end(); ?>
