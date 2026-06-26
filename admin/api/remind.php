<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_auth('editor');

$db = get_db();

// Find drafts: incomplete, older than 24h, email present, reminder count < 3
$st = $db->prepare("
    SELECT * FROM form_drafts
    WHERE completed=0
      AND email IS NOT NULL AND email != ''
      AND created_at < NOW() - INTERVAL '24 hours'
      AND (reminder_count < 3 OR reminder_count IS NULL)
      AND (reminder_sent_at IS NULL OR reminder_sent_at < NOW() - INTERVAL '72 hours')
    ORDER BY created_at DESC
    LIMIT 50
");
$st->execute();
$drafts = $st->fetchAll();

$sent = 0;
$failed = 0;

foreach ($drafts as $d) {
    $lang     = $d['lang'] ?? 'en';
    $name     = $d['name'] ?: ($lang === 'ja' ? '応募者' : 'Applicant');
    $email    = $d['email'];
    $token    = $d['token'];
    $resume_url = ($lang === 'ja')
        ? "https://shimane-ib.roboco-op.org/apply/ja?token={$token}"
        : "https://shimane-ib.roboco-op.org/apply?token={$token}";

    if ($lang === 'ja') {
        $subject = '【島根IB】応募フォームのご記入をお願いします';
        $body    = "
{$name} 様

島根県 × Robo Co-op デジタル人材育成研修にご興味をいただきありがとうございます。

応募フォームのご記入が途中になっているようです。
下のリンクから途中から再開できますのでぜひご記入ください。

▶ フォームを続ける: {$resume_url}

ご不明な点がございましたら、お気軽にお問い合わせください。
info@roboco-op.org

--
一般社団法人 Robo Co-op
島根県デジタル人材育成研修 事務局
        ";
    } else {
        $subject = 'Complete your Shimane IB Application';
        $body    = "
Hi {$name},

Thank you for your interest in the Shimane Prefecture × Robo Co-op Digital Talent Development Program!

It looks like you started an application but haven't finished yet. You can pick up right where you left off using the link below:

▶ Resume your application: {$resume_url}

If you have any questions, feel free to reach out at info@roboco-op.org

Best,
The Robo Co-op Team
Shimane IB Program
        ";
    }

    $headers  = "From: Robo Co-op <no-reply@roboco-op.org>\r\n";
    $headers .= "Reply-To: info@roboco-op.org\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";

    $ok = mail($email, $subject, trim($body), $headers);
    if ($ok) {
        $db->prepare("UPDATE form_drafts SET reminder_sent_at=CURRENT_TIMESTAMP, reminder_count=reminder_count+1 WHERE id=?")
           ->execute([$d['id']]);
        $sent++;
    } else {
        $failed++;
    }
}

// Redirect back with result message
$msg = urlencode("Reminders sent: {$sent}" . ($failed ? ", failed: {$failed}" : ".") . " ({$sent} of " . count($drafts) . " eligible drafts.)");
header("Location: " . base_url("/admin/submissions?tab=drafts&msg={$msg}"));
