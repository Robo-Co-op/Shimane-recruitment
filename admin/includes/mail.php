<?php
function _admin_base_url(): string {
    if (isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $path   = defined('BASE_URL') ? BASE_URL : '';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $path;
    }
    return 'https://robocoop.org/robouni/shimane-ai';
}

function _admin_mail_html(string $to, string $subject, string $html_body): bool {
    $headers  = "From: Robo Co-op <no-reply@roboco-op.org>\r\n";
    $headers .= "Reply-To: info@roboco-op.org\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $html_body, $headers);
}

function _email_template(string $title, string $content, string $btn_text, string $btn_url): string {
    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#F0F7F6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F0F7F6;padding:40px 16px;">
  <tr><td align="center">

    <!-- Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.07);">

      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#3DBFAF,#2A9485);padding:32px 40px;">
          <table cellpadding="0" cellspacing="0">
            <tr>
              <td style="background:rgba(255,255,255,0.2);border-radius:10px;width:40px;height:40px;text-align:center;vertical-align:middle;">
                <span style="color:#ffffff;font-weight:900;font-size:14px;line-height:40px;">RC</span>
              </td>
              <td style="padding-left:12px;">
                <div style="color:#ffffff;font-weight:700;font-size:17px;line-height:1.2;">Robo Co-op</div>
                <div style="color:rgba(255,255,255,0.75);font-size:12px;">Shimane IB Recruitment</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:36px 40px;">
          ' . $content . '

          <!-- Button -->
          <table cellpadding="0" cellspacing="0" style="margin:28px 0;">
            <tr>
              <td style="background:linear-gradient(135deg,#3DBFAF,#2A9485);border-radius:10px;">
                <a href="' . htmlspecialchars($btn_url) . '" style="display:inline-block;padding:14px 32px;color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;letter-spacing:0.3px;">' . htmlspecialchars($btn_text) . ' &rarr;</a>
              </td>
            </tr>
          </table>

          <p style="font-size:13px;color:#8AABA6;line-height:1.6;margin:0;">
            If the button does not work, copy and paste this link into your browser:<br>
            <a href="' . htmlspecialchars($btn_url) . '" style="color:#3DBFAF;word-break:break-all;">' . htmlspecialchars($btn_url) . '</a>
          </p>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#F8FBFA;border-top:1px solid #E8F0EE;padding:20px 40px;">
          <p style="margin:0;font-size:12px;color:#A8C4BF;line-height:1.6;">
            &copy; ' . date('Y') . ' Robo Co-op &mdash; Shimane IB Recruitment Admin<br>
            If you did not expect this email, you can safely ignore it.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
}

function send_admin_invite(string $to_email, string $to_name, string $token): bool {
    $link    = _admin_base_url() . '/admin/accept-invite?token=' . urlencode($token);
    $first   = htmlspecialchars(explode(' ', trim($to_name))[0]);
    $content = '
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1E2D2B;">You\'ve been invited</h1>
      <p style="margin:0 0 20px;font-size:15px;color:#4A6560;line-height:1.6;">Hi ' . $first . ',</p>
      <p style="margin:0 0 16px;font-size:15px;color:#4A6560;line-height:1.6;">
        You have been invited to join the <strong>Shimane IB Recruitment</strong> admin panel as a team member.
      </p>
      <p style="margin:0;font-size:15px;color:#4A6560;line-height:1.6;">
        Click the button below to set your password and activate your account. This invitation link expires in <strong>7 days</strong>.
      </p>';
    $html = _email_template('You\'ve been invited — Shimane IB Admin', $content, 'Activate My Account', $link);
    return _admin_mail_html($to_email, 'You have been invited to Shimane IB Admin', $html);
}

function send_application_confirmation_en(string $to_email, string $to_name): bool {
    $first   = htmlspecialchars(explode(' ', trim($to_name))[0] ?: $to_name);
    $content = '
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1E2D2B;">Application Received ✓</h1>
      <p style="margin:0 0 20px;font-size:15px;color:#4A6560;line-height:1.6;">Hi ' . $first . ',</p>
      <p style="margin:0 0 16px;font-size:15px;color:#4A6560;line-height:1.6;">
        Thank you for applying to the <strong>Shimane Prefecture × Robo Co-op FY2026 Digital Talent Development Program</strong>.
        We have received your application and our team will review it carefully.
      </p>
      <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1E2D2B;">What happens next:</p>
      <ol style="margin:0 0 16px;padding-left:20px;font-size:14px;color:#4A6560;line-height:2.2;">
        <li>Our team reviews your application</li>
        <li>We will contact you within <strong>3 business days</strong> via email</li>
        <li>Online interview — full details will be in your invitation email</li>
      </ol>
      <p style="margin:0;font-size:14px;color:#4A6560;line-height:1.6;">
        If you have any questions, please contact us at
        <a href="mailto:info@roboco-op.org" style="color:#3DBFAF;">info@roboco-op.org</a>.
      </p>';
    $html = _email_template(
        'Application Received — Shimane IB',
        $content,
        'Contact Us',
        'mailto:info@roboco-op.org'
    );
    return _admin_mail_html($to_email, 'Application Received — Shimane IB / Robo Co-op', $html);
}

function send_application_confirmation_ja(string $to_email, string $to_name): bool {
    $name    = htmlspecialchars($to_name);
    $content = '
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1E2D2B;">応募を受け付けました ✓</h1>
      <p style="margin:0 0 20px;font-size:15px;color:#4A6560;line-height:1.6;">' . $name . ' 様</p>
      <p style="margin:0 0 16px;font-size:15px;color:#4A6560;line-height:1.6;">
        <strong>島根県 × Robo Co-op 令和8年度 デジタル人材育成研修</strong> へのご応募ありがとうございます。
        応募内容を受け付けました。担当者が内容を確認いたします。
      </p>
      <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1E2D2B;">今後の流れ：</p>
      <ol style="margin:0 0 16px;padding-left:20px;font-size:14px;color:#4A6560;line-height:2.2;">
        <li>担当者が応募内容を確認します</li>
        <li><strong>3営業日以内</strong>にメールにてご連絡します</li>
        <li>オンライン面接（詳細はご連絡メールに記載します）</li>
      </ol>
      <p style="margin:0;font-size:14px;color:#4A6560;line-height:1.6;">
        ご不明な点は <a href="mailto:info@roboco-op.org" style="color:#3DBFAF;">info@roboco-op.org</a> までお問い合わせください。
      </p>';
    $html = _email_template(
        '応募受付完了 — 島根IB',
        $content,
        'お問い合わせ',
        'mailto:info@roboco-op.org'
    );
    return _admin_mail_html($to_email, '応募受付完了のお知らせ — 島根IB / Robo Co-op', $html);
}

function send_staff_notification(string $applicant_name, string $applicant_email, string $lang): void {
    $staff   = ['midori.urashima@roboco-op.org', 'kazumi.hanaoka@roboco-op.org', 'eliyahe@roboco-op.org'];
    $name    = htmlspecialchars($applicant_name);
    $email   = htmlspecialchars($applicant_email);
    $langlab = strtolower($lang) === 'ja' ? '日本語' : 'English';
    $url     = _admin_base_url() . '/admin/submissions';
    $content = '
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1E2D2B;">新しい応募が届きました 📋</h1>
      <p style="margin:0 0 20px;font-size:15px;color:#4A6560;line-height:1.6;">島根IB / Robo Co-op への応募が1件届きました。</p>
      <table style="width:100%;border-collapse:collapse;margin-bottom:16px;font-size:14px;">
        <tr>
          <td style="padding:10px 14px;background:#F0F7F6;font-weight:700;color:#1E2D2B;width:28%;border-radius:6px 0 0 0;">氏名</td>
          <td style="padding:10px 14px;color:#1E2D2B;font-weight:600;">' . $name . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;font-weight:700;color:#1E2D2B;">メール</td>
          <td style="padding:10px 14px;color:#3DBFAF;">' . $email . '</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;background:#F0F7F6;font-weight:700;color:#1E2D2B;">言語</td>
          <td style="padding:10px 14px;background:#F0F7F6;color:#4A6560;">' . $langlab . '</td>
        </tr>
      </table>';
    $html = _email_template('新規応募通知 — 島根IB', $content, '応募内容を確認する →', $url);
    foreach ($staff as $addr) {
        _admin_mail_html($addr, '【新規応募】' . $applicant_name . ' さんより応募がありました', $html);
    }
}

function send_password_reset(string $to_email, string $to_name, string $token): bool {
    $link    = _admin_base_url() . '/admin/reset-password?token=' . urlencode($token);
    $first   = htmlspecialchars(explode(' ', trim($to_name))[0]);
    $content = '
      <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1E2D2B;">Reset your password</h1>
      <p style="margin:0 0 20px;font-size:15px;color:#4A6560;line-height:1.6;">Hi ' . $first . ',</p>
      <p style="margin:0 0 16px;font-size:15px;color:#4A6560;line-height:1.6;">
        We received a request to reset the password for your <strong>Shimane IB Admin</strong> account.
      </p>
      <p style="margin:0 0 16px;font-size:15px;color:#4A6560;line-height:1.6;">
        Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.
      </p>
      <p style="margin:0;font-size:13px;color:#8AABA6;">
        If you did not request a password reset, no action is needed — your account remains secure.
      </p>';
    $html = _email_template('Reset your password — Shimane IB Admin', $content, 'Reset My Password', $link);
    return _admin_mail_html($to_email, 'Reset your Shimane IB Admin password', $html);
}
