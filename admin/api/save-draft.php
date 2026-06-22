<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/db.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

$db    = get_db();
$token = $data['token'] ?? null;
$email = trim($data['email'] ?? '');
$name  = trim($data['name'] ?? '');
$lang  = in_array($data['lang'] ?? '', ['en','ja']) ? $data['lang'] : 'en';
$step  = max(1, min(3, (int)($data['step'] ?? 1)));
$ip    = $_SERVER['REMOTE_ADDR'] ?? '';

// Sanitise stored form_data — only allow known fields
$allowed = ['name','email','phone','how_heard','how_heard_other','resume_url','pc_skill',
            'ai_experience','reason','interview_day','interview_day_other',
            'interview_time','interview_time_other','support_program'];
$form_data = [];
foreach ($allowed as $k) {
    if (isset($data[$k])) $form_data[$k] = (string)$data[$k];
}

if ($token) {
    // Update existing draft
    $st = $db->prepare("SELECT id FROM form_drafts WHERE token=?");
    $st->execute([$token]);
    $existing = $st->fetchColumn();
    if ($existing) {
        $db->prepare("UPDATE form_drafts SET email=?,name=?,step_reached=?,form_data=?,updated_at=CURRENT_TIMESTAMP WHERE token=?")
           ->execute([$email ?: null, $name ?: null, $step, json_encode($form_data), $token]);
        echo json_encode(['ok'=>true,'token'=>$token]);
        exit;
    }
}

// Create new draft
$token = bin2hex(random_bytes(16));
$db->prepare("INSERT INTO form_drafts (token,email,name,lang,step_reached,form_data,ip_address) VALUES (?,?,?,?,?,?,?)")
   ->execute([$token, $email ?: null, $name ?: null, $lang, $step, json_encode($form_data), $ip]);

echo json_encode(['ok'=>true,'token'=>$token]);
