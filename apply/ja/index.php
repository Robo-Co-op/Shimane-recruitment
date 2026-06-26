<?php
$lang = 'ja';
$submitted = false;
$errors = [];
$resume_draft = null;
$done_email = '';
$done_name  = '';

require_once __DIR__ . '/../../includes/base.php';
require_once __DIR__ . '/../../admin/includes/db.php';

// Load questions from file cache — no DB connection needed for a plain GET visit
$_raw_qs_ja = get_form_questions('ja-application');
$_qmap_ja   = array_column($_raw_qs_ja, null, 'field_name');
// Override Q6 hint: remove the optional-field note regardless of what DB contains
if (isset($_qmap_ja['resume_url'])) {
    $_qmap_ja['resume_url']['hint'] = 'Google Drive、Dropbox などにアップロードしたファイルの URL をご記入ください。';
}

function qj_label(string $name, string $default): string {
    global $_qmap_ja;
    return $_qmap_ja[$name]['label'] ?? $default;
}
function qj_hint(string $name, string $default = ''): string {
    global $_qmap_ja;
    return $_qmap_ja[$name]['hint'] ?? $default;
}
function qj_placeholder(string $name, string $default = ''): string {
    global $_qmap_ja;
    return $_qmap_ja[$name]['placeholder'] ?? $default;
}
function qj_options(string $name, array $defaults): array {
    global $_qmap_ja;
    return !empty($_qmap_ja[$name]['options']) ? $_qmap_ja[$name]['options'] : $defaults;
}

// ── Resume from token (GET) ───────────────────────────────────────────────────
$resume_token = trim($_GET['token'] ?? '');
if ($resume_token) {
    $db = get_db();
    $st = $db->prepare("SELECT * FROM form_drafts WHERE token=? AND completed=0 LIMIT 1");
    $st->execute([$resume_token]);
    $resume_draft = $st->fetch();
    if ($resume_draft) {
        $_POST = array_merge($_POST, json_decode($resume_draft['form_data'] ?? '{}', true) ?: []);
        $_POST['_draft_token'] = $resume_token;
    }
}

// ── Handle final POST submission ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty(trim($_POST['_website'] ?? ''))) {
        $submitted  = true;
        $done_email = trim($_POST['email'] ?? '');
        $done_name  = trim($_POST['name']  ?? '');
    } else {
    try {
    $db = get_db();
    $name               = trim($_POST['name'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $email_confirm      = trim($_POST['email_confirm'] ?? '');
    $phone              = trim($_POST['phone'] ?? '');
    $how_heard          = trim($_POST['how_heard'] ?? '');
    $how_heard_other    = trim($_POST['how_heard_other'] ?? '');
    $resume_url         = trim($_POST['resume_url'] ?? '');
    $pc_skill           = trim($_POST['pc_skill'] ?? '');
    $ai_experience      = trim($_POST['ai_experience'] ?? '');
    $reason             = trim($_POST['reason'] ?? '');
    $interview_day      = trim($_POST['interview_day'] ?? '');
    $interview_day_other  = trim($_POST['interview_day_other'] ?? '');
    $interview_time     = trim($_POST['interview_time'] ?? '');
    $interview_time_other = trim($_POST['interview_time_other'] ?? '');
    $support_program    = trim($_POST['support_program'] ?? '');
    $support_situation  = trim($_POST['support_situation'] ?? '');
    $other_questions    = trim($_POST['other_questions'] ?? '');
    $confirm_submit     = trim($_POST['confirm_submit'] ?? '');
    $draft_token        = trim($_POST['_draft_token'] ?? '');

    if (empty($name))               $errors[] = '氏名を入力してください。';
    elseif (mb_strlen($name) < 2)   $errors[] = '氏名は2文字以上で入力してください。';
    if (empty($email))              $errors[] = 'メールアドレスを入力してください。';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = '有効なメールアドレスを入力してください。';
    if ($email !== $email_confirm)  $errors[] = 'メールアドレスが一致しません。';
    if (empty($phone))              $errors[] = '電話番号を入力してください。';
    else { $pd = preg_replace('/\D/', '', $phone); if (strlen($pd) < 10) $errors[] = '有効な電話番号を入力してください（10桁以上）。'; }
    if (empty($reason))             $errors[] = '応募動機を入力してください。';
    if (empty($support_program))    $errors[] = 'サポートプログラムへの意向を選択してください。';
    if (in_array($support_program, ['yes','undecided']) && empty($support_situation)) $errors[] = '現在のご状況とサポート枠を希望する理由をご記入ください。';
    if ($confirm_submit !== 'yes')  $errors[] = '送信前に「はい」を選択して確認してください。';

    $is_duplicate = false;
    if (empty($errors)) {
        $ip_addr = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ip_addr) {
            $rl = $db->prepare("SELECT COUNT(*) FROM form_submissions WHERE ip_address=? AND submitted_at > NOW() - INTERVAL '1 hour'");
            $rl->execute([$ip_addr]);
            if ((int)$rl->fetchColumn() >= 3) $errors[] = '送信回数が多すぎます。しばらくしてからもう一度お試しください。';
        }
        if (empty($errors)) {
            $dc = $db->prepare("SELECT id FROM form_submissions WHERE email=? LIMIT 1");
            $dc->execute([$email]);
            $is_duplicate = (bool)$dc->fetchColumn();
        }
    }
    if (empty($errors)) {
        try {
            $draft_id = null;
            if ($draft_token) {
                $dst = $db->prepare("SELECT id FROM form_drafts WHERE token=?");
                $dst->execute([$draft_token]);
                $draft_id = $dst->fetchColumn() ?: null;
            }

            $db->prepare("INSERT INTO form_submissions
                (draft_id,name,email,phone,how_heard,how_heard_other,resume_url,pc_skill,ai_experience,reason,
                 interview_day,interview_day_other,interview_time,interview_time_other,support_program,
                 support_situation,other_questions,confirm_submit,lang,ip_address,is_duplicate)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'ja',?,?)")
               ->execute([$draft_id,$name,$email,$phone,$how_heard,$how_heard_other,$resume_url,$pc_skill,
                          $ai_experience,$reason,$interview_day,$interview_day_other,$interview_time,
                          $interview_time_other,$support_program,$support_situation,$other_questions,
                          $confirm_submit,$_SERVER['REMOTE_ADDR']??'',(int)$is_duplicate]);

            if ($draft_id) {
                $db->prepare("UPDATE form_drafts SET completed=1, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                   ->execute([$draft_id]);
            }

            // CSV backup
            $dir = dirname(__DIR__, 2) . '/submissions';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $file  = $dir . '/applications.csv';
            $isNew = !file_exists($file) || filesize($file) === 0;
            $fp    = fopen($file, 'a');
            if ($isNew) {
                fputcsv($fp, ['timestamp','name','email','phone','how_heard','how_heard_other',
                              'resume_url','pc_skill','ai_experience','reason',
                              'interview_day','interview_day_other','interview_time','interview_time_other',
                              'support_program','support_situation','other_questions','confirm_submit','lang']);
            }
            fputcsv($fp, [date('Y-m-d H:i:s'), $name, $email, $phone, $how_heard, $how_heard_other,
                          $resume_url, $pc_skill, $ai_experience, $reason,
                          $interview_day, $interview_day_other, $interview_time, $interview_time_other,
                          $support_program, $support_situation, $other_questions, $confirm_submit, 'ja']);
            fclose($fp);

            $done_email = $email;
            $done_name  = $name;
            $submitted  = true;
        } catch (\Throwable $e) {
            $errors[] = 'システムエラーが発生しました。時間をおいて再度お試しください。（' . htmlspecialchars($e->getMessage()) . '）';
            error_log('apply/ja submit error: ' . $e->getMessage());
        }
    }
    } catch (\Throwable $e) {
        $errors[] = 'DB接続エラー：' . htmlspecialchars($e->getMessage());
        error_log('apply/ja db error: ' . $e->getMessage());
    }
    } // end !honeypot
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>応募フォーム — 島根IB | Robo Co-op</title>
  <?php include __DIR__ . '/../../includes/styles.php'; ?>
  <style>
    /* ── Page shell ── */
    .apply-page {
      min-height: calc(100vh - 68px);
      background: linear-gradient(160deg, #E5F6F4 0%, #F8F2EE 55%, #EBF5F0 100%);
      padding: 48px 20px 72px;
    }

    /* ── Card wrapper ── */
    .form-wrap {
      max-width: 680px;
      margin: 0 auto;
    }

    .form-header {
      text-align: center;
      margin-bottom: 32px;
    }
    .form-header .badge {
      display: inline-block;
      background: white;
      color: var(--mint-dark);
      border: 1.5px solid var(--mint);
      border-radius: 20px;
      padding: 5px 18px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 14px;
    }
    .form-header h1 {
      font-size: clamp(20px, 4vw, 26px);
      font-weight: 900;
      color: var(--warm-dark);
      line-height: 1.4;
      margin-bottom: 8px;
    }
    .form-header p {
      font-size: 14px;
      color: var(--warm-mid);
      line-height: 1.7;
    }

    /* ── Progress bar ── */
    .progress-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      margin-bottom: 28px;
    }
    .pb-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      position: relative;
      z-index: 1;
    }
    .pb-num {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: white;
      border: 2.5px solid var(--warm-light);
      color: var(--warm-light);
      font-size: 15px; font-weight: 900;
      display: flex; align-items: center; justify-content: center;
      transition: all .25s;
    }
    .pb-label {
      font-size: 11px; font-weight: 700;
      color: var(--warm-light);
      white-space: nowrap;
      transition: color .25s;
    }
    .pb-step.active .pb-num  { background: var(--mint); border-color: var(--mint); color: white; }
    .pb-step.active .pb-label { color: var(--mint-dark); }
    .pb-step.done .pb-num  { background: var(--mint-dark); border-color: var(--mint-dark); color: white; }
    .pb-step.done .pb-label { color: var(--mint-dark); }
    .pb-line {
      flex: 1;
      height: 2.5px;
      background: var(--warm-light);
      margin: 0 4px;
      margin-bottom: 22px;
      min-width: 40px;
      max-width: 100px;
      transition: background .25s;
    }
    .pb-line.done { background: var(--mint-dark); }

    /* ── Form card ── */
    .form-card {
      background: white;
      border-radius: 24px;
      box-shadow: 0 6px 32px rgba(61,191,175,.12);
      overflow: hidden;
    }

    .card-section-head {
      background: linear-gradient(135deg, var(--mint-pale), #F0FAF8);
      border-bottom: 1px solid #D0EDE9;
      padding: 22px 32px;
    }
    .card-section-head h2 {
      font-size: 17px;
      font-weight: 900;
      color: var(--warm-dark);
      margin-bottom: 2px;
    }
    .card-section-head p {
      font-size: 13px;
      color: var(--warm-mid);
      line-height: 1.6;
      margin: 0;
    }

    .card-body { padding: 28px 32px; }

    /* ── Privacy notice ── */
    .privacy-notice {
      background: var(--mint-pale);
      border-left: 3px solid var(--mint);
      border-radius: 0 10px 10px 0;
      padding: 14px 16px;
      font-size: 13px;
      color: var(--warm-mid);
      line-height: 1.65;
      margin-bottom: 28px;
    }

    /* ── Field groups ── */
    .field-group {
      margin-bottom: 24px;
    }
    .field-label {
      display: block;
      font-size: 14px;
      font-weight: 700;
      color: var(--warm-dark);
      margin-bottom: 8px;
    }
    .field-label .req {
      color: #E05555;
      margin-left: 4px;
      font-size: 12px;
    }
    .field-hint {
      font-size: 12px;
      color: var(--warm-mid);
      margin-top: -4px;
      margin-bottom: 8px;
      line-height: 1.5;
    }

    /* ── Text inputs ── */
    .text-input {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #D5E8E5;
      border-radius: 12px;
      font-size: 14px;
      color: var(--warm-dark);
      background: white;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
      font-family: inherit;
    }
    .text-input::placeholder { color: #B8CECC; }
    .text-input:focus {
      border-color: var(--mint);
      box-shadow: 0 0 0 3px rgba(61,191,175,.12);
    }
    .text-input.error { border-color: #E05555; }

    textarea.text-input {
      resize: vertical;
      min-height: 120px;
      line-height: 1.6;
    }

    .char-counter {
      text-align: right;
      font-size: 11px;
      color: var(--warm-light);
      margin-top: 4px;
    }
    .char-counter.warn { color: var(--peach); }

    /* ── Radio options ── */
    .radio-group { display: flex; flex-direction: column; gap: 10px; }
    .radio-option {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 13px 16px;
      border: 1.5px solid #D5E8E5;
      border-radius: 12px;
      cursor: pointer;
      transition: border-color .18s, background .18s;
      position: relative;
    }
    .radio-option:hover { border-color: var(--mint); background: var(--mint-pale); }
    .radio-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0; height: 0;
    }
    .radio-dot {
      width: 20px; height: 20px;
      border: 2px solid #B8CECC;
      border-radius: 50%;
      flex-shrink: 0;
      margin-top: 1px;
      display: flex; align-items: center; justify-content: center;
      transition: border-color .18s;
    }
    .radio-dot::after {
      content: '';
      width: 10px; height: 10px;
      border-radius: 50%;
      background: var(--mint);
      opacity: 0;
      transform: scale(0);
      transition: opacity .18s, transform .18s;
    }
    .radio-option:has(input:checked) {
      border-color: var(--mint);
      background: var(--mint-pale);
    }
    .radio-option:has(input:checked) .radio-dot {
      border-color: var(--mint);
    }
    .radio-option:has(input:checked) .radio-dot::after {
      opacity: 1;
      transform: scale(1);
    }
    .radio-text {
      font-size: 14px;
      color: var(--warm-dark);
      line-height: 1.5;
    }
    .radio-text .sub {
      display: block;
      font-size: 12px;
      color: var(--warm-mid);
      margin-top: 2px;
    }

    /* ── Other field (shown when Other is selected) ── */
    .other-input {
      margin-top: 8px;
      display: none;
    }
    .other-input.visible { display: block; }

    /* ── Field divider ── */
    .field-divider {
      height: 1px;
      background: #EEF5F4;
      margin: 28px 0;
    }

    /* ── Step sections ── */
    .form-step { display: none; }
    .form-step.active { display: block; }

    /* ── Navigation ── */
    .form-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 20px;
      gap: 12px;
    }
    .btn-back {
      background: white;
      color: var(--warm-mid);
      border: 1.5px solid #D5E8E5;
      padding: 13px 28px;
      border-radius: 40px;
      font-size: 14px; font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      transition: border-color .2s, color .2s;
    }
    .btn-back:hover { border-color: var(--mint); color: var(--mint-dark); }
    .btn-next, .btn-submit {
      background: linear-gradient(135deg, var(--mint), var(--mint-dark));
      color: white;
      border: none;
      padding: 14px 40px;
      border-radius: 40px;
      font-size: 15px; font-weight: 900;
      cursor: pointer;
      font-family: inherit;
      transition: opacity .2s, transform .2s;
      box-shadow: 0 4px 18px rgba(61,191,175,.30);
      margin-left: auto;
    }
    .btn-submit {
      background: linear-gradient(135deg, var(--peach), #E07840);
      box-shadow: 0 4px 18px rgba(245,168,122,.35);
    }
    .btn-next:hover, .btn-submit:hover {
      opacity: .9;
      transform: translateY(-2px);
    }

    /* ── Server-side error banner ── */
    .error-banner {
      background: #FFF0F0;
      border: 1.5px solid #E05555;
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 24px;
      font-size: 13px;
      color: #B52B2B;
    }
    .error-banner ul { padding-left: 18px; margin: 4px 0 0; }
    .error-banner li { margin-top: 4px; }

    /* ── Field error text ── */
    .field-error {
      font-size: 12px;
      color: #E05555;
      margin-top: 5px;
      display: none;
    }
    .field-error.visible { display: block; }

    /* ── Support program highlight box ── */
    .support-info {
      background: linear-gradient(135deg, #F0FAF8, #FFF5F0);
      border: 1.5px solid #D0EDE9;
      border-radius: 16px;
      padding: 20px 22px;
      margin-bottom: 24px;
    }
    .support-info h3 {
      font-size: 15px; font-weight: 900;
      color: var(--warm-dark); margin-bottom: 8px;
    }
    .support-info p { font-size: 13px; color: var(--warm-mid); line-height: 1.7; margin-bottom: 12px; }
    .support-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .sup-pill {
      background: white;
      border: 1px solid #D0EDE9;
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 12px;
      color: var(--warm-mid);
      display: flex; align-items: center; gap: 6px;
    }

    /* ── Success state ── */
    .success-card {
      background: white;
      border-radius: 24px;
      box-shadow: 0 6px 32px rgba(61,191,175,.12);
      overflow: hidden;
      text-align: center;
    }
    .success-header {
      background: linear-gradient(135deg, #3DBFAF, #2A9485);
      padding: 28px 36px 72px;
      position: relative;
    }
    .success-brand {
      display: flex; align-items: center;
      gap: 10px; justify-content: center;
    }
    .s-mark {
      width: 36px; height: 36px;
      background: rgba(255,255,255,.22);
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; color: #fff; font-size: 13px;
    }
    .s-brand-name { color: rgba(255,255,255,.9); font-weight: 700; font-size: 15px; }
    .s-logo { height: 48px; width: auto; filter: brightness(0) invert(1); }
    .check-wrap {
      position: absolute; bottom: -40px;
      left: 50%; transform: translateX(-50%);
    }
    .check-circle-bg {
      width: 80px; height: 80px; border-radius: 50%;
      background: #fff;
      box-shadow: 0 6px 28px rgba(0,0,0,.13);
      display: flex; align-items: center; justify-content: center;
    }
    .checkmark { width: 44px; height: 44px; }
    .checkmark-circle {
      stroke: #3DBFAF; stroke-width: 2.5; fill: none;
      stroke-dasharray: 145; stroke-dashoffset: 145;
      animation: draw-circle .55s ease-out .1s forwards;
    }
    .checkmark-check {
      stroke: #3DBFAF; stroke-width: 3.2; fill: none;
      stroke-linecap: round; stroke-linejoin: round;
      stroke-dasharray: 48; stroke-dashoffset: 48;
      animation: draw-check .35s ease-out .6s forwards;
    }
    @keyframes draw-circle { to { stroke-dashoffset: 0; } }
    @keyframes draw-check  { to { stroke-dashoffset: 0; } }
    .success-body { padding: 58px 36px 44px; }
    .success-body h2 {
      font-size: 22px; font-weight: 900;
      color: var(--warm-dark); margin-bottom: 10px;
    }
    .s-intro {
      font-size: 14px; color: var(--warm-mid);
      line-height: 1.85; margin-bottom: 24px;
    }
    .email-confirm {
      display: inline-block;
      background: var(--mint-pale);
      border-radius: 12px;
      padding: 12px 24px;
      margin-bottom: 28px;
    }
    .ec-label {
      font-size: 11px; font-weight: 700; color: var(--mint-dark);
      text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px;
    }
    .ec-val { font-size: 14px; font-weight: 700; color: var(--warm-dark); }
    .next-steps {
      background: #F8FBFA; border-radius: 14px;
      padding: 20px 24px; margin-bottom: 22px; text-align: left;
    }
    .next-steps h3 {
      font-size: 11px; font-weight: 700; color: var(--mint-dark);
      text-transform: uppercase; letter-spacing: .07em; margin-bottom: 16px;
    }
    .ns-step { display: flex; gap: 13px; margin-bottom: 14px; }
    .ns-step:last-child { margin-bottom: 0; }
    .ns-num {
      width: 24px; height: 24px; border-radius: 50%;
      background: var(--mint); color: #fff;
      font-size: 11px; font-weight: 900; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      margin-top: 1px;
    }
    .ns-text { font-size: 13px; color: var(--warm-mid); line-height: 1.65; }
    .ns-text strong { color: var(--warm-dark); display: block; margin-bottom: 1px; }
    .response-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: #FEF4E5; border-radius: 8px;
      padding: 8px 18px; font-size: 13px;
      color: #7A4A00; font-weight: 600; margin-bottom: 28px;
    }
    .success-back-btn {
      display: inline-block;
      background: linear-gradient(135deg, #3DBFAF, #2A9485);
      color: #fff; font-weight: 700; font-size: 14px;
      padding: 13px 36px; border-radius: 40px;
      text-decoration: none;
      box-shadow: 0 4px 18px rgba(61,191,175,.3);
      transition: opacity .2s, transform .2s;
    }
    .success-back-btn:hover { opacity: .9; transform: translateY(-1px); }

    /* ── Responsive ── */
    @media (max-width: 560px) {
      .card-section-head, .card-body { padding: 18px 20px; }
      .form-nav { flex-wrap: wrap; }
      .btn-back { order: 2; width: 100%; text-align: center; }
      .btn-next, .btn-submit { order: 1; width: 100%; margin-left: 0; }
    }
  </style>
</head>
<body>

<!-- ========== HEADER ========== -->
<header>
  <a class="logo" href="/">
    <img src="/logo.png" alt="Robo Co-op" class="logo-img">
    <span class="logo-text">Robo Co-op</span>
  </a>
  <div class="header-right">
    <a href="/" class="lang-switch">← サイトに戻る</a>
  </div>
</header>

<!-- ========== FORM PAGE ========== -->
<div class="apply-page">
  <div class="form-wrap">

    <?php if ($submitted): ?>
    <!-- ── SUCCESS ── -->
    <div class="success-card">

      <!-- Gradient brand header -->
      <div class="success-header">
        <div class="success-brand">
          <img src="/logo.png" alt="Robo Co-op" class="s-logo">
        </div>
        <div class="check-wrap">
          <div class="check-circle-bg">
            <svg class="checkmark" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
              <circle class="checkmark-circle" cx="26" cy="26" r="23" fill="none"/>
              <path class="checkmark-check" fill="none" d="M14 27l8 8 16-17"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="success-body">
        <h2>応募が完了しました</h2>
        <p class="s-intro">
          島根県 × Robo Co-op<br>
          令和8年度 デジタル人材育成研修へのご応募ありがとうございます。<br>
          担当者が内容を確認のうえ、ご連絡いたします。
        </p>

        <div class="email-confirm">
          <div class="ec-label">確認メール送信先</div>
          <div class="ec-val"><?= htmlspecialchars($done_email) ?></div>
        </div>

        <div class="next-steps">
          <h3>次のステップ</h3>
          <div class="ns-step">
            <div class="ns-num">1</div>
            <div class="ns-text"><strong>応募内容の確認</strong>担当者が応募内容を丁寧に確認します。</div>
          </div>
          <div class="ns-step">
            <div class="ns-num">2</div>
            <div class="ns-text"><strong>面談日程のご連絡</strong>3営業日以内にメールにてご連絡いたします。</div>
          </div>
          <div class="ns-step">
            <div class="ns-num">3</div>
            <div class="ns-text"><strong>オンライン面談</strong>詳細はご連絡メールにてお知らせします。</div>
          </div>
        </div>

        <div class="response-badge">
          ⏱&nbsp; 返信の目安：3営業日以内
        </div>

        <br>
        <a href="/" class="success-back-btn">← 研修情報に戻る</a>
      </div>

    </div>

    <?php else: ?>
    <!-- ── FORM ── -->
    <div class="form-header">
      <div class="badge">📝 応募フォーム</div>
      <h1>島根IB — 令和8年度<br>デジタル人材育成研修</h1>
      <p>島根県 × Robo Co-op &nbsp;·&nbsp; 現在募集中</p>
    </div>

    <!-- Progress bar -->
    <div class="progress-bar" id="progress-bar">
      <div class="pb-step active" data-step="1">
        <div class="pb-num">1</div>
        <div class="pb-label">基本情報</div>
      </div>
      <div class="pb-line" id="line-1"></div>
      <div class="pb-step" data-step="2">
        <div class="pb-num">2</div>
        <div class="pb-label">経歴・スキル</div>
      </div>
      <div class="pb-line" id="line-2"></div>
      <div class="pb-step" data-step="3">
        <div class="pb-num">3</div>
        <div class="pb-label">サポート</div>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="error-banner">
      <strong>以下の項目を修正してから送信してください：</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="form-card">
      <form method="POST" action="/apply/ja/" id="app-form" novalidate>
        <input type="hidden" name="_draft_token" id="draft_token" value="<?= htmlspecialchars($_POST['_draft_token'] ?? '') ?>">
        <div style="display:none" aria-hidden="true"><input type="text" name="_website" value="" tabindex="-1" autocomplete="off"></div>

        <!-- ══════════════════════════════════════
             STEP 1 — 基本情報
        ══════════════════════════════════════ -->
        <div class="form-step active" id="step-1">
          <div class="card-section-head">
            <h2>基本情報</h2>
            <p><span style="color:#E05555">*</span> の付いた項目は必須です。</p>
          </div>
          <div class="card-body">

            <div class="privacy-notice">
              <strong>個人情報の取り扱いについて</strong><br>
              このフォームで収集した個人情報は、研修プログラムの運営のみに使用し、
              ご本人の同意なく第三者に提供することはありません。
            </div>

            <!-- 氏名 -->
            <div class="field-group">
              <label class="field-label" for="name"><?= htmlspecialchars(qj_label('name','1. 氏名')) ?> <span class="req">*</span></label>
              <input class="text-input" type="text" id="name" name="name"
                     placeholder="<?= htmlspecialchars(qj_placeholder('name','例：山田 太郎')) ?>"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="name">
              <div class="field-error" id="err-name">氏名を入力してください。</div>
            </div>

            <!-- メールアドレス -->
            <div class="field-group">
              <label class="field-label" for="email"><?= htmlspecialchars(qj_label('email','2. メールアドレス')) ?> <span class="req">*</span></label>
              <input class="text-input" type="email" id="email" name="email"
                     placeholder="<?= htmlspecialchars(qj_placeholder('email','your@email.com')) ?>"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
              <div class="field-error" id="err-email">有効なメールアドレスを入力してください。</div>
            </div>

            <!-- メールアドレス確認 -->
            <div class="field-group">
              <label class="field-label" for="email_confirm"><?= htmlspecialchars(qj_label('email_confirm','3. メールアドレス（確認）')) ?> <span class="req">*</span></label>
              <?php $ec_h = qj_hint('email_confirm','確認のため、もう一度メールアドレスを入力してください。'); if($ec_h):?><p class="field-hint"><?= htmlspecialchars($ec_h) ?></p><?php endif;?>
              <input class="text-input" type="email" id="email_confirm" name="email_confirm"
                     placeholder="<?= htmlspecialchars(qj_placeholder('email_confirm','your@email.com')) ?>"
                     value="<?= htmlspecialchars($_POST['email_confirm'] ?? '') ?>">
              <div class="field-error" id="err-email-confirm">メールアドレスが一致しません。</div>
            </div>

            <!-- 電話番号 -->
            <div class="field-group">
              <label class="field-label" for="phone"><?= htmlspecialchars(qj_label('phone','4. 電話番号')) ?> <span class="req">*</span></label>
              <?php $ph_h = qj_hint('phone','メールでご連絡できない場合に、電話でご連絡することがあります。'); if($ph_h):?><p class="field-hint"><?= htmlspecialchars($ph_h) ?></p><?php endif;?>
              <input class="text-input" type="tel" id="phone" name="phone"
                     placeholder="<?= htmlspecialchars(qj_placeholder('phone','例：080-1234-5678')) ?>"
                     value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel">
              <div class="field-error" id="err-phone">電話番号を入力してください。</div>
            </div>

            <!-- 研修を知ったきっかけ -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('how_heard','5. この研修をどこで知りましたか？')) ?></label>
              <div class="radio-group">
                <?php
                $howHeardOpts = qj_options('how_heard', [
                  ['value'=>'municipality',  'label'=>'市区町村や支援機関からの情報','sub'=>''],
                  ['value'=>'social_media',  'label'=>'SNS（Facebook、X/Twitter など）','sub'=>''],
                  ['value'=>'recommendation','label'=>'家族・知人からの紹介','sub'=>''],
                  ['value'=>'robocoop_web',  'label'=>'Robo Co-op のウェブサイト','sub'=>''],
                  ['value'=>'other',         'label'=>'その他','sub'=>''],
                ]);
                $selectedHow = $_POST['how_heard'] ?? '';
                foreach ($howHeardOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="how_heard" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedHow === $opt['value'] ? 'checked' : '' ?>
                         onchange="toggleOther(this,'how-other')">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($opt['label']) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="other-input <?= $selectedHow === 'other' ? 'visible' : '' ?>" id="how-other">
                <input class="text-input" type="text" name="how_heard_other"
                       placeholder="具体的にご記入ください..."
                       value="<?= htmlspecialchars($_POST['how_heard_other'] ?? '') ?>">
              </div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /step-1 -->

        <!-- ══════════════════════════════════════
             STEP 2 — 経歴・スキル
        ══════════════════════════════════════ -->
        <div class="form-step" id="step-2">
          <div class="card-section-head">
            <h2>経歴・スキル</h2>
            <p>あなたの経験や志望動機をお聞かせください。</p>
          </div>
          <div class="card-body">

            <!-- 履歴書URL -->
            <div class="field-group">
              <label class="field-label" for="resume_url"><?= htmlspecialchars(qj_label('resume_url','6. 履歴書・職務経歴書 URL')) ?> <span class="req">*</span></label>
              <?php $ru_h = qj_hint('resume_url','Google Drive、Dropbox などにアップロードしたファイルの URL をご記入ください。'); if($ru_h):?><p class="field-hint"><?= htmlspecialchars($ru_h) ?></p><?php endif;?>
              <input class="text-input" type="url" id="resume_url" name="resume_url"
                     placeholder="<?= htmlspecialchars(qj_placeholder('resume_url','https://drive.google.com/...')) ?>"
                     value="<?= htmlspecialchars($_POST['resume_url'] ?? '') ?>">
              <div class="field-error" id="err-resume">履歴書・職務経歴書の URL を入力してください。</div>
            </div>

            <div class="field-divider"></div>

            <!-- PCスキル -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('pc_skill','7. PC スキル')) ?> <span class="req">*</span></label>
              <?php $pc_h = qj_hint('pc_skill','ご自身のパソコンスキルに最も近いものを選択してください。'); if($pc_h):?><p class="field-hint"><?= htmlspecialchars($pc_h) ?></p><?php endif;?>
              <div class="radio-group">
                <?php
                $pcOpts = qj_options('pc_skill', [
                  ['value'=>'pc_1','label'=>'パソコンをほとんど使ったことがない。','sub'=>''],
                  ['value'=>'pc_2','label'=>'基本的な操作ができる。','sub'=>'文字入力、インターネット閲覧、メールの送受信など。'],
                  ['value'=>'pc_3','label'=>'Word・Excel が使える。','sub'=>'簡単な文書作成、表の作成、データ入力ができる。'],
                  ['value'=>'pc_4','label'=>'仕事でパソコンを日常的に使っている。','sub'=>'Excel 関数を使ったデータ整理などができる。'],
                  ['value'=>'pc_5','label'=>'専門的な作業ができる。','sub'=>'プログラミング、Web 開発、データ分析など。'],
                ]);
                $selectedPC = $_POST['pc_skill'] ?? '';
                foreach ($pcOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="pc_skill" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedPC === $opt['value'] ? 'checked' : '' ?>>
                  <div class="radio-dot"></div>
                  <span class="radio-text">
                    <?= htmlspecialchars($opt['label']) ?>
                    <?php if(!empty($opt['sub'])):?><span class="sub"><?= htmlspecialchars($opt['sub']) ?></span><?php endif;?>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-pc">PC スキルを選択してください。</div>
            </div>

            <div class="field-divider"></div>

            <!-- AI経験 -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('ai_experience','8. AI ツールの利用経験')) ?> <span class="req">*</span></label>
              <?php $ai_h = qj_hint('ai_experience','ChatGPT などの AI ツールの利用経験として、最も近いものを選択してください。'); if($ai_h):?><p class="field-hint"><?= htmlspecialchars($ai_h) ?></p><?php endif;?>
              <div class="radio-group">
                <?php
                $aiOpts = qj_options('ai_experience', [
                  ['value'=>'ai_1','label'=>'AI ツールを使ったことがない。','sub'=>''],
                  ['value'=>'ai_2','label'=>'試したことはあるが、使いこなせていない。','sub'=>''],
                  ['value'=>'ai_3','label'=>'簡単な作業に AI ツールを使ったことがある。','sub'=>'文章作成、調べもの、要約など。'],
                  ['value'=>'ai_4','label'=>'仕事や学習に AI ツールを活用している。','sub'=>'目的に合わせた指示を工夫して使っている。'],
                  ['value'=>'ai_5','label'=>'AI ツールを活用して資料作成や業務改善ができる。','sub'=>'AI の出力を確認・修正し、他の作業にも役立てられる。'],
                ]);
                $selectedAI = $_POST['ai_experience'] ?? '';
                foreach ($aiOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="ai_experience" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedAI === $opt['value'] ? 'checked' : '' ?>>
                  <div class="radio-dot"></div>
                  <span class="radio-text">
                    <?= htmlspecialchars($opt['label']) ?>
                    <?php if(!empty($opt['sub'])):?><span class="sub"><?= htmlspecialchars($opt['sub']) ?></span><?php endif;?>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-ai">AI ツールの利用経験を選択してください。</div>
            </div>

            <div class="field-divider"></div>

            <!-- 応募動機 -->
            <div class="field-group">
              <label class="field-label" for="reason"><?= htmlspecialchars(qj_label('reason','9. 応募動機')) ?> <span class="req">*</span></label>
              <?php $re_h = qj_hint('reason','応募の理由・動機をご記入ください（500 文字程度）。'); if($re_h):?><p class="field-hint"><?= htmlspecialchars($re_h) ?></p><?php endif;?>
              <textarea class="text-input" id="reason" name="reason"
                        rows="5" maxlength="600"
                        placeholder="<?= htmlspecialchars(qj_placeholder('reason','応募の動機や理由をご記入ください...')) ?>"
                        oninput="updateCharCount(this,'reason-count',500)"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
              <div class="char-counter" id="reason-count">0 / 500</div>
              <div class="field-error" id="err-reason">応募動機を入力してください。</div>
            </div>

            <div class="field-divider"></div>

            <!-- 面接希望日 -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('interview_day','10. 面接希望日')) ?> <span class="req">*</span></label>
              <?php $id_h = qj_hint('interview_day','特定の日程をご希望の場合は「その他」を選択してご記入ください。'); if($id_h):?><p class="field-hint"><?= htmlspecialchars($id_h) ?></p><?php endif;?>
              <div class="radio-group">
                <?php
                $dayOpts = qj_options('interview_day', [
                  ['value'=>'weekdays', 'label'=>'平日',    'sub'=>''],
                  ['value'=>'weekends', 'label'=>'土日・祝日','sub'=>''],
                  ['value'=>'day_other','label'=>'その他',  'sub'=>''],
                ]);
                $selectedDay = $_POST['interview_day'] ?? '';
                foreach ($dayOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="interview_day" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedDay === $opt['value'] ? 'checked' : '' ?>
                         onchange="toggleOther(this,'day-other')">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($opt['label']) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="other-input <?= $selectedDay === 'day_other' ? 'visible' : '' ?>" id="day-other">
                <input class="text-input" type="text" name="interview_day_other"
                       placeholder="ご希望の日程をご記入ください..."
                       value="<?= htmlspecialchars($_POST['interview_day_other'] ?? '') ?>">
              </div>
              <div class="field-error" id="err-interview-day">面接希望日を選択してください。</div>
            </div>

            <!-- 面接希望時間帯 -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('interview_time','11. 面接希望時間帯')) ?> <span class="req">*</span></label>
              <?php $it_h = qj_hint('interview_time','特定の時間帯をご希望の場合は「その他」を選択してご記入ください。'); if($it_h):?><p class="field-hint"><?= htmlspecialchars($it_h) ?></p><?php endif;?>
              <div class="radio-group">
                <?php
                $timeOpts = qj_options('interview_time', [
                  ['value'=>'9_12',      'label'=>'9:00 〜 12:00','sub'=>''],
                  ['value'=>'12_15',     'label'=>'12:00 〜 15:00','sub'=>''],
                  ['value'=>'15_18',     'label'=>'15:00 〜 18:00','sub'=>''],
                  ['value'=>'time_other','label'=>'その他',        'sub'=>''],
                ]);
                $selectedTime = $_POST['interview_time'] ?? '';
                foreach ($timeOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="interview_time" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedTime === $opt['value'] ? 'checked' : '' ?>
                         onchange="toggleOther(this,'time-other')">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($opt['label']) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="other-input <?= $selectedTime === 'time_other' ? 'visible' : '' ?>" id="time-other">
                <input class="text-input" type="text" name="interview_time_other"
                       placeholder="ご希望の時間帯をご記入ください..."
                       value="<?= htmlspecialchars($_POST['interview_time_other'] ?? '') ?>">
              </div>
              <div class="field-error" id="err-interview-time">面接希望時間帯を選択してください。</div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /step-2 -->

        <!-- ══════════════════════════════════════
             STEP 3 — サポートプログラム
        ══════════════════════════════════════ -->
        <div class="form-step" id="step-3">
          <div class="card-section-head">
            <h2>集中学習サポートプログラム</h2>
            <p>任意 — 対象者には別途ご案内します。</p>
          </div>
          <div class="card-body">

            <div class="support-info">
              <h3>サポートプログラムとは？</h3>
              <p>
                経済的な事情やその他の事情により、十分な学習時間の確保が難しい方を対象に、
                <strong>「集中学習サポートプログラム」</strong>を提供しています。
                生活費のサポートを受けながら学習に専念することができます。
              </p>
              <p>
                ※ サポート枠には上限があります（全10名中3名）。
                応募いただいても、申込状況や選考結果によってご要望に沿えない場合があります。
                詳細はプログラム情報ページをご確認ください。
              </p>
              <div class="support-pills">
                <div class="sup-pill">🎁 受講料：完全無料</div>
                <div class="sup-pill">💰 生活支援：月20万円 × 3ヶ月</div>
                <div class="sup-pill">💻 デジタル拠点へのアクセス</div>
                <div class="sup-pill">🕐 育児・介護との両立可</div>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('support_program','このサポートプログラムへの応募を希望しますか？')) ?> <span class="req">*</span></label>
              <div class="radio-group">
                <?php
                $supportOpts = qj_options('support_program', [
                  ['value'=>'yes',      'label'=>'はい、応募を希望します。','sub'=>''],
                  ['value'=>'undecided','label'=>'まだ決めていません。詳しく話を聞きたいです。','sub'=>''],
                  ['value'=>'no',       'label'=>'いいえ、応募しません。','sub'=>''],
                ]);
                $selectedSupport = $_POST['support_program'] ?? '';
                foreach ($supportOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="support_program" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedSupport === $opt['value'] ? 'checked' : '' ?>
                         onchange="toggleSituation(this)">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($opt['label']) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-support">いずれかを選択してください。</div>
            </div>

            <?php $showSit = in_array($selectedSupport, ['yes','undecided']); ?>
            <div id="situation-group" style="<?= $showSit ? '' : 'display:none' ?>">
            <div class="field-divider"></div>

            <!-- Q13: 現在のご状況 -->
            <div class="field-group">
              <label class="field-label" for="support_situation"><?= htmlspecialchars(qj_label('support_situation','13. 現在のご状況とサポート枠を希望する理由')) ?> <span class="req">*</span></label>
              <?php $sit_h = qj_hint('support_situation','現在の生活・就業・家庭のご状況について、差し支えない範囲で具体的に記入してください。特に、サポート枠を希望される理由が分かるように、現在の就業状況、収入面での不安、子育て・介護などご家庭の事情、学習や就労にあたって課題になっていることなどを聞かせてください。'); if($sit_h):?><p class="field-hint"><?= htmlspecialchars($sit_h) ?></p><?php endif;?>
              <textarea class="text-input" id="support_situation" name="support_situation"
                        rows="6" maxlength="1000"
                        placeholder="<?= htmlspecialchars(qj_placeholder('support_situation','現在のご状況をご記入ください...')) ?>"
                        oninput="updateCharCount(this,'sit-count',800)"><?= htmlspecialchars($_POST['support_situation'] ?? '') ?></textarea>
              <div class="char-counter" id="sit-count">0 / 800</div>
              <div class="field-error" id="err-situation">現在のご状況とサポート枠を希望する理由をご記入ください。</div>
            </div>
            </div><!-- /situation-group -->

            <div class="field-divider"></div>

            <!-- Q14: その他 -->
            <div class="field-group">
              <label class="field-label" for="other_questions"><?= htmlspecialchars(qj_label('other_questions','14. その他に、気になることや事前に相談しておきたいことがあれば、自由に記入してください')) ?></label>
              <textarea class="text-input" id="other_questions" name="other_questions"
                        rows="3"
                        placeholder="<?= htmlspecialchars(qj_placeholder('other_questions','回答を入力してください')) ?>"><?= htmlspecialchars($_POST['other_questions'] ?? '') ?></textarea>
            </div>

            <div class="field-divider"></div>

            <!-- Q15: 送信確認 -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(qj_label('confirm_submit','15. この内容で申し込みを送信してよろしいですか？')) ?> <span class="req">*</span></label>
              <?php $cs_h = qj_hint('confirm_submit','送信後の修正はできませんので、内容を確認のうえ送信してください。'); if($cs_h):?><p class="field-hint"><?= htmlspecialchars($cs_h) ?></p><?php endif;?>
              <div class="radio-group">
                <?php
                $confirmOpts = qj_options('confirm_submit', [
                  ['value'=>'yes','label'=>'はい','sub'=>''],
                ]);
                $selectedConfirm = $_POST['confirm_submit'] ?? '';
                foreach ($confirmOpts as $opt):
                ?>
                <label class="radio-option">
                  <input type="radio" name="confirm_submit" value="<?= htmlspecialchars($opt['value']) ?>"
                         <?= $selectedConfirm === $opt['value'] ? 'checked' : '' ?>>
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($opt['label']) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-confirm">「はい」を選択してください。</div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /step-3 -->

        <!-- Navigation buttons -->
        <div class="form-nav" style="padding: 0 32px 28px;">
          <button type="button" class="btn-back" id="btn-back" style="display:none" onclick="changeStep(-1)">
            ← 戻る
          </button>
          <button type="button" class="btn-next" id="btn-next" onclick="changeStep(1)">
            次へ →
          </button>
          <button type="submit" class="btn-submit" id="btn-submit" style="display:none">
            応募を送信する →
          </button>
        </div>
        <div style="text-align:center; padding: 0 32px 20px;">
          <button type="button" id="btn-save" onclick="saveAndShowLink()" style="background:none;border:none;color:var(--mint-dark);font-size:13px;cursor:pointer;text-decoration:underline;padding:4px 0">💾 入力内容を保存して後で続きを入力する</button>
          <div id="save-msg" style="display:none;margin-top:8px;padding:10px 14px;background:#F0FAF8;border:1px solid var(--mint);border-radius:8px;font-size:13px;text-align:left">
            ✅ 保存しました！このリンクをブックマークするか、コピーして保存してください：<br>
            <a id="save-link" href="#" style="word-break:break-all;color:var(--mint-dark);font-size:12px"></a>
          </div>
        </div>

      </form>
    </div><!-- /form-card -->
    <?php endif; ?>

  </div><!-- /form-wrap -->
</div><!-- /apply-page -->

<!-- ========== FOOTER ========== -->
<footer>
  <div class="ft-name">一般社団法人 Robo Co-op</div>
  <p style="margin-bottom:8px;">令和8年度 島根県デジタル人材育成研修</p>
  <p>HP: <a href="https://roboco-op.org" target="_blank">roboco-op.org</a>&nbsp;／&nbsp;お問い合わせ: <a href="mailto:info@roboco-op.org">info@roboco-op.org</a></p>
  <p style="margin-top:16px; font-size:11px;">© 2026 Robo Co-op. All rights reserved.</p>
</footer>

<script>
  // ── Draft auto-save ────────────────────────────────────────────────────────
  let draftToken = document.getElementById('draft_token').value || null;

  function collectFormData() {
    const fields = ['name','email','phone','how_heard','how_heard_other','resume_url',
                    'pc_skill','ai_experience','reason','interview_day','interview_day_other',
                    'interview_time','interview_time_other','support_program',
                    'support_situation','other_questions','confirm_submit'];
    const data = {};
    fields.forEach(f => {
      const el = document.querySelector(`[name="${f}"]:checked`) || document.querySelector(`[name="${f}"]`);
      if (el) data[f] = el.value;
    });
    return data;
  }

  async function saveDraft(nextStep) {
    const email = document.getElementById('email')?.value?.trim();
    if (!email) return;
    try {
      const payload = Object.assign(collectFormData(), { token: draftToken, step: nextStep, lang: 'ja' });
      const res  = await fetch('<?= BASE_URL ?>/admin/api/save-draft', { method:'POST', body: JSON.stringify(payload) });
      const json = await res.json();
      if (json.token) {
        draftToken = json.token;
        document.getElementById('draft_token').value = json.token;
        if (history.replaceState) history.replaceState(null, '', '<?= BASE_URL ?>/apply/ja?token=' + json.token);
      }
    } catch(e) {}
  }

  let currentStep = <?= !empty($errors) ? 3 : 1 ?>;
  const totalSteps = 3;

  function updateUI() {
    for (let i = 1; i <= totalSteps; i++) {
      const el = document.getElementById('step-' + i);
      el.classList.toggle('active', i === currentStep);
    }
    document.querySelectorAll('.pb-step').forEach(s => {
      const n = parseInt(s.dataset.step);
      s.classList.remove('active', 'done');
      if (n === currentStep) s.classList.add('active');
      else if (n < currentStep) s.classList.add('done');
    });
    document.querySelectorAll('.pb-line').forEach((l, idx) => {
      l.classList.toggle('done', idx + 1 < currentStep);
    });
    document.getElementById('btn-back').style.display   = currentStep > 1 ? '' : 'none';
    document.getElementById('btn-next').style.display   = currentStep < totalSteps ? '' : 'none';
    document.getElementById('btn-submit').style.display = currentStep === totalSteps ? '' : 'none';
    document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function validateStep(step) {
    let ok = true;
    if (step === 1) {
      const name = document.getElementById('name');
      const email = document.getElementById('email');
      const conf  = document.getElementById('email_confirm');
      const phone = document.getElementById('phone');

      const showErr = (id, show) => {
        document.getElementById(id).classList.toggle('visible', show);
      };

      const nameOk = [...name.value.trim()].length >= 2;
      showErr('err-name', !nameOk);
      name.classList.toggle('error', !nameOk);
      if (!nameOk) ok = false;

      const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const emailOk = emailRx.test(email.value.trim());
      showErr('err-email', !emailOk);
      email.classList.toggle('error', !emailOk);
      if (!emailOk) ok = false;

      const confOk = conf.value.trim() === email.value.trim() && conf.value.trim().length > 0;
      showErr('err-email-confirm', !confOk);
      conf.classList.toggle('error', !confOk);
      if (!confOk) ok = false;

      const phoneOk = phone.value.replace(/\D/g, '').length >= 10;
      showErr('err-phone', !phoneOk);
      phone.classList.toggle('error', !phoneOk);
      if (!phoneOk) ok = false;
    }
    if (step === 2) {
      const resume = document.getElementById('resume_url');
      const resumeOk = resume.value.trim().length > 0;
      document.getElementById('err-resume').classList.toggle('visible', !resumeOk);
      resume.classList.toggle('error', !resumeOk);
      if (!resumeOk) ok = false;

      const pcOk = !!document.querySelector('input[name="pc_skill"]:checked');
      document.getElementById('err-pc').classList.toggle('visible', !pcOk);
      if (!pcOk) ok = false;

      const aiOk = !!document.querySelector('input[name="ai_experience"]:checked');
      document.getElementById('err-ai').classList.toggle('visible', !aiOk);
      if (!aiOk) ok = false;

      const reason = document.getElementById('reason');
      const reasonOk = reason.value.trim().length > 0;
      document.getElementById('err-reason').classList.toggle('visible', !reasonOk);
      reason.classList.toggle('error', !reasonOk);
      if (!reasonOk) ok = false;

      const dayOk = !!document.querySelector('input[name="interview_day"]:checked');
      document.getElementById('err-interview-day').classList.toggle('visible', !dayOk);
      if (!dayOk) ok = false;

      const timeOk = !!document.querySelector('input[name="interview_time"]:checked');
      document.getElementById('err-interview-time').classList.toggle('visible', !timeOk);
      if (!timeOk) ok = false;
    }
    if (step === 3) {
      const support = document.querySelector('input[name="support_program"]:checked');
      const supportOk = !!support;
      document.getElementById('err-support').classList.toggle('visible', !supportOk);
      if (!supportOk) ok = false;

      if (document.getElementById('situation-group').style.display !== 'none') {
        const situation = document.getElementById('support_situation');
        const situationOk = situation.value.trim().length > 0;
        document.getElementById('err-situation').classList.toggle('visible', !situationOk);
        situation.classList.toggle('error', !situationOk);
        if (!situationOk) ok = false;
      }

      const confirm = document.querySelector('input[name="confirm_submit"]:checked');
      const confirmOk = !!confirm;
      document.getElementById('err-confirm').classList.toggle('visible', !confirmOk);
      if (!confirmOk) ok = false;
    }
    return ok;
  }

  function changeStep(dir) {
    if (dir === 1 && !validateStep(currentStep)) return;
    const nextStep = Math.max(1, Math.min(totalSteps, currentStep + dir));
    if (dir === 1) saveDraft(nextStep);
    currentStep = nextStep;
    updateUI();
  }

  async function saveAndShowLink() {
    const email = document.getElementById('email')?.value?.trim();
    if (!email) { alert('先にメールアドレスを入力してください。'); return; }
    await saveDraft(currentStep);
    if (!draftToken) { alert('保存できませんでした。もう一度お試しください。'); return; }
    const link = window.location.href;
    document.getElementById('save-link').href = link;
    document.getElementById('save-link').textContent = link;
    document.getElementById('save-msg').style.display = '';
    document.getElementById('btn-save').textContent = '✅ 保存しました！';
  }

  function toggleSituation(radio) {
    const show = radio.value === 'yes' || radio.value === 'undecided';
    const group = document.getElementById('situation-group');
    group.style.display = show ? '' : 'none';
    if (!show) {
      document.getElementById('support_situation').value = '';
      document.getElementById('err-situation').classList.remove('visible');
      document.getElementById('support_situation').classList.remove('error');
    }
  }

  function toggleOther(radio, targetId) {
    const container = document.getElementById(targetId);
    if (!container) return;
    container.classList.toggle('visible', radio.value.endsWith('other'));
  }

  function updateCharCount(el, countId, max) {
    const len = el.value.length;
    const counter = document.getElementById(countId);
    counter.textContent = len + ' / ' + max;
    counter.classList.toggle('warn', len > max * 0.9);
  }

  const reasonEl = document.getElementById('reason');
  if (reasonEl) updateCharCount(reasonEl, 'reason-count', 500);
  const sitEl = document.getElementById('support_situation');
  if (sitEl) updateCharCount(sitEl, 'sit-count', 800);

  document.getElementById('app-form').addEventListener('submit', function(e) {
    if (!validateStep(3)) e.preventDefault();
  });

  updateUI();
</script>
</body>
</html>
