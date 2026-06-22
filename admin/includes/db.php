<?php
function get_db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = dirname(__DIR__, 2) . '/db';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $pdo = new PDO('sqlite:' . $dir . '/shimane.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON");
    _init_schema($pdo);
    return $pdo;
}

function _init_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        email        TEXT UNIQUE NOT NULL,
        name         TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        role         TEXT NOT NULL DEFAULT 'viewer',
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login   DATETIME
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS form_drafts (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        token          TEXT UNIQUE NOT NULL,
        email          TEXT,
        name           TEXT,
        lang           TEXT DEFAULT 'en',
        step_reached   INTEGER DEFAULT 1,
        form_data      TEXT DEFAULT '{}',
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed      INTEGER DEFAULT 0,
        reminder_sent_at DATETIME,
        reminder_count INTEGER DEFAULT 0,
        ip_address     TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS form_submissions (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        draft_id         INTEGER,
        name             TEXT,
        email            TEXT,
        phone            TEXT,
        how_heard        TEXT,
        how_heard_other  TEXT,
        resume_url       TEXT,
        pc_skill         TEXT,
        ai_experience    TEXT,
        reason           TEXT,
        interview_day    TEXT,
        interview_day_other TEXT,
        interview_time   TEXT,
        interview_time_other TEXT,
        support_program  TEXT,
        lang             TEXT DEFAULT 'en',
        submitted_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address       TEXT,
        notes            TEXT,
        status           TEXT DEFAULT 'new'
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS analytics_events (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        event_type TEXT,
        page       TEXT,
        lang       TEXT,
        referrer   TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS site_content (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        content_key TEXT NOT NULL,
        lang        TEXT NOT NULL DEFAULT 'en',
        value       TEXT,
        updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(content_key, lang)
    )");
}

function get_content(string $key, string $lang, string $default = ''): string {
    try {
        $db  = get_db();
        $row = $db->prepare("SELECT value FROM site_content WHERE content_key=? AND lang=?")->execute([$key, $lang]) ? null : null;
        $st  = $db->prepare("SELECT value FROM site_content WHERE content_key=? AND lang=?");
        $st->execute([$key, $lang]);
        $row = $st->fetch();
        return $row ? $row['value'] : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}
