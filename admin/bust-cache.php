<?php
/**
 * Called by deploy hook to clear form-question file caches.
 * Safe to run multiple times; missing files are ignored.
 */
require_once __DIR__ . '/includes/db.php';
bust_form_questions_cache('ja-application');
bust_form_questions_cache('en-application');
echo "Cache cleared.\n";
