<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_auth('viewer');
$db     = get_db();
$format = $_GET['format'] ?? 'csv';

$subs = $db->query("SELECT id,name,email,phone,how_heard,how_heard_other,resume_url,pc_skill,ai_experience,reason,interview_day,interview_day_other,interview_time,interview_time_other,support_program,lang,status,notes,submitted_at,ip_address FROM form_submissions ORDER BY submitted_at DESC")->fetchAll();

$headers = ['ID','Name','Email','Phone','How Heard','How Heard (Other)','Resume URL','PC Skill','AI Experience','Reason','Interview Day','Interview Day (Other)','Interview Time','Interview Time (Other)','Support Program','Language','Status','Admin Notes','Submitted At','IP Address'];

$pc_labels = ['pc_1'=>'Little/no experience','pc_2'=>'Basic tasks','pc_3'=>'Word & Excel (basic)','pc_4'=>'Regular work use','pc_5'=>'Specialised'];
$ai_labels = ['ai_1'=>'Never used','ai_2'=>'Tried, not familiar','ai_3'=>'Simple tasks','ai_4'=>'Work/learning use','ai_5'=>'Effective use'];

function fmt_sub(array $s, array $pc, array $ai): array {
    return [
        $s['id'], $s['name'], $s['email'], $s['phone'],
        $s['how_heard'], $s['how_heard_other'],
        $s['resume_url'],
        $pc[$s['pc_skill']] ?? $s['pc_skill'],
        $ai[$s['ai_experience']] ?? $s['ai_experience'],
        $s['reason'],
        $s['interview_day'], $s['interview_day_other'],
        $s['interview_time'], $s['interview_time_other'],
        $s['support_program'], strtoupper($s['lang']),
        $s['status'], $s['notes'],
        $s['submitted_at'], $s['ip_address'],
    ];
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="shimane-applications-' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output', 'w');
    fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));  // UTF-8 BOM for Excel
    fputcsv($f, $headers);
    foreach ($subs as $s) fputcsv($f, fmt_sub($s, $pc_labels, $ai_labels));
    fclose($f);
    exit;
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="shimane-applications-' . date('Y-m-d') . '.xls"');
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr style="background:#3DBFAF;color:#fff">';
    foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
    echo '</tr>';
    foreach ($subs as $i => $s) {
        $bg = $i % 2 ? '#F0F5F4' : '#fff';
        echo "<tr style=\"background:$bg\">";
        foreach (fmt_sub($s, $pc_labels, $ai_labels) as $v) {
            echo '<td style="font-family:Arial;font-size:12px">' . htmlspecialchars((string)$v) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

if ($format === 'pdf') {
    // Print-friendly HTML for browser PDF export
    header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Applications Export — <?= date('Y-m-d') ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;font-size:11px;padding:20px}
h1{font-size:16px;margin-bottom:4px}
.meta{font-size:11px;color:#666;margin-bottom:16px}
table{width:100%;border-collapse:collapse;page-break-inside:auto}
th{background:#1B2E2B;color:#fff;padding:6px 8px;text-align:left;font-size:10px}
td{padding:5px 8px;border-bottom:1px solid #eee;vertical-align:top}
tr:nth-child(even){background:#f7faf9}
.reason{max-width:200px;word-wrap:break-word}
@media print{@page{size:landscape;margin:15mm}.no-print{display:none}}
</style>
</head><body>
<div class="no-print" style="margin-bottom:20px">
  <button onclick="window.print()" style="padding:8px 16px;background:#3DBFAF;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨 Print / Save as PDF</button>
  <a href="/admin/submissions" style="margin-left:12px;font-size:13px">← Back to Admin</a>
</div>
<h1>Shimane IB — Application Export</h1>
<div class="meta">Generated: <?= date('F j, Y — g:i a') ?> &nbsp;·&nbsp; Total: <?= count($subs) ?> submissions</div>
<table>
<tr><?php foreach (['#','Name','Email','Phone','Lang','Status','Support','Reason','Submitted'] as $h) echo "<th>$h</th>"; ?></tr>
<?php foreach ($subs as $i => $s): ?>
<tr>
  <td><?= $s['id'] ?></td>
  <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
  <td><?= htmlspecialchars($s['email']) ?></td>
  <td><?= htmlspecialchars($s['phone']) ?></td>
  <td><?= strtoupper($s['lang']) ?></td>
  <td><?= htmlspecialchars($s['status']) ?></td>
  <td><?= htmlspecialchars($s['support_program']) ?></td>
  <td class="reason"><?= htmlspecialchars(mb_substr($s['reason'], 0, 120)) ?><?= strlen($s['reason']) > 120 ? '…' : '' ?></td>
  <td><?= date('M j, Y', strtotime($s['submitted_at'])) ?></td>
</tr>
<?php endforeach; ?>
</table>
<script>window.print();</script>
</body></html>
<?php
    exit;
}

// Fallback: Word (HTML with .doc)
header('Content-Type: application/msword; charset=UTF-8');
header('Content-Disposition: attachment; filename="shimane-applications-' . date('Y-m-d') . '.doc"');
echo '<html><head><meta charset="UTF-8"></head><body>';
echo '<h1 style="font-family:Arial">Shimane IB Applications — ' . date('F j, Y') . '</h1>';
echo '<p style="font-family:Arial;font-size:12px">Total: ' . count($subs) . ' submissions</p><br>';
echo '<table border="1" cellpadding="4" style="font-family:Arial;font-size:11px;border-collapse:collapse">';
echo '<tr style="background:#1B2E2B;color:#fff"><th>Name</th><th>Email</th><th>Phone</th><th>Lang</th><th>Status</th><th>Support</th><th>Reason</th><th>Date</th></tr>';
foreach ($subs as $s) {
    echo '<tr>';
    foreach ([$s['name'],$s['email'],$s['phone'],strtoupper($s['lang']),$s['status'],$s['support_program'],$s['reason'],date('M j Y',strtotime($s['submitted_at']))] as $v) {
        echo '<td>' . htmlspecialchars((string)$v) . '</td>';
    }
    echo '</tr>';
}
echo '</table></body></html>';
