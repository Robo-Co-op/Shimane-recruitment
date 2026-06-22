<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/includes/db.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['type'])) { http_response_code(400); echo '{}'; exit; }

$db = get_db();
$db->prepare("INSERT INTO analytics_events (session_id,event_type,page,lang,referrer,user_agent) VALUES (?,?,?,?,?,?)")
   ->execute([
       $data['sid'] ?? '',
       $data['type'] ?? 'pageview',
       $data['page'] ?? '',
       $data['lang'] ?? 'en',
       $data['ref']  ?? '',
       substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
   ]);

echo json_encode(['ok' => true]);
