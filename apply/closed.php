<?php
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'ja') ? 'ja' : 'en';
require_once __DIR__ . '/../includes/base.php';
require_once __DIR__ . '/../includes/app_settings.php';
$_is_ja   = $lang === 'ja';
$deadline = get_app_deadline();
$_deadline_fmt = $_is_ja
    ? date('Y年n月j日', strtotime($deadline))
    : date('j F Y',    strtotime($deadline));
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $_is_ja ? '応募期間が終了しました | Robo Co-op' : 'Applications Closed | Robo Co-op' ?></title>
  <?php include __DIR__ . '/../includes/styles.php'; ?>
  <style>
    .closed-wrap {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      text-align: center;
      background: linear-gradient(160deg, #E8F8F6 0%, #FDFAF6 100%);
    }
    .closed-icon {
      width: 88px;
      height: 88px;
      background: linear-gradient(135deg, #3DBFAF, #2A9485);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      margin: 0 auto 28px;
      box-shadow: 0 8px 32px rgba(61,191,175,.25);
    }
    .closed-card {
      background: #fff;
      border-radius: 24px;
      padding: 48px 40px;
      max-width: 520px;
      width: 100%;
      box-shadow: 0 16px 56px rgba(0,0,0,.08);
    }
    .closed-badge {
      display: inline-block;
      background: #FEE2E2;
      color: #991B1B;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
      padding: 4px 12px;
      border-radius: 20px;
      margin-bottom: 16px;
    }
    .closed-title {
      font-size: 26px;
      font-weight: 900;
      color: var(--warm-dark);
      line-height: 1.3;
      margin-bottom: 14px;
    }
    .closed-desc {
      font-size: 15px;
      color: var(--warm-mid);
      line-height: 1.8;
      margin-bottom: 32px;
    }
    .closed-deadline {
      background: var(--mint-pale);
      border-radius: 12px;
      padding: 14px 20px;
      font-size: 13px;
      color: var(--warm-mid);
      margin-bottom: 32px;
    }
    .closed-deadline strong { color: var(--warm-dark); }
    .btn-home {
      display: inline-block;
      background: linear-gradient(135deg, var(--mint-dark), var(--mint-darker));
      color: #fff;
      font-weight: 800;
      font-size: 15px;
      padding: 14px 32px;
      border-radius: 40px;
      text-decoration: none;
      box-shadow: 0 4px 18px rgba(61,191,175,.3);
    }
    .btn-home:hover { opacity: .9; }
    @media (max-width: 480px) {
      .closed-card { padding: 36px 24px; }
      .closed-title { font-size: 22px; }
    }
  </style>
</head>
<body>

<!-- Header -->
<header>
  <a class="logo" href="<?= $_is_ja ? '/' : '/en' ?>">
    <img src="/logo.png" alt="Robo Co-op" class="logo-img">
    <span class="logo-text">Robo Co-op</span>
  </a>
  <div class="header-right">
    <?php if ($_is_ja): ?>
      <a href="/apply/closed?lang=en" class="lang-switch">EN</a>
    <?php else: ?>
      <a href="/apply/closed?lang=ja" class="lang-switch">日本語</a>
    <?php endif; ?>
  </div>
</header>

<!-- Closed message -->
<div class="closed-wrap">
  <div class="closed-card">
    <div class="closed-icon">🔒</div>

    <div class="closed-badge">
      <?= $_is_ja ? '応募受付終了' : 'Applications Closed' ?>
    </div>

    <h1 class="closed-title">
      <?= $_is_ja
        ? "応募期間が<br>終了しました"
        : "The application period<br>has ended." ?>
    </h1>

    <p class="closed-desc">
      <?php if ($_is_ja): ?>
        このたびは Robo Co-op の研修プログラムにご興味をお持ちいただき、<br>
        ありがとうございます。<br>
        令和8年度の受講生募集は終了いたしました。
      <?php else: ?>
        Thank you for your interest in the Robo Co-op training program.<br>
        Applications for the FY2026 cohort are now closed.
      <?php endif; ?>
    </p>

    <div class="closed-deadline">
      <?= $_is_ja
        ? '<strong>募集締め切り：</strong>' . $_deadline_fmt
        : '<strong>Application deadline was:</strong> ' . $_deadline_fmt ?>
    </div>

    <p class="closed-desc" style="margin-bottom:32px;font-size:13px">
      <?php if ($_is_ja): ?>
        次回の募集に関する情報は、Robo Co-op の公式サイトやSNSでお知らせします。<br>
        ご不明な点は <a href="mailto:info@roboco-op.org" style="color:var(--mint-dark)">info@roboco-op.org</a> までお問い合わせください。
      <?php else: ?>
        Information about future cohorts will be announced on the Robo Co-op website and social media.<br>
        For enquiries, contact <a href="mailto:info@roboco-op.org" style="color:var(--mint-dark)">info@roboco-op.org</a>.
      <?php endif; ?>
    </p>

    <a href="<?= $_is_ja ? '/' : '/en' ?>" class="btn-home">
      <?= $_is_ja ? '← ホームに戻る' : '← Back to Home' ?>
    </a>
  </div>
</div>

<!-- Footer -->
<footer>
  <?php if ($_is_ja): ?>
  <div class="ft-name">一般社団法人 Robo Co-op</div>
  <p style="margin-bottom:8px;">令和8年度 島根県デジタル人材育成研修 企画・運営</p>
  <p>HP: <a href="https://roboco-op.org" target="_blank">roboco-op.org</a>　／　お問い合わせ: <a href="mailto:info@roboco-op.org">info@roboco-op.org</a></p>
  <?php else: ?>
  <div class="ft-name">Robo Co-op (General Incorporated Association)</div>
  <p style="margin-bottom:8px;">FY2026 Shimane Prefecture Digital Talent Development Program — Planning &amp; Operations</p>
  <p>Website: <a href="https://roboco-op.org" target="_blank">roboco-op.org</a> &nbsp;/&nbsp; Contact: <a href="mailto:info@roboco-op.org">info@roboco-op.org</a></p>
  <?php endif; ?>
  <p style="margin-top:16px; font-size:11px;">© 2026 Robo Co-op. All rights reserved.</p>
</footer>

</body>
</html>
