<?php
/**
 * Run once after deploying Q6 hint change to:
 *  1. Update the hint text in the DB directly
 *  2. Clear the file-based question cache
 *
 * Usage (via SSH or Hostinger terminal):
 *   php admin/bust-cache.php
 *
 * Safe to run multiple times.
 */
require_once __DIR__ . '/includes/db.php';

$db = get_db();

$updates = [
    ['slug' => 'ja-application', 'field' => 'resume_url',
     'hint' => 'Google Drive、Dropbox などにアップロードしたファイルの URL をご記入ください。'],
    ['slug' => 'en-application', 'field' => 'resume_url',
     'hint' => 'Share the URL of the file where you uploaded your resume (Google Drive, Dropbox, etc.).'],
];

foreach ($updates as $u) {
    $st = $db->prepare("
        UPDATE form_questions q
        SET hint = ?
        FROM forms f
        WHERE f.id = q.form_id
          AND f.slug = ?
          AND q.field_name = ?
    ");
    $st->execute([$u['hint'], $u['slug'], $u['field']]);
    echo "Updated {$u['slug']} / {$u['field']}: {$st->rowCount()} row(s)\n";
    bust_form_questions_cache($u['slug']);
    echo "Cache cleared for {$u['slug']}\n";
}

echo "Done.\n";
