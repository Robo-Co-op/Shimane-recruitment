<?php
function get_db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    // ── Credentials: env vars (production/CI) → config.php (local dev) ────────
    $host   = getenv('DB_HOST')     ?: null;
    $port   = getenv('DB_PORT')     ?: null;
    $dbname = getenv('DB_NAME')     ?: null;
    $user   = getenv('DB_USER')     ?: null;
    $pass   = getenv('DB_PASSWORD') ?: null;
    $schema = getenv('DB_SCHEMA')   ?: null;

    if (!$host) {
        $cfg = dirname(__DIR__, 2) . '/config.php';
        if (file_exists($cfg)) require_once $cfg;
        $host   = defined('DB_HOST')     ? DB_HOST     : null;
        $port   = defined('DB_PORT')     ? DB_PORT     : '5432';
        $dbname = defined('DB_NAME')     ? DB_NAME     : 'postgres';
        $user   = defined('DB_USER')     ? DB_USER     : 'postgres';
        $pass   = defined('DB_PASSWORD') ? DB_PASSWORD : '';
        $schema = defined('DB_SCHEMA')   ? DB_SCHEMA   : 'shimane';
    }

    if (!$host) {
        http_response_code(500);
        die('<h2>Database not configured.</h2>Copy <code>config.php.example</code> to <code>config.php</code> and fill in your Supabase credentials.');
    }

    $port   = $port   ?: '5432';
    $schema = $schema ?: 'shimane';

    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbname}",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => true,
        ]
    );

    // Always set search_path (needed even on a reused persistent connection)
    $pdo->exec("SET search_path TO {$schema}");

    // Run migrations only once — skip on every subsequent request via flag file.
    // Ensure the db/ directory exists (it is excluded from FTP deploy).
    $flag    = dirname(__DIR__, 2) . '/db/.schema_v3';
    $flag_dir = dirname($flag);
    if (!is_dir($flag_dir)) @mkdir($flag_dir, 0755, true);
    if (!file_exists($flag)) {
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");
        _init_schema($pdo);
        _ensure_admin_seeded($pdo);
        _ensure_forms_seeded($pdo);
        _migrate_form_questions($pdo);
        @file_put_contents($flag, date('c'));
    }

    return $pdo;
}

function _init_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id                BIGSERIAL PRIMARY KEY,
        email             TEXT UNIQUE NOT NULL,
        name              TEXT NOT NULL,
        password_hash     TEXT NOT NULL DEFAULT '',
        role              TEXT NOT NULL DEFAULT 'viewer',
        status            TEXT NOT NULL DEFAULT 'active',
        invite_token      TEXT,
        invite_expires_at TIMESTAMPTZ,
        reset_token       TEXT,
        reset_expires_at  TIMESTAMPTZ,
        created_at        TIMESTAMPTZ DEFAULT NOW(),
        last_login        TIMESTAMPTZ
    )");
    // Safe migrations for existing tables
    foreach ([
        "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS status            TEXT NOT NULL DEFAULT 'active'",
        "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS invite_token      TEXT",
        "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS invite_expires_at TIMESTAMPTZ",
        "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS reset_token       TEXT",
        "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS reset_expires_at  TIMESTAMPTZ",
    ] as $sql) { $db->exec($sql); }

    $db->exec("CREATE TABLE IF NOT EXISTS form_drafts (
        id               BIGSERIAL PRIMARY KEY,
        token            TEXT UNIQUE NOT NULL,
        email            TEXT,
        name             TEXT,
        lang             TEXT DEFAULT 'en',
        step_reached     INTEGER DEFAULT 1,
        form_data        TEXT DEFAULT '{}',
        created_at       TIMESTAMPTZ DEFAULT NOW(),
        updated_at       TIMESTAMPTZ DEFAULT NOW(),
        completed        INTEGER DEFAULT 0,
        reminder_sent_at TIMESTAMPTZ,
        reminder_count   INTEGER DEFAULT 0,
        ip_address       TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS form_submissions (
        id                   BIGSERIAL PRIMARY KEY,
        draft_id             BIGINT,
        name                 TEXT,
        email                TEXT,
        phone                TEXT,
        how_heard            TEXT,
        how_heard_other      TEXT,
        resume_url           TEXT,
        pc_skill             TEXT,
        ai_experience        TEXT,
        reason               TEXT,
        interview_day        TEXT,
        interview_day_other  TEXT,
        interview_time       TEXT,
        interview_time_other TEXT,
        support_program      TEXT,
        support_situation    TEXT,
        other_questions      TEXT,
        confirm_submit       TEXT,
        lang                 TEXT DEFAULT 'en',
        submitted_at         TIMESTAMPTZ DEFAULT NOW(),
        ip_address           TEXT,
        notes                TEXT,
        status               TEXT DEFAULT 'new'
    )");
    foreach ([
        "ALTER TABLE form_submissions ADD COLUMN IF NOT EXISTS support_situation TEXT",
        "ALTER TABLE form_submissions ADD COLUMN IF NOT EXISTS other_questions    TEXT",
        "ALTER TABLE form_submissions ADD COLUMN IF NOT EXISTS confirm_submit     TEXT",
        "ALTER TABLE form_submissions ADD COLUMN IF NOT EXISTS is_duplicate       BOOLEAN DEFAULT FALSE",
    ] as $sql) { $db->exec($sql); }

    $db->exec("CREATE TABLE IF NOT EXISTS analytics_events (
        id         BIGSERIAL PRIMARY KEY,
        session_id TEXT,
        event_type TEXT,
        page       TEXT,
        lang       TEXT,
        referrer   TEXT,
        user_agent TEXT,
        created_at TIMESTAMPTZ DEFAULT NOW()
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_analytics_created_at
        ON analytics_events (created_at DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_analytics_event_type
        ON analytics_events (event_type, created_at DESC)");

    $db->exec("CREATE TABLE IF NOT EXISTS site_content (
        id          BIGSERIAL PRIMARY KEY,
        content_key TEXT NOT NULL,
        lang        TEXT NOT NULL DEFAULT 'en',
        value       TEXT,
        updated_at  TIMESTAMPTZ DEFAULT NOW(),
        UNIQUE (content_key, lang)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS forms (
        id          BIGSERIAL PRIMARY KEY,
        slug        TEXT UNIQUE NOT NULL,
        lang        TEXT NOT NULL DEFAULT 'en',
        title       TEXT NOT NULL,
        description TEXT DEFAULT '',
        status      TEXT DEFAULT 'active',
        created_at  TIMESTAMPTZ DEFAULT NOW(),
        updated_at  TIMESTAMPTZ DEFAULT NOW()
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS form_questions (
        id           BIGSERIAL PRIMARY KEY,
        form_id      BIGINT NOT NULL REFERENCES forms(id) ON DELETE CASCADE,
        sort_order   INTEGER DEFAULT 0,
        step         INTEGER DEFAULT 1,
        field_name   TEXT NOT NULL,
        field_type   TEXT NOT NULL DEFAULT 'text',
        label        TEXT NOT NULL DEFAULT '',
        hint         TEXT DEFAULT '',
        placeholder  TEXT DEFAULT '',
        required     INTEGER DEFAULT 0,
        options_json TEXT DEFAULT '[]',
        max_length   INTEGER,
        active       INTEGER DEFAULT 1,
        created_at   TIMESTAMPTZ DEFAULT NOW()
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_form_questions_form_id
        ON form_questions (form_id, sort_order)");
}

function _iq(PDO $db, int $fid, int $step, int $sort, string $name, string $type,
             string $label, string $hint, string $ph, int $req, array $opts, ?int $mx): void {
    $db->prepare("INSERT INTO form_questions
        (form_id,step,sort_order,field_name,field_type,label,hint,placeholder,required,options_json,max_length)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$fid,$step,$sort,$name,$type,$label,$hint,$ph,$req,
                  json_encode($opts, JSON_UNESCAPED_UNICODE),$mx]);
}

function _ensure_admin_seeded(PDO $db): void {
    $count = (int)$db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($count > 0) return;
    $db->prepare("INSERT INTO admin_users (email,name,password_hash,role,status) VALUES (?,?,?,?,?)")
       ->execute(['admin@roboco-op.org', 'Admin', password_hash('Admin1234!', PASSWORD_DEFAULT), 'admin', 'active']);
}

function _ensure_forms_seeded(PDO $db): void {
    $count = (int)$db->query("SELECT COUNT(*) FROM forms")->fetchColumn();
    if ($count > 0) return;

    // ── English form ─────────────────────────────────────────────────────────
    $db->exec("INSERT INTO forms (slug,lang,title,description) VALUES
        ('en-application','en','English Application Form','FY2026 Digital Talent Development Program — Shimane IB')");
    $en = (int)$db->lastInsertId('forms_id_seq');

    _iq($db,$en,1,10,'name','text','1. Name','','Enter your full name',1,[],null);
    _iq($db,$en,1,20,'email','email','2. Email address','','your@email.com',1,[],null);
    _iq($db,$en,1,30,'email_confirm','email','3. Confirmation email address',
        'Re-enter your email address to confirm it.','your@email.com',1,[],null);
    _iq($db,$en,1,40,'phone','tel','4. Phone number',
        'If we are unable to contact you via email, we may reach out by phone.',
        'e.g. 080-1234-5678',1,[],null);
    _iq($db,$en,1,50,'how_heard','radio','5. How did you hear about this training?','','',0,[
        ['value'=>'municipality','label'=>'Information from a local municipality or support organization','sub'=>''],
        ['value'=>'social_media','label'=>'Social media (Facebook, X/Twitter, etc.)','sub'=>''],
        ['value'=>'recommendation','label'=>'Recommendation from family or friends','sub'=>''],
        ['value'=>'robocoop_web','label'=>"Robo Co-op's website",'sub'=>''],
        ['value'=>'other','label'=>'Other','sub'=>''],
    ],null);

    _iq($db,$en,2,60,'resume_url','url','6. Resume / CV — URL',
        'Share the URL of the file where you uploaded your resume (Google Drive, Dropbox, etc.).',
        'https://drive.google.com/...',0,[],null);
    _iq($db,$en,2,70,'pc_skill','radio','7. PC skill',
        'Select the option that best describes your computer skills.','',0,[
        ['value'=>'pc_1','label'=>'I have little to no experience using a computer.','sub'=>''],
        ['value'=>'pc_2','label'=>'I can perform basic computer tasks.','sub'=>'Typing, browsing the internet, sending/receiving emails.'],
        ['value'=>'pc_3','label'=>'I can use Word and Excel.','sub'=>'Create simple documents, tables, and data entries.'],
        ['value'=>'pc_4','label'=>'I use a computer regularly at work.','sub'=>'Can use Excel functions and organize data.'],
        ['value'=>'pc_5','label'=>'I can perform specialized tasks.','sub'=>'Programming, web development, and data analysis.'],
    ],null);
    _iq($db,$en,2,80,'ai_experience','radio','8. AI Tool Usage and Experience',
        'Please select the option that best describes your experience using AI tools such as ChatGPT.','',0,[
        ['value'=>'ai_1','label'=>'I have never used AI tools.','sub'=>''],
        ['value'=>'ai_2','label'=>'I have tried AI tools, but I am still not familiar with how to use them effectively.','sub'=>''],
        ['value'=>'ai_3','label'=>'I have used AI tools for simple tasks.','sub'=>'Writing, research, and summarization.'],
        ['value'=>'ai_4','label'=>'I use AI tools for work or learning.','sub'=>'Providing instructions tailored to my needs.'],
        ['value'=>'ai_5','label'=>'I can effectively use AI tools to create documents and improve workflows.','sub'=>'Reviewing and refining AI outputs to support other tasks.'],
    ],null);
    _iq($db,$en,2,90,'reason','textarea','9. Reason for applying',
        'Please describe your motivation for applying (around 500 characters).',
        'Describe your motivation for applying...',1,[],600);
    _iq($db,$en,2,100,'interview_day','radio','10. Preferred interview day',
        'If you prefer a specific day, please select "Other" and specify.','',0,[
        ['value'=>'weekdays','label'=>'Weekdays','sub'=>''],
        ['value'=>'weekends','label'=>'Weekends / Holidays','sub'=>''],
        ['value'=>'day_other','label'=>'Other','sub'=>''],
    ],null);
    _iq($db,$en,2,110,'interview_time','radio','11. Preferred interview time slot',
        'If you prefer a specific time, please select "Other" and specify.','',0,[
        ['value'=>'9_12','label'=>'9:00 – 12:00','sub'=>''],
        ['value'=>'12_15','label'=>'12:00 – 15:00','sub'=>''],
        ['value'=>'15_18','label'=>'15:00 – 18:00','sub'=>''],
        ['value'=>'time_other','label'=>'Other','sub'=>''],
    ],null);
    _iq($db,$en,3,120,'support_program','radio',
        'Would you like to apply for this support program?','','',1,[
        ['value'=>'yes','label'=>'Yes, I would like to apply.','sub'=>''],
        ['value'=>'undecided','label'=>'I am undecided and would like to discuss it further.','sub'=>''],
        ['value'=>'no','label'=>'No, I do not wish to apply.','sub'=>''],
    ],null);
    _iq($db,$en,3,130,'support_situation','textarea',
        '13. Current Situation and Reason for Requesting the Support Program',
        'Please describe your current living, employment, and family situation in as much detail as you feel comfortable sharing. In particular, please help us understand why you are requesting support by explaining your current employment status, financial concerns, family responsibilities such as childcare or caregiving, and any challenges you are facing in pursuing your studies or finding employment.',
        'Please describe your current situation...',1,[],1000);
    _iq($db,$en,3,140,'other_questions','textarea',
        '14. If you have any questions, concerns, or topics you would like to discuss in advance, please feel free to enter them below.',
        '','Enter any questions or comments (optional)...',0,[],null);
    _iq($db,$en,3,150,'confirm_submit','radio',
        '15. Would you like to submit your application with the information provided above?',
        'Please review your information carefully before submitting, as changes cannot be made after submission.',
        '',1,[['value'=>'yes','label'=>'Yes','sub'=>'']],null);

    // ── Japanese form ─────────────────────────────────────────────────────────
    $db->exec("INSERT INTO forms (slug,lang,title,description) VALUES
        ('ja-application','ja','日本語応募フォーム','令和8年度 デジタル人材育成研修 — 島根IB')");
    $ja = (int)$db->lastInsertId('forms_id_seq');

    _iq($db,$ja,1,10,'name','text','1. 氏名','','例：山田 太郎',1,[],null);
    _iq($db,$ja,1,20,'email','email','2. メールアドレス','','your@email.com',1,[],null);
    _iq($db,$ja,1,30,'email_confirm','email','3. メールアドレス（確認）',
        '確認のため、もう一度メールアドレスを入力してください。','your@email.com',1,[],null);
    _iq($db,$ja,1,40,'phone','tel','4. 電話番号',
        'メールでご連絡できない場合に、電話でご連絡することがあります。',
        '例：080-1234-5678',1,[],null);
    _iq($db,$ja,1,50,'how_heard','radio','5. この研修をどこで知りましたか？','','',0,[
        ['value'=>'municipality','label'=>'市区町村や支援機関からの情報','sub'=>''],
        ['value'=>'social_media','label'=>'SNS（Facebook、X/Twitter など）','sub'=>''],
        ['value'=>'recommendation','label'=>'家族・知人からの紹介','sub'=>''],
        ['value'=>'robocoop_web','label'=>'Robo Co-op のウェブサイト','sub'=>''],
        ['value'=>'other','label'=>'その他','sub'=>''],
    ],null);
    _iq($db,$ja,2,60,'resume_url','url','6. 履歴書・職務経歴書 URL',
        'Google Drive、Dropbox などにアップロードしたファイルの URL をご記入ください。',
        'https://drive.google.com/...',0,[],null);
    _iq($db,$ja,2,70,'pc_skill','radio','7. PC スキル',
        'ご自身のパソコンスキルに最も近いものを選択してください。','',0,[
        ['value'=>'pc_1','label'=>'パソコンをほとんど使ったことがない。','sub'=>''],
        ['value'=>'pc_2','label'=>'基本的な操作ができる。','sub'=>'文字入力、インターネット閲覧、メールの送受信など。'],
        ['value'=>'pc_3','label'=>'Word・Excel が使える。','sub'=>'簡単な文書作成、表の作成、データ入力ができる。'],
        ['value'=>'pc_4','label'=>'仕事でパソコンを日常的に使っている。','sub'=>'Excel 関数を使ったデータ整理などができる。'],
        ['value'=>'pc_5','label'=>'専門的な作業ができる。','sub'=>'プログラミング、Web 開発、データ分析など。'],
    ],null);
    _iq($db,$ja,2,80,'ai_experience','radio','8. AI ツールの利用経験',
        'ChatGPT などの AI ツールの利用経験として、最も近いものを選択してください。','',0,[
        ['value'=>'ai_1','label'=>'AI ツールを使ったことがない。','sub'=>''],
        ['value'=>'ai_2','label'=>'試したことはあるが、使いこなせていない。','sub'=>''],
        ['value'=>'ai_3','label'=>'簡単な作業に AI ツールを使ったことがある。','sub'=>'文章作成、調べもの、要約など。'],
        ['value'=>'ai_4','label'=>'仕事や学習に AI ツールを活用している。','sub'=>'目的に合わせた指示を工夫して使っている。'],
        ['value'=>'ai_5','label'=>'AI ツールを活用して資料作成や業務改善ができる。','sub'=>'AI の出力を確認・修正し、他の作業にも役立てられる。'],
    ],null);
    _iq($db,$ja,2,90,'reason','textarea','9. 応募動機',
        '応募の理由・動機をご記入ください（500 文字程度）。',
        '応募の動機や理由をご記入ください...',1,[],600);
    _iq($db,$ja,2,100,'interview_day','radio','10. 面接希望日',
        '特定の日程をご希望の場合は「その他」を選択してご記入ください。','',0,[
        ['value'=>'weekdays','label'=>'平日','sub'=>''],
        ['value'=>'weekends','label'=>'土日・祝日','sub'=>''],
        ['value'=>'day_other','label'=>'その他','sub'=>''],
    ],null);
    _iq($db,$ja,2,110,'interview_time','radio','11. 面接希望時間帯',
        '特定の時間帯をご希望の場合は「その他」を選択してご記入ください。','',0,[
        ['value'=>'9_12','label'=>'9:00 〜 12:00','sub'=>''],
        ['value'=>'12_15','label'=>'12:00 〜 15:00','sub'=>''],
        ['value'=>'15_18','label'=>'15:00 〜 18:00','sub'=>''],
        ['value'=>'time_other','label'=>'その他','sub'=>''],
    ],null);
    _iq($db,$ja,3,120,'support_program','radio',
        'このサポートプログラムへの応募を希望しますか？','','',1,[
        ['value'=>'yes','label'=>'はい、応募を希望します。','sub'=>''],
        ['value'=>'undecided','label'=>'まだ決めていません。詳しく話を聞きたいです。','sub'=>''],
        ['value'=>'no','label'=>'いいえ、応募しません。','sub'=>''],
    ],null);
    _iq($db,$ja,3,130,'support_situation','textarea',
        '13. 現在のご状況とサポート枠を希望する理由',
        '現在の生活・就業・家庭のご状況について、差し支えない範囲で具体的に記入してください。特に、サポート枠を希望される理由が分かるように、現在の就業状況、収入面での不安、子育て・介護などご家庭の事情、学習や就労にあたって課題になっていることなどを聞かせてください。',
        '現在のご状況をご記入ください...',1,[],1000);
    _iq($db,$ja,3,140,'other_questions','textarea',
        '14. その他に、気になることや事前に相談しておきたいことがあれば、自由に記入してください',
        '','回答を入力してください',0,[],null);
    _iq($db,$ja,3,150,'confirm_submit','radio',
        '15. この内容で申し込みを送信してよろしいですか？',
        '送信後の修正はできませんので、内容を確認のうえ送信してください。',
        '',1,[['value'=>'yes','label'=>'はい','sub'=>'']],null);
}

function _migrate_form_questions(PDO $db): void {
    foreach (['en-application','ja-application'] as $slug) {
        $st = $db->prepare("SELECT id FROM forms WHERE slug=?");
        $st->execute([$slug]);
        $fid = $st->fetchColumn();
        if (!$fid) continue;
        $exists = $db->prepare("SELECT COUNT(*) FROM form_questions WHERE form_id=? AND field_name='support_situation'");
        $exists->execute([$fid]);
        if ((int)$exists->fetchColumn() > 0) continue;

        if ($slug === 'en-application') {
            _iq($db,(int)$fid,3,130,'support_situation','textarea',
                '13. Current Situation and Reason for Requesting the Support Program',
                'Please describe your current living, employment, and family situation in as much detail as you feel comfortable sharing. In particular, please help us understand why you are requesting support by explaining your current employment status, financial concerns, family responsibilities such as childcare or caregiving, and any challenges you are facing in pursuing your studies or finding employment.',
                'Please describe your current situation...',1,[],1000);
            _iq($db,(int)$fid,3,140,'other_questions','textarea',
                '14. If you have any questions, concerns, or topics you would like to discuss in advance, please feel free to enter them below.',
                '','Enter any questions or comments (optional)...',0,[],null);
            _iq($db,(int)$fid,3,150,'confirm_submit','radio',
                '15. Would you like to submit your application with the information provided above?',
                'Please review your information carefully before submitting, as changes cannot be made after submission.',
                '',1,[['value'=>'yes','label'=>'Yes','sub'=>'']],null);
        } else {
            _iq($db,(int)$fid,3,130,'support_situation','textarea',
                '13. 現在のご状況とサポート枠を希望する理由',
                '現在の生活・就業・家庭のご状況について、差し支えない範囲で具体的に記入してください。特に、サポート枠を希望される理由が分かるように、現在の就業状況、収入面での不安、子育て・介護などご家庭の事情、学習や就労にあたって課題になっていることなどを聞かせてください。',
                '現在のご状況をご記入ください...',1,[],1000);
            _iq($db,(int)$fid,3,140,'other_questions','textarea',
                '14. その他に、気になることや事前に相談しておきたいことがあれば、自由に記入してください',
                '','回答を入力してください',0,[],null);
            _iq($db,(int)$fid,3,150,'confirm_submit','radio',
                '15. この内容で申し込みを送信してよろしいですか？',
                '送信後の修正はできませんので、内容を確認のうえ送信してください。',
                '',1,[['value'=>'yes','label'=>'はい','sub'=>'']],null);
        }
    }
}

function get_form_questions(string $slug): array {
    // File cache — avoids a Supabase round-trip on every apply-page view
    // Bump CACHE_VER to bust stale caches after question content changes
    define('CACHE_VER', '2');
    $cache = dirname(__DIR__, 2) . '/db/.fq_' . preg_replace('/[^a-z0-9_-]/', '', $slug) . '_v' . CACHE_VER . '.json';
    if (file_exists($cache) && (time() - filemtime($cache)) < 3600) {
        return json_decode(file_get_contents($cache), true) ?: [];
    }
    $db = get_db();
    $st = $db->prepare("
        SELECT q.* FROM form_questions q
        JOIN forms f ON f.id = q.form_id
        WHERE f.slug = ? AND q.active = 1
        ORDER BY q.sort_order ASC
    ");
    $st->execute([$slug]);
    $rows = $st->fetchAll();
    foreach ($rows as &$row) {
        $row['options'] = json_decode($row['options_json'] ?? '[]', true) ?: [];
    }
    $cache_dir = dirname($cache);
    if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
    @file_put_contents($cache, json_encode($rows, JSON_UNESCAPED_UNICODE));
    return $rows;
}

function bust_form_questions_cache(string $slug): void {
    $cache = dirname(__DIR__, 2) . '/db/.fq_' . preg_replace('/[^a-z0-9_-]/', '', $slug) . '_v' . CACHE_VER . '.json';
    @unlink($cache);
}

function get_content(string $key, string $lang, string $default = ''): string {
    try {
        $db = get_db();
        $st = $db->prepare("SELECT value FROM site_content WHERE content_key=? AND lang=?");
        $st->execute([$key, $lang]);
        $row = $st->fetch();
        return $row ? $row['value'] : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}
