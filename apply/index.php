<?php
$lang = 'en';
$submitted = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (empty($name))   $errors[] = 'Name is required.';
    if (empty($email))  $errors[] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($email !== $email_confirm) $errors[] = 'Email addresses do not match.';
    if (empty($phone))  $errors[] = 'Phone number is required.';
    if (empty($reason)) $errors[] = 'Reason for applying is required.';
    if (empty($support_program)) $errors[] = 'Please indicate your interest in the support program.';

    if (empty($errors)) {
        $dir = dirname(__DIR__) . '/submissions';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file  = $dir . '/applications.csv';
        $isNew = !file_exists($file);
        $fp    = fopen($file, 'a');
        if ($isNew) {
            fputcsv($fp, ['timestamp','name','email','phone','how_heard','how_heard_other',
                          'resume_url','pc_skill','ai_experience','reason',
                          'interview_day','interview_day_other','interview_time','interview_time_other',
                          'support_program']);
        }
        fputcsv($fp, [date('Y-m-d H:i:s'), $name, $email, $phone, $how_heard, $how_heard_other,
                      $resume_url, $pc_skill, $ai_experience, $reason,
                      $interview_day, $interview_day_other, $interview_time, $interview_time_other,
                      $support_program]);
        fclose($fp);
        $submitted = true;
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
      padding: 52px 36px;
      text-align: center;
    }
    .success-icon {
      font-size: 56px;
      margin-bottom: 20px;
    }
    .success-card h2 {
      font-size: 24px; font-weight: 900;
      color: var(--warm-dark); margin-bottom: 12px;
    }
    .success-card p {
      font-size: 15px; color: var(--warm-mid);
      line-height: 1.8; margin-bottom: 8px;
      max-width: 460px; margin-left: auto; margin-right: auto;
    }
    .success-card .ref {
      margin-top: 28px;
      background: var(--mint-pale);
      border-radius: 12px;
      padding: 14px 20px;
      font-size: 13px; color: var(--mint-dark);
      font-weight: 700;
    }
    .success-back {
      display: inline-block;
      margin-top: 28px;
      color: var(--mint-dark);
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
    }
    .success-back:hover { text-decoration: underline; }

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
  <div class="logo">
    <div class="logo-mark">RC</div>
    <div>
      <div class="logo-text">Robo Co-op</div>
      <div class="logo-sub">General Incorporated Association</div>
    </div>
  </div>
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
      <div class="success-icon">🎉</div>
      <h2>Application Submitted!</h2>
      <p>
        Thank you for applying to the Shimane Prefecture × Robo Co-op<br>
        FY2026 Digital Talent Development Program.
      </p>
      <p>
        We have received your application and our team will review it carefully.<br>
        We will contact you at <strong><?= htmlspecialchars($email) ?></strong> with next steps.
      </p>
      <div class="ref">
        Expected response: within 5–7 business days
      </div>
      <br>
      <a href="/en" class="success-back">← Return to program information</a>
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
      <form method="POST" action="/apply" id="app-form" novalidate>

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
              <label class="field-label" for="name">1. Name <span class="req">*</span></label>
              <input class="text-input" type="text" id="name" name="name"
                     placeholder="Enter your full name"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="name">
              <div class="field-error" id="err-name">Please enter your full name.</div>
            </div>

            <!-- Email -->
            <div class="field-group">
              <label class="field-label" for="email">2. Email address <span class="req">*</span></label>
              <input class="text-input" type="email" id="email" name="email"
                     placeholder="your@email.com"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
              <div class="field-error" id="err-email">Please enter a valid email address.</div>
            </div>

            <!-- Email confirm -->
            <div class="field-group">
              <label class="field-label" for="email_confirm">3. Confirmation email address <span class="req">*</span></label>
              <p class="field-hint">Re-enter your email address to confirm it.</p>
              <input class="text-input" type="email" id="email_confirm" name="email_confirm"
                     placeholder="your@email.com"
                     value="<?= htmlspecialchars($_POST['email_confirm'] ?? '') ?>">
              <div class="field-error" id="err-email-confirm">Email addresses do not match.</div>
            </div>

            <!-- Phone -->
            <div class="field-group">
              <label class="field-label" for="phone">4. Phone number <span class="req">*</span></label>
              <p class="field-hint">If we are unable to contact you via email, we may reach out by phone.</p>
              <input class="text-input" type="tel" id="phone" name="phone"
                     placeholder="e.g. 080-1234-5678"
                     value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel">
              <div class="field-error" id="err-phone">Please enter your phone number.</div>
            </div>

            <!-- How did you hear -->
            <div class="field-group">
              <label class="field-label">5. How did you hear about this training?</label>
              <div class="radio-group">
                <?php
                $howHeardOptions = [
                  'municipality'   => 'Information from a local municipality or support organization',
                  'social_media'   => 'Social media (Facebook, X/Twitter, etc.)',
                  'recommendation' => 'Recommendation from family or friends',
                  'robocoop_web'   => 'Robo Co-op\'s website',
                  'other'          => 'Other',
                ];
                $selectedHow = $_POST['how_heard'] ?? '';
                foreach ($howHeardOptions as $val => $label):
                ?>
                <label class="radio-option">
                  <input type="radio" name="how_heard" value="<?= $val ?>"
                         <?= $selectedHow === $val ? 'checked' : '' ?>
                         onchange="toggleOther(this,'how-other')">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($label) ?></span>
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
              <label class="field-label" for="resume_url">6. Resume / CV — URL</label>
              <p class="field-hint">
                Share the URL of the file where you uploaded your resume (Google Drive, Dropbox, etc.).
                Leave blank if you do not have one ready.
              </p>
              <input class="text-input" type="url" id="resume_url" name="resume_url"
                     placeholder="https://drive.google.com/..."
                     value="<?= htmlspecialchars($_POST['resume_url'] ?? '') ?>">
            </div>

            <div class="field-divider"></div>

            <!-- PC skill -->
            <div class="field-group">
              <label class="field-label">7. PC skill</label>
              <p class="field-hint">Select the option that best describes your computer skills.</p>
              <div class="radio-group">
                <?php
                $pcOptions = [
                  'pc_1' => ['I have little to no experience using a computer.', ''],
                  'pc_2' => ['I can perform basic computer tasks.', 'Typing, browsing the internet, sending/receiving emails.'],
                  'pc_3' => ['I can use Word and Excel.', 'Create simple documents, tables, and data entries.'],
                  'pc_4' => ['I use a computer regularly at work.', 'Can use Excel functions and organize data.'],
                  'pc_5' => ['I can perform specialized tasks.', 'Programming, web development, and data analysis.'],
                ];
                $selectedPC = $_POST['pc_skill'] ?? '';
                foreach ($pcOptions as $val => [$main, $sub]):
                ?>
                <label class="radio-option">
                  <input type="radio" name="pc_skill" value="<?= $val ?>"
                         <?= $selectedPC === $val ? 'checked' : '' ?>>
                  <div class="radio-dot"></div>
                  <span class="radio-text">
                    <?= htmlspecialchars($main) ?>
                    <?php if ($sub): ?><span class="sub"><?= htmlspecialchars($sub) ?></span><?php endif; ?>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="field-divider"></div>

            <!-- AI experience -->
            <div class="field-group">
              <label class="field-label">8. AI Tool Usage and Experience</label>
              <p class="field-hint">Please select the option that best describes your experience using AI tools such as ChatGPT.</p>
              <div class="radio-group">
                <?php
                $aiOptions = [
                  'ai_1' => ['I have never used AI tools.', ''],
                  'ai_2' => ['I have tried AI tools, but I am still not familiar with how to use them effectively.', ''],
                  'ai_3' => ['I have used AI tools for simple tasks.', 'Writing, research, and summarization.'],
                  'ai_4' => ['I use AI tools for work or learning.', 'Providing instructions tailored to my needs.'],
                  'ai_5' => ['I can effectively use AI tools to create documents and improve workflows.', 'Reviewing and refining AI outputs to support other tasks.'],
                ];
                $selectedAI = $_POST['ai_experience'] ?? '';
                foreach ($aiOptions as $val => [$main, $sub]):
                ?>
                <label class="radio-option">
                  <input type="radio" name="ai_experience" value="<?= $val ?>"
                         <?= $selectedAI === $val ? 'checked' : '' ?>>
                  <div class="radio-dot"></div>
                  <span class="radio-text">
                    <?= htmlspecialchars($main) ?>
                    <?php if ($sub): ?><span class="sub"><?= htmlspecialchars($sub) ?></span><?php endif; ?>
                  </span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="field-divider"></div>

            <!-- Reason for applying -->
            <div class="field-group">
              <label class="field-label" for="reason">9. Reason for applying <span class="req">*</span></label>
              <p class="field-hint">Please describe your motivation for applying (around 500 characters).</p>
              <textarea class="text-input" id="reason" name="reason"
                        rows="5" maxlength="600"
                        placeholder="Describe your motivation for applying..."
                        oninput="updateCharCount(this,'reason-count',500)"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
              <div class="char-counter" id="reason-count">0 / 500</div>
              <div class="field-error" id="err-reason">Please describe your reason for applying.</div>
            </div>

            <div class="field-divider"></div>

            <!-- Interview day -->
            <div class="field-group">
              <label class="field-label">10. Preferred interview day</label>
              <p class="field-hint">If you prefer a specific day, please select "Other" and specify.</p>
              <div class="radio-group">
                <?php
                $dayOptions = [
                  'weekdays'   => 'Weekdays',
                  'weekends'   => 'Weekends / Holidays',
                  'day_other'  => 'Other',
                ];
                $selectedDay = $_POST['interview_day'] ?? '';
                foreach ($dayOptions as $val => $label):
                ?>
                <label class="radio-option">
                  <input type="radio" name="interview_day" value="<?= $val ?>"
                         <?= $selectedDay === $val ? 'checked' : '' ?>
                         onchange="toggleOther(this,'day-other')">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($label) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="other-input <?= $selectedDay === 'day_other' ? 'visible' : '' ?>" id="day-other">
                <input class="text-input" type="text" name="interview_day_other"
                       placeholder="Please specify your preferred day..."
                       value="<?= htmlspecialchars($_POST['interview_day_other'] ?? '') ?>">
              </div>
            </div>

            <!-- Interview time -->
            <div class="field-group">
              <label class="field-label">11. Preferred interview time slot</label>
              <p class="field-hint">If you prefer a specific time, please select "Other" and specify.</p>
              <div class="radio-group">
                <?php
                $timeOptions = [
                  '9_12'      => '9:00 – 12:00',
                  '12_15'     => '12:00 – 15:00',
                  '15_18'     => '15:00 – 18:00',
                  'time_other'=> 'Other',
                ];
                $selectedTime = $_POST['interview_time'] ?? '';
                foreach ($timeOptions as $val => $label):
                ?>
                <label class="radio-option">
                  <input type="radio" name="interview_time" value="<?= $val ?>"
                         <?= $selectedTime === $val ? 'checked' : '' ?>
                         onchange="toggleOther(this,'time-other')">
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($label) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="other-input <?= $selectedTime === 'time_other' ? 'visible' : '' ?>" id="time-other">
                <input class="text-input" type="text" name="interview_time_other"
                       placeholder="Please specify your preferred time slot..."
                       value="<?= htmlspecialchars($_POST['interview_time_other'] ?? '') ?>">
              </div>
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
              <label class="field-label">Would you like to apply for this support program? <span class="req">*</span></label>
              <div class="radio-group">
                <?php
                $supportOptions = [
                  'yes'       => ['Yes, I would like to apply.', ''],
                  'undecided' => ['I am undecided and would like to discuss it further.', ''],
                  'no'        => ['No, I do not wish to apply.', ''],
                ];
                $selectedSupport = $_POST['support_program'] ?? '';
                foreach ($supportOptions as $val => [$main, $sub]):
                ?>
                <label class="radio-option">
                  <input type="radio" name="support_program" value="<?= $val ?>"
                         <?= $selectedSupport === $val ? 'checked' : '' ?>>
                  <div class="radio-dot"></div>
                  <span class="radio-text"><?= htmlspecialchars($main) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="field-error" id="err-support">Please select an option.</div>
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
      const reason = document.getElementById('reason');
      const reasonOk = reason.value.trim().length > 0;
      document.getElementById('err-reason').classList.toggle('visible', !reasonOk);
      reason.classList.toggle('error', !reasonOk);
      if (!reasonOk) ok = false;
    }
    if (step === 3) {
      const support = document.querySelector('input[name="support_program"]:checked');
      const supportOk = !!support;
      document.getElementById('err-support').classList.toggle('visible', !supportOk);
      if (!supportOk) ok = false;
    }
    return ok;
  }

  function changeStep(dir) {
    if (dir === 1 && !validateStep(currentStep)) return;
    currentStep = Math.max(1, Math.min(totalSteps, currentStep + dir));
    updateUI();
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

  // Init char counter
  const reasonEl = document.getElementById('reason');
  if (reasonEl) updateCharCount(reasonEl, 'reason-count', 500);

  // Inline form submit validation
  document.getElementById('app-form').addEventListener('submit', function(e) {
    if (!validateStep(3)) e.preventDefault();
  });

  // Init on load (handles PHP error repopulate)
  updateUI();
</script>
</body>
</html>
