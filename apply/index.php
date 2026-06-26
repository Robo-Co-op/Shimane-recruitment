<?php
$lang = 'en';
$submitted = false;
$errors = [];
$resume_draft = null;
$done_email = '';
$done_name  = '';

require_once __DIR__ . '/../includes/base.php';
require_once __DIR__ . '/../admin/includes/db.php';

// Load questions from file cache — no DB connection needed for a plain GET visit
$_raw_qs = get_form_questions('en-application');
$_qmap   = array_column($_raw_qs, null, 'field_name');
// Override Q6 hint: remove the optional-field note regardless of what DB contains
if (isset($_qmap['resume_url'])) {
    $_qmap['resume_url']['hint'] = 'Share the URL of the file where you uploaded your resume (Google Drive, Dropbox, etc.).';
}

function q_label(string $name, string $default): string {
    global $_qmap;
    return $_qmap[$name]['label'] ?? $default;
}
function q_hint(string $name, string $default = ''): string {
    global $_qmap;
    return $_qmap[$name]['hint'] ?? $default;
}
function q_placeholder(string $name, string $default = ''): string {
    global $_qmap;
    return $_qmap[$name]['placeholder'] ?? $default;
}
function q_options(string $name, array $defaults): array {
    global $_qmap;
    return !empty($_qmap[$name]['options']) ? $_qmap[$name]['options'] : $defaults;
}

// ── Resume from token (GET) ──────────────────────────────────────────────────
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

// ── Handle final POST submission ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (empty($name))   $errors[] = 'Name is required.';
    if (empty($email))  $errors[] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($email !== $email_confirm) $errors[] = 'Email addresses do not match.';
    if (empty($phone))  $errors[] = 'Phone number is required.';
    if (empty($reason)) $errors[] = 'Reason for applying is required.';
    if (empty($support_program)) $errors[] = 'Please indicate your interest in the support program.';
    if (in_array($support_program, ['yes','undecided']) && empty($support_situation)) $errors[] = 'Please describe your current situation and reason for requesting support.';
    if ($confirm_submit !== 'yes') $errors[] = 'Please confirm your submission by selecting "Yes".';

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
                 support_situation,other_questions,confirm_submit,lang,ip_address)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'en',?)")
               ->execute([$draft_id,$name,$email,$phone,$how_heard,$how_heard_other,$resume_url,$pc_skill,
                          $ai_experience,$reason,$interview_day,$interview_day_other,$interview_time,
                          $interview_time_other,$support_program,$support_situation,$other_questions,
                          $confirm_submit,$_SERVER['REMOTE_ADDR']??'']);

            if ($draft_id) {
                $db->prepare("UPDATE form_drafts SET completed=1, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                   ->execute([$draft_id]);
            }

            $dir = dirname(__DIR__) . '/submissions';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $file = $dir . '/applications.csv';
            $fp   = fopen($file, 'a');
            if (!file_exists($file) || filesize($file) === 0) {
                fputcsv($fp, ['timestamp','name','email','phone','how_heard','how_heard_other','resume_url',
                              'pc_skill','ai_experience','reason','interview_day','interview_day_other',
                              'interview_time','interview_time_other','support_program',
                              'support_situation','other_questions','confirm_submit']);
            }
            fputcsv($fp, [date('Y-m-d H:i:s'),$name,$email,$phone,$how_heard,$how_heard_other,$resume_url,
                          $pc_skill,$ai_experience,$reason,$interview_day,$interview_day_other,
                          $interview_time,$interview_time_other,$support_program,
                          $support_situation,$other_questions,$confirm_submit]);
            fclose($fp);

            $done_email = $email;
            $done_name  = $name;
            $submitted  = true;
        } catch (\Throwable $e) {
            $errors[] = 'A system error occurred. Please try again later. (' . htmlspecialchars($e->getMessage()) . ')';
            error_log('apply/en submit error: ' . $e->getMessage());
        }
    }
    } catch (\Throwable $e) {
        $errors[] = 'DB connection error: ' . htmlspecialchars($e->getMessage());
        error_log('apply/en db error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Form — Shimane IB | Robo Co-op</title>
  <?php include __DIR__ . '/../includes/styles.php'; ?>
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
      font-size: clamp(22px, 4vw, 28px);
      font-weight: 900;
      color: var(--warm-dark);
      line-height: 1.35;
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
  <a class="logo" href="/en">
    <img src="/logo.png" alt="Robo Co-op" class="logo-img">
  </a>
  <div class="header-right">
    <a href="/en" class="lang-switch">← Back to site</a>
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
        <h2>Application Submitted!</h2>
        <p class="s-intro">
          Thank you for applying to the<br>
          Shimane Prefecture × Robo Co-op FY2026<br>
          Digital Talent Development Program.<br>
          Our team will review your application carefully.
        </p>

        <div class="email-confirm">
          <div class="ec-label">Confirmation sent to</div>
          <div class="ec-val"><?= htmlspecialchars($done_email) ?></div>
        </div>

        <div class="next-steps">
          <h3>What happens next</h3>
          <div class="ns-step">
            <div class="ns-num">1</div>
            <div class="ns-text"><strong>Application Review</strong>Our team will carefully review your application.</div>
          </div>
          <div class="ns-step">
            <div class="ns-num">2</div>
            <div class="ns-text"><strong>Interview Invitation</strong>We will contact you within 3 business days via email.</div>
          </div>
          <div class="ns-step">
            <div class="ns-num">3</div>
            <div class="ns-text"><strong>Online Interview</strong>Full details will be shared in your invitation email.</div>
          </div>
        </div>

        <div class="response-badge">
          ⏱&nbsp; Expected response: within 3 business days
        </div>

        <br>
        <a href="/en" class="success-back-btn">← Return to program information</a>
      </div>

    </div>

    <?php else: ?>
    <!-- ── FORM ── -->
    <div class="form-header">
      <div class="badge">📝 Application Form</div>
      <h1>Shimane IB — FY2026<br>Digital Talent Development Program</h1>
      <p>Shimane Prefecture × Robo Co-op &nbsp;·&nbsp; Applications open now</p>
    </div>

    <!-- Progress bar -->
    <div class="progress-bar" id="progress-bar">
      <div class="pb-step active" data-step="1">
        <div class="pb-num">1</div>
        <div class="pb-label">Basic Info</div>
      </div>
      <div class="pb-line" id="line-1"></div>
      <div class="pb-step" data-step="2">
        <div class="pb-num">2</div>
        <div class="pb-label">Background</div>
      </div>
      <div class="pb-line" id="line-2"></div>
      <div class="pb-step" data-step="3">
        <div class="pb-num">3</div>
        <div class="pb-label">Support</div>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="error-banner">
      <strong>Please fix the following before submitting:</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="form-card">
      <form method="POST" action="/apply/" id="app-form" novalidate>
        <input type="hidden" name="_draft_token" id="draft_token" value="<?= htmlspecialchars($_POST['_draft_token'] ?? '') ?>">

        <!-- ══════════════════════════════════════
             STEP 1 — Basic Information
        ══════════════════════════════════════ -->
        <div class="form-step active" id="step-1">
          <div class="card-section-head">
            <h2>Basic information</h2>
            <p>Fields marked <span style="color:#E05555">*</span> are required.</p>
          </div>
          <div class="card-body">

            <div class="privacy-notice">
              <strong>Regarding personal information</strong><br>
              Personal data collected in this form will be used solely for operating the training program
              and will not be provided to third parties without your consent.
            </div>

            <!-- Name -->
            <div class="field-group">
              <label class="field-label" for="name"><?= htmlspecialchars(q_label('name','1. Name')) ?> <span class="req">*</span></label>
              <input class="text-input" type="text" id="name" name="name"
                     placeholder="<?= htmlspecialchars(q_placeholder('name','Enter your full name')) ?>"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="name">
              <div class="field-error" id="err-name">Please enter your full name.</div>
            </div>

            <!-- Email -->
            <div class="field-group">
              <label class="field-label" for="email"><?= htmlspecialchars(q_label('email','2. Email address')) ?> <span class="req">*</span></label>
              <input class="text-input" type="email" id="email" name="email"
                     placeholder="<?= htmlspecialchars(q_placeholder('email','your@email.com')) ?>"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
              <div class="field-error" id="err-email">Please enter a valid email address.</div>
            </div>

            <!-- Email confirm -->
            <div class="field-group">
              <label class="field-label" for="email_confirm"><?= htmlspecialchars(q_label('email_confirm','3. Confirmation email address')) ?> <span class="req">*</span></label>
              <?php $ec_hint = q_hint('email_confirm','Re-enter your email address to confirm it.'); if ($ec_hint): ?><p class="field-hint"><?= htmlspecialchars($ec_hint) ?></p><?php endif; ?>
              <input class="text-input" type="email" id="email_confirm" name="email_confirm"
                     placeholder="<?= htmlspecialchars(q_placeholder('email_confirm','your@email.com')) ?>"
                     value="<?= htmlspecialchars($_POST['email_confirm'] ?? '') ?>">
              <div class="field-error" id="err-email-confirm">Email addresses do not match.</div>
            </div>

            <!-- Phone -->
            <div class="field-group">
              <label class="field-label" for="phone"><?= htmlspecialchars(q_label('phone','4. Phone number')) ?> <span class="req">*</span></label>
              <?php $ph_hint = q_hint('phone','If we are unable to contact you via email, we may reach out by phone.'); if ($ph_hint): ?><p class="field-hint"><?= htmlspecialchars($ph_hint) ?></p><?php endif; ?>
              <input class="text-input" type="tel" id="phone" name="phone"
                     placeholder="<?= htmlspecialchars(q_placeholder('phone','e.g. 080-1234-5678')) ?>"
                     value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel">
              <div class="field-error" id="err-phone">Please enter your phone number.</div>
            </div>

            <!-- How did you hear -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('how_heard','5. How did you hear about this training?')) ?></label>
              <div class="radio-group">
                <?php
                $howHeardOpts = q_options('how_heard', [
                  ['value'=>'municipality',  'label'=>'Information from a local municipality or support organization','sub'=>''],
                  ['value'=>'social_media',  'label'=>'Social media (Facebook, X/Twitter, etc.)','sub'=>''],
                  ['value'=>'recommendation','label'=>'Recommendation from family or friends','sub'=>''],
                  ['value'=>'robocoop_web',  'label'=>"Robo Co-op's website",'sub'=>''],
                  ['value'=>'other',         'label'=>'Other','sub'=>''],
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
                       placeholder="Please specify..."
                       value="<?= htmlspecialchars($_POST['how_heard_other'] ?? '') ?>">
              </div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /step-1 -->

        <!-- ══════════════════════════════════════
             STEP 2 — Background & Skills
        ══════════════════════════════════════ -->
        <div class="form-step" id="step-2">
          <div class="card-section-head">
            <h2>Background &amp; Skills</h2>
            <p>Help us understand your experience and motivations.</p>
          </div>
          <div class="card-body">

            <!-- Resume URL -->
            <div class="field-group">
              <label class="field-label" for="resume_url"><?= htmlspecialchars(q_label('resume_url','6. Resume / CV — URL')) ?> <span class="req">*</span></label>
              <?php $ru_hint = q_hint('resume_url','Share the URL of the file where you uploaded your resume (Google Drive, Dropbox, etc.).'); if ($ru_hint): ?><p class="field-hint"><?= htmlspecialchars($ru_hint) ?></p><?php endif; ?>
              <input class="text-input" type="url" id="resume_url" name="resume_url"
                     placeholder="<?= htmlspecialchars(q_placeholder('resume_url','https://drive.google.com/...')) ?>"
                     value="<?= htmlspecialchars($_POST['resume_url'] ?? '') ?>">
              <div class="field-error" id="err-resume">Please enter the URL of your resume / CV.</div>
            </div>

            <div class="field-divider"></div>

            <!-- PC skill -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('pc_skill','7. PC skill')) ?> <span class="req">*</span></label>
              <?php $pc_hint = q_hint('pc_skill','Select the option that best describes your computer skills.'); if ($pc_hint): ?><p class="field-hint"><?= htmlspecialchars($pc_hint) ?></p><?php endif; ?>
              <div class="radio-group">
                <?php
                $pcOpts = q_options('pc_skill', [
                  ['value'=>'pc_1','label'=>'I have little to no experience using a computer.','sub'=>''],
                  ['value'=>'pc_2','label'=>'I can perform basic computer tasks.','sub'=>'Typing, browsing the internet, sending/receiving emails.'],
                  ['value'=>'pc_3','label'=>'I can use Word and Excel.','sub'=>'Create simple documents, tables, and data entries.'],
                  ['value'=>'pc_4','label'=>'I use a computer regularly at work.','sub'=>'Can use Excel functions and organize data.'],
                  ['value'=>'pc_5','label'=>'I can perform specialized tasks.','sub'=>'Programming, web development, and data analysis.'],
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
                    <?php if (!empty($opt['sub'])): ?><span class="sub"><?= htmlspecialchars($opt['sub']) ?></span><?php endif; ?>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-pc">Please select your PC skill level.</div>
            </div>

            <div class="field-divider"></div>

            <!-- AI experience -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('ai_experience','8. AI Tool Usage and Experience')) ?> <span class="req">*</span></label>
              <?php $ai_hint = q_hint('ai_experience','Please select the option that best describes your experience using AI tools such as ChatGPT.'); if ($ai_hint): ?><p class="field-hint"><?= htmlspecialchars($ai_hint) ?></p><?php endif; ?>
              <div class="radio-group">
                <?php
                $aiOpts = q_options('ai_experience', [
                  ['value'=>'ai_1','label'=>'I have never used AI tools.','sub'=>''],
                  ['value'=>'ai_2','label'=>'I have tried AI tools, but I am still not familiar with how to use them effectively.','sub'=>''],
                  ['value'=>'ai_3','label'=>'I have used AI tools for simple tasks.','sub'=>'Writing, research, and summarization.'],
                  ['value'=>'ai_4','label'=>'I use AI tools for work or learning.','sub'=>'Providing instructions tailored to my needs.'],
                  ['value'=>'ai_5','label'=>'I can effectively use AI tools to create documents and improve workflows.','sub'=>'Reviewing and refining AI outputs to support other tasks.'],
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
                    <?php if (!empty($opt['sub'])): ?><span class="sub"><?= htmlspecialchars($opt['sub']) ?></span><?php endif; ?>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-ai">Please select your AI tool experience level.</div>
            </div>

            <div class="field-divider"></div>

            <!-- Reason for applying -->
            <div class="field-group">
              <label class="field-label" for="reason"><?= htmlspecialchars(q_label('reason','9. Reason for applying')) ?> <span class="req">*</span></label>
              <?php $re_hint = q_hint('reason','Please describe your motivation for applying (around 500 characters).'); if ($re_hint): ?><p class="field-hint"><?= htmlspecialchars($re_hint) ?></p><?php endif; ?>
              <textarea class="text-input" id="reason" name="reason"
                        rows="5" maxlength="600"
                        placeholder="<?= htmlspecialchars(q_placeholder('reason','Describe your motivation for applying...')) ?>"
                        oninput="updateCharCount(this,'reason-count',500)"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
              <div class="char-counter" id="reason-count">0 / 500</div>
              <div class="field-error" id="err-reason">Please describe your reason for applying.</div>
            </div>

            <div class="field-divider"></div>

            <!-- Interview day -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('interview_day','10. Preferred interview day')) ?> <span class="req">*</span></label>
              <?php $id_hint = q_hint('interview_day','If you prefer a specific day, please select "Other" and specify.'); if ($id_hint): ?><p class="field-hint"><?= htmlspecialchars($id_hint) ?></p><?php endif; ?>
              <div class="radio-group">
                <?php
                $dayOpts = q_options('interview_day', [
                  ['value'=>'weekdays', 'label'=>'Weekdays',          'sub'=>''],
                  ['value'=>'weekends', 'label'=>'Weekends / Holidays','sub'=>''],
                  ['value'=>'day_other','label'=>'Other',             'sub'=>''],
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
                       placeholder="Please specify your preferred day..."
                       value="<?= htmlspecialchars($_POST['interview_day_other'] ?? '') ?>">
              </div>
              <div class="field-error" id="err-interview-day">Please select your preferred interview day.</div>
            </div>

            <!-- Interview time -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('interview_time','11. Preferred interview time slot')) ?> <span class="req">*</span></label>
              <?php $it_hint = q_hint('interview_time','If you prefer a specific time, please select "Other" and specify.'); if ($it_hint): ?><p class="field-hint"><?= htmlspecialchars($it_hint) ?></p><?php endif; ?>
              <div class="radio-group">
                <?php
                $timeOpts = q_options('interview_time', [
                  ['value'=>'9_12',      'label'=>'9:00 – 12:00','sub'=>''],
                  ['value'=>'12_15',     'label'=>'12:00 – 15:00','sub'=>''],
                  ['value'=>'15_18',     'label'=>'15:00 – 18:00','sub'=>''],
                  ['value'=>'time_other','label'=>'Other',        'sub'=>''],
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
                       placeholder="Please specify your preferred time slot..."
                       value="<?= htmlspecialchars($_POST['interview_time_other'] ?? '') ?>">
              </div>
              <div class="field-error" id="err-interview-time">Please select your preferred interview time slot.</div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /step-2 -->

        <!-- ══════════════════════════════════════
             STEP 3 — Support Program
        ══════════════════════════════════════ -->
        <div class="form-step" id="step-3">
          <div class="card-section-head">
            <h2>Intensive Learning Support Program</h2>
            <p>Optional — available to qualifying applicants.</p>
          </div>
          <div class="card-body">

            <div class="support-info">
              <h3>What is the support program?</h3>
              <p>
                We offer an <strong>"Intensive Learning Support Program"</strong> for individuals who may have
                difficulty securing sufficient study time due to financial circumstances or other challenges.
                Participants can receive support for living expenses while focusing on their studies.
              </p>
              <p>
                Please note: The number of available support slots is limited (3 of 10 total).
                Even if you apply, we may not be able to accommodate your request depending on the
                number of applications and selection results.
                For more details, please refer to the program information page.
              </p>
              <div class="support-pills">
                <div class="sup-pill">🎁 Tuition: completely free</div>
                <div class="sup-pill">💰 Living support: ¥200,000/month × 3 months</div>
                <div class="sup-pill">💻 Access to digital hubs</div>
                <div class="sup-pill">🕐 Compatible with childcare &amp; caregiving</div>
              </div>
            </div>

            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('support_program','Would you like to apply for this support program?')) ?> <span class="req">*</span></label>
              <div class="radio-group">
                <?php
                $supportOpts = q_options('support_program', [
                  ['value'=>'yes',      'label'=>'Yes, I would like to apply.','sub'=>''],
                  ['value'=>'undecided','label'=>'I am undecided and would like to discuss it further.','sub'=>''],
                  ['value'=>'no',       'label'=>'No, I do not wish to apply.','sub'=>''],
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
              <div class="field-error" id="err-support">Please select an option.</div>
            </div>

            <?php $showSit = in_array($selectedSupport, ['yes','undecided']); ?>
            <div id="situation-group" style="<?= $showSit ? '' : 'display:none' ?>">
            <div class="field-divider"></div>

            <!-- Q13: Current situation -->
            <div class="field-group">
              <label class="field-label" for="support_situation"><?= htmlspecialchars(q_label('support_situation','13. Current Situation and Reason for Requesting the Support Program')) ?> <span class="req">*</span></label>
              <?php $sit_h = q_hint('support_situation','Please describe your current living, employment, and family situation in as much detail as you feel comfortable sharing. In particular, please help us understand why you are requesting support by explaining your current employment status, financial concerns, family responsibilities such as childcare or caregiving, and any challenges you are facing in pursuing your studies or finding employment.'); if($sit_h):?><p class="field-hint"><?= htmlspecialchars($sit_h) ?></p><?php endif;?>
              <textarea class="text-input" id="support_situation" name="support_situation"
                        rows="6" maxlength="1000"
                        placeholder="<?= htmlspecialchars(q_placeholder('support_situation','Please describe your current situation...')) ?>"
                        oninput="updateCharCount(this,'sit-count',800)"><?= htmlspecialchars($_POST['support_situation'] ?? '') ?></textarea>
              <div class="char-counter" id="sit-count">0 / 800</div>
              <div class="field-error" id="err-situation">Please describe your current situation and reason for requesting support.</div>
            </div>
            </div><!-- /situation-group -->

            <div class="field-divider"></div>

            <!-- Q14: Other questions -->
            <div class="field-group">
              <label class="field-label" for="other_questions"><?= htmlspecialchars(q_label('other_questions','14. If you have any questions, concerns, or topics you would like to discuss in advance, please feel free to enter them below.')) ?></label>
              <textarea class="text-input" id="other_questions" name="other_questions"
                        rows="3"
                        placeholder="<?= htmlspecialchars(q_placeholder('other_questions','Enter any questions or comments (optional)...')) ?>"><?= htmlspecialchars($_POST['other_questions'] ?? '') ?></textarea>
            </div>

            <div class="field-divider"></div>

            <!-- Q15: Confirm submission -->
            <div class="field-group">
              <label class="field-label"><?= htmlspecialchars(q_label('confirm_submit','15. Would you like to submit your application with the information provided above?')) ?> <span class="req">*</span></label>
              <?php $cs_h = q_hint('confirm_submit','Please review your information carefully before submitting, as changes cannot be made after submission.'); if($cs_h):?><p class="field-hint"><?= htmlspecialchars($cs_h) ?></p><?php endif;?>
              <div class="radio-group">
                <?php
                $confirmOpts = q_options('confirm_submit', [
                  ['value'=>'yes','label'=>'Yes','sub'=>''],
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
              <div class="field-error" id="err-confirm">Please confirm your submission.</div>
            </div>

          </div><!-- /card-body -->
        </div><!-- /step-3 -->

        <!-- Navigation buttons -->
        <div class="form-nav" style="padding: 0 32px 28px;">
          <button type="button" class="btn-back" id="btn-back" style="display:none" onclick="changeStep(-1)">
            ← Back
          </button>
          <button type="button" class="btn-next" id="btn-next" onclick="changeStep(1)">
            Next →
          </button>
          <button type="submit" class="btn-submit" id="btn-submit" style="display:none">
            Submit Application →
          </button>
        </div>

      </form>
    </div><!-- /form-card -->
    <?php endif; ?>

  </div><!-- /form-wrap -->
</div><!-- /apply-page -->

<!-- ========== FOOTER ========== -->
<footer>
  <div class="ft-name">Robo Co-op (General Incorporated Association)</div>
  <p style="margin-bottom:8px;">FY2026 Shimane Prefecture Digital Talent Development Program</p>
  <p>HP: <a href="https://roboco-op.org" target="_blank">roboco-op.org</a>&nbsp;／&nbsp;Contact: <a href="mailto:info@roboco-op.org">info@roboco-op.org</a></p>
  <p style="margin-top:16px; font-size:11px;">© 2026 Robo Co-op. All rights reserved.</p>
</footer>

<script>
  let currentStep = <?= !empty($errors) ? 3 : 1 ?>;
  const totalSteps = 3;

  function updateUI() {
    // Show/hide steps
    for (let i = 1; i <= totalSteps; i++) {
      const el = document.getElementById('step-' + i);
      el.classList.toggle('active', i === currentStep);
    }
    // Progress bar
    document.querySelectorAll('.pb-step').forEach(s => {
      const n = parseInt(s.dataset.step);
      s.classList.remove('active', 'done');
      if (n === currentStep) s.classList.add('active');
      else if (n < currentStep) s.classList.add('done');
    });
    document.querySelectorAll('.pb-line').forEach((l, idx) => {
      l.classList.toggle('done', idx + 1 < currentStep);
    });
    // Buttons
    document.getElementById('btn-back').style.display   = currentStep > 1 ? '' : 'none';
    document.getElementById('btn-next').style.display   = currentStep < totalSteps ? '' : 'none';
    document.getElementById('btn-submit').style.display = currentStep === totalSteps ? '' : 'none';
    // Scroll to top of card
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

      const nameOk = name.value.trim().length > 0;
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

      const phoneOk = phone.value.trim().length > 0;
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

  // ── Draft auto-save ──────────────────────────────────────────────────────
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
      const payload = Object.assign(collectFormData(), {
        token: draftToken, step: nextStep, lang: 'en'
      });
      const res  = await fetch('<?= BASE_URL ?>/admin/api/save-draft', { method:'POST', body: JSON.stringify(payload) });
      const json = await res.json();
      if (json.token) {
        draftToken = json.token;
        document.getElementById('draft_token').value = json.token;
        // Update URL without reload so resume link works
        if (history.replaceState) {
          history.replaceState(null, '', '<?= BASE_URL ?>/apply?token=' + json.token);
        }
      }
    } catch(e) {}
  }

  function changeStep(dir) {
    if (dir === 1 && !validateStep(currentStep)) return;
    const nextStep = Math.max(1, Math.min(totalSteps, currentStep + dir));
    if (dir === 1) saveDraft(nextStep);
    currentStep = nextStep;
    updateUI();
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
    const group = radio.closest('.field-group') || radio.closest('.radio-group').parentElement;
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

  // Init char counters
  const reasonEl = document.getElementById('reason');
  if (reasonEl) updateCharCount(reasonEl, 'reason-count', 500);
  const sitEl = document.getElementById('support_situation');
  if (sitEl) updateCharCount(sitEl, 'sit-count', 800);

  // Inline form submit validation
  document.getElementById('app-form').addEventListener('submit', function(e) {
    if (!validateStep(3)) e.preventDefault();
  });

  // Init on load (handles PHP error repopulate)
  updateUI();
</script>
</body>
</html>
